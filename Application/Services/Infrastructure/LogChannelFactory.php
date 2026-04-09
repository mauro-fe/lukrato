<?php

declare(strict_types=1);

namespace Application\Services\Infrastructure;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

final class LogChannelFactory
{
    public static function build(string $logFilePath): Logger
    {
        $logDir = dirname($logFilePath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $stream = new StreamHandler($logFilePath, Logger::DEBUG);
        $stream->setFormatter(new LineFormatter(
            "[%datetime%] [%level_name%]: %message% %context%\n",
            'Y-m-d H:i:s',
            true,
            true
        ));

        $logger = new Logger('app');
        $logger->pushHandler($stream);

        return $logger;
    }
}