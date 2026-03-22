<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Lancamento\LancamentoUpdateService;

class UpdateController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private LancamentoUpdateService $updateService;
    private ContaRepository $contaRepo;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?LancamentoUpdateService $updateService = null,
        ?ContaRepository $contaRepo = null
    ) {
        parent::__construct();
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->updateService = $updateService ?? new LancamentoUpdateService();
        $this->contaRepo = $contaRepo ?? new ContaRepository();
    }

    public function __invoke(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
        if (!$lancamento) {
            return Response::errorResponse('Lancamento nao encontrado', 404);
        }

        if ((bool) ($lancamento->eh_transferencia ?? 0) === true) {
            return $this->updateTransferencia($userId, $lancamento);
        }

        $result = $this->updateService->updateFromPayload($userId, $lancamento, $this->getRequestPayload());

        if ($result->isValidationError()) {
            return Response::validationErrorResponse($result->data['errors']);
        }

        if ($result->isError()) {
            return Response::errorResponse($result->message, $result->httpCode);
        }

        return Response::successResponse($result->data['lancamento']);
    }

    private function updateTransferencia(int $userId, mixed $lancamento): Response
    {
        $payload = $this->getRequestPayload();
        $errors = [];

        $data = $payload['data'] ?? (string) $lancamento->data;
        if (empty($data)) {
            $errors['data'] = 'A data é obrigatória.';
        } elseif (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $data)) {
            $errors['data'] = 'Data inválida. Use o formato YYYY-MM-DD.';
        }

        $valor = $payload['valor'] ?? $lancamento->valor;
        if (is_string($valor)) {
            $valor = str_replace(['R$', ' ', '.'], '', $valor);
            $valor = str_replace(',', '.', $valor);
        }
        $valor = round(abs((float) $valor), 2);
        if ($valor <= 0) {
            $errors['valor'] = 'O valor deve ser maior que zero.';
        }

        $contaOrigemId = isset($payload['conta_id']) ? (int) $payload['conta_id'] : (int) $lancamento->conta_id;
        $contaDestinoId = isset($payload['conta_id_destino']) ? (int) $payload['conta_id_destino'] : (int) $lancamento->conta_id_destino;

        if ($contaOrigemId === $contaDestinoId) {
            $errors['conta_id_destino'] = 'A conta de destino deve ser diferente da origem.';
        }

        if ($contaOrigemId && !$this->contaRepo->belongsToUser($contaOrigemId, $userId)) {
            $errors['conta_id'] = 'Conta de origem inválida.';
        }

        if ($contaDestinoId && !$this->contaRepo->belongsToUser($contaDestinoId, $userId)) {
            $errors['conta_id_destino'] = 'Conta de destino inválida.';
        }

        if (!empty($errors)) {
            return Response::validationErrorResponse($errors);
        }

        $descricao = $payload['descricao'] ?? null;
        if ($descricao === null || trim($descricao) === '') {
            $origem = $this->contaRepo->findByIdAndUser($contaOrigemId, $userId);
            $destino = $this->contaRepo->findByIdAndUser($contaDestinoId, $userId);
            $nomeOrigem = $origem->nome ?? $origem->instituicao ?? 'Conta';
            $nomeDestino = $destino->nome ?? $destino->instituicao ?? 'Conta';
            $descricao = "Transferência: {$nomeOrigem} → {$nomeDestino}";
        }

        $this->lancamentoRepo->update($lancamento->id, [
            'data' => $data,
            'valor' => $valor,
            'conta_id' => $contaOrigemId,
            'conta_id_destino' => $contaDestinoId,
            'descricao' => mb_substr(trim($descricao), 0, 190),
        ]);

        $updated = $this->lancamentoRepo->findByIdAndUser($lancamento->id, $userId);
        $updated->loadMissing(['categoria', 'conta', 'subcategoria']);

        return Response::successResponse(LancamentoResponseFormatter::format($updated));
    }
}
