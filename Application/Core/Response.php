<?php

namespace Application\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private $content = '';

    /**
     * Define o código de status HTTP
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Define um header
     */
    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Define múltiplos headers
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Define o conteúdo da resposta
     */
    public function setContent($content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Retorna resposta JSON
     */
    public function json($data, int $statusCode = 200): self
    {
        file_put_contents('json_debug.txt', json_encode($data));

        $this->statusCode = $statusCode;
        $this->header('Content-Type', 'application/json; charset=utf-8');
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    /**
     * Retorna HTML
     */
    public function html(string $html, int $statusCode = 200): self
    {
        $this->statusCode = $statusCode;
        $this->header('Content-Type', 'text/html; charset=utf-8');
        $this->content = $html;
        return $this;
    }

    /**
     * Redireciona para URL
     */
    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->statusCode = $statusCode;
        $this->header('Location', $url);
        return $this;
    }

    /**
     * Retorna arquivo para download
     */
    public function download(string $filePath, string $fileName = null): self
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Arquivo não encontrado: {$filePath}");
        }

        $fileName = $fileName ?: basename($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $this->header('Content-Type', $mimeType);
        $this->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $this->header('Content-Length', filesize($filePath));
        $this->content = file_get_contents($filePath);

        return $this;
    }

    /**
     * Define cookie
     */
    public function cookie(string $name, string $value, int $minutes = 60, string $path = '/'): self
    {
        $expire = time() + ($minutes * 60);
        setcookie($name, $value, $expire, $path, '', true, true);
        return $this;
    }

    /**
     * Remove cookie
     */
    public function forgetCookie(string $name, string $path = '/'): self
    {
        setcookie($name, '', time() - 3600, $path);
        return $this;
    }

    /**
     * Envia a resposta
     */
    public function send(): void
    {
        // Define status code
        http_response_code($this->statusCode);

        // Envia headers
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        // Envia conteúdo
        echo $this->content;

        // Finaliza a execução
        exit;
    }

    /**
     * Métodos estáticos para respostas rápidas
     */

    public static function success($data = null, string $message = 'Success'): void
    {
        (new self())->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ])->send();
    }

    public static function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        (new self())->json($response, $statusCode)->send();
    }

    public static function notFound(string $message = 'Resource not found'): void
    {
        (new self())->json([
            'status' => 'error',
            'message' => $message
        ], 404)->send();
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        (new self())->json([
            'status' => 'error',
            'message' => $message
        ], 401)->send();
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        (new self())->json([
            'status' => 'error',
            'message' => $message
        ], 403)->send();
    }

    public static function validationError(array $errors): void
    {
        (new self())->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $errors
        ], 422)->send();
    }
}