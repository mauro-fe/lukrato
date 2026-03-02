<?php

/**
 * Dispatch Fatura Reminders
 *
 * Envia lembretes de vencimento de faturas de cartão de crédito.
 * Usa os campos dia_vencimento do cartão e lembrar_fatura_antes_segundos
 * para calcular quando o lembrete deve ser disparado.
 *
 * Executar via cron: php cli/dispatch_fatura_reminders.php
 * Recomendado: a cada 1 hora
 */

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Application\Models\Notificacao;
use Application\Services\Communication\MailService;
use Application\Services\Infrastructure\LogService;

LogService::info('=== [dispatch_fatura_reminders] Inicio do lembrete de faturas ===');

try {
    $now = new \DateTimeImmutable('now');
    $mesAtual = $now->format('Y-m'); // ex: 2026-02

    $baseUrl = defined('BASE_URL')
        ? rtrim(BASE_URL, '/')
        : rtrim($_ENV['APP_URL'] ?? '', '/');
    $linkFaturas = $baseUrl ? $baseUrl . '/faturas' : null;

    $mailService = new MailService();

    // Buscar cartões ativos com lembrete configurado
    $cartoes = CartaoCredito::with(['usuario:id,nome,email'])
        ->where('ativo', true)
        ->where('arquivado', false)
        ->whereNotNull('dia_vencimento')
        ->whereNotNull('lembrar_fatura_antes_segundos')
        ->where('lembrar_fatura_antes_segundos', '>', 0)
        ->where(function ($q) use ($mesAtual) {
            // Não notificou esse mês ainda
            $q->whereNull('fatura_notificado_mes')
                ->orWhere('fatura_notificado_mes', '<', $mesAtual);
        })
        ->get();

    $count = count($cartoes);
    LogService::info("[dispatch_fatura_reminders] Cartões com lembrete para processar: {$count}");

    $enviados = 0;
    $ignorados = 0;

    foreach ($cartoes as $cartao) {
        $diaVencimento = (int) $cartao->dia_vencimento;
        $leadSeconds = (int) $cartao->lembrar_fatura_antes_segundos;

        // Calcular a data de vencimento da fatura atual
        // Se estamos no meio do mês e o vencimento já passou, calcular para o próximo mês
        $mesRef = (int) $now->format('n');
        $anoRef = (int) $now->format('Y');
        $diaAtual = (int) $now->format('j');

        // Se o vencimento já passou este mês E já notificou este mês, pular
        // (isso já é filtrado pela query, mas reforçamos)
        if ($diaAtual > $diaVencimento) {
            // O vencimento deste mês já passou; próximo vencimento é mês que vem
            $mesRef++;
            if ($mesRef > 12) {
                $mesRef = 1;
                $anoRef++;
            }
        }

        // Montar a data de vencimento
        $diaReal = min($diaVencimento, (int) date('t', mktime(0, 0, 0, $mesRef, 1, $anoRef)));
        $dataVencimento = new \DateTimeImmutable(
            sprintf('%04d-%02d-%02d 12:00:00', $anoRef, $mesRef, $diaReal)
        );

        $mesNotificacao = $dataVencimento->format('Y-m');

        // Se já notificamos para este mês de vencimento, pular
        if ($cartao->fatura_notificado_mes === $mesNotificacao) {
            $ignorados++;
            continue;
        }

        // Calcular quando o lembrete deve disparar
        $reminderTimestamp = $dataVencimento->getTimestamp() - $leadSeconds;
        $nowTs = $now->getTimestamp();

        // Só disparar se o momento do lembrete já chegou
        // E o vencimento ainda não passou (ou passou há menos de 24h)
        $limiteAtraso = $nowTs - (24 * 3600);
        if ($dataVencimento->getTimestamp() < $limiteAtraso) {
            LogService::info(sprintf(
                "[dispatch_fatura_reminders] Ignorado cartão #%d (%s): vencimento muito antigo (%s)",
                $cartao->id,
                $cartao->nome_cartao,
                $dataVencimento->format('d/m/Y')
            ));
            $ignorados++;
            continue;
        }

        if ($reminderTimestamp > $nowTs) {
            // Ainda não chegou o momento do lembrete
            $ignorados++;
            continue;
        }

        // ===== DISPARAR LEMBRETE =====
        $segundosRestantes = $dataVencimento->getTimestamp() - $nowTs;
        $tempoRestante = '';
        if ($segundosRestantes > 86400) {
            $dias = floor($segundosRestantes / 86400);
            $tempoRestante = $dias . ' dia' . ($dias > 1 ? 's' : '');
        } elseif ($segundosRestantes > 3600) {
            $horas = floor($segundosRestantes / 3600);
            $tempoRestante = $horas . ' hora' . ($horas > 1 ? 's' : '');
        } elseif ($segundosRestantes > 0) {
            $tempoRestante = 'algumas horas';
        } else {
            $tempoRestante = 'hoje';
        }

        $usuario = $cartao->usuario;
        if (!$usuario) {
            $ignorados++;
            continue;
        }

        LogService::info(sprintf(
            "[dispatch_fatura_reminders] Enviando lembrete para cartão #%d (%s) - vencimento %s",
            $cartao->id,
            $cartao->nome_cartao,
            $dataVencimento->format('d/m/Y')
        ));

        $mensagem = $segundosRestantes > 0
            ? sprintf(
                'A fatura do cartão %s vence em %s (%s).',
                $cartao->nome_cartao,
                $tempoRestante,
                $dataVencimento->format('d/m/Y')
            )
            : sprintf(
                'A fatura do cartão %s vence hoje (%s)!',
                $cartao->nome_cartao,
                $dataVencimento->format('d/m/Y')
            );

        // Notificação in-app
        if ($cartao->fatura_canal_inapp) {
            try {
                Notificacao::create([
                    'user_id' => $cartao->user_id,
                    'tipo' => 'fatura',
                    'titulo' => 'Lembrete de fatura',
                    'mensagem' => $mensagem,
                    'link' => $linkFaturas,
                    'lida' => 0,
                ]);
                LogService::info("[dispatch_fatura_reminders] Notificação in-app criada para user #{$cartao->user_id}");
            } catch (\Throwable $e) {
                LogService::error("[dispatch_fatura_reminders] Erro ao criar notificação in-app: " . $e->getMessage());
            }
        }

        // Notificação por email
        if ($cartao->fatura_canal_email && $mailService->isConfigured()) {
            if ($usuario->email) {
                try {
                    $assunto = $segundosRestantes > 0
                        ? "Lembrete: Fatura do {$cartao->nome_cartao} vence em {$tempoRestante}"
                        : "Lembrete: Fatura do {$cartao->nome_cartao} vence HOJE!";

                    $corpo = "<p>Olá, {$usuario->nome}!</p>"
                        . "<p>{$mensagem}</p>"
                        . "<p>Não esqueça de efetuar o pagamento para evitar juros e multas.</p>"
                        . ($linkFaturas ? "<p><a href=\"{$linkFaturas}\">Ver minhas faturas</a></p>" : '');

                    $mailService->send(
                        $usuario->email,
                        $assunto,
                        $corpo
                    );
                    LogService::info("[dispatch_fatura_reminders] Email enviado para {$usuario->email}");
                } catch (\Throwable $e) {
                    LogService::error("[dispatch_fatura_reminders] Erro ao enviar email: " . $e->getMessage());
                }
            }
        }

        // Marcar como notificado para este mês
        $cartao->fatura_notificado_mes = $mesNotificacao;
        $cartao->save();

        $enviados++;
    }

    LogService::info(sprintf(
        "[dispatch_fatura_reminders] Concluído: %d lembretes enviados, %d ignorados",
        $enviados,
        $ignorados
    ));
} catch (\Throwable $e) {
    LogService::error('[dispatch_fatura_reminders] Erro fatal: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
    echo "ERRO FATAL: {$e->getMessage()}\n";
    exit(1);
}
