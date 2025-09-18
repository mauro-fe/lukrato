<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;

class LancamentosController
{
    // GET /api/lancamentos?month=YYYY-MM[&account_id=][&tipo=receita|despesa][&limit=500]
    public function index(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Não autenticado', 401); // helper do teu Response
            return;
        }

        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            Response::validationError(['month' => 'Formato inválido (YYYY-MM)']);
            return;
        }

        [$y, $m] = array_map('intval', explode('-', $month));
        $from = sprintf('%04d-%02d-01', $y, $m);
        $to   = date('Y-m-t', strtotime($from));

        $accId = isset($_GET['account_id']) && $_GET['account_id'] !== ''
            ? (int) $_GET['account_id'] : null;

        $tipo = $_GET['tipo'] ?? null;
        $tipo = in_array($tipo, ['receita', 'despesa'], true) ? $tipo : null;

        $limit = (int)($_GET['limit'] ?? 500);
        if ($limit <= 0)  $limit = 500;
        if ($limit > 1000) $limit = 1000;

        $q = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->leftJoin('contas as a',     'a.id', '=', 'l.conta_id')
            ->where('l.user_id', $userId)
            ->whereBetween('l.data', [$from, $to])
            ->when($accId, fn($w) => $w->where(function ($s) use ($accId) {
                $s->where('l.conta_id', $accId)
                    ->orWhere('l.conta_id_destino', $accId);
            }))
            ->when($tipo, fn($w) => $w->where('l.tipo', $tipo)) // aplica filtro do select
            ->whereRaw('COALESCE(l.eh_saldo_inicial, 0) = 0')
            ->orderBy('l.data', 'desc')
            ->orderBy('l.id',   'desc')
            ->limit($limit);

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
        ])->values()->all(); // <-- array puro

        Response::success($out); // ou: Response::jsonBody($out)->send();
    }

    // DELETE /api/lancamentos/{id}
    public function destroy(int $id): void
    {
        $uid = Auth::id();
        if (!$uid) {
            Response::error('Não autenticado', 401);
            return;
        }

        $t = Lancamento::where('user_id', $uid)
            ->where('id', $id)               // find() ignoraria o where anterior
            ->first();

        if (!$t) {
            Response::error('Lançamento não encontrado', 404);
            return;
        }
        if ((int)($t->eh_saldo_inicial ?? 0) === 1) {
            Response::error('Não é possível excluir o saldo inicial.', 422);
            return;
        }

        $t->delete();
        Response::success(['ok' => true]);
    }
}
