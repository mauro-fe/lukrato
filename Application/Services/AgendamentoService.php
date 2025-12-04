<?php

namespace Application\Services;

use Application\Models\Agendamento;
use Application\Models\Lancamento;
use Application\Lib\Auth;
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
}