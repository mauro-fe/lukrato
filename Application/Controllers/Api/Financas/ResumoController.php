<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Financas;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Metas\MetaService;
use Application\Services\Orcamentos\OrcamentoService;
use Application\UseCases\Financas\GetFinancasInsightsUseCase;
use Application\UseCases\Financas\GetFinancasResumoUseCase;

class ResumoController extends ApiController
{
    private GetFinancasResumoUseCase $getFinancasResumoUseCase;
    private GetFinancasInsightsUseCase $getFinancasInsightsUseCase;

    public function __construct(
        ?MetaService $metaService = null,
        ?OrcamentoService $orcamentoService = null,
        ?DemoPreviewService $demoPreviewService = null,
        ?GetFinancasResumoUseCase $getFinancasResumoUseCase = null,
        ?GetFinancasInsightsUseCase $getFinancasInsightsUseCase = null
    ) {
        parent::__construct();

        $resolveMetaService = function () use (&$metaService): MetaService {
            $metaService = $this->resolveOrCreate(
                $metaService,
                MetaService::class,
                static fn(): MetaService => new MetaService()
            );

            return $metaService;
        };

        $resolveOrcamentoService = function () use (&$orcamentoService): OrcamentoService {
            $orcamentoService = $this->resolveOrCreate(
                $orcamentoService,
                OrcamentoService::class,
                static fn(): OrcamentoService => new OrcamentoService()
            );

            return $orcamentoService;
        };

        $resolveDemoPreviewService = function () use (&$demoPreviewService): DemoPreviewService {
            $demoPreviewService = $this->resolveOrCreate(
                $demoPreviewService,
                DemoPreviewService::class,
                static fn(): DemoPreviewService => new DemoPreviewService()
            );

            return $demoPreviewService;
        };

        $this->getFinancasResumoUseCase = $this->resolveOrCreate(
            $getFinancasResumoUseCase,
            GetFinancasResumoUseCase::class,
            fn(): GetFinancasResumoUseCase => new GetFinancasResumoUseCase(
                $resolveMetaService(),
                $resolveOrcamentoService(),
                $resolveDemoPreviewService()
            )
        );
        $this->getFinancasInsightsUseCase = $this->resolveOrCreate(
            $getFinancasInsightsUseCase,
            GetFinancasInsightsUseCase::class,
            fn(): GetFinancasInsightsUseCase => new GetFinancasInsightsUseCase(
                $resolveOrcamentoService(),
                $resolveDemoPreviewService()
            )
        );
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
