<?php

declare(strict_types=1);

namespace Application\Core;

use Application\Core\Validation\RequestValidator;

class Request
{
    private readonly array $server;
    private readonly array $query;
    private readonly array $body;
    private readonly array $data;
    private readonly array $files;
    private readonly string $method;
    private readonly array $headers;
    private readonly ?array $json;
    private readonly ?string $jsonError;
    private readonly string $rawInput;

    public function __construct(
        ?array $server = null,
        ?array $query = null,
        ?array $post = null,
        ?array $files = null,
        ?string $rawInput = null,
        ?array $headers = null
    ) {
        $this->server = $server ?? $_SERVER ?? [];
        $this->method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        $this->headers = $this->normalizeHeaders($headers ?? $this->detectHeaders($this->server));
        $this->files = $files ?? $_FILES ?? [];
        $this->rawInput = $rawInput ?? (file_get_contents('php://input') ?: '');

        $this->parseData($query ?? $_GET ?? [], $post ?? $_POST ?? []);
    }

    private function parseData(array $query, array $post): void
    {
        $this->query = $query;

        [$body, $json, $jsonError] = $this->parseBody($post);

        $this->body = $body;
        $this->json = $json;
        $this->jsonError = $jsonError;
        $this->data = array_merge($this->query, $this->body);
    }

    /**
     * @return array{0: array, 1: ?array, 2: ?string}
     */
    private function parseBody(array $post): array
    {
        $contentType = $this->contentType();

        if (str_contains($contentType, 'application/json')) {
            return $this->parseJsonBody();
        }

        if (
            str_contains($contentType, 'application/x-www-form-urlencoded')
            || str_contains($contentType, 'multipart/form-data')
        ) {
            return [$this->parseFormBody($post), null, null];
        }

        if ($this->isPost()) {
            return [$post, null, null];
        }

        return [[], null, null];
    }

    /**
     * @return array{0: array, 1: ?array, 2: ?string}
     */
    private function parseJsonBody(): array
    {
        if ($this->rawInput === '') {
            return [[], null, null];
        }

        try {
            $decoded = json_decode($this->rawInput, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($decoded)) {
                return [$decoded, $decoded, null];
            }
        } catch (\JsonException) {
            return [[], null, 'JSON invalido na requisicao.'];
        }

        return [[], null, null];
    }

    private function parseFormBody(array $post): array
    {
        if ($this->isPost()) {
            return $post;
        }

        parse_str($this->rawInput, $parsed);

        return is_array($parsed) ? $parsed : [];
    }

