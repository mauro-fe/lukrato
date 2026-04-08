<?php

declare(strict_types=1);

namespace Application\UseCases\Lancamentos;

use Application\Container\ApplicationContainer;
use Application\DTO\Requests\CreateLancamentoDTO;
use Application\DTO\ServiceResultDTO;
use Application\Models\Meta;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Metas\MetaProgressService;
use Application\Services\Lancamento\LancamentoLimitService;
use Application\Validators\LancamentoValidator;
use DomainException;
use ValueError;

class CreateLancamentoUseCase
{
    private readonly LancamentoLimitService $limitService;
    private readonly LancamentoRepository $lancamentoRepo;
    private readonly CategoriaRepository $categoriaRepo;
    private readonly ContaRepository $contaRepo;
    private readonly MetaProgressService $metaProgressService;

    public function __construct(
        ?LancamentoLimitService $limitService = null,
        ?LancamentoRepository $lancamentoRepo = null,
        ?CategoriaRepository $categoriaRepo = null,
        ?ContaRepository $contaRepo = null,
        ?MetaProgressService $metaProgressService = null
    ) {
        $this->limitService = ApplicationContainer::resolveOrNew($limitService, LancamentoLimitService::class);
        $this->lancamentoRepo = ApplicationContainer::resolveOrNew($lancamentoRepo, LancamentoRepository::class);
        $this->categoriaRepo = ApplicationContainer::resolveOrNew($categoriaRepo, CategoriaRepository::class);
        $this->contaRepo = ApplicationContainer::resolveOrNew($contaRepo, ContaRepository::class);
        $this->metaProgressService = ApplicationContainer::resolveOrNew($metaProgressService, MetaProgressService::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, array $payload): ServiceResultDTO
    {
        try {
            $errors = LancamentoValidator::validateCreate($payload);

            $contaId = $this->normalizeOptionalId($payload['conta_id'] ?? null);
            $categoriaId = $this->normalizeOptionalId($payload['categoria_id'] ?? null);
            $metaId = $this->normalizeOptionalId($payload['meta_id'] ?? ($payload['metaId'] ?? null));
            $metaId = LancamentoValidator::validateMetaOwnership($metaId, $userId, $errors);
            $meta = $metaId
                ? Meta::where('id', $metaId)->where('user_id', $userId)->first()
                : null;
            $metaOperacao = $metaId
                ? LancamentoValidator::resolveMetaOperationForContext(
                    LancamentoValidator::normalizeMetaOperation($payload['meta_operacao'] ?? ($payload['metaOperacao'] ?? null)),
                    [
                        'tipo' => $payload['tipo'] ?? null,
                        'eh_transferencia' => false,
                    ],
                    $meta
                )
                : null;
            $metaValor = $metaId
                ? (LancamentoValidator::sanitizeMetaValor($payload['meta_valor'] ?? ($payload['metaValor'] ?? null))
                    ?? LancamentoValidator::sanitizeValor($payload['valor'] ?? 0))
                : null;
            LancamentoValidator::validateMetaLinkRules($metaId, [
                'tipo' => $payload['tipo'] ?? null,
                'eh_transferencia' => false,
                'forma_pagamento' => $payload['forma_pagamento'] ?? null,
                'meta_operacao' => $metaOperacao,
                'meta_valor' => $metaValor,
                'valor' => $payload['valor'] ?? 0,
            ], $errors);
            $errors = array_merge($errors, $this->validateLancamentoRelations($userId, $contaId, $categoriaId));

            if ($errors !== []) {
                return ServiceResultDTO::validationFail($errors);
            }

            try {
                $this->limitService->assertCanCreate($userId, (string) ($payload['data'] ?? ''));
            } catch (DomainException $e) {
                $message = trim($e->getMessage());

                return ServiceResultDTO::fail(
                    $message !== '' ? $message : 'Nao foi possivel criar o lancamento.',
                    402
                );
            }

            $dto = CreateLancamentoDTO::fromRequest(
                $userId,
                $this->buildLancamentoWriteData($payload, $contaId, $categoriaId, $metaId, $metaOperacao, $metaValor)
            );

            $lancamento = $this->lancamentoRepo->create($dto->toArray());
            $this->recalculateAffectedMetas($userId, $metaId ?? 0);
            $usage = $this->limitService->usage($userId, substr((string) ($payload['data'] ?? ''), 0, 7));

            return ServiceResultDTO::ok('Lançamento criado', [
                'ok' => true,
                'id' => (int) $lancamento->id,
                'usage' => $usage,
                'ui_message' => $this->limitService->getWarningMessage($usage),
                'upgrade_cta' => ($usage['should_warn'] ?? false) ? $this->limitService->getUpgradeCta() : null,
            ]);
        } catch (ValueError $e) {
            $message = trim($e->getMessage());

            return ServiceResultDTO::fail(
                $message !== '' ? $message : 'Dados invalidos para criar lancamento.',
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
            $errors['conta_id'] = 'Conta invalida.';
        }

        if ($categoriaId !== null && !$this->categoriaRepo->belongsToUser($categoriaId, $userId)) {
            $errors['categoria_id'] = 'Categoria invalida.';
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

    private function recalculateAffectedMetas(int $userId, int ...$metaIds): void
    {
        $metaIds = array_values(array_unique(array_filter($metaIds, static fn(int $metaId): bool => $metaId > 0)));

        foreach ($metaIds as $metaId) {
            $this->metaProgressService->recalculateMeta($userId, $metaId);
        }
    }
}
