<?php

declare(strict_types=1);

namespace Application\Core;

use Application\Lib\Helpers;
use Application\Core\Exceptions\ValidationException;

class Request
{
    private readonly array $query;
    private readonly array $body;
    private readonly array $data;
    private readonly array $files;
    private readonly string $method;
    private readonly array $headers;
    private readonly ?array $json;

    public function __construct()
    {
        $this->method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->headers = $this->normalizeHeaders(getallheaders() ?: []);
        $this->files   = $_FILES ?? [];

        $this->parseData();
    }

    private function parseData(): void
    {
        // 1. Query string (GET)
        $this->query = $_GET ?? [];

        // 2. Body (POST, PUT, PATCH, JSON)
        $body = [];
        $json = null;
        $contentType = $this->contentType();

        // str_contains (PHP 8.0+)
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            if ($raw) {
                try {
                    // JSON_THROW_ON_ERROR (PHP 7.3+)
                    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        $json = $decoded;
                        $body = $decoded;
                    }
                } catch (\JsonException $e) {
                    // JSON malformado, $body continua vazio
                }
            }
        } elseif (str_contains($contentType, 'application/x-www-form-urlencoded') || str_contains($contentType, 'multipart/form-data')) {
            if ($this->isPost()) {
                $body = $_POST ?? [];
            } else {
                // Lida com PUT/PATCH/DELETE usando x-www-form-urlencoded
                $raw = file_get_contents('php://input') ?: '';
                parse_str($raw, $parsed);
                if (is_array($parsed)) $body = $parsed;
            }
        } elseif ($this->isPost()) {
            // Fallback para POST sem content-type
            $body = $_POST ?? [];
        }

        $this->body = $body;
        $this->json = $json;

        // 3. Data (junção de Query e Body)
        // O Body (POST/JSON) tem precedência sobre a Query String (GET)
        $this->data = array_merge($this->query, $this->body);
    }

    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $k => $v) {
            $normalized[strtolower((string)$k)] = (string)$v;
        }
        return $normalized;
    }

    // --- Métodos de Acesso ---

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
        return strtolower($this->header('x-requested-with') ?? '') === 'xmlhttprequest';
    }

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
        if ($h && preg_match('/^Bearer\s+(.+)$/i', $h, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('accept') ?? '';
        return str_contains($accept, 'application/json');
    }

    // --- Acesso a Dados ---

    public function query(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->query;
        return $this->query[$key] ?? $default;
    }

    public function post(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->body;
        return $this->body[$key] ?? $default;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function input(string $key = null, mixed $default = null): mixed
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

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Retorna o corpo JSON decodificado (se o Content-Type for JSON).
     */
    public function json(): ?array
    {
        return $this->json;
    }

    // --- Validação ---

    /**
     * Valida os dados da requisição (query + body).
     * @throws ValidationException
     */
    public function validate(array $rules, array $filters = []): array
    {
        $gump = new \GUMP();

        // Adiciona validadores customizados
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
            throw new ValidationException($gump->get_errors_array(), 'Validation failed', 422);
        }
        return $validated;
    }

    // --- Utilidades ---

    public function ip(): string
    {
        // Ordem de verificação, priorizando proxies confiáveis
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
        // Fallback para o IP da conexão direta
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
