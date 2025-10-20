<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Categoria;
use Application\Models\Conta;
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
            l.observacao,
            l.categoria_id,
            l.conta_id,
            l.conta_id_destino,
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
            'observacao'       => (string)($r->observacao ?? ''),
            'categoria_id'     => isset($r->categoria_id) && (int)$r->categoria_id > 0 ? (int)$r->categoria_id : null,
            'conta_id'         => isset($r->conta_id) && (int)$r->conta_id > 0 ? (int)$r->conta_id : null,
            'conta_id_destino' => isset($r->conta_id_destino) && (int)$r->conta_id_destino > 0 ? (int)$r->conta_id_destino : null,
            'eh_transferencia' => (bool) ($r->eh_transferencia ?? 0),
            'eh_saldo_inicial' => (bool)($r->eh_saldo_inicial ?? 0),
            'categoria'        => (string)$r->categoria,
            'conta'            => (string)$r->conta,
            'conta_nome'       => (string)$r->conta_nome,
            'conta_instituicao' => (string)$r->conta_instituicao,
        ])->values()->all();

        Response::success($out);
    }

    public function update(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Não autenticado', 401);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST ?? [];
        }

        /** @var Lancamento|null $lancamento */
        $lancamento = Lancamento::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$lancamento) {
            Response::error('Lançamento não encontrado', 404);
            return;
        }

        if ((int)($lancamento->eh_saldo_inicial ?? 0) === 1) {
            Response::error('Não é possível editar o saldo inicial.', 422);
            return;
        }

        if ((int)($lancamento->eh_transferencia ?? 0) === 1) {
            Response::error('Não é possível editar uma transferência.', 422);
            return;
        }

        $errors = [];

        $tipo = strtolower(trim((string)($payload['tipo'] ?? $lancamento->tipo ?? '')));
        if (!in_array($tipo, [Lancamento::TIPO_RECEITA, Lancamento::TIPO_DESPESA], true)) {
            $errors['tipo'] = 'Tipo inválido.';
        }

        $data = (string)($payload['data'] ?? $lancamento->data ?? '');
        if (!$data || !preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $data)) {
            $errors['data'] = 'Data inválida. Use o formato YYYY-MM-DD.';
        }

        $valorRaw = $payload['valor'] ?? $lancamento->valor ?? 0;
        if (is_string($valorRaw)) {
            $s = trim($valorRaw);
            $s = str_replace(['R$', ' '], '', $s);
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
            $valorRaw = $s;
        }
        if (!is_numeric($valorRaw)) {
            $errors['valor'] = 'Valor inválido.';
        }
        $valor = (float)$valorRaw;
        if (!is_finite($valor)) {
            $errors['valor'] = 'Valor inválido.';
        }
        if ($valor < 0) {
            $valor = abs($valor);
        }
        $valor = round($valor, 2);

        $descricao = trim((string)($payload['descricao'] ?? $lancamento->descricao ?? ''));
        if (mb_strlen($descricao) > 190) {
            $descricao = mb_substr($descricao, 0, 190);
        }

        $observacao = trim((string)($payload['observacao'] ?? $lancamento->observacao ?? ''));
        if (mb_strlen($observacao) > 500) {
            $observacao = mb_substr($observacao, 0, 500);
        }

        $categoriaId = $payload['categoria_id'] ?? $payload['categoriaId'] ?? null;
        if ($categoriaId === '' || $categoriaId === null) {
            $categoriaId = null;
        } else {
            $categoriaId = (int)$categoriaId;
            if ($categoriaId > 0) {
                $categoriaExiste = Categoria::forUser($userId)->where('id', $categoriaId)->exists();
                if (!$categoriaExiste) {
                    $errors['categoria_id'] = 'Categoria inválida.';
                }
            } else {
                $categoriaId = null;
            }
        }

        $contaId = $payload['conta_id'] ?? $payload['contaId'] ?? $lancamento->conta_id;
        if ($contaId === '' || $contaId === null) {
            $errors['conta_id'] = 'Conta obrigatória.';
        } else {
            $contaId = (int)$contaId;
            $contaExiste = Conta::forUser($userId)->where('id', $contaId)->exists();
            if (!$contaExiste) {
                $errors['conta_id'] = 'Conta inválida.';
            }
        }

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        $lancamento->tipo = $tipo;
        $lancamento->data = $data;
        $lancamento->valor = $valor;
        $lancamento->descricao = $descricao;
        $lancamento->observacao = $observacao;
        $lancamento->categoria_id = $categoriaId;
        $lancamento->conta_id = $contaId;
        $lancamento->conta_id_destino = null;
        $lancamento->eh_transferencia = 0;
        $lancamento->save();

        $lancamento->refresh()->loadMissing(['categoria', 'conta']);

        Response::success([
            'id'               => (int)$lancamento->id,
            'data'             => $lancamento->data instanceof \DateTimeInterface ? $lancamento->data->format('Y-m-d') : (string)$lancamento->data,
            'tipo'             => (string)$lancamento->tipo,
            'valor'            => (float)$lancamento->valor,
            'descricao'        => (string)($lancamento->descricao ?? ''),
            'observacao'       => (string)($lancamento->observacao ?? ''),
            'categoria_id'     => $lancamento->categoria_id ? (int)$lancamento->categoria_id : null,
            'conta_id'         => $lancamento->conta_id ? (int)$lancamento->conta_id : null,
            'eh_transferencia' => (bool)$lancamento->eh_transferencia,
            'eh_saldo_inicial' => (bool)$lancamento->eh_saldo_inicial,
            'categoria'        => $lancamento->categoria->nome ?? '',
            'categoria_nome'   => $lancamento->categoria->nome ?? '',
            'conta'            => $lancamento->conta?->nome ?? $lancamento->conta?->instituicao ?? '',
            'conta_nome'       => $lancamento->conta?->nome ?? $lancamento->conta?->instituicao ?? '',
        ]);
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
