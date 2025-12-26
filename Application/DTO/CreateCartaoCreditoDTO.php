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
        // Debug temporário
        file_put_contents(
            __DIR__ . '/../../storage/logs/debug-cartao.log',
            date('Y-m-d H:i:s') . " - Data recebida: " . json_encode($data) . "\n",
            FILE_APPEND
        );

        $limiteTotal = self::parseLimiteTotal($data['limite_total'] ?? 0);

        file_put_contents(
            __DIR__ . '/../../storage/logs/debug-cartao.log',
            date('Y-m-d H:i:s') . " - Limite total convertido: " . $limiteTotal . "\n",
            FILE_APPEND
        );

        return new self(
            userId: $userId,
            contaId: (int) ($data['conta_id'] ?? 0),
            nomeCartao: trim((string) ($data['nome_cartao'] ?? '')),
            bandeira: strtolower(trim((string) ($data['bandeira'] ?? ''))),
            ultimosDigitos: trim((string) ($data['ultimos_digitos'] ?? '')),
            limiteTotal: $limiteTotal,
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
