<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Illuminate\Database\Capsule\Manager as DB;

class TransactionsController
{

    public function index(): void
    {
        $userId = Auth::id();

        $month = trim($_GET['month'] ?? date('Y-m'));
        $dt = \DateTime::createFromFormat('Y-m', $month);
        if (!$dt || $dt->format('Y-m') !== $month) {
            $month = date('Y-m');
            $dt = new \DateTime("$month-01");
        }
        $from = $dt->format('Y-m-01');
        $to   = $dt->format('Y-m-t');

        $limit = (int)($_GET['limit'] ?? 100);
        $limit = max(1, min($limit, 1000));

        $acc   = $_GET['account_id'] ?? null;
        $accId = ($acc === '' || $acc === null) ? null : (int)$acc;

        $q = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->leftJoin('contas as co', 'co.id', '=', 'l.conta_id')
            ->leftJoin('contas as cd', 'cd.id', '=', 'l.conta_id_destino')
            ->where('l.user_id', $userId)
            ->whereBetween('l.data', [$from, $to])
            ->when($accId, function ($w) use ($accId) {
                $w->where(function ($s) use ($accId) {
                    $s->where('l.conta_id', $accId)->orWhere('l.conta_id_destino', $accId);
                });
            })
            ->orderBy('l.data', 'desc')
            ->orderBy('l.id', 'desc')
            ->limit($limit)
            ->selectRaw('
                l.id, l.data, l.tipo, l.valor, l.descricao, l.observacao, l.eh_transferencia,
                l.categoria_id, l.conta_id, l.conta_id_destino,
                COALESCE(c.nome, "—")                         as categoria_nome,
                COALESCE(co.nome, co.instituicao, "—")        as conta_origem_nome,
                COALESCE(cd.nome, cd.instituicao, "—")        as conta_destino_nome
            ');

        $rows = $q->get();

        $out = $rows->map(function ($t) {
            $isTransfer = (int)$t->eh_transferencia === 1;

            $label = $isTransfer
                ? (($t->conta_origem_nome ?: '—') . ' → ' . ($t->conta_destino_nome ?: '—'))
                : ($t->conta_origem_nome ?: '—');

            return [
                'id'               => (int)$t->id,
                'data'             => (string)$t->data,
                'tipo'             => (string)$t->tipo,
                'valor'            => (float)$t->valor,
                'descricao'        => (string)($t->descricao ?? ''),
                'observacao'       => (string)($t->observacao ?? ''),
                'eh_transferencia' => $isTransfer,

                'categoria'        => $t->categoria_id ? [
                    'id'   => (int)$t->categoria_id,
                    'nome' => (string)$t->categoria_nome
                ] : null,

                'conta'            => $t->conta_id ? [
                    'id'   => (int)$t->conta_id,
                    'nome' => (string)$t->conta_origem_nome
                ] : null,
                'conta_destino'    => $t->conta_id_destino ? [
                    'id'   => (int)$t->conta_id_destino,
                    'nome' => (string)$t->conta_destino_nome
                ] : null,

                'conta_nome'          => (string)($t->conta_origem_nome ?? ''),
                'conta_destino_nome'  => (string)($t->conta_destino_nome ?? ''),
                'conta_label'         => $label,
            ];
        });

        Response::json($out->all());
    }
}
