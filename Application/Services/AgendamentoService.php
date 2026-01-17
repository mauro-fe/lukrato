<?php

namespace Application\Services;

use Application\Models\Agendamento;
use Application\Models\Lancamento;
use Application\Lib\Auth;
use Application\Services\LogService;
use RuntimeException;

class AgendamentoService
{

    private function getUserId(Agendamento $agendamento): int
    {
        $userId = $agendamento->user_id ?? Auth::user()->id ?? null;

        if (!$userId) {
            throw new RuntimeException('Usuário não definido para o agendamento.');
        }

        return (int) $userId;
    }


    private function centavosParaValor(?int $valorCentavos): float
    {
        if ($valorCentavos === null) {
            return 0.0;
        }

        return round($valorCentavos / 100, 2);
    }

    private function gerarObservacao(Agendamento $agendamento): string
    {
        $observacaoBase = trim((string) ($agendamento->descricao ?? ''));

        if ($observacaoBase !== '') {
            return "{$observacaoBase} (Agendamento #{$agendamento->id})";
        }

        return "Gerado automaticamente do agendamento #{$agendamento->id}";
    }

    private function extrairDataPagamento($dataPagamento): string
    {
        if ($dataPagamento instanceof \DateTimeInterface) {
            return $dataPagamento->format('Y-m-d');
        }

        if (!empty($dataPagamento)) {
            $dt = date_create($dataPagamento);
            if ($dt) {
                return $dt->format('Y-m-d');
            }
        }

        return date('Y-m-d');
    }

    private function buscarLancamentoExistente(int $userId, string $observacao): ?Lancamento
    {
        return Lancamento::where('user_id', $userId)
            ->where('observacao', $observacao)
            ->first();
    }

    public function createLancamentoFromAgendamento(Agendamento $agendamento): ?Lancamento
    {
        $userId = $this->getUserId($agendamento);
        $valor = $this->centavosParaValor($agendamento->valor_centavos);

        if ($valor <= 0) {
            throw new RuntimeException('Valor do agendamento deve ser maior que zero.');
        }

        $descricao = trim((string) ($agendamento->titulo ?? ''));
        if ($descricao === '') {
            $descricao = 'Lançamento de Agendamento';
        }

        $observacao = $this->gerarObservacao($agendamento);
        $data = $this->extrairDataPagamento($agendamento->data_pagamento);

        $lancamentoExistente = $this->buscarLancamentoExistente($userId, $observacao);
        if ($lancamentoExistente) {
            return $lancamentoExistente;
        }

        return Lancamento::create([
            'user_id'          => $userId,
            'tipo'             => $agendamento->tipo ?? Lancamento::TIPO_DESPESA,
            'data'             => $data,
            'categoria_id'     => $agendamento->categoria_id,
            'conta_id'         => $agendamento->conta_id,
            'descricao'        => $descricao,
            'observacao'       => $observacao,
            'valor'            => $valor,
            'eh_transferencia' => 0,
            'eh_saldo_inicial' => 0,
        ]);
    }

    /**
     * EXECUTAR AGENDAMENTO - Lógica principal do sistema
     * 
     * CASO A: NÃO RECORRENTE
     * - Criar lançamento
     * - Marcar como concluído
     * - Agendamento não aparece mais
     * 
     * CASO B: RECORRENTE
     * - Criar lançamento
     * - Avançar data para próxima ocorrência
     * - Agendamento continua ativo
     * 
     * @param Agendamento $agendamento
     * @return array ['lancamento' => Lancamento, 'agendamento' => Agendamento, 'proximaData' => string|null]
     * @throws RuntimeException
     */
    public function executarAgendamento(Agendamento $agendamento): array
    {
        // 1. Criar lançamento
        $lancamento = $this->createLancamentoFromAgendamento($agendamento);

        if (!$lancamento) {
            throw new RuntimeException('Falha ao criar lançamento.');
        }

        // 2. Verificar se é recorrente
        if ($agendamento->recorrente && $agendamento->recorrencia_freq) {
            // CASO B: RECORRENTE - Avançar data
            $proximaData = $this->calcularProximaData(
                $agendamento->data_pagamento,
                $agendamento->recorrencia_freq,
                $agendamento->recorrencia_intervalo ?? 1
            );

            // Atualizar para próxima ocorrência
            $agendamento->update([
                'data_pagamento' => $proximaData,
                'concluido_em' => date('Y-m-d H:i:s'), // Registra última execução
                // Status continua PENDENTE para aparecer novamente
            ]);

            LogService::info('Agendamento recorrente executado e avançado', [
                'agendamento_id' => $agendamento->id,
                'proxima_data' => $proximaData,
            ]);

            return [
                'lancamento' => $lancamento,
                'agendamento' => $agendamento->fresh(),
                'proximaData' => $proximaData,
                'recorrente' => true,
            ];
        } else {
            // CASO A: NÃO RECORRENTE - Finalizar
            $agendamento->update([
                'status' => 'concluido',
                'concluido_em' => date('Y-m-d H:i:s'),
            ]);

            LogService::info('Agendamento único executado e finalizado', [
                'agendamento_id' => $agendamento->id,
            ]);

            return [
                'lancamento' => $lancamento,
                'agendamento' => $agendamento->fresh(),
                'proximaData' => null,
                'recorrente' => false,
            ];
        }
    }

