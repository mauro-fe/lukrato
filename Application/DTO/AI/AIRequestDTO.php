<?php

declare(strict_types=1);

namespace Application\DTO\AI;

use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;

/**
 * Requisição padronizada para o sistema de IA.
 * Encapsula todos os dados necessários para processar qualquer tipo de interação.
 */
readonly class AIRequestDTO
{
    /**
     * @param int|null    $userId   ID do usuário (null = admin/sistema)
     * @param string      $message  Mensagem ou input do usuário
     * @param IntentType|null $intent Intent detectado (null = auto-detect)
     * @param array       $context  Contexto do sistema já coletado
     * @param AIChannel   $channel  Canal de origem da interação
     * @param array       $metadata Dados extras (categories, period, phone, etc.)
     */
    public function __construct(
        public ?int        $userId,
        public string      $message,
        public ?IntentType $intent = null,
        public array       $context = [],
        public AIChannel   $channel = AIChannel::WEB,
        public array       $metadata = [],
    ) {}

    // ─── Factories ──────────────────────────────────────────

    /**
     * Cria request de chat do usuário.
     */
    public static function chat(int $userId, string $message, array $context = [], AIChannel $channel = AIChannel::WEB): self
    {
        return new self($userId, $message, IntentType::CHAT, $context, $channel);
    }

    /**
     * Cria request de chat do admin.
     */
    public static function adminChat(string $message, array $context = []): self
    {
        return new self(null, $message, null, $context, AIChannel::ADMIN);
    }

    /**
     * Cria request de categorização.
     */
    public static function categorize(int $userId, string $description, array $categories = []): self
    {
        return new self(
            $userId,
            $description,
            IntentType::CATEGORIZE,
            metadata: ['categories' => $categories],
        );
    }

    /**
     * Cria request de análise financeira.
     */
    public static function analyze(int $userId, array $context = [], string $period = 'último mês'): self
    {
        return new self(
            $userId,
            "Análise financeira do período: {$period}",
            IntentType::ANALYZE,
            $context,
            metadata: ['period' => $period],
        );
    }

    /**
     * Cria request de extração de transação (WhatsApp).
     */
    public static function extractTransaction(int $userId, string $message, string $phone = ''): self
    {
        return new self(
            $userId,
            $message,
            IntentType::EXTRACT_TRANSACTION,
            channel: AIChannel::WHATSAPP,
            metadata: ['phone' => $phone],
        );
    }

    // ─── Helpers ────────────────────────────────────────────

    /**
     * Retorna valor de metadata ou default.
     */
    public function meta(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Verifica se o request é de admin.
     */
    public function isAdmin(): bool
    {
        return $this->channel === AIChannel::ADMIN;
    }

    /**
     * Retorna max tokens conforme o canal.
     */
    public function maxTokens(): int
    {
        return $this->channel->maxResponseTokens();
    }
}
