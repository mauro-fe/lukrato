<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Metas;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Metas\MetaService;
use Application\UseCases\Metas\AddMetaAporteUseCase;
use Application\UseCases\Metas\CreateMetaUseCase;
use Application\UseCases\Metas\DeleteMetaUseCase;
use Application\UseCases\Metas\GetMetaTemplatesUseCase;
use Application\UseCases\Metas\GetMetasListUseCase;
use Application\UseCases\Metas\UpdateMetaUseCase;

class MetasController extends ApiController
{
    private CreateMetaUseCase $createMetaUseCase;
    private UpdateMetaUseCase $updateMetaUseCase;
    private AddMetaAporteUseCase $addMetaAporteUseCase;
    private DeleteMetaUseCase $deleteMetaUseCase;
    private GetMetaTemplatesUseCase $getMetaTemplatesUseCase;
    private GetMetasListUseCase $getMetasListUseCase;

    public function __construct(
        ?MetaService $metaService = null,
        ?DemoPreviewService $demoPreviewService = null,
        ?CreateMetaUseCase $createMetaUseCase = null,
        ?UpdateMetaUseCase $updateMetaUseCase = null,
        ?AddMetaAporteUseCase $addMetaAporteUseCase = null,
        ?DeleteMetaUseCase $deleteMetaUseCase = null,
        ?GetMetaTemplatesUseCase $getMetaTemplatesUseCase = null,
        ?GetMetasListUseCase $getMetasListUseCase = null
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

        $resolveDemoPreviewService = function () use (&$demoPreviewService): DemoPreviewService {
            $demoPreviewService = $this->resolveOrCreate(
                $demoPreviewService,
                DemoPreviewService::class,
                static fn(): DemoPreviewService => new DemoPreviewService()
            );

            return $demoPreviewService;
        };

        $this->createMetaUseCase = $this->resolveOrCreate(
            $createMetaUseCase,
            CreateMetaUseCase::class,
            fn(): CreateMetaUseCase => new CreateMetaUseCase($resolveMetaService())
        );
        $this->updateMetaUseCase = $this->resolveOrCreate(
            $updateMetaUseCase,
            UpdateMetaUseCase::class,
            fn(): UpdateMetaUseCase => new UpdateMetaUseCase($resolveMetaService())
        );
        $this->addMetaAporteUseCase = $this->resolveOrCreate(
            $addMetaAporteUseCase,
            AddMetaAporteUseCase::class,
            fn(): AddMetaAporteUseCase => new AddMetaAporteUseCase($resolveMetaService())
        );
        $this->deleteMetaUseCase = $this->resolveOrCreate(
            $deleteMetaUseCase,
            DeleteMetaUseCase::class,
            fn(): DeleteMetaUseCase => new DeleteMetaUseCase($resolveMetaService())
        );
        $this->getMetaTemplatesUseCase = $this->resolveOrCreate(
            $getMetaTemplatesUseCase,
            GetMetaTemplatesUseCase::class,
            fn(): GetMetaTemplatesUseCase => new GetMetaTemplatesUseCase($resolveMetaService())
        );
        $this->getMetasListUseCase = $this->resolveOrCreate(
            $getMetasListUseCase,
            GetMetasListUseCase::class,
            fn(): GetMetasListUseCase => new GetMetasListUseCase($resolveMetaService(), $resolveDemoPreviewService())
        );
    }

    public function index(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $status = $this->getQuery('status');
            $result = $this->getMetasListUseCase->execute($userId, is_string($status) ? $status : null);

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao carregar metas.');
        }
    }

    public function store(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->createMetaUseCase->execute($userId, $this->getRequestPayload());

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao criar meta.');
        }
    }

    public function update(mixed $id = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->updateMetaUseCase->execute($userId, (int) $id, $this->getRequestPayload());

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao atualizar meta.');
        }
    }

    public function aporte(mixed $id = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->addMetaAporteUseCase->execute($userId, (int) $id, $this->getRequestPayload());

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao registrar aporte.');
        }
    }

    public function destroy(mixed $id = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->deleteMetaUseCase->execute($userId, (int) $id);

            return $this->respondServiceResult(
                $result,
                successData: null,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao remover meta.');
        }
    }

    public function templates(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->getMetaTemplatesUseCase->execute($userId);

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao carregar templates.');
        }
    }
}
