<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Formatters\LancamentoResponseFormatter;
use Application\UseCases\Lancamentos\ToggleLancamentoPagoUseCase;

class MarcarPagoController extends ApiController
{
    private ToggleLancamentoPagoUseCase $togglePagoUseCase;

    public function __construct(
        ?ToggleLancamentoPagoUseCase $togglePagoUseCase = null
    ) {
        parent::__construct();

        $this->togglePagoUseCase = $this->resolveOrCreate($togglePagoUseCase, ToggleLancamentoPagoUseCase::class);
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
