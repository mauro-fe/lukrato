<?php

namespace Application\Services;

class PagarmeService
{
    private string $apiKey;
    private ?string $planId;

    public function __construct()
    {
        $this->apiKey = $_ENV['PAGARME_API_KEY'] ?? '';
        $this->planId = $_ENV['PAGARME_PLAN_ID'] ?? null;
    }

    /** Executa uma chamada HTTP para a API do Pagar.me */
    private function request(string $method, string $url, array $body = null): array
    {
        $ch = curl_init($url);
        $headers = ['Authorization: Bearer ' . $this->apiKey, 'Content-Type: application/json'];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($resp === false) {
            throw new \RuntimeException('Erro cURL: ' . curl_error($ch));
        }
        curl_close($ch);
        $json = json_decode($resp, true);
        if ($status >= 400) {
            throw new \RuntimeException('Pagar.me HTTP ' . $status . ': ' . $resp);
        }
        return $json ?? [];
    }

    /** Cria/retorna customer no Pagar.me */
    public function ensureCustomer(array $user): array
    {
        if (!empty($user['pagarme_customer_id'])) {
            // opcional: recuperar para checar
            return ['id' => $user['pagarme_customer_id']];
        }

        // !! Ajuste o endpoint/conteúdo conforme sua conta e versão de API do Pagar.me
        $url  = 'https://api.pagar.me/core/v5/customers';
        $body = [
            'name'  => $user['name'] ?? 'Usuário',
            'email' => $user['email'],
            'metadata' => ['app_user_id' => (string)$user['id']]
        ];
        $created = $this->request('POST', $url, $body);
        return $created; // deve conter 'id'
    }

    /** Garante a existência de um plano mensal de R$ 12 (use o ID se já existir) */
    public function ensurePlan(): array
    {
        if ($this->planId) {
            return ['id' => $this->planId];
        }
        // !! Se preferir criar pelo painel, ignore este método e defina PAGARME_PLAN_ID no .env
        $url  = 'https://api.pagar.me/core/v5/plans';
        $body = [
            'name' => 'Sem anúncios (mensal)',
            'amount' => 1200, // 12,00 BRL em centavos
            'currency' => 'BRL',
            'interval' => 'month',
            'interval_count' => 1,
            'billing_type' => 'prepaid', // ajuste conforme necessidade
        ];
        $plan = $this->request('POST', $url, $body);
        return $plan; // deve conter 'id'
    }

    /**
     * Cria uma sessão/checkout para assinatura.
     * Cenário 1: Checkout hospedado do Pagar.me (quando disponível)
     * Cenário 2: Criação de subscription + coleta de pagamento por link/token (ajuste conforme seu fluxo)
     */
    public function createSubscriptionCheckout(array $user, string $returnUrl): array
    {
        $customer = $this->ensureCustomer($user);
        $plan     = $this->ensurePlan();

        // Fluxo A (recomendado): gerar um "checkout" ou "payment link" de assinatura
        // !! Ajustar endpoint/params do seu ambiente:
        $url  = 'https://api.pagar.me/core/v5/checkout_sessions';
        $body = [
            'customer_id' => $customer['id'],
            'subscription' => [
                'plan_id' => $plan['id'],
                'metadata' => ['app_user_id' => (string)$user['id']],
            ],
            'success_url' => $returnUrl . '?success=1',
            'cancel_url'  => $returnUrl . '?canceled=1',
            // Ative métodos disponíveis na sua conta (cartão/Pix/boleto)
            'payment_methods' => ['credit_card', 'pix'], // ajuste
        ];

        $session = $this->request('POST', $url, $body);
        // Espera retornar algo como uma URL de checkout hospedado
        return [
            'checkout_url' => $session['url'] ?? $session['checkout_url'] ?? null,
            'customer_id'  => $customer['id'],
            'plan_id'      => $plan['id']
        ];
    }

    /** (Opcional) Validação de assinatura do webhook */
    public function isValidWebhook(array $headers, string $rawBody): bool
    {
        // Ajuste conforme cabeçalho/assinatura do Pagar.me configurada no painel.
        // Ex.: compare um HMAC enviado no header com seu PAGARME_WEBHOOK_SECRET.
        // Abaixo: fallback para sempre true (troque na produção).
        return true;
    }
}
