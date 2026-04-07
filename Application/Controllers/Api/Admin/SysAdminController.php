<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Admin;

use Application\Controllers\ApiController;
use Application\Core\Exceptions\ClientErrorException;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Models\Usuario;
use Application\Services\Admin\SysAdminOpsService;
use Application\Services\Admin\SysAdminUserService;
use Application\Services\Infrastructure\LogService;
use Throwable;

class SysAdminController extends ApiController
{
    public function __construct(
        private ?SysAdminUserService $userService = null,
        private ?SysAdminOpsService $opsService = null
    ) {
        parent::__construct();

        $this->userService = $this->resolveOrCreate($this->userService, SysAdminUserService::class);
        $this->opsService = $this->resolveOrCreate($this->opsService, SysAdminOpsService::class);
    }

    public function toggleMaintenance(): Response
    {
        $this->adminUser();

        return $this->resultActionOrInternal(
            fn(): array => $this->opsService->toggleMaintenance($this->getRequestPayload()),
            'Erro ao processar modo de manutenção.'
        );
    }

    public function maintenanceStatus(): Response
    {
        $this->adminUser(releaseSession: true);

        return Response::successResponse($this->opsService->getMaintenanceStatus());
    }

    public function grantAccess(): Response
    {
        $admin = $this->adminUser(message: 'Acesso negado. Apenas administradores podem executar esta acão.');

        return $this->runAdminUserAction(
            $admin,
            fn(): array => $this->userService->grantAccess(
                $this->adminId($admin),
                $this->adminName($admin),
                $this->getRequestPayload()
            ),
            'Não foi possivel liberar o acesso.',
            'Erro ao processar solicitação.',
            'Erro ao liberar acesso.'
        );
    }

    public function revokeAccess(): Response
    {
        $admin = $this->adminUser(message: 'Acesso negado. Apenas administradores podem executar esta ação.');

        return $this->runAdminUserAction(
            $admin,
            fn(): array => $this->userService->revokeAccess(
                $this->adminId($admin),
                $this->adminName($admin),
                $this->getRequestPayload()
            ),
            'Não foi possivel remover o acesso.',
            'Erro ao processar solicitação.',
            'Erro ao remover acesso.'
        );
    }

    public function listUsers(): Response
    {
        $this->adminUser(releaseSession: true);

        return $this->dataActionOrInternal(
            fn(): mixed => $this->userService->listUsers($this->collectQueryParams()),
            'Erro ao buscar usuarios.',
            'Erro ao listar usuarios.'
        );
    }

    public function getUser(int $id): Response
    {
        $this->adminUser(releaseSession: true);

        try {
            return Response::successResponse($this->userService->getUser($id));
        } catch (ClientErrorException $e) {
            return $this->domainErrorResponse(
                $e,
                'Usuario não encontrado.',
                $e->statusCode,
                [],
                $e->statusCode === 404 ? 'RESOURCE_NOT_FOUND' : null
            );
        } catch (Throwable $e) {
            $context = ['target_user_id' => $id];
            $this->logSysAdminError('Erro ao buscar usuario.', $e, $context);

            return $this->internalErrorResponse($e, 'Erro ao buscar usuario.', 500, $context);
        }
    }

    public function updateUser(int $id): Response
    {
        $admin = $this->adminUser();

        return $this->runAdminUserAction(
            $admin,
            fn(): array => $this->userService->updateUser(
                $this->adminId($admin),
                $this->adminName($admin),
                $id,
                $this->getRequestPayload()
            ),
            'Não foi possivel atualizar o usuario.',
            'Erro ao atualizar usuario.',
            'Erro ao atualizar usuario.',
            ['target_user_id' => $id],
            true
        );
    }

    public function deleteUser(int $id): Response
    {
        $admin = $this->adminUser();

        return $this->runAdminUserAction(
            $admin,
            fn(): array => $this->userService->deleteUser($this->adminId($admin), $this->adminName($admin), $id),
            'Não foi possivel excluir o usuario.',
            'Erro ao excluir usuario.',
            'Erro ao excluir usuario.',
            ['target_user_id' => $id],
            true
        );
    }

