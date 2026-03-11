<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\AI\Telegram\TelegramUserResolver;

/**
 * Controller para vincular/desvincular Telegram ao perfil do usuário.
 *
 * Fluxo:
 * 1. POST /api/telegram/link   → gera código de 6 dígitos (cache 10min)
 * 2. POST /api/telegram/unlink → remove vínculo
 * 3. GET  /api/telegram/status → retorna status do vínculo
 *
 * Diferente do WhatsApp, o Telegram não precisa de POST /verify separado.
 * O bot recebe o código e faz a verificação automaticamente via deep link ou mensagem.
 */
class TelegramLinkController extends BaseController
{
    /**
     * Gera código de verificação para vincular Telegram.
     * O usuário deve enviar este código ao bot @LukratoBot no Telegram.
     */
    public function requestLink(): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            Response::error('Não autenticado', 401);
            return;
        }

        // Verificar se já está vinculado
        $user = \Application\Models\Usuario::find($userId);
        if ($user && $user->telegram_verified && $user->telegram_chat_id) {
            Response::error('Telegram já está vinculado. Desvincule primeiro para vincular novamente.', 409);
            return;
        }

        // Gera código com cache reverso para lookup pelo bot
        $code = TelegramUserResolver::generateVerificationCodeWithReverse($userId);

        $botUsername = $_ENV['TELEGRAM_BOT_USERNAME'] ?? getenv('TELEGRAM_BOT_USERNAME') ?: 'LukratoBot';

        Response::json([
            'success' => true,
            'message' => "Código gerado! Envie \"{$code}\" para o bot @{$botUsername} no Telegram.",
            'data'    => [
                'code'        => $code,
                'bot_url'     => "https://t.me/{$botUsername}?start={$code}",
                'expires_in'  => 600, // 10 minutos
            ],
        ]);
    }

    /**
     * Remove o vínculo do Telegram.
     */
    public function unlink(): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            Response::error('Não autenticado', 401);
            return;
        }

        $user = \Application\Models\Usuario::find($userId);

        if (!$user) {
            Response::error('Usuário não encontrado', 404);
            return;
        }

        $user->telegram_chat_id  = null;
        $user->telegram_verified = false;
        $user->save();

        Response::json([
            'success' => true,
            'message' => 'Telegram desvinculado.',
        ]);
    }

    /**
     * Retorna o status atual do vínculo.
     */
    public function status(): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            Response::error('Não autenticado', 401);
            return;
        }

        $user = \Application\Models\Usuario::find($userId);

        $linked = $user && $user->telegram_verified && $user->telegram_chat_id;

        Response::json([
            'success' => true,
            'data'    => [
                'linked'   => $linked,
                'username' => $linked ? $this->maskChatId($user->telegram_chat_id) : null,
            ],
        ]);
    }

    /**
     * Mascara o chat_id para exibição.
     */
    private function maskChatId(string $chatId): string
    {
        if (strlen($chatId) < 4) {
            return '****';
        }

        return str_repeat('*', strlen($chatId) - 4) . substr($chatId, -4);
    }
}
