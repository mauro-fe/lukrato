<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\Container\ApplicationContainer;
use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Models\CartaoCredito;
use Application\Models\PendingAiAction;
use Application\Repositories\ContaRepository;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\Helpers\UserCategoryLoader;
use Application\Services\AI\PromptBuilder;
use Application\Services\AI\Rules\CategoryRuleEngine;
use Application\Services\AI\TransactionDetectorService;
use Application\Services\Infrastructure\LogService;

/**
 * Handler para extração de transações financeiras a partir de linguagem natural.
 * Usado principalmente no WhatsApp: "gastei 40 no uber", "ifood 32.50", "salário 5000".
 *
 * Agora suporta cartão de crédito e parcelamento:
 *   "parcelei geladeira no nubank 1500 em 12x"  → cria FaturaCartaoItem
 *   "comprei roupa no crédito 200"               → cria FaturaCartaoItem
 *
 * Pipeline: TransactionDetectorService (regex, 0 tokens) → LLM fallback → CartaoCredito resolve → PendingAiAction.
 */
class TransactionExtractorHandler implements AIHandlerInterface
{
    private ?AIProvider $provider = null;

    private ContaRepository $contaRepository;

    public function __construct(?ContaRepository $contaRepository = null)
    {
        $this->contaRepository = ApplicationContainer::resolveOrNew($contaRepository, ContaRepository::class);
    }

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
            $category = CategoryRuleEngine::match(
                $extracted['descricao'],
                $request->userId,
                $extracted['categoria_contexto'] ?? null
            );

            $result = array_merge($extracted, [
                'categoria'        => $category['categoria'] ?? null,
                'subcategoria'     => $category['subcategoria'] ?? null,
                'categoria_id'     => $category['categoria_id'] ?? null,
                'subcategoria_id'  => $category['subcategoria_id'] ?? null,
                'confidence'       => 'rule',
            ]);

            // Resolver cartão de crédito se mencionado
            if ($request->userId) {
                $result = $this->resolveCartaoCredito($result, $message, $request->userId);
            }

