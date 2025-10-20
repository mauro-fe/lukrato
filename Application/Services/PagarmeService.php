<?php

namespace Application\Services;

final class PagarmeService
{
    private string $apiKey;
    private ?string $planId;
    private string $base = 'https://api.pagar.me/core/v5';

    public function __construct()
    {
        $this->apiKey = $_ENV['PAGARME_API_KEY'] ?? '';
        $this->planId = $_ENV['PAGARME_PLAN_ID'] ?? null;
        if (!$this->apiKey) {
            throw new \RuntimeException('PAGARME_API_KEY ausente no .env');
        }
    }

    private function request(string $method, string $path, array $body = null): array
    {
        $url = rtrim($this->base, '/') . $path;
        $ch  = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 60,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        $resp   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Erro cURL: ' . $err);
        }
        curl_close($ch);
        $json = json_decode($resp, true);
        if ($status >= 400) throw new \RuntimeException("Pagar.me HTTP {$status}: " . ($resp ?: ''));
        return is_array($json) ? $json : [];
    }

    /** Garante/retorna customer_id */
    public function ensureCustomer(array $user): array
    {
        if (!empty($user['pagarme_customer_id'])) {
            return ['id' => $user['pagarme_customer_id']];
        }
        $payload = [
            'name'     => $user['name']  ?? ($user['nome'] ?? 'Usuário'),
            'email'    => $user['email'],
            'metadata' => ['app_user_id' => (string)$user['id']],
        ];
        return $this->request('POST', '/customers', $payload); // retorna ['id'=>...]
    }

    /** Garante/retorna plan_id (se não quiser criar por API, defina no .env) */
    public function ensurePlan(): array
    {
        if ($this->planId) return ['id' => $this->planId];
        $payload = [
            'name'           => 'Sem anúncios (mensal)',
            'amount'         => 1200,    // R$ 12,00 em centavos
            'currency'       => 'BRL',
            'interval'       => 'month',
            'interval_count' => 1,
            'billing_type'   => 'prepaid',
        ];
        return $this->request('POST', '/plans', $payload);
    }

    /**
     * Cria assinatura de plano com cartão (tokenizado no front).
     * $card = ['card_token' => 'tok_xxx']  OU  ['id' => 'card_xxx'] (cofre)
     */
    public function createPlanSubscription(array $user, string $planId, array $card): array
    {
        $customer = $this->ensureCustomer($user);
        $payload  = [
            'plan_id'        => $planId,
            'payment_method' => 'credit_card',
            'customer_id'    => $customer['id'],
            'card'           => $card,
            'installments'   => 1,
            'metadata'       => ['app_user_id' => (string)$user['id']],
        ];
        $sub = $this->request('POST', '/subscriptions', $payload);
        return [
            'subscription_id' => $sub['id'] ?? null,
            'customer_id'     => $customer['id'],
        ];
    }

    /** Validação de webhook (HMAC genérico). Ajuste o header se necessário. */
    public function isValidWebhook(array $headers, string $rawBody): bool
    {
        $secret = $_ENV['PAGARME_WEBHOOK_SECRET'] ?? '';
        if (!$secret) return true; // liberar em dev; exija em produção

        $sig = $headers['X-Hub-Signature'] ?? ($headers['X-Signature'] ?? ($headers['x-hub-signature'] ?? ($headers['x-signature'] ?? '')));
        if (!$sig) return false;
        if (stripos($sig, 'sha256=') === 0) $sig = substr($sig, 7);

        $calc = hash_hmac('sha256', $rawBody, $secret);
        return function_exists('hash_equals') ? hash_equals($sig, $calc) : ($sig === $calc);
    }
}
