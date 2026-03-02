<?php

declare(strict_types=1);

namespace Application\DTO\Requests;

/**
 * DTO para atualização de subcategoria.
 */
readonly class UpdateSubcategoriaDTO
{
    public function __construct(
        public string $nome,
        public ?string $icone = null,
    ) {}

    public function toArray(): array
    {
        return [
            'nome'  => $this->nome,
            'icone' => $this->icone,
        ];
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            nome: trim($data['nome'] ?? ''),
            icone: !empty($data['icone']) ? trim($data['icone']) : null,
        );
    }
}
