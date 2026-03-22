<?php

declare(strict_types=1);

namespace Application\Controllers\Settings;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;
use Application\Services\Referral\ReferralAntifraudService;
use Throwable;

class AccountController extends BaseController
{
    public function delete(): Response
    {
        $requestId = uniqid('acc_del_', true);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        LogService::info('Iniciando processo de exclusao de conta', [
            'request_id' => $requestId,
            'ip' => $ip,
        ]);

        $userId = $this->requireApiUserIdOrFail();

        try {
            $user = Usuario::find($userId);

            if (!$user) {
                LogService::warning('Usuario nao encontrado ao tentar excluir conta', [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'ip' => $ip,
                ]);

                return Response::notFoundResponse('Usuario nao encontrado.');
            }

            $originalEmail = $user->email;

            $anonymizedEmail = 'deleted_' . time() . '_' . substr(md5((string) $user->id), 0, 8) . '@anonimizado.local';
            $user->email = $anonymizedEmail;
            $user->nome = 'Usuario Removido';
            $user->google_id = null;
            $user->save();

            $result = $user->delete();

            if (!$result) {
                LogService::error('Falha ao excluir conta', [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'email' => $originalEmail,
                    'ip' => $ip,
                ]);

                return Response::errorResponse('Nao foi possivel excluir sua conta. Tente novamente.', 500);
            }

            $antifraudService = new ReferralAntifraudService();
            $antifraudService->onAccountDeleted($originalEmail, $userId, $ip);

            Auth::logout();

            LogService::info('Conta excluida com sucesso', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'email_original' => $originalEmail,
                'email_anonimizado' => $anonymizedEmail,
                'delete_result' => $result,
                'ip' => $ip,
            ]);

            return Response::successResponse(null, 'Conta excluida com sucesso.');
        } catch (Throwable $e) {
            LogService::error('Erro inesperado ao excluir conta', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'ip' => $ip,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Response::errorResponse('Erro ao excluir conta. Tente novamente mais tarde.', 500);
        }
    }
}
