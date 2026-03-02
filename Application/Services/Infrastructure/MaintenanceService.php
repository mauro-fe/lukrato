<?php

declare(strict_types=1);

namespace Application\Services\Infrastructure;

/**
 * Gerencia o modo de manutenção do sistema.
 * Usa arquivo de flag no storage — sem dependência de banco de dados.
 */
class MaintenanceService
{
    private const FLAG_FILE = BASE_PATH . '/storage/maintenance.flag';

    /**
     * Verifica se o sistema está em manutenção
     */
    public static function isActive(): bool
    {
        return file_exists(self::FLAG_FILE);
    }

    /**
     * Ativa o modo de manutenção
     */
    public static function activate(string $reason = '', ?int $estimatedMinutes = null): void
    {
        $data = [
            'activated_at' => date('Y-m-d H:i:s'),
            'reason'       => $reason,
            'estimated_minutes' => $estimatedMinutes,
        ];

        file_put_contents(self::FLAG_FILE, json_encode($data, JSON_PRETTY_PRINT));

        LogService::info('🔧 Modo manutenção ATIVADO', $data);
    }

    /**
     * Desativa o modo de manutenção
     */
    public static function deactivate(): void
    {
        if (file_exists(self::FLAG_FILE)) {
            unlink(self::FLAG_FILE);
        }

        LogService::info('✅ Modo manutenção DESATIVADO');
    }

    /**
     * Retorna dados da manutenção ativa (ou null)
     */
    public static function getData(): ?array
    {
        if (!self::isActive()) {
            return null;
        }

        $content = file_get_contents(self::FLAG_FILE);
        return json_decode($content, true) ?: [];
    }
}
