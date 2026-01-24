<?php

namespace Application\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Application\Services\CircuitBreakerService;

class AsaasService
{
    private Client $client;
    private string $apiKey;
    private ?string $webhookToken;
    private string $baseUrl;
    private string $userAgent;
    private CircuitBreakerService $circuitBreaker;

    public function __construct()
    {
        // Pega primeiro de $_ENV (phpdotenv), se não tiver cai pro getenv()
        $this->apiKey       = $_ENV['ASAAS_API_KEY']       ?? getenv('ASAAS_API_KEY')       ?: '';
        $this->baseUrl      = $_ENV['ASAAS_BASE_URL']      ?? getenv('ASAAS_BASE_URL')      ?: 'https://sandbox.asaas.com/api/v3';
        $this->userAgent    = $_ENV['ASAAS_USER_AGENT']    ?? getenv('ASAAS_USER_AGENT')    ?: 'Lukrato/1.0 (PHP)';
        $this->webhookToken = $_ENV['ASAAS_WEBHOOK_TOKEN'] ?? getenv('ASAAS_WEBHOOK_TOKEN') ?: null;

        if (empty($this->apiKey)) {
            throw new \RuntimeException('ASAAS_API_KEY não configurada no .env');
        }

        $this->client = new Client([
            'base_uri'    => rtrim($this->baseUrl, '/') . '/',
            'timeout'     => 10,
            'http_errors' => false,
            'headers'     => [
                'Content-Type' => 'application/json',
                'User-Agent'   => $this->userAgent,
                'access_token' => $this->apiKey,
            ],
        ]);

        // ✅ Inicializar Circuit Breaker
        $this->circuitBreaker = new CircuitBreakerService('asaas');
    }


    /**
     * Método core para chamadas à API do Asaas.
     *
     * @param string $method GET|POST|PUT|DELETE
     * @param string $uri    Ex: 'customers', 'subscriptions/{id}'
     * @param array|null $body  Corpo JSON (se houver)
     *
     * @return array Decodificado do JSON do Asaas
     */
    private function request(string $method, string $uri, ?array $body = null): array
    {
        // ✅ Usar Circuit Breaker para proteger contra falhas
        return $this->circuitBreaker->execute(function () use ($method, $uri, $body) {
            $options = [];

            if (!empty($body)) {
                $options['json'] = $body;
            }

            try {
                $response = $this->client->request($method, ltrim($uri, '/'), $options);
            } catch (GuzzleException $e) {
                // Se você tiver LogService, pode logar aqui
                if (class_exists(LogService::class)) {
                    LogService::error('Erro HTTP ao chamar Asaas', [
                        'exception' => $e->getMessage(),
                        'uri'       => $uri,
                        'method'    => $method,
                        'body'      => $body,
                    ]);
                }

                throw new \RuntimeException('Falha ao comunicar com o Asaas. Tente novamente em instantes.');
            }

            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $data       = json_decode($rawBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                if (class_exists(LogService::class)) {
                    LogService::error('Resposta inválida do Asaas (JSON)', [
                        'status' => $statusCode,
                        'body'   => $rawBody,
                    ]);
                }

                throw new \RuntimeException('Resposta inesperada do Asaas. Tente novamente em instantes.');
            }

            if ($statusCode >= 200 && $statusCode < 300) {
                return $data ?? [];
            }

            // Formato de erro padrão do Asaas
            $message = $data['errors'][0]['description']
                ?? $data['message']
                ?? 'Erro desconhecido na API do Asaas';

            if (class_exists(LogService::class)) {
                LogService::error('Erro retornado do Asaas', [
                    'status'  => $statusCode,
                    'message' => $message,
                    'uri'     => $uri,
                    'method'  => $method,
                ]);
            }

            throw new \RuntimeException($message);
        });
    }

    /**
     * Cria um cliente no Asaas.
     * Ideal para vincular ao usuário do Lukrato (user_id).
     *
     * @param array $data [
     *   'name' => string,
     *   'email' => string|null,
     *   'mobilePhone' => string|null,
     *   'cpfCnpj' => string|null,
     *   'externalReference' => string|null
     * ]
     */
    public function createCustomer(array $data): array
    {
        $payload = [
            'name'              => $data['name'],
            'email'             => $data['email']         ?? null,
            'mobilePhone'       => $data['mobilePhone']   ?? null,
            'cpfCnpj'           => $data['cpfCnpj']       ?? null,
            'externalReference' => $data['externalReference'] ?? null,
        ];

        // Remove nulls e strings vazias
        $payload = array_filter(
            $payload,
            static fn($value) => !is_null($value) && $value !== ''
        );

        return $this->request('POST', 'customers', $payload);
    }

