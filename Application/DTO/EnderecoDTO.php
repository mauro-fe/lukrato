<?php

namespace Application\DTO;

/**
 * Data Transfer Object para dados de endereço.
 */
class EnderecoDTO
{
    public function __construct(
        public readonly string $cep,
        public readonly string $rua,
        public readonly string $numero,
        public readonly string $complemento,
        public readonly string $bairro,
        public readonly string $cidade,
        public readonly string $estado
    ) {}

    /**
     * Cria uma instância do DTO a partir de um array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cep: trim($data['cep'] ?? ''),
            rua: trim($data['rua'] ?? ''),
            numero: trim($data['numero'] ?? ''),
            complemento: trim($data['complemento'] ?? ''),
            bairro: trim($data['bairro'] ?? ''),
            cidade: trim($data['cidade'] ?? ''),
            estado: strtoupper(trim($data['estado'] ?? ''))
        );
    }

    /**
     * Verifica se o endereço está vazio (todos os campos principais vazios).
     */
    public function isEmpty(): bool
    {
        $camposObrigatorios = [
            $this->cep,
            $this->rua,
            $this->numero,
            $this->bairro,
            $this->cidade,
            $this->estado
        ];

        return empty(implode('', $camposObrigatorios));
    }

    /**
     * Converte o DTO para array.
     */
    public function toArray(): array
    {
        return [
            'cep' => $this->cep,
            'rua' => $this->rua,
            'numero' => $this->numero,
            'complemento' => $this->complemento,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
        ];
    }
}