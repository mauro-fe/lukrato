<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

use Application\Container\ApplicationContainer;
use Application\DTO\Requests\CreateCategoriaDTO;
use Application\Repositories\CategoriaRepository;
use Application\Services\AI\Helpers\UserCategoryLoader;

class CreateCategoriaAction implements ActionInterface
{
    private CategoriaRepository $repo;

    public function __construct(?CategoriaRepository $repo = null)
    {
        $this->repo = ApplicationContainer::resolveOrNew($repo, CategoriaRepository::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, array $payload): ActionResult
    {
        $nome = trim($payload['nome'] ?? '');
        $tipo = mb_strtolower(trim($payload['tipo'] ?? ''));

        if ($this->repo->hasDuplicate($userId, $nome, $tipo)) {
            return ActionResult::fail("Já existe uma categoria \"{$nome}\" do tipo \"{$tipo}\".");
        }

        $dto = CreateCategoriaDTO::fromRequest($userId, [
            'nome'  => $nome,
            'tipo'  => $tipo,
            'icone' => $payload['icone'] ?? null,
        ]);

        $categoria = $this->repo->create($dto->toArray());

        UserCategoryLoader::invalidate($userId);

        return ActionResult::ok(
            "Categoria **{$nome}** ({$tipo}) criada com sucesso!",
            ['id' => $categoria->id, 'nome' => $categoria->nome, 'tipo' => $categoria->tipo]
        );
    }
}
