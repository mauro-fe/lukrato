<?php

declare(strict_types=1);

namespace Application\Validators;

class OrcamentoValidator
{
    public static function validateSave(array $data): array
    {
        $errors = [];

        // Categoria
        $categoriaId = $data['categoria_id'] ?? null;
        if (empty($categoriaId) || !is_numeric($categoriaId)) {
            $errors['categoria_id'] = 'Selecione uma categoria.';
        }

        // Valor limite
        $valorLimite = $data['valor_limite'] ?? null;
        if ($valorLimite === null || $valorLimite === '') {
            $errors['valor_limite'] = 'O valor do orçamento é obrigatório.';
        } elseif (!is_numeric($valorLimite) || (float) $valorLimite <= 0) {
            $errors['valor_limite'] = 'O valor deve ser maior que zero.';
        }

        return $errors;
    }

    public static function validateBulk(array $data): array
    {
        $errors = [];

        if (!isset($data['orcamentos']) || !is_array($data['orcamentos'])) {
            $errors['orcamentos'] = 'Dados dos orçamentos são obrigatórios.';
            return $errors;
        }

        foreach ($data['orcamentos'] as $i => $item) {
            if (empty($item['categoria_id'])) {
                $errors["orcamentos.{$i}.categoria_id"] = "Item {$i}: categoria obrigatória.";
            }
            if (!isset($item['valor_limite']) || !is_numeric($item['valor_limite']) || (float) $item['valor_limite'] <= 0) {
                $errors["orcamentos.{$i}.valor_limite"] = "Item {$i}: valor deve ser maior que zero.";
            }
        }

        return $errors;
    }

    public static function validateMonth(array $data): array
    {
        $errors = [];

        $mes = $data['mes'] ?? null;
        $ano = $data['ano'] ?? null;

        if ($mes === null || !is_numeric($mes) || $mes < 1 || $mes > 12) {
            $errors['mes'] = 'Mês inválido.';
        }

        if ($ano === null || !is_numeric($ano) || $ano < 2020 || $ano > 2050) {
            $errors['ano'] = 'Ano inválido.';
        }

        return $errors;
    }
}
