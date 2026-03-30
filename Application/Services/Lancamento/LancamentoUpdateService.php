<?php

declare(strict_types=1);

namespace Application\Services\Lancamento;

use Application\DTO\ServiceResultDTO;
use Application\DTO\Requests\UpdateLancamentoDTO;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Models\Lancamento;
use Application\Models\Meta;
use Application\Repositories\LancamentoRepository;
use Application\Services\AI\Rules\CategoryRuleEngine;
use Application\Services\Financeiro\MetaProgressService;
use Application\Validators\LancamentoValidator;

/**
 * Service responsável pela atualização de lançamentos.
 *
 * Encapsula: merge do payload com dados atuais, validação,
 * construção do DTO, tratamento do flag pago e persistência.
 */
class LancamentoUpdateService
{
    private LancamentoRepository $lancamentoRepo;
    private LancamentoStatusService $statusService;
    private MetaProgressService $metaProgressService;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?LancamentoStatusService $statusService = null,
        ?MetaProgressService $metaProgressService = null
    ) {
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->statusService = $statusService ?? new LancamentoStatusService();
        $this->metaProgressService = $metaProgressService ?? new MetaProgressService();
    }

    /**
     * Atualiza um lançamento a partir do payload bruto do request.
     *
     * @param int $userId
     * @param Lancamento $lancamento Lançamento existente
     * @param array $payload Dados do request
     * @return ServiceResultDTO
     */
    public function updateFromPayload(int $userId, Lancamento $lancamento, array $payload): ServiceResultDTO
    {
        $mergedData = $this->mergeWithExisting($payload, $lancamento);

        // Validar
        $errors = LancamentoValidator::validateUpdate($mergedData);

        $contaId     = is_scalar($mergedData['conta_id']) ? (int) $mergedData['conta_id'] : null;
        $contaId     = LancamentoValidator::validateContaOwnership($contaId, $userId, $errors);
        $categoriaId = is_scalar($mergedData['categoria_id']) ? (int) $mergedData['categoria_id'] : null;
        $categoriaId = LancamentoValidator::validateCategoriaOwnership($categoriaId, $userId, $errors);
        $metaId = $mergedData['meta_id'] ?? null;
        $metaId = is_scalar($metaId) && $metaId !== '' ? (int) $metaId : null;
        $metaId = LancamentoValidator::validateMetaOwnership($metaId, $userId, $errors);
        $meta = $metaId
            ? Meta::where('id', $metaId)->where('user_id', $userId)->first()
            : null;
        $metaOperacao = $metaId
            ? LancamentoValidator::resolveMetaOperationForContext(
                LancamentoValidator::normalizeMetaOperation($mergedData['meta_operacao'] ?? null),
                [
                    'tipo' => strtolower(trim((string) ($mergedData['tipo'] ?? $lancamento->tipo))),
                    'eh_transferencia' => (bool) ($lancamento->eh_transferencia ?? false),
                ],
                $meta
            )
            : null;
        $metaValor = $metaId
            ? (LancamentoValidator::sanitizeMetaValor($mergedData['meta_valor'] ?? null)
                ?? LancamentoValidator::sanitizeValor($mergedData['valor'] ?? $lancamento->valor))
            : null;
        LancamentoValidator::validateMetaLinkRules($metaId, [
            'tipo' => strtolower(trim((string) ($mergedData['tipo'] ?? $lancamento->tipo))),
            'eh_transferencia' => (bool) ($lancamento->eh_transferencia ?? false),
            'forma_pagamento' => (string) ($mergedData['forma_pagamento'] ?? $lancamento->forma_pagamento ?? ''),
            'meta_operacao' => $metaOperacao,
            'meta_valor' => $metaValor,
            'valor' => $mergedData['valor'] ?? $lancamento->valor,
        ], $errors);

        // Validar subcategoria (opcional)
        $subcategoriaId = $mergedData['subcategoria_id'] ?? null;
        $subcategoriaId = is_scalar($subcategoriaId) && !empty($subcategoriaId) ? (int) $subcategoriaId : null;

        // Se a categoria mudou, limpar subcategoria (pode não pertencer à nova categoria)
        $categoriaChanged = $categoriaId !== (int) $lancamento->categoria_id;
        if ($categoriaChanged && $subcategoriaId && !array_key_exists('subcategoria_id', $payload)) {
            $subcategoriaId = null;
        }

        if ($subcategoriaId && $categoriaId) {
            $subcategoriaId = LancamentoValidator::validateSubcategoriaOwnership($subcategoriaId, $categoriaId, $userId, $errors);
        }

        if (!empty($errors)) {
            return ServiceResultDTO::validationFail($errors);
        }

        // Construir DTO
        $dto = UpdateLancamentoDTO::fromRequest([
            'tipo'            => strtolower(trim($mergedData['tipo'])),
            'data'            => $mergedData['data'],
            'hora_lancamento' => $mergedData['hora_lancamento'] ?? null,
            'valor'           => LancamentoValidator::sanitizeValor($mergedData['valor']),
            'descricao'       => mb_substr(trim($mergedData['descricao'] ?? ''), 0, 190),
            'observacao'      => mb_substr(trim($mergedData['observacao'] ?? ''), 0, 500),
            'categoria_id'    => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
            'meta_id'         => $metaId,
            'meta_operacao'   => $metaOperacao,
            'meta_valor'      => $metaValor,
            'conta_id'        => $contaId,
            'forma_pagamento' => $mergedData['forma_pagamento'] ?? null,
        ]);

        $updateData = $dto->toArray();
        $metaAnteriorId = (int) ($lancamento->meta_id ?? 0);

        // Tratar flag pago se enviado
        if (array_key_exists('pago', $payload)) {
            $updateData += $this->statusService->buildPagoPayload((bool) $payload['pago']);
        }

        $this->lancamentoRepo->update($lancamento->id, $updateData);

        // Aprender categorização quando o usuário muda a categoria de um lançamento
        if ($categoriaChanged && $categoriaId && !empty($lancamento->descricao)) {
            CategoryRuleEngine::learn(
                $userId,
                $lancamento->descricao,
                $categoriaId,
                $subcategoriaId,
                'correction'
            );
        }

        $lancamento = $this->lancamentoRepo->find($lancamento->id);
        $lancamento->loadMissing(['categoria', 'conta', 'subcategoria', 'meta']);

        $this->recalculateAffectedMetas($userId, $metaAnteriorId, (int) ($lancamento->meta_id ?? 0));

        return ServiceResultDTO::ok(
            'Lançamento atualizado',
            ['lancamento' => LancamentoResponseFormatter::format($lancamento)]
        );
    }

    /**
     * Mescla o payload do request com os dados atuais do lançamento.
     * Campos não enviados mantêm o valor original.
     */
    private function mergeWithExisting(array $payload, Lancamento $lancamento): array
    {
        return [
            'tipo'            => $payload['tipo'] ?? $lancamento->tipo,
            'data'            => $payload['data'] ?? $lancamento->data,
            'hora_lancamento' => array_key_exists('hora_lancamento', $payload)
                ? ($payload['hora_lancamento'] ?: null)
                : $lancamento->hora_lancamento,
            'valor'           => $payload['valor'] ?? $lancamento->valor,
            'descricao'       => $payload['descricao'] ?? $lancamento->descricao,
            'observacao'      => $payload['observacao'] ?? $lancamento->observacao,
            'meta_id'         => array_key_exists('meta_id', $payload)
                ? $payload['meta_id']
                : ($payload['metaId'] ?? $lancamento->meta_id),
            'meta_operacao'   => array_key_exists('meta_operacao', $payload)
                ? $payload['meta_operacao']
                : ($payload['metaOperacao'] ?? $lancamento->meta_operacao),
            'meta_valor'      => array_key_exists('meta_valor', $payload)
                ? $payload['meta_valor']
                : ($payload['metaValor'] ?? $lancamento->meta_valor),
            'conta_id'        => $payload['conta_id'] ?? $payload['contaId'] ?? $lancamento->conta_id,
            'categoria_id'    => $payload['categoria_id'] ?? $payload['categoriaId'] ?? $lancamento->categoria_id,
            'subcategoria_id' => $payload['subcategoria_id'] ?? $payload['subcategoriaId'] ?? $lancamento->subcategoria_id,
            'forma_pagamento' => array_key_exists('forma_pagamento', $payload)
                ? $payload['forma_pagamento']
                : $lancamento->forma_pagamento,
        ];
    }

    private function recalculateAffectedMetas(int $userId, int ...$metaIds): void
    {
        $metaIds = array_values(array_unique(array_filter($metaIds, static fn(int $metaId): bool => $metaId > 0)));

        foreach ($metaIds as $metaId) {
            $this->metaProgressService->recalculateMeta($userId, $metaId);
        }
    }
}
