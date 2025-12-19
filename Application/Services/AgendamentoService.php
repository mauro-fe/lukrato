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
     * Processa agendamentos pendentes e cria lançamentos
     * 
     * @param Agendamento $agendamento
     * @return bool
     */
    public function processarAgendamento(Agendamento $agendamento): bool
    {
        if ($agendamento->status !== 'pendente') {
            return false;
        }

        try {
            // Criar lançamento
            $lancamento = $this->createLancamentoFromAgendamento($agendamento);
            
            if ($lancamento) {
                // Atualizar agendamento para concluído
                $agendamento->update([
                    'status' => 'concluido',
                    'data_pagamento' => date('Y-m-d'),
                ]);

                return true;
            }

            return false;
        } catch (\Throwable $e) {
            LogService::error('Erro ao processar agendamento', [
                'agendamento_id' => $agendamento->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Calcula a próxima data de vencimento baseada na recorrência
     * 
     * @param string $dataBase Data base (Y-m-d)
     * @param string $recorrencia Tipo de recorrência (mensal, semanal, anual)
     * @return string Nova data (Y-m-d)
     */
    public function calcularProximaData(string $dataBase, string $recorrencia): string
    {
        $data = \DateTime::createFromFormat('Y-m-d', $dataBase);
        
        if (!$data) {
            return date('Y-m-d');
        }

        return match(strtolower($recorrencia)) {
            'semanal' => $data->modify('+1 week')->format('Y-m-d'),
            'mensal' => $data->modify('+1 month')->format('Y-m-d'),
            'anual' => $data->modify('+1 year')->format('Y-m-d'),
            default => $dataBase,
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
