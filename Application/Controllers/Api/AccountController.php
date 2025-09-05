<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Models\Conta;
use Application\Lib\Auth;

class AccountController
{
    public function index(): void
    {
        $userId = Auth::id();

        $rows = Conta::forUser($userId)->ativas()
            ->orderBy('nome')
            ->get();

        // ...
        Response::json($rows->map(fn($c) => [
            'id'            => (int) $c->id,
            'nome'          => (string) $c->nome,
            'instituicao'   => (string) ($c->instituicao ?? ''),
            'moeda'         => (string) ($c->moeda ?? 'BRL'),
            'saldoInicial'  => (float)  ($c->saldo_inicial ?? 0),
            'ativo'         => (bool)   $c->ativo,
            'tipo_id'       => isset($c->tipo_id) ? (int)$c->tipo_id : null,
        ]));
    }

    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $nome = trim((string)($data['nome'] ?? ''));

        if ($nome === '') {
            Response::json(['status' => 'error', 'message' => 'Nome obrigatório.'], 422);
            return;
        }

        $conta = new Conta([
            'user_id'       => Auth::id(),
            'nome'          => $nome,
            'instituicao'   => $data['instituicao'] ?? null,
            'moeda'         => $data['moeda'] ?? 'BRL',
            'tipo_id'       => isset($data['tipo_id']) ? (int)$data['tipo_id'] : null,
            'saldo_inicial' => round((float)($data['saldo_inicial'] ?? 0), 2),
            'ativo'         => 1,
        ]);
        $conta->save();

        Response::json(['ok' => true, 'id' => (int) $conta->id]);
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
        if (isset($data['saldo_inicial'])) $conta->saldo_inicial = round((float)$data['saldo_inicial'], 2);
        if (isset($data['tipo_id']))       $conta->tipo_id       = (int) $data['tipo_id'];

        $conta->save();
        Response::json(['ok' => true]);
    }

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
}
