<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Services\Lancamento\LancamentoStatusService;

class MarcarPagoController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private LancamentoStatusService $statusService;
    private ParcelamentoRepository $parcelamentoRepo;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?LancamentoStatusService $statusService = null,
        ?ParcelamentoRepository $parcelamentoRepo = null
    ) {
        parent::__construct();
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->statusService = $statusService ?? new LancamentoStatusService();
        $this->parcelamentoRepo = $parcelamentoRepo ?? new ParcelamentoRepository();
    }

    public function __invoke(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
        if (!$lancamento) {
            return Response::errorResponse('Lancamento nao encontrado', 404);
        }

        try {
            $lancamento = $this->statusService->marcarPago($lancamento);
        } catch (\DomainException $e) {
            return $this->domainErrorResponse($e, 'Nao foi possivel marcar o lancamento como pago.', 422);
        }

        if ($lancamento->parcelamento_id) {
            $this->parcelamentoRepo->atualizarParcelasPagas($lancamento->parcelamento_id);
        }

        $lancamento->loadMissing(['categoria', 'conta', 'parcelamento']);

        return Response::successResponse(
            LancamentoResponseFormatter::format($lancamento),
            'Lançamento marcado como pago.'
        );
    }

    public function desmarcar(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
        if (!$lancamento) {
            return Response::errorResponse('Lancamento nao encontrado', 404);
        }

        try {
            $lancamento = $this->statusService->desmarcarPago($lancamento);
        } catch (\DomainException $e) {
            return $this->domainErrorResponse($e, 'Nao foi possivel marcar o lancamento como pendente.', 422);
        }

        if ($lancamento->parcelamento_id) {
            $this->parcelamentoRepo->atualizarParcelasPagas($lancamento->parcelamento_id);
        }

        $lancamento->loadMissing(['categoria', 'conta', 'parcelamento']);

        return Response::successResponse(
            LancamentoResponseFormatter::format($lancamento),
            'Lançamento marcado como pendente.'
        );
    }
}
