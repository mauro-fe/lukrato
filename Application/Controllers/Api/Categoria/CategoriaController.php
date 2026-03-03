<?php

namespace Application\Controllers\Api\Categoria;

use Application\Controllers\BaseController;
use Application\Models\Categoria;
use Application\Models\Lancamento;
use Application\Core\Response;
use Application\Repositories\CategoriaRepository;
use Application\Enums\CategoriaTipo;
use Application\DTO\Requests\CreateCategoriaDTO;
use Application\DTO\Requests\UpdateCategoriaDTO;
use Application\Validators\CategoriaValidator;
use Application\Services\Gamification\GamificationService;
use Application\Services\Plan\PlanLimitService;
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
        $this->requireAuthApi();

        // Liberar lock da sessão para permitir requisições paralelas
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $tipo = $this->request?->get('tipo');

        if ($tipo) {
            try {
                $tipoEnum = CategoriaTipo::from(strtolower($tipo));
                $categorias = $this->categoriaRepo->findRootsByType($this->userId, $tipoEnum);
            } catch (ValueError) {
                $categorias = $this->categoriaRepo->findRootsByUser($this->userId);
            }
        } else {
            $categorias = $this->categoriaRepo->findRootsByUser($this->userId);
        }

        Response::success($categorias);
    }


    public function store(): void
    {
        $this->requireAuthApi();
        $payload = $this->getRequestPayload();

        // Verificar limite do plano
        $planLimitService = new PlanLimitService();
        $limitCheck = $planLimitService->canCreateCategoria($this->userId);

        if (!$limitCheck['allowed']) {
            Response::error($limitCheck['message'], 403, [
                'limit_reached' => true,
                'upgrade_url' => $limitCheck['upgrade_url'],
                'limit_info' => [
                    'limit' => $limitCheck['limit'],
                    'used' => $limitCheck['used'],
                    'remaining' => $limitCheck['remaining']
                ]
            ]);
            return;
        }

        // Validar dados
        $errors = CategoriaValidator::validateCreate($payload);
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // Sanitizar dados
        $nome = trim((string)($payload['nome'] ?? ''));
        $tipo = strtolower(trim((string)($payload['tipo'] ?? '')));
        $icone = trim((string)($payload['icone'] ?? ''));
        $icone = $icone !== '' ? $icone : null;

        // Verificar duplicata no banco (inclui categorias globais)
        if ($this->categoriaRepo->hasDuplicate($this->userId, $nome, $tipo)) {
            Response::error('Categoria já existe com este nome e tipo.', 409);
            return;
        }

        // Criar DTO e categoria
        $dto = CreateCategoriaDTO::fromRequest($this->userId, ['nome' => $nome, 'tipo' => $tipo, 'icone' => $icone]);
        $categoria = $this->categoriaRepo->create($dto->toArray());

        // Gamificação: adicionar pontos
        $gamificationResult = [];
        try {
            $gamificationService = new GamificationService();
            $pointsResult = $gamificationService->addPoints(
                $this->userId,
                \Application\Enums\GamificationAction::CREATE_CATEGORIA,
                $categoria->id,
                'categoria'
            );

            $achievementService = new \Application\Services\Gamification\AchievementService();
            $newAchievements = $achievementService->checkAndUnlockAchievements($this->userId, 'categoria_created');

            $gamificationResult = ['points' => $pointsResult];

            if (!empty($newAchievements)) {
                $gamificationResult['achievements'] = $newAchievements;
            }
        } catch (\Exception $e) {
            // Gamificação é secundária, não bloqueia a criação
        }

        Response::success([
            'categoria' => $categoria->fresh(),
            'gamification' => $gamificationResult,
        ], 'Categoria criada com sucesso', 201);
    }


    public function update(mixed $routeParam = null): void
    {
        $this->requireAuthApi();
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
        $icone = trim((string)($payload['icone'] ?? ''));
        $icone = $icone !== '' ? $icone : null;

        // Verificar duplicata (incluindo globais)
        if ($this->categoriaRepo->hasDuplicate($this->userId, $nome, $tipo, $categoria->id)) {
            Response::error('Categoria já existe.', 409);
            return;
        }

        // Criar DTO e atualizar — filtra null para não sobrescrever ícone existente
        $dto = UpdateCategoriaDTO::fromRequest(['nome' => $nome, 'tipo' => $tipo, 'icone' => $icone]);
        $this->categoriaRepo->update($categoria->id, $dto->toArray());

        Response::success($categoria->fresh());
    }


    public function delete(mixed $routeParam = null): void
    {
        $this->requireAuthApi();
        $payload = $this->getRequestPayload();

        $id = is_numeric($routeParam) ? (int)$routeParam : (int)($payload['id'] ?? 0);
        $force = filter_var($payload['force'] ?? ($_GET['force'] ?? false), FILTER_VALIDATE_BOOLEAN);

        if ($id <= 0) {
            Response::validationError(['id' => 'ID inválido']);
            return;
        }

        $categoria = Categoria::forUser($this->userId)->find($id);
        if (!$categoria) {
            Response::error('Categoria não encontrada.', 404);
            return;
        }

        // Verificar subcategorias e lançamentos vinculados
        $subcategoriasCount = $categoria->subcategorias()->count();
        $lancamentosCount = $categoria->lancamentos()->count();
        $totalVinculados = $subcategoriasCount + $lancamentosCount;

        if ($totalVinculados > 0 && !$force) {
            Response::json([
                'status' => 'confirm_delete',
                'message' => "Esta categoria possui itens vinculados. Confirme para excluir.",
                'counts' => [
                    'subcategorias' => $subcategoriasCount,
                    'lancamentos' => $lancamentosCount,
                    'total' => $totalVinculados,
                ],
            ], 422);
            return;
        }

        // Excluir subcategorias e a categoria
        if ($subcategoriasCount > 0) {
            $categoria->subcategorias()->delete();
        }

        // Desvincular lançamentos (set categoria_id = null)
        if ($lancamentosCount > 0) {
            Lancamento::where('categoria_id', $categoria->id)
                ->update(['categoria_id' => null, 'subcategoria_id' => null]);
        }

        $categoria->delete();
        Response::success([
            'deleted' => true,
            'removed_subcategorias' => $subcategoriasCount,
            'unlinked_lancamentos' => $lancamentosCount,
        ]);
    }
}
