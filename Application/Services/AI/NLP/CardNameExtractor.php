<?php

declare(strict_types=1);

namespace Application\Services\AI\NLP;

/**
 * Extrai nome de banco/cartão de crédito a partir de texto em linguagem natural (pt-BR).
 */
class CardNameExtractor
{
    private const BANK_PATTERNS = [
        'nubank'              => 'nubank',
        'inter'               => 'inter',
        'ita[úu]'             => 'itaú',
        'itau'                => 'itaú',
        'bradesco'            => 'bradesco',
        'santander'           => 'santander',
        'c6'                  => 'c6',
        'next'                => 'next',
        'bb'                  => 'banco do brasil',
        'banco\s+do\s+brasil' => 'banco do brasil',
        'caixa'               => 'caixa',
        'original'            => 'original',
        'neon'                => 'neon',
        'picpay'              => 'picpay',
        'mercado\s+pago'      => 'mercado pago',
        'will'                => 'will',
        'xp'                  => 'xp',
        'sicredi'             => 'sicredi',
        'sicoob'              => 'sicoob',
        'bmg'                 => 'bmg',
        'pan'                 => 'pan',
        'digio'               => 'digio',
        'pagbank'             => 'pagbank',
        'ame'                 => 'ame',
        'stone'               => 'stone',
        'safra'               => 'safra',
        'banrisul'            => 'banrisul',
        'btg'                 => 'btg',
    ];

    /**
     * Extrai nome normalizado do banco/cartão da mensagem, ou null.
     */
    public static function extract(string $message): ?string
    {
        $normalized = mb_strtolower(trim($message));

        foreach (self::BANK_PATTERNS as $pattern => $name) {
            if (preg_match('/\b' . $pattern . '\b/iu', $normalized)) {
                return $name;
            }
        }

        return null;
    }
}
