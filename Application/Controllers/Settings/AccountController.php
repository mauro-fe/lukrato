<?php

namespace Application\Controllers\Settings;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\LogService;
use Application\Core\Exceptions\AuthException;
use Exception;

class AccountController extends BaseController
{
    public function delete(): void
    {
        $requestId = uniqid('acc_del_', true);

        LogService::info('Iniciando processo de exclusão de conta', [
            'request_id' => $requestId,
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        try {
            $this->requireAuth();
        } catch (AuthException $e) {
            LogService::warning('Tentativa de excluir conta sem autenticação', [
                'request_id' => $requestId,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
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
                    'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);

                Response::notFound('Usuário não encontrado.');
                return;
            }

            $result = $user->delete();

            LogService::info('Conta excluída com sucesso (soft-delete)', [
                'request_id' => $requestId,
                'user_id'    => $this->userId,
                'email'      => $user->email,
                'delete_result' => $result,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            Response::success(null, 'Conta excluída com sucesso.');
            return;
        } catch (Exception $e) {

            LogService::error('Erro inesperado ao excluir conta', [
                'request_id' => $requestId,
                'user_id'    => $this->userId ?? null,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                'exception'  => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            Response::error('Erro ao excluir conta. Tente novamente mais tarde.', 500);
            return;
        }
    }
}
