<?php

declare(strict_types=1);

namespace Application\DTO;

use DateTimeImmutable;

/**
 * DTO para atualização de agendamento.
 */
readonly class UpdateAgendamentoDTO
{
    public function __construct(
        public ?string $titulo = null,
        public ?string $tipo = null,
        public ?int $valor_centavos = null,
        public ?string $data_pagamento = null,
        public ?string $proxima_execucao = null,
        public ?int $lembrar_antes_segundos = null,
        public ?bool $canal_email = null,
        public ?bool $canal_inapp = null,
        public ?string $descricao = null,
        public ?int $categoria_id = null,
        public ?int $conta_id = null,
        public ?string $moeda = null,
        public ?bool $recorrente = null,
        public ?string $recorrencia_freq = null,
        public ?int $recorrencia_intervalo = null,
        public ?string $recorrencia_fim = null,
    ) {}

    /**
     * Cria um DTO a partir de dados do request.
     */
    public static function fromRequest(array $data): self
    {
        // Converter valor para centavos se necessário
        $valorCentavos = null;
        if (isset($data['valor_centavos'])) {
            $valorCentavos = (int) $data['valor_centavos'];
        } elseif (isset($data['valor']) || isset($data['agValor'])) {
            $valorCentavos = self::moneyToCents($data['valor'] ?? $data['agValor'] ?? null);
        }

        // Normalizar data de pagamento se fornecida
        $dataPagamento = null;
        if (isset($data['data_pagamento'])) {
            $dataPagamento = str_replace('T', ' ', $data['data_pagamento']);
        }

        // Calcular próxima execução se data ou lembrete foram alterados
        $proximaExecucao = null;
        if ($dataPagamento !== null || isset($data['lembrar_antes_segundos'])) {
            // Precisaremos recalcular no controller com os dados do agendamento existente
            $proximaExecucao = $data['proxima_execucao'] ?? null;
        }

        // Tratar campos de recorrência - strings vazias devem ser null
        $recorrenciaFreq = isset($data['recorrencia_freq']) && $data['recorrencia_freq'] !== ''
            ? $data['recorrencia_freq']
            : null;
        $recorrenciaIntervalo = isset($data['recorrencia_intervalo']) && $data['recorrencia_intervalo'] !== ''
            ? (int) $data['recorrencia_intervalo']
            : null;
        $recorrenciaFim = isset($data['recorrencia_fim']) && $data['recorrencia_fim'] !== ''
            ? $data['recorrencia_fim']
            : null;

        return new self(
            titulo: isset($data['titulo']) ? $data['titulo'] : null,
            tipo: isset($data['tipo']) ? strtolower(trim($data['tipo'])) : null,
            valor_centavos: $valorCentavos,
            data_pagamento: $dataPagamento,
            proxima_execucao: $proximaExecucao,
            lembrar_antes_segundos: isset($data['lembrar_antes_segundos']) ? (int) $data['lembrar_antes_segundos'] : null,
            canal_email: isset($data['canal_email']) ? filter_var($data['canal_email'], FILTER_VALIDATE_BOOLEAN) : null,
            canal_inapp: isset($data['canal_inapp']) ? filter_var($data['canal_inapp'], FILTER_VALIDATE_BOOLEAN) : null,
            descricao: isset($data['descricao']) ? $data['descricao'] : null,
            categoria_id: isset($data['categoria_id']) && $data['categoria_id'] !== '' ? (int) $data['categoria_id'] : null,
            conta_id: isset($data['conta_id']) && $data['conta_id'] !== '' ? (int) $data['conta_id'] : null,
            moeda: isset($data['moeda']) ? strtoupper(trim($data['moeda'])) : null,
            recorrente: isset($data['recorrente']) ? filter_var($data['recorrente'], FILTER_VALIDATE_BOOLEAN) : null,
            recorrencia_freq: $recorrenciaFreq,
            recorrencia_intervalo: $recorrenciaIntervalo,
            recorrencia_fim: $recorrenciaFim,
        );
    }

    /**
     * Converte para array apenas com campos não nulos.
     * Quando recorrente é false, limpa os campos de recorrência.
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->titulo !== null) $data['titulo'] = $this->titulo;
        if ($this->tipo !== null) $data['tipo'] = $this->tipo;
        if ($this->valor_centavos !== null) $data['valor_centavos'] = $this->valor_centavos;
        if ($this->data_pagamento !== null) $data['data_pagamento'] = $this->data_pagamento;
        if ($this->proxima_execucao !== null) $data['proxima_execucao'] = $this->proxima_execucao;
        if ($this->lembrar_antes_segundos !== null) $data['lembrar_antes_segundos'] = $this->lembrar_antes_segundos;
        if ($this->canal_email !== null) $data['canal_email'] = $this->canal_email;
        if ($this->canal_inapp !== null) $data['canal_inapp'] = $this->canal_inapp;
        if ($this->descricao !== null) $data['descricao'] = $this->descricao;
        if ($this->categoria_id !== null) $data['categoria_id'] = $this->categoria_id;
        if ($this->conta_id !== null) $data['conta_id'] = $this->conta_id;
        if ($this->moeda !== null) $data['moeda'] = $this->moeda;

        // Tratamento especial para campos de recorrência
        if ($this->recorrente !== null) {
            $data['recorrente'] = $this->recorrente;

            // Se recorrente é false, limpar campos de recorrência
            if ($this->recorrente === false) {
                $data['recorrencia_freq'] = null;
                $data['recorrencia_intervalo'] = null;
                $data['recorrencia_fim'] = null;
            } else {
                // Se recorrente é true, incluir campos de recorrência se fornecidos
                if ($this->recorrencia_freq !== null) $data['recorrencia_freq'] = $this->recorrencia_freq;
                if ($this->recorrencia_intervalo !== null) $data['recorrencia_intervalo'] = $this->recorrencia_intervalo;
                if ($this->recorrencia_fim !== null) $data['recorrencia_fim'] = $this->recorrencia_fim;
            }
        } else {
            // Se recorrente não foi enviado, incluir campos de recorrência se fornecidos
            if ($this->recorrencia_freq !== null) $data['recorrencia_freq'] = $this->recorrencia_freq;
            if ($this->recorrencia_intervalo !== null) $data['recorrencia_intervalo'] = $this->recorrencia_intervalo;
            if ($this->recorrencia_fim !== null) $data['recorrencia_fim'] = $this->recorrencia_fim;
        }

        return $data;
    }

    /**
     * Recalcula próxima execução.
     */
    public function withProximaExecucao(string $dataPagamento, int $lembrarSegundos): self
    {
        $proximaExecucao = (new DateTimeImmutable($dataPagamento))
            ->modify("-{$lembrarSegundos} seconds")
            ->format('Y-m-d H:i:s');

        return new self(
            titulo: $this->titulo,
            tipo: $this->tipo,
            valor_centavos: $this->valor_centavos,
            data_pagamento: $this->data_pagamento,
            proxima_execucao: $proximaExecucao,
            lembrar_antes_segundos: $this->lembrar_antes_segundos,
            canal_email: $this->canal_email,
            canal_inapp: $this->canal_inapp,
            descricao: $this->descricao,
            categoria_id: $this->categoria_id,
            conta_id: $this->conta_id,
            moeda: $this->moeda,
            recorrente: $this->recorrente,
            recorrencia_freq: $this->recorrencia_freq,
            recorrencia_intervalo: $this->recorrencia_intervalo,
            recorrencia_fim: $this->recorrencia_fim,
        );
    }

    /**
     * Converte string monetária para centavos.
     */
    private static function moneyToCents(?string $str): ?int
    {
        if (empty($str)) {
            return null;
        }

        $s = preg_replace('/[^\d,.-]/', '', $str);

        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }

        $valor = (float) $s;
        return (int) round($valor * 100);
    }
}
