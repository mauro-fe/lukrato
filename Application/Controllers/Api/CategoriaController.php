<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Models\Categoria;
use Application\Core\Response;
use Application\Services\LogService;
use Application\Repositories\CategoriaRepository;
use Application\Enums\CategoriaTipo;
use GUMP;
use Exception;
use ValueError;

class CategoriaController extends BaseController
{
    private CategoriaRepository $categoriaRepo;

    public function __construct()
    {
        parent::__construct();
        $this->categoriaRepo = new CategoriaRepository();
    }
    private function extractId(mixed $routeParam, array $payload): int
    {
        if (is_array($routeParam) && isset($routeParam['id'])) {
            return (int) $routeParam['id'];
        }

        if (is_string($routeParam) || is_int($routeParam)) {
            return (int) $routeParam;
        }
        if (isset($payload['id'])) {
            return (int) $payload['id'];
        }

        return 0;
    }


    public function index(): void
    {
        try {
            $this->requireAuth();

            $tipo = $this->request?->get('tipo');

            if ($tipo) {
                try {
                    $tipoEnum = CategoriaTipo::from(strtolower($tipo));
                    $categorias = $this->categoriaRepo->findByType($this->userId, $tipoEnum);
                } catch (ValueError) {
                    LogService::warning('Filtro de tipo de categoria inválido', ['tipo' => $tipo]);
                    $categorias = $this->categoriaRepo->findByUser($this->userId);
                }
            } else {
                $categorias = $this->categoriaRepo->findByUser($this->userId);
            }

            Response::success($categorias);
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
            $payload = $this->getRequestPayload();

            $nome = trim((string)($payload['nome'] ?? ''));
            $tipo = strtolower(trim((string)($payload['tipo'] ?? '')));

            $erros = [];
            if (mb_strlen($nome) < 2 || mb_strlen($nome) > 100) {
                $erros['nome'] = 'O nome deve ter entre 2 e 100 caracteres.';
            }

            try {
                CategoriaTipo::from($tipo);
            } catch (ValueError) {
                $erros['tipo'] = 'Tipo inválido. Tipos permitidos: ' . CategoriaTipo::listValuesString();
            }

            if ($erros) {
                Response::validationError($erros);
                return;
            }

            if ($this->categoriaRepo->hasDuplicate($this->userId, $nome, $tipo)) {
                Response::error('Categoria já existe com este nome e tipo.', 409);
                return;
            }

            $categoria = $this->categoriaRepo->createForUser($this->userId, [
                'nome' => $nome,
                'tipo' => $tipo,
            ]);

            Response::success($categoria->fresh(), 'Categoria criada com sucesso', 201);
        } catch (Exception $e) {
            LogService::error('Falha ao criar categoria', [
                'user_id'   => $this->userId ?? null,
                'payload'   => $payload,
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao criar categoria', 500);
        }
    }


    public function update(mixed $routeParam = null): void
    {
        $payload = [];
        try {
            $this->requireAuth();
            $payload = $this->getRequestPayload();

            $id = $this->extractId($routeParam, $payload);

            if ($id <= 0) {
                Response::validationError(['id' => 'ID inválido.']);
                return;
            }

            $categoria = Categoria::forUser($this->userId)->find($id);
            if (!$categoria) {
                Response::error('Categoria não encontrada.', 404);
                return;
            }

            $gump = new GUMP();

            $sanitizedPayload = $gump->sanitize($payload ?? []);

            $gump->validation_rules([
                'nome' => 'required|min_len,2|max_len,100',
                'tipo' => 'required|contains_list,' . CategoriaTipo::listValuesString(),
            ]);

            $gump->filter_rules([
                'nome' => 'trim',
                'tipo' => 'trim|lower_case',
            ]);

            $data = $gump->run($sanitizedPayload);

            if ($data === false) {
                LogService::warning('Validação falhou ao atualizar categoria', [
                    'user_id' => $this->userId,
                    'errors'  => $gump->get_errors_array(),
                    'payload' => $payload,
                    'categoria_id' => $id,
                ]);
                Response::validationError($gump->get_errors_array());
                return;
            }

            $dup = Categoria::forUser($this->userId)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($data['nome'])])
                ->where('tipo', $data['tipo'])
                ->where('id', '!=', $categoria->id)
                ->first();

            if ($dup) {
                LogService::info('Tentativa de atualizar categoria para um nome duplicado', [
                    'user_id' => $this->userId,
                    'categoria_id' => $categoria->id,
                    'nome' => $data['nome'],
                    'tipo' => $data['tipo'],
                ]);
                Response::error('Categoria já existe.', 409);
                return;
            }

            $categoria->nome = $data['nome'];
            $categoria->tipo = $data['tipo'];
            $categoria->save();

            LogService::info('Categoria atualizada', [
                'user_id' => $this->userId,
                'categoria_id' => $categoria->id,
            ]);

            Response::success($categoria->fresh());
        } catch (Exception $e) {
            LogService::error('Falha ao atualizar categoria', [
                'user_id' => $this->userId ?? null,
                'payload' => $payload,
                'routeParam' => $routeParam,
                'exception' => $e->getMessage(),
            ]);

            Response::error('Falha ao atualizar categoria', 500);
        }
    }


    public function delete(mixed $routeParam = null): void
    {
        try {
            $this->requireAuth();
            $payload = $this->getRequestPayload();

            $id = $this->extractId($routeParam, $payload);

            if ($id <= 0) {
                LogService::warning('Delete categoria: ID inválido', ['user_id' => $this->userId, 'routeParam' => $routeParam]);
                Response::validationError(['id' => 'ID inválido']);
                return;
            }

            LogService::info('Tentando excluir categoria', ['user_id' => $this->userId, 'categoria_id' => $id]);

            $categoria = Categoria::forUser($this->userId)->find($id);
            if (!$categoria) {
                LogService::warning('Categoria não encontrada ou sem permissão para excluir', ['user_id' => $this->userId, 'categoria_id' => $id]);
                Response::error('Categoria não encontrada.', 404);
                return;
            }

            $snapshot = [
                'id'   => $categoria->id,
                'nome' => $categoria->nome,
                'tipo' => $categoria->tipo,
            ];
            $categoria->delete();

            LogService::info('Categoria excluída', ['user_id' => $this->userId, ...$snapshot]);

            Response::success(['deleted' => true]);
        } catch (Exception $e) {
            LogService::error('Falha ao excluir categoria', [
                'user_id' => $this->userId ?? null,
                'routeParam' => $routeParam,
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao excluir categoria', 500);
        }
    }
}