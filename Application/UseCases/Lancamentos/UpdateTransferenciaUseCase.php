<?php

declare(strict_types=1);

namespace Application\UseCases\Lancamentos;

use Application\DTO\ServiceResultDTO;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Models\Lancamento;
use Application\Models\Meta;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Metas\MetaProgressService;
use Application\Validators\LancamentoValidator;

class UpdateTransferenciaUseCase
{
    public function __construct(
        private readonly LancamentoRepository $lancamentoRepo = new LancamentoRepository(),
        private readonly ContaRepository $contaRepo = new ContaRepository(),
        private readonly MetaProgressService $metaProgressService = new MetaProgressService()
    ) {
    }

    /**
     * @param object $lancamento
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, object $lancamento, array $payload): ServiceResultDTO
    {
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
            return ServiceResultDTO::validationFail($errors);
        }

        $descricao = $payload['descricao'] ?? null;
        if ($descricao === null || trim((string) $descricao) === '') {
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

        $this->lancamentoRepo->update((int) $lancamento->id, [
            'data' => $data,
            'valor' => $valor,
            'meta_id' => $metaId,
            'meta_operacao' => $metaOperacao,
            'meta_valor' => $metaValor,
            'conta_id' => $contaOrigemId,
            'conta_id_destino' => $contaDestinoId,
            'descricao' => mb_substr(trim((string) $descricao), 0, 190),
            'observacao' => $observacao !== null ? mb_substr((string) $observacao, 0, 500) : null,
        ]);

        $this->recalculateAffectedMetas($userId, $metaAnteriorId, (int) ($metaId ?? 0));

        $updated = $this->lancamentoRepo->findByIdAndUser((int) $lancamento->id, $userId);
        if (!$updated instanceof Lancamento) {
            return ServiceResultDTO::error('Erro ao atualizar transferencia.', 500);
        }

        $updated->loadMissing(['categoria', 'conta', 'subcategoria', 'meta']);

        return ServiceResultDTO::ok('Lancamento atualizado', [
            'lancamento' => LancamentoResponseFormatter::format($updated),
        ]);
    }

    private function recalculateAffectedMetas(int $userId, int ...$metaIds): void
    {
        $metaIds = array_values(array_unique(array_filter($metaIds, static fn(int $metaId): bool => $metaId > 0)));

        foreach ($metaIds as $metaId) {
            $this->metaProgressService->recalculateMeta($userId, $metaId);
        }
    }
}
