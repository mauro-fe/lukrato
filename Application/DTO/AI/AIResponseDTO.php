<?php

declare(strict_types=1);

namespace Application\DTO\AI;

use Application\Enums\AI\IntentType;

/**
 * Resposta padronizada do sistema de IA.
 * Todo Handler retorna uma instância deste DTO.
 */
readonly class AIResponseDTO
{
    /**
     * @param bool        $success    Se a operação foi bem-sucedida
     * @param string      $message    Resposta textual para o usuário
     * @param array       $data       Payload estruturado (insights, transaction, category, etc.)
     * @param IntentType|null $intent Intent que gerou a resposta
     * @param int         $tokensUsed Tokens totais consumidos (0 se não usou LLM)
     * @param bool        $cached     Se a resposta veio do cache
     * @param string      $source     Origem: 'llm', 'cache', 'rule', 'computed'
     */
    public function __construct(
        public bool        $success,
        public string      $message,
        public array       $data = [],
        public ?IntentType $intent = null,
        public int         $tokensUsed = 0,
        public bool        $cached = false,
        public string      $source = 'llm',
    ) {}

    // ─── Factories ──────────────────────────────────────────

    /**
     * Resposta de sucesso com uso de LLM.
     */
    public static function fromLLM(string $message, array $data = [], ?IntentType $intent = null, int $tokens = 0): self
    {
        return new self(true, $message, $data, $intent, $tokens, false, 'llm');
    }

    /**
     * Resposta de sucesso a partir do cache.
     */
    public static function fromCache(string $message, array $data = [], ?IntentType $intent = null): self
    {
        return new self(true, $message, $data, $intent, 0, true, 'cache');
    }

    /**
     * Resposta de sucesso baseada em regra/regex (sem LLM).
     */
    public static function fromRule(string $message, array $data = [], ?IntentType $intent = null): self
    {
        return new self(true, $message, $data, $intent, 0, false, 'rule');
    }

    /**
     * Resposta de sucesso a partir de dados computados (SQL/agregações).
     */
    public static function fromComputed(string $message, array $data = [], ?IntentType $intent = null): self
    {
        return new self(true, $message, $data, $intent, 0, false, 'computed');
    }

    /**
     * Resposta de falha.
     */
    public static function fail(string $message, ?IntentType $intent = null): self
    {
        return new self(false, $message, [], $intent);
    }

    // ─── Helpers ────────────────────────────────────────────

    /**
     * Retorna se a resposta usou LLM (chamou provedor de IA).
     */
    public function usedLLM(): bool
    {
        return $this->source === 'llm';
    }

    /**
     * Serializa para resposta da API.
     */
    public function toArray(): array
    {
        return [
            'success'     => $this->success,
            'message'     => $this->message,
            'data'        => $this->data,
            'intent'      => $this->intent?->value,
            'tokens_used' => $this->tokensUsed,
            'cached'      => $this->cached,
            'source'      => $this->source,
        ];
    }
}
