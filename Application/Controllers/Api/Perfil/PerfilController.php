<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Perfil;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Infrastructure\LogService;
use Application\Services\User\PerfilApiWorkflowService;
use Application\UseCases\Perfil\AvatarUseCase;
use Application\UseCases\Perfil\DashboardPreferencesUseCase;
use Application\UseCases\Perfil\DeleteAccountUseCase;
use Throwable;

class PerfilController extends ApiController
{
    private PerfilApiWorkflowService $workflowService;
    private AvatarUseCase $avatarUseCase;
    private DashboardPreferencesUseCase $dashboardPreferencesUseCase;
    private DeleteAccountUseCase $deleteAccountUseCase;

    public function __construct(
        ?PerfilApiWorkflowService $workflowService = null,
        ?DashboardPreferencesUseCase $dashboardPreferencesUseCase = null,
        ?AvatarUseCase $avatarUseCase = null,
        ?DeleteAccountUseCase $deleteAccountUseCase = null
    ) {
        parent::__construct();

        $this->workflowService = $this->resolveOrCreate($workflowService, PerfilApiWorkflowService::class);
        $this->dashboardPreferencesUseCase = $this->resolveOrCreate(
            $dashboardPreferencesUseCase,
            DashboardPreferencesUseCase::class
        );
        $this->avatarUseCase = $this->resolveOrCreate($avatarUseCase, AvatarUseCase::class);
        $this->deleteAccountUseCase = $this->resolveOrCreate($deleteAccountUseCase, DeleteAccountUseCase::class);
    }

    public function show(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        try {
            $perfil = $this->workflowService->getProfile($user->id);

            if (!$perfil) {
                return Response::errorResponse('Usuário não encontrado', 404);
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
            return $this->respondPerfilWorkflowResult([
                'success' => (bool) ($result['success'] ?? false),
                'errors' => $result['errors'] ?? [],
                'data' => [
                    'message' => 'Perfil atualizado com sucesso',
                    'user' => $result['user'] ?? null,
                    'new_achievements' => $result['new_achievements'] ?? [],
                    'email_change_pending' => (bool) ($result['email_change_pending'] ?? false),
                    'email_verification_sent' => (bool) ($result['email_verification_sent'] ?? false),
                ],
            ], validationFailure: true);
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
            return $this->respondPerfilWorkflowResult([
                'success' => (bool) ($result['success'] ?? false),
                'errors' => $result['errors'] ?? [],
                'data' => [
                    'message' => $result['message'] ?? '',
                ],
            ], validationFailure: true);
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
            return $this->respondPerfilWorkflowResult($result);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'update_theme');

            return Response::errorResponse('Erro ao atualizar tema', 500);
        }
    }

    public function uploadAvatar(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            $result = $this->avatarUseCase->upload($user, $this->request->file('avatar'));
            return $this->respondPerfilWorkflowResult($result);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'upload_avatar');

            return Response::errorResponse('Erro ao enviar foto de perfil', 500);
        }
    }

    public function removeAvatar(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            return Response::successResponse($this->avatarUseCase->remove($user)['data']);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'remove_avatar');

            return Response::errorResponse('Erro ao remover foto de perfil', 500);
        }
    }

    public function updateAvatarPreferences(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            $result = $this->avatarUseCase->updatePreferences($user, $this->getRequestPayload());

            return Response::successResponse($result['data']);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'update_avatar_preferences');

            return Response::errorResponse('Erro ao atualizar o enquadramento da foto.', 500);
        }
    }

    public function getDashboardPreferences(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        return Response::successResponse($this->dashboardPreferencesUseCase->get($user));
    }

    public function updateDashboardPreferences(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            return Response::successResponse(
                $this->dashboardPreferencesUseCase->update($user, $this->getRequestPayload()),
                'Preferências do dashboard atualizadas'
            );
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'update_dashboard_preferences');

            return Response::errorResponse('Erro ao salvar preferências do dashboard', 500);
        }
    }

    public function delete(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            return Response::successResponse($this->deleteAccountUseCase->execute($user->id));
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

    /**
     * @param array<string, mixed> $result
     */
    private function respondPerfilWorkflowResult(array $result, bool $validationFailure = false): Response
    {
        if ($validationFailure && !($result['success'] ?? false)) {
            $result['message'] = 'Validation failed';
            $result['status'] = 422;
            $result['errors'] = is_array($result['errors'] ?? null) ? $result['errors'] : [];
        }

        return $this->respondApiWorkflowResult(
            $result,
            useWorkflowFailureOnFailure: false,
            mapValidationFailedTo422: $validationFailure
        );
    }
}
