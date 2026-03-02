<?php

declare(strict_types=1);

namespace Application\DTO\Requests;

readonly class CreateLancamentoDTO
{
    public function __construct(
        public int $userId,
        public string $tipo,
        public string $data,
        public ?string $horaLancamento = null,
        public float $valor,
        public string $descricao,
        public ?string $observacao = null,
        public ?int $categoriaId = null,
        public ?int $subcategoriaId = null,
        public ?int $contaId = null,
        public bool $ehTransferencia = false,
        public bool $ehSaldoInicial = false,
        public ?int $contaIdDestino = null,
        public bool $pago = true,
        public ?string $formaPagamento = null,
        // Recorrência
        public bool $recorrente = false,
        public ?string $recorrenciaFreq = null,
        public ?string $recorrenciaFim = null,
        public ?int $recorrenciaTotal = null,
        // Lembretes
        public ?int $lembrarAntesSegundos = null,
        public bool $canalEmail = false,
        public bool $canalInapp = false,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tipo' => $this->tipo,
            'data' => $this->data,
            'hora_lancamento' => $this->horaLancamento,
            'valor' => $this->valor,
            'descricao' => $this->descricao,
            'observacao' => $this->observacao,
            'categoria_id' => $this->categoriaId,
            'subcategoria_id' => $this->subcategoriaId,
            'conta_id' => $this->contaId,
            'eh_transferencia' => $this->ehTransferencia ? 1 : 0,
            'eh_saldo_inicial' => $this->ehSaldoInicial ? 1 : 0,
            'conta_id_destino' => $this->contaIdDestino,
            'pago' => $this->pago ? 1 : 0,
            'data_pagamento' => $this->pago ? date('Y-m-d') : null,
            'forma_pagamento' => $this->formaPagamento,
            'recorrente' => $this->recorrente ? 1 : 0,
            'recorrencia_freq' => $this->recorrenciaFreq,
            'recorrencia_fim' => $this->recorrenciaFim,
            'recorrencia_total' => $this->recorrenciaTotal,
            'lembrar_antes_segundos' => $this->lembrarAntesSegundos,
            'canal_email' => $this->canalEmail ? 1 : 0,
            'canal_inapp' => $this->canalInapp ? 1 : 0,
        ];
    }

    public static function fromRequest(int $userId, array $data): self
    {
        return new self(
            userId: $userId,
            tipo: $data['tipo'] ?? '',
            data: $data['data'] ?? '',
            horaLancamento: !empty($data['hora_lancamento']) ? $data['hora_lancamento'] : null,
            valor: (float)($data['valor'] ?? 0),
            descricao: $data['descricao'] ?? '',
            observacao: $data['observacao'] ?? null,
            categoriaId: isset($data['categoria_id']) ? (int)$data['categoria_id'] : null,
            subcategoriaId: isset($data['subcategoria_id']) ? (int)$data['subcategoria_id'] : null,
            contaId: isset($data['conta_id']) ? (int)$data['conta_id'] : null,
            ehTransferencia: (bool)($data['eh_transferencia'] ?? false),
            ehSaldoInicial: (bool)($data['eh_saldo_inicial'] ?? false),
            contaIdDestino: isset($data['conta_id_destino']) ? (int)$data['conta_id_destino'] : null,
            pago: !isset($data['pago']) || (bool)$data['pago'],
            formaPagamento: $data['forma_pagamento'] ?? null,
            recorrente: (bool)($data['recorrente'] ?? false),
            recorrenciaFreq: $data['recorrencia_freq'] ?? null,
            recorrenciaFim: $data['recorrencia_fim'] ?? null,
            recorrenciaTotal: isset($data['recorrencia_total']) ? (int)$data['recorrencia_total'] : null,
            lembrarAntesSegundos: isset($data['lembrar_antes_segundos']) ? (int)$data['lembrar_antes_segundos'] : null,
            canalEmail: (bool)($data['canal_email'] ?? false),
            canalInapp: (bool)($data['canal_inapp'] ?? false),
        );
    }
}
