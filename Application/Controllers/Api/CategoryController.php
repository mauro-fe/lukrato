<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Models\Categoria;
use Application\Core\Response;
use GUMP;
use Exception;

class CategoryController extends BaseController
{
    /** GET /api/categorias?tipo=receita|despesa|transferencia */
    public function index(): void
    {
        try {
            $this->requireAuth();

            $tipo = $this->request->get('tipo');
            $q = Categoria::forUser($this->adminId)->orderBy('nome', 'asc');
            if ($tipo) $q->where('tipo', $tipo);

            Response::success($q->get()); // 200 + payload padrão
        } catch (Exception $e) {
            Response::error('Falha ao listar categorias', 500);
        }
    }

    /** POST /api/categorias  (nome, tipo, cor) */
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

            // evita duplicidade (case-insensitive) por nome+tipo do mesmo usuário
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

            Response::success($cat); // 200
        } catch (Exception $e) {
            Response::error('Falha ao criar categoria', 500);
        }
    }

    /** POST /api/categorias/{id}/delete */
    // Application/Controllers/Api/CategoryController.php
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
