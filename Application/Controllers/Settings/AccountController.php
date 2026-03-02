<?php

namespace Application\Controllers\Settings;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;
use Application\Services\Referral\ReferralAntifraudService;
use Application\Core\Exceptions\AuthException;
use Exception;

class AccountController extends BaseController
{
    public function delete(): void
    {
        $requestId = uniqid('acc_del_', true);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        LogService::info('Iniciando processo de exclusão de conta', [
            'request_id' => $requestId,
            'ip'         => $ip,
        ]);

        try {
            $this->requireAuth();
        } catch (AuthException $e) {
            LogService::warning('Tentativa de excluir conta sem autenticação', [
                'request_id' => $requestId,
                'ip'         => $ip,
                'error'      => $e->getMessage(),
            ]);

            Response::error('Usuário não autenticado.', 401);
            return;
        }

        try {
            $user = Usuario::find($this->userId);

            if (!$user) {
                LogService::warning('Usuário não encontrado ao tentar excluir conta', [
                    'request_id' => $requestId,
                    'user_id'    => $this->userId,
                    'ip'         => $ip,
                ]);

                Response::notFound('Usuário não encontrado.');
                return;
            }

            // Guarda email original para log e anti-fraude
            $originalEmail = $user->email;

            // Anonimiza email para liberar para novo cadastro (mantém histórico)
            $anonymizedEmail = 'deleted_' . time() . '_' . substr(md5((string) $user->id), 0, 8) . '@anonimizado.local';
            $user->email = $anonymizedEmail;
            $user->nome = 'Usuário Removido';
            $user->google_id = null; // Remove vinculação com Google
            $user->save();

            // Soft delete - mantém histórico
            $result = $user->delete();

            if (!$result) {
                LogService::error('Falha ao excluir conta', [
                    'request_id' => $requestId,
                    'user_id'    => $this->userId,
                    'email'      => $originalEmail,
                    'ip'         => $ip,
                ]);

                Response::error('Não foi possível excluir sua conta. Tente novamente.', 500);
                return;
            }

            // Registra no sistema anti-fraude para aplicar quarentena
            $antifraudService = new ReferralAntifraudService();
            $antifraudService->onAccountDeleted($originalEmail, $this->userId, $ip);

            Auth::logout();

            LogService::info('Conta excluída com sucesso', [
                'request_id' => $requestId,
                'user_id'    => $this->userId,
                'email_original' => $originalEmail,
                'email_anonimizado' => $anonymizedEmail,
                'delete_result' => $result,
                'ip'         => $ip,
            ]);

            Response::success(null, 'Conta excluída com sucesso.');
            return;
        } catch (Exception $e) {

            LogService::error('Erro inesperado ao excluir conta', [
                'request_id' => $requestId,
                'user_id'    => $this->userId ?? null,
                'ip'         => $ip ?? null,
                'exception'  => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            Response::error('Erro ao excluir conta. Tente novamente mais tarde.', 500);
            return;
        }
    }
}
