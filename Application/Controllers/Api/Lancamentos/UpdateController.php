<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Models\Meta;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Financeiro\MetaProgressService;
use Application\Services\Lancamento\LancamentoUpdateService;
use Application\Validators\LancamentoValidator;

class UpdateController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private LancamentoUpdateService $updateService;
    private ContaRepository $contaRepo;
    private MetaProgressService $metaProgressService;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?LancamentoUpdateService $updateService = null,
        ?ContaRepository $contaRepo = null,
        ?MetaProgressService $metaProgressService = null
    ) {
        parent::__construct();
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->updateService = $updateService ?? new LancamentoUpdateService();
        $this->contaRepo = $contaRepo ?? new ContaRepository();
        $this->metaProgressService = $metaProgressService ?? new MetaProgressService();
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
            $errors['data'] = 'A data e obrigatoria.';
        } elseif (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $data)) {
            $errors['data'] = 'Data invalida. Use o formato YYYY-MM-DD.';
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
        $contaDestinoId = isset($payload['conta_id_destino'])
            ? (int) $payload['conta_id_destino']
            : (isset($payload['conta_destino_id']) ? (int) $payload['conta_destino_id'] : (int) $lancamento->conta_id_destino);

        if ($contaOrigemId === $contaDestinoId) {
            $errors['conta_id_destino'] = 'A conta de destino deve ser diferente da origem.';
        }

        if ($contaOrigemId && !$this->contaRepo->belongsToUser($contaOrigemId, $userId)) {
            $errors['conta_id'] = 'Conta de origem invalida.';
        }

        if ($contaDestinoId && !$this->contaRepo->belongsToUser($contaDestinoId, $userId)) {
            $errors['conta_id_destino'] = 'Conta de destino invalida.';
        }

        $metaId = array_key_exists('meta_id', $payload) ? $payload['meta_id'] : ($payload['metaId'] ?? $lancamento->meta_id);
        $metaId = is_scalar($metaId) && $metaId !== '' ? (int) $metaId : null;
        $metaId = LancamentoValidator::validateMetaOwnership($metaId, $userId, $errors);
        $meta = $metaId
            ? Meta::where('id', $metaId)->where('user_id', $userId)->first()
            : null;
        $metaOperacao = $metaId
            ? LancamentoValidator::resolveMetaOperationForContext(
                LancamentoValidator::normalizeMetaOperation($payload['meta_operacao'] ?? ($payload['metaOperacao'] ?? $lancamento->meta_operacao ?? null)),
                [
                    'tipo' => 'transferencia',
                    'eh_transferencia' => true,
                ],
                $meta
            )
            : null;
        $metaValor = $metaId
            ? (LancamentoValidator::sanitizeMetaValor($payload['meta_valor'] ?? ($payload['metaValor'] ?? $lancamento->meta_valor ?? null))
                ?? $valor)
            : null;
        LancamentoValidator::validateMetaLinkRules($metaId, [
            'tipo' => 'transferencia',
            'eh_transferencia' => true,
            'meta_operacao' => $metaOperacao,
            'meta_valor' => $metaValor,
            'valor' => $valor,
        ], $errors);

        if (!empty($errors)) {
            return Response::validationErrorResponse($errors);
        }

        $descricao = $payload['descricao'] ?? null;
        if ($descricao === null || trim($descricao) === '') {
            $origem = $this->contaRepo->findByIdAndUser($contaOrigemId, $userId);
            $destino = $this->contaRepo->findByIdAndUser($contaDestinoId, $userId);
            $nomeOrigem = $origem->nome ?? $origem->instituicao ?? 'Conta';
            $nomeDestino = $destino->nome ?? $destino->instituicao ?? 'Conta';
            $descricao = "Transferencia: {$nomeOrigem} -> {$nomeDestino}";
        }

        $observacao = array_key_exists('observacao', $payload)
            ? (trim((string) $payload['observacao']) ?: null)
            : $lancamento->observacao;
        $metaAnteriorId = (int) ($lancamento->meta_id ?? 0);

        $this->lancamentoRepo->update($lancamento->id, [
            'data' => $data,
            'valor' => $valor,
            'meta_id' => $metaId,
            'meta_operacao' => $metaOperacao,
            'meta_valor' => $metaValor,
            'conta_id' => $contaOrigemId,
            'conta_id_destino' => $contaDestinoId,
            'descricao' => mb_substr(trim($descricao), 0, 190),
            'observacao' => $observacao !== null ? mb_substr($observacao, 0, 500) : null,
        ]);

        $this->recalculateAffectedMetas($userId, $metaAnteriorId, (int) ($metaId ?? 0));

        $updated = $this->lancamentoRepo->findByIdAndUser($lancamento->id, $userId);
        $updated->loadMissing(['categoria', 'conta', 'subcategoria', 'meta']);

        return Response::successResponse(LancamentoResponseFormatter::format($updated));
    }

    private function recalculateAffectedMetas(int $userId, int ...$metaIds): void
    {
        $metaIds = array_values(array_unique(array_filter($metaIds, static fn(int $metaId): bool => $metaId > 0)));

        foreach ($metaIds as $metaId) {
            $this->metaProgressService->recalculateMeta($userId, $metaId);
        }
    }
}
