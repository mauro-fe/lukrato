<?php

declare(strict_types=1);

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

    public function jsonBody(mixed $data, int $statusCode = 200): self
    {
        $this->statusCode = $statusCode;
        $this->header('Content-Type', 'application/json; charset=utf-8');
        try {
            // JSON_THROW_ON_ERROR (PHP 7.3+) garante que erros de encoding (ex: UTF-8 inválido) sejam capturados
            $this->content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            // Se falhar, retorna um erro JSON válido
            $this->statusCode = 500;
            $this->content = json_encode([
                'status' => 'error', 
                'message' => 'JSON encoding error: ' . $e->getMessage()
            ]);
        }
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
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException("Arquivo não encontrado ou sem permissão: {$filePath}");
        }

        $fileName = $fileName ?: basename($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $this->header('Content-Type', $mimeType);
        $this->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $this->header('Content-Length', (string)filesize($filePath));
        
        // Limpa o buffer de saída para evitar corrupção de arquivo
        if (ob_get_level() > 0) ob_end_clean(); 
        
        readfile($filePath);
        $this->content = ''; // O conteúdo foi enviado por readfile
        
        return $this;
    }

    /**
     * Define um cookie seguro (HttpOnly, Secure, SameSite=Lax).
     * Usa o formato de array (PHP 7.3+).
     */
    public function cookie(string $name, string $value, int $minutes = 60, string $path = '/'): self
    {
        setcookie($name, $value, [
            'expires'  => time() + ($minutes * 60),
            'path'     => $path,
            'secure'   => true,  // Sempre true para HTTPS
            'httponly' => true,  // Não acessível por JS
            'samesite' => 'Lax', // Proteção CSRF
        ]);
        return $this;
    }

    /**
     * Remove um cookie.
     */
    public function forgetCookie(string $name, string $path = '/'): self
    {
        setcookie($name, '', [
            'expires'  => time() - 3600, // No passado
            'path'     => $path,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        return $this;
    }

    /**
     * Envia a resposta ao navegador e encerra a execução.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $key => $value) {
                header("{$key}: {$value}");
            }
        }

        echo $this->content;
        exit;
    }

    // --- Métodos Estáticos (Helpers) ---

    public static function json(mixed $data, int $statusCode = 200): void
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

    // --- Respostas JSON Padronizadas ---

    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        self::json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    public static function error(string $message, int $statusCode = 400, mixed $errors = null): void
    {
        $payload = ['status' => 'error', 'message' => $message];
        if ($errors !== null) $payload['errors'] = $errors;
        self::json($payload, $statusCode);
    }

    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    public static function validationError(array $errors): void
    {
        self::error('Validation failed', 422, $errors);
    }
}