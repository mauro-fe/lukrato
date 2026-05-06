<?php

declare(strict_types=1);

namespace Application\Validators;

use Application\Models\Categoria;
use Application\Repositories\CategoriaRepository;

/**
 * Validador para subcategorias.
 */
class SubcategoriaValidator
{
    /**
     * Valida dados para criação de subcategoria.
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validateCreate(array $data): array
    {
        $errors = [];

        // Validar nome
        $nome = trim((string) ($data['nome'] ?? ''));
        if (empty($nome)) {
            $errors['nome'] = 'O nome é obrigatório.';
        } elseif (mb_strlen($nome) > 100) {
            $errors['nome'] = 'O nome não pode ter mais de 100 caracteres.';
        }

        // Validar ícone (opcional)
        $icone = trim((string) ($data['icone'] ?? ''));
        if (!empty($icone) && mb_strlen($icone) > 50) {
            $errors['icone'] = 'O ícone não pode ter mais de 50 caracteres.';
        }

        return $errors;
    }

    /**
     * Valida dados para atualização de subcategoria.
     * Apenas valida campos presentes no array (suporta atualizações parciais).
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validateUpdate(array $data): array
    {
        $errors = [];

        if (array_key_exists('nome', $data)) {
            $nome = trim((string) ($data['nome'] ?? ''));
            if (empty($nome)) {
                $errors['nome'] = 'O nome é obrigatório.';
            } elseif (mb_strlen($nome) > 100) {
                $errors['nome'] = 'O nome não pode ter mais de 100 caracteres.';
            }
        }

        if (array_key_exists('icone', $data)) {
            $icone = trim((string) ($data['icone'] ?? ''));
            if (!empty($icone) && mb_strlen($icone) > 50) {
                $errors['icone'] = 'O ícone não pode ter mais de 50 caracteres.';
            }
        }

        return $errors;
    }

    /**
     * Valida que o parent_id aponta para uma categoria raiz (não subcategoria).
     * Impede cascata de N níveis — máximo 1 nível de profundidade.
     */
    public static function validateParentIsRoot(int $parentId): bool
    {
        $parent = Categoria::find($parentId);

        if (!$parent) {
            return false;
        }

        return $parent->isRoot();
    }

    /**
     * Valida que a subcategoria pertence à categoria pai informada.
     */
    public static function validateBelongsToParent(int $subcategoriaId, int $categoriaId): bool
    {
        $subcategoria = Categoria::find($subcategoriaId);

        if (!$subcategoria || !$subcategoria->isSubcategoria()) {
            return false;
        }

        return (int) $subcategoria->parent_id === $categoriaId;
    }
}
