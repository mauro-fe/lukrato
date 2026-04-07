<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Services\Lancamento\LancamentoStatusService;
use Application\UseCases\Lancamentos\ToggleLancamentoPagoUseCase;

class MarcarPagoController extends ApiController
{
    private ToggleLancamentoPagoUseCase $togglePagoUseCase;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?LancamentoStatusService $statusService = null,
        ?ParcelamentoRepository $parcelamentoRepo = null,
        ?ToggleLancamentoPagoUseCase $togglePagoUseCase = null
    ) {
        parent::__construct();

        if ($togglePagoUseCase !== null) {
            $this->togglePagoUseCase = $togglePagoUseCase;

            return;
        }

        if ($lancamentoRepo !== null || $statusService !== null || $parcelamentoRepo !== null) {
            $this->togglePagoUseCase = new ToggleLancamentoPagoUseCase(
                $this->resolveOrCreate($lancamentoRepo, LancamentoRepository::class),
                $this->resolveOrCreate($statusService, LancamentoStatusService::class),
                $this->resolveOrCreate($parcelamentoRepo, ParcelamentoRepository::class)
            );

            return;
        }

        $this->togglePagoUseCase = $this->resolveOrCreate(
            null,
            ToggleLancamentoPagoUseCase::class,
            fn(): ToggleLancamentoPagoUseCase => new ToggleLancamentoPagoUseCase()
        );
    }

    public function __invoke(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $result = $this->togglePagoUseCase->execute($userId, $id, true);

        $lancamento = $result->data['lancamento'] ?? null;
        if ($lancamento === null) {
            return $this->respondServiceResult($result);
        }

        $lancamento->loadMissing(['categoria', 'conta', 'parcelamento']);

        return $this->respondServiceResult(
            $result,
            successData: LancamentoResponseFormatter::format($lancamento),
            successMessage: $result->message
        );
    }

    public function desmarcar(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $result = $this->togglePagoUseCase->execute($userId, $id, false);

        $lancamento = $result->data['lancamento'] ?? null;
        if ($lancamento === null) {
            return $this->respondServiceResult($result);
        }

        $lancamento->loadMissing(['categoria', 'conta', 'parcelamento']);

        return $this->respondServiceResult(
            $result,
            successData: LancamentoResponseFormatter::format($lancamento),
            successMessage: $result->message
        );
    }
}
