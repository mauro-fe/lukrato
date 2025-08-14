<?php

namespace Application\Core;

use Application\Lib\Helpers;
use Application\Core\Exceptions\ValidationException;



class Request
{
    private array $data = [];
    private array $files = [];
    private string $method;
    private array $headers = [];

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->parseData();
        $this->files = $_FILES ?? [];
        $this->headers = getallheaders() ?: [];
    }

    /**
     * Retorna o método HTTP da requisição (GET, POST, etc.)
     */
    public function method(): string // <--- MÉTODO ADICIONADO AQUI
    {
        return $this->method;
    }

    /**
     * Parse dados da requisição
     */
    private function parseData(): void
    {
        // GET data
        $this->data = $_GET ?? [];

        // POST data
        if ($this->isPost()) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (strpos($contentType, 'application/json') !== false) {
                // JSON data
                $json = file_get_contents('php://input');
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    $this->data = array_merge($this->data, $decoded);
                }
            } else {
                // Form data
                $this->data = array_merge($this->data, $_POST ?? []);
            }
        }
    }

    /**
     * Pega valor do request (GET ou POST)
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Pega todos os dados da requisição
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Pega apenas campos específicos do request
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }

    /**
     * Pega todos os dados exceto campos específicos
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->data, array_flip($keys));
    }

    /**
     * Verifica se tem um campo no request
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Verifica se campo está preenchido (não vazio)
     */
    public function filled(string $key): bool
    {
        return $this->has($key) && !empty($this->data[$key]);
    }

    /**
     * Pega arquivo enviado
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Verifica se tem arquivo com sucesso
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Métodos de verificação de tipo de requisição
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }

    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }

    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Pega um header da requisição
     */
    public function header(string $key): ?string
    {
        $key = str_replace('_', '-', strtolower($key));
        // getallheaders() retorna chaves em Pascal-Case ou similar, normalizamos para comparar
        $headersNormalized = array_change_key_case($this->headers, CASE_LOWER);
        return $headersNormalized[$key] ?? null;
    }

    /**
     * Valida dados com GUMP
     */
    public function validate(array $rules, array $filters = []): array
    {
        $gump = new \GUMP();

        // Validador customizado para CPF ou CNPJ
        $gump->add_validator("cpf_cnpj", function ($field, $input, $param = null) {
            $value = preg_replace('/\D/', '', $input[$field] ?? '');

            if (strlen($value) === 11) {
                return Helpers::isValidCpf($value);
            }

            if (strlen($value) === 14) {
                return Helpers::isValidCnpj($value);
            }

            return false;
        }, 'O campo {field} deve conter um CPF ou CNPJ válido.');

        if (!empty($filters)) {
            $gump->filter_rules($filters);
        }

        $gump->validation_rules($rules);

        $validated = $gump->run($this->data);

        if ($validated === false) {
            throw new ValidationException($gump->get_errors_array());
        }

        return $validated;
    }


    /**
     * Sanitiza string (APENAS PARA SAÍDA HTML/JS) - Este método deve ser usado com cautela,
     * pois a sanitização para segurança XSS é melhor na view.
     * Para sanitização de INPUT, confie nos filtros do GUMP.
     */
    public function sanitize(string $value): string // <--- Sugerido para REMOVER ou REVER o uso
    {
        // Se for mantido, é para sanitização de SAÍDA.
        // O método original do Helpers::sanitize é mais adequado para esta finalidade.
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Pega IP do cliente
     */
    public function ip(): string
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);

                    if (filter_var(
                        $ip,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                    ) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

