<?php

declare(strict_types=1);

namespace Application\DTOs\Requests;

/**
 * DTO para criação de categoria.
 */
readonly class CreateCategoriaDTO
{
    public function __construct(
        public int $userId,
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
            'user_id' => $this->userId,
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'icone' => $this->icone,
        ];
    }

    /**
     * Cria DTO a partir de array de request.
     */
    public static function fromRequest(int $userId, array $data): self
    {
        return new self(
            userId: $userId,
            nome: $data['nome'] ?? '',
            tipo: $data['tipo'] ?? '',
            icone: $data['icone'] ?? null,
        );
    }
}
