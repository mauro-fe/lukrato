<?php

namespace Application\Core;

use Application\Lib\Helpers;
use Application\Core\Exceptions\ValidationException;

class Request
{
    /** Dados apenas da query string (GET) */
    private array $query = [];
    /** Dados apenas do corpo (POST/JSON/PUT/PATCH/DELETE) */
    private array $body = [];
    /** Dados mesclados (query + body) */
    private array $data = [];
    /** Arquivos */
    private array $files = [];
    /** Método HTTP */
    private string $method;
    /** Cabeçalhos normalizados (lowercase) */
    private array $headers = [];
    /** Cache do JSON decodificado (se houver) */
    private ?array $json = null;

    public function __construct()
    {
        $this->method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->headers = $this->normalizeHeaders(getallheaders() ?: []);
        $this->files   = $_FILES ?? [];

        $this->parseData();
    }

    // ==========================
    // Básico
    // ==========================
    public function method(): string
    {
        return $this->method;
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }
    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }
    public function isPatch(): bool
    {
        return $this->method === 'PATCH';
    }
    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }

    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    // ==========================
    // Headers
    // ==========================
    public function header(string $key): ?string
    {
        $k = strtolower(str_replace('_', '-', $key));
        return $this->headers[$k] ?? null;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function contentType(): string
    {
        return $this->header('content-type') ?? '';
    }

    public function bearerToken(): ?string
    {
        $h = $this->header('authorization');
        if (!$h) return null;
        if (preg_match('/^Bearer\s+(.+)$/i', $h, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('accept') ?? '';
        return stripos($accept, 'application/json') !== false;
    }

    // ==========================
    // Dados (GET/POST/JSON)
    // ==========================

    /**
     * Dados SOMENTE da query string (GET)
     */
    public function query(string $key = null, $default = null)
    {
        if ($key === null) return $this->query;
        return $this->query[$key] ?? $default;
    }

    /**
     * Dados SOMENTE do corpo (POST/PUT/PATCH/DELETE form/json)
     */
    public function post(string $key = null, $default = null)
    {
        if ($key === null) return $this->body;
        return $this->body[$key] ?? $default;
    }

    /**
     * Dados mesclados (query + body) – equivalente ao que você chamava de get()
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Alias conveniente: sem argumentos retorna tudo; com chave, retorna o valor.
     */
    public function input(string $key = null, $default = null)
    {
        if ($key === null) return $this->data;
        return $this->data[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->data, array_flip($keys));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function filled(string $key): bool
    {
        return $this->has($key) && $this->data[$key] !== '' && $this->data[$key] !== null;
    }

    // ==========================
    // Arquivos
    // ==========================
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    // ==========================
    // JSON
    // ==========================
    /**
     * Retorna corpo JSON decodificado (array) se Content-Type JSON, senão null.
     */
    public function json(): ?array
    {
        return $this->json;
    }

    // ==========================
    // Validação (GUMP)
    // ==========================
    public function validate(array $rules, array $filters = []): array
    {
        $gump = new \GUMP();

        $gump->add_validator("cpf_cnpj", function ($field, $input, $param = null) {
            $value = preg_replace('/\D/', '', $input[$field] ?? '');
            if (strlen($value) === 11) return Helpers::isValidCpf($value);
            if (strlen($value) === 14) return Helpers::isValidCnpj($value);
            return false;
        }, 'O campo {field} deve conter um CPF ou CNPJ válido.');

        if (!empty($filters)) $gump->filter_rules($filters);
        $gump->validation_rules($rules);

        $validated = $gump->run($this->data);
        if ($validated === false) {
            throw new ValidationException($gump->get_errors_array());
        }
        return $validated;
    }

    // ==========================
    // Utilidades
    // ==========================
    /** Sanitização para SAÍDA (não para entrada) */
    public function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    public function ip(): string
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // ==========================
    // Internos
    // ==========================
    private function parseData(): void
    {
        // Query sempre vem do GET
        $this->query = $_GET ?? [];

        // Corpo: POST/PUT/PATCH/DELETE
        $this->body = [];
        $ct = $this->contentType();

        // JSON
        if (stripos($ct, 'application/json') !== false) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $this->json = $decoded;
                $this->body = $decoded;
            }
        }
        // Form-data ou x-www-form-urlencoded
        elseif (stripos($ct, 'application/x-www-form-urlencoded') !== false || stripos($ct, 'multipart/form-data') !== false) {
            if ($this->isPost()) {
                $this->body = $_POST ?? [];
            } else {
                // PUT/PATCH/DELETE com form-encoded
                $raw = file_get_contents('php://input') ?: '';
                $parsed = [];
                parse_str($raw, $parsed);
                if (is_array($parsed)) $this->body = $parsed;
            }
        }
        // Sem content-type ou métodos simples: tenta POST padrão
        else {
            if ($this->isPost()) $this->body = $_POST ?? [];
        }

        // Mescla final (query tem precedência menor que body)
        $this->data = array_merge($this->query, $this->body);
    }

    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $k => $v) {
            $normalized[strtolower($k)] = $v;
        }
        return $normalized;
    }
}
