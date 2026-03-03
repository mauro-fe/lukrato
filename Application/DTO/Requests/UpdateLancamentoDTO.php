<?php

declare(strict_types=1);

namespace Application\DTO\Requests;

readonly class UpdateLancamentoDTO
{
    public function __construct(
        public string $tipo,
        public string $data,
        public float $valor,
        public string $descricao,
        public ?string $horaLancamento = null,
        public ?string $observacao = null,
        public ?int $categoriaId = null,
        public ?int $subcategoriaId = null,
        public ?int $contaId = null,
        public ?int $contaDestinoId = null,
        public ?string $formaPagamento = null,
    ) {}

    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'data' => $this->data,
            'hora_lancamento' => $this->horaLancamento,
            'valor' => $this->valor,
            'descricao' => $this->descricao,
            'observacao' => $this->observacao,
            'categoria_id' => $this->categoriaId,
            'subcategoria_id' => $this->subcategoriaId,
            'conta_id' => $this->contaId,
            'conta_id_destino' => $this->contaDestinoId,
            'forma_pagamento' => $this->formaPagamento,
        ];
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            tipo: $data['tipo'] ?? '',
            data: $data['data'] ?? '',
            horaLancamento: !empty($data['hora_lancamento']) ? $data['hora_lancamento'] : null,
            valor: (float)($data['valor'] ?? 0),
            descricao: $data['descricao'] ?? '',
            observacao: $data['observacao'] ?? null,
            categoriaId: isset($data['categoria_id']) ? (int)$data['categoria_id'] : null,
            subcategoriaId: isset($data['subcategoria_id']) ? (int)$data['subcategoria_id'] : null,
            contaId: isset($data['conta_id']) ? (int)$data['conta_id'] : null,
            contaDestinoId: isset($data['conta_id_destino']) ? (int)$data['conta_id_destino'] : null,
            formaPagamento: $data['forma_pagamento'] ?? null,
        );
    }
}
