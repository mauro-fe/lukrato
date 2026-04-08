<?php

declare(strict_types=1);

namespace Application\Services\Infrastructure;

use Application\Container\ApplicationContainer;
use Application\Core\Exceptions\ValidationException;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;

/**
 * Serviço de verificação Cloudflare Turnstile (CAPTCHA progressivo).
 *
 * Fluxo:
 *  1. A cada login falhado, incrementa um contador por IP no Redis.
 *  2. Quando o contador atinge TURNSTILE_THRESHOLD, o front-end exibe o widget.
 *  3. No próximo submit, o controller chama verify() que valida o token
 *     contra a API da Cloudflare.
 *  4. Login bem-sucedido zera o contador.
 */
class TurnstileService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    private const COUNTER_TTL = 900; // 15 minutos
    private const SOFT_DENY_BUFFER = 2;

    private CacheService $cache;

    public function __construct(?CacheService $cache = null)
    {
        $this->cache = ApplicationContainer::resolveOrNew($cache, CacheService::class);
    }

    /**
     * Verifica se o Turnstile está configurado (chaves preenchidas).
     */
    public static function isEnabled(): bool
    {
        return defined('TURNSTILE_SITE_KEY')
            && defined('TURNSTILE_SECRET_KEY')
            && TURNSTILE_SITE_KEY !== ''
            && TURNSTILE_SECRET_KEY !== '';
    }

    /**
     * Retorna quantas tentativas falhadas o IP acumulou.
     */
    public function getFailedAttempts(string $ip): int
    {
        return (int) $this->cache->get("turnstile_fails:{$ip}", 0);
    }

    /**
     * Indica se o CAPTCHA deve ser exibido para este IP.
     */
    public function shouldRequireCaptcha(string $ip): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        $threshold = defined('TURNSTILE_THRESHOLD') ? TURNSTILE_THRESHOLD : 3;
        return $this->getFailedAttempts($ip) >= $threshold;
    }

    /**
     * Incrementa o contador de falhas para o IP.
     */
    public function recordFailedAttempt(string $ip): void
    {
        $key = "turnstile_fails:{$ip}";
        $current = $this->getFailedAttempts($ip);
        $this->cache->set($key, $current + 1, self::COUNTER_TTL);
    }

    /**
     * Zera o contador após login bem-sucedido.
     */
    public function resetFailedAttempts(string $ip): void
    {
        $this->cache->forget("turnstile_fails:{$ip}");
    }

    /**
     * Valida o token Turnstile contra a API da Cloudflare.
     *
     * @param string $token  O cf-turnstile-response do formulário.
     * @param string $ip     IP do cliente (para verificação extra).
     * @throws ValidationException Se o token for inválido.
     */
    public function verify(string $token, string $ip): void
    {
        if (empty($token)) {
            LogService::persist(
                LogLevel::WARNING,
                LogCategory::SECURITY,
                'Turnstile: token vazio em requisição que exige CAPTCHA',
                ['ip' => $ip],
            );
            throw new ValidationException(
                ['captcha' => 'Verificação de segurança necessária. Complete o CAPTCHA.'],
                'Validation failed',
                422
            );
        }

        $payload = [
            'secret'   => TURNSTILE_SECRET_KEY,
            'response' => $token,
            'remoteip' => $ip,
        ];

        $ch = curl_init(self::VERIFY_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            $failedAttempts = $this->getFailedAttempts($ip);
            $threshold = defined('TURNSTILE_THRESHOLD') ? (int) TURNSTILE_THRESHOLD : 3;
            $softDeny = $failedAttempts >= ($threshold + self::SOFT_DENY_BUFFER);

            LogService::persist(
                LogLevel::ERROR,
                LogCategory::SECURITY,
                'Turnstile: falha na comunicação com Cloudflare',
                [
                    'ip'        => $ip,
                    'http_code' => $httpCode,
                    'curl_err'  => $curlError,
                    'failed_attempts' => $failedAttempts,
                    'soft_deny' => $softDeny,
                ],
            );

            if ($softDeny) {
                throw new ValidationException(
                    ['captcha' => 'Não foi possível validar a verificação de segurança agora. Tente novamente em instantes.'],
                    'Validation failed',
                    422
                );
            }

            return;
        }

        $result = json_decode($response, true);

        if (empty($result['success'])) {
            $errorCodes = $result['error-codes'] ?? [];

            LogService::persist(
                LogLevel::WARNING,
                LogCategory::SECURITY,
                'Turnstile: verificação falhou',
                [
                    'ip'          => $ip,
                    'error_codes' => $errorCodes,
                ],
            );

            throw new ValidationException(
                ['captcha' => 'Verificação de segurança falhou. Tente novamente.'],
                'Validation failed',
                422
            );
        }
    }
}
