<?php

declare(strict_types=1);

namespace Application\Controllers\Api\User;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Lib\Auth;
use Application\Providers\PerfilControllerFactory;
use Application\Services\Infrastructure\LogService;
use Application\Services\User\PerfilApiWorkflowService;
use Application\Services\User\PerfilAvatarService;
use Application\Services\User\PerfilService;
use Application\Validators\PerfilValidator;
use Throwable;

class PerfilController extends BaseController
{
    private PerfilApiWorkflowService $workflowService;
    private PerfilAvatarService $avatarService;

    public function __construct(
        ?PerfilService $perfilService = null,
        ?PerfilValidator $validator = null,
        ?PerfilApiWorkflowService $workflowService = null,
        ?PerfilAvatarService $avatarService = null
    ) {
        parent::__construct();

        if ($workflowService === null) {
            if ($perfilService === null || $validator === null) {
                [$perfilService, $validator] = PerfilControllerFactory::buildDependencies();
            }

            $workflowService = new PerfilApiWorkflowService($perfilService, $validator);
        }

        $this->workflowService = $workflowService;
        $this->avatarService = $avatarService ?? new PerfilAvatarService();
    }

    public function show(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        try {
            $perfil = $this->workflowService->getProfile($user->id);

            if (!$perfil) {
                return Response::errorResponse('UsuÃ¡rio nÃ£o encontrado', 404);
            }

            return Response::successResponse([
                'user' => $perfil,
            ], 'Perfil carregado');
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'show_perfil');

            return Response::errorResponse('Erro interno', 500);
        }
    }

    public function update(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            $result = $this->workflowService->updateProfile($user->id, $this->getRequestPayload());

            if (!$result['success']) {
                return Response::validationErrorResponse($result['errors']);
            }

            return Response::successResponse([
                'message' => 'Perfil atualizado com sucesso',
                'user' => $result['user'],
                'new_achievements' => $result['new_achievements'],
            ]);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'update_perfil');

            $statusCode = $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                ? 404
                : 500;

            return Response::errorResponse('Erro interno ao atualizar perfil', $statusCode);
        }
    }

    public function updatePassword(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            $result = $this->workflowService->updatePassword($user, $this->getRequestPayload());

            if (!$result['success']) {
                return Response::validationErrorResponse($result['errors']);
            }

            return Response::successResponse([
                'message' => $result['message'],
            ]);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'update_password');

            return Response::errorResponse('Erro ao alterar senha', 500);
        }
    }

    public function updateTheme(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            $result = $this->workflowService->updateTheme($user, $this->getRequestPayload());

            if (!$result['success']) {
                return Response::errorResponse($result['message'], $result['status']);
            }

            return Response::successResponse($result['data']);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'update_theme');

            return Response::errorResponse('Erro ao atualizar tema', 500);
        }
    }

    public function uploadAvatar(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            $result = $this->avatarService->uploadAvatar($user, $_FILES['avatar'] ?? null);

            if (!$result['success']) {
                return Response::errorResponse($result['message'], $result['status']);
            }

            return Response::successResponse($result['data']);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'upload_avatar');

            return Response::errorResponse('Erro ao enviar foto de perfil', 500);
        }
    }

    public function removeAvatar(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            return Response::successResponse($this->avatarService->removeAvatar($user)['data']);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'remove_avatar');

            return Response::errorResponse('Erro ao remover foto de perfil', 500);
        }
    }

    public function updateAvatarPreferences(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            $result = $this->avatarService->updateAvatarPreferences($user, $this->getRequestPayload());

            return Response::successResponse($result['data']);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'update_avatar_preferences');

            return Response::errorResponse('Erro ao atualizar o enquadramento da foto.', 500);
        }
    }

    public function getDashboardPreferences(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        return Response::successResponse([
            'preferences' => $user->dashboard_preferences ?? [],
        ]);
    }

    public function updateDashboardPreferences(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            $payload = $this->getRequestPayload();
            $allowed = [
                'toggleHealthScore',
                'toggleAlertas',
                'toggleGrafico',
                'togglePrevisao',
                'toggleMetas',
                'toggleCartoes',
                'toggleContas',
                'toggleOrcamentos',
                'toggleFaturas',
                'toggleGamificacao',
            ];
            $prefs = [];

            foreach ($allowed as $key) {
                if (array_key_exists($key, $payload)) {
                    $prefs[$key] = (bool) $payload[$key];
                }
            }

            $user->dashboard_preferences = $prefs;
            $user->save();

            return Response::successResponse([
                'preferences' => $prefs,
            ], 'Preferências do dashboard atualizadas');
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'update_dashboard_preferences');

            return Response::errorResponse('Erro ao salvar preferências do dashboard', 500);
        }
    }

    public function delete(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            $this->workflowService->deleteAccount($user->id);
            Auth::logout();

            return Response::successResponse([
                'message' => 'Conta excluÃ­da com sucesso',
            ]);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'delete_account');

            return Response::errorResponse('Erro ao excluir conta', 500);
        }
    }

    private function logPerfilException(Throwable $e, string $action): void
    {
        LogService::captureException($e, LogCategory::AUTH, [
            'action' => $action,
            'user_id' => $this->userId,
        ]);
    }
}
