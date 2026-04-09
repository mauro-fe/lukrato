<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Config\TelegramRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\AI\Telegram\TelegramQrCodeService;
use Application\Services\AI\Telegram\TelegramUserResolver;

/**
 * Controller para vincular/desvincular Telegram ao perfil do usuario.
 */
class TelegramLinkController extends ApiController
{
    /**
     * Gera codigo de verificacao para vincular Telegram.
     */
    public function requestLink(): Response
    {
        $userId = $this->userId(releaseSession: true);

        if ($this->hasLinkedTelegram($this->userById($userId))) {
            return Response::errorResponse('Telegram já está vinculado. Desvincule primeiro para vincular novamente.', 409);
        }

        $code = TelegramUserResolver::generateVerificationCodeWithReverse($userId);

        $botUsername = $this->botUsername();
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
        $user = $this->userById($this->userId());

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
        $user = $this->userById($this->userId(releaseSession: true));

        $linked = $this->hasLinkedTelegram($user);

        return Response::successResponse([
            'linked' => $linked,
            'username' => $linked ? $this->maskChatId($user->telegram_chat_id) : null,
        ]);
    }

    /**
     * Mascara o chat_id para exibicao.
     */
    private function maskChatId(string|int $chatId): string
    {
        $chatId = (string) $chatId;

        if (strlen($chatId) < 4) {
            return '****';
        }

        return str_repeat('*', strlen($chatId) - 4) . substr($chatId, -4);
    }

    private function userId(bool $releaseSession = false): int
    {
        if ($releaseSession) {
            return $this->requireApiUserIdAndReleaseSessionOrFail();
        }

        return $this->requireApiUserIdOrFail();
    }

    private function userById(int $userId): ?Usuario
    {
        return Usuario::find($userId);
    }

    private function hasLinkedTelegram(?Usuario $user): bool
    {
        return $user !== null && (bool) $user->telegram_verified && !empty($user->telegram_chat_id);
    }

    private function botUsername(): string
    {
        return $this->telegramRuntimeConfig()->botUsername();
    }

    private function telegramRuntimeConfig(): TelegramRuntimeConfig
    {
        return ApplicationContainer::resolveOrNew(null, TelegramRuntimeConfig::class);
    }
}
