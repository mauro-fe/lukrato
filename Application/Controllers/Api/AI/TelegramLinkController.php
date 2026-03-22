<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\AI\Telegram\TelegramQrCodeService;
use Application\Services\AI\Telegram\TelegramUserResolver;

/**
 * Controller para vincular/desvincular Telegram ao perfil do usuario.
 */
class TelegramLinkController extends BaseController
{
    /**
     * Gera codigo de verificacao para vincular Telegram.
     */
    public function requestLink(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $user = Usuario::find($userId);
        if ($user && $user->telegram_verified && $user->telegram_chat_id) {
            return Response::errorResponse('Telegram já está vinculado. Desvincule primeiro para vincular novamente.', 409);
        }

        $code = TelegramUserResolver::generateVerificationCodeWithReverse($userId);

        $botUsername = $_ENV['TELEGRAM_BOT_USERNAME'] ?? getenv('TELEGRAM_BOT_USERNAME') ?: 'LukratoBot';
        $botUrl = "https://t.me/{$botUsername}?start={$code}";

        return Response::successResponse([
            'code' => $code,
            'bot_url' => $botUrl,
            'qr_code_data_uri' => TelegramQrCodeService::makeDataUri($botUrl),
            'expires_in' => 600,
        ], "Código gerado! Envie \"{$code}\" para o bot @{$botUsername} no Telegram.");
    }

    /**
     * Remove o vinculo do Telegram.
     */
    public function unlink(): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $user = Usuario::find($userId);

        if (!$user) {
            return Response::errorResponse('Usuário não encontrado', 404);
        }

        $user->telegram_chat_id = null;
        $user->telegram_verified = false;
        $user->save();

        return Response::successResponse(null, 'Telegram desvinculado.');
    }

    /**
     * Retorna o status atual do vinculo.
     */
    public function status(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $user = Usuario::find($userId);

        $linked = $user && $user->telegram_verified && $user->telegram_chat_id;

        return Response::successResponse([
            'linked' => $linked,
            'username' => $linked ? $this->maskChatId($user->telegram_chat_id) : null,
        ]);
    }

    /**
     * Mascara o chat_id para exibicao.
     */
    private function maskChatId(string $chatId): string
    {
        if (strlen($chatId) < 4) {
            return '****';
        }

        return str_repeat('*', strlen($chatId) - 4) . substr($chatId, -4);
    }
}
