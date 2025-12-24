<?php

declare(strict_types=1);

namespace Application\DTOs\Requests;

/**
 * DTO para atualização de categoria.
 */
readonly class UpdateCategoriaDTO
{
    public function __construct(
        public string $nome,
        public string $tipo,
        public ?string $icone = null,
    ) {}

    /**
     * Converte para array para uso com repository.
     */
    public function toArray(): array
    {
        return [
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'icone' => $this->icone,
        ];
    }

    /**
     * Cria DTO a partir de array de request.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            nome: $data['nome'] ?? '',
            tipo: $data['tipo'] ?? '',
            icone: $data['icone'] ?? null,
        );
    }
}
