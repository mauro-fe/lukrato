<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\AI\WhatsApp\WhatsAppUserResolver;

/**
 * Controller para vincular/desvincular WhatsApp ao perfil do usuário.
 *
 * Fluxo:
 * 1. POST /api/whatsapp/link   → gera código de 6 dígitos (cache 10min)
 * 2. POST /api/whatsapp/verify → valida código + vincula phone ao user
 * 3. POST /api/whatsapp/unlink → remove vínculo
 * 4. GET  /api/whatsapp/status → retorna status do vínculo
 */
class WhatsAppLinkController extends BaseController
{
    /**
     * Gera código de verificação para vincular WhatsApp.
     * Body: { "phone": "5511999999999" }
     */
    public function requestLink(): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            Response::error('Não autenticado', 401);
            return;
        }

        $phone = trim($_POST['phone'] ?? '');

        if ($phone === '' || strlen(preg_replace('/[^\d]/', '', $phone)) < 10) {
            Response::error('Número de telefone inválido. Use o formato: 5511999999999', 422);
            return;
        }

        // Verificar se phone já está vinculado a outro usuário
        $normalized = preg_replace('/[^\d]/', '', $phone);

        $existing = \Application\Models\Usuario::query()
            ->where('whatsapp_phone', $normalized)
            ->where('whatsapp_verified', true)
            ->where('id', '!=', $userId)
            ->exists();

        if ($existing) {
            Response::error('Este número já está vinculado a outra conta.', 409);
            return;
        }

        $code = WhatsAppUserResolver::generateVerificationCode($userId);

        Response::json([
            'success' => true,
            'message' => "Código de verificação gerado. Envie \"{$code}\" no WhatsApp do Lukrato para confirmar.",
            'data'    => [
                'phone'      => $normalized,
                'expires_in' => 600, // 10 minutos
            ],
        ]);
    }

    /**
     * Valida o código e vincula o phone.
     * Body: { "phone": "5511999999999", "code": "123456" }
     */
    public function verify(): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            Response::error('Não autenticado', 401);
            return;
        }

        $phone = trim($_POST['phone'] ?? '');
        $code  = trim($_POST['code']  ?? '');

        if ($phone === '' || $code === '') {
            Response::error('Phone e código são obrigatórios.', 422);
            return;
        }

        $success = WhatsAppUserResolver::verifyAndLink($userId, $phone, $code);

        if (!$success) {
            Response::error('Código inválido ou expirado. Gere um novo código.', 422);
            return;
        }

        Response::json([
            'success' => true,
            'message' => 'WhatsApp vinculado com sucesso!',
        ]);
    }

    /**
     * Remove o vínculo do WhatsApp.
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

        $user->whatsapp_phone    = null;
        $user->whatsapp_verified = false;
        $user->save();

        Response::json([
            'success' => true,
            'message' => 'WhatsApp desvinculado.',
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

        $linked = $user && $user->whatsapp_verified && $user->whatsapp_phone;

        Response::json([
            'success' => true,
            'data'    => [
                'linked' => $linked,
                'phone'  => $linked ? $this->maskPhone($user->whatsapp_phone) : null,
            ],
        ]);
    }

    /**
     * Mascara o phone para exibição: 5511999999999 → 55 11 ****9999
     */
    private function maskPhone(string $phone): string
    {
        if (strlen($phone) < 10) {
            return '****';
        }

        $country = substr($phone, 0, 2);
        $ddd     = substr($phone, 2, 2);
        $last4   = substr($phone, -4);

        return "{$country} {$ddd} ****{$last4}";
    }
}