    /**
     * Busca cliente pelo ID do Asaas.
     */
    public function getCustomer(string $customerId): array
    {
        return $this->request('GET', "customers/{$customerId}");
    }

    /**
     * Atualiza um cliente existente no Asaas.
     * 
     * @param string $customerId ID do cliente no Asaas
     * @param array $data Campos a atualizar
     */
    public function updateCustomer(string $customerId, array $data): array
    {
        $payload = array_filter(
            $data,
            static fn($value) => !is_null($value) && $value !== ''
        );

        return $this->request('POST', "customers/{$customerId}", $payload);
    }

    /**
     * Cria uma assinatura recorrente.
     * Aqui você vai usar para o plano Premium do Lukrato.
     *
     * @param array $data [
     *   'customerId' => string (ID Asaas),
     *   'value' => float,
     *   'description' => string,
     *   'billingType' => string (CREDIT_CARD|PIX|BOLETO),
     *   'cycle' => string (WEEKLY|MONTHLY|YEARLY),
     *   'nextDueDate' => string (Y-m-d),
     *   'externalReference' => string|null,
     *   'creditCard' => [...],              // opcional (se billingType = CREDIT_CARD)
     *   'creditCardHolderInfo' => [...]     // opcional
     * ]
     */
    public function createSubscription(array $data): array
    {
        $payload = [
            'customer'          => $data['customerId'],
            'billingType'       => $data['billingType']       ?? 'CREDIT_CARD',
            'value'             => $data['value'],
            'description'       => $data['description']       ?? null,
            'cycle'             => $data['cycle']             ?? 'MONTHLY',
            'nextDueDate'       => $data['nextDueDate']       ?? date('Y-m-d'),
            'externalReference' => $data['externalReference'] ?? null,
        ];

        if (!empty($data['creditCard']) && !empty($data['creditCardHolderInfo'])) {
            $payload['creditCard']          = $data['creditCard'];
            $payload['creditCardHolderInfo'] = $data['creditCardHolderInfo'];
        }

        $payload = array_filter(
            $payload,
            static fn($value) => !is_null($value)
        );

        return $this->request('POST', 'subscriptions', $payload);
    }

    /**
     * Busca uma assinatura específica.
     */
    public function getSubscription(string $subscriptionId): array
    {
        return $this->request('GET', "subscriptions/{$subscriptionId}");
    }

    /**
     * Cancela uma assinatura.
     * No Lukrato, você pode usar para o "Cancelar plano".
     */
    public function cancelSubscription(string $subscriptionId): array
    {
        return $this->request('DELETE', "subscriptions/{$subscriptionId}");
    }

    /**
     * Cria um pagamento avulso (se quiser usar Pix avulso ou boleto, por exemplo).
     *
     * @param array $data [
     *   'customerId' => string,
     *   'value' => float,
     *   'billingType' => string (PIX|BOLETO|CREDIT_CARD),
     *   'dueDate' => string (Y-m-d),
     *   'description' => string|null,
     *   'externalReference' => string|null
     * ]
     */
    public function createPayment(array $data): array
    {
        // Aceita tanto 'customer' quanto 'customerId' para compatibilidade
        $customerId = $data['customer'] ?? $data['customerId'] ?? null;

        if (empty($customerId)) {
            throw new \RuntimeException('Customer inválido ou não informado.');
        }

        $payload = [
            'customer'          => $customerId,
            'billingType'       => $data['billingType']       ?? 'PIX',
            'value'             => $data['value'],
            'dueDate'           => $data['dueDate']           ?? date('Y-m-d'),
            'description'       => $data['description']       ?? null,
            'externalReference' => $data['externalReference'] ?? null,
        ];

        $payload = array_filter(
            $payload,
            static fn($value) => !is_null($value)
        );

        return $this->request('POST', 'payments', $payload);
    }

    /**
     * Busca informações de um pagamento específico.
     */
    public function getPayment(string $paymentId): array
    {
        return $this->request('GET', "payments/{$paymentId}");
    }

    /**
     * Cancela/exclui um pagamento pendente.
     * 
     * @param string $paymentId ID do pagamento no Asaas
     * @return array Resposta da API
     */
    public function cancelPayment(string $paymentId): array
    {
        return $this->request('DELETE', "payments/{$paymentId}");
    }

