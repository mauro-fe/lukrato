<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Models\Categoria;
use Application\Lib\Auth;

class TransactionsController
{
    /** POST /api/transactions */
    public function store(): void
    {
        $userId = Auth::id();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        // validações básicas
        $tipo  = strtolower(trim((string)($data['tipo'] ?? '')));
        if (!in_array($tipo, [Lancamento::TIPO_RECEITA, Lancamento::TIPO_DESPESA], true)) {
            Response::json(['status'=>'error','message'=>'Tipo inválido.'], 422); return;
        }

        $dt = trim((string)($data['data'] ?? ''));
        if (!$dt || !\DateTime::createFromFormat('Y-m-d', $dt)) {
            Response::json(['status'=>'error','message'=>'Data inválida (use YYYY-MM-DD).'], 422); return;
        }

        $valor = (float)($data['valor'] ?? 0);
        if ($valor <= 0) {
            Response::json(['status'=>'error','message'=>'Valor deve ser maior que zero.'], 422); return;
        }

        // conta é opcional, mas se vier precisa ser do usuário
        $contaId = isset($data['conta_id']) ? (int)$data['conta_id'] : null;
        if ($contaId) {
            $conta = Conta::forUser($userId)->find($contaId);
            if (!$conta) {
                Response::json(['status'=>'error','message'=>'Conta inválida.'], 422); return;
            }
        }

        // categoria opcional, se vier valida dono
        $categoriaId = isset($data['categoria_id']) ? (int)$data['categoria_id'] : null;
        if ($categoriaId) {
            $cat = Categoria::forUser($userId)->find($categoriaId);
            if (!$cat) $categoriaId = null; // ignora se não for do usuário
        }

        $lan = new Lancamento([
            'user_id'          => $userId,
            'tipo'             => $tipo,            // 'receita' | 'despesa'
            'data'             => $dt,
            'valor'            => $valor,           // mutator já normaliza string BR
            'categoria_id'     => $categoriaId,
            'conta_id'         => $contaId,         // << amarra no card
            'conta_id_destino' => null,
            'descricao'        => $data['descricao'] ?? null,
            'observacao'       => $data['observacao'] ?? null,
            'eh_transferencia' => 0,                // não é transferência
        ]);
        $lan->save();

        Response::json([
            'ok' => true,
            'id' => (int)$lan->id,
        ], 201);
    }
}
