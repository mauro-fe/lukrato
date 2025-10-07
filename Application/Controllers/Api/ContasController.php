<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Models\Conta;
use Application\Lib\Auth;
use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Capsule\Manager;

class ContasController
{
    public function index(): void
    {
        $userId       = Auth::id();

        $archived     = (int)($_GET['archived'] ?? 0) === 1;
        $onlyActive   = (int)($_GET['only_active'] ?? ($archived ? 0 : 1)) === 1;

        $withBalances = (int)($_GET['with_balances'] ?? 0) === 1;
        $month        = trim((string)($_GET['month'] ?? date('Y-m')));

        $q = Conta::forUser($userId);
        if ($archived) {
            $q->arquivadas();
        } elseif ($onlyActive) {
            $q->ativas();
        }

        $rows = $q->orderBy('nome')->get();

        $extras = [];
        $saldoIniciais = [];
        $ids = $rows->pluck('id')->all();

        if ($rows->count()) {
            $saldoIniciais = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id', $ids)
                ->where('eh_saldo_inicial', 1)
                ->selectRaw("
                    conta_id,
                    SUM(
                        CASE
                            WHEN tipo = ?
                                THEN -valor
                            ELSE valor
                        END
                    ) as total
                ", [Lancamento::TIPO_DESPESA])
                ->groupBy('conta_id')
                ->pluck('total', 'conta_id')
                ->all();
        }

        if ($withBalances && $rows->count()) {
            $dt = \DateTime::createFromFormat('Y-m', $month);
            if (!$dt || $dt->format('Y-m') !== $month) {
                $dt = new \DateTime(date('Y-m') . '-01');
            }
            $ate = (new \DateTimeImmutable($dt->format('Y-m-01')))
                ->modify('last day of this month')
                ->format('Y-m-d');

            // RECEITAS (não transferência) até a data
            $rec = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id', $ids)
                ->where('eh_transferencia', 0)
                ->where('data', '<=', $ate)
                ->where('tipo', Lancamento::TIPO_RECEITA)
                ->selectRaw('conta_id, SUM(valor) as tot')
                ->groupBy('conta_id')->pluck('tot', 'conta_id')->all();

            // DESPESAS (não transferência) até a data
            $des = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id', $ids)
                ->where('eh_transferencia', 0)
                ->where('data', '<=', $ate)
                ->where('tipo', Lancamento::TIPO_DESPESA)
                ->selectRaw('conta_id, SUM(valor) as tot')
                ->groupBy('conta_id')->pluck('tot', 'conta_id')->all();

            // TRANSFERÊNCIAS RECEBIDAS até a data
            $tin = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id_destino', $ids)
                ->where('eh_transferencia', 1)
                ->where('data', '<=', $ate)
                ->selectRaw('conta_id_destino as cid, SUM(valor) as tot')
                ->groupBy('cid')->pluck('tot', 'cid')->all();

            // TRANSFERÊNCIAS ENVIADAS até a data
            $tout = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id', $ids)
                ->where('eh_transferencia', 1)
                ->where('data', '<=', $ate)
                ->selectRaw('conta_id as cid, SUM(valor) as tot')
                ->groupBy('cid')->pluck('tot', 'cid')->all();

            foreach ($ids as $cid) {
                $r = (float)($rec[$cid]  ?? 0);
                $d = (float)($des[$cid]  ?? 0);
                $i = (float)($tin[$cid]  ?? 0);
                $o = (float)($tout[$cid] ?? 0);

                $extras[$cid] = [
                    'saldoAtual'  => $r - $d + $i - $o,
                    // Obs: abaixo são totais até a data. Se quiser "do mês", troque por BETWEEN no 1º e último dia do mês.
                    'entradasMes' => $r + $i,
                    'saidasMes'   => $d + $o,
                    'saldoInicial' => (float)($saldoIniciais[$cid] ?? 0),
                ];
            }
        }

        Response::json($rows->map(function ($c) use ($extras, $saldoIniciais) {
            $x = $extras[$c->id] ?? null;
            return [
                'id'            => (int)$c->id,
                'nome'          => (string)$c->nome,
                'instituicao'   => (string)($c->instituicao ?? ''),
                'moeda'         => (string)($c->moeda ?? 'BRL'),
                // Mantido por compatibilidade, mas agora vem sempre 0 (não existe mais coluna).
                'saldoInicial'  => $x ? (float)$x['saldoInicial'] : (float)($saldoIniciais[$c->id] ?? 0),
                // saldo inicial calculado a partir do lançamento dedicado (caso exista)
                'saldoAtual'    => $x ? (float)$x['saldoAtual']  : null,
                'entradasMes'   => $x ? (float)$x['entradasMes'] : 0.0,
                'saidasMes'     => $x ? (float)$x['saidasMes']   : 0.0,
                'ativo'         => (bool)$c->ativo,
                'arquivada'     => !(bool)$c->ativo,
            ];
        })->all());
    }

    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $nome = trim((string)($data['nome'] ?? ''));

        if ($nome === '') {
            Response::json(['status' => 'error', 'message' => 'Nome obrigatório.'], 422);
            return;
        }

        $moeda = strtoupper(trim((string)($data['moeda'] ?? 'BRL')));
        $allowedMoedas = ['BRL', 'USD', 'EUR'];
        if (!in_array($moeda, $allowedMoedas, true)) $moeda = 'BRL';

        $tipoId = isset($data['tipo_id']) && $data['tipo_id'] !== '' ? (int)$data['tipo_id'] : null;

        // normaliza saldo_inicial recebido (apenas para criar Lançamento)
        $saldoInicial = (float)($data['saldo_inicial'] ?? 0);

        DB::beginTransaction();
        try {
            $conta = new Conta([
                'user_id'     => Auth::id(),
                'nome'        => $nome,
                'instituicao' => $data['instituicao'] ?? null,
                'moeda'       => $moeda,
                'tipo_id'     => $tipoId,
                'ativo'       => 1,
            ]);
            $conta->save();

            if (abs($saldoInicial) > 0.00001) {
                Lancamento::create([
                    'user_id'           => Auth::id(),
                    'tipo'              => $saldoInicial >= 0 ? Lancamento::TIPO_RECEITA : Lancamento::TIPO_DESPESA,
                    'data'              => date('Y-m-d'),
                    'categoria_id'      => null,
                    'conta_id'          => $conta->id,
                    'conta_id_destino'  => null,
                    'descricao'         => 'Saldo inicial da conta ' . $nome,
                    'observacao'        => null,
                    'valor'             => abs($saldoInicial),
                    'eh_transferencia'  => 0,
                    'eh_saldo_inicial'  => 1,
                ]);
            }

            DB::commit();
            Response::json(['ok' => true, 'id' => (int) $conta->id]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Response::json(['status' => 'error', 'message' => 'Falha ao criar conta: ' . $e->getMessage()], 500);
        }
    }

    public function update(int $id): void
    {
        $conta = Conta::forUser(Auth::id())->find($id);
        if (!$conta) {
            Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        foreach (['nome', 'instituicao', 'moeda'] as $f) {
            if (array_key_exists($f, $data)) $conta->{$f} = trim((string)$data[$f]);
        }
        if (array_key_exists('ativo', $data)) {
            $conta->ativo = (int) !!$data['ativo'];
        }

        DB::beginTransaction();
        try {
            $conta->save();

            // Upsert do SALDO INICIAL via lancamento (sem coluna na tabela contas)
            if (array_key_exists('saldo_inicial', $data)) {
                $novoSaldo = (float) $data['saldo_inicial'];

                // procura lançamento de saldo inicial existente
                $lanc = Lancamento::where('user_id', Auth::id())
                    ->where('conta_id', $conta->id)
                    ->where('eh_transferencia', 0)
                    ->where('eh_saldo_inicial', 1)
                    ->first();

                if (abs($novoSaldo) <= 0.00001) {
                    // zera: remove o lançamento de saldo inicial se existir
                    if ($lanc) $lanc->delete();
                } else {
                    $payload = [
                        'user_id'           => Auth::id(),
                        'tipo'              => $novoSaldo >= 0 ? Lancamento::TIPO_RECEITA : Lancamento::TIPO_DESPESA,
                        'data'              => $lanc ? $lanc->data->format('Y-m-d') : date('Y-m-d'),
                        'categoria_id'      => null,
                        'conta_id'          => $conta->id,
                        'conta_id_destino'  => null,
                        'descricao'         => 'Saldo inicial da conta ' . ($conta->nome ?? ''),
                        'observacao'        => null,
                        'valor'             => abs($novoSaldo),
                        'eh_transferencia'  => 0,
                        'eh_saldo_inicial'  => 1,
                    ];

                    if ($lanc) {
                        $lanc->fill($payload)->save();
                    } else {
                        Lancamento::create($payload);
                    }
                }
            }

            DB::commit();
            Response::json(['ok' => true, 'ativo' => (bool)$conta->ativo]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Response::json(['status' => 'error', 'message' => 'Falha ao atualizar: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): void
    {
        $this->archive($id);
    }

    public function archive(int $id): void
    {
        $conta = Conta::forUser(Auth::id())->find($id);
        if (!$conta) {
            Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
            return;
        }
        $conta->ativo = 0;
        $conta->save();
        Response::json(['ok' => true]);
    }

    public function restore(int $id): void
    {
        $conta = Conta::forUser(Auth::id())->find($id);
        if (!$conta) {
            Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
            return;
        }
        $conta->ativo = 1;
        $conta->save();
        Response::json(['ok' => true]);
    }

    public function hardDelete(int $id): void
    {
        $uid   = Auth::id();
        $conta = Conta::forUser($uid)->find($id);

        if (!$conta) {
            Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
            return;
        }

        // aceita confirmação via query (?force=1) ou no body JSON {"force": true}
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $force   = (int)($_GET['force'] ?? 0) === 1 || !empty($payload['force']);

        // Contagens para exibir na confirmação
        $countOrig = Lancamento::where('user_id', $uid)->where('conta_id', $id)->count();
        $countDest = Lancamento::where('user_id', $uid)->where('conta_id_destino', $id)->count();
        $totalLanc = $countOrig + $countDest;

        if ($totalLanc > 0 && !$force) {
            // NÃO deleta; pede confirmação ao frontend
            Response::json([
                'status'       => 'confirm_delete',
                'message'      => 'Esta conta possui lançamentos vinculados. Deseja excluir a conta e TODOS os lançamentos vinculados?',
                'counts'       => [
                    'origem'   => $countOrig,
                    'destino'  => $countDest,
                    'total'    => $totalLanc,
                ],
                'suggestion'   => 'Reenvie com force=1 para confirmar. Caso não confirme, a conta será apenas arquivada.',
            ], 422);
            return;
        }

        // Se tem lançamentos e NÃO confirmou, apenas arquiva
        if ($totalLanc > 0 && !$force) {
            $conta->ativo = 0;
            $conta->save();
            Response::json(['ok' => true, 'archived' => true]);
            return;
        }

        // Confirmado (force=1) -> apaga lançamentos + conta (transação)
        Manager::connection()->transaction(function () use ($uid, $id, $conta, $totalLanc) {
            // Apaga todos os lançamentos onde a conta aparece (origem ou destino)
            Lancamento::where('user_id', $uid)->where('conta_id', $id)->delete();
            Lancamento::where('user_id', $uid)->where('conta_id_destino', $id)->delete();

            // Agora pode remover a conta
            $conta->delete();
        });

        Response::json([
            'ok'                    => true,
            'deleted'               => true,
            'deleted_lancamentos'   => $totalLanc,
            'message'               => 'Conta e lançamentos vinculados excluídos definitivamente.',
        ]);
    }
}
