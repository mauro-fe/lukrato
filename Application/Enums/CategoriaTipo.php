<?php

declare(strict_types=1);

namespace Application\Enums;

enum CategoriaTipo: string
{
    case RECEITA = 'receita';
    case DESPESA = 'despesa';
    case TRANSFERENCIA = 'transferencia';
    case AMBAS = 'ambas';

    /**
     * Retorna todos os valores possíveis do enum.
     */
    public static function listValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Retorna valores em formato string separado por ponto e vírgula.
     */
    public static function listValuesString(): string
    {
        return implode(';', self::listValues());
    }

    /**
     * Verifica se um valor é válido.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::listValues(), true);
    }
}
