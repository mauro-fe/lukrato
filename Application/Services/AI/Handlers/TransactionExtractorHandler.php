<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Models\PendingAiAction;
use Application\Repositories\ContaRepository;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\PromptBuilder;
use Application\Services\AI\Rules\CategoryRuleEngine;
use Application\Services\AI\TransactionDetectorService;

/**
 * Handler para extração de transações financeiras a partir de linguagem natural.
 * Usado principalmente no WhatsApp: "gastei 40 no uber", "ifood 32.50", "salário 5000".
 *
 * Pipeline: TransactionDetectorService (regex, 0 tokens) → LLM fallback.
 */
class TransactionExtractorHandler implements AIHandlerInterface
{
    private ?AIProvider $provider = null;

    public function setProvider(AIProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function supports(IntentType $intent): bool
    {
        return $intent === IntentType::EXTRACT_TRANSACTION;
    }

    public function handle(AIRequestDTO $request): AIResponseDTO
    {
        $message = trim($request->message);

        if (mb_strlen($message) < 3) {
            return AIResponseDTO::fail(
                'Mensagem muito curta para extrair transação.',
                IntentType::EXTRACT_TRANSACTION,
            );
        }

        // Pass 1: Regex extraction via TransactionDetectorService (0 tokens)
        $extracted = TransactionDetectorService::extract($message);

        if ($extracted !== null) {
            // Categorizar via rules
            $category = CategoryRuleEngine::match($extracted['descricao'], $request->userId);

            $result = array_merge($extracted, [
                'categoria'        => $category['categoria'] ?? null,
                'subcategoria'     => $category['subcategoria'] ?? null,
                'categoria_id'     => $category['categoria_id'] ?? null,
                'subcategoria_id'  => $category['subcategoria_id'] ?? null,
                'confidence'       => 'rule',
            ]);

            return $this->buildResponse($result, $request, 'rule');
        }

        // Pass 2: LLM extraction
        return $this->extractWithAI($message, $request);
    }

    /**
     * Extração via LLM quando regex falha.
     */
    private function extractWithAI(string $message, AIRequestDTO $request): AIResponseDTO
    {
        try {
            $userPrompt = PromptBuilder::transactionExtractionUser($message);

            // Usar chat com contexto mínimo (o prompt de extração é injetado pelo provider)
            $response = $this->provider->chat($userPrompt, []);

            // Tentar parsear JSON da resposta
            $data = $this->parseJsonResponse($response);

            if ($data === null) {
                return AIResponseDTO::fail(
                    'Não consegui entender a transação. Tente algo como: "gastei 40 no uber" ou "ifood 32.50"',
                    IntentType::EXTRACT_TRANSACTION,
                );
            }

            // Normalizar
            $data['valor'] = (float) ($data['valor'] ?? 0);
            $data['tipo']  = $data['tipo'] ?? 'despesa';
            $data['data']  = $data['data'] ?? date('Y-m-d');

            // Validar campos obrigatórios
            if (empty($data['descricao']) || $data['valor'] <= 0) {
                return AIResponseDTO::fail(
                    'Não consegui extrair descrição e valor da transação. Tente algo como: "gastei 40 no uber" ou "ifood 32.50"',
                    IntentType::EXTRACT_TRANSACTION,
                );
            }

            // Categorizar
            if (!empty($data['descricao'])) {
                $category = CategoryRuleEngine::match($data['descricao'], $request->userId);
                if ($category !== null) {
                    $data = array_merge($data, [
                        'categoria'        => $category['categoria'],
                        'subcategoria'     => $category['subcategoria'],
                        'categoria_id'     => $category['categoria_id'],
                        'subcategoria_id'  => $category['subcategoria_id'],
                    ]);
                }
            }

            $data['confidence'] = 'ai';

            return $this->buildResponse($data, $request, 'llm');
        } catch (\Throwable $e) {
            return AIResponseDTO::fail(
                'Erro ao processar a transação. Tente novamente.',
                IntentType::EXTRACT_TRANSACTION,
            );
        }
    }

    /**
     * Tenta parsear JSON de uma resposta da IA.
     */
    private function parseJsonResponse(string $response): ?array
    {
        // Tentar extrair JSON da resposta (pode ter texto ao redor)
        if (preg_match('/\{[^}]+\}/s', $response, $match)) {
            $data = json_decode($match[0], true);
            if (is_array($data) && isset($data['descricao'])) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Monta resposta com PendingAiAction para canal web, ou resposta direta para WhatsApp.
     */
    private function buildResponse(array $result, AIRequestDTO $request, string $source): AIResponseDTO
    {
        $confirmText = $this->formatConfirmation($result);

        // No canal web, criar PendingAiAction para confirmação via botões
        if ($request->channel === AIChannel::WEB && $request->userId) {
            $conversationId = $request->context['conversation_id'] ?? null;

            // Buscar contas ativas do usuário para seleção
            $contaRepo = new ContaRepository();
            $contas = $contaRepo->findActive($request->userId);

            if ($contas->isEmpty()) {
                return AIResponseDTO::fromRule(
                    '⚠️ Você precisa ter pelo menos uma conta cadastrada para registrar lançamentos.',
                    ['action' => 'no_accounts'],
                    IntentType::EXTRACT_TRANSACTION
                );
            }

            // Se tem apenas 1 conta, auto-preencher no payload
            if ($contas->count() === 1) {
                $result['conta_id'] = $contas->first()->id;
            }

            $pending = PendingAiAction::create([
                'user_id'         => $request->userId,
                'conversation_id' => $conversationId,
                'action_type'     => 'create_lancamento',
                'payload'         => $result,
                'status'          => 'awaiting_confirm',
                'expires_at'      => now()->addMinutes(10),
            ]);

            $accountsList = $contas->map(fn($c) => ['id' => $c->id, 'nome' => $c->nome])->values()->toArray();

            $responseData = array_merge($result, [
                'action'     => 'confirm',
                'pending_id' => $pending->id,
                'accounts'   => $accountsList,
            ]);

            $confirmText .= "\n\n**Deseja confirmar?** Responda **sim** ou **não**.";

            return $source === 'llm'
                ? AIResponseDTO::fromLLM($confirmText, $responseData, IntentType::EXTRACT_TRANSACTION)
                : AIResponseDTO::fromRule($confirmText, $responseData, IntentType::EXTRACT_TRANSACTION);
        }

        // WhatsApp ou sem userId — retornar sem PendingAiAction (fluxo existente)
        return $source === 'llm'
            ? AIResponseDTO::fromLLM($confirmText, $result, IntentType::EXTRACT_TRANSACTION)
            : AIResponseDTO::fromRule($confirmText, $result, IntentType::EXTRACT_TRANSACTION);
    }

    /**
     * Formata mensagem de confirmação.
     */
    private function formatConfirmation(array $data): string
    {
        $tipo   = ($data['tipo'] ?? 'despesa') === 'receita' ? '💰 Receita' : '💸 Despesa';
        $valor  = 'R$ ' . number_format($data['valor'] ?? 0, 2, ',', '.');
        $desc   = $data['descricao'] ?? 'Sem descrição';
        $cat    = $data['categoria'] ?? null;

        $msg = "{$tipo}: **{$desc}** — **{$valor}**";

        if ($cat) {
            $sub = $data['subcategoria'] ?? null;
            $msg .= $sub ? " ({$cat} > {$sub})" : " ({$cat})";
        }

        return $msg;
    }
}
