<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Notification;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\Communication\CampaignApiWorkflowService;
use Application\Services\Communication\NotificationService;
use Throwable;

/**
 * API para gerenciamento de campanhas de mensagens pelo sysadmin.
 * Endpoints protegidos apenas para administradores.
 */
class CampaignController extends ApiController
{
    private CampaignApiWorkflowService $workflowService;

    public function __construct(
        ?NotificationService $notificationService = null,
        ?CampaignApiWorkflowService $workflowService = null
    ) {
        parent::__construct();

        $resolvedNotificationService = $this->resolveOrCreate($notificationService, NotificationService::class);
        $this->workflowService = $this->resolveOrCreate(
            $workflowService,
            CampaignApiWorkflowService::class,
            fn(): CampaignApiWorkflowService => new CampaignApiWorkflowService($resolvedNotificationService)
        );
    }

    private function requireAdminOrFail(): Usuario
    {
        return $this->requireApiAdminUserOrFail('Acesso negado. Apenas administradores podem acessar este recurso.');
    }

    public function index(): Response
    {
        $this->requireAdminOrFail();

        try {
            $page = $this->getIntQuery('page', 1);
            $perPage = $this->getIntQuery('per_page', 10);

            return Response::successResponse(
                $this->workflowService->listCampaigns($page, $perPage),
                'Campanhas listadas com sucesso'
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao listar campanhas.');
        }
    }

    public function store(): Response
    {
        $admin = $this->requireApiAdminUserAndReleaseSessionOrFail('Acesso negado. Apenas administradores podem acessar este recurso.');

        try {
            $result = $this->workflowService->createCampaign($admin->id, $admin->nome, $this->getRequestPayload());
            return $this->respondApiWorkflowResult(
                $result,
                preserveSuccessMeta: true,
                useWorkflowFailureOnFailure: false
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao enviar campanha.');
        }
    }

    public function preview(): Response
    {
        $this->requireAdminOrFail();

        try {
            $query = [
                'plan' => $this->getQuery('plan', 'all'),
                'status' => $this->getQuery('status', 'all'),
                'days_inactive' => $this->getQuery('days_inactive'),
                'email_verified' => $this->getQuery('email_verified'),
            ];

            return Response::successResponse(
                $this->workflowService->preview($query),
                'Preview gerado com sucesso'
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao gerar preview.');
        }
    }

    public function stats(): Response
    {
        $this->requireAdminOrFail();

        try {
            return Response::successResponse(
                $this->workflowService->getStats(),
                'Estatisticas obtidas com sucesso'
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao obter estatisticas.');
        }
    }

    public function options(): Response
    {
        $this->requireAdminOrFail();

        return Response::successResponse(
            $this->workflowService->getOptions(),
            'Opções obtidas com sucesso'
        );
    }

    public function show(int $id): Response
    {
        $this->requireAdminOrFail();

        try {
            $campaign = $this->workflowService->showCampaign($id);

            if ($campaign === null) {
                return Response::errorResponse('Campanha nao encontrada', 404);
            }

            return Response::successResponse($campaign, 'Campanha obtida com sucesso');
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao obter campanha.');
        }
    }

    public function cancelScheduled(int $id): Response
    {
        $this->requireAdminOrFail();

        try {
            $result = $this->workflowService->cancelScheduled($id);
            return $this->respondApiWorkflowResult(
                $result,
                preserveSuccessMeta: true,
                useWorkflowFailureOnFailure: false
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao cancelar campanha.');
        }
    }

    public function processDue(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail('Acesso negado. Apenas administradores podem acessar este recurso.');

        try {
            return Response::successResponse(
                $this->workflowService->processDueCampaigns(),
                'Fila de campanhas sincronizada com sucesso'
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao sincronizar fila de campanhas.');
        }
    }

    public function birthdays(): Response
    {
        $this->requireAdminOrFail();

        try {
            $days = $this->getIntQuery('days', 7);

            return Response::successResponse(
                $this->workflowService->getBirthdays($days),
                'Aniversariantes obtidos com sucesso'
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao obter aniversariantes.');
        }
    }

    public function sendBirthdays(): Response
    {
        $this->requireAdminOrFail();

        try {
            $payload = $this->getRequestPayload();
            $sendEmail = (bool) ($payload['send_email'] ?? true);

            return Response::successResponse(
                $this->workflowService->sendBirthdays($sendEmail),
                'Notificacoes de aniversario processadas'
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao enviar notificacoes de aniversario.');
        }
    }
}
