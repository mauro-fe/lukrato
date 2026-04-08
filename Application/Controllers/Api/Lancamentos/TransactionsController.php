<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\UseCases\Lancamentos\CreateLancamentoUseCase;
use Application\UseCases\Lancamentos\CreateTransferenciaUseCase;
use Application\UseCases\Lancamentos\UpdateLancamentoUseCase;
use Throwable;

class TransactionsController extends ApiController
{
    private CreateLancamentoUseCase $createLancamentoUseCase;
    private UpdateLancamentoUseCase $updateLancamentoUseCase;
    private CreateTransferenciaUseCase $createTransferenciaUseCase;

    public function __construct(
        ?CreateLancamentoUseCase $createLancamentoUseCase = null,
        ?UpdateLancamentoUseCase $updateLancamentoUseCase = null,
        ?CreateTransferenciaUseCase $createTransferenciaUseCase = null
    ) {
        parent::__construct();
        $this->createLancamentoUseCase = $this->resolveOrCreate($createLancamentoUseCase, CreateLancamentoUseCase::class);
        $this->updateLancamentoUseCase = $this->resolveOrCreate($updateLancamentoUseCase, UpdateLancamentoUseCase::class);
        $this->createTransferenciaUseCase = $this->resolveOrCreate($createTransferenciaUseCase, CreateTransferenciaUseCase::class);
    }

    public function store(): Response
    {
        try {
            $uid = $this->requireApiUserIdOrFail();
            $result = $this->createLancamentoUseCase->execute($uid, $this->getRequestPayload());
            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (Throwable $e) {
            return Response::errorResponse('Erro ao salvar lancamento.', 500);
        }
    }

    public function update(mixed $routeParam = null): Response
    {
        try {
            $uid = $this->requireApiUserIdOrFail();
            $id = $this->extractLancamentoId($routeParam);
            $result = $this->updateLancamentoUseCase->execute($uid, $id, $this->getRequestPayload());
            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao atualizar lancamento.');
        }
    }

    public function transfer(): Response
    {
        try {
            $uid = $this->requireApiUserIdOrFail();
            $result = $this->createTransferenciaUseCase->execute($uid, $this->getRequestPayload());
            return $this->respondServiceResult(
                $result,
                successData: $result->data,
                successMessage: $result->message,
                successStatus: $result->httpCode
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao realizar transferencia.');
        }
    }

    private function extractLancamentoId(mixed $routeParam): int
    {
        return (int) (is_array($routeParam) ? ($routeParam['id'] ?? 0) : $routeParam);
    }
}
