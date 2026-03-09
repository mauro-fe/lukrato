<?php

declare(strict_types=1);

namespace Application\Services\AI\WhatsApp;

use Application\Models\Usuario;
use Application\Services\Infrastructure\CacheService;

/**
 * Resolve um número de telefone do WhatsApp para um usuário Lukrato.
 *
 * Fluxo:
 * 1. Busca usuario com whatsapp_phone = $phone E whatsapp_verified = true
 * 2. Se não encontrou → retorna null (usuário não vinculado)
 *
 * O vínculo é feito via:
 *  - Painel web: usuário gera código, digita no WhatsApp
 *  - API: POST /api/whatsapp/link (autenticado) → registra phone
 *  - POST /api/whatsapp/verify (código de verificação)
 */
class WhatsAppUserResolver
{
    /**
     * Resolve phone → usuario verificado.
     *
     * @param string $phone  Número no formato E.164 sem "+" (ex: 5511999999999)
     * @return Usuario|null
     */
    public static function resolve(string $phone): ?Usuario
    {
        if ($phone === '') {
            return null;
        }

        // Normalizar: remover +, espaços, traços
        $normalized = preg_replace('/[^\d]/', '', $phone);

        return Usuario::query()
            ->where('whatsapp_phone', $normalized)
            ->where('whatsapp_verified', true)
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Verifica se um phone já está vinculado a algum usuário.
     */
    public static function isLinked(string $phone): bool
    {
        $normalized = preg_replace('/[^\d]/', '', $phone);

        return Usuario::query()
            ->where('whatsapp_phone', $normalized)
            ->where('whatsapp_verified', true)
            ->exists();
    }

    /**
     * Gera um código de verificação de 6 dígitos para vincular WhatsApp.
     * Armazena em cache com TTL de 10 minutos.
     */
    public static function generateVerificationCode(int $userId): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $cache = new CacheService();
        $cache->set("whatsapp_verify:{$userId}", $code, 600); // 10 min

        return $code;
    }

    /**
     * Valida o código de verificação e vincula o phone ao usuário.
     *
     * @return bool true se verificado com sucesso
     */
    public static function verifyAndLink(int $userId, string $phone, string $code): bool
    {
        $cache      = new CacheService();
        $storedCode = $cache->get("whatsapp_verify:{$userId}");

        if ($storedCode === null || $storedCode !== $code) {
            return false;
        }

        $normalized = preg_replace('/[^\d]/', '', $phone);

        $user = Usuario::find($userId);
        if (!$user) {
            return false;
        }

        $user->whatsapp_phone    = $normalized;
        $user->whatsapp_verified = true;
        $user->save();

        // Limpar código usado
        $cache->forget("whatsapp_verify:{$userId}");

        return true;
    }
}
