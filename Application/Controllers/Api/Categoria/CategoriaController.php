<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Categoria;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\DTO\Requests\CreateCategoriaDTO;
use Application\DTO\Requests\UpdateCategoriaDTO;
use Application\Enums\CategoriaTipo;
use Application\Models\Categoria;
use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use Application\Repositories\CategoriaRepository;
use Application\Services\AI\Helpers\UserCategoryLoader;
use Application\Services\Gamification\AchievementService;
use Application\Services\Gamification\GamificationService;
use Application\Services\Plan\PlanLimitService;
use Application\Validators\CategoriaValidator;
use ValueError;

class CategoriaController extends ApiController
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
        $this->categoriaRepo = $this->resolveOrCreate($categoriaRepo, CategoriaRepository::class);
        $this->planLimitService = $this->resolveOrCreate($planLimitService, PlanLimitService::class);
        $this->gamificationService = $this->resolveOrCreate($gamificationService, GamificationService::class);
        $this->achievementService = $this->resolveOrCreate($achievementService, AchievementService::class);
    }

    public function index(): Response
    {
        $userId = $this->userId(releaseSession: true);
        $tipoEnum = $this->resolveCategoriaTipoFilter();

        $categorias = $tipoEnum === null
            ? $this->categoriaRepo->findRootsByUser($userId)
            : $this->categoriaRepo->findRootsByType($userId, $tipoEnum);

        return Response::successResponse($categorias);
    }

    public function store(): Response
    {
        $userId = $this->userId();
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

        ['nome' => $nome, 'tipo' => $tipo, 'icone' => $icone] = $this->normalizedCategoriaFields($payload);

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
            // Gamificacao e secundaria e não bloqueia a criacao.
        }

        UserCategoryLoader::invalidate($userId);

        return Response::successResponse([
            'categoria' => $categoria->fresh(),
            'gamification' => $gamificationResult,
        ], 'Categoria criada com sucesso', 201);
    }

    public function update(mixed $routeParam = null): Response
    {
        $userId = $this->userId();
        $payload = $this->getRequestPayload();

        $id = $this->resolveId($routeParam, $payload);
        if ($id === null) {
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

        ['nome' => $nome, 'tipo' => $tipo, 'icone' => $icone] = $this->normalizedCategoriaFields($payload);

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
        $userId = $this->userId();
        $payload = $this->getRequestPayload();

        $id = $this->resolveId($routeParam, $payload);
        $force = filter_var($payload['force'] ?? $this->getQuery('force', false), FILTER_VALIDATE_BOOLEAN);

        if ($id === null) {
            return Response::validationErrorResponse(['id' => 'ID inválido']);
        }

        $categoria = Categoria::forUser($userId)->find($id);
        if (!$categoria) {
            return Response::errorResponse('Categoria não encontrada.', 404);
        }

        if ($categoria->is_seeded) {
            return Response::errorResponse('Categorias padrão não podem ser excluídas.', 403);
        }

        $subcategoriaIds = $categoria->subcategorias()->pluck('id')->map(static fn($id): int => (int) $id)->all();
        $subcategoriasCount = count($subcategoriaIds);
        $lancamentosCount = $categoria->lancamentos()->count();
        $faturaItensCount = FaturaCartaoItem::where('user_id', $userId)
            ->where(function ($query) use ($categoria, $subcategoriaIds) {
                $query->where('categoria_id', $categoria->id)
                    ->orWhere('subcategoria_id', $categoria->id);

                if ($subcategoriaIds !== []) {
                    $query->orWhereIn('subcategoria_id', $subcategoriaIds);
                }
            })
            ->count();
        $totalVinculados = $subcategoriasCount + $lancamentosCount + $faturaItensCount;

        if ($totalVinculados > 0 && !$force) {
            return Response::errorResponse('Esta categoria possui itens vinculados. Confirme para excluir.', 422, [
                'confirm_delete' => true,
                'counts' => [
                    'subcategorias' => $subcategoriasCount,
                    'lancamentos' => $lancamentosCount,
                    'itens_fatura' => $faturaItensCount,
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

        if ($faturaItensCount > 0) {
            FaturaCartaoItem::where('user_id', $userId)
                ->where(function ($query) use ($categoria, $subcategoriaIds) {
                    $query->where('categoria_id', $categoria->id)
                        ->orWhere('subcategoria_id', $categoria->id);

                    if ($subcategoriaIds !== []) {
                        $query->orWhereIn('subcategoria_id', $subcategoriaIds);
                    }
                })
                ->update(['categoria_id' => null, 'subcategoria_id' => null]);
        }

        $categoria->delete();
        UserCategoryLoader::invalidate($userId);

        return Response::successResponse([
            'deleted' => true,
            'removed_subcategorias' => $subcategoriasCount,
            'unlinked_lancamentos' => $lancamentosCount,
            'unlinked_fatura_itens' => $faturaItensCount,
        ]);
    }

    /**
     * Reordena categorias do usuário.
     * Espera { ids: [1, 3, 2, ...] }
     */
    public function reorder(): Response
    {
        $userId = $this->userId();
        $payload = $this->getRequestPayload();

        $ids = $payload['ids'] ?? null;
        if (!is_array($ids) || empty($ids)) {
            return Response::errorResponse('Lista de IDs é obrigatória.', 422);
        }

        $ids = array_map('intval', $ids);
        $this->categoriaRepo->reorderForUser($userId, $ids);

        return Response::successResponse(['reordered' => true]);
    }

    private function userId(bool $releaseSession = false): int
    {
        if ($releaseSession) {
            return $this->requireApiUserIdAndReleaseSessionOrFail();
        }

        return $this->requireApiUserIdOrFail();
    }

    private function resolveCategoriaTipoFilter(): ?CategoriaTipo
    {
        $tipo = trim($this->getStringQuery('tipo', ''));
        if ($tipo === '') {
            return null;
        }

        try {
            return CategoriaTipo::from(strtolower($tipo));
        } catch (ValueError) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveId(mixed $routeParam, array $payload): ?int
    {
        $id = is_numeric($routeParam) ? (int) $routeParam : (int) ($payload['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function optionalTrimmed(array $payload, string $key): ?string
    {
        $value = trim((string) ($payload[$key] ?? ''));

        return $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{nome:string,tipo:string,icone:?string}
     */
    private function normalizedCategoriaFields(array $payload): array
    {
        return [
            'nome' => trim((string) ($payload['nome'] ?? '')),
            'tipo' => strtolower(trim((string) ($payload['tipo'] ?? ''))),
            'icone' => $this->optionalTrimmed($payload, 'icone'),
        ];
    }
}
