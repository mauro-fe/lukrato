<?php

declare(strict_types=1);

namespace Application\DTO\Requests;

readonly class CreateLancamentoDTO
{
    public function __construct(
        public int $userId,
        public string $tipo,
        public string $data,
        public float $valor,
        public string $descricao,
        public ?string $observacao = null,
        public ?int $categoriaId = null,
        public ?int $contaId = null,
        public bool $ehTransferencia = false,
        public bool $ehSaldoInicial = false,
        public ?int $contaIdDestino = null,
        public bool $pago = true,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tipo' => $this->tipo,
            'data' => $this->data,
            'valor' => $this->valor,
            'descricao' => $this->descricao,
            'observacao' => $this->observacao,
            'categoria_id' => $this->categoriaId,
            'conta_id' => $this->contaId,
            'eh_transferencia' => $this->ehTransferencia ? 1 : 0,
            'eh_saldo_inicial' => $this->ehSaldoInicial ? 1 : 0,
            'conta_id_destino' => $this->contaIdDestino,
            'pago' => $this->pago ? 1 : 0,
        ];
    }

    public static function fromRequest(int $userId, array $data): self
    {
        return new self(
            userId: $userId,
            tipo: $data['tipo'] ?? '',
            data: $data['data'] ?? '',
            valor: (float)($data['valor'] ?? 0),
            descricao: $data['descricao'] ?? '',
            observacao: $data['observacao'] ?? null,
            categoriaId: isset($data['categoria_id']) ? (int)$data['categoria_id'] : null,
            contaId: isset($data['conta_id']) ? (int)$data['conta_id'] : null,
            ehTransferencia: (bool)($data['eh_transferencia'] ?? false),
            ehSaldoInicial: (bool)($data['eh_saldo_inicial'] ?? false),
            contaIdDestino: isset($data['conta_id_destino']) ? (int)$data['conta_id_destino'] : null,
            pago: !isset($data['pago']) || (bool)$data['pago'],
        );
    }
}
