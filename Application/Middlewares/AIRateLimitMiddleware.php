<?php

declare(strict_types=1);

namespace Application\Middlewares;

use Application\Core\Exceptions\ValidationException;
use Application\Core\Request;
use Application\Lib\Auth;
use Application\Services\AI\Security\AIRateLimiter;

/**
 * Rate limit dedicado para rotas de IA.
 *
 * Aplica limite por usuário autenticado e uma camada secundária por IP.
 * Isso reduz abuso de conta sem punir toda a aplicação com o middleware genérico.
 */
final class AIRateLimitMiddleware
{
    private const RULES = [
        'chat'                 => ['user_limit' => 20, 'ip_limit' => 80,  'window' => 60],
        'conversation_message' => ['user_limit' => 20, 'ip_limit' => 80,  'window' => 60],
        'suggest_category'     => ['user_limit' => 30, 'ip_limit' => 120, 'window' => 60],
        'analyze'              => ['user_limit' => 10, 'ip_limit' => 40,  'window' => 60],
        'extract_transaction'  => ['user_limit' => 30, 'ip_limit' => 120, 'window' => 60],
        'conversation_manage'  => ['user_limit' => 15, 'ip_limit' => 60,  'window' => 60],
        'action'               => ['user_limit' => 25, 'ip_limit' => 100, 'window' => 60],
        'sysadmin_chat'        => ['user_limit' => 20, 'ip_limit' => 80,  'window' => 60],
        'sysadmin_category'    => ['user_limit' => 30, 'ip_limit' => 120, 'window' => 60],
        'sysadmin_analyze'     => ['user_limit' => 10, 'ip_limit' => 40,  'window' => 60],
        'default'              => ['user_limit' => 20, 'ip_limit' => 80,  'window' => 60],
    ];

    public static function handle(Request $request): void
    {
        $bucket = self::resolveBucket();
        $rule = self::RULES[$bucket] ?? self::RULES['default'];
        $limiter = new AIRateLimiter();
        $ip = $request->ip() ?: 'unknown';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $user = Auth::user();

        if (!$limiter->allow(
            'ip',
            $bucket,
            $ip,
            $rule['ip_limit'],
            $rule['window'],
            ['ip' => $ip, 'uri' => $uri, 'method' => $request->method()],
        )) {
            throw self::buildException($bucket, $rule['window']);
        }

        if ($user && isset($user->id) && !$limiter->allow(
            'user',
            $bucket,
            (string) $user->id,
            $rule['user_limit'],
            $rule['window'],
            ['user_id' => $user->id, 'ip' => $ip, 'uri' => $uri, 'method' => $request->method()],
        )) {
            throw self::buildException($bucket, $rule['window']);
        }
    }

    private static function buildException(string $bucket, int $windowSeconds): ValidationException
    {
        return new ValidationException(
            errors: [
                'rate_limit' => "Muitas requisições para o fluxo de IA ({$bucket}). Aguarde {$windowSeconds} segundos.",
                'bucket'     => $bucket,
            ],
            message: 'AI rate limit exceeded',
            code: 429,
        );
    }

    private static function resolveBucket(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

        return match (true) {
            str_contains($path, '/api/sysadmin/ai/chat') => 'sysadmin_chat',
            str_contains($path, '/api/sysadmin/ai/suggest-category') => 'sysadmin_category',
            str_contains($path, '/api/sysadmin/ai/analyze-spending') => 'sysadmin_analyze',
            str_contains($path, '/api/ai/suggest-category') => 'suggest_category',
            str_contains($path, '/api/ai/analyze') => 'analyze',
            str_contains($path, '/api/ai/extract-transaction') => 'extract_transaction',
            str_contains($path, '/api/ai/conversations/') && strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' => 'conversation_message',
            str_contains($path, '/api/ai/conversations') => 'conversation_manage',
            str_contains($path, '/api/ai/actions/') => 'action',
            str_contains($path, '/api/ai/chat') => 'chat',
            default => 'default',
        };
    }
}
