<?php

declare(strict_types=1);

namespace Application\Core;

use Application\Config\InfrastructureRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Core\Validation\RequestValidator;

/**
 * @phpstan-type InputBag array<array-key, mixed>
 * @phpstan-type HeaderBag array<string, string>
 * @phpstan-type FileBag array<string, array<string, mixed>>
 * @phpstan-type ParsedBody array{0: InputBag, 1: ?InputBag, 2: ?string}
 */
class Request
{
    /** @var InputBag */
    private readonly array $server;
    /** @var InputBag */
    private readonly array $query;
    /** @var InputBag */
    private readonly array $body;
    /** @var InputBag */
    private readonly array $data;
    /** @var FileBag */
    private readonly array $files;
    private readonly string $method;
    /** @var HeaderBag */
    private readonly array $headers;
    /** @var InputBag|null */
    private readonly ?array $json;
    private readonly ?string $jsonError;
    private readonly string $rawInput;
    private readonly InfrastructureRuntimeConfig $runtimeConfig;

    /**
     * @param InputBag|null $server
     * @param InputBag|null $query
     * @param InputBag|null $post
     * @param FileBag|null $files
     * @param array<array-key, mixed>|null $headers
     */
    public function __construct(
        ?array $server = null,
        ?array $query = null,
        ?array $post = null,
        ?array $files = null,
        ?string $rawInput = null,
        ?array $headers = null,
        ?InfrastructureRuntimeConfig $runtimeConfig = null
    ) {
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, InfrastructureRuntimeConfig::class);
        $this->server = $server ?? self::globalArray('_SERVER');
        $this->method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        $this->headers = $this->normalizeHeaders($headers ?? $this->detectHeaders($this->server));
        $this->files = $files ?? self::globalArray('_FILES');
        $this->rawInput = $rawInput ?? (file_get_contents('php://input') ?: '');

        [$body, $json, $jsonError] = $this->parseBody($post ?? self::globalArray('_POST'));

        $this->query = $query ?? self::globalArray('_GET');
        $this->body = $body;
        $this->json = $json;
        $this->jsonError = $jsonError;
        $this->data = array_merge($this->query, $this->body);
    }

    /**
     * @return InputBag
     */
    private static function globalArray(string $key): array
    {
        $value = $GLOBALS[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * @param InputBag $post
     * @return ParsedBody
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
     * @return ParsedBody
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
            return [[], null, 'JSON inválido na requisição.'];
        }

        return [[], null, null];
    }

    /**
     * @param InputBag $post
     * @return InputBag
     */
    private function parseFormBody(array $post): array
    {
        if ($this->isPost()) {
            return $post;
        }

        parse_str($this->rawInput, $parsed);

        return $parsed;
    }

    /**
     * @param InputBag $server
     * @return HeaderBag
     */
    private function detectHeaders(array $server): array
    {
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

        if ($headers !== []) {
            return $headers;
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers) && $headers !== []) {
                return $this->normalizeHeaders($headers);
            }
        }

        return [];
    }

    /**
     * @param array<array-key, mixed> $headers
     * @return HeaderBag
     */
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

    public function server(?string $key = null, mixed $default = null): mixed
    {
        return $this->valueFromBag($this->server, $key, $default);
    }

    public function uri(string $default = '/'): string
    {
        $uri = $this->server('REQUEST_URI', $default);

        return is_string($uri) && $uri !== '' ? $uri : $default;
    }

    /**
     * @return HeaderBag
     */
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

    /**
     * @return InputBag
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * @param array<array-key, array-key> $keys
     * @return InputBag
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }

    /**
     * @param array<array-key, array-key> $keys
     * @return InputBag
     */
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

    /**
     * @return array<string, mixed>|null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * @return InputBag|null
     */
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

    /**
     * @param InputBag $default
     * @return InputBag
     */
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

    /**
     * @param InputBag $default
     * @return InputBag
     */
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

    /**
     * @param InputBag $default
     * @return InputBag
     */
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

    /**
     * @param InputBag $default
     * @return InputBag
     */
    public function arrayValue(string $key, array $default = []): array
    {
        return $this->inputArray($key, $default);
    }

    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function validate(array $rules, array $filters = []): array
    {
        return ApplicationContainer::resolveOrNew(null, RequestValidator::class)
            ->validate($this->data, $rules, $filters);
    }

    public function ip(): string
    {
        $remoteAddr = $this->normalizeIpCandidate($this->server['REMOTE_ADDR'] ?? null) ?? '0.0.0.0';

        $trustedProxies = $this->runtimeConfig->trustedProxies();

        if (!empty($trustedProxies) && in_array($remoteAddr, $trustedProxies, true)) {
            $fallbackIp = null;
            $forwardedHeaders = [
                'HTTP_CF_CONNECTING_IP',
                'HTTP_X_REAL_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_CLIENT_IP',
            ];

            foreach ($forwardedHeaders as $key) {
                if (!empty($this->server[$key])) {
                    foreach (explode(',', $this->server[$key]) as $ip) {
                        $candidate = $this->normalizeIpCandidate($ip);
                        if ($candidate === null) {
                            continue;
                        }

                        if (!$this->isPrivateOrReservedIp($candidate)) {
                            return $candidate;
                        }

                        $fallbackIp ??= $candidate;
                    }
                }
            }

            if ($fallbackIp !== null) {
                return $fallbackIp;
            }
        }

        return $remoteAddr;
    }

    private function normalizeIpCandidate(mixed $value): ?string
    {
        $ip = trim((string) $value);

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    private function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * @param InputBag $bag
     */
    private function valueFromBag(array $bag, ?string $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $bag;
        }

        return $bag[$key] ?? $default;
    }

    /**
     * @param InputBag $bag
     */
    private function stringFromBag(array $bag, string $key, string $default): string
    {
        return $this->normalizeString($this->valueFromBag($bag, $key), $default);
    }

    /**
     * @param InputBag $bag
     */
    private function intFromBag(array $bag, string $key, int $default): int
    {
        return $this->normalizeInt($this->valueFromBag($bag, $key), $default);
    }

    /**
     * @param InputBag $bag
     */
    private function boolFromBag(array $bag, string $key, bool $default): bool
    {
        return $this->normalizeBool($this->valueFromBag($bag, $key), $default);
    }

    /**
     * @param InputBag $bag
     * @param InputBag $default
     * @return InputBag
     */
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
