<?php

declare(strict_types=1);

namespace Application\Services\Infrastructure;

use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Models\ErrorLog;
use Monolog\Logger;

class LogService
{
    private const LOG_DIR = BASE_PATH . '/storage/logs';
    private const REDACTED = '[REDACTED]';
    private const REDACTED_PAYLOAD = '[REDACTED_PAYLOAD]';
    private const REDACTED_SESSION = '[REDACTED_SESSION]';

    private static ?Logger $logger = null;
    private static ?string $requestId = null;

    /**
     * Se a persistencia no DB esta habilitada.
     * Desativado automaticamente se a tabela não existe ou o DB falha.
     */
    private static bool $dbEnabled = true;
    private static bool $dbChecked = false;

    public static function info(string $message, array $context = []): void
    {
        self::writeLog('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::writeLog('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::writeLog('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::writeLog('critical', $message, $context);
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        self::writeLog($level, $message, $context);
    }

    public static function sanitizeContext(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $sanitized[$key] = self::sanitizeValue(
                $value,
                is_string($key) ? $key : null
            );
        }

        return $sanitized;
    }

    public static function sanitizeMessage(string $message): string
    {
        if ($message === '') {
            return $message;
        }

        $message = self::sanitizeInlineAuthorization($message);
        $message = self::sanitizeInlineCookie($message);
        $message = self::sanitizeInlinePairs($message);
        $message = self::sanitizeInlineUrls($message);
        $message = self::sanitizeInlineEmails($message);
        $message = self::sanitizeInlineCpfLabels($message);

        return $message;
    }

    public static function safeErrorLog(string $message): void
    {
        error_log(self::sanitizeMessage($message));
    }

    /**
     * Registra um erro no banco de dados + arquivo de log.
     * Use para erros que precisam de visibilidade no painel admin.
     */
    public static function persist(
        LogLevel $level,
        LogCategory $category,
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
        ?int $userId = null,
    ): ?ErrorLog {
        [$context, $userId] = self::enrichContext($context, $userId);
        $sanitizedMessage = self::sanitizeMessage($message);
        $sanitizedContext = self::sanitizeContext($context);

        self::writeLog($level->value, $sanitizedMessage, array_merge($sanitizedContext, [
            'category' => $category->value,
            'user_id' => $userId,
            'request_id' => $sanitizedContext['request_id'] ?? self::currentRequestId(),
        ]));

        return self::writeToDb($level, $category, $sanitizedMessage, $sanitizedContext, $exception, $userId);
    }

    /**
     * Captura uma exception completa e persiste.
     * Atalho para os catch blocks mais comuns.
     */
    public static function captureException(
        \Throwable $e,
        LogCategory $category = LogCategory::GENERAL,
        array $context = [],
        ?int $userId = null,
        LogLevel $level = LogLevel::ERROR,
    ): ?ErrorLog {
        return self::persist(
            level: $level,
            category: $category,
            message: $e->getMessage(),
            context: $context,
            exception: $e,
            userId: $userId,
        );
    }

    public static function reportException(
        \Throwable $e,
        string $publicMessage = 'Erro interno do servidor.',
        array $context = [],
        ?int $userId = null,
        LogCategory $category = LogCategory::GENERAL,
        LogLevel $level = LogLevel::ERROR,
    ): string {
        $errorId = bin2hex(random_bytes(6));
        $requestId = self::currentRequestId();

        self::persist(
            level: $level,
            category: $category,
            message: $publicMessage,
            context: array_merge($context, [
                'error_id' => $errorId,
                'request_id' => $requestId,
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]),
            exception: $e,
            userId: $userId,
        );

        return $errorId;
    }

    public static function currentRequestId(): string
    {
        if (self::$requestId !== null) {
            return self::$requestId;
        }

        $headerRequestId = self::request()->header('x-request-id');
        if (is_string($headerRequestId) && preg_match('/^[a-zA-Z0-9._:-]{6,100}$/', $headerRequestId) === 1) {
            self::$requestId = $headerRequestId;
            return self::$requestId;
        }

        self::$requestId = bin2hex(random_bytes(8));

        return self::$requestId;
    }

    /**
     * Buscar logs com filtros e paginação
     */
    public static function query(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        if (!self::isDbAvailable()) {
            return ['data' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }

        $query = ErrorLog::query()->orderByDesc('created_at');

        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (isset($filters['resolved'])) {
            $filters['resolved']
                ? $query->whereNotNull('resolved_at')
                : $query->whereNull('resolved_at');
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere('exception_message', 'like', "%{$search}%");
            });
        }
        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        $total = $query->count();
        $data = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Resumo para o dashboard admin: contadores por nivel nas ultimas 24h
     */
    public static function summary(int $hours = 24): array
    {
        if (!self::isDbAvailable()) {
            return ['total' => 0, 'unresolved' => 0, 'by_level' => [], 'by_category' => []];
        }

        $byLevel = ErrorLog::whereNull('resolved_at')
            ->selectRaw('level, COUNT(*) as total')
            ->groupBy('level')
            ->pluck('total', 'level')
            ->toArray();

        $byCategory = ErrorLog::whereNull('resolved_at')
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'category')
            ->toArray();

        $unresolved = ErrorLog::whereNull('resolved_at')->count();

        return [
            'total' => array_sum($byLevel),
            'unresolved' => $unresolved,
            'by_level' => $byLevel,
            'by_category' => $byCategory,
            'period_hours' => $hours,
        ];
    }

