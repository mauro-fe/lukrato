<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Illuminate\Database\Capsule\Manager as DB;

class LancamentosController
{
    // GET /api/lancamentos?month=YYYY-MM[&account_id=]
    public function index(): void
    {
        $userId = Auth::id();
        $month  = $_GET['month'] ?? date('Y-m');
        [$y, $m] = explode('-', $month);
        $from = "$y-$m-01";
        $to   = date('Y-m-t', strtotime($from));

        $acc   = $_GET['account_id'] ?? null;
        $accId = ($acc === '' || $acc === null) ? null : (int)$acc;
        $q = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->leftJoin('contas as a', 'a.id', '=', 'l.conta_id')
            ->where('l.user_id', $userId)
            ->whereBetween('l.data', [$from, $to])
            ->when($accId, fn($w) => $w->where(function ($s) use ($accId) {
                $s->where('l.conta_id', $accId)->orWhere('l.conta_id_destino', $accId);
            }))
            ->orderBy('l.data', 'desc')->orderBy('l.id', 'desc');

        $rows = $q->selectRaw('
        l.id, l.data, l.tipo, l.valor, l.descricao, l.eh_transferencia,
        COALESCE(c.nome,"—") as categoria,
        COALESCE(a.instituicao, a.nome, "—") as conta   -- <=== AQUI
    ')
            ->get()
            ->map(fn($r) => [
                'id'               => (int)$r->id,
                'data'             => (string)$r->data,
                'tipo'             => (string)$r->tipo,
                'valor'            => (float)$r->valor,
                'descricao'        => (string)($r->descricao ?? ''),
                'eh_transferencia' => (bool)$r->eh_transferencia,
                'categoria'        => (string)$r->categoria,
                'conta'            => (string)$r->conta, // agora já vem “Sicredi”, “Banco do Brasil”, etc.
            ]);

        Response::json($rows);
    }
    /**** DELETE /api/lancamentos/{id} ****/
    public function destroy(int $id): void
    {
        $userId = \Application\Lib\Auth::id();

        // Confere se é do usuário
        $row = \Illuminate\Database\Capsule\Manager::table('lancamentos')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$row) {
            \Application\Core\Response::json(['status' => 'error', 'message' => 'Lançamento não encontrado'], 404);
            return;
        }

        // Apaga
        \Illuminate\Database\Capsule\Manager::table('lancamentos')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        \Application\Core\Response::json(['ok' => true]);
    }
}
