<?php

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Lancamento\LancamentoUpdateService;

class UpdateController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private LancamentoUpdateService $updateService;

    public function __construct()
    {
        parent::__construct();
        $this->lancamentoRepo = new LancamentoRepository();
        $this->updateService = new LancamentoUpdateService();
    }

    public function __invoke(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
        if (!$lancamento) {
            Response::error('Lancamento nao encontrado', 404);
            return;
        }

        if ((bool) ($lancamento->eh_transferencia ?? 0) === true) {
            $this->updateTransferencia($userId, $lancamento);
            return;
        }

        $result = $this->updateService->updateFromPayload($userId, $lancamento, $this->getRequestPayload());

        if ($result->isValidationError()) {
            Response::validationError($result->data['errors']);
            return;
        }

        if ($result->isError()) {
            Response::error($result->message, $result->httpCode);
            return;
        }

        Response::success($result->data['lancamento']);
    }

    /**
     * Atualiza uma transferência (data, valor, contas e descrição).
     */
    private function updateTransferencia(int $userId, $lancamento): void
    {
        $payload = $this->getRequestPayload();

        $errors = [];

        // Validar data
        $data = $payload['data'] ?? (string) $lancamento->data;
        if (empty($data)) {
            $errors['data'] = 'A data é obrigatória.';
        } elseif (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $data)) {
            $errors['data'] = 'Data inválida. Use o formato YYYY-MM-DD.';
        }

        // Validar valor
        $valor = $payload['valor'] ?? $lancamento->valor;
        if (is_string($valor)) {
            $valor = str_replace(['R$', ' ', '.'], '', $valor);
            $valor = str_replace(',', '.', $valor);
        }
        $valor = round(abs((float) $valor), 2);
        if ($valor <= 0) {
            $errors['valor'] = 'O valor deve ser maior que zero.';
        }

        // Validar contas
        $contaRepo = new ContaRepository();
        $contaOrigemId = isset($payload['conta_id']) ? (int) $payload['conta_id'] : (int) $lancamento->conta_id;
        $contaDestinoId = isset($payload['conta_id_destino']) ? (int) $payload['conta_id_destino'] : (int) $lancamento->conta_id_destino;

        if ($contaOrigemId === $contaDestinoId) {
            $errors['conta_id_destino'] = 'A conta de destino deve ser diferente da origem.';
        }

        if ($contaOrigemId && !$contaRepo->belongsToUser($contaOrigemId, $userId)) {
            $errors['conta_id'] = 'Conta de origem inválida.';
        }
        if ($contaDestinoId && !$contaRepo->belongsToUser($contaDestinoId, $userId)) {
            $errors['conta_id_destino'] = 'Conta de destino inválida.';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // Buscar nomes das contas para descrição automática
        $descricao = $payload['descricao'] ?? null;
        if ($descricao === null || trim($descricao) === '') {
            $origem = $contaRepo->findByIdAndUser($contaOrigemId, $userId);
            $destino = $contaRepo->findByIdAndUser($contaDestinoId, $userId);
            $nomeOrigem = $origem->nome ?? $origem->instituicao ?? 'Conta';
            $nomeDestino = $destino->nome ?? $destino->instituicao ?? 'Conta';
            $descricao = "Transferência: {$nomeOrigem} → {$nomeDestino}";
        }

        // Atualizar lancamento
        $this->lancamentoRepo->update($lancamento->id, [
            'data'             => $data,
            'valor'            => $valor,
            'conta_id'         => $contaOrigemId,
            'conta_id_destino' => $contaDestinoId,
            'descricao'        => mb_substr(trim($descricao), 0, 190),
        ]);

        $updated = $this->lancamentoRepo->findByIdAndUser($lancamento->id, $userId);
        $updated->loadMissing(['categoria', 'conta', 'subcategoria']);

        Response::success(\Application\Formatters\LancamentoResponseFormatter::format($updated));
    }
}
