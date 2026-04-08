<?php

declare(strict_types=1);

namespace Application\Services\Communication;

use Application\Container\ApplicationContainer;
use Application\Models\Lancamento;
use Application\Models\Notificacao;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;
use Application\Services\Infrastructure\LogService;

class LancamentoReminderDispatchService
{
    private MailService $mailService;

    public function __construct(
        ?MailService $mailService = null
    ) {
        $this->mailService = ApplicationContainer::resolveOrNew($mailService, MailService::class);
    }

    /**
     * @return array<string, int|string>
     */
    public function dispatch(): array
    {
        $now = new DateTimeImmutable('now');
        $windowLimit = $now->modify('+10 minutes');
        $linkLancamentos = $this->buildLink('/lancamentos');
        $mailService = $this->getMailService();

        $stats = [
            'processed' => 0,
            'updated' => 0,
            'skipped_old' => 0,
            'waiting' => 0,
            'antecedencia_notifications' => 0,
            'antecedencia_emails' => 0,
            'day_notifications' => 0,
            'day_emails' => 0,
            'errors' => 0,
        ];

        LogService::info('=== [dispatch_reminders] Inicio do lembrete de lancamentos ===');

        $lancamentos = Lancamento::with(['usuario:id,nome,email'])
            ->where('pago', 0)
            ->whereNull('cancelado_em')
            ->whereNotNull('lembrar_antes_segundos')
            ->where('lembrar_antes_segundos', '>', 0)
            ->where(function ($query) {
                $query->where('canal_email', true)
                    ->orWhere('canal_inapp', true);
            })
            ->where(function ($query) {
                $query->whereNull('lembrete_antecedencia_em')
                    ->orWhereNull('notificado_em');
            })
            ->orderBy('data', 'asc')
            ->get();

        $stats['processed'] = count($lancamentos);

        foreach ($lancamentos as $lancamento) {
            $dataLanc = $this->resolveDate($lancamento->data);
            $pagamentoTs = $dataLanc->setTime(12, 0)->getTimestamp();
            $leadSeconds = (int) ($lancamento->lembrar_antes_segundos ?? 0);
            $reminderTimestamp = $pagamentoTs - $leadSeconds;
            $nowTs = $now->getTimestamp();
            $windowEnd = $windowLimit->getTimestamp();
            $momentoHorarioChegou = ($pagamentoTs <= $windowEnd);

            $limiteAtraso = $nowTs - (24 * 3600);
            if ($pagamentoTs < $limiteAtraso) {
                $stats['skipped_old']++;
                continue;
            }

            $enviouAlgo = false;

            $temAntecedencia = $leadSeconds > 0;
            $antecedenciaNaoEnviada = empty($lancamento->lembrete_antecedencia_em);
            $momentoAntecedenciaChegou = ($reminderTimestamp <= $windowEnd);

            if ($temAntecedencia && $antecedenciaNaoEnviada && $momentoAntecedenciaChegou && !$momentoHorarioChegou) {
                $segundosRestantes = $pagamentoTs - $nowTs;
                $tempoRestante = $this->formatRemainingTime($segundosRestantes);
                $antecedenciaEnviada = false;

                if ($lancamento->canal_inapp) {
                    try {
                        Notificacao::create([
                            'user_id' => $lancamento->user_id,
                            'tipo' => 'lancamento',
                            'titulo' => 'Lembrete de lancamento',
                            'mensagem' => sprintf(
                                'Lembrete: %s (%s) vence em %s (%s).',
                                $lancamento->descricao,
                                $lancamento->tipo,
                                $tempoRestante,
                                $dataLanc->format('d/m/Y')
                            ),
                            'link' => $linkLancamentos,
                            'lida' => 0,
                        ]);
                        $antecedenciaEnviada = true;
                        $stats['antecedencia_notifications']++;
                    } catch (Throwable $exception) {
                        $stats['errors']++;
                        LogService::error('[dispatch_reminders] Falha ao criar notificacao in-app de antecedencia', [
                            'erro' => $exception->getMessage(),
                            'lancamento_id' => $lancamento->id,
                        ]);
                    }
                }

                if ($lancamento->canal_email && $mailService->isConfigured()) {
                    $usuario = $lancamento->usuario;
                    if ($usuario && !empty($usuario->email)) {
                        try {
                            $mailService->sendLancamentoReminder($lancamento, $usuario, 'antecedencia');
                            $antecedenciaEnviada = true;
                            $stats['antecedencia_emails']++;
                        } catch (Throwable $exception) {
                            $stats['errors']++;
                            LogService::error('[dispatch_reminders] Falha ao enviar email de antecedencia', [
                                'erro' => $exception->getMessage(),
                                'lancamento_id' => $lancamento->id,
                            ]);
                        }
                    }
                }

                if ($antecedenciaEnviada) {
                    $lancamento->lembrete_antecedencia_em = $now->format('Y-m-d H:i:s');
                    $enviouAlgo = true;
                }
            }

            $horarioNaoEnviado = empty($lancamento->notificado_em);
            if ($horarioNaoEnviado && $momentoHorarioChegou) {
                $horarioEnviado = false;

                if ($lancamento->canal_inapp) {
                    try {
                        Notificacao::create([
                            'user_id' => $lancamento->user_id,
                            'tipo' => 'lancamento',
                            'titulo' => 'Lancamento vence hoje!',
                            'mensagem' => sprintf(
                                'Atencao: %s (%s) vence hoje! (%s)',
                                $lancamento->descricao,
                                $lancamento->tipo,
                                $dataLanc->format('d/m/Y')
                            ),
                            'link' => $linkLancamentos,
                            'lida' => 0,
                        ]);
                        $horarioEnviado = true;
                        $stats['day_notifications']++;
                    } catch (Throwable $exception) {
                        $stats['errors']++;
                        LogService::error('[dispatch_reminders] Falha ao criar notificacao in-app no dia', [
                            'erro' => $exception->getMessage(),
                            'lancamento_id' => $lancamento->id,
                        ]);
                    }
                }

                if ($lancamento->canal_email && $mailService->isConfigured()) {
                    $usuario = $lancamento->usuario;
                    if ($usuario && !empty($usuario->email)) {
                        try {
                            $mailService->sendLancamentoReminder($lancamento, $usuario, 'horario');
                            $horarioEnviado = true;
                            $stats['day_emails']++;
                        } catch (Throwable $exception) {
                            $stats['errors']++;
                            LogService::error('[dispatch_reminders] Falha ao enviar email no dia', [
                                'erro' => $exception->getMessage(),
                                'lancamento_id' => $lancamento->id,
                            ]);
                        }
                    }
                }

                if ($horarioEnviado) {
                    $lancamento->notificado_em = $now->format('Y-m-d H:i:s');
                    $enviouAlgo = true;
                }
            }

            if ($enviouAlgo) {
                $lancamento->save();
                $stats['updated']++;
                continue;
            }

            $stats['waiting']++;
        }

        LogService::info('=== [dispatch_reminders] Execucao finalizada com sucesso ===', $stats);

        return $stats;
    }

    private function resolveDate(mixed $value): DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
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

        if ($segundosRestantes > 60) {
            $minutos = (int) floor($segundosRestantes / 60);
            return $minutos . ' minuto' . ($minutos > 1 ? 's' : '');
        }

        return 'alguns instantes';
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
        return $this->mailService;
    }
}
