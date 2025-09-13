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
        $userId = Auth::id();
        $onlyActive    = (int)($_GET['only_active'] ?? 0) === 1;
        $withBalances  = (int)($_GET['with_balances'] ?? 0) === 1;
        $month         = trim($_GET['month'] ?? date('Y-m'));

        $q = Conta::forUser($userId);
        if ($onlyActive) $q->ativas();
        $rows = $q->orderBy('nome')->get();

        $extras = [];
        if ($withBalances && $rows->count()) {
            // período até o fim do mês
            $dt = \DateTime::createFromFormat('Y-m', $month);
            if (!$dt || $dt->format('Y-m') !== $month) $dt = new \DateTime(date('Y-m') . '-01');
            $ate = (new \DateTimeImmutable($dt->format('Y-m-01')))->modify('last day of this month')->format('Y-m-d');

            $ids = $rows->pluck('id')->all();

            // Receitas e despesas por conta (não-transferências)
            $rec = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id', $ids)
                ->where('eh_transferencia', 0)
                ->where('data', '<=', $ate)
                ->where('tipo', Lancamento::TIPO_RECEITA)
                ->selectRaw('conta_id, SUM(valor) as tot')->groupBy('conta_id')->pluck('tot', 'conta_id')->all();

            $des = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id', $ids)
                ->where('eh_transferencia', 0)
                ->where('data', '<=', $ate)
                ->where('tipo', Lancamento::TIPO_DESPESA)
                ->selectRaw('conta_id, SUM(valor) as tot')->groupBy('conta_id')->pluck('tot', 'conta_id')->all();

            // Transferências in/out
            $tin = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id_destino', $ids)
                ->where('eh_transferencia', 1)
                ->where('data', '<=', $ate)
                ->selectRaw('conta_id_destino as cid, SUM(valor) as tot')->groupBy('cid')->pluck('tot', 'cid')->all();

            $tout = Lancamento::where('user_id', $userId)
                ->whereIn('conta_id', $ids)
                ->where('eh_transferencia', 1)
                ->where('data', '<=', $ate)
                ->selectRaw('conta_id as cid, SUM(valor) as tot')->groupBy('cid')->pluck('tot', 'cid')->all();

            foreach ($ids as $cid) {
                $r = (float)($rec[$cid]  ?? 0);
                $d = (float)($des[$cid]  ?? 0);
                $i = (float)($tin[$cid]  ?? 0);
                $o = (float)($tout[$cid] ?? 0);
                $extras[$cid] = [
                    'saldoAtual'  => $r - $d + $i - $o,
                    'entradasMes' => $r + $i,
                    'saidasMes'   => $d + $o,
                ];
            }
        }

        Response::json($rows->map(function ($c) use ($extras) {
            $cid  = (int)$c->id;
            $base = [
                'id'           => $cid,
                'nome'         => (string)($c->nome ?? ''),
                'instituicao'  => (string)($c->instituicao ?? ''),
                'moeda'        => (string)($c->moeda ?? 'BRL'),
                'saldoInicial' => (float) ($c->saldo_inicial ?? 0), // manter para exibir no card, mas NÃO somar
                'tipo_id'      => $c->tipo_id !== null ? (int)$c->tipo_id : null,
                'ativo'        => (bool)  $c->ativo,
            ];
            if (isset($extras[$cid])) {
                $base['saldoAtual']  = (float)$extras[$cid]['saldoAtual']; // <— sem somar saldoInicial
                $base['entradasMes'] = (float)$extras[$cid]['entradasMes'];
                $base['saidasMes']   = (float)$extras[$cid]['saidasMes'];
            }
            return $base;
        }));
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

        // 👇 permite toggle de status
        if (array_key_exists('ativo', $data)) {
            $conta->ativo = (int) !!$data['ativo'];
        }

        $conta->save();
        Response::json(['ok' => true, 'ativo' => (bool)$conta->ativo]);
    }


    /** DELETE /api/accounts/{id}  (inativar) */
    public function destroy(int $id): void
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

    /** (Opcional) PATCH /api/accounts/{id}/restore  -> reativar */
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
}
