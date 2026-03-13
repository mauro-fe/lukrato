<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Feedback;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\Feedback\FeedbackService;

class FeedbackController extends BaseController
{
    private FeedbackService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new FeedbackService();
    }

    /**
     * POST /api/feedback
     */
    public function store(): void
    {
        $this->requireAuthApi();

        $data = $this->getJson();
        $data['pagina'] = $_SERVER['HTTP_REFERER'] ?? null;

        $result = $this->service->store($this->userId, $data);

        if ($result['success']) {
            Response::success($result['data'] ?? null, 'Feedback registrado com sucesso.');
        } else {
            Response::error($result['message'], 429);
        }
    }

    /**
     * GET /api/feedback/check-nps
     */
    public function checkNps(): void
    {
        $this->requireAuthApi();

        $shouldShow = $this->service->shouldShowNps($this->userId);

        Response::success(['show_nps' => $shouldShow]);
    }

    /**
     * GET /api/feedback/can-micro?contexto=xxx
     */
    public function canMicro(): void
    {
        $this->requireAuthApi();

        $contexto = $this->getQuery('contexto', '');

        if (empty($contexto)) {
            Response::error('Contexto obrigatorio.', 400);
            return;
        }

        $can = $this->service->canShowMicroFeedback($this->userId, $contexto);

        Response::success(['can_show' => $can]);
    }
}
