<?php

declare(strict_types=1);

namespace Application\Core;

use Application\Container\ApplicationContainer;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';
    private array $cookies = [];
    private ?string $downloadFilePath = null;
    private bool $shouldClearOutputBuffer = false;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getDownloadFilePath(): ?string
    {
        return $this->downloadFilePath;
    }

    public function shouldClearOutputBuffer(): bool
    {
        return $this->shouldClearOutputBuffer;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $key, string $value): self
    {
        $key = trim($key);

        if ($key === '' || preg_match("/[\r\n]/", $key) || preg_match("/[\r\n]/", $value)) {
            throw new \InvalidArgumentException('Invalid header.');
        }

        $this->headers[$key] = $value;
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        foreach ($headers as $key => $value) {
            $this->header((string) $key, (string) $value);
        }

        return $this;
    }

    public function setContent(string $content): self
    {
        $this->downloadFilePath = null;
        $this->shouldClearOutputBuffer = false;
        $this->content = $content;
        return $this;
    }

    public function jsonBody(mixed $data, int $statusCode = 200): self
    {
        $this->statusCode = $statusCode;
        $this->downloadFilePath = null;
        $this->shouldClearOutputBuffer = false;
        $this->header('Content-Type', 'application/json; charset=utf-8');

        // Adiciona header com TTL restante do CSRF token
        if (class_exists('\Application\Middlewares\CsrfMiddleware')) {
            $remainingTtl = \Application\Middlewares\CsrfMiddleware::getTokenRemainingTtl();
            if ($remainingTtl > 0) {
                $this->header('X-CSRF-TTL', (string) $remainingTtl);
            }
        }

        try {
            $this->content = json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            if (class_exists('\Application\Services\Infrastructure\LogService')) {
                \Application\Services\Infrastructure\LogService::captureException($e, \Application\Enums\LogCategory::GENERAL, [
                    'action' => 'response_json_encode',
                    'status_code' => $statusCode,
                ]);
            }

            $this->statusCode = 500;
            $this->content = json_encode([
                'success' => false,
                'message' => 'Erro interno ao serializar resposta.',
                'request_id' => class_exists('\Application\Services\Infrastructure\LogService')
                    ? \Application\Services\Infrastructure\LogService::currentRequestId()
                    : null,
            ]);
        }

        return $this;
    }

    public function html(string $html, int $statusCode = 200): self
    {
        $this->statusCode = $statusCode;
        $this->downloadFilePath = null;
        $this->shouldClearOutputBuffer = false;
        $this->header('Content-Type', 'text/html; charset=utf-8');
        $this->content = $html;
        return $this;
    }

    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->statusCode = $statusCode;
        $this->downloadFilePath = null;
        $this->shouldClearOutputBuffer = false;
        $this->content = '';
        $this->header('Location', $url);
        return $this;
    }

    public function download(string $filePath, ?string $fileName = null): self
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException("Arquivo nao encontrado ou sem permissao: {$filePath}");
        }

        $fileName = $fileName ?: basename($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $this->header('Content-Type', $mimeType);
        $this->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $this->header('Content-Length', (string) filesize($filePath));
        $this->downloadFilePath = $filePath;
        $this->shouldClearOutputBuffer = true;
        $this->content = '';

        return $this;
    }

    public function clearOutputBuffer(bool $clear = true): self
    {
        $this->shouldClearOutputBuffer = $clear;
        return $this;
    }

    public function cookie(string $name, string $value, int $minutes = 60, string $path = '/'): self
    {
        $this->cookies[] = [
            'name' => $name,
            'value' => $value,
            'options' => [
                'expires' => time() + ($minutes * 60),
                'path' => $path,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ],
        ];

        return $this;
    }

    public function forgetCookie(string $name, string $path = '/'): self
    {
        $this->cookies[] = [
            'name' => $name,
            'value' => '',
            'options' => [
                'expires' => time() - 3600,
                'path' => $path,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ],
        ];

        return $this;
    }

    public function send(): void
    {
        ApplicationContainer::resolveOrNew(null, ResponseEmitter::class)->emit($this);
    }

    public static function jsonResponse(mixed $data, int $statusCode = 200): self
    {
        return (new self())->jsonBody($data, $statusCode);
    }

    public static function htmlResponse(string $html, int $statusCode = 200): self
    {
        return (new self())->html($html, $statusCode);
    }

    public static function redirectResponse(string $url, int $statusCode = 302): self
    {
        return (new self())->redirect($url, $statusCode);
    }

    public static function downloadResponse(string $filePath, ?string $fileName = null): self
    {
        return (new self())->download($filePath, $fileName);
    }

    public static function successResponse(mixed $data = null, string $message = 'Success', int $statusCode = 200): self
    {
        return self::jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public static function errorResponse(string $message, int $statusCode = 400, mixed $errors = null, ?string $code = null): self
    {
        $payload = ['success' => false, 'message' => $message];
        $providedEmptyArray = is_array($errors) && $errors === [];

        if (is_array($errors)) {
            foreach (['error_id', 'request_id', 'code'] as $metaKey) {
                if (array_key_exists($metaKey, $errors) && is_scalar($errors[$metaKey])) {
                    $payload[$metaKey] = (string) $errors[$metaKey];
                    unset($errors[$metaKey]);
                }
            }

            if ($errors !== [] || $providedEmptyArray) {
                $payload['errors'] = $errors;
            }
        } elseif ($errors !== null) {
            $payload['errors'] = $errors;
        }

        if ($code !== null && !isset($payload['code'])) {
            $payload['code'] = $code;
        }

        if (!isset($payload['request_id']) && isset($payload['error_id'])) {
            $payload['request_id'] = $payload['error_id'];
        }

        return self::jsonResponse($payload, $statusCode);
    }

    public static function notFoundResponse(string $message = 'Resource not found'): self
    {
        return self::errorResponse($message, 404);
    }

    public static function unauthorizedResponse(string $message = 'Unauthorized'): self
    {
        return self::errorResponse($message, 401);
    }

    public static function forbiddenResponse(string $message = 'Forbidden'): self
    {
        return self::errorResponse($message, 403);
    }

    public static function validationErrorResponse(array $errors, int $code = 422): self
    {
        return self::errorResponse('Validation failed', $code, $errors);
    }
}
