<?php

namespace Application\Services;

use Application\Models\Agendamento;
use Application\Models\Lancamento;
use Application\Lib\Auth;
use Application\Services\LogService;
use Application\Enums\AgendamentoStatus;
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
        $dataPag = $this->extrairDataPagamento($agendamento->data_pagamento);

        if ($observacaoBase !== '') {
            return "{$observacaoBase} (Agendamento #{$agendamento->id} - venc. {$dataPag})";
        }

        return "Gerado automaticamente do agendamento #{$agendamento->id} - venc. {$dataPag}";
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
        // Quando executa o pagamento, usar o momento atual como data do lançamento
        $data = date('Y-m-d H:i:s');

        // Usar conta passada ou a do agendamento
        $contaFinal = $contaId ?? $agendamento->conta_id;

        $lancamentoExistente = $this->buscarLancamentoExistente($userId, $observacao);
        if ($lancamentoExistente) {
            return $lancamentoExistente;
        }

        $lancamentoData = [
            'user_id'          => $userId,
            'tipo'             => $agendamento->tipo ?? Lancamento::TIPO_DESPESA,
            'data'             => $data, // Data e hora do pagamento real
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

        // Capturar data_pagamento ANTES de qualquer modificação para logging
        $dataBaseOriginal = $agendamento->data_pagamento instanceof \DateTimeInterface
            ? $agendamento->data_pagamento->format('Y-m-d H:i:s')
            : (string) $agendamento->data_pagamento;

        // 2. CASO C: PARCELADO
        if ($agendamento->eh_parcelado && $agendamento->numero_parcelas > 1) {
            $parcelaAtual = $agendamento->parcela_atual ?? 1;
            $proximaParcela = $parcelaAtual + 1;

            // Verificar se é a última parcela
            if ($proximaParcela > $agendamento->numero_parcelas) {
                // Última parcela - finalizar (mantém pendente, concluido_em marca execução)
                $agendamento->update([
                    'parcela_atual' => $parcelaAtual,
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
                // IMPORTANTE: usar $dataBaseOriginal (string) para evitar bugs de timezone
                $proximaData = $this->calcularProximaData(
                    $dataBaseOriginal,
                    'monthly', // Parcelas sempre mensais
                    1
                );

                LogService::info('Agendamento parcelado - calculando próxima data', [
                    'agendamento_id' => $agendamento->id,
                    'data_pagamento_base' => $dataBaseOriginal,
                    'proxima_data_calculada' => $proximaData,
                    'parcela_atual' => $parcelaAtual,
                    'proxima_parcela' => $proximaParcela,
                ]);

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
                    'lembrete_antecedencia_em' => null,
                    // concluido_em NÃO é setado - parcelado só conclui na última parcela
                ]);

                LogService::info('Agendamento parcelado - parcela paga, avançando', [
                    'agendamento_id' => $agendamento->id,
                    'parcela_paga' => $parcelaAtual,
                    'proxima_parcela' => $proximaParcela,
                    'data_pagamento_anterior' => $dataBaseOriginal,
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
            // Forçar intervalo 1 - recorrência avança SEMPRE 1 período por execução
            $intervalo = (int) ($agendamento->recorrencia_intervalo ?? 1);
            if ($intervalo < 1) {
                $intervalo = 1;
            }

            // IMPORTANTE: usar $dataBaseOriginal (string capturada ANTES de qualquer modificação)
            // para evitar problemas de referência/timezone com o objeto Carbon
            $proximaData = $this->calcularProximaData(
                $dataBaseOriginal,
                $agendamento->recorrencia_freq,
                $intervalo
            );

            LogService::info('Agendamento recorrente - calculando próxima data', [
                'agendamento_id' => $agendamento->id,
                'data_pagamento_base' => $dataBaseOriginal,
                'recorrencia_freq' => $agendamento->recorrencia_freq,
                'recorrencia_intervalo' => $intervalo,
                'proxima_data_calculada' => $proximaData,
            ]);

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
                'lembrete_antecedencia_em' => null, // Limpa para permitir novo lembrete de antecedência
                // concluido_em NÃO é setado - recorrentes nunca ficam concluídos
            ]);

            LogService::info('Agendamento recorrente executado e avançado', [
                'agendamento_id' => $agendamento->id,
                'data_pagamento_anterior' => $dataBaseOriginal,
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

        // 4. CASO A: NÃO RECORRENTE E NÃO PARCELADO - Finalizar (mantém pendente, concluido_em marca execução)
        $agendamento->update([
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
     * @return string 'hoje', 'agendado', 'vencido', 'cancelado'
     */
    public function calcularStatusDinamico(Agendamento $agendamento): string
    {
        // Se foi cancelado, retorna cancelado
        if ($agendamento->status === 'cancelado') {
            return 'cancelado';
        }

        // Para recorrentes, calcular baseado na próxima data
        $dataAgendada = $agendamento->data_pagamento;
        if (!$dataAgendada) {
            return 'agendado';
        }

        $agendadaDT = $dataAgendada instanceof \DateTimeInterface
            ? $dataAgendada
            : new \DateTime($dataAgendada);

        $agendadaFull = $agendadaDT->format('Y-m-d H:i:s');
        $nowFull = date('Y-m-d H:i:s');
        $agendadaDate = $agendadaDT->format('Y-m-d');
        $nowDate = date('Y-m-d');

        if ($agendadaDate === $nowDate) {
            // Se o horário já passou, marca como vencido
            if ($agendadaFull < $nowFull) {
                return 'vencido';
            } else {
                return 'hoje';
            }
        } elseif ($agendadaDate < $nowDate) {
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
        // Sempre extrair como string primeiro para evitar ambiguidade de timezone/referência
        $dataString = null;
        if ($dataBase instanceof \DateTimeInterface) {
            $dataString = $dataBase->format('Y-m-d H:i:s');
        } elseif (is_string($dataBase) && !empty($dataBase)) {
            $dataString = $dataBase;
        }

        if (!$dataString || !$recorrenciaFreq) {
            return date('Y-m-d H:i:s');
        }

        // Criar DateTime a partir da string (sempre novo objeto, sem referências compartilhadas)
        $data = \DateTime::createFromFormat('Y-m-d H:i:s', $dataString);
        if (!$data) {
            $data = \DateTime::createFromFormat('Y-m-d', $dataString);
            if (!$data) {
                $data = new \DateTime($dataString);
            }
        }

        if (!$data) {
            return date('Y-m-d H:i:s');
        }

        $intervalo = max(1, $intervalo); // Mínimo 1

        // Guardar data original para log/validação
        $dataOriginal = $data->format('Y-m-d');

        // Normalizar frequência - aceitar tanto português quanto inglês
        $freq = strtolower($recorrenciaFreq);

        $resultado = match ($freq) {
            'daily', 'diario' => $data->modify("+{$intervalo} day")->format('Y-m-d H:i:s'),
            'weekly', 'semanal' => $data->modify("+{$intervalo} week")->format('Y-m-d H:i:s'),
            'monthly', 'mensal' => $data->modify("+{$intervalo} month")->format('Y-m-d H:i:s'),
            'yearly', 'anual' => $data->modify("+{$intervalo} year")->format('Y-m-d H:i:s'),
            default => $data->format('Y-m-d H:i:s'),
        };

        // Log para rastreabilidade (ajuda a diagnosticar se o bug ocorrer novamente)
        LogService::info('calcularProximaData resultado', [
            'data_base_original' => $dataString,
            'frequencia' => $freq,
            'intervalo' => $intervalo,
            'resultado' => $resultado,
        ]);

        return $resultado;
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

    // ─── Execução com lock pessimista ──────────────────────

    /**
     * Executa agendamento com lock pessimista e proteção contra duplicatas.
     * Retorna array com 'duplicate', 'resultado', 'data_pagamento_antes'.
     */
    public function executarComLock(int $userId, int $id, ?string $expectedData, ?int $contaId, ?string $formaPagamento): array
    {
        /** @var \Illuminate\Database\Connection $conn */
        $conn = \Illuminate\Database\Capsule\Manager::connection();

        return $conn->transaction(function () use ($userId, $id, $expectedData, $contaId, $formaPagamento) {

            $agendamento = Agendamento::where('user_id', $userId)
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$agendamento) {
                throw new RuntimeException('Agendamento não encontrado.', 404);
            }

            $statusExecutaveis = [AgendamentoStatus::PENDENTE->value, AgendamentoStatus::NOTIFICADO->value];
            if (!in_array($agendamento->status, $statusExecutaveis, true)) {
                throw new RuntimeException('Somente agendamentos pendentes podem ser executados.', 400);
            }

            // Verificar duplicatas
            if ($this->isDuplicate($agendamento, $id, $userId, $expectedData)) {
                return ['duplicate' => true, 'agendamento' => $agendamento];
            }

            $dataAntes = $agendamento->data_pagamento instanceof \DateTimeInterface
                ? $agendamento->data_pagamento->format('Y-m-d H:i:s')
                : (string) $agendamento->data_pagamento;

            LogService::info('Executando agendamento (com lock)', [
                'agendamento_id'       => $id,
                'data_pagamento_atual' => $dataAntes,
                'recorrente'           => (bool) $agendamento->recorrente,
                'recorrencia_freq'     => $agendamento->recorrencia_freq,
                'eh_parcelado'         => (bool) $agendamento->eh_parcelado,
                'parcela_atual'        => $agendamento->parcela_atual,
                'numero_parcelas'      => $agendamento->numero_parcelas,
                'status'               => $agendamento->status,
                'user_id'              => $userId,
            ]);

            $resultado = $this->executarAgendamento($agendamento, $contaId, $formaPagamento);
            $resultado['duplicate'] = false;
            $resultado['data_pagamento_antes'] = $dataAntes;

            return $resultado;
        });
    }

    /**
     * Monta a mensagem de resposta para execução de agendamento.
     */
    public function buildExecutionMessage(array $resultado): string
    {
        if ($resultado['parcelado'] ?? false) {
            if ($resultado['finalizado'] ?? false) {
                return "Última parcela paga! ({$resultado['parcela_paga']}/{$resultado['total_parcelas']}) Agendamento finalizado.";
            }
            return "Parcela {$resultado['parcela_paga']}/{$resultado['total_parcelas']} paga! Próxima: " . date('d/m/Y', strtotime($resultado['proximaData']));
        }

        if ($resultado['recorrente']) {
            return 'Lançamento criado! Próxima ocorrência agendada para ' . date('d/m/Y', strtotime($resultado['proximaData']));
        }

        return 'Agendamento executado com sucesso!';
    }

    // ─── Duplicate detection ────────────────────────────────

    private function isDuplicate(Agendamento $agendamento, int $id, int $userId, ?string $expectedData): bool
    {
        // 1) Verificar data esperada vs data atual
        if ($expectedData) {
            try {
                $expectedDT = new \DateTimeImmutable($expectedData);
                $expectedDT = $expectedDT->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                $expectedDate = $expectedDT->format('Y-m-d');
            } catch (\Throwable $e) {
                $expectedDate = substr($expectedData, 0, 10);
            }

            $currentData = $agendamento->data_pagamento instanceof \DateTimeInterface
                ? $agendamento->data_pagamento->format('Y-m-d H:i:s')
                : (string) $agendamento->data_pagamento;
            $currentDate = substr($currentData, 0, 10);

            if ($expectedDate !== $currentDate) {
                LogService::warning('Execução duplicada bloqueada (data já avançou)', [
                    'agendamento_id' => $id,
                    'expected_date' => $expectedDate,
                    'current_date' => $currentDate,
                    'user_id' => $userId,
                ]);
                return true;
            }
        }

        // 2) Recorrentes: proteção contra execução rápida consecutiva
        if ($agendamento->recorrente && $agendamento->updated_at && $agendamento->created_at) {
            $updatedAt = $agendamento->updated_at instanceof \DateTimeInterface
                ? $agendamento->updated_at->getTimestamp()
                : strtotime((string) $agendamento->updated_at);
            $createdAt = $agendamento->created_at instanceof \DateTimeInterface
                ? $agendamento->created_at->getTimestamp()
                : strtotime((string) $agendamento->created_at);
            $diffSeconds = time() - $updatedAt;
            $isNewlyCreated = abs($updatedAt - $createdAt) < 15;

            if ($diffSeconds >= 0 && $diffSeconds < 10 && !$isNewlyCreated && $expectedData) {
                LogService::warning('Execução duplicada bloqueada (updated_at recente para recorrente)', [
                    'agendamento_id' => $id,
                    'diff_seconds' => $diffSeconds,
                    'user_id' => $userId,
                ]);
                return true;
            }
        }

        // 3) Proteção via concluido_em
        if ($agendamento->concluido_em) {
            $ultimaExecucao = strtotime($agendamento->concluido_em);
            if (time() - $ultimaExecucao < 30) {
                LogService::warning('Execução duplicada bloqueada (concluido_em recente)', [
                    'agendamento_id' => $id,
                    'concluido_em' => $agendamento->concluido_em,
                    'user_id' => $userId,
                ]);
                return true;
            }
        }

        return false;
    }
}
