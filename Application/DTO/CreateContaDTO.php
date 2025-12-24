<?php

namespace Application\DTO;

class CreateContaDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $nome,
        public readonly ?int $instituicaoFinanceiraId = null,
        public readonly ?string $instituicao = null,
        public readonly string $tipoConta = 'conta_corrente',
        public readonly string $moeda = 'BRL',
        public readonly ?int $tipoId = null,
        public readonly float $saldoInicial = 0.0,
        public readonly bool $ativo = true,
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            nome: trim((string) ($data['nome'] ?? '')),
            instituicaoFinanceiraId: isset($data['instituicao_financeira_id']) && $data['instituicao_financeira_id'] !== '' 
                ? (int) $data['instituicao_financeira_id'] 
                : null,
            instituicao: isset($data['instituicao']) ? trim((string) $data['instituicao']) : null,
            tipoConta: trim((string) ($data['tipo_conta'] ?? 'conta_corrente')),
            moeda: strtoupper(trim((string) ($data['moeda'] ?? 'BRL'))),
            tipoId: isset($data['tipo_id']) && $data['tipo_id'] !== '' ? (int) $data['tipo_id'] : null,
            saldoInicial: (float) ($data['saldo_inicial'] ?? 0.0),
            ativo: (bool) ($data['ativo'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'nome' => $this->nome,
            'instituicao_financeira_id' => $this->instituicaoFinanceiraId,
            'instituicao' => $this->instituicao,
            'tipo_conta' => $this->tipoConta,
            'moeda' => $this->moeda,
            'tipo_id' => $this->tipoId,
            'ativo' => $this->ativo,
        ];
    }
}
