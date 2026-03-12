<?php

declare(strict_types=1);

namespace Application\Services\AI\Telegram;

/**
 * Formata respostas dos handlers de IA para o formato Telegram.
 *
 * - Converte markdown para HTML (Telegram parse_mode=HTML)
 * - Divide mensagens longas (> 4096 chars)
 * - Limpa tags HTML não suportadas pelo Telegram
 */
class TelegramResponseFormatter
{
    private const MAX_MESSAGE_LENGTH = 4096;

    /**
     * Formata mensagem para envio no Telegram.
     * Retorna array de strings (dividido se > 4096 chars).
     *
     * @return string[]
     */
    public static function format(string $message): array
    {
        $message = self::markdownToTelegramHtml($message);
        $message = self::cleanUnsupportedHtml($message);

        return self::splitMessage($message);
    }

    /**
     * Converte markdown comum para HTML do Telegram.
     */
    private static function markdownToTelegramHtml(string $text): string
    {
        // **bold** → <b>bold</b>
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', $text);

        // *italic* → <i>italic</i> (but not inside <b> tags)
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<i>$1</i>', $text);

        // `code` → <code>code</code>
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

        return $text;
    }

    /**
     * Remove tags HTML não suportadas pelo Telegram.
     * Telegram suporta: b, strong, i, em, u, ins, s, strike, del, code, pre, a.
     */
    private static function cleanUnsupportedHtml(string $text): string
    {
        return strip_tags($text, [
            'b',
            'strong',
            'i',
            'em',
            'u',
            'ins',
            's',
            'strike',
            'del',
            'code',
            'pre',
            'a',
        ]);
    }

    /**
     * Divide mensagem em pedaços de até 4096 caracteres.
     * Tenta quebrar em linhas para não cortar no meio de frases.
     *
     * @return string[]
     */
    private static function splitMessage(string $text): array
    {
        if (mb_strlen($text) <= self::MAX_MESSAGE_LENGTH) {
            return [$text];
        }

        $chunks = [];
        $remaining = $text;

        while (mb_strlen($remaining) > self::MAX_MESSAGE_LENGTH) {
            $chunk = mb_substr($remaining, 0, self::MAX_MESSAGE_LENGTH);

            // Tentar quebrar na última newline dentro do limite
            $lastNewline = mb_strrpos($chunk, "\n");
            if ($lastNewline !== false && $lastNewline > self::MAX_MESSAGE_LENGTH * 0.5) {
                $chunk = mb_substr($remaining, 0, $lastNewline);
                $remaining = ltrim(mb_substr($remaining, $lastNewline));
            } else {
                $remaining = mb_substr($remaining, self::MAX_MESSAGE_LENGTH);
            }

            $chunks[] = $chunk;
        }

        if ($remaining !== '') {
            $chunks[] = $remaining;
        }

        return $chunks;
    }
}
