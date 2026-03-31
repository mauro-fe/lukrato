<?php

declare(strict_types=1);

namespace Application\UseCases\Lancamentos;

use Application\DTO\ServiceResultDTO;
use Application\Models\Meta;
use Application\Services\Conta\TransferenciaService;
use Application\Validators\LancamentoValidator;
use ValueError;

class CreateTransferenciaUseCase
{
    public function __construct(
        private readonly TransferenciaService $transferenciaService = new TransferenciaService()
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, array $payload): ServiceResultDTO
    {
        try {
            $data = trim((string) ($payload['data'] ?? date('Y-m-d')));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                throw new ValueError('Data invalida (YYYY-MM-DD).');
            }

            $valor = LancamentoValidator::sanitizeValor($payload['valor'] ?? 0);
            if ($valor <= 0) {
                throw new ValueError('Valor deve ser maior que zero.');
            }

            $origemId = (int) ($payload['conta_id'] ?? 0);
            $destinoId = (int) ($payload['conta_id_destino'] ?? ($payload['conta_destino_id'] ?? 0));
            $metaId = $this->normalizeOptionalId($payload['meta_id'] ?? ($payload['metaId'] ?? null));

            $errors = [];
            $metaId = LancamentoValidator::validateMetaOwnership($metaId, $userId, $errors);
            $meta = $metaId
                ? Meta::where('id', $metaId)->where('user_id', $userId)->first()
                : null;
            $metaOperacao = $metaId
                ? LancamentoValidator::resolveMetaOperationForContext(
                    LancamentoValidator::normalizeMetaOperation($payload['meta_operacao'] ?? ($payload['metaOperacao'] ?? null)),
                    [
                        'tipo' => 'transferencia',
                        'eh_transferencia' => true,
                    ],
                    $meta
                )
                : null;
            $metaValor = $metaId
                ? (LancamentoValidator::sanitizeMetaValor($payload['meta_valor'] ?? ($payload['metaValor'] ?? null)) ?? $valor)
                : null;

            LancamentoValidator::validateMetaLinkRules($metaId, [
                'tipo' => 'transferencia',
                'eh_transferencia' => true,
                'meta_operacao' => $metaOperacao,
                'meta_valor' => $metaValor,
                'valor' => $valor,
            ], $errors);

            if ($errors !== []) {
                return ServiceResultDTO::validationFail($errors);
            }

            $transferencia = $this->transferenciaService->executarTransferencia(
                userId: $userId,
                contaOrigemId: $origemId,
                contaDestinoId: $destinoId,
                valor: $valor,
                data: $data,
                descricao: $payload['descricao'] ?? null,
                observacao: $payload['observacao'] ?? null,
                metaId: $metaId,
                metaValor: $metaValor
            );

            return ServiceResultDTO::ok('Success', ['id' => (int) $transferencia->id]);
        } catch (ValueError $e) {
            $message = trim($e->getMessage());

            return ServiceResultDTO::fail(
                $message !== '' ? $message : 'Dados invalidos para realizar transferencia.',
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
}
