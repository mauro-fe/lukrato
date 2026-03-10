<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

use Application\DTO\Requests\CreateCategoriaDTO;
use Application\Repositories\CategoriaRepository;

class CreateCategoriaAction implements ActionInterface
{
    public function execute(int $userId, array $payload): ActionResult
    {
        $repo = new CategoriaRepository();

        $nome = trim($payload['nome'] ?? '');
        $tipo = mb_strtolower(trim($payload['tipo'] ?? ''));

        if ($repo->hasDuplicate($userId, $nome, $tipo)) {
            return ActionResult::fail("Já existe uma categoria \"{$nome}\" do tipo \"{$tipo}\".");
        }

        $dto = CreateCategoriaDTO::fromRequest($userId, [
            'nome'  => $nome,
            'tipo'  => $tipo,
            'icone' => $payload['icone'] ?? null,
        ]);

        $categoria = $repo->create($dto->toArray());

        return ActionResult::ok(
            "Categoria **{$nome}** ({$tipo}) criada com sucesso!",
            ['id' => $categoria->id, 'nome' => $categoria->nome, 'tipo' => $categoria->tipo]
        );
    }
}
