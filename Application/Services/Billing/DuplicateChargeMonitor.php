<?php

namespace Application\Services\Billing;

use Application\Services\Billing\BillingAuditService;
use Application\Services\Infrastructure\LogService;
use Application\Enums\LogLevel;
use Application\Enums\LogCategory;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Monitor de Cobranças Duplicadas
 * Executa verificações periódicas e alerta sobre problemas
 */
class DuplicateChargeMonitor
{
    /**
     * Executa verificação de cobranças duplicadas
     * Deve ser chamado por um cron job a cada 5 minutos
     */
    public static function run(): array
    {
        $results = [
            'checked_users' => 0,
            'duplicates_found' => 0,
            'alerts_sent' => 0,
        ];

        try {
            // Buscar usuários com checkout recente (últimos 10 minutos)
            $recentCheckouts = DB::table('auditoria_cobrancas')
                ->where('action', 'checkout')
                ->where('created_at', '>=', now()->subMinutes(10))
                ->distinct()
                ->pluck('user_id');

            $results['checked_users'] = count($recentCheckouts);

            foreach ($recentCheckouts as $userId) {
                $duplicate = BillingAuditService::checkDuplicateCharges($userId, 10);

                if ($duplicate) {
                    $results['duplicates_found']++;

                    // Verificar se já não foi reportado
                    $alreadyReported = DB::table('cobrancas_duplicadas')
                        ->where('user_id', $userId)
                        ->where('detectado_em', '>=', now()->subMinutes(10))
                        ->exists();

                    if (!$alreadyReported) {
                        // Coletar detalhes
                        $charges = $duplicate['charges'];
                        $externalIds = array_column($charges, 'external_id');

                        BillingAuditService::reportDuplicateCharge([
                            'user_id' => $userId,
                            'external_id' => implode(',', array_filter($externalIds)),
                            'valor' => $duplicate['valor'],
                            'status' => 'pending_review',
                            'detalhes' => [
                                'quantidade' => $duplicate['quantidade'],
                                'charges' => $charges,
                                'detectado_em' => now()->toIso8601String(),
                            ],
                        ]);

                        $results['alerts_sent']++;

                        // Enviar notificação imediata
                        self::sendImmediateAlert($userId, $duplicate);
                    }
                }
            }
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::PAYMENT, [
                'action' => 'duplicate_charge_monitor',
            ]);
        }

        return $results;
    }

    /**
     * Verifica cobranças não resolvidas
     */
    public static function checkUnresolvedDuplicates(): array
    {
        try {
            $unresolved = DB::table('cobrancas_duplicadas')
                ->where('estornado', false)
                ->where('resolvido_em', null)
                ->where('detectado_em', '<=', now()->subHours(1))
                ->get();

            $results = [];

            foreach ($unresolved as $duplicate) {
                $results[] = [
                    'id' => $duplicate->id,
                    'user_id' => $duplicate->user_id,
                    'external_id' => $duplicate->external_id,
                    'valor' => $duplicate->valor,
                    'detectado_ha' => now()->diffInHours($duplicate->detectado_em) . ' horas',
                ];

                // Alertar novamente se passou muito tempo
                if (now()->diffInHours($duplicate->detectado_em) >= 24) {
                    self::sendCriticalAlert($duplicate);
                }
            }

            return $results;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Marca cobrança duplicada como resolvida
     */
    public static function markAsResolved(int $id, string $resolution): bool
    {
        try {
            DB::table('cobrancas_duplicadas')
                ->where('id', $id)
                ->update([
                    'estornado' => true,
                    'resolvido_em' => now(),
                    'detalhes' => DB::raw("JSON_SET(detalhes, '$.resolution', '$resolution')"),
                    'updated_at' => now(),
                ]);

            if (class_exists(LogService::class)) {
                LogService::info('Cobrança duplicada marcada como resolvida', [
                    'id' => $id,
                    'resolution' => $resolution,
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function sendImmediateAlert(int $userId, array $duplicate): void
    {
        try {
            $message = sprintf(
                "🚨 Cobrança duplicada detectada!\n\n" .
                    "Usuário ID: %d\n" .
                    "Quantidade de cobranças: %d\n" .
                    "Valor: R$ %.2f\n" .
                    "Horário: %s\n\n" .
                    "⚠️ Requer atenção imediata!",
                $userId,
                $duplicate['quantidade'],
                $duplicate['valor'],
                date('d/m/Y H:i:s')
            );

            // Logar como crítico
            if (class_exists(LogService::class)) {
                LogService::critical($message, $duplicate);
            }

            // Enviar para canais de alerta
            self::notifyAdmins($message, $duplicate);
        } catch (\Throwable $e) {
            // Não falhar por erro de notificação
        }
    }

    private static function sendCriticalAlert($duplicate): void
    {
        try {
            $message = sprintf(
                "🔴 CRÍTICO: Cobrança duplicada NÃO RESOLVIDA há %s horas!\n\n" .
                    "ID: %d\n" .
                    "Usuário ID: %d\n" .
                    "External ID: %s\n" .
                    "Valor: R$ %.2f\n" .
                    "Detectado em: %s\n\n" .
                    "⚠️⚠️⚠️ REQUER AÇÃO IMEDIATA!",
                now()->diffInHours($duplicate->detectado_em),
                $duplicate->id,
                $duplicate->user_id,
                $duplicate->external_id,
                $duplicate->valor,
                $duplicate->detectado_em
            );

            if (class_exists(LogService::class)) {
                LogService::critical($message);
            }

            self::notifyAdmins($message, (array)$duplicate);
        } catch (\Throwable $e) {
            // Não falhar
        }
    }

    private static function notifyAdmins(string $message, array $data): void
    {
        // Implementar notificações via:
        // - Email
        // - Slack
        // - Telegram
        // - SMS (Twilio)

        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? null;
        $slackWebhook = $_ENV['SLACK_WEBHOOK_URL'] ?? null;

        // Email
        if ($adminEmail && class_exists(\Application\Services\Communication\MailService::class)) {
            try {
                // Enviar email de alerta simples
                $subject = '🚨 ALERTA: Cobrança Duplicada Detectada';
                $body = nl2br($message);

                // Usar PHPMailer ou outro método disponível
                if (function_exists('mail')) {
                    mail($adminEmail, $subject, $body, [
                        'Content-Type' => 'text/html; charset=UTF-8',
                        'From' => 'noreply@lukrato.com.br',
                    ]);
                }
            } catch (\Throwable $e) {
                // Continuar mesmo se email falhar
            }
        }

        // Slack
        if ($slackWebhook) {
            try {
                $payload = json_encode([
                    'text' => $message,
                    'username' => 'Lukrato Monitor',
                    'icon_emoji' => ':rotating_light:',
                ]);

                $ch = curl_init($slackWebhook);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_exec($ch);
                curl_close($ch);
            } catch (\Throwable $e) {
                // Continuar mesmo se Slack falhar
            }
        }
    }
}
