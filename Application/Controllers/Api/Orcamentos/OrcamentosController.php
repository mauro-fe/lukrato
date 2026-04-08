<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Orcamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\DTO\ServiceResultDTO;
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
        ?SaveOrcamentoUseCase $saveOrcamentoUseCase = null,
        ?BulkSaveOrcamentosUseCase $bulkSaveOrcamentosUseCase = null,
        ?DeleteOrcamentoUseCase $deleteOrcamentoUseCase = null,
        ?GetOrcamentoSugestoesUseCase $getOrcamentoSugestoesUseCase = null,
        ?ApplyOrcamentoSugestoesUseCase $applyOrcamentoSugestoesUseCase = null,
        ?CopyOrcamentosMesUseCase $copyOrcamentosMesUseCase = null,
        ?GetOrcamentosListUseCase $getOrcamentosListUseCase = null
    ) {
        parent::__construct();

        $this->saveOrcamentoUseCase = $this->resolveOrCreate($saveOrcamentoUseCase, SaveOrcamentoUseCase::class);
        $this->bulkSaveOrcamentosUseCase = $this->resolveOrCreate($bulkSaveOrcamentosUseCase, BulkSaveOrcamentosUseCase::class);
        $this->deleteOrcamentoUseCase = $this->resolveOrCreate($deleteOrcamentoUseCase, DeleteOrcamentoUseCase::class);
        $this->getOrcamentoSugestoesUseCase = $this->resolveOrCreate($getOrcamentoSugestoesUseCase, GetOrcamentoSugestoesUseCase::class);
        $this->applyOrcamentoSugestoesUseCase = $this->resolveOrCreate($applyOrcamentoSugestoesUseCase, ApplyOrcamentoSugestoesUseCase::class);
        $this->copyOrcamentosMesUseCase = $this->resolveOrCreate($copyOrcamentosMesUseCase, CopyOrcamentosMesUseCase::class);
        $this->getOrcamentosListUseCase = $this->resolveOrCreate($getOrcamentosListUseCase, GetOrcamentosListUseCase::class);
    }

    public function index(): Response
    {
        return $this->respondUseCase(
            function (int $userId): ServiceResultDTO {
                $mes = $this->getIntQuery('mes', (int) date('m'));
                $ano = $this->getIntQuery('ano', (int) date('Y'));

                return $this->getOrcamentosListUseCase->execute($userId, $mes, $ano);
            },
            'Erro ao carregar orçamentos.'
        );
    }

    public function store(): Response
    {
        return $this->respondUseCase(
            fn(int $userId): ServiceResultDTO => $this->saveOrcamentoUseCase->execute($userId, $this->getRequestPayload()),
            'Erro ao salvar orçamento.'
        );
    }

    public function bulk(): Response
    {
        return $this->respondUseCase(
            fn(int $userId): ServiceResultDTO => $this->bulkSaveOrcamentosUseCase->execute($userId, $this->getRequestPayload()),
            'Erro ao salvar orçamentos.'
        );
    }

    public function destroy(mixed $id = null): Response
    {
        return $this->respondUseCase(
            fn(int $userId): ServiceResultDTO => $this->deleteOrcamentoUseCase->execute($userId, (int) $id),
            'Erro ao remover orçamento.',
            useResultData: false
        );
    }

    public function sugestoes(): Response
    {
        return $this->respondUseCase(
            fn(int $userId): ServiceResultDTO => $this->getOrcamentoSugestoesUseCase->execute($userId),
            'Erro ao gerar sugestões.'
        );
    }

    public function aplicarSugestoes(): Response
    {
        return $this->respondUseCase(
            fn(int $userId): ServiceResultDTO => $this->applyOrcamentoSugestoesUseCase->execute($userId, $this->getRequestPayload()),
            'Erro ao aplicar sugestões.'
        );
    }

    public function copiarMes(): Response
    {
        return $this->respondUseCase(
            fn(int $userId): ServiceResultDTO => $this->copyOrcamentosMesUseCase->execute($userId, $this->getRequestPayload()),
            'Erro ao copiar orçamentos.'
        );
    }

    /**
     * @param callable(int): ServiceResultDTO $callback
     */
    private function respondUseCase(callable $callback, string $errorMessage, bool $useResultData = true): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $callback($userId);

            return $this->respondServiceResult(
                $result,
                successData: $useResultData ? $result->data : null,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, $errorMessage);
        }
    }
}
