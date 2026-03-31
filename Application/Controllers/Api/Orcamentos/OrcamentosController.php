<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Orcamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Financeiro\OrcamentoService;
use Application\UseCases\Orcamentos\ApplyOrcamentoSugestoesUseCase;
use Application\UseCases\Orcamentos\BulkSaveOrcamentosUseCase;
use Application\UseCases\Orcamentos\CopyOrcamentosMesUseCase;
use Application\UseCases\Orcamentos\DeleteOrcamentoUseCase;
use Application\UseCases\Orcamentos\GetOrcamentosListUseCase;
use Application\UseCases\Orcamentos\GetOrcamentoSugestoesUseCase;
use Application\UseCases\Orcamentos\SaveOrcamentoUseCase;

class OrcamentosController extends ApiController
{
    private SaveOrcamentoUseCase $saveOrcamentoUseCase;
    private BulkSaveOrcamentosUseCase $bulkSaveOrcamentosUseCase;
    private DeleteOrcamentoUseCase $deleteOrcamentoUseCase;
    private GetOrcamentoSugestoesUseCase $getOrcamentoSugestoesUseCase;
    private ApplyOrcamentoSugestoesUseCase $applyOrcamentoSugestoesUseCase;
    private CopyOrcamentosMesUseCase $copyOrcamentosMesUseCase;
    private GetOrcamentosListUseCase $getOrcamentosListUseCase;

    public function __construct(
        ?OrcamentoService $orcamentoService = null,
        ?DemoPreviewService $demoPreviewService = null,
        ?SaveOrcamentoUseCase $saveOrcamentoUseCase = null,
        ?BulkSaveOrcamentosUseCase $bulkSaveOrcamentosUseCase = null,
        ?DeleteOrcamentoUseCase $deleteOrcamentoUseCase = null,
        ?GetOrcamentoSugestoesUseCase $getOrcamentoSugestoesUseCase = null,
        ?ApplyOrcamentoSugestoesUseCase $applyOrcamentoSugestoesUseCase = null,
        ?CopyOrcamentosMesUseCase $copyOrcamentosMesUseCase = null,
        ?GetOrcamentosListUseCase $getOrcamentosListUseCase = null
    ) {
        parent::__construct();

        $orcamentoService ??= new OrcamentoService();
        $demoPreviewService ??= new DemoPreviewService();

        $this->saveOrcamentoUseCase = $saveOrcamentoUseCase ?? new SaveOrcamentoUseCase($orcamentoService);
        $this->bulkSaveOrcamentosUseCase = $bulkSaveOrcamentosUseCase ?? new BulkSaveOrcamentosUseCase($orcamentoService);
        $this->deleteOrcamentoUseCase = $deleteOrcamentoUseCase ?? new DeleteOrcamentoUseCase($orcamentoService);
        $this->getOrcamentoSugestoesUseCase = $getOrcamentoSugestoesUseCase
            ?? new GetOrcamentoSugestoesUseCase($orcamentoService);
        $this->applyOrcamentoSugestoesUseCase = $applyOrcamentoSugestoesUseCase
            ?? new ApplyOrcamentoSugestoesUseCase($orcamentoService);
        $this->copyOrcamentosMesUseCase = $copyOrcamentosMesUseCase ?? new CopyOrcamentosMesUseCase($orcamentoService);
        $this->getOrcamentosListUseCase = $getOrcamentosListUseCase
            ?? new GetOrcamentosListUseCase($orcamentoService, $demoPreviewService);
    }

    public function index(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $mes = $this->getIntQuery('mes', (int) date('m'));
            $ano = $this->getIntQuery('ano', (int) date('Y'));
            $result = $this->getOrcamentosListUseCase->execute($userId, $mes, $ano);

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao carregar orçamentos.');
        }
    }

    public function store(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->saveOrcamentoUseCase->execute($userId, $this->getRequestPayload());

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao salvar orçamento.');
        }
    }

    public function bulk(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->bulkSaveOrcamentosUseCase->execute($userId, $this->getRequestPayload());

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao salvar orçamentos.');
        }
    }

    public function destroy(mixed $id = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->deleteOrcamentoUseCase->execute($userId, (int) $id);

            return $this->respondServiceResult(
                $result,
                successData: null,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao remover orçamento.');
        }
    }

    public function sugestoes(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->getOrcamentoSugestoesUseCase->execute($userId);

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao gerar sugestões.');
        }
    }

    public function aplicarSugestoes(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->applyOrcamentoSugestoesUseCase->execute($userId, $this->getRequestPayload());

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao aplicar sugestões.');
        }
    }

    public function copiarMes(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->copyOrcamentosMesUseCase->execute($userId, $this->getRequestPayload());

            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao copiar orçamentos.');
        }
    }
}
