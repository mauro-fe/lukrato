<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\AI\WhatsApp\WhatsAppUserResolver;

/**
 * Controller para vincular/desvincular WhatsApp ao perfil do usuario.
 */
class WhatsAppLinkController extends ApiController
{
    /**
     * Gera codigo de verificacao para vincular WhatsApp.
     * Body: { "phone": "5511999999999" }
     */
    public function requestLink(): Response
    {
        $userId = $this->userId(releaseSession: true);
        $normalized = $this->normalizePhone((string) $this->getPost('phone', ''));

        if (!$this->isValidPhone($normalized)) {
            return Response::errorResponse('Número de telefone inválido. Use o formato: 5511999999999', 422);
        }

        $existing = Usuario::query()
            ->where('whatsapp_phone', $normalized)
            ->where('whatsapp_verified', true)
            ->where('id', '!=', $userId)
            ->exists();

        if ($existing) {
            return Response::errorResponse('Este número já está vinculado a outra conta.', 409);
        }

        $code = WhatsAppUserResolver::generateVerificationCode($userId);

        return Response::successResponse([
            'phone' => $normalized,
            'expires_in' => 600,
        ], "Código de verificação gerado. Envie \"{$code}\" no WhatsApp do Lukrato para confirmar.");
    }

    /**
     * Valida o codigo e vincula o phone.
     * Body: { "phone": "5511999999999", "code": "123456" }
     */
    public function verify(): Response
    {
        $userId = $this->userId();
        $phone = $this->normalizePhone((string) $this->getPost('phone', ''));
        $code = trim((string) $this->getPost('code', ''));

        if ($phone === '' || $code === '') {
            return Response::errorResponse('Phone e código são obrigatórios.', 422);
        }

        $success = WhatsAppUserResolver::verifyAndLink($userId, $phone, $code);

        if (!$success) {
            return Response::errorResponse('Código inválido ou expirado. Gere um novo código.', 422);
        }

        return Response::successResponse(null, 'WhatsApp vinculado com sucesso!');
    }

    /**
     * Remove o vinculo do WhatsApp.
     */
    public function unlink(): Response
    {
        $user = $this->userById($this->userId());

        if (!$user) {
            return Response::errorResponse('Usuário não encontrado', 404);
        }

        $user->whatsapp_phone = null;
        $user->whatsapp_verified = false;
        $user->save();

        return Response::successResponse(null, 'WhatsApp desvinculado.');
    }

    /**
     * Retorna o status atual do vinculo.
     */
    public function status(): Response
    {
        $user = $this->userById($this->userId(releaseSession: true));

        $linked = $this->hasLinkedWhatsApp($user);

        return Response::successResponse([
            'linked' => $linked,
            'phone' => $linked ? $this->maskPhone($user->whatsapp_phone) : null,
        ]);
    }

    /**
     * Mascara o phone para exibicao: 5511999999999 -> 55 11 ****9999
     */
    private function maskPhone(string|int $phone): string
    {
        $phone = (string) $phone;

        if (strlen($phone) < 10) {
            return '****';
        }

        $country = substr($phone, 0, 2);
        $ddd = substr($phone, 2, 2);
        $last4 = substr($phone, -4);

        return "{$country} {$ddd} ****{$last4}";
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

    private function hasLinkedWhatsApp(?Usuario $user): bool
    {
        return $user !== null && (bool) $user->whatsapp_verified && !empty($user->whatsapp_phone);
    }

    private function normalizePhone(string $phone): string
    {
        return (string) preg_replace('/[^\d]/', '', trim($phone));
    }

    private function isValidPhone(string $phone): bool
    {
        return strlen($phone) >= 10;
    }
}
