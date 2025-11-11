<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\LogService;
use Application\Services\MercadoPagoService;
use Illuminate\Database\Capsule\Manager as DB;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use Throwable;
use Carbon\Carbon;

// --- Enums para Constantes (PHP 8.1+) ---

/**
 * Define os status de pagamento relevantes do Mercado Pago.
 */
enum PaymentStatus: string
{
    case APPROVED = 'approved';
    // Outros status podem ser adicionados aqui (pending, rejected, etc.)
}

/**
 * Define os tipos de plano do usuário.
 */
enum UserPlan: string
{
    case PRO = 'pro';
    case FREE = 'free';
}

class WebhookMercadoPagoController extends BaseController
{
    /**
     * Tenta extrair o ID do usuário dos dados do pagamento (Metadados ou Ref. Externa).
     * * @param \MercadoPago\Resources\Payment $payment O objeto de pagamento retornado pelo SDK do MP.
     * @return int|null
     */
    private function extractUserIdFromPayment(\MercadoPago\Resources\Payment $payment): ?int
    {
        // 1. Tenta pelos Metadados (que podem ser objeto ou array)
        if (isset($payment->metadata)) {
            // O SDK pode retornar metadata como um objeto stdClass
            if (is_object($payment->metadata) && isset($payment->metadata->user_id)) {
                return (int)$payment->metadata->user_id;
            }
            // Ou pode retornar como um array associativo (dependendo da versão/contexto)
            if (is_array($payment->metadata) && isset($payment->metadata['user_id'])) {
                return (int)$payment->metadata['user_id'];
            }
        }

        // 2. Tenta pela Referência Externa (ex: "user_123_...")
        if (!empty($payment->external_reference)) {
            if (preg_match('/user_(\d+)/', (string)$payment->external_reference, $matches)) {
                return (int)$matches[1];
            }
        }

        return null;
    }

    /**
     * Processa a notificação de Webhook recebida do Mercado Pago.
     */
    public function handle(): void
    {
        // 1) Configura o SDK do Mercado Pago
        try {
            MercadoPagoService::configureSdk();
        } catch (Throwable $e) {
            Response::error('MP access token ausente', 500);
            return;
        }

        // 2) Coleta e Loga a Requisição Bruta
        $raw     = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true) ?? [];

        // 3) Identifica o tipo de evento e o ID
        $type = (string)($payload['type'] ?? $payload['action'] ?? ($_GET['topic'] ?? ''));
        $id   = (string)($payload['data']['id'] ?? ($_GET['id'] ?? null));

        LogService::info('Webhook MP recebido', [
            'headers' => function_exists('getallheaders') ? getallheaders() : [],
            'query'   => $_GET,
            'payload' => $payload,
        ]);

        try {
            // 4) Processa apenas eventos de 'payment' que tenham ID
            if ($id !== '' && str_contains(strtolower($type), 'payment')) {
                
                $client  = new PaymentClient();
                $payment = $client->get($id); // Consulta oficial na API do MP

                $userId = $this->extractUserIdFromPayment($payment);

                // 5) Lógica de Negócio: Pagamento Aprovado
                if ($payment->status === PaymentStatus::APPROVED->value && $userId !== null) {
                    
                    DB::connection()->transaction(function () use ($userId, $payment) {
                        /** @var Usuario|null $user */
                        $user = Usuario::find($userId);
                        if (!$user) {
                            throw new \RuntimeException('Usuário não encontrado para o pagamento ' . $payment->id);
                        }

                        // Verifica se o usuário já tem um plano Pro ativo
                        $renovaEm = $user->plano_renova_em ? strtotime($user->plano_renova_em) : 0;
                        if ($user->plano === UserPlan::PRO->value && $renovaEm > time()) {
                            LogService::info('Webhook MP ignorado (usuário já possui plano PRO vigente)', [
                                'user_id'    => $user->id,
                                'payment_id' => $payment->id,
                            ]);
                            return; // Encerra a transação, mas não dá erro
                        }

                        // Atualiza o plano do usuário
                        $user->plano = UserPlan::PRO->value;
                        $user->plano_renova_em = Carbon::now()->addDays(30)->toDateTimeString();
                        $user->save();
                        
                        // TODO: Persistir o registro do pagamento em uma tabela de pagamentos/faturas
                    });
                }
            }

            // 6) Responde 200 OK para o Mercado Pago (evita reenvios)
            Response::success(['received' => true]);

        } catch (MPApiException $e) {
            $apiResponse = $e->getApiResponse();
            $statusCode  = $apiResponse?->getStatusCode() ?? 400;
            $content     = $apiResponse?->getContent();

            LogService::error('Erro MPApiException no webhook', [
                'status'  => $statusCode,
                'content' => $content,
            ]);
            Response::error('Erro no webhook (MP)', 500); 

        } catch (Throwable $e) {
            LogService::error('Exceção no webhook MP', ['ex' => $e->getMessage()]);
            Response::error('Erro no processamento do webhook: ' . $e->getMessage(), 500);
        }
    }
}