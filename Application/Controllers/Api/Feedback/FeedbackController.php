<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Feedback;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Feedback\FeedbackService;

class FeedbackController extends ApiController
{
    private FeedbackService $service;

    public function __construct(?FeedbackService $service = null)
    {
        parent::__construct();
        $this->service = $this->resolveOrCreate($service, FeedbackService::class);
    }

    /**
     * POST /api/feedback
     */
    public function store(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        $data = $this->getJson();
        $data['pagina'] = $_SERVER['HTTP_REFERER'] ?? null;

        $result = $this->service->store($userId, $data);
        $workflowResult = (bool) ($result['success'] ?? false)
            ? [
                'success' => true,
                'data' => $result['data'] ?? null,
                'message' => 'Feedback registrado com sucesso.',
                'status' => 200,
            ]
            : [
                'success' => false,
                'message' => (string) ($result['message'] ?? 'Erro ao registrar feedback.'),
                'status' => 429,
            ];

        return $this->respondApiWorkflowResult(
            $workflowResult,
            useWorkflowFailureOnFailure: false,
            preserveSuccessMeta: true
        );
    }

    /**
     * GET /api/feedback/check-nps
     */
    public function checkNps(): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $shouldShow = $this->service->shouldShowNps($userId);

        return Response::successResponse(['show_nps' => $shouldShow]);
    }

    /**
     * GET /api/feedback/can-micro?contexto=xxx
     */
    public function canMicro(): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $contexto = $this->getQuery('contexto', '');

        if (empty($contexto)) {
            return Response::errorResponse('Contexto obrigatorio.', 400);
        }

        $can = $this->service->canShowMicroFeedback($userId, $contexto);

        return Response::successResponse(['can_show' => $can]);
    }
}
