<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;

class LancamentosController
{
    public function index(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Não autenticado', 401);
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

        $categoriaParam = $_GET['categoria_id'] ?? '';
        $categoriaId = null;
        $categoriaIsNull = false;
        if ($categoriaParam !== '') {
            if (in_array($categoriaParam, ['none', 'null'], true)) {
                $categoriaIsNull = true;
            } elseif (is_numeric($categoriaParam)) {
                $categoriaParam = (int) $categoriaParam;
                if ($categoriaParam === 0) {
                    $categoriaIsNull = true;
                } elseif ($categoriaParam > 0) {
                    $categoriaId = $categoriaParam;
                }
            }
        }

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
            ->when($categoriaIsNull, fn($w) => $w->whereNull('l.categoria_id'))
            ->when($categoriaId, fn($w) => $w->where('l.categoria_id', $categoriaId))
            ->when($tipo, fn($w) => $w->where('l.tipo', $tipo))
            ->orderBy('l.data', 'desc')
            ->orderBy('l.id', 'desc')
            ->limit($limit);

        $rows = $q->selectRaw('
            l.id,
            l.data,
            l.tipo,
            l.valor,
            l.descricao,
            l.eh_transferencia,
            COALESCE(c.nome, "") as categoria,
            COALESCE(a.nome, a.instituicao, "") as conta,
            COALESCE(a.nome, "") as conta_nome,
            COALESCE(a.instituicao, "") as conta_instituicao,
            COALESCE(l.eh_saldo_inicial, 0) as eh_saldo_inicial
        ')->get();

        $out = $rows->map(fn($r) => [
            'id'               => (int)$r->id,
            'data'             => (string)$r->data,
            'tipo'             => (string)$r->tipo,
            'valor'            => (float)$r->valor,
            'descricao'        => (string)($r->descricao ?? ''),
            'eh_transferencia' => (bool) ($r->eh_transferencia ?? 0),
            'eh_saldo_inicial' => (bool)($r->eh_saldo_inicial ?? 0),
            'categoria'        => (string)$r->categoria,
            'conta'            => (string)$r->conta,
            'conta_nome'       => (string)$r->conta_nome,
            'conta_instituicao'=> (string)$r->conta_instituicao,
        ])->values()->all();

        Response::success($out);
    }

    public function destroy(int $id): void
    {
        $uid = Auth::id();
        if (!$uid) {
            Response::error('Não autenticado', 401);
            return;
        }

        $t = Lancamento::where('user_id', $uid)
            ->where('id', $id)
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