    /**
     * Marcar log como resolvido
     */
    public static function resolve(int $logId, ?int $resolvedBy = null): bool
    {
        if (!self::isDbAvailable()) {
            return false;
        }

        $log = ErrorLog::find($logId);
        if ($log) {
            $log->markResolved($resolvedBy);
            return true;
        }

        return false;
    }

    /**
     * Limpar logs antigos (executar via CLI/cron)
     */
    public static function cleanup(int $daysToKeep = 30, bool $includeUnresolved = false): int
    {
        if (!self::isDbAvailable()) {
            return 0;
        }

        $cutoff = now()->subDays($daysToKeep);
        $query = ErrorLog::query();

        if ($includeUnresolved) {
            return $query
                ->where('created_at', '<', $cutoff)
                ->delete();
        }

        return $query
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<', $cutoff)
            ->delete();
    }

    private static function writeToDb(
        LogLevel $level,
        LogCategory $category,
        string $message,
        array $context,
        ?\Throwable $exception,
        ?int $userId,
    ): ?ErrorLog {
        if (!self::isDbAvailable()) {
            return null;
        }

        try {
            $request = self::request();
            $url = $request->server('REQUEST_URI');
            $method = $request->server('REQUEST_METHOD');
            $ip = $request->ip();
            $userAgent = $request->header('user-agent');

            if ($userId === null) {
                try {
                    $userId = \Application\Lib\Auth::id();
                } catch (\Throwable) {
                    // ignore
                }
            }

            $data = [
                'level' => $level->value,
                'category' => $category->value,
                'message' => mb_substr(self::sanitizeMessage($message), 0, 500),
                'context' => !empty($context) ? self::sanitizeContext($context) : null,
                'user_id' => $userId,
                'url' => $url ? mb_substr(self::sanitizeUrl($url), 0, 500) : null,
                'method' => $method,
                'ip' => $ip,
                'user_agent' => $userAgent ? mb_substr(self::sanitizeMessage($userAgent), 0, 500) : null,
            ];

            if ($exception) {
                $data['exception_class'] = get_class($exception);
                $data['exception_message'] = mb_substr(self::sanitizeMessage($exception->getMessage()), 0, 65535);
                $data['file'] = $exception->getFile();
                $data['line'] = $exception->getLine();
                $data['stack_trace'] = mb_substr(self::sanitizeMessage($exception->getTraceAsString()), 0, 65535);
            }

            return ErrorLog::create($data);
        } catch (\Throwable $e) {
            self::safeErrorLog('[LogService] Falha ao persistir no DB: ' . $e->getMessage());
            self::$dbEnabled = false;
            return null;
        }
    }

