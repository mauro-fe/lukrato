<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Admin;

use Application\Controllers\BaseController;
use Application\Core\Exceptions\ClientErrorException;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Models\Usuario;
use Application\Services\Admin\SysAdminOpsService;
use Application\Services\Admin\SysAdminUserService;
use Application\Services\Infrastructure\LogService;
use Throwable;

class SysAdminController extends BaseController
{
    public function __construct(
        private ?SysAdminUserService $userService = null,
        private ?SysAdminOpsService $opsService = null
    ) {
        parent::__construct();

        $this->userService ??= new SysAdminUserService();
        $this->opsService ??= new SysAdminOpsService();
    }

    public function toggleMaintenance(): Response
    {
        $this->requireApiAdminUserOrFail();

        try {
            $result = $this->opsService->toggleMaintenance($this->getRequestPayload());

            return Response::successResponse($result['data'], $result['message']);
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao processar modo de manutencao.');
        }
    }

    public function maintenanceStatus(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return Response::successResponse($this->opsService->getMaintenanceStatus());
    }

    public function grantAccess(): Response
    {
        $admin = $this->requireApiAdminUserOrFail('Acesso negado. Apenas administradores podem executar esta acao.');

        try {
            $result = $this->userService->grantAccess(
                (int) $admin->id,
                (string) $admin->nome,
                $this->getRequestPayload()
            );

            return Response::successResponse($result['data'], $result['message']);
        } catch (ClientErrorException $e) {
            return $this->domainErrorResponse($e, 'Nao foi possivel liberar o acesso.', $e->statusCode);
        } catch (Throwable $e) {
            $this->logSysAdminError('Erro ao liberar acesso.', $e, ['admin_user_id' => $admin->id]);

            return $this->internalErrorResponse($e, 'Erro ao processar solicitacao.', 500, [
                'admin_user_id' => $admin->id,
            ]);
        }
    }

    public function revokeAccess(): Response
    {
        $admin = $this->requireApiAdminUserOrFail('Acesso negado. Apenas administradores podem executar esta acao.');

        try {
            $result = $this->userService->revokeAccess(
                (int) $admin->id,
                (string) $admin->nome,
                $this->getRequestPayload()
            );

            return Response::successResponse($result['data'], $result['message']);
        } catch (ClientErrorException $e) {
            return $this->domainErrorResponse($e, 'Nao foi possivel remover o acesso.', $e->statusCode);
        } catch (Throwable $e) {
            $this->logSysAdminError('Erro ao remover acesso.', $e, ['admin_user_id' => $admin->id]);

            return $this->internalErrorResponse($e, 'Erro ao processar solicitacao.', 500, [
                'admin_user_id' => $admin->id,
            ]);
        }
    }

    public function listUsers(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        try {
            return Response::successResponse($this->userService->listUsers($this->collectQueryParams()));
        } catch (Throwable $e) {
            $this->logSysAdminError('Erro ao listar usuarios.', $e);

            return $this->internalErrorResponse($e, 'Erro ao buscar usuarios.');
        }
    }

