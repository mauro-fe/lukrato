<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Financas;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\UseCases\Financas\GetFinanceiroMetricsUseCase;
use Application\UseCases\Financas\GetFinanceiroOptionsUseCase;
use Application\UseCases\Financas\GetFinanceiroTransactionsUseCase;
use Throwable;

class MetricsController extends ApiController
{
    private GetFinanceiroMetricsUseCase $getMetricsUseCase;
    private GetFinanceiroTransactionsUseCase $getTransactionsUseCase;
    private GetFinanceiroOptionsUseCase $getOptionsUseCase;

    public function __construct(
        ?GetFinanceiroMetricsUseCase $getMetricsUseCase = null,
        ?GetFinanceiroTransactionsUseCase $getTransactionsUseCase = null,
        ?GetFinanceiroOptionsUseCase $getOptionsUseCase = null
    ) {
        parent::__construct();

        $this->getMetricsUseCase = $this->resolveOrCreate($getMetricsUseCase, GetFinanceiroMetricsUseCase::class);
        $this->getTransactionsUseCase = $this->resolveOrCreate($getTransactionsUseCase, GetFinanceiroTransactionsUseCase::class);
        $this->getOptionsUseCase = $this->resolveOrCreate($getOptionsUseCase, GetFinanceiroOptionsUseCase::class);
    }

    public function metrics(): Response
    {
        $uid = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $period = $this->parseYearMonth($this->getStringQuery('month', date('Y-m')));
            $viewType = $this->getStringQuery('view', 'caixa');
            $result = $this->getMetricsUseCase->execute(
                $uid,
                $period['start'],
                $period['end'],
                $viewType
            );

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao carregar metricas.');
        }
    }

    public function transactions(): Response
    {
        $uid = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $period = $this->parseYearMonth($this->getStringQuery('month', date('Y-m')));
            $limit = min($this->getIntQuery('limit', 50), 1000);
            $result = $this->getTransactionsUseCase->execute(
                $uid,
                $period['start'],
                $period['end'],
                $limit
            );

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao carregar transacoes.');
        }
    }

    public function options(): Response
    {
        $uid = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $result = $this->getOptionsUseCase->execute($uid);

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao carregar opcoes.');
        }
    }
}
