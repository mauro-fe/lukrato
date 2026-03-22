<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Categoria;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\DTO\Requests\CreateCategoriaDTO;
use Application\DTO\Requests\UpdateCategoriaDTO;
use Application\Enums\CategoriaTipo;
use Application\Models\Categoria;
use Application\Models\Lancamento;
use Application\Repositories\CategoriaRepository;
use Application\Services\AI\Helpers\UserCategoryLoader;
use Application\Services\Gamification\AchievementService;
use Application\Services\Gamification\GamificationService;
use Application\Services\Plan\PlanLimitService;
use Application\Validators\CategoriaValidator;
use ValueError;

class CategoriaController extends BaseController
{
    private CategoriaRepository $categoriaRepo;
    private PlanLimitService $planLimitService;
    private GamificationService $gamificationService;
    private AchievementService $achievementService;

    public function __construct(
        ?CategoriaRepository $categoriaRepo = null,
        ?PlanLimitService $planLimitService = null,
        ?GamificationService $gamificationService = null,
        ?AchievementService $achievementService = null
    ) {
        parent::__construct();
        $this->categoriaRepo = $categoriaRepo ?? new CategoriaRepository();
        $this->planLimitService = $planLimitService ?? new PlanLimitService();
        $this->gamificationService = $gamificationService ?? new GamificationService();
        $this->achievementService = $achievementService ?? new AchievementService();
    }

    public function index(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $tipo = $this->request?->get('tipo');

        if ($tipo) {
            try {
                $tipoEnum = CategoriaTipo::from(strtolower((string) $tipo));
                $categorias = $this->categoriaRepo->findRootsByType($userId, $tipoEnum);
            } catch (ValueError) {
                $categorias = $this->categoriaRepo->findRootsByUser($userId);
            }
        } else {
            $categorias = $this->categoriaRepo->findRootsByUser($userId);
        }

        return Response::successResponse($categorias);
    }

    public function store(): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $payload = $this->getRequestPayload();

        $limitCheck = $this->planLimitService->canCreateCategoria($userId);
        if (!$limitCheck['allowed']) {
            return Response::errorResponse($limitCheck['message'], 403, [
                'limit_reached' => true,
                'upgrade_url' => $limitCheck['upgrade_url'],
                'limit_info' => [
                    'limit' => $limitCheck['limit'],
                    'used' => $limitCheck['used'],
                    'remaining' => $limitCheck['remaining'],
                ],
            ]);
        }

        $errors = CategoriaValidator::validateCreate($payload);
        if (!empty($errors)) {
            return Response::validationErrorResponse($errors);
        }

        $nome = trim((string) ($payload['nome'] ?? ''));
        $tipo = strtolower(trim((string) ($payload['tipo'] ?? '')));
        $icone = trim((string) ($payload['icone'] ?? ''));
        $icone = $icone !== '' ? $icone : null;

        if ($this->categoriaRepo->hasDuplicate($userId, $nome, $tipo)) {
            return Response::errorResponse('Categoria já existe com este nome e tipo.', 409);
        }

        $dto = CreateCategoriaDTO::fromRequest($userId, [
            'nome' => $nome,
            'tipo' => $tipo,
            'icone' => $icone,
        ]);
        $categoria = $this->categoriaRepo->create($dto->toArray());

        $gamificationResult = [];
        try {
            $pointsResult = $this->gamificationService->addPoints(
                $userId,
                \Application\Enums\GamificationAction::CREATE_CATEGORIA,
                $categoria->id,
                'categoria'
            );

            $newAchievements = $this->achievementService->checkAndUnlockAchievements($userId, 'categoria_created');

            $gamificationResult = ['points' => $pointsResult];
            if (!empty($newAchievements)) {
                $gamificationResult['achievements'] = $newAchievements;
            }
        } catch (\Exception) {
            // Gamificacao e secundaria e nao bloqueia a criacao.
        }

        UserCategoryLoader::invalidate($userId);

