<?php

declare(strict_types=1);

namespace Application\Validators;

use Application\Enums\CategoriaTipo;

/**
 * Validador para categorias.
 */
class CategoriaValidator
{
    /**
     * Valida dados para criação de categoria.
     */
    public static function validateCreate(array $data): array
    {
        $errors = [];

        // Validar nome
        $nome = trim($data['nome'] ?? '');
        if (empty($nome)) {
            $errors['nome'] = 'O nome é obrigatório.';
        } elseif (mb_strlen($nome) > 100) {
            $errors['nome'] = 'O nome não pode ter mais de 100 caracteres.';
        }

        // Validar tipo
        $tipo = strtolower(trim($data['tipo'] ?? ''));
        if (empty($tipo)) {
            $errors['tipo'] = 'O tipo é obrigatório.';
        } else {
            try {
                CategoriaTipo::from($tipo);
            } catch (\ValueError) {
                $errors['tipo'] = 'Tipo inválido. Use "receita", "despesa" ou "ambas".';
            }
        }

        // Validar ícone (opcional)
        $icone = trim($data['icone'] ?? '');
        if (!empty($icone) && mb_strlen($icone) > 50) {
            $errors['icone'] = 'O ícone não pode ter mais de 50 caracteres.';
        }

        return $errors;
    }

    /**
     * Valida dados para atualização de categoria.
     */
    public static function validateUpdate(array $data): array
    {
        return self::validateCreate($data);
    }
}
