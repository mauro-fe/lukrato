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
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $phone = trim((string) $this->getPost('phone', ''));

        if ($phone === '' || strlen((string) preg_replace('/[^\d]/', '', $phone)) < 10) {
            return Response::errorResponse('Número de telefone inválido. Use o formato: 5511999999999', 422);
        }

        $normalized = (string) preg_replace('/[^\d]/', '', $phone);

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
        $userId = $this->requireApiUserIdOrFail();
        $phone = trim((string) $this->getPost('phone', ''));
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
        $userId = $this->requireApiUserIdOrFail();
        $user = Usuario::find($userId);

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
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $user = Usuario::find($userId);

        $linked = $user && $user->whatsapp_verified && $user->whatsapp_phone;

        return Response::successResponse([
            'linked' => $linked,
            'phone' => $linked ? $this->maskPhone($user->whatsapp_phone) : null,
        ]);
    }

    /**
     * Mascara o phone para exibicao: 5511999999999 -> 55 11 ****9999
     */
    private function maskPhone(string $phone): string
    {
        if (strlen($phone) < 10) {
            return '****';
        }

        $country = substr($phone, 0, 2);
        $ddd = substr($phone, 2, 2);
        $last4 = substr($phone, -4);

        return "{$country} {$ddd} ****{$last4}";
    }
}
