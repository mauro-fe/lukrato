<?php

declare(strict_types=1);

namespace Application\Services\AI\NLP;

/**
 * Normaliza texto em português brasileiro informal antes do pipeline de IA.
 *
 * Responsável por:
 * - Expandir abreviações comuns de WhatsApp/Telegram
 * - Limpar pontuação excessiva
 * - Normalizar espaços
 * - Preparar o texto para regex matching consistente
 *
 * IMPORTANTE: NÃO faz lowercasing — isso é feito por cada consumer conforme necessidade.
 * IMPORTANTE: NÃO remove acentos — preserva texto original, apenas expande abreviações.
 */
class TextNormalizer
{
    /**
     * Abreviações WhatsApp/Telegram → expansão.
     * Ordenadas por tamanho decrescente para evitar conflitos de substring.
     *
     * Regra: só expandir quando a abreviação é uma palavra isolada (\b).
     */
    private const ABBREVIATIONS = [
        // 3+ caracteres primeiro (evitar conflito com abreviações menores)
        'pfv'  => 'por favor',
        'tmj'  => 'tamo junto',
        'flw'  => 'falou',
        'vlw'  => 'valeu',
        'blz'  => 'beleza',
        'msg'  => 'mensagem',
        'obg'  => 'obrigado',
        'abs'  => 'abraços',
        'qto'  => 'quanto',
        'qdo'  => 'quando',
        'cmg'  => 'comigo',
        'ctg'  => 'contigo',
        'msm'  => 'mesmo',
        'tbm'  => 'também',
        'ngm'  => 'ninguém',
        'vdd'  => 'verdade',
        'slk'  => 'sério',
        'dps'  => 'depois',
        'hrs'  => 'horas',
        'min'  => 'minutos',
        'seg'  => 'segundos',

        // 2 caracteres
        'vc'   => 'você',
        'tb'   => 'também',
        'td'   => 'tudo',
        'pq'   => 'porque',
        'mt'   => 'muito',
        'hj'   => 'hoje',
        'hr'   => 'hora',
        'dq'   => 'daqui',
        'oq'   => 'o que',
        'nd'   => 'nada',
        'qm'   => 'quem',
        'aq'   => 'aqui',
        'gp'   => 'grupo',

        // Confirmações e negações (WhatsApp)
        'ss'   => 'sim',
        'nn'   => 'não',
    ];

    /**
     * Normaliza uma mensagem para processamento pelo pipeline de IA.
     *
     * @param string $message Mensagem original do usuário
     * @return string Mensagem normalizada (mesmo case do original, abreviações expandidas)
     */
    public static function normalize(string $message): string
    {
        $text = trim($message);

        if ($text === '') {
            return '';
        }

        // 1. Normalizar espaços múltiplos e quebras de linha
        $text = preg_replace('/\s+/', ' ', $text);

        // 2. Limpar pontuação excessiva (manter no máximo 1)
        $text = preg_replace('/!{2,}/', '!', $text);
        $text = preg_replace('/\?{2,}/', '?', $text);
        $text = preg_replace('/\.{4,}/', '...', $text);

        // 3. Expandir abreviações (case-insensitive, word boundaries)
        $text = self::expandAbbreviations($text);

        // 4. Trim final
        return trim($text);
    }

    /**
     * Expande abreviações de WhatsApp/Telegram para palavras completas.
     * Usa word boundaries para evitar substituições dentro de palavras.
     */
    private static function expandAbbreviations(string $text): string
    {
        foreach (self::ABBREVIATIONS as $abbr => $expansion) {
            // \b garante word boundary — não expande 'ss' dentro de 'assistente'
            $pattern = '/\b' . preg_quote($abbr, '/') . '\b/iu';

            $text = preg_replace_callback($pattern, function ($match) use ($expansion) {
                // Preservar case: se original é maiúsculo, expandir em maiúsculo
                if (ctype_upper($match[0])) {
                    return mb_strtoupper($expansion);
                }
                if (ctype_upper($match[0][0] ?? '')) {
                    return mb_strtoupper(mb_substr($expansion, 0, 1)) . mb_substr($expansion, 1);
                }
                return $expansion;
            }, $text);
        }

        return $text;
    }

    /**
     * Remove emojis de uma string, retornando texto limpo.
     * Útil quando emojis atrapalham regex matching.
     */
    public static function stripEmojis(string $text): string
    {
        // Remove emojis Unicode (blocos comuns)
        return preg_replace('/[\x{1F600}-\x{1F64F}' .  // Emoticons
            '\x{1F300}-\x{1F5FF}' .                     // Symbols & Pictographs
            '\x{1F680}-\x{1F6FF}' .                     // Transport & Map
            '\x{1F1E0}-\x{1F1FF}' .                     // Flags
            '\x{2600}-\x{26FF}' .                        // Misc Symbols
            '\x{2700}-\x{27BF}' .                        // Dingbats
            '\x{FE00}-\x{FE0F}' .                        // Variation Selectors
            '\x{1F900}-\x{1F9FF}' .                      // Supplemental Symbols
            '\x{200D}' .                                  // ZWJ
            '\x{20E3}' .                                  // Combining Enclosing Keycap
            '\x{FE0F}' .                                  // Variation Selector-16
            ']+/u', '', $text);
    }
}