        return Response::successResponse([
            'categoria' => $categoria->fresh(),
            'gamification' => $gamificationResult,
        ], 'Categoria criada com sucesso', 201);
    }

    public function update(mixed $routeParam = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $payload = $this->getRequestPayload();

        $id = is_numeric($routeParam) ? (int) $routeParam : (int) ($payload['id'] ?? 0);

        if ($id <= 0) {
            return Response::validationErrorResponse(['id' => 'ID inválido.']);
        }

        $categoria = Categoria::forUser($userId)->find($id);
        if (!$categoria) {
            return Response::errorResponse('Categoria não encontrada.', 404);
        }

        $errors = CategoriaValidator::validateUpdate($payload);
        if (!empty($errors)) {
            return Response::validationErrorResponse($errors);
        }

        $nome = trim((string) ($payload['nome'] ?? ''));
        $tipo = strtolower(trim((string) ($payload['tipo'] ?? '')));
        $icone = trim((string) ($payload['icone'] ?? ''));
        $icone = $icone !== '' ? $icone : null;

        if ($this->categoriaRepo->hasDuplicate($userId, $nome, $tipo, $categoria->id)) {
            return Response::errorResponse('Categoria já existe.', 409);
        }

        $oldType = $categoria->tipo;
        $dto = UpdateCategoriaDTO::fromRequest([
            'nome' => $nome,
            'tipo' => $tipo,
            'icone' => $icone,
        ]);
        $this->categoriaRepo->update($categoria->id, $dto->toArray());

        if ($tipo !== $oldType) {
            Categoria::where('parent_id', $categoria->id)
                ->where('user_id', $userId)
                ->update(['tipo' => $tipo]);
        }

        UserCategoryLoader::invalidate($userId);

        return Response::successResponse($categoria->fresh());
    }

    public function delete(mixed $routeParam = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $payload = $this->getRequestPayload();

        $id = is_numeric($routeParam) ? (int) $routeParam : (int) ($payload['id'] ?? 0);
        $force = filter_var($payload['force'] ?? ($_GET['force'] ?? false), FILTER_VALIDATE_BOOLEAN);

        if ($id <= 0) {
            return Response::validationErrorResponse(['id' => 'ID inválido']);
        }

        $categoria = Categoria::forUser($userId)->find($id);
        if (!$categoria) {
            return Response::errorResponse('Categoria não encontrada.', 404);
        }

        if ($categoria->is_seeded) {
            return Response::errorResponse('Categorias padrão não podem ser excluídas.', 403);
        }

        $subcategoriasCount = $categoria->subcategorias()->count();
        $lancamentosCount = $categoria->lancamentos()->count();
        $totalVinculados = $subcategoriasCount + $lancamentosCount;

        if ($totalVinculados > 0 && !$force) {
            return Response::errorResponse('Esta categoria possui itens vinculados. Confirme para excluir.', 422, [
                'confirm_delete' => true,
                'counts' => [
                    'subcategorias' => $subcategoriasCount,
                    'lancamentos' => $lancamentosCount,
                    'total' => $totalVinculados,
                ],
            ]);
        }

        if ($subcategoriasCount > 0) {
            $categoria->subcategorias()->delete();
        }

        if ($lancamentosCount > 0) {
            Lancamento::where('categoria_id', $categoria->id)
                ->update(['categoria_id' => null, 'subcategoria_id' => null]);
        }

        $categoria->delete();
        UserCategoryLoader::invalidate($userId);

        return Response::successResponse([
            'deleted' => true,
            'removed_subcategorias' => $subcategoriasCount,
            'unlinked_lancamentos' => $lancamentosCount,
        ]);
    }

    /**
     * Reordena categorias do usuário.
     * Espera { ids: [1, 3, 2, ...] }
     */
    public function reorder(): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $payload = $this->getRequestPayload();

        $ids = $payload['ids'] ?? null;
        if (!is_array($ids) || empty($ids)) {
            return Response::errorResponse('Lista de IDs é obrigatória.', 422);
        }

        $ids = array_map('intval', $ids);
        $this->categoriaRepo->reorderForUser($userId, $ids);

        return Response::successResponse(['reordered' => true]);
    }
}