    public function getStats(): Response
    {
        $this->adminUser(releaseSession: true);

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
        $this->adminUser(releaseSession: true);

        return $this->dataActionOrInternal(
            fn(): mixed => $this->opsService->getErrorLogs($this->collectQueryParams()),
            'Erro ao buscar logs.'
        );
    }

    public function errorLogsSummary(): Response
    {
        $this->adminUser(releaseSession: true);

        return $this->dataActionOrInternal(
            fn(): mixed => $this->opsService->getErrorLogsSummary($this->collectQueryParams()),
            'Erro ao buscar resumo.'
        );
    }

    public function resolveErrorLog(int $id): Response
    {
        $admin = $this->adminUser();

        try {
            $resolved = $this->opsService->resolveErrorLog($id, $this->adminId($admin));

            if ($resolved) {
                return Response::successResponse(null, 'Log marcado como resolvido');
            }

            return Response::errorResponse('Log não encontrado', 404);
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao resolver log.');
        }
    }

    public function cleanupErrorLogs(): Response
    {
        $this->adminUser();

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

    public function clearCache(): Response
    {
        $this->adminUser();

        return $this->resultActionOrInternal(
            fn(): array => $this->opsService->clearCache(),
            'Erro ao limpar cache.',
            fn(array $result): mixed => ['details' => $result['details'] ?? []]
        );
    }

    private function adminUser(bool $releaseSession = false, string $message = 'Acesso negado'): Usuario
    {
        if ($releaseSession) {
            return $this->requireApiAdminUserAndReleaseSessionOrFail($message);
        }

        return $this->requireApiAdminUserOrFail($message);
    }

    private function adminId(Usuario $admin): int
    {
        return (int) $admin->id;
    }

    private function adminName(Usuario $admin): string
    {
        return (string) $admin->nome;
    }

    /**
     * @param callable():array<string, mixed> $resolver
     * @param callable(array<string, mixed>):mixed|null $dataResolver
     */
    private function resultActionOrInternal(
        callable $resolver,
        string $internalFallback,
        ?callable $dataResolver = null
    ): Response {
        try {
            $result = $resolver();
            $data = $dataResolver !== null ? $dataResolver($result) : ($result['data'] ?? null);
            $message = (string) ($result['message'] ?? 'Success');

            return Response::successResponse($data, $message);
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, $internalFallback);
        }
    }

    /**
     * @param callable():mixed $resolver
     * @param array<string, mixed> $context
     */
    private function dataActionOrInternal(
        callable $resolver,
        string $internalFallback,
        ?string $logMessage = null,
        array $context = []
    ): Response {
        try {
            return Response::successResponse($resolver());
        } catch (Throwable $e) {
            if ($logMessage !== null) {
                $this->logSysAdminError($logMessage, $e, $context);
            }

            return $this->internalErrorResponse($e, $internalFallback);
        }
    }

    /**
     * @param callable():array<string, mixed> $resolver
     * @param array<string, mixed> $context
     */
    private function runAdminUserAction(
        Usuario $admin,
        callable $resolver,
        string $domainFallback,
        string $internalFallback,
        ?string $logMessage = null,
        array $context = [],
        bool $markNotFoundAsResourceNotFound = false
    ): Response {
        try {
            $result = $resolver();

            return Response::successResponse(
                $result['data'] ?? null,
                (string) ($result['message'] ?? 'Success')
            );
        } catch (ClientErrorException $e) {
            return $this->domainErrorResponse(
                $e,
                $domainFallback,
                $e->statusCode,
                [],
                $markNotFoundAsResourceNotFound && $e->statusCode === 404 ? 'RESOURCE_NOT_FOUND' : null
            );
        } catch (Throwable $e) {
            $internalContext = array_merge(['admin_user_id' => $this->adminId($admin)], $context);

            if ($logMessage !== null) {
                $this->logSysAdminError($logMessage, $e, $internalContext);
            }

            return $this->internalErrorResponse($e, $internalFallback, 500, $internalContext);
        }
    }

    private function toBool(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true);
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
