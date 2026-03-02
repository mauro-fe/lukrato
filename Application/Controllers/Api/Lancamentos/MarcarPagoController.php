<?php

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Repositories\LancamentoRepository;
use Application\Services\Lancamento\LancamentoStatusService;
use Application\Formatters\LancamentoResponseFormatter;

class MarcarPagoController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private LancamentoStatusService $statusService;

    public function __construct()
    {
        parent::__construct();
        $this->lancamentoRepo = new LancamentoRepository();
        $this->statusService = new LancamentoStatusService();
    }

    public function __invoke(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
        if (!$lancamento) {
            Response::error('Lancamento nao encontrado', 404);
            return;
        }

        try {
            $lancamento = $this->statusService->marcarPago($lancamento);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 422);
            return;
        }

        $lancamento->loadMissing(['categoria', 'conta']);

        Response::success(
            LancamentoResponseFormatter::format($lancamento),
            'Lançamento marcado como pago.'
        );
    }
}
