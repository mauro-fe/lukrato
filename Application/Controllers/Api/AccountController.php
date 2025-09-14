<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Models\Conta;
use Application\Lib\Auth;
use Application\Models\Lancamento;

class AccountController
{
    /** GET /api/accounts[?only_active=1] */
    public function index(): void
    {
        $userId       = Auth::id();

        // ?archived=1 -> só arquivadas
        // ?only_active=1 -> só ativas (padrão = 1, exceto se archived=1)
        $archived     = (int)($_GET['archived'] ?? 0) === 1;
        $onlyActive   = (int)($_GET['only_active'] ?? ($archived ? 0 : 1)) === 1;

        // ?with_balances=1&month=YYYY-MM  (opcional)
        $withBalances = (int)($_GET['with_balances'] ?? 0) === 1;
        $month        = trim((string)($_GET['month'] ?? date('Y-m')));

        // ---- filtro base
        $q = Conta::forUser($userId);
        if ($archived) {
            $q->arquivadas();
        } elseif ($onlyActive) {
            $q->ativas();
        }

        $rows = $q->orderBy('nome')->get();

        // ---- extras: saldos/movimentos até o fim do mês informado
        $extras = [];
        if ($withBalances && $rows->count()) {
            $dt = \DateTime::createFromFormat('Y-m', $month);
            if (!$dt || $dt->format('Y-m') !== $month) {
                $dt = new \DateTime(date('Y-m') . '-01');
            }
            $ate = (new \DateTimeImmutable($dt->format('Y-m-01')))
                ->modify('last day of this month')
                ->format('Y-m-d');

            $ids      = $rows->pluck('id')->all();
            $initById = $rows->pluck('saldo_inicial', 'id')
                ->map(fn($v) => (float)$v)->all();

            // receitas (não-transferência)
            $rec = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id', $ids)
                ->where('eh_transferencia', 0)
                ->where('data', '<=', $ate)
                ->where('tipo', Lancamento::TIPO_RECEITA)
                ->selectRaw('conta_id, SUM(valor) as tot')
                ->groupBy('conta_id')->pluck('tot', 'conta_id')->all();

            // despesas (não-transferência)
            $des = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id', $ids)
                ->where('eh_transferencia', 0)
                ->where('data', '<=', $ate)
                ->where('tipo', Lancamento::TIPO_DESPESA)
                ->selectRaw('conta_id, SUM(valor) as tot')
                ->groupBy('conta_id')->pluck('tot', 'conta_id')->all();

            // transferências recebidas
            $tin = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id_destino', $ids)
                ->where('eh_transferencia', 1)
                ->where('data', '<=', $ate)
                ->selectRaw('conta_id_destino as cid, SUM(valor) as tot')
                ->groupBy('cid')->pluck('tot', 'cid')->all();

            // transferências enviadas
            $tout = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id', $ids)
                ->where('eh_transferencia', 1)
                ->where('data', '<=', $ate)
                ->selectRaw('conta_id as cid, SUM(valor) as tot')
                ->groupBy('cid')->pluck('tot', 'cid')->all();

            foreach ($ids as $cid) {
                $r  = (float)($rec[$cid]   ?? 0);
                $d  = (float)($des[$cid]   ?? 0);
                $i  = (float)($tin[$cid]   ?? 0);
                $o  = (float)($tout[$cid]  ?? 0);
                $si = (float)($initById[$cid] ?? 0);

                $extras[$cid] = [
                    'saldoAtual'  => $si + $r - $d + $i - $o,
                    'entradasMes' => $r + $i,
                    'saidasMes'   => $d + $o,
                ];
            }
        }

        // ---- saída
        Response::json($rows->map(function ($c) use ($extras) {
            $x = $extras[$c->id] ?? null;
            return [
                'id'            => (int)$c->id,
                'nome'          => (string)$c->nome,
                'instituicao'   => (string)($c->instituicao ?? ''),
                'moeda'         => (string)($c->moeda ?? 'BRL'),
                'saldoInicial'  => (float)($c->saldo_inicial ?? 0),
                'saldoAtual'    => $x ? (float)$x['saldoAtual']  : null,
                'entradasMes'   => $x ? (float)$x['entradasMes'] : 0.0,
                'saidasMes'     => $x ? (float)$x['saidasMes']   : 0.0,
                'ativo'         => (bool)$c->ativo,
                'arquivada'     => !(bool)$c->ativo,
            ];
        })->all());
    }


    /** POST /api/accounts */
    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $nome = trim((string)($data['nome'] ?? ''));

        if ($nome === '') {
            Response::json(['status' => 'error', 'message' => 'Nome obrigatório.'], 422);
            return;
        }

        // saneia moeda e tipo
        $moeda = strtoupper(trim((string)($data['moeda'] ?? 'BRL')));
        $allowedMoedas = ['BRL', 'USD', 'EUR'];
        if (!in_array($moeda, $allowedMoedas, true)) $moeda = 'BRL';

        $tipoId = isset($data['tipo_id']) && $data['tipo_id'] !== '' ? (int)$data['tipo_id'] : null;

        $conta = new Conta([
            'user_id'       => Auth::id(),
            'nome'          => $nome,
            'instituicao'   => $data['instituicao'] ?? null,
            'moeda'         => $moeda,
            'saldo_inicial' => round((float)($data['saldo_inicial'] ?? 0), 2),
            'tipo_id'       => $tipoId,  // novo
            'ativo'         => 1,
        ]);
        $conta->save();

        Response::json(['ok' => true, 'id' => (int) $conta->id]);
    }

    // ... index(), store() ficam como estão

    /** PUT /api/accounts/{id} */
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
        if (array_key_exists('saldo_inicial', $data)) {
            $conta->saldo_inicial = round((float)$data['saldo_inicial'], 2);
        }
        if (array_key_exists('ativo', $data)) {
            $conta->ativo = (int) !!$data['ativo'];
        }

        $conta->save();
        Response::json(['ok' => true, 'ativo' => (bool)$conta->ativo]);
    }

    /** DELETE /api/accounts/{id}  -> alias de arquivar (compat.) */
    public function destroy(int $id): void
    {
        $this->archive($id);
    }

    /** PATCH /api/accounts/{id}/archive */
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

    /** PATCH /api/accounts/{id}/restore */
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

    // Application\Controllers\Api\AccountController.php

    /** POST /api/accounts/{id}/delete  (exclusão definitiva) */
    public function hardDelete(int $id): void
    {
        $uid   = Auth::id();
        $conta = Conta::forUser($uid)->find($id);

        if (!$conta) {
            Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
            return;
        }

        // Verifica vínculos em lançamentos (saída, entrada via transferência)
        $temLanc = Lancamento::where('user_id', $uid)
            ->where(function ($q) use ($id) {
                $q->where('conta_id', $id)
                    ->orWhere('conta_id_destino', $id);
            })
            ->exists();

        if ($temLanc) {
            Response::json([
                'status'  => 'error',
                'message' => 'Não é possível excluir: existem lançamentos vinculados a esta conta.'
            ], 422);
            return;
        }

        // Exclusão física
        $conta->delete();

        Response::json(['ok' => true]);
    }
}