    public function getUser(int $id): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        try {
            return Response::successResponse($this->userService->getUser($id));
        } catch (ClientErrorException $e) {
            return $this->domainErrorResponse(
                $e,
                'Usuario nao encontrado.',
                $e->statusCode,
                [],
                $e->statusCode === 404 ? 'RESOURCE_NOT_FOUND' : null
            );
        } catch (Throwable $e) {
            $this->logSysAdminError('Erro ao buscar usuario.', $e, ['target_user_id' => $id]);

            return $this->internalErrorResponse($e, 'Erro ao buscar usuario.', 500, [
                'target_user_id' => $id,
            ]);
        }
    }

    public function updateUser(int $id): Response
    {
        $admin = $this->requireApiAdminUserOrFail();

        try {
            $result = $this->userService->updateUser(
                (int) $admin->id,
                (string) $admin->nome,
                $id,
                $this->getRequestPayload()
            );

            return Response::successResponse($result['data'], $result['message']);
        } catch (ClientErrorException $e) {
            return $this->domainErrorResponse(
                $e,
                'Nao foi possivel atualizar o usuario.',
                $e->statusCode,
                [],
                $e->statusCode === 404 ? 'RESOURCE_NOT_FOUND' : null
            );
        } catch (Throwable $e) {
            $this->logSysAdminError('Erro ao atualizar usuario.', $e, [
                'admin_user_id' => $admin->id,
                'target_user_id' => $id,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao atualizar usuario.', 500, [
                'admin_user_id' => $admin->id,
                'target_user_id' => $id,
            ]);
        }
    }

    public function deleteUser(int $id): Response
    {
        $admin = $this->requireApiAdminUserOrFail();

        try {
            $result = $this->userService->deleteUser((int) $admin->id, (string) $admin->nome, $id);

            return Response::successResponse($result['data'], $result['message']);
        } catch (ClientErrorException $e) {
            return $this->domainErrorResponse(
                $e,
                'Nao foi possivel excluir o usuario.',
                $e->statusCode,
                [],
                $e->statusCode === 404 ? 'RESOURCE_NOT_FOUND' : null
            );
        } catch (Throwable $e) {
            $this->logSysAdminError('Erro ao excluir usuario.', $e, [
                'admin_user_id' => $admin->id,
                'target_user_id' => $id,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao excluir usuario.', 500, [
                'admin_user_id' => $admin->id,
                'target_user_id' => $id,
            ]);
        }
    }

    public function getStats(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        try {
            return Response::successResponse($this->userService->getStats());
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::GENERAL, [
                'action' => 'sysadmin_stats',
            ]);

            return $this->internalErrorResponse($e, 'Erro ao buscar estatisticas.');
        }
    }

    public function errorLogs(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        try {
            return Response::successResponse($this->opsService->getErrorLogs($this->collectQueryParams()));
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao buscar logs.');
        }
    }

    public function errorLogsSummary(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        try {
            return Response::successResponse($this->opsService->getErrorLogsSummary($this->collectQueryParams()));
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao buscar resumo.');
        }
    }

    public function resolveErrorLog(int $id): Response
    {
        try {
            $userId = $this->requireApiAdminUserOrFail()->id;
            $resolved = $this->opsService->resolveErrorLog($id, (int) $userId);

            if ($resolved) {
                return Response::successResponse(null, 'Log marcado como resolvido');
            }

            return Response::errorResponse('Log nao encontrado', 404);
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao resolver log.');
        }
    }

    public function cleanupErrorLogs(): Response
    {
        $this->requireApiAdminUserOrFail();

        $payload = $this->getRequestPayload();
        $days = isset($payload['days']) ? (int) $payload['days'] : $this->getIntQuery('days', 30);
        $includeUnresolved = $this->toBool(
            $payload['include_unresolved'] ?? $this->getQuery('include_unresolved'),
            false
        );

        try {
            $deleted = $this->opsService->cleanupErrorLogs($days, $includeUnresolved);
            $message = $includeUnresolved
                ? "{$deleted} log(s) com mais de {$days} dia(s) removido(s)"
                : "{$deleted} log(s) resolvido(s) há mais de {$days} dia(s) removido(s)";

            return Response::successResponse([
                'count' => $deleted,
                'days' => $days,
                'include_unresolved' => $includeUnresolved,
            ], $message);
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao limpar logs.');
        }
    }

    private function toBool(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true);
    }

    public function clearCache(): Response
    {
        $this->requireApiAdminUserOrFail();

        try {
            $result = $this->opsService->clearCache();

            return Response::successResponse([
                'details' => $result['details'],
            ], $result['message']);
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao limpar cache.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function collectQueryParams(): array
    {
        return [
            'query' => $this->getQuery('query'),
            'status' => $this->getQuery('status'),
            'plan' => $this->getQuery('plan'),
            'page' => $this->getQuery('page'),
            'perPage' => $this->getQuery('perPage'),
            'per_page' => $this->getQuery('per_page'),
            'level' => $this->getQuery('level'),
            'category' => $this->getQuery('category'),
            'resolved' => $this->getQuery('resolved'),
            'user_id' => $this->getQuery('user_id'),
            'search' => $this->getQuery('search'),
            'date_from' => $this->getQuery('date_from'),
            'date_to' => $this->getQuery('date_to'),
            'hours' => $this->getQuery('hours'),
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logSysAdminError(string $message, Throwable $e, array $context = []): void
    {
        LogService::error($message, array_merge($context, [
            'exception' => get_class($e),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]));
    }
}
