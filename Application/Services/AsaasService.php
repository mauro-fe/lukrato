<?php

namespace Application\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AsaasService
{
    private Client $client;
    private string $apiKey;
    private ?string $webhookToken;
    private string $baseUrl;
    private string $userAgent;

    public function __construct()
    {
        $this->apiKey       = getenv('ASAAS_API_KEY') ?: '';
        $this->baseUrl      = getenv('ASAAS_BASE_URL') ?: 'https://sandbox.asaas.com/api/v3';
        $this->userAgent    = getenv('ASAAS_USER_AGENT') ?: 'Lukrato/1.0 (PHP)';
        $this->webhookToken = getenv('ASAAS_WEBHOOK_TOKEN') ?: null;

        if (empty($this->apiKey)) {
            // Aqui é erro de configuração, não de runtime
            throw new \RuntimeException('ASAAS_API_KEY não configurada no .env');
        }

        $this->client = new Client([
            'base_uri'    => rtrim($this->baseUrl, '/') . '/',
            'timeout'     => 10,
            'http_errors' => false, // vamos tratar manualmente
            'headers'     => [
                'Content-Type' => 'application/json',
                'User-Agent'   => $this->userAgent,
                'access_token' => $this->apiKey,
            ],
        ]);
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
        $options = [];

        if (!empty($body)) {
            $options['json'] = $body;
        }

        try {
            $response = $this->client->request($method, ltrim($uri, '/'), $options);
        } catch (GuzzleException $e) {
            // Se você tiver LogService, pode logar aqui
            if (class_exists(\Application\Services\LogService::class)) {
                \Application\Services\LogService::error('Erro HTTP ao chamar Asaas', [
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
            if (class_exists(\Application\Services\LogService::class)) {
                \Application\Services\LogService::error('Resposta inválida do Asaas (JSON)', [
                    'status' => $statusCode,
                    'body'   => $rawBody,
                ]);
            }

            throw new \RuntimeException('Resposta inesperada do Asaas. Tente novamente em instantes.');
        }

        if ($statusCode >= 200 && $statusCode < 300) {
            return $data ?? [];
        }

        // Formato de erro padrão do Asaas:
        // {
        //   "errors": [
        //       { "code": "invalid_parameter", "description": "Mensagem..." }
        //   ]
        // }
        $message = $data['errors'][0]['description']
            ?? $data['message']
            ?? 'Erro inesperado ao comunicar com o Asaas.';

        if (class_exists(\Application\Services\LogService::class)) {
            \Application\Services\LogService::warning('Erro de API do Asaas', [
                'status'   => $statusCode,
                'uri'      => $uri,
                'method'   => $method,
                'request'  => $body,
                'response' => $data,
            ]);
        }

        throw new \RuntimeException($message, $statusCode);
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
        $payload = [
            'customer'          => $data['customerId'],
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
     * Valida o token do webhook enviado pelo Asaas.
     * Configure o mesmo token no painel deles e no ASAAS_WEBHOOK_TOKEN.
     */
    public function validateWebhookRequest(array $headers): bool
    {
        if (empty($this->webhookToken)) {
            // Sem token configurado, não faz validação
            return true;
        }

        // Normaliza possíveis variações de header
        $keyCandidates = [
            'asaas-access-token',
            'Asaas-Access-Token',
            'ASAAS-ACCESS-TOKEN',
        ];

        $received = null;

        foreach ($keyCandidates as $key) {
            if (isset($headers[$key])) {
                $received = $headers[$key];
                break;
            }
        }

        if (!is_string($received)) {
            return false;
        }

        return hash_equals($this->webhookToken, $received);
    }
}
