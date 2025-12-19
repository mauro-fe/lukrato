<?php

declare(strict_types=1);

namespace Application\DTOs\Requests;

/**
 * DTO para atualização de lançamento.
 */
readonly class UpdateLancamentoDTO
{
    public function __construct(
        public string $tipo,
        public string $data,
        public float $valor,
        public string $descricao,
        public ?string $observacao = null,
        public ?int $categoriaId = null,
        public ?int $contaId = null,
    ) {}

    /**
     * Converte para array para uso com repository.
     */
    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'data' => $this->data,
            'valor' => $this->valor,
            'descricao' => $this->descricao,
            'observacao' => $this->observacao,
            'categoria_id' => $this->categoriaId,
            'conta_id' => $this->contaId,
        ];
    }

    /**
     * Cria DTO a partir de array de request.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            tipo: $data['tipo'] ?? '',
            data: $data['data'] ?? '',
            valor: (float)($data['valor'] ?? 0),
            descricao: $data['descricao'] ?? '',
            observacao: $data['observacao'] ?? null,
            categoriaId: isset($data['categoria_id']) ? (int)$data['categoria_id'] : null,
            contaId: isset($data['conta_id']) ? (int)$data['conta_id'] : null,
        );
    }
}
