<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Models\Categoria;
use Application\Core\Response;
use GUMP;
use Exception;

class CategoriaController extends BaseController
{
    public function index(): void
    {
        try {
            $this->requireAuth();

            $tipo = $this->request->get('tipo');
            $q = Categoria::forUser($this->adminId)->orderBy('nome', 'asc');
            if ($tipo) $q->where('tipo', $tipo);

            Response::success($q->get());
        } catch (Exception $e) {
            Response::error('Falha ao listar categorias', 500);
        }
    }

    public function store(): void
    {
        try {
            $this->requireAuth();

            $gump = new GUMP();
            $_POST = $gump->sanitize($_POST);

            $gump->validation_rules([
                'nome' => 'required|min_len,2|max_len,100',
                'tipo' => 'required|contains_list,receita;despesa;transferencia',
            ]);
            $gump->filter_rules([
                'nome' => 'trim|sanitize_string',
                'tipo' => 'trim',
            ]);

            $data = $gump->run($_POST);
            if (!$data) {
                Response::validationError($gump->get_errors_array()); // 422
                return;
            }

            $dup = Categoria::forUser($this->adminId)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($data['nome'])])
                ->where('tipo', $data['tipo'])
                ->first();

            if ($dup) {
                Response::error('Categoria já existe.', 409);
                return;
            }

            $cat = Categoria::create([
                'user_id' => $this->adminId,
                'nome'    => $data['nome'],
                'tipo'    => $data['tipo'],
            ]);

            Response::success($cat);
        } catch (Exception $e) {
            Response::error('Falha ao criar categoria', 500);
        }
    }

    public function delete(array $params = []): void
    {
        try {
            $this->requireAuth();

            $id = 0;
            if (isset($params['id'])) {
                $id = (int)$params['id'];
            } elseif (isset($_POST['id'])) {
                $id = (int)$_POST['id'];
            }

            if ($id <= 0) {
                Response::validationError(['id' => 'ID inválido']);
                return;
            }

            $cat = Categoria::forUser($this->adminId)->findOrFail($id);
            $cat->delete();

            Response::success();
        } catch (\Throwable $e) {
            Response::error('Falha ao excluir', 500);
        }
    }
}