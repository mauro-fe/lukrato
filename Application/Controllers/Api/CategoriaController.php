<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Models\Categoria;
use Application\Core\Response;
use Application\Services\LogService;
use GUMP;
use Exception;

class CategoriaController extends BaseController
{
    public function index(): void
    {
        try {
            $this->requireAuth();

            $tipo = $this->request->get('tipo');
            $query = Categoria::forUser($this->userId)->orderBy('nome', 'asc');
            if ($tipo) {
                $query->where('tipo', $tipo);
            }

            Response::success($query->get());
        } catch (Exception $e) {
            LogService::error('Falha ao listar categorias', [
                'user_id'   => $this->userId ?? null,
                'exception' => $e->getMessage(),
            ]);

            Response::error('Falha ao listar categorias', 500);
        }
    }

    public function store(): void
    {
        $payload = [];

        try {
            $this->requireAuth();

            $payload = $this->getJson() ?? [];
            if (empty($payload)) {
                $payload = $this->request->post();
            }

            $nome = trim((string)($payload['nome'] ?? ''));
            $tipo = strtolower(trim((string)($payload['tipo'] ?? '')));
            $tiposPermitidos = ['receita', 'despesa', 'transferencia', 'ambas'];

            $erros = [];
            if (mb_strlen($nome) < 2) {
                $erros['nome'] = 'Informe um nome com pelo menos 2 caracteres.';
            }
            if (!in_array($tipo, $tiposPermitidos, true)) {
                $erros['tipo'] = 'Tipo inválido.';
            }

            if ($erros) {
                Response::validationError($erros);
                return;
            }

            $duplicada = Categoria::forUser($this->userId)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)])
                ->where('tipo', $tipo)
                ->first();

            if ($duplicada) {
                Response::error('Categoria já existe.', 409);
                return;
            }

            $categoria = new Categoria();
            $categoria->user_id = $this->userId;
            $categoria->nome = mb_substr($nome, 0, 100);
            $categoria->tipo = $tipo;
            $categoria->save();

            Response::success($categoria->fresh(), 'Categoria criada com sucesso');
        } catch (Exception $e) {
            LogService::error('Falha ao criar categoria', [
                'user_id'   => $this->userId ?? null,
                'payload'   => $payload,
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao criar categoria', 500);
        }
    }

    public function update($routeParam = null): void
    {
        try {
            $this->requireAuth();

            $id = 0;
            if (is_array($routeParam) && isset($routeParam['id'])) {
                $id = (int) $routeParam['id'];
            } elseif (is_string($routeParam) || is_int($routeParam)) {
                $id = (int) $routeParam;
            }
            if ($id <= 0 && isset($_POST['id'])) {
                $id = (int) $_POST['id'];
            }
            if ($id <= 0) {
                Response::validationError(['id' => 'ID inválido']);
                return;
            }

            $categoria = Categoria::forUser($this->userId)->find($id);
            if (!$categoria) {
                Response::error('Categoria não encontrada.', 404);
                return;
            }

            $payload = $this->request->post();
            if (empty($payload)) {
                $payload = $this->getJson() ?? [];
            }

            unset($payload['_token'], $payload['csrf_token']);

            $gump = new GUMP();
            $_POST = $gump->sanitize($payload ?? []);

            $gump->validation_rules([
                'nome' => 'required|min_len,2|max_len,100',
                'tipo' => 'required|contains_list,receita;despesa;transferencia;ambas',
            ]);

            $gump->filter_rules([
                'nome' => 'trim',
                'tipo' => 'trim|lowercase',
            ]);

            if (!$gump->run($_POST)) {
                LogService::warning('Validação falhou ao atualizar categoria', [
                    'user_id' => $this->userId,
                    'errors'  => $gump->get_errors_array(),
                    'payload' => $payload,
                    'categoria_id' => $id,
                ]);
                Response::validationError($gump->get_errors_array());
                return;
            }

            $data = $gump->get_cleaned_data();

            $dup = Categoria::forUser($this->userId)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($data['nome'])])
                ->where('tipo', $data['tipo'])
                ->where('id', '!=', $categoria->id)
                ->first();

            if ($dup) {
                LogService::info('Tentativa de atualizar categoria para um nome duplicado', [
                    'user_id'      => $this->userId,
                    'categoria_id' => $categoria->id,
                    'nome'         => $data['nome'],
                    'tipo'         => $data['tipo'],
                    'duplicada_id' => $dup->id,
                ]);
                Response::error('Categoria já existe.', 409);
                return;
            }

            $categoria->nome = $data['nome'];
            $categoria->tipo = $data['tipo'];
            $categoria->save();

            LogService::info('Categoria atualizada', [
                'user_id'      => $this->userId,
                'categoria_id' => $categoria->id,
                'nome'         => $categoria->nome,
                'tipo'         => $categoria->tipo,
            ]);

            Response::success($categoria->fresh());
        } catch (Exception $e) {
            LogService::error('Falha ao atualizar categoria', [
                'user_id'    => $this->userId ?? null,
                'payload'    => $this->request->post() ?? [],
                'routeParam' => $routeParam,
                'exception'  => $e->getMessage(),
            ]);

            Response::error('Falha ao atualizar categoria', 500);
        }
    }

    public function delete($routeParam = null): void
    {
        try {
            $this->requireAuth();

            $id = 0;

            if (is_array($routeParam)) {
                if (isset($routeParam['id'])) {
                    $id = (int) $routeParam['id'];
                }
            } elseif (is_string($routeParam) || is_int($routeParam)) {
                $id = (int) $routeParam;
            }

            if ($id <= 0 && isset($_POST['id'])) {
                $id = (int) $_POST['id'];
            }

            if ($id <= 0) {
                LogService::warning('Delete categoria: ID inválido', [
                    'user_id'    => $this->userId,
                    'routeParam' => $routeParam,
                    'raw_post'   => $_POST,
                ]);
                Response::validationError(['id' => 'ID inválido']);
                return;
            }

            LogService::info('Tentando excluir categoria', [
                'user_id'      => $this->userId,
                'categoria_id' => $id,
            ]);

            $categoria = Categoria::where('user_id', $this->userId)->find($id);
            if (!$categoria) {
                LogService::warning('Categoria não encontrada ou sem permissão para excluir', [
                    'user_id'      => $this->userId,
                    'categoria_id' => $id,
                ]);
                Response::error('Categoria não encontrada.', 404);
                return;
            }

            $snapshot = [
                'id'   => $categoria->id,
                'nome' => $categoria->nome,
                'tipo' => $categoria->tipo,
            ];
            $categoria->delete();

            LogService::info('Categoria excluída', [
                'user_id' => $this->userId,
                ...$snapshot,
            ]);

            Response::success(['deleted' => true]);
        } catch (Exception $e) {
            LogService::error('Falha ao excluir categoria', [
                'user_id'    => $this->userId ?? null,
                'routeParam' => $routeParam,
                'payload'    => $_POST ?? [],
                'exception'  => $e->getMessage(),
            ]);
            Response::error('Falha ao excluir categoria', 500);
        }
    }
}
