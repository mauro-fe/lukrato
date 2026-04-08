<?php

declare(strict_types=1);

namespace Application\Services\AI\Telegram;

use Application\Container\ApplicationContainer;
use Application\Models\Usuario;
use Application\Services\Infrastructure\CacheService;

/**
 * Resolve um chat_id do Telegram para um usuário Lukrato.
 *
 * Fluxo:
 * 1. Busca usuario com telegram_chat_id = $chatId E telegram_verified = true
 * 2. Se não encontrou → retorna null (usuário não vinculado)
 *
 * O vínculo é feito via:
 *  - Painel web: usuário gera código, envia para o bot no Telegram
 *  - API: POST /api/telegram/link (autenticado) → gera código
 *  - Usuário envia código ao bot → bot valida e vincula
 */
class TelegramUserResolver
{
    /**
     * Resolve chat_id → usuario verificado.
     */
    public static function resolve(string $chatId): ?Usuario
    {
        if ($chatId === '') {
            return null;
        }

        return Usuario::query()
            ->where('telegram_chat_id', $chatId)
            ->where('telegram_verified', true)
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Verifica se um chat_id já está vinculado a algum usuário.
     */
    public static function isLinked(string $chatId): bool
    {
        return Usuario::query()
            ->where('telegram_chat_id', $chatId)
            ->where('telegram_verified', true)
            ->exists();
    }

    /**
     * Gera um código de verificação de 6 dígitos para vincular Telegram.
     * Armazena em cache com TTL de 10 minutos.
     */
    public static function generateVerificationCode(int $userId): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $cache = self::cache();
        $cache->set("telegram_verify:{$userId}", $code, 600); // 10 min

        return $code;
    }

    /**
     * Valida o código de verificação e vincula o chat_id ao usuário.
     *
     * @return bool true se verificado com sucesso
     */
    public static function verifyAndLink(int $userId, string $chatId, string $code): bool
    {
        $cache      = self::cache();
        $storedCode = $cache->get("telegram_verify:{$userId}");

        if ($storedCode === null || $storedCode !== $code) {
            return false;
        }

        $user = Usuario::find($userId);
        if (!$user) {
            return false;
        }

        $user->telegram_chat_id  = $chatId;
        $user->telegram_verified = true;
        $user->save();

        // Limpar código usado
        $cache->forget("telegram_verify:{$userId}");

        return true;
    }

    /**
     * Tenta vincular via código recebido no chat do bot.
     * Busca qual usuário gerou aquele código e vincula o chat_id.
     *
     * @return Usuario|null  Usuário vinculado ou null se código inválido
     */
    public static function verifyByCode(string $chatId, string $code): ?Usuario
    {
        $cache = self::cache();

        // Buscar em todos os códigos pendentes (pattern: telegram_verify:*)
        // Como CacheService pode não suportar scan, buscamos usuários que possam ter esse código
        // Abordagem: iterar usuários recentes não é viável. Usamos cache reverso.
        $userId = $cache->get("telegram_code_reverse:{$code}");

        if ($userId === null) {
            return null;
        }

        $storedCode = $cache->get("telegram_verify:{$userId}");

        if ($storedCode === null || $storedCode !== $code) {
            return null;
        }

        $user = Usuario::find($userId);
        if (!$user) {
            return null;
        }

        // Verificar se chat_id já está vinculado a outro usuário
        $existingUser = Usuario::query()
            ->where('telegram_chat_id', $chatId)
            ->where('telegram_verified', true)
            ->where('id', '!=', $userId)
            ->first();

        if ($existingUser) {
            return null;
        }

        $user->telegram_chat_id  = $chatId;
        $user->telegram_verified = true;
        $user->save();

        $cache->forget("telegram_verify:{$userId}");
        $cache->forget("telegram_code_reverse:{$code}");

        return $user;
    }

    /**
     * Gera código com cache reverso para lookup pelo bot.
     */
    public static function generateVerificationCodeWithReverse(int $userId): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $cache = self::cache();
        $cache->set("telegram_verify:{$userId}", $code, 600);
        $cache->set("telegram_code_reverse:{$code}", $userId, 600);

        return $code;
    }

    private static function cache(): CacheService
    {
        /** @var CacheService $cache */
        $cache = ApplicationContainer::resolveOrNew(null, CacheService::class);

        return $cache;
    }
}