    /**
     * Obtém o QR Code PIX de um pagamento.
     * 
     * @param string $paymentId ID do pagamento no Asaas
     * @return array [
     *   'encodedImage' => string (base64 da imagem QR Code),
     *   'payload' => string (código copia-e-cola),
     *   'expirationDate' => string
     * ]
     */
    public function getPixQrCode(string $paymentId): array
    {
        return $this->request('GET', "payments/{$paymentId}/pixQrCode");
    }

    /**
     * Obtém a linha digitável e URL do boleto.
     * 
     * @param string $paymentId ID do pagamento no Asaas
     * @return array [
     *   'identificationField' => string (linha digitável),
     *   'nossoNumero' => string,
     *   'barCode' => string
     * ]
     */
    public function getBoletoIdentificationField(string $paymentId): array
    {
        return $this->request('GET', "payments/{$paymentId}/identificationField");
    }

    /**
     * Valida o token do webhook enviado pelo Asaas.
     * 
     * SEGURANÇA MULTI-CAMADA:
     * 1. Verifica token no header (asaas-access-token)
     * 2. Opcional: Valida assinatura HMAC se configurada
     * 3. Opcional: Valida IP de origem (whitelist)
     * 
     * Configure no .env:
     * - ASAAS_WEBHOOK_TOKEN: Token configurado no painel Asaas
     * - ASAAS_WEBHOOK_SECRET: (Opcional) Secret para HMAC
     * - ASAAS_WEBHOOK_IPS: (Opcional) IPs permitidos separados por vírgula
     */
    public function validateWebhookRequest(array $headers, ?string $rawBody = null): bool
    {
        // ✅ NÍVEL 1: Token simples (obrigatório)
        if (empty($this->webhookToken)) {
            // Sem token configurado = ERRO DE CONFIGURAÇÃO
            if (class_exists(LogService::class)) {
                LogService::critical('ASAAS_WEBHOOK_TOKEN não configurado! Webhooks desprotegidos!');
            }
            // Por segurança, rejeitar se não tiver token configurado
            return false;
        }

        // Normaliza possíveis variações de header
        $keyCandidates = [
            'asaas-access-token',
            'Asaas-Access-Token',
            'ASAAS-ACCESS-TOKEN',
            'X-Asaas-Access-Token',
        ];

        $received = null;

        foreach ($keyCandidates as $key) {
            if (isset($headers[$key])) {
                $received = $headers[$key];
                break;
            }
        }

        if (!is_string($received) || $received === '') {
            if (class_exists(LogService::class)) {
                LogService::warning('Webhook sem token de acesso', [
                    'headers_received' => array_keys($headers),
                ]);
            }
            return false;
        }

        // Validação com hash_equals (timing-attack safe)
        if (!hash_equals($this->webhookToken, $received)) {
            if (class_exists(LogService::class)) {
                LogService::warning('Webhook com token inválido', [
                    'expected_length' => strlen($this->webhookToken),
                    'received_length' => strlen($received),
                ]);
            }
            return false;
        }

        // ✅ NÍVEL 2: Validação HMAC (opcional mas recomendado)
        $webhookSecret = $_ENV['ASAAS_WEBHOOK_SECRET'] ?? getenv('ASAAS_WEBHOOK_SECRET') ?: null;

        if ($webhookSecret && $rawBody !== null) {
            $signatureHeader = $headers['X-Asaas-Signature']
                ?? $headers['x-asaas-signature']
                ?? $headers['Asaas-Signature']
                ?? null;

            if ($signatureHeader) {
                $expectedSignature = hash_hmac('sha256', $rawBody, $webhookSecret);

                if (!hash_equals($expectedSignature, $signatureHeader)) {
                    if (class_exists(LogService::class)) {
                        LogService::warning('Webhook com assinatura HMAC inválida');
                    }
                    return false;
                }
            }
        }

        // ✅ NÍVEL 3: Validação de IP (opcional)
        $allowedIps = $_ENV['ASAAS_WEBHOOK_IPS'] ?? getenv('ASAAS_WEBHOOK_IPS') ?: null;

        if ($allowedIps) {
            $clientIp = $this->getClientIp();
            $whitelist = array_map('trim', explode(',', $allowedIps));

            if (!in_array($clientIp, $whitelist, true)) {
                if (class_exists(LogService::class)) {
                    LogService::warning('Webhook de IP não autorizado', [
                        'ip' => $clientIp,
                        'whitelist' => $whitelist,
                    ]);
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Obtém IP real do cliente (considerando proxies)
     */
    private function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Se for lista, pegar o primeiro
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
