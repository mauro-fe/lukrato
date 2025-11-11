<?php

namespace Application\Services;

use Application\Models\Agendamento;
use Application\Models\Lancamento;
use Application\Lib\Auth;

class AgendamentoService
{
    /**
     * Obtém o ID do usuário autenticado (ou do agendamento, se disponível).
     * @return int
     * @throws \RuntimeException Se o ID do usuário não puder ser determinado.
     */
    private function getUserId(Agendamento $agendamento): int
    {
        $userId = $agendamento->user_id ?? Auth::user()->id ?? null;
        
        if (!$userId) {
            throw new \RuntimeException('Usuário não definido para o agendamento.');
        }

        return (int) $userId;
    }

    /**
     * Gera um novo Lancamento a partir de um Agendamento concluído.
     * Esta é a lógica de negócio central para a conclusão de um agendamento.
     * * @param Agendamento $agendamento O agendamento que será convertido.
     * @return Lancamento|null O lançamento recém-criado ou existente.
     * @throws \RuntimeException Se o valor for inválido ou o usuário não for definido.
     */
    public function createLancamentoFromAgendamento(Agendamento $agendamento): ?Lancamento
    {
        $userId = $this->getUserId($agendamento);

        $valorCentavos = $agendamento->valor_centavos;
        
        // Converte centavos para o formato de moeda (float ou decimal, dependendo do Model Lancamento)
        $valor = $valorCentavos !== null ? round(((int) $valorCentavos) / 100, 2) : 0.0;
        
        if ($valor <= 0) {
            throw new \RuntimeException('Valor do agendamento deve ser maior que zero.');
        }

        $descricao = trim((string) ($agendamento->titulo ?? ''));
        if ($descricao === '') {
            $descricao = 'Lançamento de Agendamento';
        }

        // Gera a observação, incluindo a referência ao Agendamento
        $observacaoBase = trim((string) ($agendamento->descricao ?? ''));
        $observacao = $observacaoBase !== ''
            ? $observacaoBase . ' (Agendamento #' . $agendamento->id . ')'
            : 'Gerado automaticamente do agendamento #' . $agendamento->id;

        // Determina a data do lançamento (pode ser a data de pagamento do agendamento)
        $dataPagamento = $agendamento->data_pagamento;
        $data = date('Y-m-d'); // Fallback para data atual

        if ($dataPagamento instanceof \DateTimeInterface) {
            $data = $dataPagamento->format('Y-m-d');
        } elseif (!empty($dataPagamento)) {
            $dt = date_create($dataPagamento);
            $data = $dt ? $dt->format('Y-m-d') : date('Y-m-d');
        }

        // Verifica se o Lançamento já existe para evitar duplicidade
        $existing = Lancamento::where('user_id', $userId)
            ->where('observacao', $observacao)
            ->first();
            
        if ($existing) {
            // Retorna o existente se já foi criado
            return $existing;
        }

        // Cria e retorna o novo Lançamento
        return Lancamento::create([
            'user_id'          => $userId,
            // Assumindo que Lancamento::TIPO_DESPESA é uma constante no Model Lancamento
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
}