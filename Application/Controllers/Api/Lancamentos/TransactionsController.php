<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Conta\TransferenciaService;
use Application\Services\Metas\MetaProgressService;
use Application\Services\Lancamento\LancamentoLimitService;
use Application\UseCases\Lancamentos\CreateLancamentoUseCase;
use Application\UseCases\Lancamentos\CreateTransferenciaUseCase;
use Application\UseCases\Lancamentos\UpdateLancamentoUseCase;
use Throwable;

class TransactionsController extends ApiController
{
    private LancamentoLimitService $limitService;
    private TransferenciaService $transferenciaService;
    private LancamentoRepository $lancamentoRepo;
    private CategoriaRepository $categoriaRepo;
    private ContaRepository $contaRepo;
    private MetaProgressService $metaProgressService;
    private CreateLancamentoUseCase $createLancamentoUseCase;
    private UpdateLancamentoUseCase $updateLancamentoUseCase;
    private CreateTransferenciaUseCase $createTransferenciaUseCase;

    public function __construct(
        ?LancamentoLimitService $limitService = null,
        ?TransferenciaService $transferenciaService = null,
        ?LancamentoRepository $lancamentoRepo = null,
        ?CategoriaRepository $categoriaRepo = null,
        ?ContaRepository $contaRepo = null,
        ?MetaProgressService $metaProgressService = null,
        ?CreateLancamentoUseCase $createLancamentoUseCase = null,
        ?UpdateLancamentoUseCase $updateLancamentoUseCase = null,
        ?CreateTransferenciaUseCase $createTransferenciaUseCase = null
    ) {
        parent::__construct();
        $this->limitService = $limitService ?? new LancamentoLimitService();
        $this->transferenciaService = $transferenciaService ?? new TransferenciaService();
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->categoriaRepo = $categoriaRepo ?? new CategoriaRepository();
        $this->contaRepo = $contaRepo ?? new ContaRepository();
        $this->metaProgressService = $metaProgressService ?? new MetaProgressService();
        $this->createLancamentoUseCase = $createLancamentoUseCase
            ?? new CreateLancamentoUseCase(
                $this->limitService,
                $this->lancamentoRepo,
                $this->categoriaRepo,
                $this->contaRepo,
                $this->metaProgressService
            );
        $this->updateLancamentoUseCase = $updateLancamentoUseCase
            ?? new UpdateLancamentoUseCase(
                $this->lancamentoRepo,
                $this->categoriaRepo,
                $this->contaRepo,
                $this->metaProgressService
            );
        $this->createTransferenciaUseCase = $createTransferenciaUseCase
            ?? new CreateTransferenciaUseCase($this->transferenciaService);
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
