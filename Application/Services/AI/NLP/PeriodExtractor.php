<?php

declare(strict_types=1);

namespace Application\Services\AI\NLP;

/**
 * Extrai período (mês/ano) a partir de texto em linguagem natural (pt-BR).
 * Retorna [month, year] ou null para usar o período padrão.
 */
class PeriodExtractor
{
    private const MONTHS = [
        'janeiro' => 1,
        'jan' => 1,
        'fevereiro' => 2,
        'fev' => 2,
        'março' => 3,
        'marco' => 3,
        'mar' => 3,
        'abril' => 4,
        'abr' => 4,
        'maio' => 5,
        'mai' => 5,
        'junho' => 6,
        'jun' => 6,
        'julho' => 7,
        'jul' => 7,
        'agosto' => 8,
        'ago' => 8,
        'setembro' => 9,
        'set' => 9,
        'outubro' => 10,
        'out' => 10,
        'novembro' => 11,
        'nov' => 11,
        'dezembro' => 12,
        'dez' => 12,
    ];

    /**
     * Extrai [month, year] da mensagem ou retorna null.
     */
    public static function extract(string $message): ?array
    {
        $message = mb_strtolower(trim($message));

        // "mês passado" / "mes anterior"
        if (preg_match('/m[eê]s\s+(passado|anterior)/iu', $message)) {
            $prev = now()->subMonth();
            return [(int) $prev->month, (int) $prev->year];
        }

        // "ano passado" → null (requer lógica mais complexa)
        if (preg_match('/ano\s+(passado|anterior)/iu', $message)) {
            return null;
        }

        // "último trimestre" / "trimestre passado" → null
        if (preg_match('/(último|ultimo)\s+trimestre|trimestre\s+passado/iu', $message)) {
            return null;
        }

        // Nome de mês explícito (ex: "janeiro", "em março", "março 2025")
        foreach (self::MONTHS as $name => $num) {
            if (preg_match('/\b' . preg_quote($name, '/') . '\b(?:\s+(?:de\s+)?(\d{4}))?/iu', $message, $m)) {
                $year = !empty($m[1]) ? (int) $m[1] : (int) now()->year;
                return [$num, $year];
            }
        }

        return null;
    }
}