            return $this->buildResponse($result, $request, 'rule');
        }

        // Pass 2: LLM extraction
        return $this->extractWithAI($message, $request);
    }

    /**
     * Extração via LLM com function calling (structured output) quando regex falha.
     */
    private function extractWithAI(string $message, AIRequestDTO $request): AIResponseDTO
    {
        try {
            // Incluir categorias do usuário no prompt para melhor sugestão
            $userPrompt = PromptBuilder::transactionExtractionUser($message);

            if ($request->userId) {
                $userCategories = UserCategoryLoader::load($request->userId);
                if (!empty($userCategories)) {
                    $catList = implode(', ', $userCategories);
                    $userPrompt .= "\n\nCategorias disponíveis do usuário: {$catList}";
                }
            }

            // Usar function calling para output estruturado garantido
            $data = $this->provider->chatWithTools(
                $userPrompt,
                [\Application\Services\AI\Schemas\EntitySchemas::lancamento()],
                [
                    'system_prompt' => PromptBuilder::transactionExtractionSystem(),
                    'temperature'   => 0.1,
                    'max_tokens'    => 500,
                ]
            );

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
            $category = CategoryRuleEngine::match(
                $data['descricao'],
                $request->userId,
                $data['categoria_contexto'] ?? null
            );
            if ($category !== null) {
                $data = array_merge($data, [
                    'categoria'        => $category['categoria'],
                    'subcategoria'     => $category['subcategoria'],
                    'categoria_id'     => $category['categoria_id'],
                    'subcategoria_id'  => $category['subcategoria_id'],
                ]);
            }

            $data['confidence'] = 'ai';

            // Resolver cartão de crédito se mencionado
            if ($request->userId) {
                $data = $this->resolveCartaoCredito($data, $message, $request->userId);
            }

            return $this->buildResponse($data, $request, 'llm');
        } catch (\Throwable $e) {
            LogService::warning('TransactionExtractorHandler.extractWithAI', [
                'error' => $e->getMessage(),
            ]);

            return AIResponseDTO::fail(
                'Erro ao processar a transação. Tente novamente.',
                IntentType::EXTRACT_TRANSACTION,
            );
        }
    }

    /**
     * Resolve cartão de crédito mencionado na mensagem.
     * Se o usuário disse "no nubank" e tem um cartão Nubank, auto-preenche cartao_credito_id.
     */
    private function resolveCartaoCredito(array $data, string $message, int $userId): array
    {
        $fp = $data['forma_pagamento'] ?? null;

        // Se forma_pagamento não é cartao_credito, não precisa resolver
        if ($fp !== 'cartao_credito') {
            return $data;
        }

        // Se já tem cartao_credito_id, não precisa resolver
        if (!empty($data['cartao_credito_id'])) {
            return $data;
        }

        // Buscar cartões ativos do usuário
        $cartoes = CartaoCredito::where('user_id', $userId)
            ->where('ativo', true)
            ->get();

        if ($cartoes->isEmpty()) {
            return $data;
        }

        // Se tem só 1 cartão, auto-preencher
        if ($cartoes->count() === 1) {
            $data['cartao_credito_id'] = $cartoes->first()->id;
            $data['_cartao_nome'] = $cartoes->first()->nome_cartao;
            return $data;
        }

        // Tentar detectar nome na mensagem
        $nomeCartao = $data['nome_cartao'] ?? null;
        if ($nomeCartao === null) {
            // Tentar regex na mensagem
            $cardPattern = '/(?:no|na|do|da|pelo|pela)\s+(nubank|inter|ita[úu]|itau|bradesco|santander|bb|banco\s*do\s*brasil|sicredi|sicoob|c6|c6\s*bank|original|bmg|pan|neon|next|will|digio|picpay|pagbank|mercado\s*pago|ame|stone|safra|caixa|banrisul|btg)/iu';
            if (preg_match($cardPattern, $message, $m)) {
                $nomeCartao = trim($m[1]);
            }
        }

        if ($nomeCartao !== null) {
            $nomeCartaoLower = mb_strtolower($nomeCartao);
            foreach ($cartoes as $cartao) {
                if (str_contains(mb_strtolower($cartao->nome_cartao), $nomeCartaoLower)) {
                    $data['cartao_credito_id'] = $cartao->id;
                    $data['_cartao_nome'] = $cartao->nome_cartao;
                    return $data;
                }
            }
        }

        // Múltiplos cartões e não conseguiu resolver — será pedido ao usuário na confirmação
        return $data;
    }

    /**
     * Monta resposta unificada para todos os canais.
     */
    private function buildResponse(array $result, AIRequestDTO $request, string $source): AIResponseDTO
    {
        $confirmText = $this->formatConfirmation($result);

        if ($request->userId) {
            $conversationId = $request->context['conversation_id'] ?? null;
            $isCartao = ($result['forma_pagamento'] ?? null) === 'cartao_credito';
            $accountsList = [];
            $cardsList = [];

            if (!$isCartao) {
                $contas = $this->contaRepository->findActive($request->userId);

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

                if ($request->channel === AIChannel::WEB) {
                    $accountsList = $contas->map(fn($c) => ['id' => $c->id, 'nome' => $c->nome])->values()->toArray();
                }
            }

            if ($isCartao && empty($result['cartao_credito_id'])) {
                $cartoes = CartaoCredito::where('user_id', $request->userId)->where('ativo', true)->get();
                if ($cartoes->isEmpty()) {
                    return AIResponseDTO::fromRule(
                        '⚠️ Você não tem nenhum cartão de crédito cadastrado. Cadastre um cartão primeiro.',
                        ['action' => 'no_cards'],
                        IntentType::EXTRACT_TRANSACTION
                    );
                }
                if ($cartoes->count() === 1) {
                    $result['cartao_credito_id'] = $cartoes->first()->id;
                    $result['_cartao_nome'] = $cartoes->first()->nome_cartao;
                    $confirmText = $this->formatConfirmation($result);
                } elseif ($request->channel === AIChannel::WEB) {
                    $cardsList = $cartoes->map(fn($c) => [
                        'id'   => $c->id,
                        'nome' => $c->nome_cartao,
                        'bandeira' => $c->bandeira,
                        'ultimos_digitos' => $c->ultimos_digitos,
                    ])->values()->toArray();
                }
            }

            $pending = PendingAiAction::create([
                'user_id'         => $request->userId,
                'conversation_id' => $conversationId,
                'action_type'     => 'create_lancamento',
                'payload'         => $result,
                'status'          => 'awaiting_confirm',
                'expires_at'      => now()->addMinutes(10),
            ]);

            $responseData = array_merge($result, [
                'action'     => 'confirm',
                'pending_id' => $pending->id,
            ]);

            if (!empty($accountsList)) {
                $responseData['accounts'] = $accountsList;
            }
            if (!empty($cardsList)) {
                $responseData['cards'] = $cardsList;
            }

            $confirmText .= match ($request->channel) {
                AIChannel::WEB => "\n\n**Deseja confirmar?** Responda **sim** ou **não**.",
                default => "\n\nConfirme para continuar.",
            };

            return $source === 'llm'
                ? AIResponseDTO::fromLLM($confirmText, $responseData, IntentType::EXTRACT_TRANSACTION)
                : AIResponseDTO::fromRule($confirmText, $responseData, IntentType::EXTRACT_TRANSACTION);
        }

        return $source === 'llm'
            ? AIResponseDTO::fromLLM($confirmText, $result, IntentType::EXTRACT_TRANSACTION)
            : AIResponseDTO::fromRule($confirmText, $result, IntentType::EXTRACT_TRANSACTION);
    }

    /**
     * Formata mensagem de confirmação com suporte a cartão de crédito e parcelamento.
     */
    private function formatConfirmation(array $data): string
    {
        $isCartao = ($data['forma_pagamento'] ?? null) === 'cartao_credito';
        $valor    = (float) ($data['valor'] ?? 0);
        $desc     = $data['descricao'] ?? 'Sem descrição';
        $cat      = $data['categoria'] ?? null;

        if ($isCartao) {
            $cartaoNome = $data['_cartao_nome'] ?? 'Cartão de Crédito';

            if (!empty($data['eh_parcelado']) && !empty($data['total_parcelas'])) {
                $parcelas = (int) $data['total_parcelas'];
                $valorParcela = $valor / $parcelas;
                $valorFmt = 'R$ ' . number_format($valor, 2, ',', '.');
                $parcelaFmt = 'R$ ' . number_format($valorParcela, 2, ',', '.');
                $msg = "💳 **{$desc}** — **{$valorFmt}** ({$parcelas}x de {$parcelaFmt}) no {$cartaoNome}";
            } else {
                $valorFmt = 'R$ ' . number_format($valor, 2, ',', '.');
                $msg = "💳 **{$desc}** — **{$valorFmt}** no {$cartaoNome}";
            }
        } else {
            $tipo = ($data['tipo'] ?? 'despesa') === 'receita' ? '💰 Receita' : '💸 Despesa';
            $valorFmt = 'R$ ' . number_format($valor, 2, ',', '.');
            $msg = "{$tipo}: **{$desc}** — **{$valorFmt}**";

            // Mostrar forma de pagamento se detectada
            $fp = $data['forma_pagamento'] ?? null;
            if ($fp && !in_array($fp, ['cartao_credito', null])) {
                $fpLabel = match ($fp) {
                    'pix'            => 'via PIX',
                    'cartao_debito'  => 'no débito',
                    'dinheiro'       => 'em dinheiro',
                    'boleto'         => 'via boleto',
                    default          => '',
                };
                if ($fpLabel) {
                    $msg .= " {$fpLabel}";
                }
            }
        }

        if ($cat) {
            $sub = $data['subcategoria'] ?? null;
            $msg .= $sub ? " ({$cat} > {$sub})" : " ({$cat})";
        }

        return $msg;
    }
}
