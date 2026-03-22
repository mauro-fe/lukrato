<?php

declare(strict_types=1);

namespace Application\Core;

class ResponseEmitter
{
    public function emit(Response $response): void
    {
        if (!headers_sent()) {
            http_response_code($response->getStatusCode());

            foreach ($response->getHeaders() as $key => $value) {
                header("{$key}: {$value}");
            }

            foreach ($response->getCookies() as $cookie) {
                setcookie($cookie['name'], $cookie['value'], $cookie['options']);
            }
        }

        if ($response->shouldClearOutputBuffer()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        $downloadFilePath = $response->getDownloadFilePath();
        if ($downloadFilePath !== null) {
            readfile($downloadFilePath);
            return;
        }

        echo $response->getContent();
        return;
    }
}
