<?php

declare(strict_types=1);

namespace Application\Services\Communication;

use Application\Models\CartaoCredito;
use Application\Models\Notificacao;
use Application\Services\Infrastructure\LogService;
use Application\Services\Mail\EmailTemplate;
use DateTimeImmutable;
use Throwable;

class FaturaReminderDispatchService
{
    public function __construct(
        private readonly ?MailService $mailService = null
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function dispatch(): array
    {
        $now = new DateTimeImmutable('now');
        $mesAtual = $now->format('Y-m');
        $linkFaturas = $this->buildLink('/faturas');
        $mailService = $this->getMailService();

        $stats = [
            'processed' => 0,
            'sent' => 0,
            'ignored' => 0,
            'inapp_notifications' => 0,
            'emails_sent' => 0,
            'errors' => 0,
        ];

        LogService::info('=== [dispatch_fatura_reminders] Inicio do lembrete de faturas ===');

        $cartoes = CartaoCredito::with(['usuario:id,nome,email'])
            ->where('ativo', true)
            ->where('arquivado', false)
            ->whereNotNull('dia_vencimento')
            ->whereNotNull('lembrar_fatura_antes_segundos')
            ->where('lembrar_fatura_antes_segundos', '>', 0)
            ->where(function ($q) use ($mesAtual) {
                $q->whereNull('fatura_notificado_mes')
                    ->orWhere('fatura_notificado_mes', '<', $mesAtual);
            })
            ->get();

        $stats['processed'] = count($cartoes);

        foreach ($cartoes as $cartao) {
            $diaVencimento = (int) $cartao->dia_vencimento;
            $leadSeconds = (int) $cartao->lembrar_fatura_antes_segundos;

            $mesRef = (int) $now->format('n');
            $anoRef = (int) $now->format('Y');
            $diaAtual = (int) $now->format('j');

            if ($diaAtual > $diaVencimento) {
                $mesRef++;
                if ($mesRef > 12) {
                    $mesRef = 1;
                    $anoRef++;
                }
            }

            $diaReal = min($diaVencimento, (int) date('t', mktime(0, 0, 0, $mesRef, 1, $anoRef)));
            $dataVencimento = new DateTimeImmutable(
                sprintf('%04d-%02d-%02d 12:00:00', $anoRef, $mesRef, $diaReal)
            );

            $mesNotificacao = $dataVencimento->format('Y-m');
            if ($cartao->fatura_notificado_mes === $mesNotificacao) {
                $stats['ignored']++;
                continue;
            }

            $reminderTimestamp = $dataVencimento->getTimestamp() - $leadSeconds;
            $nowTs = $now->getTimestamp();
            $limiteAtraso = $nowTs - (24 * 3600);

            if ($dataVencimento->getTimestamp() < $limiteAtraso || $reminderTimestamp > $nowTs) {
                $stats['ignored']++;
                continue;
            }

            $segundosRestantes = $dataVencimento->getTimestamp() - $nowTs;
            $tempoRestante = $this->formatRemainingTime($segundosRestantes);
            $usuario = $cartao->usuario;

            if (!$usuario) {
                $stats['ignored']++;
                continue;
            }

            $mensagem = $segundosRestantes > 0
                ? sprintf(
                    'A fatura do cartao %s vence em %s (%s).',
                    $cartao->nome_cartao,
                    $tempoRestante,
                    $dataVencimento->format('d/m/Y')
                )
                : sprintf(
                    'A fatura do cartao %s vence hoje (%s)!',
                    $cartao->nome_cartao,
                    $dataVencimento->format('d/m/Y')
                );

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
                    $stats['inapp_notifications']++;
                } catch (Throwable $e) {
                    $stats['errors']++;
                    LogService::error('[dispatch_fatura_reminders] Erro ao criar notificacao in-app', [
                        'cartao_id' => $cartao->id,
                        'erro' => $e->getMessage(),
                    ]);
                }
            }

            if ($cartao->fatura_canal_email && $mailService->isConfigured() && $usuario->email) {
                try {
                    $assunto = $segundosRestantes > 0
                        ? "Lembrete: Fatura do {$cartao->nome_cartao} vence em {$tempoRestante}"
                        : "Lembrete: Fatura do {$cartao->nome_cartao} vence HOJE!";

                    $nomeCartao = htmlspecialchars($cartao->nome_cartao, ENT_QUOTES, 'UTF-8');
                    $nomeUsuario = htmlspecialchars($usuario->nome, ENT_QUOTES, 'UTF-8');
                    $dataFormatada = $dataVencimento->format('d/m/Y');

                    $conteudo = "
                        <p style='font-size: 15px; line-height: 1.8; color: #5a6c7d; margin: 0 0 20px 0;'>
                            Ola, {$nomeUsuario}!
                        </p>
                        <p style='font-size: 15px; line-height: 1.8; color: #5a6c7d; margin: 0 0 20px 0;'>
                            {$mensagem}
                        </p>
                        <p style='font-size: 15px; line-height: 1.8; color: #e74c3c; margin: 0 0 20px 0; font-weight: 600;'>
                            Nao esqueca de efetuar o pagamento para evitar juros e multas.
                        </p>
                    ";

                    if ($linkFaturas) {
                        $conteudo .= "
                            <div style='text-align: center; margin: 32px 0;'>
                                <a href='{$linkFaturas}'
                                   style='display: inline-block; padding: 14px 28px; border-radius: 10px;
                                          background: #e67e22; color: #ffffff; text-decoration: none; font-weight: 600;'>
                                    Ver minhas faturas
                                </a>
                            </div>
                        ";
                    }

                    $corpo = EmailTemplate::wrap(
                        'Lembrete de Fatura',
                        '#e67e22',
                        'Lembrete de Fatura',
                        "Cartao {$nomeCartao} - Vencimento {$dataFormatada}",
                        $conteudo,
                        'Este e um lembrete automatico configurado por voce no Lukrato.'
                    );

                    $mailService->send(
                        $usuario->email,
                        $usuario->nome,
                        $assunto,
                        $corpo
                    );
                    $stats['emails_sent']++;
                } catch (Throwable $e) {
                    $stats['errors']++;
                    LogService::error('[dispatch_fatura_reminders] Erro ao enviar email', [
                        'cartao_id' => $cartao->id,
                        'erro' => $e->getMessage(),
                    ]);
                }
            }

            $cartao->fatura_notificado_mes = $mesNotificacao;
            $cartao->save();
            $stats['sent']++;
        }

        LogService::info('=== [dispatch_fatura_reminders] Execucao finalizada com sucesso ===', $stats);

        return $stats;
    }

    private function formatRemainingTime(int $segundosRestantes): string
    {
        if ($segundosRestantes > 86400) {
            $dias = (int) floor($segundosRestantes / 86400);
            return $dias . ' dia' . ($dias > 1 ? 's' : '');
        }

        if ($segundosRestantes > 3600) {
            $horas = (int) floor($segundosRestantes / 3600);
            return $horas . ' hora' . ($horas > 1 ? 's' : '');
        }

        if ($segundosRestantes > 0) {
            return 'algumas horas';
        }

        return 'hoje';
    }

    private function buildLink(string $path): ?string
    {
        $baseUrl = defined('BASE_URL')
            ? rtrim(BASE_URL, '/')
            : rtrim($_ENV['APP_URL'] ?? '', '/');

        return $baseUrl !== '' ? $baseUrl . $path : null;
    }

    private function getMailService(): MailService
    {
        return $this->mailService ?? new MailService();
    }
}
