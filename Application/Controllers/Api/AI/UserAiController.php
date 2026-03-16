<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\DTO\AI\AIRequestDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Models\AiChatMessage;
use Application\Models\AiConversation;
use Application\Models\PendingAiAction;
use Application\Services\AI\AIQuotaService;
use Application\Services\AI\AIService;
use Application\Services\AI\Context\UserContextBuilder;
use Application\Services\AI\ContextCompressor;
use Application\Services\AI\Media\MediaAsset;
use Application\Services\AI\Media\MediaRouterService;
use Application\Services\AI\Media\MediaType;
use Application\Services\AI\Media\ReceiptAnalysisResult;

/**
 * Controller de IA para usuários autenticados.
 * Expõe chat, categorização, análise financeira e gestão de conversas.
 */
class UserAiController extends BaseController
{
    /**
     * POST /api/ai/chat
     *
     * Body: { "message": string }
     */
    public function chat(): void
    {
        $this->requireAuthApi();

        $payload = $this->getRequestPayload();
        $message = trim($payload['message'] ?? '');
        $resolvedInput = $this->resolveIncomingMessage($message);

        if (!$resolvedInput['success']) {
            Response::error($resolvedInput['error'] ?? 'Nao consegui processar o anexo.', 422);
            return;
        }

        $message = $resolvedInput['message'];

        if ($message === '') {
            Response::error('Mensagem não pode ser vazia.', 422);
            return;
        }

        if (mb_strlen($message) > 2000) {
            Response::error('Mensagem muito longa (máximo 2000 caracteres).', 422);
            return;
        }

        try {
            // Coletar contexto scoped ao usuário
            $contextBuilder = new UserContextBuilder();
            $context = $contextBuilder->build($this->userId);
        } catch (\Throwable) {
            $context = [];
        }

        // Comprimir contexto baseado na mensagem
        $context = ContextCompressor::compress($context, $message);

        // Dispatch pela nova pipeline unificada
        $ai = new AIService();
        $request = new AIRequestDTO(
            userId: $this->userId,
            message: $message,
            context: $context,
            channel: AIChannel::WEB,
        );

        $response = $ai->dispatch($request);

        $chatData = [
            'response' => $response->message,
            'intent'   => $response->intent?->value,
            'source'   => $response->source,
            'cached'   => $response->cached,
            'derived_message' => $resolvedInput['derived_message'],
        ];

        if ($response->success) {
            Response::success($chatData);
        } else {
            Response::error($response->message, 503);
        }
    }

    /**
     * POST /api/ai/suggest-category
     *
     * Body: { "description": string }
     */
    public function suggestCategory(): void
    {
        $this->requireAuthApi();

        $payload     = $this->getRequestPayload();
        $description = trim($payload['description'] ?? '');

        if (mb_strlen($description) < 2) {
            Response::error('Descrição muito curta para sugerir categoria.', 422);
            return;
        }

        $ai = new AIService();
        $request = AIRequestDTO::categorize($this->userId, $description);

        $response = $ai->dispatch($request);

        // Normaliza chaves para inglês (frontend espera 'category', 'subcategory', etc.)
        $d = $response->data;
        $categoryData = [
            'category'        => $d['categoria']       ?? null,
            'subcategory'     => $d['subcategoria']    ?? null,
            'category_id'     => $d['categoria_id']    ?? null,
            'subcategory_id'  => $d['subcategoria_id'] ?? null,
            'confidence'      => $d['confidence']      ?? null,
            'source'          => $response->source,
        ];

        if ($response->success) {
            Response::success($categoryData);
        } else {
            Response::error('Não foi possível sugerir categoria.', 422);
        }
    }

    /**
     * POST /api/ai/analyze
     *
     * Body: { "period"?: string }
     */
    public function analyze(): void
    {
        $this->requireAuthApi();

        $payload = $this->getRequestPayload();
        $period  = trim($payload['period'] ?? 'último mês');

        try {
            $contextBuilder = new UserContextBuilder();
            $context = $contextBuilder->build($this->userId);
        } catch (\Throwable) {
            $context = [];
        }

        $ai = new AIService();
        $request = AIRequestDTO::analyze($this->userId, $context, $period);

        $response = $ai->dispatch($request);

        if (!$response->success) {
            Response::error($response->message, 503);
            return;
        }

        $analyzeData = is_array($response->data) ? $response->data : [];
        $analyzeData['source'] = $response->source;
        $analyzeData['cached'] = $response->cached;
        Response::success($analyzeData);
    }

