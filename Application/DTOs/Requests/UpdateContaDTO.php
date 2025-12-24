<?php

declare(strict_types=1);

namespace Application\DTOs\Requests;

/**
 * DTO para atualização de conta.
 */
readonly class UpdateContaDTO
{
    public function __construct(
        public string $nome,
        public string $moeda,
        public ?string $instituicao = null,
        public ?float $saldoInicial = null,
    ) {}

    /**
     * Converte para array para uso com repository.
     */
    public function toArray(): array
    {
        $data = [
            'nome' => $this->nome,
            'moeda' => $this->moeda,
            'instituicao' => $this->instituicao,
        ];

        if ($this->saldoInicial !== null) {
            $data['saldo_inicial'] = $this->saldoInicial;
        }

        return $data;
    }

    /**
     * Cria DTO a partir de array de request.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            nome: $data['nome'] ?? '',
            moeda: $data['moeda'] ?? 'BRL',
            instituicao: $data['instituicao'] ?? null,
            saldoInicial: isset($data['saldo_inicial']) ? (float)$data['saldo_inicial'] : null,
        );
    }
}
