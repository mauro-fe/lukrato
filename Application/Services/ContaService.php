<?php

namespace Application\Services;

use Application\Models\Conta;
use Application\Models\Lancamento;
use Application\DTO\CreateContaDTO;
use Application\DTO\UpdateContaDTO;
use Application\Validators\ContaValidator;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

class ContaService
{
    public function __construct(
        private readonly ContaValidator $validator = new ContaValidator()
    ) {}

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
            $mes = $mes ?? date('Y-m');
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
        
        if (!$this->validator->validateCreate($data)) {
            return [
                'success' => false,
                'errors' => $this->validator->getErrors(),
                'message' => $this->validator->getFirstError(),
            ];
        }

        DB::beginTransaction();
        try {
            $conta = new Conta($data);
            $conta->save();

            // Criar lançamento de saldo inicial se necessário
            if (abs($dto->saldoInicial) > 0.00001) {
                $this->criarSaldoInicial($conta, $dto->saldoInicial);
            }

            DB::commit();
            
            return [
                'success' => true,
                'data' => $conta->fresh()->load('instituicaoFinanceira')->toArray(),
                'id' => $conta->id,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Erro ao criar conta: ' . $e->getMessage(),
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
        
        if (!empty($data) && !$this->validator->validateUpdate($data)) {
            return [
                'success' => false,
                'errors' => $this->validator->getErrors(),
                'message' => $this->validator->getFirstError(),
            ];
        }

        DB::beginTransaction();
        try {
            // Atualizar campos da conta
            foreach ($data as $key => $value) {
                if ($key !== 'saldo_inicial' && property_exists($conta, $key)) {
                    $conta->$key = $value;
                }
            }
            $conta->save();

            // Atualizar saldo inicial se fornecido
            if ($dto->saldoInicial !== null) {
                $this->atualizarSaldoInicial($conta, $dto->saldoInicial, $userId);
            }

            DB::commit();
            
            return [
                'success' => true,
                'data' => $conta->fresh()->load('instituicaoFinanceira')->toArray(),
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Erro ao atualizar conta: ' . $e->getMessage(),
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
     * Criar lançamento de saldo inicial
     */
    private function criarSaldoInicial(Conta $conta, float $valor): void
    {
        $isReceita = $valor >= 0;

        Lancamento::create([
            'user_id' => $conta->user_id,
            'tipo' => $isReceita ? 'receita' : 'despesa',
            'data' => date('Y-m-d'),
            'categoria_id' => null,
            'conta_id' => $conta->id,
            'conta_id_destino' => null,
            'descricao' => 'Saldo inicial da conta ' . $conta->nome,
            'observacao' => null,
            'valor' => abs($valor),
            'eh_transferencia' => 0,
            'eh_saldo_inicial' => 1,
        ]);
    }

    /**
     * Atualizar lançamento de saldo inicial
     */
    private function atualizarSaldoInicial(Conta $conta, float $novoValor, int $userId): void
    {
        $lancamento = Lancamento::where('user_id', $userId)
            ->where('conta_id', $conta->id)
            ->where('eh_saldo_inicial', 1)
            ->first();

        if (abs($novoValor) <= 0.00001) {
            // Remover saldo inicial
            if ($lancamento) {
                $lancamento->delete();
            }
            return;
        }

        $isReceita = $novoValor >= 0;
        $payload = [
            'user_id' => $userId,
            'tipo' => $isReceita ? 'receita' : 'despesa',
            'data' => $lancamento?->data?->format('Y-m-d') ?? date('Y-m-d'),
            'categoria_id' => null,
            'conta_id' => $conta->id,
            'conta_id_destino' => null,
            'descricao' => 'Saldo inicial da conta ' . $conta->nome,
            'observacao' => null,
            'valor' => abs($novoValor),
            'eh_transferencia' => 0,
            'eh_saldo_inicial' => 1,
        ];

        if ($lancamento) {
            $lancamento->fill($payload)->save();
        } else {
            Lancamento::create($payload);
        }
    }

    /**
     * Calcular saldos das contas
     */
    private function calcularSaldos(int $userId, array $contaIds, string $mes): array
    {
        if (empty($contaIds)) {
            return [];
        }

        // Data final do mês
        $dt = \DateTime::createFromFormat('Y-m', $mes);
        if (!$dt || $dt->format('Y-m') !== $mes) {
            $dt = new \DateTime(date('Y-m') . '-01');
        }
        $dataFim = (new \DateTimeImmutable($dt->format('Y-m-01')))
            ->modify('last day of this month')
            ->format('Y-m-d');

        // Saldos iniciais
        $saldosIniciais = Lancamento::where('user_id', $userId)
            ->whereIn('conta_id', $contaIds)
            ->where('eh_saldo_inicial', 1)
            ->selectRaw("
                conta_id,
                SUM(CASE WHEN tipo = 'despesa' THEN -valor ELSE valor END) as total
            ")
            ->groupBy('conta_id')
            ->pluck('total', 'conta_id')
            ->all();

        // Receitas
        $receitas = Lancamento::where('user_id', $userId)
            ->whereIn('conta_id', $contaIds)
            ->where('eh_transferencia', 0)
            ->where('eh_saldo_inicial', 0)
            ->where('data', '<=', $dataFim)
            ->where('tipo', 'receita')
            ->selectRaw('conta_id, SUM(valor) as total')
            ->groupBy('conta_id')
            ->pluck('total', 'conta_id')
            ->all();

        // Despesas
        $despesas = Lancamento::where('user_id', $userId)
            ->whereIn('conta_id', $contaIds)
            ->where('eh_transferencia', 0)
            ->where('eh_saldo_inicial', 0)
            ->where('data', '<=', $dataFim)
            ->where('tipo', 'despesa')
            ->selectRaw('conta_id, SUM(valor) as total')
            ->groupBy('conta_id')
            ->pluck('total', 'conta_id')
            ->all();

        // Transferências recebidas
        $transfIn = Lancamento::where('user_id', $userId)
            ->whereIn('conta_id_destino', $contaIds)
            ->where('eh_transferencia', 1)
            ->where('data', '<=', $dataFim)
            ->selectRaw('conta_id_destino as cid, SUM(valor) as total')
            ->groupBy('cid')
            ->pluck('total', 'cid')
            ->all();

        // Transferências enviadas
        $transfOut = Lancamento::where('user_id', $userId)
            ->whereIn('conta_id', $contaIds)
            ->where('eh_transferencia', 1)
            ->where('data', '<=', $dataFim)
            ->selectRaw('conta_id as cid, SUM(valor) as total')
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
}
