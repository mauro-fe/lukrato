<?php

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Services\Lancamento\LancamentoStatusService;
use Application\Formatters\LancamentoResponseFormatter;

class MarcarPagoController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private LancamentoStatusService $statusService;
    private ParcelamentoRepository $parcelamentoRepo;

    public function __construct()
    {
        parent::__construct();
        $this->lancamentoRepo = new LancamentoRepository();
        $this->statusService = new LancamentoStatusService();
        $this->parcelamentoRepo = new ParcelamentoRepository();
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

        // Atualizar parcelas_pagas no parcelamento
        if ($lancamento->parcelamento_id) {
            $this->parcelamentoRepo->atualizarParcelasPagas($lancamento->parcelamento_id);
        }

        $lancamento->loadMissing(['categoria', 'conta', 'parcelamento']);

        Response::success(
            LancamentoResponseFormatter::format($lancamento),
            'Lançamento marcado como pago.'
        );
    }

    /**
     * Desmarca um lançamento como pago (volta para pendente).
     */
    public function desmarcar(int $id): void
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
            $lancamento = $this->statusService->desmarcarPago($lancamento);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 422);
            return;
        }

        // Atualizar parcelas_pagas no parcelamento
        if ($lancamento->parcelamento_id) {
            $this->parcelamentoRepo->atualizarParcelasPagas($lancamento->parcelamento_id);
        }

        $lancamento->loadMissing(['categoria', 'conta', 'parcelamento']);

        Response::success(
            LancamentoResponseFormatter::format($lancamento),
            'Lançamento marcado como pendente.'
        );
    }
}
