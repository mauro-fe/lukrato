<?php

declare(strict_types=1);

namespace Application\Services\Conta;

use Application\Models\Conta;
use Application\Models\Lancamento;
use Application\DTO\CreateContaDTO;
use Application\DTO\UpdateContaDTO;
use Application\Validators\ContaValidator;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

class ContaService
{
    public function __construct()
    {
    }

    /**
     * Lista contas do usuário com filtros
     */
    public function listarContas(
        int $userId,
        bool $arquivadas = false,
        bool $apenasAtivas = true,
        bool $comSaldos = false,
        string $mes = null
    ): array {
        $query = Conta::forUser($userId)->with('instituicaoFinanceira');

        if ($arquivadas) {
            $query->arquivadas();
        } elseif ($apenasAtivas) {
            $query->ativas();
        }

        $contas = $query->orderBy('created_at', 'desc')->get();

        if ($comSaldos && $contas->count() > 0) {
            $saldos = $this->calcularSaldos($userId, $contas->pluck('id')->all(), $mes);

            return $contas->map(function ($conta) use ($saldos) {
                $saldo = $saldos[$conta->id] ?? null;
                return array_merge($conta->toArray(), $saldo ?? []);
            })->all();
        }

        return $contas->toArray();
    }

    /**
     * Criar nova conta
     */
    public function criarConta(CreateContaDTO $dto): array
    {
        $data = $dto->toArray();

        $errors = ContaValidator::validateCreate($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'message' => reset($errors) ?: 'Erro de validação',
            ];
        }

        DB::beginTransaction();
        try {
            // Incluir saldo inicial no array de dados
            $data['saldo_inicial'] = $dto->saldoInicial ?? 0;

            $conta = new Conta($data);
            $conta->save();

            DB::commit();


            return [
                'success' => true,
                'data' => $conta->fresh()->load('instituicaoFinanceira')->toArray(),
                'id' => $conta->id,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            \Application\Services\Infrastructure\LogService::captureException($e, \Application\Enums\LogCategory::GENERAL, [
                'action' => 'criar_conta',
                'user_id' => $dto->userId,
            ]);
            return [
                'success' => false,
                'message' => 'Erro ao criar conta.',
            ];
        }
    }

