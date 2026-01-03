<?php

declare(strict_types=1);

namespace Application\DTO\Requests;

readonly class UpdateCategoriaDTO
{
    public function __construct(
        public string $nome,
        public string $tipo,
        public ?string $icone = null,
    ) {}

    public function toArray(): array
    {
        return [
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'icone' => $this->icone,
        ];
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            nome: $data['nome'] ?? '',
            tipo: $data['tipo'] ?? '',
            icone: $data['icone'] ?? null,
        );
    }
}
