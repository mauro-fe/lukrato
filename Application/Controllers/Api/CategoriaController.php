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
            $q = Categoria::forUser($this->adminId)->orderBy('nome', 'asc');
            if ($tipo) $q->where('tipo', $tipo);

            Response::success($q->get());
        } catch (Exception $e) {
            // Log de erro
            LogService::error('Falha ao listar categorias', [
                'user_id' => $this->adminId ?? null,
                'exception' => $e->getMessage(),
            ]);

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
                // Log de validação
                LogService::warning('Validação falhou ao criar categoria', [
                    'user_id' => $this->adminId,
                    'errors'  => $gump->get_errors_array(),
                    'payload' => $_POST,
                ]);

                Response::validationError($gump->get_errors_array()); // 422
                return;
            }

            $dup = Categoria::forUser($this->adminId)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($data['nome'])])
                ->where('tipo', $data['tipo'])
                ->first();

            if ($dup) {
                LogService::info('Tentativa de criar categoria duplicada', [
                    'user_id' => $this->adminId,
                    'nome'    => $data['nome'],
                    'tipo'    => $data['tipo'],
                    'dup_id'  => $dup->id,
                ]);

                Response::error('Categoria já existe.', 409);
                return;
            }

            $cat = Categoria::create([
                'user_id' => $this->adminId,
                'nome'    => $data['nome'],
                'tipo'    => $data['tipo'],
            ]);

            // Log de criação OK
            LogService::info('Categoria criada', [
                'user_id'      => $this->adminId,
                'categoria_id' => $cat->id,
                'nome'         => $cat->nome,
                'tipo'         => $cat->tipo,
                'ip'           => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);

            Response::success($cat);
        } catch (Exception $e) {
            // Log de exceção
            LogService::error('Falha ao criar categoria', [
                'user_id'   => $this->adminId ?? null,
                'payload'   => $_POST ?? [],
                'exception' => $e->getMessage(),
            ]);

            Response::error('Falha ao criar categoria', 500);
        }
    }

    public function delete($routeParam = null): void
    {
        try {
            $this->requireAuth();

            // aceita tanto a forma antiga (array) quanto string/int vindo do Router
            $id = 0;

            if (is_array($routeParam)) {
                // padrão antigo: delete(array $params)
                if (isset($routeParam['id'])) {
                    $id = (int) $routeParam['id'];
                }
            } elseif (is_string($routeParam) || is_int($routeParam)) {
                // padrão atual do Router: delete('13')
                $id = (int) $routeParam;
            }

            // fallback: id via POST (ex.: quando não veio id na rota)
            if ($id <= 0 && isset($_POST['id'])) {
                $id = (int) $_POST['id'];
            }

            if ($id <= 0) {
                LogService::warning('Delete categoria: ID inválido', [
                    'user_id'    => $this->adminId,
                    'routeParam' => $routeParam,
                    'raw_post'   => $_POST,
                ]);
                Response::validationError(['id' => 'ID inválido']);
                return;
            }

            LogService::info('Tentando excluir categoria', [
                'user_id'     => $this->adminId,
                'categoria_id' => $id,
            ]);

            $cat = Categoria::where('user_id', $this->adminId)->find($id);
            if (!$cat) {
                LogService::warning('Categoria não encontrada/sem permissão para excluir', [
                    'user_id'     => $this->adminId,
                    'categoria_id' => $id,
                ]);
                Response::error('Categoria não encontrada.', 404);
                return;
            }

            // se houver trava de integridade, trate aqui (409)
            // if ($cat->lancamentos()->exists()) {
            //     LogService::info('Bloqueio de exclusão: possui lançamentos vinculados', [...]);
            //     Response::error('Não é possível excluir: há lançamentos vinculados.', 409);
            //     return;
            // }

            $snapshot = ['id' => $cat->id, 'nome' => $cat->nome, 'tipo' => $cat->tipo];
            $cat->delete();

            LogService::info('Categoria excluída', [
                'user_id' => $this->adminId,
                ...$snapshot,
            ]);

            Response::success(['deleted' => true]);
        } catch (\Throwable $e) {
            LogService::error('Falha ao excluir categoria', [
                'user_id'   => $this->adminId ?? null,
                'routeParam' => $routeParam,
                'payload'   => $_POST ?? [],
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao excluir', 500);
        }
    }
}