    /**
     * Atualizar conta existente
     */
    public function atualizarConta(int $contaId, int $userId, UpdateContaDTO $dto): array
    {
        $conta = Conta::forUser($userId)->find($contaId);

        if (!$conta) {
            return [
                'success' => false,
                'message' => 'Conta não encontrada.',
            ];
        }

        $data = $dto->toArray();

        // LOG: Dados do DTO
        \Application\Services\Infrastructure\LogService::info('📊 ContaService - Dados para atualização', [
            'conta_id' => $contaId,
            'user_id' => $userId,
            'data_dto' => $data,
            'conta_antes' => [
                'nome' => $conta->nome,
                'instituicao_financeira_id' => $conta->instituicao_financeira_id,
                'tipo_conta' => $conta->tipo_conta
            ]
        ]);

        if (!empty($data)) {
            $errors = ContaValidator::validateUpdate($data);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'errors' => $errors,
                    'message' => reset($errors) ?: 'Erro de validação',
                ];
            }
        }

        DB::beginTransaction();
        try {
            // Atualizar campos da conta (agora incluindo saldo_inicial)
            $camposAtualizados = [];
            foreach ($data as $key => $value) {
                if (in_array($key, $conta->getFillable())) {
                    $conta->$key = $value;
                    $camposAtualizados[$key] = $value;
                }
            }

            // Atualizar saldo inicial se fornecido
            if ($dto->saldoInicial !== null) {
                $conta->saldo_inicial = $dto->saldoInicial;
                $camposAtualizados['saldo_inicial'] = $dto->saldoInicial;
            }

            // LOG: Campos que serão atualizados
            \Application\Services\Infrastructure\LogService::info('💾 Salvando alterações', [
                'conta_id' => $contaId,
                'campos_atualizados' => $camposAtualizados
            ]);

            $conta->save();

            DB::commit();

            // LOG: Sucesso
            \Application\Services\Infrastructure\LogService::info('✅ Conta atualizada no banco', [
                'conta_id' => $contaId,
                'conta_depois' => [
                    'nome' => $conta->nome,
                    'instituicao_financeira_id' => $conta->instituicao_financeira_id,
                    'tipo_conta' => $conta->tipo_conta
                ]
            ]);

            return [
                'success' => true,
                'data' => $conta->fresh()->load('instituicaoFinanceira')->toArray(),
            ];
        } catch (Throwable $e) {
            DB::rollBack();

            \Application\Services\Infrastructure\LogService::error('❌ Erro ao salvar conta', [
                'conta_id' => $contaId,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao atualizar conta.',
            ];
        }
    }

    /**
     * Arquivar conta
     */
    public function arquivarConta(int $contaId, int $userId): array
    {
        $conta = Conta::forUser($userId)->find($contaId);

        if (!$conta) {
            return ['success' => false, 'message' => 'Conta não encontrada.'];
        }

        $conta->ativo = false;
        $conta->save();

        return ['success' => true, 'message' => 'Conta arquivada com sucesso.'];
    }

    /**
     * Restaurar conta arquivada
     */
    public function restaurarConta(int $contaId, int $userId): array
    {
        $conta = Conta::forUser($userId)->find($contaId);

        if (!$conta) {
            return ['success' => false, 'message' => 'Conta não encontrada.'];
        }

        $conta->ativo = true;
        $conta->save();

        return ['success' => true, 'message' => 'Conta restaurada com sucesso.'];
    }

    /**
     * Excluir conta permanentemente
     */
    public function excluirConta(int $contaId, int $userId, bool $force = false): array
    {
        $conta = Conta::forUser($userId)->find($contaId);

        if (!$conta) {
            return ['success' => false, 'message' => 'Conta não encontrada.'];
        }

        // Verificar lançamentos vinculados
        $countOrigem = Lancamento::where('user_id', $userId)->where('conta_id', $contaId)->count();
        $countDestino = Lancamento::where('user_id', $userId)->where('conta_id_destino', $contaId)->count();
        $totalLancamentos = $countOrigem + $countDestino;

        if ($totalLancamentos > 0 && !$force) {
            return [
                'success' => false,
                'requires_confirmation' => true,
                'message' => "Esta conta possui {$totalLancamentos} lançamento(s) vinculado(s). Confirme para excluir tudo.",
                'counts' => [
                    'origem' => $countOrigem,
                    'destino' => $countDestino,
                    'total' => $totalLancamentos,
                ],
            ];
        }

        DB::transaction(function () use ($userId, $contaId, $conta) {
            // Excluir lançamentos
            Lancamento::where('user_id', $userId)->where('conta_id', $contaId)->delete();
            Lancamento::where('user_id', $userId)->where('conta_id_destino', $contaId)->delete();

            // Excluir conta
            $conta->delete();
        });


        return [
            'success' => true,
            'message' => 'Conta e lançamentos excluídos permanentemente.',
            'deleted_lancamentos' => $totalLancamentos,
        ];
    }

    /**
     * REMOVIDO: Métodos criarSaldoInicial e atualizarSaldoInicial
     * Agora o saldo inicial é armazenado diretamente no campo contas.saldo_inicial
     */

    /**
     * Calcular saldos das contas
     */
    private function calcularSaldos(int $userId, array $contaIds, ?string $mes = null): array
    {
        if (empty($contaIds)) {
            return [];
        }

        // Data final do mês (null = sem limite de data = saldo acumulado total)
        $dataFim = null;
        if ($mes !== null) {
            $dt = \DateTime::createFromFormat('Y-m', $mes);
            if (!$dt || $dt->format('Y-m') !== $mes) {
                $dt = new \DateTime(date('Y-m') . '-01');
            }
            $dataFim = (new \DateTimeImmutable($dt->format('Y-m-01')))
                ->modify('last day of this month')
                ->format('Y-m-d');
        }

        // Saldos iniciais (agora do campo da tabela contas)
        $saldosIniciais = Conta::whereIn('id', $contaIds)
            ->pluck('saldo_inicial', 'id')
            ->all();

        // Receitas - apenas lançamentos pagos que afetam caixa
        $qReceitas = Lancamento::where('user_id', $userId)
            ->whereIn('conta_id', $contaIds)
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('tipo', 'receita')
            ->where('afeta_caixa', true);
        if ($dataFim !== null) {
            $qReceitas->where('data', '<=', $dataFim);
        }
        $receitas = $qReceitas->selectRaw('conta_id, SUM(valor) as total')
            ->groupBy('conta_id')
            ->pluck('total', 'conta_id')
            ->all();

        // Despesas - apenas lançamentos pagos que afetam caixa
        $qDespesas = Lancamento::where('user_id', $userId)
            ->whereIn('conta_id', $contaIds)
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('tipo', 'despesa')
            ->where('afeta_caixa', true);
        if ($dataFim !== null) {
            $qDespesas->where('data', '<=', $dataFim);
        }
        $despesas = $qDespesas->selectRaw('conta_id, SUM(valor) as total')
            ->groupBy('conta_id')
            ->pluck('total', 'conta_id')
            ->all();

        // Transferências recebidas
        $qTransfIn = Lancamento::where('user_id', $userId)
            ->whereIn('conta_id_destino', $contaIds)
            ->where('eh_transferencia', 1)
            ->where('pago', 1)
            ->where('afeta_caixa', 1);
        if ($dataFim !== null) {
            $qTransfIn->where('data', '<=', $dataFim);
        }
        $transfIn = $qTransfIn->selectRaw('conta_id_destino as cid, SUM(valor) as total')
            ->groupBy('cid')
            ->pluck('total', 'cid')
            ->all();

        // Transferências enviadas
        $qTransfOut = Lancamento::where('user_id', $userId)
            ->whereIn('conta_id', $contaIds)
            ->where('eh_transferencia', 1)
            ->where('pago', 1)
            ->where('afeta_caixa', 1);
        if ($dataFim !== null) {
            $qTransfOut->where('data', '<=', $dataFim);
        }
        $transfOut = $qTransfOut->selectRaw('conta_id as cid, SUM(valor) as total')
            ->groupBy('cid')
            ->pluck('total', 'cid')
            ->all();

        $resultado = [];
        foreach ($contaIds as $id) {
            $saldoInicial = (float) ($saldosIniciais[$id] ?? 0);
            $rec = (float) ($receitas[$id] ?? 0);
            $des = (float) ($despesas[$id] ?? 0);
            $tIn = (float) ($transfIn[$id] ?? 0);
            $tOut = (float) ($transfOut[$id] ?? 0);

            $saldoAtual = $saldoInicial + $rec - $des + $tIn - $tOut;

            $resultado[$id] = [
                'saldoInicial' => $saldoInicial,
                'saldoAtual' => $saldoAtual,
                'entradasTotal' => $rec + $tIn,
                'saidasTotal' => $des + $tOut,
            ];
        }

        return $resultado;
    }

    /**
     * Retorna o saldo atual de uma conta específica (calculado até hoje)
     */
    public function getSaldoAtual(int $contaId, int $userId): float
    {
        $saldos = $this->calcularSaldos($userId, [$contaId], date('Y-m'));
        return (float) ($saldos[$contaId]['saldoAtual'] ?? 0);
    }

}