    private static function request(): Request
    {
        return ApplicationContainer::resolveOrNew(null, Request::class);
    }

    /**
     * @return array{0: array<string, mixed>, 1: ?int}
     */
    private static function enrichContext(array $context, ?int $userId): array
    {
        if (!isset($context['request_id']) || !is_string($context['request_id']) || trim($context['request_id']) === '') {
            $context['request_id'] = self::currentRequestId();
        }

        $resolvedUserId = $userId;
        if (isset($context['user_id']) && is_scalar($context['user_id'])) {
            $resolvedUserId = (int) $context['user_id'];
        }

        if ($resolvedUserId === null) {
            try {
                $resolvedUserId = \Application\Lib\Auth::id();
            } catch (\Throwable) {
                $resolvedUserId = null;
            }
        }

        if ($resolvedUserId !== null) {
            $context['user_id'] = $resolvedUserId;
        }

        return [$context, $resolvedUserId];
    }

    private static function isDbAvailable(): bool
    {
        if (!self::$dbEnabled) {
            return false;
        }

        if (!self::$dbChecked) {
            try {
                $exists = \Illuminate\Database\Capsule\Manager::schema()->hasTable('error_logs');
                self::$dbEnabled = $exists;
                self::$dbChecked = true;
            } catch (\Throwable) {
                self::$dbEnabled = false;
                self::$dbChecked = true;
            }
        }

        return self::$dbEnabled;
    }

    private static function writeLog(string $level, string $message, array $context = []): void
    {
        $logger = self::getLogger();
        $level = self::normalizeLogLevel($level);
        $sanitizedMessage = self::sanitizeMessage($message);
        [$context] = self::enrichContext($context, null);
        $sanitizedContext = self::sanitizeContext($context);

        $logger->log($level, $sanitizedMessage, $sanitizedContext);
    }

    /**
     * @return 'alert'|'critical'|'debug'|'emergency'|'error'|'info'|'notice'|'warning'
     */
    private static function normalizeLogLevel(string $level): string
    {
        return match (strtolower($level)) {
            'alert' => 'alert',
            'critical' => 'critical',
            'debug' => 'debug',
            'emergency' => 'emergency',
            'error' => 'error',
            'notice' => 'notice',
            'warning' => 'warning',
            default => 'info',
        };
    }

    private static function sanitizeValue(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && self::isPayloadKey($key)) {
            return self::summarizePayload($value);
        }

        if ($key !== null && self::isSessionKey($key)) {
            return self::summarizeSession($value);
        }

        if ($key !== null && self::isHeaderBagKey($key) && is_array($value)) {
            return self::sanitizeHeaders($value);
        }

        if ($key !== null && self::isSensitiveKey($key)) {
            return self::REDACTED;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_array($value)) {
            return self::sanitizeContext($value);
        }

        if (is_object($value)) {
            return '[object ' . get_class($value) . ']';
        }

        if (!is_string($value)) {
            return $value;
        }

        if ($key !== null && self::isEmailKey($key)) {
            return self::maskEmail($value);
        }

        if ($key !== null && self::isCpfKey($key)) {
            return self::maskCpf($value);
        }

        if ($key !== null && self::isUrlKey($key)) {
            return self::sanitizeUrl($value);
        }

