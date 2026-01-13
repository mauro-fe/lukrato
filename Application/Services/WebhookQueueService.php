<?php

namespace Application\Services;

use Application\Services\LogService;
use Predis\Client as RedisClient;

/**
 * Sistema de Queue simples para processar webhooks de forma assíncrona
 * Evita timeout e garante processamento confiável
 */
class WebhookQueueService
{
    private ?RedisClient $redis = null;
    private const QUEUE_KEY = 'webhooks:queue';
    private const PROCESSING_KEY = 'webhooks:processing';
    private const FAILED_KEY = 'webhooks:failed';

    public function __construct()
    {
        try {
            if (class_exists(RedisClient::class)) {
                $this->redis = new RedisClient([
                    'scheme' => 'tcp',
                    'host'   => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                    'port'   => $_ENV['REDIS_PORT'] ?? 6379,
                ]);
                $this->redis->ping();
            }
        } catch (\Throwable $e) {
            $this->redis = null;
            if (class_exists(LogService::class)) {
                LogService::warning('Redis não disponível para queue de webhooks');
            }
        }
    }

    /**
     * Adiciona webhook na fila para processamento
     */
    public function enqueue(array $webhookData): bool
    {
        if ($this->redis) {
            // Usar Redis List
            $payload = json_encode([
                'data' => $webhookData,
                'enqueued_at' => time(),
                'attempts' => 0,
            ]);

            $this->redis->rpush(self::QUEUE_KEY, [$payload]);
            return true;
        } else {
            // Fallback: processar imediatamente (não ideal)
            return false;
        }
    }

    /**
     * Processa próximo webhook da fila
     */
    public function processNext(): ?array
    {
        if (!$this->redis) {
            return null;
        }

        // Move da fila para processando (atomicamente)
        $payload = $this->redis->brpoplpush(self::QUEUE_KEY, self::PROCESSING_KEY, 0);

        if (!$payload) {
            return null;
        }

        $data = json_decode($payload, true);

        if (!$data) {
            // Payload inválido, remover
            $this->redis->lrem(self::PROCESSING_KEY, 1, $payload);
            return null;
        }

        return [
            'payload' => $payload,
            'data' => $data,
        ];
    }

    /**
     * Marca webhook como processado com sucesso
     */
    public function markAsProcessed(string $payload): void
    {
        if ($this->redis) {
            $this->redis->lrem(self::PROCESSING_KEY, 1, $payload);
        }
    }

    /**
     * Marca webhook como falho e retorna para fila (retry)
     */
    public function markAsFailed(string $payload, int $maxAttempts = 3): void
    {
        if (!$this->redis) {
            return;
        }

        $data = json_decode($payload, true);

        if (!$data) {
            $this->redis->lrem(self::PROCESSING_KEY, 1, $payload);
            return;
        }

        $data['attempts'] = ($data['attempts'] ?? 0) + 1;
        $data['last_error_at'] = time();

        if ($data['attempts'] >= $maxAttempts) {
            // Mover para fila de falhos
            $this->redis->rpush(self::FAILED_KEY, [json_encode($data)]);
            $this->redis->lrem(self::PROCESSING_KEY, 1, $payload);

            if (class_exists(LogService::class)) {
                LogService::error('Webhook falhou após múltiplas tentativas', [
                    'attempts' => $data['attempts'],
                    'data' => $data['data'] ?? [],
                ]);
            }
        } else {
            // Retry: voltar para fila
            $newPayload = json_encode($data);
            $this->redis->rpush(self::QUEUE_KEY, [$newPayload]);
            $this->redis->lrem(self::PROCESSING_KEY, 1, $payload);
        }
    }

    /**
     * Retorna tamanho das filas
     */
    public function getQueueStats(): array
    {
        if (!$this->redis) {
            return [
                'pending' => 0,
                'processing' => 0,
                'failed' => 0,
            ];
        }

        return [
            'pending' => $this->redis->llen(self::QUEUE_KEY),
            'processing' => $this->redis->llen(self::PROCESSING_KEY),
            'failed' => $this->redis->llen(self::FAILED_KEY),
        ];
    }

    /**
     * Limpa fila de falhos
     */
    public function clearFailed(): int
    {
        if (!$this->redis) {
            return 0;
        }

        $count = $this->redis->llen(self::FAILED_KEY);
        $this->redis->del([self::FAILED_KEY]);

        return $count;
    }
}
