<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Financas;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\UseCases\Financas\GetFinancasInsightsUseCase;
use Application\UseCases\Financas\GetFinancasResumoUseCase;

class ResumoController extends ApiController
{
    private GetFinancasResumoUseCase $getFinancasResumoUseCase;
    private GetFinancasInsightsUseCase $getFinancasInsightsUseCase;

    public function __construct(
        ?GetFinancasResumoUseCase $getFinancasResumoUseCase = null,
        ?GetFinancasInsightsUseCase $getFinancasInsightsUseCase = null
    ) {
        parent::__construct();

        $this->getFinancasResumoUseCase = $this->resolveOrCreate($getFinancasResumoUseCase, GetFinancasResumoUseCase::class);
        $this->getFinancasInsightsUseCase = $this->resolveOrCreate($getFinancasInsightsUseCase, GetFinancasInsightsUseCase::class);
    }

    public function resumo(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $mes = $this->getIntQuery('mes', (int) date('m'));
            $ano = $this->getIntQuery('ano', (int) date('Y'));
            $result = $this->getFinancasResumoUseCase->execute($userId, $mes, $ano);

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao carregar resumo financeiro.');
        }
    }

    public function insights(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $mes = $this->getIntQuery('mes', (int) date('m'));
            $ano = $this->getIntQuery('ano', (int) date('Y'));
            $result = $this->getFinancasInsightsUseCase->execute($userId, $mes, $ano);

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao gerar insights.');
        }
    }
}