        return self::sanitizeMessage($value);
    }

    private static function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];

        foreach ($headers as $name => $value) {
            if (is_string($name) && self::isSensitiveKey($name)) {
                $sanitized[$name] = self::REDACTED;
                continue;
            }

            $sanitized[$name] = self::sanitizeValue(
                $value,
                is_string($name) ? $name : null
            );
        }

        return $sanitized;
    }

    private static function summarizePayload(mixed $value): string
    {
        return sprintf(
            '%s type=%s meta=%s sha256=%s',
            self::REDACTED_PAYLOAD,
            get_debug_type($value),
            self::payloadMetadata($value),
            self::payloadHash($value)
        );
    }

    private static function summarizeSession(mixed $value): string
    {
        $meta = is_array($value) ? 'keys=' . count($value) : 'type=' . get_debug_type($value);

        return self::REDACTED_SESSION . ' ' . $meta;
    }

    private static function payloadMetadata(mixed $value): string
    {
        return match (true) {
            is_string($value) => 'length=' . strlen($value),
            is_array($value) => 'items=' . count($value),
            default => 'size=1',
        };
    }

    private static function payloadHash(mixed $value): string
    {
        $encoded = is_string($value)
            ? $value
            : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        if (!is_string($encoded) || $encoded === '') {
            $encoded = serialize([$value]);
        }

        return substr(hash('sha256', $encoded), 0, 12);
    }

    private static function sanitizeInlinePairs(string $message): string
    {
        $keys = 'password|senha|current_password|new_password|password_confirmation|token|csrf_token|_token|session_id|access_token|refresh_token|validator|token_hash|api_key|client_secret|secret|selector';

        $message = (string) preg_replace_callback(
            '/("?(?:' . $keys . ')"?\s*:\s*)"([^"]*)"/iu',
            static fn(array $matches): string => $matches[1] . '"' . self::REDACTED . '"',
            $message
        );

        return (string) preg_replace_callback(
            '/((?:^|[\s,{])"?)((' . $keys . '))("?\s*[:=]\s*)([^,\s}\]]+)/iu',
            static fn(array $matches): string => $matches[1] . $matches[2] . $matches[4] . self::REDACTED,
            $message
        );
    }

    private static function sanitizeInlineAuthorization(string $message): string
    {
        return (string) preg_replace_callback(
            '/(authorization\s*[:=]\s*)(Bearer\s+)?([^\s,\r\n]+)/iu',
            static function (array $matches): string {
                $prefix = $matches[1];
                $scheme = $matches[2];

                return $prefix . $scheme . self::REDACTED;
            },
            $message
        );
    }

    private static function sanitizeInlineCookie(string $message): string
    {
        return (string) preg_replace(
            '/((?:cookie|set-cookie)\s*[:=]\s*)([^;\r\n]+)/iu',
            '$1' . self::REDACTED,
            $message
        );
    }

    private static function sanitizeInlineUrls(string $message): string
    {
        return (string) preg_replace_callback(
            '/https?:\/\/[^\s]+|\/[A-Za-z0-9_\-\/.?=&%]+/u',
            static function (array $matches): string {
                $candidate = $matches[0];

                if (!str_contains($candidate, '?')) {
                    return $candidate;
                }

                return self::sanitizeUrl($candidate);
            },
            $message
        );
    }

    private static function sanitizeInlineEmails(string $message): string
    {
        return (string) preg_replace_callback(
            '/\b([A-Z0-9._%+\-]+)@([A-Z0-9.\-]+\.[A-Z]{2,})\b/i',
            static fn(array $matches): string => self::maskEmail($matches[0]),
            $message
        );
    }

    private static function sanitizeInlineCpfLabels(string $message): string
    {
        return (string) preg_replace_callback(
            '/\b(cpf|documento)\b(\s*[:=]\s*)([0-9.\-\/]{11,18})/iu',
            static fn(array $matches): string => $matches[1] . $matches[2] . self::maskCpf($matches[3]),
            $message
        );
    }

    private static function sanitizeUrl(string $value): string
    {
        if ($value === '' || !str_contains($value, '?')) {
            return $value;
        }

        $parts = parse_url($value);
        if ($parts === false || !isset($parts['query'])) {
            return (string) preg_replace_callback(
                '/([?&])(password|senha|token|csrf_token|_token|authorization|session_id|access_token|refresh_token|validator|selector|api_key|client_secret)=([^&]+)/iu',
                static fn(array $matches): string => $matches[1] . $matches[2] . '=' . self::REDACTED,
                $value
            );
        }

        parse_str($parts['query'], $query);
        $sanitizedQuery = [];

        foreach ($query as $queryKey => $queryValue) {
            $sanitizedQuery[$queryKey] = self::sanitizeValue($queryValue, (string) $queryKey);
        }

        $base = '';
        if (isset($parts['scheme'])) {
            $base .= $parts['scheme'] . '://';
        }
        if (isset($parts['user'])) {
            $base .= $parts['user'];
            if (isset($parts['pass'])) {
                $base .= ':' . self::REDACTED;
            }
            $base .= '@';
        }
        if (isset($parts['host'])) {
            $base .= $parts['host'];
        }
        if (isset($parts['port'])) {
            $base .= ':' . $parts['port'];
        }
        $base .= $parts['path'] ?? '';

        $queryString = http_build_query($sanitizedQuery);
        if ($queryString !== '') {
            $base .= '?' . $queryString;
        }
        if (isset($parts['fragment'])) {
            $base .= '#' . $parts['fragment'];
        }

        return $base;
    }

    private static function maskEmail(string $value): string
    {
        if (!str_contains($value, '@')) {
            return self::REDACTED;
        }

        [$local, $domain] = explode('@', $value, 2);
        $firstChar = $local !== '' ? mb_substr($local, 0, 1) : '*';

        return $firstChar . '***@' . $domain;
    }

    private static function maskCpf(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);

        if (!is_string($digits) || strlen($digits) !== 11) {
            return self::REDACTED;
        }

        return '***.***.***-**';
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = self::normalizeKey($key);

        return in_array($normalized, [
            'password',
            'senha',
            'currentpassword',
            'newpassword',
            'passwordconfirmation',
            'token',
            'csrftoken',
            'authorization',
            'sessionid',
            'accesstoken',
            'refreshtoken',
            'validator',
            'tokenhash',
            'apikey',
            'clientsecret',
            'secret',
            'cookie',
            'setcookie',
            'selector',
            'expectedprefix',
            'providedprefix',
            'tokenprefix',
            'oldtokenprefix',
            'newtokenprefix',
            'legacytokenprefix',
        ], true) || str_ends_with($normalized, 'token') || str_ends_with($normalized, 'secret');
    }

    private static function isPayloadKey(string $key): bool
    {
        return in_array(self::normalizeKey($key), [
            'payload',
            'rawbody',
            'rawpayload',
            'requestbody',
            'responsebody',
            'requestpayload',
            'responsepayload',
            'webhookpayload',
            'webhookbody',
            'server',
            'request',
            'response',
        ], true);
    }

    private static function isSessionKey(string $key): bool
    {
        return in_array(self::normalizeKey($key), [
            'session',
            'sessiondata',
            'sessioncontext',
        ], true);
    }

    private static function isHeaderBagKey(string $key): bool
    {
        return in_array(self::normalizeKey($key), [
            'headers',
            'requestheaders',
            'responseheaders',
        ], true);
    }

    private static function isEmailKey(string $key): bool
    {
        return str_contains(self::normalizeKey($key), 'email');
    }

    private static function isCpfKey(string $key): bool
    {
        return in_array(self::normalizeKey($key), [
            'cpf',
            'cpfcnpj',
            'documentocpf',
        ], true);
    }

    private static function isUrlKey(string $key): bool
    {
        return in_array(self::normalizeKey($key), [
            'url',
            'uri',
            'requesturi',
            'redirecturl',
            'path',
        ], true);
    }

    private static function normalizeKey(string $key): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower($key)) ?? strtolower($key);
    }

    private static function getLogger(): Logger
    {
        if (!self::$logger) {
            self::$logger = LogChannelFactory::build(self::getLogFilePath());
        }

        return self::$logger;
    }

    private static function getLogFilePath(): string
    {
        return self::LOG_DIR . '/app-' . date('Y-m-d') . '.log';
    }
}
