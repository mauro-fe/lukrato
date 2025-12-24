<?php

namespace Application\Enums;

enum AgendamentoStatus: string
{
    case PENDENTE = 'pendente';
    case CONCLUIDO = 'concluido';
    case CANCELADO = 'cancelado';

    public static function listValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function listValuesString(): string
    {
        return implode(', ', self::listValues());
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::listValues(), true);
    }
}
