<?php

namespace Application\DTO;

class UpdateContaDTO
{
    public function __construct(
        public readonly ?string $nome = null,
        public readonly ?int $instituicaoFinanceiraId = null,
        public readonly ?string $instituicao = null,
        public readonly ?string $tipoConta = null,
        public readonly ?string $moeda = null,
        public readonly ?int $tipoId = null,
        public readonly ?float $saldoInicial = null,
        public readonly ?bool $ativo = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nome: isset($data['nome']) ? trim((string) $data['nome']) : null,
            instituicaoFinanceiraId: isset($data['instituicao_financeira_id']) && $data['instituicao_financeira_id'] !== '' 
                ? (int) $data['instituicao_financeira_id'] 
                : null,
            instituicao: isset($data['instituicao']) ? trim((string) $data['instituicao']) : null,
            tipoConta: isset($data['tipo_conta']) ? trim((string) $data['tipo_conta']) : null,
            moeda: isset($data['moeda']) ? strtoupper(trim((string) $data['moeda'])) : null,
            tipoId: isset($data['tipo_id']) && $data['tipo_id'] !== '' ? (int) $data['tipo_id'] : null,
            saldoInicial: isset($data['saldo_inicial']) ? (float) $data['saldo_inicial'] : null,
            ativo: isset($data['ativo']) ? (bool) $data['ativo'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [];
        
        if ($this->nome !== null) $data['nome'] = $this->nome;
        if ($this->instituicaoFinanceiraId !== null) $data['instituicao_financeira_id'] = $this->instituicaoFinanceiraId;
        if ($this->instituicao !== null) $data['instituicao'] = $this->instituicao;
        if ($this->tipoConta !== null) $data['tipo_conta'] = $this->tipoConta;
        if ($this->moeda !== null) $data['moeda'] = $this->moeda;
        if ($this->tipoId !== null) $data['tipo_id'] = $this->tipoId;
        if ($this->ativo !== null) $data['ativo'] = $this->ativo;
        
        return $data;
    }
}