    private function detectHeaders(array $server): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers) && $headers !== []) {
                return $headers;
            }
        }

        $headers = [];

        foreach ($server as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = (string) $value;
                continue;
            }

            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $header = str_replace('_', '-', $key);
                $headers[$header] = (string) $value;
            }
        }

        return $headers;
    }

    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $key => $value) {
            $normalized[strtolower((string) $key)] = (string) $value;
        }

        return $normalized;
    }

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
        if (strtolower($this->header('x-requested-with') ?? '') === 'xmlhttprequest') {
            return true;
        }

        return $this->wantsJson();
    }

    public function header(string $key): ?string
    {
        $normalizedKey = strtolower(str_replace('_', '-', $key));

        return $this->headers[$normalizedKey] ?? null;
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
        $authorization = $this->header('authorization');
        if ($authorization && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('accept') ?? '';

        return str_contains($accept, 'application/json') || str_contains($accept, '+json');
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $this->valueFromBag($this->query, $key, $default);
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        return $this->valueFromBag($this->body, $key, $default);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        return $this->valueFromBag($this->data, $key, $default);
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

    public function json(): ?array
    {
        return $this->json;
    }

    public function hasJsonError(): bool
    {
        return $this->jsonError !== null;
    }

    public function jsonError(): ?string
    {
        return $this->jsonError;
    }

    public function rawInput(): string
    {
        return $this->rawInput;
    }

    public function queryString(string $key, string $default = ''): string
    {
        return $this->stringFromBag($this->query, $key, $default);
    }

    public function queryInt(string $key, int $default = 0): int
    {
        return $this->intFromBag($this->query, $key, $default);
    }

    public function queryBool(string $key, bool $default = false): bool
    {
        return $this->boolFromBag($this->query, $key, $default);
    }

    public function queryArray(string $key, array $default = []): array
    {
        return $this->arrayFromBag($this->query, $key, $default);
    }

    public function postString(string $key, string $default = ''): string
    {
        return $this->stringFromBag($this->body, $key, $default);
    }

    public function postInt(string $key, int $default = 0): int
    {
        return $this->intFromBag($this->body, $key, $default);
    }

    public function postBool(string $key, bool $default = false): bool
    {
        return $this->boolFromBag($this->body, $key, $default);
    }

    public function postArray(string $key, array $default = []): array
    {
        return $this->arrayFromBag($this->body, $key, $default);
    }

    public function inputString(string $key, string $default = ''): string
    {
        return $this->stringFromBag($this->data, $key, $default);
    }

    public function inputInt(string $key, int $default = 0): int
    {
        return $this->intFromBag($this->data, $key, $default);
    }

    public function inputBool(string $key, bool $default = false): bool
    {
        return $this->boolFromBag($this->data, $key, $default);
    }

    public function inputArray(string $key, array $default = []): array
    {
        return $this->arrayFromBag($this->data, $key, $default);
    }

    public function stringValue(string $key, string $default = ''): string
    {
        return $this->inputString($key, $default);
    }

    public function intValue(string $key, int $default = 0): int
    {
        return $this->inputInt($key, $default);
    }

    public function boolValue(string $key, bool $default = false): bool
    {
        return $this->inputBool($key, $default);
    }

    public function arrayValue(string $key, array $default = []): array
    {
        return $this->inputArray($key, $default);
    }

    public function validate(array $rules, array $filters = []): array
    {
        return (new RequestValidator())->validate($this->data, $rules, $filters);
    }

    public function ip(): string
    {
        $remoteAddr = $this->server['REMOTE_ADDR'] ?? '0.0.0.0';

        $trustedProxies = array_filter(array_map(
            'trim',
            explode(',', $_ENV['TRUSTED_PROXIES'] ?? '')
        ));

        if (!empty($trustedProxies) && in_array($remoteAddr, $trustedProxies, true)) {
            $forwardedHeaders = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP'];
            foreach ($forwardedHeaders as $key) {
                if (!empty($this->server[$key])) {
                    foreach (explode(',', $this->server[$key]) as $ip) {
                        $ip = trim($ip);
                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                            return $ip;
                        }
                    }
                }
            }
        }

        return $remoteAddr;
    }

    private function valueFromBag(array $bag, ?string $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $bag;
        }

        return $bag[$key] ?? $default;
    }

    private function stringFromBag(array $bag, string $key, string $default): string
    {
        return $this->normalizeString($this->valueFromBag($bag, $key), $default);
    }

    private function intFromBag(array $bag, string $key, int $default): int
    {
        return $this->normalizeInt($this->valueFromBag($bag, $key), $default);
    }

    private function boolFromBag(array $bag, string $key, bool $default): bool
    {
        return $this->normalizeBool($this->valueFromBag($bag, $key), $default);
    }

    private function arrayFromBag(array $bag, string $key, array $default): array
    {
        $value = $this->valueFromBag($bag, $key);

        return is_array($value) ? $value : $default;
    }

    private function normalizeString(mixed $value, string $default): string
    {
        if ($value === null) {
            return $default;
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return $default;
    }

    private function normalizeInt(mixed $value, int $default): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    private function normalizeBool(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $normalized = mb_strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'yes', 'sim', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'nao', 'off', ''], true)) {
                return false;
            }
        }

        return $default;
    }
}
