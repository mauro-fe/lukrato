<?php

declare(strict_types=1);

namespace Application\DTO;

use Application\Enums\AgendamentoStatus;
use DateTimeImmutable;

/**
 * DTO para criação de agendamento.
 */
readonly class CreateAgendamentoDTO
{
    public function __construct(
        public int $user_id,
        public string $titulo,
        public string $tipo,
        public int $valor_centavos,
        public string $data_pagamento,
        public string $proxima_execucao,
        public int $lembrar_antes_segundos = 0,
        public bool $canal_email = false,
        public bool $canal_inapp = true,
        public ?string $descricao = null,
        public ?int $categoria_id = null,
        public ?int $conta_id = null,
        public string $moeda = 'BRL',
        public bool $recorrente = false,
        public ?string $recorrencia_freq = null,
        public ?int $recorrencia_intervalo = null,
        public ?string $recorrencia_fim = null,
        public bool $eh_parcelado = false,
        public ?int $numero_parcelas = null,
        public int $parcela_atual = 1,
        public string $status = AgendamentoStatus::PENDENTE->value,
    ) {}

    /**
     * Cria um DTO a partir de dados do request.
     */
    public static function fromRequest(
        int $userId,
        array $data,
        ?string $proximaExecucao = null
    ): self {
        // Converter valor para centavos se necessário
        $valorCentavos = $data['valor_centavos'] ?? null;
        if ($valorCentavos === null || $valorCentavos === '') {
            $valorCentavos = self::moneyToCents($data['valor'] ?? $data['agValor'] ?? null);
        }

        // Normalizar data de pagamento
        $dataPagamento = str_replace('T', ' ', $data['data_pagamento']);

        // Calcular próxima execução se não fornecida
        if ($proximaExecucao === null) {
            $lembrarSegundos = (int) ($data['lembrar_antes_segundos'] ?? 0);
            $proximaExecucao = self::calcularProximaExecucao($dataPagamento, $lembrarSegundos);
        }

        return new self(
            user_id: $userId,
            titulo: $data['titulo'],
            tipo: strtolower(trim($data['tipo'])),
            valor_centavos: (int) $valorCentavos,
            data_pagamento: $dataPagamento,
            proxima_execucao: $proximaExecucao,
            lembrar_antes_segundos: (int) ($data['lembrar_antes_segundos'] ?? 0),
            canal_email: filter_var($data['canal_email'] ?? false, FILTER_VALIDATE_BOOLEAN),
            canal_inapp: filter_var($data['canal_inapp'] ?? true, FILTER_VALIDATE_BOOLEAN),
            descricao: !empty($data['descricao']) ? $data['descricao'] : null,
            categoria_id: !empty($data['categoria_id']) ? (int) $data['categoria_id'] : null,
            conta_id: !empty($data['conta_id']) ? (int) $data['conta_id'] : null,
            moeda: strtoupper(trim($data['moeda'] ?? 'BRL')),
            recorrente: filter_var($data['recorrente'] ?? false, FILTER_VALIDATE_BOOLEAN),
            recorrencia_freq: !empty($data['recorrencia_freq']) ? $data['recorrencia_freq'] : null,
            recorrencia_intervalo: !empty($data['recorrencia_intervalo']) ? (int) $data['recorrencia_intervalo'] : null,
            recorrencia_fim: !empty($data['recorrencia_fim']) ? $data['recorrencia_fim'] : null,
            eh_parcelado: filter_var($data['eh_parcelado'] ?? false, FILTER_VALIDATE_BOOLEAN),
            numero_parcelas: !empty($data['numero_parcelas']) ? (int) $data['numero_parcelas'] : null,
            parcela_atual: (int) ($data['parcela_atual'] ?? 1),
        );
    }

    /**
     * Converte para array para salvar no banco.
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'titulo' => $this->titulo,
            'tipo' => $this->tipo,
            'valor_centavos' => $this->valor_centavos,
            'data_pagamento' => $this->data_pagamento,
            'proxima_execucao' => $this->proxima_execucao,
            'lembrar_antes_segundos' => $this->lembrar_antes_segundos,
            'canal_email' => $this->canal_email,
            'canal_inapp' => $this->canal_inapp,
            'descricao' => $this->descricao,
            'categoria_id' => $this->categoria_id,
            'conta_id' => $this->conta_id,
            'moeda' => $this->moeda,
            'recorrente' => $this->recorrente,
            'recorrencia_freq' => $this->recorrencia_freq,
            'recorrencia_intervalo' => $this->recorrencia_intervalo,
            'recorrencia_fim' => $this->recorrencia_fim,
            'eh_parcelado' => $this->eh_parcelado,
            'numero_parcelas' => $this->numero_parcelas,
            'parcela_atual' => $this->parcela_atual,
            'status' => $this->status,
        ];
    }

    /**
     * Converte valor monetário para centavos.
     * Aceita string formatada ou número (int/float).
     */
    private static function moneyToCents(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Se for número, converte diretamente
        if (is_int($value) || is_float($value)) {
            return (int) round($value * 100);
        }

        // Se for string, faz o parsing
        $str = (string) $value;
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

    /**
     * Calcula próxima execução baseado na data de pagamento e tempo de lembrete.
     */
    private static function calcularProximaExecucao(string $dataPagamento, int $lembrarSegundos): string
    {
        return (new DateTimeImmutable($dataPagamento))
            ->modify("-{$lembrarSegundos} seconds")
            ->format('Y-m-d H:i:s');
    }
}
