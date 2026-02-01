<?php

declare(strict_types=1);

namespace Application\DTO\Requests;

/**
 * DTO para criação de lançamentos.
 * 
 * IMPORTANTE - Campos de Competência vs Caixa:
 * - afetaCaixa: Define se o lançamento afeta saldo disponível (fluxo de caixa)
 *   - true (default): Lançamentos normais que movimentam conta
 *   - false: Lançamentos de cartão pendentes (só afetam quando fatura é paga)
 * 
 * - afetaCompetencia: Define se conta nas despesas do mês de competência
 *   - true (default): Sempre conta para análise financeira
 * 
 * - origemTipo: Tipo de origem do lançamento
 *   - 'normal': Lançamento comum
 *   - 'cartao_credito': Compra/pagamento de cartão
 *   - 'parcelamento': Parcela de compra parcelada
 *   - 'agendamento': Lançamento de agendamento
 *   - 'transferencia': Transferência entre contas
 */
readonly class CreateLancamentoDTO
{
    public function __construct(
        public int $userId,
        public string $tipo,
        public string $data,
        public float $valor,
        public string $descricao,
        public ?string $observacao = null,
        public ?int $categoriaId = null,
        public ?int $contaId = null,
        public bool $ehTransferencia = false,
        public bool $ehSaldoInicial = false,
        public ?int $contaIdDestino = null,
        public bool $pago = true,
        // Campos de competência - usam default do banco se não informados
        public ?bool $afetaCaixa = null,
        public ?bool $afetaCompetencia = null,
        public ?string $dataCompetencia = null,
        public ?string $origemTipo = null,
    ) {}

    public function toArray(): array
    {
        $data = [
            'user_id' => $this->userId,
            'tipo' => $this->tipo,
            'data' => $this->data,
            'valor' => $this->valor,
            'descricao' => $this->descricao,
            'observacao' => $this->observacao,
            'categoria_id' => $this->categoriaId,
            'conta_id' => $this->contaId,
            'eh_transferencia' => $this->ehTransferencia ? 1 : 0,
            'eh_saldo_inicial' => $this->ehSaldoInicial ? 1 : 0,
            'conta_id_destino' => $this->contaIdDestino,
            'pago' => $this->pago ? 1 : 0,
        ];

        // Adicionar campos de competência apenas se explicitamente definidos
        // Caso contrário, o banco usa os defaults (afeta_caixa=true, afeta_competencia=true)
        if ($this->afetaCaixa !== null) {
            $data['afeta_caixa'] = $this->afetaCaixa ? 1 : 0;
        }
        if ($this->afetaCompetencia !== null) {
            $data['afeta_competencia'] = $this->afetaCompetencia ? 1 : 0;
        }
        if ($this->dataCompetencia !== null) {
            $data['data_competencia'] = $this->dataCompetencia;
        }
        if ($this->origemTipo !== null) {
            $data['origem_tipo'] = $this->origemTipo;
        }

        return $data;
    }

    public static function fromRequest(int $userId, array $data): self
    {
        return new self(
            userId: $userId,
            tipo: $data['tipo'] ?? '',
            data: $data['data'] ?? '',
            valor: (float)($data['valor'] ?? 0),
            descricao: $data['descricao'] ?? '',
            observacao: $data['observacao'] ?? null,
            categoriaId: isset($data['categoria_id']) ? (int)$data['categoria_id'] : null,
            contaId: isset($data['conta_id']) ? (int)$data['conta_id'] : null,
            ehTransferencia: (bool)($data['eh_transferencia'] ?? false),
            ehSaldoInicial: (bool)($data['eh_saldo_inicial'] ?? false),
            contaIdDestino: isset($data['conta_id_destino']) ? (int)$data['conta_id_destino'] : null,
            pago: !isset($data['pago']) || (bool)$data['pago'],
            // Campos de competência - só passados se presentes no request
            afetaCaixa: isset($data['afeta_caixa']) ? (bool)$data['afeta_caixa'] : null,
            afetaCompetencia: isset($data['afeta_competencia']) ? (bool)$data['afeta_competencia'] : null,
            dataCompetencia: $data['data_competencia'] ?? null,
            origemTipo: $data['origem_tipo'] ?? null,
        );
    }
}
