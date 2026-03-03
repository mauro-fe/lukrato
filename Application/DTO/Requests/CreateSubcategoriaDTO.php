<?php

declare(strict_types=1);

namespace Application\DTO\Requests;

/**
 * DTO para criação de subcategoria.
 * O tipo é herdado da categoria pai automaticamente.
 */
readonly class CreateSubcategoriaDTO
{
    public function __construct(
        public int $userId,
        public int $parentId,
        public string $nome,
        public ?string $icone = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id'   => $this->userId,
            'parent_id' => $this->parentId,
            'nome'      => $this->nome,
            'icone'     => $this->icone,
        ];
    }

    public static function fromRequest(int $userId, int $parentId, array $data): self
    {
        return new self(
            userId: $userId,
            parentId: $parentId,
            nome: trim($data['nome'] ?? ''),
            icone: !empty($data['icone']) ? trim($data['icone']) : null,
        );
    }
}