    /**
     * Calcula STATUS DINÂMICO baseado na data e recorrência
     * NÃO SALVA NO BANCO - apenas retorna o status calculado
     * 
     * @param Agendamento $agendamento
     * @return string 'hoje', 'agendado', 'vencido', 'executado'
     */
    public function calcularStatusDinamico(Agendamento $agendamento): string
    {
        // Se foi cancelado, retorna cancelado
        if ($agendamento->status === 'cancelado') {
            return 'cancelado';
        }

        // Se foi executado E não é recorrente, está finalizado
        if ($agendamento->concluido_em && !$agendamento->recorrente) {
            return 'executado';
        }

        // Para recorrentes, calcular baseado na próxima data
        $dataAgendada = $agendamento->data_pagamento;
        if (!$dataAgendada) {
            return 'agendado';
        }

        $hoje = date('Y-m-d');
        $dataComparacao = $dataAgendada instanceof \DateTimeInterface
            ? $dataAgendada->format('Y-m-d')
            : date('Y-m-d', strtotime($dataAgendada));

        if ($dataComparacao === $hoje) {
            return 'hoje';
        } elseif ($dataComparacao < $hoje) {
            return 'vencido';
        } else {
            return 'agendado';
        }
    }

    /**
     * Calcula a próxima data de vencimento baseada na recorrência
     * 
     * @param string|\DateTimeInterface $dataBase Data base
     * @param string|null $recorrenciaFreq Tipo de recorrência (daily, weekly, monthly, yearly)
     * @param int $intervalo Intervalo de recorrência (padrão: 1)
     * @return string Nova data (Y-m-d H:i:s)
     */
    public function calcularProximaData(
        $dataBase,
        ?string $recorrenciaFreq = null,
        int $intervalo = 1
    ): string {
        // Converter para DateTime
        if ($dataBase instanceof \DateTimeInterface) {
            $data = \DateTime::createFromFormat('Y-m-d H:i:s', $dataBase->format('Y-m-d H:i:s'));
        } else {
            $data = \DateTime::createFromFormat('Y-m-d H:i:s', $dataBase);
            if (!$data) {
                $data = \DateTime::createFromFormat('Y-m-d', $dataBase);
                if (!$data) {
                    $data = new \DateTime($dataBase);
                }
            }
        }

        if (!$data || !$recorrenciaFreq) {
            return date('Y-m-d H:i:s');
        }

        $intervalo = max(1, $intervalo); // Mínimo 1

        return match (strtolower($recorrenciaFreq)) {
            'daily' => $data->modify("+{$intervalo} day")->format('Y-m-d H:i:s'),
            'weekly' => $data->modify("+{$intervalo} week")->format('Y-m-d H:i:s'),
            'monthly' => $data->modify("+{$intervalo} month")->format('Y-m-d H:i:s'),
            'yearly' => $data->modify("+{$intervalo} year")->format('Y-m-d H:i:s'),
            default => $data->format('Y-m-d H:i:s'),
        };
    }

    /**
     * Processa recorrência do agendamento
     * 
     * @param Agendamento $agendamento
     * @return bool
     */
    public function processarRecorrencia(Agendamento $agendamento): bool
    {
        if ($agendamento->recorrencia === 'unico' || !$agendamento->recorrencia) {
            return false;
        }

        try {
            $proximaData = $this->calcularProximaData(
                $agendamento->data_vencimento,
                $agendamento->recorrencia
            );

            // Criar novo agendamento
            Agendamento::create([
                'user_id' => $agendamento->user_id,
                'tipo' => $agendamento->tipo,
                'titulo' => $agendamento->titulo,
                'descricao' => $agendamento->descricao,
                'valor_centavos' => $agendamento->valor_centavos,
                'categoria_id' => $agendamento->categoria_id,
                'conta_id' => $agendamento->conta_id,
                'data_vencimento' => $proximaData,
                'data_pagamento' => null,
                'recorrencia' => $agendamento->recorrencia,
                'status' => 'pendente',
            ]);

            return true;
        } catch (\Throwable $e) {
            LogService::error('Erro ao processar recorrência', [
                'agendamento_id' => $agendamento->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Valida data de pagamento
     * 
     * @param string $data Data a validar
     * @return bool
     */
    public function validarDataPagamento(string $data): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $data);
        return $dt && $dt->format('Y-m-d') === $data;
    }
}
