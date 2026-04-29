<?php

declare(strict_types=1);

namespace Application\Services\Categoria;

use Application\Container\ApplicationContainer;
use Application\Contracts\SubcategoriaServiceInterface;
use Application\DTO\Requests\CreateSubcategoriaDTO;
use Application\DTO\Requests\UpdateSubcategoriaDTO;
use Application\Models\Categoria;
use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use Application\Repositories\CategoriaRepository;
use Application\Services\Plan\PlanLimitService;
use Application\Validators\SubcategoriaValidator;

/**
 * Service responsável pelas operações de subcategorias.
 *
 * Subcategorias são categorias com parent_id preenchido (máximo 1 nível).
 * Herdam o tipo (receita/despesa) da categoria pai automaticamente.
 */
class SubcategoriaService implements SubcategoriaServiceInterface
{
    private CategoriaRepository $categoriaRepo;
    private PlanLimitService $planLimitService;

    public function __construct(
        ?CategoriaRepository $categoriaRepo = null,
        ?PlanLimitService $planLimitService = null
    ) {
        $this->categoriaRepo = ApplicationContainer::resolveOrNew($categoriaRepo, CategoriaRepository::class);
        $this->planLimitService = ApplicationContainer::resolveOrNew($planLimitService, PlanLimitService::class);
    }

    /**
     * {@inheritdoc}
     */
    public function listByCategoria(int $categoriaId, int $userId): array
    {
        // Validar que a categoria pai existe e pertence ao usuário
        $parent = $this->findParentOrFail($categoriaId, $userId);

        $subcategorias = $this->categoriaRepo->findSubcategoriasByParent($categoriaId, $userId);

        return [
            'parent' => [
                'id'   => $parent->id,
                'nome' => $parent->nome,
                'tipo' => $parent->tipo,
                'icone' => $parent->icone,
            ],
            'subcategorias' => $subcategorias->toArray(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function listAllGrouped(int $userId): array
    {
        $roots = $this->categoriaRepo->findRootsByUser($userId);

        return $roots->map(function (Categoria $cat) {
            return [
                'id'             => $cat->id,
                'nome'           => $cat->nome,
                'tipo'           => $cat->tipo,
                'icone'          => $cat->icone,
                'subcategorias'  => $cat->subcategorias->toArray(),
            ];
        })->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function create(CreateSubcategoriaDTO $dto): Categoria
    {
        // 1. Verificar limite do plano
        $this->planLimitService->assertCanCreateSubcategoria($dto->userId);

        // 2. Validar que o parent_id aponta para uma categoria raiz
        if (!SubcategoriaValidator::validateParentIsRoot($dto->parentId)) {
            throw new \DomainException('Subcategorias só podem ser criadas dentro de categorias raiz (1 nível).');
        }

        // 3. Validar que a categoria pai existe e pertence ao usuário
        $parent = $this->findParentOrFail($dto->parentId, $dto->userId);

        // 4. Verificar duplicata dentro do mesmo pai
        if ($this->categoriaRepo->hasDuplicateSubcategoria($dto->userId, $dto->parentId, $dto->nome)) {
            throw new \DomainException('Já existe uma subcategoria com este nome nesta categoria.');
        }

        // 5. Criar subcategoria herdando tipo do pai
        return $this->categoriaRepo->create([
            'nome'      => $dto->nome,
            'icone'     => $dto->icone,
            'tipo'      => $parent->tipo,
            'user_id'   => $dto->userId,
            'parent_id' => $dto->parentId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, UpdateSubcategoriaDTO $dto, int $userId): Categoria
    {
        // 1. Buscar subcategoria garantindo que pertence ao usuário
        $subcategoria = $this->categoriaRepo->findOwnSubcategoriaByIdAndUser($id, $userId);

        if (!$subcategoria) {
            throw new \DomainException('Subcategoria não encontrada.');
        }

        // 2. Verificar duplicata (excluindo a própria)
        if ($this->categoriaRepo->hasDuplicateSubcategoria($userId, $subcategoria->parent_id, $dto->nome, $id)) {
            throw new \DomainException('Já existe uma subcategoria com este nome nesta categoria.');
        }

        // 3. Atualizar
        $updateData = array_filter($dto->toArray(), fn($v) => $v !== null);

        if (!empty($updateData)) {
            $this->categoriaRepo->update($id, $updateData);
        }

        return $subcategoria->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id, int $userId): void
    {
        $subcategoria = $this->categoriaRepo->findOwnSubcategoriaByIdAndUser($id, $userId);

        if (!$subcategoria) {
            throw new \DomainException('Subcategoria não encontrada.');
        }

        // Impedir exclusão de subcategorias padrão (seeded)
        if ($subcategoria->is_seeded) {
            throw new \DomainException('Subcategorias padrão não podem ser excluídas.');
        }

        // Lançamentos que usam esta subcategoria terão subcategoria_id = NULL (SET NULL na FK)
        Lancamento::where('user_id', $userId)
            ->where('subcategoria_id', $subcategoria->id)
            ->update(['subcategoria_id' => null]);

        FaturaCartaoItem::where('user_id', $userId)
            ->where('subcategoria_id', $subcategoria->id)
            ->update(['subcategoria_id' => null]);

        $subcategoria->delete();
    }

    /**
     * Busca a categoria pai validando existência e propriedade.
     *
     * @param int $categoriaId
     * @param int $userId
     * @return Categoria
     * @throws \DomainException
     */
    private function findParentOrFail(int $categoriaId, int $userId): Categoria
    {
        $parent = $this->categoriaRepo->findByIdAndUser($categoriaId, $userId);

        if (!$parent) {
            throw new \DomainException('Categoria pai não encontrada.');
        }

        if ($parent->isSubcategoria()) {
            throw new \DomainException('Não é possível criar subcategorias dentro de outra subcategoria.');
        }

        return $parent;
    }
}
