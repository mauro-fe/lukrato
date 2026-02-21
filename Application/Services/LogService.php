<?php

namespace Application\Services;

use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Models\ErrorLog;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class LogService
{
    private const LOG_DIR = BASE_PATH . '/storage/logs';
    private static ?Logger $logger = null;

    /**
     * Se a persistência no DB está habilitada.
     * Desativado automaticamente se a tabela não existe ou o DB falha.
     */
    private static bool $dbEnabled = true;
    private static bool $dbChecked = false;

    // ─── Métodos públicos (compatíveis com uso existente) ──

    public static function info(string $message, array $context = []): void
    {
        self::getLogger()->info($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::getLogger()->warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getLogger()->error($message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::getLogger()->critical($message, $context);
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $logger = self::getLogger();
        if (method_exists($logger, $level)) {
            $logger->{$level}($message, $context);
        } else {
            $logger->log(strtoupper($level), $message, $context);
        }
    }

    // ─── Métodos com persistência no DB ─────────────────────

    /**
     * Registra um erro no banco de dados + arquivo de log.
     * Use para erros que precisam de visibilidade no painel admin.
     */
    public static function persist(
        LogLevel    $level,
        LogCategory $category,
        string      $message,
        array       $context = [],
        ?\Throwable $exception = null,
        ?int        $userId = null,
    ): ?ErrorLog {
        // Sempre gravar no arquivo de log
        $fileLevel = $level->value;
        self::$fileLevel($message, array_merge($context, [
            'category' => $category->value,
            'user_id'  => $userId,
        ]));

        // Persistir no banco
        return self::writeToDb($level, $category, $message, $context, $exception, $userId);
    }

    /**
     * Captura uma exception completa e persiste.
     * Atalho para os catch blocks mais comuns.
     */
    public static function captureException(
        \Throwable  $e,
        LogCategory $category = LogCategory::GENERAL,
        array       $context = [],
        ?int        $userId = null,
        LogLevel    $level = LogLevel::ERROR,
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

    // ─── Query helpers (para o painel admin) ────────────────

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
        $data  = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return [
            'data'     => $data,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Resumo para o dashboard admin: contadores por nível nas últimas 24h
     */
    public static function summary(int $hours = 24): array
    {
        if (!self::isDbAvailable()) {
            return ['total' => 0, 'unresolved' => 0, 'by_level' => [], 'by_category' => []];
        }

        $since = now()->subHours($hours);

        $byLevel = ErrorLog::where('created_at', '>=', $since)
            ->selectRaw('level, COUNT(*) as total')
            ->groupBy('level')
            ->pluck('total', 'level')
            ->toArray();

        $byCategory = ErrorLog::where('created_at', '>=', $since)
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'category')
            ->toArray();

        $unresolved = ErrorLog::whereNull('resolved_at')
            ->where('level', '!=', 'info')
            ->count();

        return [
            'total'       => array_sum($byLevel),
            'unresolved'  => $unresolved,
            'by_level'    => $byLevel,
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
    public static function cleanup(int $daysToKeep = 30): int
    {
        if (!self::isDbAvailable()) {
            return 0;
        }

        return ErrorLog::where('created_at', '<', now()->subDays($daysToKeep))
            ->whereNotNull('resolved_at')
            ->delete();
    }

    // ─── Internals ──────────────────────────────────────────

    private static function writeToDb(
        LogLevel     $level,
        LogCategory  $category,
        string       $message,
        array        $context,
        ?\Throwable  $exception,
        ?int         $userId,
    ): ?ErrorLog {
        if (!self::isDbAvailable()) {
            return null;
        }

        try {
            // Capturar contexto da request
            $url       = $_SERVER['REQUEST_URI'] ?? null;
            $method    = $_SERVER['REQUEST_METHOD'] ?? null;
            $ip        = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            // Auto-detect userId se não informado
            if ($userId === null) {
                try {
                    $userId = \Application\Lib\Auth::id();
                } catch (\Throwable) {
                    // ignore
                }
            }

            $data = [
                'level'    => $level->value,
                'category' => $category->value,
                'message'  => mb_substr($message, 0, 500),
                'context'  => !empty($context) ? $context : null,
                'user_id'  => $userId,
                'url'      => $url ? mb_substr($url, 0, 500) : null,
                'method'   => $method,
                'ip'       => $ip,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
            ];

            if ($exception) {
                $data['exception_class']   = get_class($exception);
                $data['exception_message'] = mb_substr($exception->getMessage(), 0, 65535);
                $data['file']              = $exception->getFile();
                $data['line']              = $exception->getLine();
                $data['stack_trace']       = mb_substr($exception->getTraceAsString(), 0, 65535);
            }

            return ErrorLog::create($data);
        } catch (\Throwable $e) {
            // Falha silenciosa — não queremos que o logging quebre a app
            error_log("[LogService] Falha ao persistir no DB: " . $e->getMessage());
            self::$dbEnabled = false;
            return null;
        }
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

    private static function getLogger(): Logger
    {
        if (!self::$logger) {
            $logFilePath = self::getLogFilePath();

            $logDir = dirname($logFilePath);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0775, true);
            }

            $stream = new StreamHandler($logFilePath, Logger::DEBUG);

            $output = "[%datetime%] [%level_name%]: %message% %context%\n";
            $formatter = new LineFormatter($output, "Y-m-d H:i:s", true, true);
            $stream->setFormatter($formatter);

            self::$logger = new Logger('app');
            self::$logger->pushHandler($stream);
        }

        return self::$logger;
    }

    private static function getLogFilePath(): string
    {
        return self::LOG_DIR . '/app-' . date('Y-m-d') . '.log';
    }
}
