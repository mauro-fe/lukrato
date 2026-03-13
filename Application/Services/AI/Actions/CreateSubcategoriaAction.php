<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

use Application\DTO\Requests\CreateSubcategoriaDTO;
use Application\Models\Categoria;
use Application\Services\Categoria\SubcategoriaService;
use Application\Services\AI\Helpers\UserCategoryLoader;

class CreateSubcategoriaAction implements ActionInterface
{
    public function execute(int $userId, array $payload): ActionResult
    {
        $parentId = (int) ($payload['parent_id'] ?? 0);

        if ($parentId <= 0) {
            return ActionResult::fail('É necessário informar a categoria pai para criar uma subcategoria.');
        }

        $parent = Categoria::where('id', $parentId)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->whereNull('parent_id')
            ->first();

        if (!$parent) {
            return ActionResult::fail('Categoria pai não encontrada ou inválida.');
        }

        $service = new SubcategoriaService();
        $dto = CreateSubcategoriaDTO::fromRequest($userId, $parentId, [
            'nome'  => trim($payload['nome'] ?? ''),
            'icone' => $payload['icone'] ?? null,
        ]);

        $sub = $service->create($dto);

        UserCategoryLoader::invalidate($userId);

        return ActionResult::ok(
            "Subcategoria **{$payload['nome']}** criada em **{$parent->nome}**!",
            ['id' => $sub->id ?? null, 'nome' => $payload['nome'], 'parent' => $parent->nome]
        );
    }
}
