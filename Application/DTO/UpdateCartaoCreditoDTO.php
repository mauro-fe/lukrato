<?php

namespace Application\DTO;

class UpdateCartaoCreditoDTO
{
    public function __construct(
        public readonly ?string $nomeCartao = null,
        public readonly ?string $bandeira = null,
        public readonly ?string $ultimosDigitos = null,
        public readonly ?float $limiteTotal = null,
        public readonly ?int $diaVencimento = null,
        public readonly ?int $diaFechamento = null,
        public readonly ?string $corCartao = null,
        public readonly ?bool $ativo = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nomeCartao: isset($data['nome_cartao']) ? trim((string) $data['nome_cartao']) : null,
            bandeira: isset($data['bandeira']) ? strtolower(trim((string) $data['bandeira'])) : null,
            ultimosDigitos: isset($data['ultimos_digitos']) ? trim((string) $data['ultimos_digitos']) : null,
            limiteTotal: isset($data['limite_total']) ? self::parseLimiteTotal($data['limite_total']) : null,
            diaVencimento: isset($data['dia_vencimento']) && $data['dia_vencimento'] !== ''
                ? (int) $data['dia_vencimento']
                : null,
            diaFechamento: isset($data['dia_fechamento']) && $data['dia_fechamento'] !== ''
                ? (int) $data['dia_fechamento']
                : null,
            corCartao: isset($data['cor_cartao']) ? trim((string) $data['cor_cartao']) : null,
            ativo: isset($data['ativo']) ? (bool) $data['ativo'] : null,
        );
    }

    /**
     * Converte limite total de diferentes formatos para float
     */
    private static function parseLimiteTotal(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            // Remove R$, espaços e outros caracteres não numéricos exceto . e ,
            $cleaned = preg_replace('/[^0-9.,]/', '', $value);

            // Se tem vírgula e ponto, assume formato brasileiro (1.234,56)
            if (str_contains($cleaned, '.') && str_contains($cleaned, ',')) {
                $cleaned = str_replace('.', '', $cleaned); // Remove separador de milhar
                $cleaned = str_replace(',', '.', $cleaned); // Vírgula vira ponto decimal
            }
            // Se tem apenas vírgula, assume que é decimal brasileiro (1234,56)
            elseif (str_contains($cleaned, ',')) {
                $cleaned = str_replace(',', '.', $cleaned);
            }
            // Se tem apenas ponto, pode ser formato americano ou brasileiro
            // Assume que é decimal se houver apenas um ponto

            return (float) $cleaned;
        }

        return 0.0;
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->nomeCartao !== null) $data['nome_cartao'] = $this->nomeCartao;
        if ($this->bandeira !== null) $data['bandeira'] = $this->bandeira;
        if ($this->ultimosDigitos !== null) $data['ultimos_digitos'] = $this->ultimosDigitos;
        if ($this->limiteTotal !== null) $data['limite_total'] = $this->limiteTotal;
        if ($this->diaVencimento !== null) $data['dia_vencimento'] = $this->diaVencimento;
        if ($this->diaFechamento !== null) $data['dia_fechamento'] = $this->diaFechamento;
        if ($this->corCartao !== null) $data['cor_cartao'] = $this->corCartao;
        if ($this->ativo !== null) $data['ativo'] = $this->ativo;

        return $data;
    }
}