    /**
     * POST /api/ai/extract-transaction
     *
     * Body: { "message": string }
     */
    public function extractTransaction(): void
    {
        $this->requireAuthApi();

        $payload = $this->getRequestPayload();
        $message = trim($payload['message'] ?? '');

        if (mb_strlen($message) < 3) {
            Response::error('Mensagem muito curta.', 422);
            return;
        }

        $ai = new AIService();
        $request = AIRequestDTO::extractTransaction($this->userId, $message);
        // Usar canal WEB (não WhatsApp) quando chamado pela API
        $request = new AIRequestDTO(
            userId: $this->userId,
            message: $message,
            intent: IntentType::EXTRACT_TRANSACTION,
            channel: AIChannel::WEB,
        );

        $response = $ai->dispatch($request);

        if ($response->success) {
            $extractData = is_array($response->data) ? $response->data : [];
            $extractData['source'] = $response->source;
            Response::success($extractData, $response->message);
        } else {
            Response::error($response->message, 422);
        }
    }

    /**
     * @return array{success:bool,message:string,derived_message:?string,error?:string}
     */
    private function resolveIncomingMessage(string $message): array
    {
        $message = trim($message);

        if (!$this->request->hasFile('attachment')) {
            return [
                'success' => true,
                'message' => $message,
                'derived_message' => null,
            ];
        }

        $file = $this->request->file('attachment');
        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_file($tmpName)) {
            return [
                'success' => false,
                'message' => $message,
                'derived_message' => null,
                'error' => 'Arquivo enviado e invalido ou nao pode ser lido.',
            ];
        }

        $content = file_get_contents($tmpName);
        if (!is_string($content) || $content === '') {
            return [
                'success' => false,
                'message' => $message,
                'derived_message' => null,
                'error' => 'Nao consegui ler o arquivo enviado.',
            ];
        }

        $asset = new MediaAsset(
            sourceType: 'document',
            content: $content,
            mimeType: $file['type'] ?? null,
            filename: $file['name'] ?? null,
            fileSize: isset($file['size']) ? (int) $file['size'] : null,
            caption: $message !== '' ? $message : null,
        );

        if ($asset->mediaType() === MediaType::VIDEO) {
            return [
                'success' => false,
                'message' => $message,
                'derived_message' => null,
                'error' => 'Videos nao sao suportados. Envie imagem, PDF ou audio.',
            ];
        }

        $result = (new MediaRouterService())->process($asset);
        if ($result->isUnsupported()) {
            return [
                'success' => false,
                'message' => $message,
                'derived_message' => null,
                'error' => $result->error ?? 'Tipo de arquivo nao suportado. Envie imagem, PDF ou audio.',
            ];
        }

        if (!$result->success) {
            return [
                'success' => false,
                'message' => $message,
                'derived_message' => null,
                'error' => $result->error ?? 'Nao consegui processar o arquivo enviado.',
            ];
        }

        $derivedMessage = $result->text;

        if ($result->isReceiptAnalysis()) {
            $receipt = new ReceiptAnalysisResult(
                success: $result->success,
                data: $result->data,
                rawText: $result->text,
                tokensUsed: $result->tokensUsed,
                error: $result->error,
            );

            if (!$receipt->isFinancial()) {
                if ($message !== '') {
                    return [
                        'success' => true,
                        'message' => $message,
                        'derived_message' => null,
                    ];
                }

                $desc = $receipt->data['descricao'] ?? 'Nao identifiquei informacoes financeiras nesse arquivo.';
                return [
                    'success' => false,
                    'message' => '',
                    'derived_message' => null,
                    'error' => $desc,
                ];
            }

            $derivedMessage = $receipt->toTransactionText();
        }

        $finalMessage = trim($message !== '' && $derivedMessage !== ''
            ? "{$message}\n{$derivedMessage}"
            : ($derivedMessage !== '' ? $derivedMessage : $message));

