<?php

namespace Application\Services;

use Predis\Client as PredisClient;
use Application\Core\Exceptions\ValidationException; // Importante para lançar o erro correto

class CacheService
{
    private ?PredisClient $redis = null;
    private bool $enabled = false;

    public function __construct()
    {
        // Carrega configurações do ENV...
        $this->enabled = ($_ENV['REDIS_ENABLED'] ?? 'false') === 'true';

        if (!$this->enabled) {
            return;
        }

        $config = [
            'scheme'             => 'tcp',
            'host'               => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port'               => (int)($_ENV['REDIS_PORT'] ?? 6379),
            'timeout'            => (float)($_ENV['REDIS_TIMEOUT'] ?? 0.5),
            'read_write_timeout' => (float)($_ENV['REDIS_RW_TIMEOUT'] ?? 1.0),
            'database'           => (int)($_ENV['REDIS_DB'] ?? 0),
        ];

        $password = $_ENV['REDIS_PASSWORD'] ?? null;
        if ($password !== null && $password !== '' && strtolower($password) !== 'null') {
            $config['password'] = $password;
        }

        try {
            $this->redis = new PredisClient($config);
            $this->redis->ping();
        } catch (\Throwable $e) {
            $this->enabled = false;
            $this->redis = null;
            error_log('[CacheService] Redis indisponível: ' . $e->getMessage());
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->redis !== null;
    }

    /**
     * Verifica limite de tentativas (Rate Limiting).
     * Incrementa contador e lança exceção se exceder.
     * * @param string $key Identificador único (ex: login:ip_address)
     * @param int $limit Máximo de tentativas
     * @param int $seconds Janela de tempo em segundos
     * @throws ValidationException
     */
    public function checkRateLimit(string $key, int $limit = 5, int $seconds = 60): void
    {
        if (!$this->isEnabled()) {
            // Se o Redis estiver fora, permitimos passar (Fail Open)
            // ou bloqueamos, dependendo da política de segurança. Aqui permitimos.
            return;
        }

        $rateKey = 'rate_limit:' . $key;

        try {
            // Incrementa o contador atomicamente
            $current = $this->redis->incr($rateKey);

            // Se for a primeira tentativa (valor 1), define o tempo de expiração
            if ($current === 1) {
                $this->redis->expire($rateKey, $seconds);
            }

            if ($current > $limit) {
                // Opcional: pegar o TTL restante para avisar o usuário
                //$ttl = $this->redis->ttl($rateKey);

                // Lança a exceção que o Controller espera
                throw new ValidationException([
                    'rate_limit' => "Muitas tentativas. Aguarde {$seconds} segundos e tente novamente."
                ]);
            }
        } catch (\Throwable $e) {
            // Se for nossa ValidationException, relança ela
            if ($e instanceof ValidationException) {
                throw $e;
            }

            // Erros do Redis: Loga e segue vida para não travar o usuário
            error_log("[CacheService] checkRateLimit falhou: " . $e->getMessage());
        }
    }

    // ... (seus métodos set, get, remember, forget, flush continuam iguais abaixo) ...

    public function set(string $key, mixed $value, int $ttl = 300): bool
    {
        if (!$this->isEnabled()) return false;
        try {
            // Prefer JSON for safety. If value is not JSON-serializable, fall back
            // to a restricted unserialize-safe format.
            try {
                $json = json_encode($value, JSON_THROW_ON_ERROR);
                $payload = json_encode(['fmt' => 'json', 'v' => $json], JSON_THROW_ON_ERROR);
            } catch (\JsonException $je) {
                // Fallback: use serialize but mark as 'ser'. When reading we will
                // use unserialize with allowed_classes=false to avoid object injection.
                $payload = json_encode(['fmt' => 'ser', 'v' => serialize($value)]);
            }

            return (bool) $this->redis->setex($key, $ttl, $payload);
        } catch (\Throwable $e) {
            error_log("[CacheService] SET falhou ({$key}): " . $e->getMessage());
            return false;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isEnabled()) return $default;
        try {
            $raw = $this->redis->get($key);
            if ($raw === null) return $default;

            $decoded = json_decode((string)$raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['fmt'], $decoded['v'])) {
                if ($decoded['fmt'] === 'json') {
                    // stored as JSON string inside the wrapper
                    try {
                        $value = json_decode($decoded['v'], true, 512, JSON_THROW_ON_ERROR);
                        return $value;
                    } catch (\JsonException $je) {
                        return $default;
                    }
                }

                if ($decoded['fmt'] === 'ser') {
                    // Restricted unserialize to avoid object instantiation
                    try {
                        $un = unserialize($decoded['v'], ['allowed_classes' => false]);
                        return $un === false && $decoded['v'] !== serialize(false) ? $default : $un;
                    } catch (\Throwable $ue) {
                        return $default;
                    }
                }
            }

            return $default;
        } catch (\Throwable $e) {
            error_log("[CacheService] GET falhou ({$key}): " . $e->getMessage());
            return $default;
        }
    }

    // ... (restante da classe igual)
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->get($key, null);
        if ($cached !== null) return $cached;
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function forget(string $key): bool
    {
        if (!$this->isEnabled()) return false;
        try {
            return (int) $this->redis->del([$key]) > 0;
        } catch (\Throwable $e) {
            error_log("[CacheService] FORGET falhou ({$key}): " . $e->getMessage());
            return false;
        }
    }

    public function flush(): bool
    {
        if (!$this->isEnabled()) return false;
        try {
            return (string) $this->redis->flushdb() === 'OK';
        } catch (\Throwable $e) {
            error_log("[CacheService] FLUSH falhou: " . $e->getMessage());
            return false;
        }
    }
}
