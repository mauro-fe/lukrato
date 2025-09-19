<?php

namespace Application\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function jsonBody($data, int $statusCode = 200): self
    {

        $this->statusCode = $statusCode;
        $this->header('Content-Type', 'application/json; charset=utf-8');
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function html(string $html, int $statusCode = 200): self
    {
        $this->statusCode = $statusCode;
        $this->header('Content-Type', 'text/html; charset=utf-8');
        $this->content = $html;
        return $this;
    }

    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->statusCode = $statusCode;
        $this->header('Location', $url);
        return $this;
    }

    public function download(string $filePath, ?string $fileName = null): self
    {
        if (!is_file($filePath)) {
            throw new \RuntimeException("Arquivo não encontrado: {$filePath}");
        }

        $fileName = $fileName ?: basename($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $this->header('Content-Type', $mimeType);
        $this->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $this->header('Content-Length', (string)filesize($filePath));
        $this->content = (string)file_get_contents($filePath);

        return $this;
    }

    public function cookie(string $name, string $value, int $minutes = 60, string $path = '/'): self
    {
        $expire = time() + ($minutes * 60);
        setcookie($name, $value, [
            'expires'  => $expire,
            'path'     => $path,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        return $this;
    }

    public function forgetCookie(string $name, string $path = '/'): self
    {
        setcookie($name, '', [
            'expires'  => time() - 3600,
            'path'     => $path,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        echo $this->content;
        exit;
    }


    public static function json($data, int $statusCode = 200): void
    {
        (new self())->jsonBody($data, $statusCode)->send();
    }

    public static function htmlOut(string $html, int $statusCode = 200): void
    {
        (new self())->html($html, $statusCode)->send();
    }

    public static function redirectTo(string $url, int $statusCode = 302): void
    {
        (new self())->redirect($url, $statusCode)->send();
    }

    public static function downloadFile(string $filePath, ?string $fileName = null): void
    {
        (new self())->download($filePath, $fileName)->send();
    }

    public static function success($data = null, string $message = 'Success'): void
    {
        self::json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], 200);
    }

    public static function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        $payload = ['status' => 'error', 'message' => $message];
        if (!empty($errors)) $payload['errors'] = $errors;
        self::json($payload, $statusCode);
    }

    public static function notFound(string $message = 'Resource not found'): void
    {
        self::json(['status' => 'error', 'message' => $message], 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::json(['status' => 'error', 'message' => $message], 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::json(['status' => 'error', 'message' => $message], 403);
    }

    public static function validationError(array $errors): void
    {
        self::json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $errors], 422);
    }
}
