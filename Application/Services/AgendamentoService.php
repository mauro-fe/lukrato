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

    public function createLancamentoFromAgendamento(
        Agendamento $agendamento,
        ?int $contaId = null,
        ?string $formaPagamento = null
    ): ?Lancamento {
        $userId = $this->getUserId($agendamento);
        $valor = $this->centavosParaValor($agendamento->valor_centavos);

        if ($valor <= 0) {
            throw new RuntimeException('Valor do agendamento deve ser maior que zero.');
        }

        $descricao = trim((string) ($agendamento->titulo ?? ''));
        if ($descricao === '') {
            $descricao = 'Lançamento de Agendamento';
        }

        // Se for parcelado, adicionar info da parcela na descrição
        if ($agendamento->eh_parcelado && $agendamento->numero_parcelas > 1) {
            $descricao .= " ({$agendamento->parcela_atual}/{$agendamento->numero_parcelas})";
        }

        $observacao = $this->gerarObservacao($agendamento);
        $data = $this->extrairDataPagamento($agendamento->data_pagamento);

        // Usar conta passada ou a do agendamento
        $contaFinal = $contaId ?? $agendamento->conta_id;

        $lancamentoExistente = $this->buscarLancamentoExistente($userId, $observacao);
        if ($lancamentoExistente) {
            return $lancamentoExistente;
        }

        $lancamentoData = [
            'user_id'          => $userId,
            'tipo'             => $agendamento->tipo ?? Lancamento::TIPO_DESPESA,
            'data'             => $data,
            'categoria_id'     => $agendamento->categoria_id,
            'conta_id'         => $contaFinal,
            'descricao'        => $descricao,
            'observacao'       => $observacao,
            'valor'            => $valor,
            'eh_transferencia' => 0,
            'eh_saldo_inicial' => 0,
            'pago'             => true,
        ];

        // Adicionar forma de pagamento se fornecida
        if ($formaPagamento) {
            $lancamentoData['forma_pagamento'] = $formaPagamento;
        }

        return Lancamento::create($lancamentoData);
    }

    /**
     * EXECUTAR AGENDAMENTO - Lógica principal do sistema
     * 
     * CASO A: NÃO RECORRENTE E NÃO PARCELADO
     * - Criar lançamento
     * - Marcar como concluído
     * - Agendamento não aparece mais
     * 
     * CASO B: RECORRENTE
     * - Criar lançamento
     * - Avançar data para próxima ocorrência
     * - Agendamento continua ativo
     * 
     * CASO C: PARCELADO
     * - Criar lançamento com info da parcela
     * - Incrementar parcela_atual
     * - Se parcela_atual >= numero_parcelas, finalizar
     * - Senão, avançar data para próximo mês
     * 
     * @param Agendamento $agendamento
     * @param int|null $contaId Conta para o lançamento (sobrescreve a do agendamento)
     * @param string|null $formaPagamento Forma de pagamento do lançamento
     * @return array ['lancamento' => Lancamento, 'agendamento' => Agendamento, 'proximaData' => string|null]
     * @throws RuntimeException
     */
    public function executarAgendamento(
        Agendamento $agendamento,
        ?int $contaId = null,
        ?string $formaPagamento = null
    ): array {
        // 1. Criar lançamento com conta e forma de pagamento fornecidas
        $lancamento = $this->createLancamentoFromAgendamento($agendamento, $contaId, $formaPagamento);

        if (!$lancamento) {
            throw new RuntimeException('Falha ao criar lançamento.');
        }

        // 2. CASO C: PARCELADO
        if ($agendamento->eh_parcelado && $agendamento->numero_parcelas > 1) {
            $parcelaAtual = $agendamento->parcela_atual ?? 1;
            $proximaParcela = $parcelaAtual + 1;
            
            // Verificar se é a última parcela
            if ($proximaParcela > $agendamento->numero_parcelas) {
                // Última parcela - finalizar
                $agendamento->update([
                    'parcela_atual' => $parcelaAtual,
                    'status' => 'concluido',
                    'concluido_em' => date('Y-m-d H:i:s'),
                ]);

                LogService::info('Agendamento parcelado finalizado (última parcela)', [
                    'agendamento_id' => $agendamento->id,
                    'parcela_final' => $parcelaAtual,
                    'total_parcelas' => $agendamento->numero_parcelas,
                ]);

                return [
                    'lancamento' => $lancamento,
                    'agendamento' => $agendamento->fresh(),
                    'proximaData' => null,
                    'recorrente' => false,
                    'parcelado' => true,
                    'parcela_paga' => $parcelaAtual,
                    'total_parcelas' => $agendamento->numero_parcelas,
                    'finalizado' => true,
                ];
            } else {
                // Ainda há parcelas - avançar para próximo mês
                $proximaData = $this->calcularProximaData(
                    $agendamento->data_pagamento,
                    'monthly', // Parcelas sempre mensais
                    1
                );

                // Calcular próxima execução baseada no lembrete
                $lembrarSegundos = (int) ($agendamento->lembrar_antes_segundos ?? 0);
                $proximaExecucao = (new \DateTimeImmutable($proximaData))
                    ->modify("-{$lembrarSegundos} seconds")
                    ->format('Y-m-d H:i:s');

                $agendamento->update([
                    'parcela_atual' => $proximaParcela,
                    'data_pagamento' => $proximaData,
                    'proxima_execucao' => $proximaExecucao,
                    'status' => 'pendente',
                    'notificado_em' => null,
                ]);

                LogService::info('Agendamento parcelado - parcela paga, avançando', [
                    'agendamento_id' => $agendamento->id,
                    'parcela_paga' => $parcelaAtual,
                    'proxima_parcela' => $proximaParcela,
                    'proxima_data' => $proximaData,
                ]);

                return [
                    'lancamento' => $lancamento,
                    'agendamento' => $agendamento->fresh(),
                    'proximaData' => $proximaData,
                    'recorrente' => false,
                    'parcelado' => true,
                    'parcela_paga' => $parcelaAtual,
                    'total_parcelas' => $agendamento->numero_parcelas,
                    'finalizado' => false,
                ];
            }
        }

        // 3. CASO B: RECORRENTE
        if ($agendamento->recorrente && $agendamento->recorrencia_freq) {
            $proximaData = $this->calcularProximaData(
                $agendamento->data_pagamento,
                $agendamento->recorrencia_freq,
                $agendamento->recorrencia_intervalo ?? 1
            );

            // Calcular próxima execução baseada no lembrete
            $lembrarSegundos = (int) ($agendamento->lembrar_antes_segundos ?? 0);
            $proximaExecucao = (new \DateTimeImmutable($proximaData))
                ->modify("-{$lembrarSegundos} seconds")
                ->format('Y-m-d H:i:s');

            // Atualizar para próxima ocorrência
            $agendamento->update([
                'data_pagamento' => $proximaData,
                'proxima_execucao' => $proximaExecucao,
                'status' => 'pendente', // Volta para pendente
                'notificado_em' => null, // Limpa para permitir nova notificação
                'concluido_em' => date('Y-m-d H:i:s'), // Registra última execução
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
                'parcelado' => false,
            ];
        }

        // 4. CASO A: NÃO RECORRENTE E NÃO PARCELADO - Finalizar
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
            'parcelado' => false,
        ];
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

        // Normalizar frequência - aceitar tanto português quanto inglês
        $freq = strtolower($recorrenciaFreq);
        
        return match ($freq) {
            'daily', 'diario' => $data->modify("+{$intervalo} day")->format('Y-m-d H:i:s'),
            'weekly', 'semanal' => $data->modify("+{$intervalo} week")->format('Y-m-d H:i:s'),
            'monthly', 'mensal' => $data->modify("+{$intervalo} month")->format('Y-m-d H:i:s'),
            'yearly', 'anual' => $data->modify("+{$intervalo} year")->format('Y-m-d H:i:s'),
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
