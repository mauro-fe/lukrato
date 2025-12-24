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
use Application\Services\GamificationService;
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

        error_log("📂 [CATEGORIAS] Requisição recebida - User ID: {$this->userId}");

        $tipo = $this->request?->get('tipo');
        error_log("📂 [CATEGORIAS] Tipo filtro: " . var_export($tipo, true));

        if ($tipo) {
            try {
                $tipoEnum = CategoriaTipo::from(strtolower($tipo));
                $categorias = $this->categoriaRepo->findByType($this->userId, $tipoEnum);
                error_log("📂 [CATEGORIAS] Filtradas por tipo: " . count($categorias) . " categorias");
            } catch (ValueError) {
                $categorias = $this->categoriaRepo->findByUser($this->userId);
                error_log("📂 [CATEGORIAS] Tipo inválido, retornando todas: " . count($categorias));
            }
        } else {
            $categorias = $this->categoriaRepo->findByUser($this->userId);
            error_log("📂 [CATEGORIAS] Todas as categorias: " . count($categorias));
        }

        error_log("📂 [CATEGORIAS] Retornando " . count($categorias) . " categorias");
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

        // 🎮 GAMIFICAÇÃO: Adicionar pontos por criar categoria
        $gamificationResult = [];
        try {
            $gamificationService = new GamificationService();
            $pointsResult = $gamificationService->addPoints(
                $this->userId,
                \Application\Enums\GamificationAction::CREATE_CATEGORIA,
                $categoria->id,
                'categoria'
            );
            $gamificationResult = ['points' => $pointsResult];
        } catch (\Exception $e) {
            error_log("🎮 [GAMIFICATION] Erro ao processar gamificação: " . $e->getMessage());
        }

        Response::success([
            'categoria' => $categoria->fresh(),
            'gamification' => $gamificationResult,
        ], 'Categoria criada com sucesso', 201);
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
