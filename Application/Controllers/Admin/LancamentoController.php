<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Models\Lancamento;
use Application\Models\Categoria;

class LancamentoController extends BaseController
{
    public function index()
    {
        $userId = $_SESSION['usuario_id'] ?? $_SESSION['admin_id'] ?? null;

        $lancamentos = Lancamento::with('categoria')
            ->where('user_id', $userId)
            ->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        $this->render('lancamentos/index', compact('lancamentos'), 'admin/home/header', 'admin/footer');
    }

    public function create()
    {
        $userId = $_SESSION['usuario_id'] ?? $_SESSION['admin_id'] ?? null;

        $categorias = Categoria::where('user_id', $userId)
            ->orderBy('nome')
            ->get();

        $this->render('lancamentos/create', compact('categorias'), 'admin/home/header', 'admin/footer');
    }

    public function store()
    {
        $userId = $_SESSION['usuario_id'] ?? $_SESSION['admin_id'] ?? null;

        $tipo        = $_POST['tipo'] ?? '';
        $rawValor    = $_POST['valor'] ?? '0';
        // aceita "1.234,56" ou "1234.56"
        $valor       = (float) str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,\.]/', '', $rawValor));
        $categoriaId = $_POST['categoria_id'] ?: null;
        $descricao   = trim($_POST['descricao'] ?? '');
        $data        = $_POST['data'] ?? date('Y-m-d');

        if (!in_array($tipo, ['receita', 'despesa'], true) || $valor <= 0 || !$data) {
            $_SESSION['error'] = 'Preencha tipo, valor (>0) e data.';
            header('Location: ' . BASE_URL . 'admin/' . ($_SESSION['admin_username'] ?? 'admin') . '/lancamentos/novo');
            exit;
        }

        Lancamento::create([
            'tipo'         => $tipo,
            'valor'        => $valor,
            'categoria_id' => $categoriaId ?: null,
            'descricao'    => $descricao,
            'data'         => $data,
            'user_id'      => $userId,
        ]);

        $_SESSION['success'] = 'Lançamento adicionado.';
        header('Location: ' . BASE_URL . 'admin/' . ($_SESSION['admin_username'] ?? 'admin') . '/dashboard');
        exit;
    }
}