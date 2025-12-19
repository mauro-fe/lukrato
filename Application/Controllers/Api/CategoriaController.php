<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Models\Categoria;
use Application\Core\Response;
use Application\Repositories\CategoriaRepository;
use Application\Enums\CategoriaTipo;
use Application\DTOs\Requests\CreateCategoriaDTO;
use Application\DTOs\Requests\UpdateCategoriaDTO;
use Application\Validators\CategoriaValidator;
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

    public function index(): void
    {
        $this->requireAuth();
        $tipo = $this->request?->get('tipo');

        if ($tipo) {
            try {
                $tipoEnum = CategoriaTipo::from(strtolower($tipo));
                $categorias = $this->categoriaRepo->findByType($this->userId, $tipoEnum);
            } catch (ValueError) {
                $categorias = $this->categoriaRepo->findByUser($this->userId);
            }
        } else {
            $categorias = $this->categoriaRepo->findByUser($this->userId);
        }

        Response::success($categorias);
    }


    public function store(): void
    {
        $this->requireAuth();
        $payload = $this->getRequestPayload();

        // Validar dados
        $errors = CategoriaValidator::validateCreate($payload);
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // Sanitizar dados
        $nome = trim((string)($payload['nome'] ?? ''));
        $tipo = strtolower(trim((string)($payload['tipo'] ?? '')));

        // Verificar duplicata
        if ($this->categoriaRepo->hasDuplicate($this->userId, $nome, $tipo)) {
            Response::error('Categoria já existe com este nome e tipo.', 409);
            return;
        }

        // Criar DTO e categoria
        $dto = CreateCategoriaDTO::fromRequest($this->userId, ['nome' => $nome, 'tipo' => $tipo]);
        $categoria = $this->categoriaRepo->create($dto->toArray());

        Response::success($categoria->fresh(), 'Categoria criada com sucesso', 201);
    }


    public function update(mixed $routeParam = null): void
    {
        $this->requireAuth();
        $payload = $this->getRequestPayload();

        $id = is_numeric($routeParam) ? (int)$routeParam : (int)($payload['id'] ?? 0);

        if ($id <= 0) {
            Response::validationError(['id' => 'ID inválido.']);
            return;
        }

        $categoria = Categoria::forUser($this->userId)->find($id);
        if (!$categoria) {
            Response::error('Categoria não encontrada.', 404);
            return;
        }

        // Validar dados
        $errors = CategoriaValidator::validateUpdate($payload);
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // Sanitizar dados
        $nome = trim((string)($payload['nome'] ?? ''));
        $tipo = strtolower(trim((string)($payload['tipo'] ?? '')));

        // Verificar duplicata
        $dup = Categoria::forUser($this->userId)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)])
            ->where('tipo', $tipo)
            ->where('id', '!=', $categoria->id)
            ->first();

        if ($dup) {
            Response::error('Categoria já existe.', 409);
            return;
        }

        // Criar DTO e atualizar
        $dto = UpdateCategoriaDTO::fromRequest(['nome' => $nome, 'tipo' => $tipo]);
        $this->categoriaRepo->update($categoria->id, $dto->toArray());

        Response::success($categoria->fresh());
    }


    public function delete(mixed $routeParam = null): void
    {
        $this->requireAuth();
        $payload = $this->getRequestPayload();

        $id = is_numeric($routeParam) ? (int)$routeParam : (int)($payload['id'] ?? 0);

        if ($id <= 0) {
            Response::validationError(['id' => 'ID inválido']);
            return;
        }

        $categoria = Categoria::forUser($this->userId)->find($id);
        if (!$categoria) {
            Response::error('Categoria não encontrada.', 404);
            return;
        }

        $categoria->delete();
        Response::success(['deleted' => true]);
    }
}