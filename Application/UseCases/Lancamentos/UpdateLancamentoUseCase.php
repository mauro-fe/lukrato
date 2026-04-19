<?php

declare(strict_types=1);

namespace Application\UseCases\Lancamentos;

use Application\Container\ApplicationContainer;
use Application\DTO\Requests\UpdateLancamentoDTO;
use Application\DTO\ServiceResultDTO;
use Application\Models\Lancamento;
use Application\Models\Meta;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Metas\MetaProgressService;
use Application\Validators\LancamentoValidator;
use ValueError;

class UpdateLancamentoUseCase
{
    private readonly LancamentoRepository $lancamentoRepo;
    private readonly CategoriaRepository $categoriaRepo;
    private readonly ContaRepository $contaRepo;
    private readonly MetaProgressService $metaProgressService;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?CategoriaRepository $categoriaRepo = null,
        ?ContaRepository $contaRepo = null,
        ?MetaProgressService $metaProgressService = null
    ) {
        $this->lancamentoRepo = ApplicationContainer::resolveOrNew($lancamentoRepo, LancamentoRepository::class);
        $this->categoriaRepo = ApplicationContainer::resolveOrNew($categoriaRepo, CategoriaRepository::class);
        $this->contaRepo = ApplicationContainer::resolveOrNew($contaRepo, ContaRepository::class);
        $this->metaProgressService = ApplicationContainer::resolveOrNew($metaProgressService, MetaProgressService::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, int $lancamentoId, array $payload): ServiceResultDTO
    {
        try {
            if ($lancamentoId <= 0) {
                throw new ValueError('ID inválido.');
            }

            $lancamento = $this->lancamentoRepo->findByIdAndUser($lancamentoId, $userId);
            if (!$lancamento) {
                return ServiceResultDTO::fail('Lançamento não encontrado.', 404);
            }

            if ((bool) ($lancamento->eh_transferencia ?? 0) === true) {
                throw new ValueError('Transferências não podem ser editadas aqui.');
            }

            $mergedData = $this->mergeLancamentoPayload($payload, $lancamento);
            $errors = LancamentoValidator::validateUpdate($mergedData);

            $contaId = $this->normalizeOptionalId($mergedData['conta_id'] ?? null);
            $categoriaId = $this->normalizeOptionalId($mergedData['categoria_id'] ?? null);
            $metaId = $this->normalizeOptionalId($mergedData['meta_id'] ?? ($mergedData['metaId'] ?? null));
            $metaId = LancamentoValidator::validateMetaOwnership($metaId, $userId, $errors);

            $meta = $metaId
                ? Meta::where('id', $metaId)->where('user_id', $userId)->first()
                : null;

            $metaOperacao = $metaId
                ? LancamentoValidator::resolveMetaOperationForContext(
                    LancamentoValidator::normalizeMetaOperation($mergedData['meta_operacao'] ?? ($mergedData['metaOperacao'] ?? null)),
                    [
                        'tipo' => $mergedData['tipo'] ?? $lancamento->tipo,
                        'eh_transferencia' => false,
                    ],
                    $meta
                )
                : null;

            $metaValor = $metaId
                ? (LancamentoValidator::sanitizeMetaValor($mergedData['meta_valor'] ?? ($mergedData['metaValor'] ?? null))
                    ?? LancamentoValidator::sanitizeValor($mergedData['valor'] ?? $lancamento->valor))
                : null;

            LancamentoValidator::validateMetaLinkRules($metaId, [
                'tipo' => $mergedData['tipo'] ?? $lancamento->tipo,
                'eh_transferencia' => false,
                'forma_pagamento' => $mergedData['forma_pagamento'] ?? $lancamento->forma_pagamento,
                'meta_operacao' => $metaOperacao,
                'meta_valor' => $metaValor,
                'valor' => $mergedData['valor'] ?? $lancamento->valor,
            ], $errors);

            $errors = array_merge($errors, $this->validateLancamentoRelations($userId, $contaId, $categoriaId));

            if ($errors !== []) {
                return ServiceResultDTO::validationFail($errors);
            }

            $dto = UpdateLancamentoDTO::fromRequest(
                $this->buildLancamentoWriteData($mergedData, $contaId, $categoriaId, $metaId, $metaOperacao, $metaValor)
            );

            $metaAnteriorId = (int) ($lancamento->meta_id ?? 0);
            $this->lancamentoRepo->update($lancamento->id, $dto->toArray());
            $this->recalculateAffectedMetas($userId, $metaAnteriorId, $metaId ?? 0);

            return new ServiceResultDTO(
                success: true,
                message: 'Sucesso',
                data: ['id' => (int) $lancamento->id],
                httpCode: 200
            );
        } catch (ValueError $e) {
            $message = trim($e->getMessage());

            return ServiceResultDTO::fail(
                $message !== '' ? $message : 'Dados inválidos para atualizar lançamento.',
                422
            );
        }
    }

    private function normalizeOptionalId(mixed $value): ?int
    {
        if (!is_scalar($value)) {
            return null;
        }

        $id = (int) $value;
        return $id > 0 ? $id : null;
    }

    /**
     * @return array<string, string>
     */
    private function validateLancamentoRelations(int $userId, ?int $contaId, ?int $categoriaId): array
    {
        $errors = [];

        if ($contaId !== null && !$this->contaRepo->belongsToUser($contaId, $userId)) {
            $errors['conta_id'] = 'Conta inválida.';
        }

        if ($categoriaId !== null && !$this->categoriaRepo->belongsToUser($categoriaId, $userId)) {
            $errors['categoria_id'] = 'Categoria inválida.';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildLancamentoWriteData(
        array $data,
        ?int $contaId,
        ?int $categoriaId,
        ?int $metaId = null,
        ?string $metaOperacao = null,
        ?float $metaValor = null
    ): array {
        return [
            'tipo' => strtolower(trim((string) ($data['tipo'] ?? ''))),
            'data' => (string) ($data['data'] ?? ''),
            'valor' => LancamentoValidator::sanitizeValor($data['valor'] ?? 0),
            'descricao' => mb_substr(trim((string) ($data['descricao'] ?? '')), 0, 190),
            'observacao' => mb_substr(trim((string) ($data['observacao'] ?? '')), 0, 500),
            'categoria_id' => $categoriaId,
            'meta_id' => $metaId,
            'meta_operacao' => $metaOperacao,
            'meta_valor' => $metaValor,
            'conta_id' => $contaId,
            'forma_pagamento' => $data['forma_pagamento'] ?? null,
        ];
    }

    private function mergeLancamentoPayload(array $payload, Lancamento $lancamento): array
    {
        return [
            'tipo' => $payload['tipo'] ?? $lancamento->tipo,
            'data' => $payload['data'] ?? $lancamento->data,
            'valor' => $payload['valor'] ?? $lancamento->valor,
            'descricao' => $payload['descricao'] ?? $lancamento->descricao,
            'observacao' => $payload['observacao'] ?? $lancamento->observacao,
            'meta_id' => array_key_exists('meta_id', $payload) ? $payload['meta_id'] : ($payload['metaId'] ?? $lancamento->meta_id),
            'meta_operacao' => array_key_exists('meta_operacao', $payload)
                ? $payload['meta_operacao']
                : ($payload['metaOperacao'] ?? $lancamento->meta_operacao),
            'meta_valor' => array_key_exists('meta_valor', $payload)
                ? $payload['meta_valor']
                : ($payload['metaValor'] ?? $lancamento->meta_valor),
            'forma_pagamento' => array_key_exists('forma_pagamento', $payload)
                ? $payload['forma_pagamento']
                : $lancamento->forma_pagamento,
            'conta_id' => $payload['conta_id'] ?? $lancamento->conta_id,
            'categoria_id' => $payload['categoria_id'] ?? $lancamento->categoria_id,
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
