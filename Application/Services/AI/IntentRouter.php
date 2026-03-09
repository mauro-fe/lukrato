<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\Enums\AI\IntentType;
use Application\Services\Infrastructure\CacheService;

/**
 * Detecta a intenção do usuário a partir da mensagem.
 * Abordagem híbrida: regex/keywords primeiro (0 tokens), LLM como fallback.
 */
class IntentRouter
{
    /**
     * Mapeamento de padrões regex para intents.
     * Ordem importa: o primeiro match vence.
     *
     * @var array<string, IntentType>
     */
    private const PATTERN_MAP = [
        // Extração de transação (linguagem de registro)
        'gastei|paguei|pago|recebi|ganhei|comprei|vendi|transferi|depositei'
            => IntentType::EXTRACT_TRANSACTION,

        // Padrão: valor + descrição ou descrição + valor (WhatsApp style)
        '^\s*\d+[\.,]?\d*\s+\w'
            => IntentType::EXTRACT_TRANSACTION,

        '^\s*\w+\s+\d+[\.,]?\d*\s*$'
            => IntentType::EXTRACT_TRANSACTION,

        // Categorização
        'categori[za]|classific|qual.*categoria|suger.*categoria|subcategoria'
            => IntentType::CATEGORIZE,

        // Consultas rápidas (respondíveis direto com dados)
        'quanto\s+(gastei|gasto|recebi|tenho|sobrou|falta)|total\s+(de\s+)?(gasto|receita|despesa)|saldo\s+(atual|total|das?\s+conta)|quantos?\s+(lançamento|transaç|registro|conta|cartão|cartao)|qual\s+(meu|minha|o)\s+(saldo|gasto|receita)'
            => IntentType::QUICK_QUERY,

        // Análise financeira
        'analis[ea]|insight|padrão\s+de\s+gasto|economizar|reduzir\s+gasto|compar[ea].*mês|evolução|tendência|sugest[ãa]o.*financ|dica.*financ|como\s+posso\s+(economizar|juntar|guardar|poupar)'
            => IntentType::ANALYZE,

        // Consultas rápidas do admin
        'quantos\s+usuário|quantos\s+usuario|mrr|receita\s+recorrente|erro.*crítico|erro.*critico|assinante|cadastro.*semana|crescimento.*usuário'
            => IntentType::QUICK_QUERY,
    ];

    /**
     * Padrões que indicam intent forçado pelo canal.
     * WhatsApp com mensagens curtas quase sempre é extração de transação.
     */
    private const WHATSAPP_TRANSACTION_PATTERN =
        '/^\s*(?:(?:gastei|paguei|recebi|comprei|ganhei)\s+)?(?:r\$\s*)?\d+[\.,]?\d*\s+/iu';

    private CacheService $cache;

    public function __construct()
    {
        $this->cache = new CacheService();
    }

    /**
     * Detecta o intent a partir da mensagem do usuário.
     * Retorna null se não consseguir determinar (fallback para CHAT).
     */
    public function detect(string $message, bool $isWhatsApp = false): IntentType
    {
        $normalized = mb_strtolower(trim($message));

        // WhatsApp: mensagens curtas com número são quase sempre transações
        if ($isWhatsApp && mb_strlen($normalized) <= 100) {
            if (preg_match(self::WHATSAPP_TRANSACTION_PATTERN, $normalized)) {
                return IntentType::EXTRACT_TRANSACTION;
            }
        }

        // Verificar cache de intent para mensagens similares
        $cacheKey = 'ai:intent:' . md5($normalized);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $intent = IntentType::tryFrom($cached);
            if ($intent !== null) {
                return $intent;
            }
        }

        // Pass 1: regex/keyword matching (0 tokens)
        $detected = $this->matchByPattern($normalized);

        if ($detected !== null) {
            $this->cache->set($cacheKey, $detected->value, 86400);
            return $detected;
        }

        // Default: conversa geral
        return IntentType::CHAT;
    }

    /**
     * Tenta detectar intent por regex.
     */
    private function matchByPattern(string $message): ?IntentType
    {
        foreach (self::PATTERN_MAP as $pattern => $intent) {
            if (preg_match('/' . $pattern . '/iu', $message)) {
                return $intent;
            }
        }

        return null;
    }

    /**
     * Retorna todos os padrões registrados (para testes/debug).
     *
     * @return array<string, IntentType>
     */
    public function getPatterns(): array
    {
        return self::PATTERN_MAP;
    }
}
