<?php

declare(strict_types=1);

namespace Application\DTOs\Requests;

/**
 * DTO para criação de conta.
 */
readonly class CreateContaDTO
{
    public function __construct(
        public int $userId,
        public string $nome,
        public string $moeda,
        public ?string $instituicao = null,
        public float $saldoInicial = 0.00,
    ) {}

    /**
     * Converte para array para uso com repository.
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'nome' => $this->nome,
            'moeda' => $this->moeda,
            'instituicao' => $this->instituicao,
            'saldo_inicial' => $this->saldoInicial,
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
            moeda: $data['moeda'] ?? 'BRL',
            instituicao: $data['instituicao'] ?? null,
            saldoInicial: (float)($data['saldo_inicial'] ?? 0),
        );
    }
}
