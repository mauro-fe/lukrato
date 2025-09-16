<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;

class LancamentosController
{
    // GET /api/lancamentos?month=YYYY-MM[&account_id=]
    public function index(): void
    {
        $userId = Auth::id();
        $month  = $_GET['month'] ?? date('Y-m');

        // datas do mês
        [$y, $m] = explode('-', $month);
        $from = sprintf('%04d-%02d-01', (int)$y, (int)$m);
        $to   = date('Y-m-t', strtotime($from));

        // filtro por conta (opcional)
        $acc   = $_GET['account_id'] ?? null;
        $accId = ($acc === '' || $acc === null) ? null : (int)$acc;

        $q = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->leftJoin('contas as a', 'a.id', '=', 'l.conta_id')
            ->where('l.user_id', $userId)
            ->whereBetween('l.data', [$from, $to])
            ->when($accId, fn($w) => $w->where(function ($s) use ($accId) {
                $s->where('l.conta_id', $accId)
                    ->orWhere('l.conta_id_destino', $accId);
            }))
            // ✅ não listar saldo inicial
            ->whereRaw('COALESCE(l.eh_saldo_inicial, 0) = 0')
            ->orderBy('l.data', 'desc')
            ->orderBy('l.id', 'desc');

        $rows = $q->selectRaw('
            l.id, l.data, l.tipo, l.valor, l.descricao, l.eh_transferencia,
            COALESCE(c.nome,"—") as categoria,
            COALESCE(a.instituicao, a.nome, "—") as conta
        ')->get();

        $out = $rows->map(fn($r) => [
            'id'               => (int)$r->id,
            'data'             => (string)$r->data,
            'tipo'             => (string)$r->tipo,
            'valor'            => (float)$r->valor,
            'descricao'        => (string)($r->descricao ?? ''),
            'eh_transferencia' => (bool)$r->eh_transferencia,
            'categoria'        => (string)$r->categoria,
            'conta'            => (string)$r->conta,
        ]);

        Response::json($out);
    }

    // DELETE /api/lancamentos/{id}
    public function destroy(int $id): void
    {
        $uid = Auth::id();
        $t = Lancamento::where('user_id', $uid)->find($id);
        if (!$t) {
            Response::json(['status' => 'error', 'message' => 'Lançamento não encontrado'], 404);
            return;
        }

        // ⛔ protege o saldo inicial contra exclusão direta
        if ((int)($t->eh_saldo_inicial ?? 0) === 1) {
            Response::json(['status' => 'error', 'message' => 'Não é possível excluir o saldo inicial.'], 422);
            return;
        }

        $t->delete();
        Response::json(['ok' => true]);
    }
}
