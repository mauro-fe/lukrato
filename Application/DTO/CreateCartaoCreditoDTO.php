<?php

namespace Application\DTO;

class CreateCartaoCreditoDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $contaId,
        public readonly string $nomeCartao,
        public readonly string $bandeira,
        public readonly string $ultimosDigitos,
        public readonly float $limiteTotal = 0.0,
        public readonly ?int $diaVencimento = null,
        public readonly ?int $diaFechamento = null,
        public readonly ?string $corCartao = null,
        public readonly bool $ativo = true,
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            contaId: (int) ($data['conta_id'] ?? 0),
            nomeCartao: trim((string) ($data['nome_cartao'] ?? '')),
            bandeira: strtolower(trim((string) ($data['bandeira'] ?? ''))),
            ultimosDigitos: trim((string) ($data['ultimos_digitos'] ?? '')),
            limiteTotal: (float) ($data['limite_total'] ?? 0.0),
            diaVencimento: isset($data['dia_vencimento']) && $data['dia_vencimento'] !== '' 
                ? (int) $data['dia_vencimento'] 
                : null,
            diaFechamento: isset($data['dia_fechamento']) && $data['dia_fechamento'] !== '' 
                ? (int) $data['dia_fechamento'] 
                : null,
            corCartao: isset($data['cor_cartao']) ? trim((string) $data['cor_cartao']) : null,
            ativo: (bool) ($data['ativo'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'conta_id' => $this->contaId,
            'nome_cartao' => $this->nomeCartao,
            'bandeira' => $this->bandeira,
            'ultimos_digitos' => $this->ultimosDigitos,
            'limite_total' => $this->limiteTotal,
            'limite_disponivel' => $this->limiteTotal, // Inicial = total
            'dia_vencimento' => $this->diaVencimento,
            'dia_fechamento' => $this->diaFechamento,
            'cor_cartao' => $this->corCartao,
            'ativo' => $this->ativo,
        ];
    }
}
