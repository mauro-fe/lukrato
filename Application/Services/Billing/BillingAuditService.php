<?php

declare(strict_types=1);

namespace Application\Services\Billing;

use Application\Config\BillingRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Enums\LogLevel;
use Application\Enums\LogCategory;
use Application\Services\Infrastructure\LogService;

/**
 * Serviço de Auditoria Financeira
 * Registra todas as operações críticas de cobrança
 */
class BillingAuditService
{
    /**
     * Registra ação de cobrança
     */
    public static function log(array $data): void
    {
        try {
            $request = self::request();

            $record = [
                'user_id' => $data['user_id'] ?? null,
                'assinatura_id' => $data['assinatura_id'] ?? null,
                'action' => $data['action'] ?? 'unknown',
                'status_anterior' => $data['status_anterior'] ?? null,
                'status_novo' => $data['status_novo'] ?? null,
                'external_id' => $data['external_id'] ?? null,
                'valor' => $data['valor'] ?? null,
                'metadata' => !empty($data['metadata']) ? json_encode($data['metadata']) : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('user-agent'),
                'created_at' => now(),
            ];

            DB::table('auditoria_cobrancas')->insert($record);
        } catch (\Throwable $e) {
            // Não falhar a operação principal por erro de log
            LogService::captureException($e, LogCategory::PAYMENT, [
                'action' => 'billing_audit_log',
                'data' => $data,
            ]);
        }
    }

    /**
     * Detecta possível cobrança duplicada
     */
    public static function checkDuplicateCharges(int $userId, int $minutes = 5): ?array
    {
        try {
            $since = now()->subMinutes($minutes);

            $charges = DB::table('auditoria_cobrancas')
                ->where('user_id', $userId)
                ->where('action', 'checkout')
                ->where('created_at', '>=', $since)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($charges->count() < 2) {
                return null;
            }

            // Agrupar por valor similar
            $grouped = [];
            foreach ($charges as $charge) {
                $valor = $charge->valor ?? 0;
                $key = round($valor, 0); // Agrupar por valores similares

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [];
                }
                $grouped[$key][] = $charge;
            }

            // Procurar duplicatas
            foreach ($grouped as $valor => $group) {
                if (count($group) >= 2) {
                    return [
                        'user_id' => $userId,
                        'valor' => $valor,
                        'quantidade' => count($group),
                        'charges' => $group,
                    ];
                }
            }

            return null;
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::PAYMENT, [
                'action' => 'check_duplicate_charges',
                'user_id' => $userId,
            ]);
            return null;
        }
    }

    /**
     * Registra cobrança duplicada detectada
     */
    public static function reportDuplicateCharge(array $data): void
    {
        try {
            $record = [
                'user_id' => $data['user_id'],
                'external_id' => $data['external_id'] ?? null,
                'valor' => $data['valor'] ?? 0,
                'status' => $data['status'] ?? 'pending',
                'detalhes' => json_encode($data['detalhes'] ?? []),
                'estornado' => false,
                'detectado_em' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('cobrancas_duplicadas')->insert($record);

            // Alertar
            LogService::persist(
                LogLevel::CRITICAL,
                LogCategory::PAYMENT,
                'COBRANÇA DUPLICADA DETECTADA',
                $data,
            );

            // Enviar email para admin (se configurado)
            self::notifyDuplicateCharge($data);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::PAYMENT, [
                'action' => 'report_duplicate_charge',
                'data' => $data,
            ]);
        }
    }

    /**
     * Busca histórico de auditoria de um usuário
     */
    public static function getUserHistory(int $userId, int $limit = 50): array
    {
        try {
            return DB::table('auditoria_cobrancas')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Estatísticas de cobranças
     */
    public static function getStats(?\DateTime $since = null): array
    {
        try {
            $query = DB::table('auditoria_cobrancas');

            if ($since) {
                $query->where('created_at', '>=', $since);
            }

            return [
                'total_checkouts' => $query->where('action', 'checkout')->count(),
                'total_cancels' => $query->where('action', 'cancel')->count(),
                'total_webhooks' => $query->where('action', 'webhook')->count(),
                'usuarios_unicos' => $query->distinct('user_id')->count(),
                'valor_total' => $query->sum('valor'),
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function request(): Request
    {
        return ApplicationContainer::resolveOrNew(null, Request::class);
    }

    private static function notifyDuplicateCharge(array $data): void
    {
        // Implementar notificação (email, Slack, etc)
        // Exemplo: enviar para MailService se existir
        try {
            $adminEmail = self::runtimeConfig()->adminEmail();

            if ($adminEmail && class_exists(\Application\Services\Communication\MailService::class)) {
                $subject = '🚨 Cobrança Duplicada Detectada';
                $body = "Uma possível cobrança duplicada foi detectada:\n\n";
                $body .= "Usuário ID: {$data['user_id']}\n";
                $body .= "Valor: R$ " . number_format($data['valor'] ?? 0, 2, ',', '.') . "\n";
                $body .= "External ID: {$data['external_id']}\n";
                $body .= "Horário: " . date('d/m/Y H:i:s') . "\n";

                // Mail service aqui
            }
        } catch (\Throwable $e) {
            // Não falhar por erro de notificação
        }
    }

    private static function runtimeConfig(): BillingRuntimeConfig
    {
        return ApplicationContainer::resolveOrNew(null, BillingRuntimeConfig::class);
    }
}
