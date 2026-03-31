<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Financas;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\UseCases\Financas\GetFinanceiroMetricsUseCase;
use Application\UseCases\Financas\GetFinanceiroOptionsUseCase;
use Application\UseCases\Financas\GetFinanceiroTransactionsUseCase;
use Throwable;

class MetricsController extends ApiController
{
    private LancamentoRepository $lancamentoRepo;
    private CategoriaRepository $categoriaRepo;
    private ContaRepository $contaRepo;
    private GetFinanceiroMetricsUseCase $getMetricsUseCase;
    private GetFinanceiroTransactionsUseCase $getTransactionsUseCase;
    private GetFinanceiroOptionsUseCase $getOptionsUseCase;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?CategoriaRepository $categoriaRepo = null,
        ?ContaRepository $contaRepo = null,
        ?GetFinanceiroMetricsUseCase $getMetricsUseCase = null,
        ?GetFinanceiroTransactionsUseCase $getTransactionsUseCase = null,
        ?GetFinanceiroOptionsUseCase $getOptionsUseCase = null
    ) {
        parent::__construct();

        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->categoriaRepo = $categoriaRepo ?? new CategoriaRepository();
        $this->contaRepo = $contaRepo ?? new ContaRepository();
        $this->getMetricsUseCase = $getMetricsUseCase
            ?? new GetFinanceiroMetricsUseCase($this->lancamentoRepo);
        $this->getTransactionsUseCase = $getTransactionsUseCase
            ?? new GetFinanceiroTransactionsUseCase($this->lancamentoRepo);
        $this->getOptionsUseCase = $getOptionsUseCase
            ?? new GetFinanceiroOptionsUseCase($this->categoriaRepo, $this->contaRepo);
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
