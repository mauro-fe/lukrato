<?php

namespace Application\Services;

use Illuminate\Database\Capsule\Manager as DB;

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
            $record = [
                'user_id' => $data['user_id'] ?? null,
                'assinatura_id' => $data['assinatura_id'] ?? null,
                'action' => $data['action'] ?? 'unknown',
                'status_anterior' => $data['status_anterior'] ?? null,
                'status_novo' => $data['status_novo'] ?? null,
                'external_id' => $data['external_id'] ?? null,
                'valor' => $data['valor'] ?? null,
                'metadata' => !empty($data['metadata']) ? json_encode($data['metadata']) : null,
                'ip_address' => self::getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => now(),
            ];

            DB::table('auditoria_cobrancas')->insert($record);
        } catch (\Throwable $e) {
            // Não falhar a operação principal por erro de log
            if (class_exists(LogService::class)) {
                LogService::error('Erro ao registrar auditoria de cobrança', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                ]);
            }
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
            if (class_exists(LogService::class)) {
                LogService::error('Erro ao verificar cobranças duplicadas', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                ]);
            }
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
            if (class_exists(LogService::class)) {
                LogService::critical('🚨 COBRANÇA DUPLICADA DETECTADA', $data);
            }

            // Enviar email para admin (se configurado)
            self::notifyDuplicateCharge($data);
        } catch (\Throwable $e) {
            if (class_exists(LogService::class)) {
                LogService::error('Erro ao registrar cobrança duplicada', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                ]);
            }
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

    private static function getClientIp(): ?string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    private static function notifyDuplicateCharge(array $data): void
    {
        // Implementar notificação (email, Slack, etc)
        // Exemplo: enviar para MailService se existir
        try {
            $adminEmail = $_ENV['ADMIN_EMAIL'] ?? null;

            if ($adminEmail && class_exists(\Application\Services\MailService::class)) {
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
}