        return [
            'success' => true,
            'message' => $finalMessage,
            'derived_message' => $derivedMessage !== '' ? $derivedMessage : null,
        ];
    }

    // ─── Quota ──────────────────────────────────────────────

    /**
     * GET /api/ai/quota
     * Retorna o uso de IA do usuário no mês corrente.
     * Resposta com buckets separados: chat e categorization.
     */
    public function getQuota(): void
    {
        $this->requireAuthApi();

        $user  = \Application\Lib\Auth::user();
        $usage = AIQuotaService::getUsage($user);

        Response::success($usage);
    }

    // ─── Conversations ──────────────────────────────────────

    /**
     * GET /api/ai/conversations
     * Lista as conversas do usuário (mais recente primeiro).
     */
    public function listConversations(): void
    {
        $this->requireAuthApi();

        $conversations = AiConversation::where('user_id', $this->userId)
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get(['id', 'titulo', 'created_at', 'updated_at']);

        Response::success($conversations->toArray());
    }

    /**
     * POST /api/ai/conversations
     * Cria uma nova conversa.
     */
    public function createConversation(): void
    {
        $this->requireAuthApi();

        $conversation = AiConversation::create([
            'user_id' => $this->userId,
            'titulo'  => null,
        ]);

        Response::success([
            'id'         => $conversation->id,
            'titulo'     => $conversation->titulo,
            'created_at' => $conversation->created_at?->toISOString(),
        ], 'Conversa criada.', 201);
    }

    /**
     * GET /api/ai/conversations/{id}/messages
     * Retorna as mensagens de uma conversa.
     */
    public function getMessages(int $id): void
    {
        $this->requireAuthApi();

        $conversation = AiConversation::where('id', $id)
            ->where('user_id', $this->userId)
            ->first();

        if (!$conversation) {
            Response::error('Conversa não encontrada.', 404);
            return;
        }

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get(['id', 'role', 'content', 'intent', 'created_at']);

        Response::success($messages->toArray());
    }

    /**
     * POST /api/ai/conversations/{id}/messages
     * Envia uma mensagem na conversa e obtém resposta da IA.
     * Protegido pelo AIQuotaMiddleware (quota/plano).
     */
    public function sendMessage(int $id): void
    {
        $this->requireAuthApi();

        $conversation = AiConversation::where('id', $id)
            ->where('user_id', $this->userId)
            ->first();

        if (!$conversation) {
            Response::error('Conversa não encontrada.', 404);
            return;
        }

        $payload = $this->getRequestPayload();
        $message = trim($payload['message'] ?? '');
        $resolvedInput = $this->resolveIncomingMessage($message);

        if (!$resolvedInput['success']) {
            Response::error($resolvedInput['error'] ?? 'Nao consegui processar o anexo.', 422);
            return;
        }

        $message = $resolvedInput['message'];

        if ($message === '') {
            Response::error('Mensagem não pode ser vazia.', 422);
            return;
        }

        if (mb_strlen($message) > 2000) {
            Response::error('Mensagem muito longa (máximo 2000 caracteres).', 422);
            return;
        }

        // Salvar mensagem do usuário
        $userMsg = AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $message,
        ]);

        // Coletar contexto financeiro
        try {
            $contextBuilder = new UserContextBuilder();
            $context = $contextBuilder->build($this->userId);
        } catch (\Throwable) {
            $context = [];
        }

        $context = ContextCompressor::compress($context, $message);

        // Incluir últimas mensagens como histórico de conversa (5 msgs = equilíbrio custo/contexto)
        $history = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['role', 'content'])
            ->reverse()
            ->values()
            ->map(function ($msg) {
                $item = $msg->toArray();
                // Truncar respostas longas do assistant para economizar tokens
                if (($item['role'] ?? '') === 'assistant' && mb_strlen($item['content'] ?? '') > 300) {
                    $item['content'] = mb_substr($item['content'], 0, 300) . '…';
                }
                return $item;
            })
            ->toArray();

        $context['conversation_history'] = $history;
        $context['_user_mode'] = true;
        $context['conversation_id'] = $conversation->id;

        // Dispatch pela pipeline de IA
        $ai = new AIService();
        $request = new AIRequestDTO(
            userId: $this->userId,
            message: $message,
            context: $context,
            channel: AIChannel::WEB,
        );

        $response = $ai->dispatch($request);

        // Salvar resposta do assistente
        $assistantMsg = AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $response->message,
            'tokens_used'     => $response->tokensUsed ?: null,
            'intent'          => $response->intent?->value,
        ]);

        // Gerar título automático na primeira mensagem
        if ($conversation->titulo === null) {
            $conversation->titulo = mb_substr($message, 0, 80);
            $conversation->save();
        }

        // Atualizar updated_at da conversa
        $conversation->touch();

        $sendMessageData = [
            'user_message'      => [
                'id'         => $userMsg->id,
                'role'       => 'user',
                'content'    => $userMsg->content,
                'created_at' => $userMsg->created_at?->toISOString(),
            ],
            'assistant_message' => [
                'id'         => $assistantMsg->id,
                'role'       => 'assistant',
                'content'    => $assistantMsg->content,
                'intent'     => $assistantMsg->intent,
                'created_at' => $assistantMsg->created_at?->toISOString(),
            ],
            'source'  => $response->source,
            'cached'  => $response->cached,
            'derived_message' => $resolvedInput['derived_message'],
            'ai_data' => $response->data,
        ];

        if ($response->success) {
            Response::success($sendMessageData);
        } else {
            Response::error($response->message, 503);
        }
    }

    /**
     * POST /api/ai/actions/{id}/confirm
     * Confirma uma ação pendente de IA.
     */
    public function confirmAction(int $id): void
    {
        $this->requireAuthApi();

        $pending = PendingAiAction::where('id', $id)
            ->where('user_id', $this->userId)
            ->where('status', 'awaiting_confirm')
            ->first();

        if (!$pending) {
            Response::error('Ação não encontrada ou já processada.', 404);
            return;
        }

        if ($pending->isExpired()) {
            $pending->markExpired();
            Response::error('Ação expirada. Inicie o processo novamente.', 410);
            return;
        }

        // Injetar conta_id e/ou categoria_id no payload se enviados pelo frontend
        $body = $this->getRequestPayload();
        $payload = $pending->payload;
        $changed = false;

        $contaId = isset($body['conta_id']) ? (int) $body['conta_id'] : null;
        if ($contaId !== null && $contaId > 0) {
            $payload['conta_id'] = $contaId;
            $changed = true;
        }

        $categoriaId = isset($body['categoria_id']) ? (int) $body['categoria_id'] : null;
        if ($categoriaId !== null && $categoriaId > 0) {
            $payload['categoria_id'] = $categoriaId;
            $changed = true;
        }

        if ($changed) {
            $pending->payload = $payload;
            $pending->save();
        }

        // Dispatch confirmação via pipeline
        $ai = new AIService();
        $request = new AIRequestDTO(
            userId: $this->userId,
            message: 'sim',
            intent: IntentType::CONFIRM_ACTION,
            channel: AIChannel::WEB,
        );

        $response = $ai->dispatch($request);

        if ($response->success) {
            Response::success([
                'message' => $response->message,
                'ai_data' => $response->data,
            ]);
        } else {
            Response::error($response->message, 422);
        }
    }

    /**
     * POST /api/ai/actions/{id}/reject
     * Rejeita/cancela uma ação pendente de IA.
     */
    public function rejectAction(int $id): void
    {
        $this->requireAuthApi();

        $pending = PendingAiAction::where('id', $id)
            ->where('user_id', $this->userId)
            ->where('status', 'awaiting_confirm')
            ->first();

        if (!$pending) {
            Response::error('Ação não encontrada ou já processada.', 404);
            return;
        }

        $pending->reject();

        Response::success(['message' => 'Ação cancelada com sucesso.']);
    }

    /**
     * DELETE /api/ai/conversations/{id}
     * Apaga uma conversa e suas mensagens.
     */
    public function deleteConversation(int $id): void
    {
        $this->requireAuthApi();

        $conversation = AiConversation::where('id', $id)
            ->where('user_id', $this->userId)
            ->first();

        if (!$conversation) {
            Response::error('Conversa não encontrada.', 404);
            return;
        }

        $conversation->delete();

        Response::success(null, 'Conversa excluída.');
    }
}
