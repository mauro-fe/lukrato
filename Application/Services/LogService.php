<?php

namespace Application\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class LogService
{
    private const LOG_DIR = BASE_PATH . '/storage/logs';
    private static ?Logger $logger = null;


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

    private static function getLogFilePath(): string
    {
        return self::LOG_DIR . '/app-' . date('Y-m-d') . '.log';
    }
}
