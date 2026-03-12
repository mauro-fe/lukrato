<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Models\CartaoCredito;
use Application\Models\Conta;
use Application\Models\PendingAiAction;
use Application\Repositories\ContaRepository;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\ConversationStateService;
use Application\Services\AI\IntentRules\EntityCreationIntentRule;
use Application\Services\AI\Rules\CategoryRuleEngine;
use Application\Services\AI\Schemas\EntitySchemas;
use Application\Models\Categoria;
use Application\Validators\CategoriaValidator;
use Application\Validators\ContaValidator;
use Application\Validators\LancamentoValidator;
use Application\Validators\MetaValidator;
use Application\Validators\OrcamentoValidator;
use Application\Validators\SubcategoriaValidator;

/**
 * Handler para criação de entidades financeiras via IA.
 *
 * Pipeline: Regex extraction (0 tokens) → LLM fallback → Validação → PendingAiAction → Confirmação
 *
 * Suporta:
 *  - Lançamento normal (despesa/receita em conta)
 *  - Lançamento em cartão de crédito (vai para fatura)
 *  - Lançamento parcelado (múltiplos itens na fatura)
 *  - Meta financeira
 *  - Orçamento mensal
 *  - Categoria / Subcategoria
 */
class EntityCreationHandler implements AIHandlerInterface
{
    private ?AIProvider $provider = null;

    public function setProvider(AIProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function supports(IntentType $intent): bool
    {
        return $intent === IntentType::CREATE_ENTITY;
    }

    public function handle(AIRequestDTO $request): AIResponseDTO
    {
        $message = trim($request->message);
        $userId  = $request->userId;

        if (!$userId) {
            return AIResponseDTO::fail('Usuário não identificado.', IntentType::CREATE_ENTITY);
        }

        $conversationId = $request->context['conversation_id'] ?? null;

        // ─── Multi-turn: continue active collection flow ────────
        if ($conversationId !== null) {
            $convState = ConversationStateService::getState($conversationId);

            // If we're in 'awaiting_selection', try to resolve the selection
            if ($convState['state'] === 'awaiting_selection') {
                $resolved = ConversationStateService::resolveSelection($conversationId, $message);
                if ($resolved !== null) {
                    // Selection resolved — proceed with complete data
                    // The resolved array contains the merged pending_data + selected option
                    $entityType = $convState['data']['pending_data']['_entity_type'] ?? 'lancamento';
                    unset($resolved['_entity_type']);
                    return $this->proceedToConfirmation($resolved, $entityType, $userId, $request);
                }
                // Could not resolve — ask again
                return AIResponseDTO::fromRule(
                    "Não entendi a seleção. Por favor, escolha uma das opções acima (digite o número ou o nome).",
                    ['action' => 'selection_retry'],
                    IntentType::CREATE_ENTITY
                );
            }

            // If we're in 'collecting_entity', extract new data and merge
            if ($convState['state'] === 'collecting_entity') {
                $entityType = $convState['data']['entity_type'] ?? 'lancamento';
                $newData = $this->extractByRegex($message, $entityType);

                // Also try to extract single field values (user might just type "150" for valor)
                $newData = $this->extractSingleFieldAnswer($message, $convState['data']['missing_fields'] ?? [], $newData);

                $result = ConversationStateService::updateEntityCollection($conversationId, $newData);

                if ($result['complete']) {
                    // All fields collected! Proceed to confirmation
                    if ($entityType === 'lancamento') {
                        $result['data'] = $this->resolveCartaoCredito($result['data'], $message, $userId);
                    }
                    return $this->proceedToConfirmation($result['data'], $entityType, $userId, $request);
                }

                // Still missing fields — ask next question
                $question = ConversationStateService::getNextQuestion($result['missing'], $entityType);
                return AIResponseDTO::fromRule(
                    $question,
                    ['action' => 'collecting', 'missing' => $result['missing'], 'entity_type' => $entityType],
                    IntentType::CREATE_ENTITY
                );
            }
        }

        // ─── Normal flow: detect entity type and extract ────────
        $entityType = EntityCreationIntentRule::detectEntityType($message);

        if (!$entityType) {
            return AIResponseDTO::fail(
                'Não consegui identificar o que você quer criar. Tente: "criar lançamento", "criar meta", "criar orçamento", "criar categoria", "criar subcategoria" ou "criar conta".',
                IntentType::CREATE_ENTITY
            );
        }

        // Extrair dados via regex primeiro (0 tokens)
        $extracted = $this->extractByRegex($message, $entityType);

        // Para lançamento, tentar resolver cartão de crédito se mencionado
        if ($entityType === 'lancamento') {
            $extracted = $this->resolveCartaoCredito($extracted, $message, $userId);
        }

        // Se faltam campos obrigatórios, tentar LLM
        $missing = $this->getMissingFields($extracted, $entityType);
        if (!empty($missing) && $this->provider) {
            $extracted = $this->extractWithAI($message, $entityType, $extracted);
            $missing = $this->getMissingFields($extracted, $entityType);

            // Re-resolver cartão após extração com LLM (pode ter extraído nome_cartao)
            if ($entityType === 'lancamento') {
                $extracted = $this->resolveCartaoCredito($extracted, $message, $userId);
            }
        }

        // Se ainda faltam campos obrigatórios, iniciar coleta multi-turno
        if (!empty($missing)) {
            if ($conversationId !== null) {
                ConversationStateService::startEntityCollection(
                    $conversationId,
                    $entityType,
                    $extracted,
                    $missing
                );
                $question = ConversationStateService::getNextQuestion($missing, $entityType);
                return AIResponseDTO::fromRule(
                    $question,
                    ['action' => 'collecting', 'missing' => $missing, 'entity_type' => $entityType],
                    IntentType::CREATE_ENTITY
                );
            }

            // No conversation context (e.g., WhatsApp without session) — ask all at once
            $labels = $this->getFieldLabels($entityType);
            $missingLabels = array_map(fn($f) => $labels[$f] ?? $f, $missing);

            return AIResponseDTO::fromRule(
                "Para criar " . $this->getEntityLabel($entityType) . ", preciso que você informe: **" . implode('**, **', $missingLabels) . "**.\n\nTente algo como: " . $this->getExample($entityType),
                ['action' => 'missing_fields', 'missing' => $missing, 'entity_type' => $entityType],
                IntentType::CREATE_ENTITY
            );
        }

        return $this->proceedToConfirmation($extracted, $entityType, $userId, $request);
    }

    /**
     * Procede com validação e criação de PendingAiAction para confirmação.
     * Extraído do handle() para ser reutilizado pelo fluxo multi-turno.
     */
    private function proceedToConfirmation(array $extracted, string $entityType, int $userId, AIRequestDTO $request): AIResponseDTO
    {
        $conversationId = $request->context['conversation_id'] ?? null;

        // Resolver categoria_sugerida → categoria_id (a IA extrai nome, precisamos do ID)
        if ($entityType === 'lancamento' && empty($extracted['categoria_id'])) {
            $extracted = $this->resolveCategoria($extracted, $userId);
        }

        // Validar com os validators (para lancamento, pular validação de conta_id — será adicionado na confirmação)
        $errors = $this->validate($extracted, $entityType, $userId);
        if ($entityType === 'lancamento') {
            unset($errors['conta_id']);
        }
        if (!empty($errors)) {
            $errorMessages = array_values($errors);
            return AIResponseDTO::fromRule(
                "⚠️ Encontrei alguns problemas:\n• " . implode("\n• ", $errorMessages) . "\n\nPor favor, corrija e tente novamente.",
                ['action' => 'validation_error', 'errors' => $errors, 'entity_type' => $entityType],
                IntentType::CREATE_ENTITY
            );
        }

        // Para lancamento, buscar contas do usuário (e cartões se forma_pagamento = cartão)
        $accountsList = [];
        $cardsList = [];
        $categoriesList = [];
        if ($entityType === 'lancamento') {
            $contaRepo = new ContaRepository();
            $contas = $contaRepo->findActive($userId);

            // Cartão de crédito não precisa de conta (vai direto pra fatura)
            $isCartao = ($extracted['forma_pagamento'] ?? null) === 'cartao_credito';

            if (!$isCartao) {
                if ($contas->isEmpty()) {
                    return AIResponseDTO::fromRule(
                        '⚠️ Você precisa ter pelo menos uma conta cadastrada para criar lançamentos.',
                        ['action' => 'no_accounts'],
                        IntentType::CREATE_ENTITY
                    );
                }
                // Auto-selecionar primeira conta (usuário pode trocar no dropdown)
                if (empty($extracted['conta_id'])) {
                    $extracted['conta_id'] = $contas->first()->id;
                }
                $accountsList = $contas->map(fn($c) => ['id' => $c->id, 'nome' => $c->nome])->values()->toArray();
            }

            // Se é cartão mas ainda não tem cartao_credito_id, listar cartões para seleção
            if ($isCartao && empty($extracted['cartao_credito_id'])) {
                $cartoes = CartaoCredito::where('user_id', $userId)->where('ativo', true)->get();
                if ($cartoes->isEmpty()) {
                    return AIResponseDTO::fromRule(
                        '⚠️ Você não tem nenhum cartão de crédito cadastrado. Cadastre um cartão primeiro para registrar compras no crédito.',
                        ['action' => 'no_cards'],
                        IntentType::CREATE_ENTITY
                    );
                }
                if ($cartoes->count() === 1) {
                    $extracted['cartao_credito_id'] = $cartoes->first()->id;
                } else {
                    $cardsList = $cartoes->map(fn($c) => [
                        'id'   => $c->id,
                        'nome' => $c->nome_cartao,
                        'bandeira' => $c->bandeira,
                        'ultimos_digitos' => $c->ultimos_digitos,
                    ])->values()->toArray();
                }
            }

            // Buscar categorias do usuário para dropdown opcional
            $tipoLanc = $extracted['tipo'] ?? 'despesa';
            $categorias = Categoria::where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            })
                ->where(function ($q) use ($tipoLanc) {
                    $q->where('tipo', $tipoLanc)->orWhere('tipo', 'ambas');
                })
                ->orderBy('nome')
                ->get();
            $categoriesList = $categorias->map(fn($c) => ['id' => $c->id, 'nome' => $c->nome])->values()->toArray();
        }

        // Criar PendingAiAction para confirmação
        $conversationId = $request->context['conversation_id'] ?? null;

        $pending = PendingAiAction::create([
            'user_id'         => $userId,
            'conversation_id' => $conversationId,
            'action_type'     => 'create_' . $entityType,
            'payload'         => $extracted,
            'status'          => 'awaiting_confirm',
            'expires_at'      => now()->addMinutes(10),
        ]);

        $preview = $this->formatPreview($extracted, $entityType);

        $responseData = [
            'action'      => 'confirm',
            'pending_id'  => $pending->id,
            'entity_type' => $entityType,
            'preview'     => $extracted,
        ];

        if (!empty($accountsList)) {
            $responseData['accounts'] = $accountsList;
            $responseData['selected_conta_id'] = $extracted['conta_id'] ?? null;
        }
        if (!empty($cardsList)) {
            $responseData['cards'] = $cardsList;
        }
        if (!empty($categoriesList)) {
            $responseData['categories'] = $categoriesList;
            $responseData['selected_categoria_id'] = $extracted['categoria_id'] ?? null;
        }

        return AIResponseDTO::fromRule(
            $preview . "\n\n**Deseja confirmar a criação?** Responda **sim** para confirmar ou **não** para cancelar.",
            $responseData,
            IntentType::CREATE_ENTITY
        );
    }

    // ─── Regex extractors ───────────────────────────────────────

    /**
     * Tenta extrair um valor direto quando o usuário responde uma pergunta específica.
     * Ex: perguntamos "Qual o valor?" e o usuário responde "150" ou "R$ 200".
     */
    private function extractSingleFieldAnswer(string $message, array $missingFields, array $existing): array
    {
        if (empty($missingFields)) {
            return $existing;
        }

        $msg = trim($message);
        $firstMissing = $missingFields[0];

        // Se a mensagem é curta e temos só 1 campo faltando, tentar interpretar como resposta direta
        if (mb_strlen($msg) <= 80) {
            switch ($firstMissing) {
                case 'valor':
                case 'valor_alvo':
                case 'valor_limite':
                    $normalized = $this->normalizeColloquialValues($msg);
                    if (preg_match('/R?\$?\s*(\d{1,3}(?:\.\d{3})*[,\.]\d{2}|\d+(?:[,\.]\d{1,2})?)/iu', $normalized, $m)) {
                        $valor = str_replace('.', '', $m[1]);
                        $valor = str_replace(',', '.', $valor);
                        $existing[$firstMissing] = (float) $valor;
                    }
                    break;

                case 'descricao':
                case 'titulo':
                case 'nome':
                    // Qualquer texto >2 chars serve como descrição/título/nome
                    if (mb_strlen($msg) >= 2 && mb_strlen($msg) <= 150) {
                        $existing[$firstMissing] = $msg;
                    }
                    break;

                case 'tipo':
                    if (preg_match('/\b(receita|ganho|entrada|receb)\b/iu', $msg)) {
                        $existing['tipo'] = 'receita';
                    } elseif (preg_match('/\b(despesa|gasto|saíd|said|pag)\b/iu', $msg)) {
                        $existing['tipo'] = 'despesa';
                    } elseif (preg_match('/\b(transfer[eê]ncia|transferencia)\b/iu', $msg)) {
                        $existing['tipo'] = 'transferencia';
                    } elseif (preg_match('/\bambas?\b/iu', $msg)) {
                        $existing['tipo'] = 'ambas';
                    }
                    break;

                case 'data':
                    if (preg_match('/\bhoje\b/iu', $msg)) {
                        $existing['data'] = date('Y-m-d');
                    } elseif (preg_match('/\bamanh[ãa]\b/iu', $msg)) {
                        $existing['data'] = date('Y-m-d', strtotime('+1 day'));
                    } elseif (preg_match('/\bontem\b/iu', $msg)) {
                        $existing['data'] = date('Y-m-d', strtotime('-1 day'));
                    } elseif (preg_match('/(\d{1,2})\s*[\/\-]\s*(\d{1,2})(?:\s*[\/\-]\s*(\d{2,4}))?/u', $msg, $m)) {
                        $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                        $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                        $year = isset($m[3]) ? (strlen($m[3]) === 2 ? '20' . $m[3] : $m[3]) : date('Y');
                        $existing['data'] = "{$year}-{$month}-{$day}";
                    }
                    break;
            }
        }

        return $existing;
    }

    private function extractByRegex(string $message, string $entityType): array
    {
        return match ($entityType) {
            'lancamento'   => $this->extractLancamento($message),
            'meta'         => $this->extractMeta($message),
            'orcamento'    => $this->extractOrcamento($message),
            'categoria'    => $this->extractCategoria($message),
            'subcategoria' => $this->extractSubcategoria($message),
            'conta'        => $this->extractConta($message),
            default        => [],
        };
    }

    private function extractLancamento(string $message): array
    {
        $data = [];

        // tipo: receita ou despesa
        if (preg_match('/\b(receita|ganho|sal[áa]rio|renda|entrada|receb[ei]|ganhei)\b/iu', $message)) {
            $data['tipo'] = 'receita';
        } else {
            $data['tipo'] = 'despesa';
        }

        // Normalizar valores coloquiais antes de extrair
        $msgNorm = $this->normalizeColloquialValues($message);

        // valor: R$ 100, 100 reais, 1.500,00, etc.
        if (preg_match('/R?\$?\s*(\d{1,3}(?:\.\d{3})*[,\.]\d{2}|\d+(?:[,\.]\d{1,2})?)\s*(?:reais|conto[s]?|pila[s]?)?/iu', $msgNorm, $m)) {
            $valor = str_replace('.', '', $m[1]);
            $valor = str_replace(',', '.', $valor);
            $data['valor'] = (float) $valor;
        }

        // forma_pagamento
        $data = array_merge($data, $this->extractFormaPagamento($message));

        // parcelamento
        $data = array_merge($data, $this->extractParcelamento($message));

        // Se tem parcelamento, forçar cartão de crédito
        if (!empty($data['eh_parcelado']) && empty($data['forma_pagamento'])) {
            $data['forma_pagamento'] = 'cartao_credito';
        }

        // data: hoje, amanhã, DD/MM, DD/MM/YYYY
        if (preg_match('/\bhoje\b/iu', $message)) {
            $data['data'] = date('Y-m-d');
        } elseif (preg_match('/\bamanh[ãa]\b/iu', $message)) {
            $data['data'] = date('Y-m-d', strtotime('+1 day'));
        } elseif (preg_match('/\bontem\b/iu', $message)) {
            $data['data'] = date('Y-m-d', strtotime('-1 day'));
        } elseif (preg_match('/(\d{1,2})\s*[\/\-]\s*(\d{1,2})(?:\s*[\/\-]\s*(\d{2,4}))?/u', $message, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = isset($m[3]) ? (strlen($m[3]) === 2 ? '20' . $m[3] : $m[3]) : date('Y');
            $data['data'] = "{$year}-{$month}-{$day}";
        } else {
            $data['data'] = date('Y-m-d');
        }

        // descricao: tenta extrair "de <descricao>" ou texto significativo
        if (preg_match('/\b(?:de|do|da|para|com|no|na)\s+(.{3,60})$/iu', $message, $m)) {
            $desc = trim($m[1]);
            // Remover partes que são data, valor, cartão ou parcelamento
            $desc = preg_replace('/\b(?:hoje|amanh[ãa]|ontem|\d{1,2}\/\d{1,2}(?:\/\d{2,4})?|R?\$?\s*[\d.,]+\s*(?:reais|conto[s]?|pila[s]?)?)\b/iu', '', $desc);
            $desc = preg_replace('/\b(?:cart[ãa]o|cr[ée]dito|d[ée]bito|em\s+\d{1,2}\s*x|\d{1,2}\s*x|parcela[s]?|parcelado?|nubank|inter|ita[úu]|bradesco|santander|c6)\b/iu', '', $desc);
            $desc = trim($desc, " \t\n\r\0\x0B,.");
            if (mb_strlen($desc) >= 3) {
                $data['descricao'] = mb_substr($desc, 0, 190);
            }
        }

        return $data;
    }

    /**
     * Extrai forma de pagamento da mensagem.
     */
    private function extractFormaPagamento(string $message): array
    {
        $normalized = mb_strtolower($message);

        // Cartão de crédito (verificar débito primeiro pois é mais específico)
        if (preg_match('/\b(?:d[ée]bito|no\s+d[ée]bito|cart[ãa]o\s+(?:de\s+)?d[ée]bito)\b/iu', $normalized)) {
            return ['forma_pagamento' => 'cartao_debito'];
        }
        if (preg_match('/\b(?:cart[ãa]o|cr[ée]dito|no\s+cart[ãa]o|no\s+cr[ée]dito|parcelei|parcelo)\b/iu', $normalized)) {
            return ['forma_pagamento' => 'cartao_credito'];
        }
        if (preg_match('/\b(?:pix|mandei\s+pix|fiz\s+pix|via\s+pix)\b/iu', $normalized)) {
            return ['forma_pagamento' => 'pix'];
        }
        if (preg_match('/\b(?:boleto|guia|darf|gru)\b/iu', $normalized)) {
            return ['forma_pagamento' => 'boleto'];
        }
        if (preg_match('/\b(?:dinheiro|cash|esp[ée]cie|em\s+m[ãa]os)\b/iu', $normalized)) {
            return ['forma_pagamento' => 'dinheiro'];
        }

        return [];
    }

    /**
     * Extrai informação de parcelamento.
     */
    private function extractParcelamento(string $message): array
    {
        // "em 12x" / "12 vezes" / "parcelei em 6" / "6x de 150"
        $patterns = [
            '/(?:em\s+)?(\d{1,2})\s*x\b/iu',
            '/(\d{1,2})\s*(?:vezes|parcelas?)\b/iu',
            '/parcel(?:ei|ado|ar|o)\s+(?:em\s+)?(\d{1,2})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $m)) {
                $parcelas = (int) $m[1];
                if ($parcelas >= 2 && $parcelas <= 48) {
                    return [
                        'eh_parcelado'   => true,
                        'total_parcelas' => $parcelas,
                    ];
                }
            }
        }

        return [];
    }

    /**
     * Normaliza valores coloquiais: "1k" → "1000", "mil reais" → "1000"
     */
    private function normalizeColloquialValues(string $message): string
    {
        $message = preg_replace_callback('/(\d+(?:[.,]\d+)?)\s*k\b/iu', function ($m) {
            $val = (float) str_replace(',', '.', $m[1]);
            return (string) ($val * 1000);
        }, $message);

        $message = preg_replace('/\bmil\s*(?:reais|conto[s]?|pila[s]?)?\b/iu', '1000', $message);

        return $message;
    }

    /**
     * Resolve categoria_sugerida (string) para categoria_id (int).
     * Usa CategoryRuleEngine (padrões aprendidos) e fallback por nome no banco.
     */
    private function resolveCategoria(array $data, int $userId): array
    {
        // 1. Tentar via CategoryRuleEngine (regras aprendidas + globais)
        $descricao = $data['descricao'] ?? '';
        if ($descricao !== '') {
            $match = CategoryRuleEngine::match($descricao, $userId);
            if ($match !== null && !empty($match['categoria_id'])) {
                $data['categoria_id'] = (int) $match['categoria_id'];
                if (!empty($match['subcategoria_id'])) {
                    $data['subcategoria_id'] = (int) $match['subcategoria_id'];
                }
                return $data;
            }
        }

        // 2. Fallback: buscar por nome sugerido pela IA no banco
        $sugerida = $data['categoria_sugerida'] ?? null;
        if ($sugerida !== null && $sugerida !== '') {
            $categoria = Categoria::where('user_id', $userId)
                ->whereRaw('LOWER(nome) LIKE ?', ['%' . mb_strtolower(trim($sugerida)) . '%'])
                ->first();
            if ($categoria) {
                $data['categoria_id'] = $categoria->id;
                return $data;
            }
        }

        return $data;
    }

    /**
     * Tenta resolver o cartão de crédito mencionado na mensagem.
     * Se o usuário disse "no nubank" e tem um cartão Nubank, auto-preenche cartao_credito_id.
     */
    private function resolveCartaoCredito(array $data, string $message, int $userId): array
    {
        // Se não é cartão de crédito, não precisa resolver
        if (($data['forma_pagamento'] ?? null) !== 'cartao_credito') {
            return $data;
        }

        // Se já tem cartao_credito_id, não precisa resolver
        if (!empty($data['cartao_credito_id'])) {
            return $data;
        }

        // Tentar detectar nome do cartão na mensagem
        $cardNamePattern = '/(?:no|na|do|da|pelo|pela)\s+(nubank|inter|ita[úu]|itau|bradesco|santander|bb|banco\s*do\s*brasil|sicredi|sicoob|c6|c6\s*bank|original|bmg|pan|neon|next|will|digio|picpay|pagbank|mercado\s*pago|ame|stone|safra|caixa|banrisul|btg)/iu';

        $cardName = null;
        if (preg_match($cardNamePattern, $message, $m)) {
            $cardName = trim($m[1]);
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

        // Se detectou nome, tentar match por nome do cartão
        if ($cardName !== null) {
            $cardNameLower = mb_strtolower($cardName);
            foreach ($cartoes as $cartao) {
                if (str_contains(mb_strtolower($cartao->nome_cartao), $cardNameLower)) {
                    $data['cartao_credito_id'] = $cartao->id;
                    $data['_cartao_nome'] = $cartao->nome_cartao;
                    return $data;
                }
            }
        }

        // Não conseguiu resolver — será pedido ao usuário (via cards list na confirmação ou multi-turno)
        return $data;
    }

    private function extractMeta(string $message): array
    {
        $data = [];

        // Normalizar valores coloquiais
        $msgNorm = $this->normalizeColloquialValues($message);

        // valor: R$ 5000, 5000 reais, 10k, etc.
        if (preg_match('/R?\$?\s*(\d{1,3}(?:\.\d{3})*[,\.]\d{2}|\d+(?:[,\.]\d{1,2})?)\s*(?:reais|conto[s]?|pila[s]?)?/iu', $msgNorm, $m)) {
            $valor = str_replace('.', '', $m[1]);
            $valor = str_replace(',', '.', $valor);
            $data['valor_alvo'] = (float) $valor;
        }

        // titulo: "meta de <titulo>", "meta para <titulo>", "quero juntar X pra <titulo>"
        if (preg_match('/meta\s+(?:de|para|pra)\s+(.{3,100})/iu', $message, $m)) {
            $titulo = trim($m[1]);
            $titulo = preg_replace('/\b(?:R?\$?\s*[\d.,]+\s*(?:reais|conto[s]?|pila[s]?)?)\b/iu', '', $titulo);
            $titulo = preg_replace('/\b(?:de|no valor|com valor|at[eé])\s*$/iu', '', $titulo);
            $titulo = trim($titulo, " \t\n\r\0\x0B,.");
            if (mb_strlen($titulo) >= 2) {
                $data['titulo'] = mb_substr($titulo, 0, 150);
            }
        } elseif (preg_match('/(?:juntar|economizar|guardar|poupar)\s+(?:R?\$?\s*[\d.,kK]+\s*(?:reais|conto[s]?|pila[s]?)?\s+)?(?:pra|para|pro)\s+(.{3,100})/iu', $message, $m)) {
            // "quero juntar 10k pra uma viagem"
            $titulo = trim($m[1]);
            $titulo = preg_replace('/\b(?:R?\$?\s*[\d.,]+\s*(?:reais)?)\b/iu', '', $titulo);
            $titulo = trim($titulo, " \t\n\r\0\x0B,.");
            if (mb_strlen($titulo) >= 2) {
                $data['titulo'] = mb_substr($titulo, 0, 150);
            }
        }

        return $data;
    }

    private function extractOrcamento(string $message): array
    {
        $data = [];

        // Normalizar valores coloquiais
        $msgNorm = $this->normalizeColloquialValues($message);

        // valor_limite
        if (preg_match('/R?\$?\s*(\d{1,3}(?:\.\d{3})*[,\.]\d{2}|\d+(?:[,\.]\d{1,2})?)\s*(?:reais|conto[s]?|pila[s]?)?/iu', $msgNorm, $m)) {
            $valor = str_replace('.', '', $m[1]);
            $valor = str_replace(',', '.', $valor);
            $data['valor_limite'] = (float) $valor;
        }

        // mes e ano
        $meses = [
            'janeiro' => 1,
            'fevereiro' => 2,
            'mar[çc]o' => 3,
            'abril' => 4,
            'maio' => 5,
            'junho' => 6,
            'julho' => 7,
            'agosto' => 8,
            'setembro' => 9,
            'outubro' => 10,
            'novembro' => 11,
            'dezembro' => 12,
        ];
        foreach ($meses as $pattern => $num) {
            if (preg_match('/\b' . $pattern . '\b/iu', $message)) {
                $data['mes'] = $num;
                break;
            }
        }
        if (!isset($data['mes'])) {
            $data['mes'] = (int) date('m');
        }

        if (preg_match('/\b(20\d{2})\b/', $message, $m)) {
            $data['ano'] = (int) $m[1];
        } else {
            $data['ano'] = (int) date('Y');
        }

        return $data;
    }

    private function extractCategoria(string $message): array
    {
        $data = [];

        // tipo
        if (preg_match('/\b(receita|despesa|transferencia|transfer[eê]ncia|ambas)\b/iu', $message, $m)) {
            $tipo = mb_strtolower($m[1]);
            $tipo = str_replace(['transferência', 'transferencia'], 'transferencia', $tipo);
            $data['tipo'] = $tipo;
        }

        // nome: "categoria <nome>", after tipo
        if (preg_match('/categoria\s+(.{2,60})/iu', $message, $m)) {
            $nome = trim($m[1]);
            $nome = preg_replace('/\b(?:tipo|de\s+(?:receita|despesa|transferencia|ambas))\b/iu', '', $nome);
            $nome = preg_replace('/\b(?:receita|despesa|transferencia|ambas)\b/iu', '', $nome);
            $nome = trim($nome, " \t\n\r\0\x0B,.");
            if (mb_strlen($nome) >= 2) {
                $data['nome'] = mb_substr($nome, 0, 100);
            }
        }

        return $data;
    }

    private function extractSubcategoria(string $message): array
    {
        $data = [];

        // nome da subcategoria
        if (preg_match('/sub[\s-]?categoria\s+(.{2,60})/iu', $message, $m)) {
            $nome = trim($m[1]);
            $nome = preg_replace('/\b(?:em|na|no|para|da|do)\s+.+$/iu', '', $nome);
            $nome = trim($nome, " \t\n\r\0\x0B,.");
            if (mb_strlen($nome) >= 2) {
                $data['nome'] = mb_substr($nome, 0, 100);
            }
        }

        return $data;
    }

    private function extractConta(string $message): array
    {
        $data = [];

        // Detectar instituição/banco
        $bancos = [
            'nubank'             => 'Nubank',
            'inter'              => 'Inter',
            'ita[úu]|itau'       => 'Itaú',
            'bradesco'           => 'Bradesco',
            'santander'          => 'Santander',
            'banco\s*do\s*brasil|bb' => 'Banco do Brasil',
            'caixa'              => 'Caixa',
            'sicredi'            => 'Sicredi',
            'sicoob'             => 'Sicoob',
            'c6|c6\s*bank'       => 'C6 Bank',
            'neon'               => 'Neon',
            'next'               => 'Next',
            'pagbank'            => 'PagBank',
            'picpay'             => 'PicPay',
            'mercado\s*pago'     => 'Mercado Pago',
            'banrisul'           => 'Banrisul',
            'safra'              => 'Safra',
            'btg'                => 'BTG',
            'original'           => 'Original',
            'will'               => 'Will Bank',
            'digio'              => 'Digio',
        ];

        foreach ($bancos as $pattern => $label) {
            if (preg_match('/\b(?:' . $pattern . ')\b/iu', $message)) {
                $data['instituicao'] = $label;
                $data['nome'] = $label;
                break;
            }
        }

        // Tipo de conta
        if (preg_match('/\b(?:poupan[çc]a)\b/iu', $message)) {
            $data['tipo_conta'] = 'conta_poupanca';
        } elseif (preg_match('/\b(?:corrente)\b/iu', $message)) {
            $data['tipo_conta'] = 'conta_corrente';
        } elseif (preg_match('/\b(?:carteira|dinheiro|cash)\b/iu', $message)) {
            $data['tipo_conta'] = 'carteira';
        } elseif (preg_match('/\b(?:investimento|cdb|tesouro|a[çc][ãa]o|a[çc][oõ]es|fundo)\b/iu', $message)) {
            $data['tipo_conta'] = 'investimento';
        } else {
            $data['tipo_conta'] = 'conta_corrente';
        }

        // Saldo inicial com normalização
        $msgNorm = $this->normalizeColloquialValues($message);
        if (preg_match('/(?:saldo|com)\s+(?:de\s+)?R?\$?\s*(\d{1,3}(?:\.\d{3})*[,\.]\d{2}|\d+(?:[,\.]\d{1,2})?)/iu', $msgNorm, $m)) {
            $valor = str_replace('.', '', $m[1]);
            $valor = str_replace(',', '.', $valor);
            $data['saldo_inicial'] = (float) $valor;
        } else {
            $data['saldo_inicial'] = 0.0;
        }

        // Nome customizado — "conta <nome>"
        if (empty($data['nome'])) {
            if (preg_match('/conta\s+(?:banc[áa]ria\s+)?(?:no|do|da|na)?\s*(.{2,50})/iu', $message, $m)) {
                $nome = trim($m[1]);
                $nome = preg_replace('/\b(?:corrente|poupan[çc]a|com\s+saldo|saldo|R?\$?\s*[\d.,]+)\b/iu', '', $nome);
                $nome = trim($nome, " \t\n\r\0\x0B,.");
                if (mb_strlen($nome) >= 2) {
                    $data['nome'] = mb_substr($nome, 0, 100);
                }
            }
        }

        return $data;
    }

    // ─── LLM fallback ───────────────────────────────────────────

    private function extractWithAI(string $message, string $entityType, array $partial): array
    {
        try {
            // Try function calling first (structured output, guaranteed valid JSON)
            $schema = EntitySchemas::forEntity($entityType);
            if ($schema !== null) {
                $result = $this->provider->chatWithTools(
                    "Extraia os dados de criação de {$entityType} desta mensagem do usuário brasileiro:\n\"{$message}\"",
                    [$schema],
                    [
                        'temperature'   => 0.1,
                        'max_tokens'    => 300,
                        'system_prompt' => "Você é um assistente financeiro brasileiro. Extraia os dados da mensagem e chame a função apropriada. Hoje é " . date('Y-m-d') . ". Valor monetário em BRL.",
                    ]
                );

                if ($result !== null) {
                    // Merge: regex has priority (already extracted)
                    return array_merge($result, $partial);
                }
            }

            // Fallback: free-text JSON extraction
            $prompt = $this->buildExtractionPrompt($message, $entityType, $partial);
            $response = $this->provider->chat($prompt, []);

            $json = $this->parseJsonResponse($response);
            if ($json === null) {
                return $partial;
            }

            return array_merge($json, $partial);
        } catch (\Throwable) {
            return $partial;
        }
    }

    private function buildExtractionPrompt(string $message, string $entityType, array $partial): string
    {
        $fields = match ($entityType) {
            'lancamento'   => 'tipo (receita/despesa), data (YYYY-MM-DD), valor (number), descricao (string), forma_pagamento (pix/cartao_credito/cartao_debito/dinheiro/boleto/null), eh_parcelado (bool), total_parcelas (number/null), nome_cartao (string/null)',
            'meta'         => 'titulo (string), valor_alvo (number)',
            'orcamento'    => 'categoria_id (number), valor_limite (number), mes (1-12), ano (YYYY)',
            'categoria'    => 'nome (string), tipo (receita/despesa/transferencia/ambas)',
            'subcategoria' => 'nome (string)',
            'conta'        => 'nome (string), instituicao (string/null), tipo_conta (conta_corrente/conta_poupanca/carteira/investimento/outro), saldo_inicial (number)',
            default => '',
        };

        $already = !empty($partial) ? 'Já extraído: ' . json_encode($partial, JSON_UNESCAPED_UNICODE) . '. ' : '';

        return "Extraia os campos de criação de {$entityType} da mensagem do usuário. " .
            "Campos esperados: {$fields}. {$already}" .
            "Retorne APENAS um JSON com os campos encontrados, sem explicação. " .
            "Se não conseguir extrair um campo, omita-o do JSON.\n\n" .
            "Mensagem: \"{$message}\"";
    }

    private function parseJsonResponse(string $response): ?array
    {
        if (preg_match('/\{[^}]+\}/s', $response, $match)) {
            $data = json_decode($match[0], true);
            return is_array($data) ? $data : null;
        }
        return null;
    }

    // ─── Validation ─────────────────────────────────────────────

    private function getMissingFields(array $data, string $entityType): array
    {
        $required = match ($entityType) {
            'lancamento'   => ['tipo', 'data', 'valor'],
            'meta'         => ['titulo', 'valor_alvo'],
            'orcamento'    => ['valor_limite'],
            'categoria'    => ['nome', 'tipo'],
            'subcategoria' => ['nome'],
            'conta'        => ['nome'],
            default        => [],
        };

        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    private function validate(array $data, string $entityType, int $userId): array
    {
        return match ($entityType) {
            'lancamento'   => LancamentoValidator::validateCreate($data),
            'meta'         => MetaValidator::validateCreate($data),
            'orcamento'    => $this->validateOrcamento($data),
            'categoria'    => CategoriaValidator::validateCreate($data),
            'subcategoria' => SubcategoriaValidator::validateCreate($data),
            'conta'        => ContaValidator::validateCreate($data),
            default        => [],
        };
    }

    private function validateOrcamento(array $data): array
    {
        $errors = OrcamentoValidator::validateSave($data);
        $monthErrors = OrcamentoValidator::validateMonth($data);
        return array_merge($errors, $monthErrors);
    }

    // ─── Preview / Labels ───────────────────────────────────────

    private function formatPreview(array $data, string $entityType): string
    {
        return match ($entityType) {
            'lancamento'   => $this->previewLancamento($data),
            'meta'         => $this->previewMeta($data),
            'orcamento'    => $this->previewOrcamento($data),
            'categoria'    => $this->previewCategoria($data),
            'subcategoria' => $this->previewSubcategoria($data),
            'conta'        => $this->previewConta($data),
            default        => '📋 Entidade a ser criada.',
        };
    }

    private function previewLancamento(array $d): string
    {
        $isCartao = ($d['forma_pagamento'] ?? null) === 'cartao_credito';
        $tipo = ucfirst($d['tipo'] ?? 'despesa');
        $valor = (float) ($d['valor'] ?? 0);
        $desc = $d['descricao'] ?? 'Sem descrição';
        $dataFormatted = isset($d['data']) ? date('d/m/Y', strtotime($d['data'])) : date('d/m/Y');

        if ($isCartao) {
            // Preview de compra no cartão de crédito
            $cartaoNome = $d['_cartao_nome'] ?? 'Cartão de Crédito';
            $icon = '💳';

            $lines = ["{$icon} **Compra no Cartão**: {$cartaoNome}"];

            if (!empty($d['eh_parcelado']) && !empty($d['total_parcelas'])) {
                $parcelas = (int) $d['total_parcelas'];
                $valorParcela = $valor / $parcelas;
                $valorParcelaFmt = 'R$ ' . number_format($valorParcela, 2, ',', '.');
                $valorTotalFmt = 'R$ ' . number_format($valor, 2, ',', '.');
                $lines[] = "💵 Valor: **{$valorTotalFmt}** ({$parcelas}x de {$valorParcelaFmt})";
            } else {
                $valorFmt = 'R$ ' . number_format($valor, 2, ',', '.');
                $lines[] = "💵 Valor: **{$valorFmt}** (à vista)";
            }

            $lines[] = "📝 Descrição: {$desc}";
            $lines[] = "📅 Data: {$dataFormatted}";

            // Mostrar categoria se resolvida
            if (!empty($d['categoria_id'])) {
                $catNome = Categoria::find($d['categoria_id'])?->nome;
                if ($catNome) {
                    $lines[] = "📂 Categoria: **{$catNome}**";
                }
            }

            // Calcular fatura de destino se tiver cartao_credito_id
            if (!empty($d['cartao_credito_id'])) {
                $faturaInfo = $this->calcFaturaDestino($d['cartao_credito_id'], $d['data'] ?? date('Y-m-d'));
                if ($faturaInfo) {
                    $lines[] = "📋 Vai para a fatura de **{$faturaInfo}**";
                }
            }

            return implode("\n", $lines);
        }

        // Preview de lançamento normal
        $icon = ($d['tipo'] ?? 'despesa') === 'receita' ? '💰' : '💸';
        $valorFmt = 'R$ ' . number_format($valor, 2, ',', '.');

        $lines = ["{$icon} **{$tipo}**: {$desc}"];
        $lines[] = "📅 Data: {$dataFormatted}";
        $lines[] = "💵 Valor: {$valorFmt}";

        // Mostrar forma de pagamento se detectada
        $fp = $d['forma_pagamento'] ?? null;
        if ($fp && $fp !== 'cartao_credito') {
            $fpLabel = match ($fp) {
                'pix'            => 'PIX',
                'cartao_debito'  => 'Cartão de Débito',
                'dinheiro'       => 'Dinheiro',
                'boleto'         => 'Boleto',
                'deposito'       => 'Depósito',
                'transferencia'  => 'Transferência',
                default          => ucfirst($fp),
            };
            $lines[] = "💳 Pagamento: {$fpLabel}";
        }

        // Mostrar categoria se resolvida
        if (!empty($d['categoria_id'])) {
            $catNome = Categoria::find($d['categoria_id'])?->nome;
            if ($catNome) {
                $lines[] = "📂 Categoria: **{$catNome}**";
            }
        }

        // Mostrar conta se selecionada
        if (!empty($d['conta_id'])) {
            $conta = Conta::find($d['conta_id']);
            if ($conta) {
                $lines[] = "🏦 Conta: **{$conta->nome}**";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Calcula em qual fatura mês/ano a compra cairá, baseado no dia_fechamento do cartão.
     */
    private function calcFaturaDestino(int $cartaoId, string $dataCompra): ?string
    {
        try {
            $cartao = CartaoCredito::find($cartaoId);
            if (!$cartao || !$cartao->dia_fechamento) {
                return null;
            }

            $data = new \DateTime($dataCompra);
            $diaCompra = (int) $data->format('d');
            $mesCompra = (int) $data->format('m');
            $anoCompra = (int) $data->format('Y');

            // Se a compra é ANTES do dia de fechamento, vai para a fatura do mês atual
            // Se é DEPOIS do fechamento, vai para a fatura do próximo mês
            if ($diaCompra > $cartao->dia_fechamento) {
                $mesCompra++;
                if ($mesCompra > 12) {
                    $mesCompra = 1;
                    $anoCompra++;
                }
            }

            $meses = [
                '',
                'Janeiro',
                'Fevereiro',
                'Março',
                'Abril',
                'Maio',
                'Junho',
                'Julho',
                'Agosto',
                'Setembro',
                'Outubro',
                'Novembro',
                'Dezembro'
            ];

            return $meses[$mesCompra] . '/' . $anoCompra;
        } catch (\Throwable) {
            return null;
        }
    }

    private function previewMeta(array $d): string
    {
        $valor = 'R$ ' . number_format((float) ($d['valor_alvo'] ?? 0), 2, ',', '.');
        return "🎯 **Meta**: {$d['titulo']}\n💵 Valor alvo: {$valor}";
    }

    private function previewOrcamento(array $d): string
    {
        $valor = 'R$ ' . number_format((float) ($d['valor_limite'] ?? 0), 2, ',', '.');
        $mes = str_pad((string) ($d['mes'] ?? date('m')), 2, '0', STR_PAD_LEFT);
        $ano = $d['ano'] ?? date('Y');
        return "📊 **Orçamento**: {$valor}\n📅 Período: {$mes}/{$ano}";
    }

    private function previewCategoria(array $d): string
    {
        $tipo = ucfirst($d['tipo'] ?? '');
        return "📁 **Categoria**: {$d['nome']}\n🏷️ Tipo: {$tipo}";
    }

    private function previewSubcategoria(array $d): string
    {
        return "📂 **Subcategoria**: {$d['nome']}";
    }

    private function previewConta(array $d): string
    {
        $nome = $d['nome'] ?? 'Conta';
        $instituicao = $d['instituicao'] ?? '';
        $tipoConta = match ($d['tipo_conta'] ?? 'conta_corrente') {
            'conta_corrente' => 'Conta Corrente',
            'conta_poupanca' => 'Poupança',
            'carteira'       => 'Carteira',
            'investimento'   => 'Investimento',
            default          => ucfirst($d['tipo_conta'] ?? 'outro'),
        };
        $saldo = 'R$ ' . number_format((float) ($d['saldo_inicial'] ?? 0), 2, ',', '.');

        $lines = ["🏦 **Conta**: {$nome}"];
        if ($instituicao) {
            $lines[] = "🏛️ Instituição: {$instituicao}";
        }
        $lines[] = "📋 Tipo: {$tipoConta}";
        $lines[] = "💰 Saldo inicial: {$saldo}";

        return implode("\n", $lines);
    }

    private function getEntityLabel(string $entityType): string
    {
        return match ($entityType) {
            'lancamento'   => 'um lançamento',
            'meta'         => 'uma meta',
            'orcamento'    => 'um orçamento',
            'categoria'    => 'uma categoria',
            'subcategoria' => 'uma subcategoria',
            'conta'        => 'uma conta bancária',
            default        => 'uma entidade',
        };
    }

    private function getFieldLabels(string $entityType): array
    {
        return match ($entityType) {
            'lancamento' => [
                'tipo' => 'Tipo (receita/despesa)',
                'data' => 'Data',
                'valor' => 'Valor',
                'descricao' => 'Descrição',
                'cartao_credito_id' => 'Cartão de crédito',
            ],
            'meta' => [
                'titulo' => 'Título',
                'valor_alvo' => 'Valor alvo',
            ],
            'orcamento' => [
                'categoria_id' => 'Categoria',
                'valor_limite' => 'Valor limite',
            ],
            'categoria' => [
                'nome' => 'Nome',
                'tipo' => 'Tipo (receita/despesa/transferencia/ambas)',
            ],
            'subcategoria' => [
                'nome' => 'Nome',
            ],
            'conta' => [
                'nome' => 'Nome da conta',
                'instituicao' => 'Banco/Instituição',
                'tipo_conta' => 'Tipo (corrente/poupança/carteira)',
                'saldo_inicial' => 'Saldo inicial',
            ],
            default => [],
        };
    }

    private function getExample(string $entityType): string
    {
        return match ($entityType) {
            'lancamento'   => '"criar despesa de R$ 150 de conta de luz hoje" ou "comprei geladeira no nubank por 1500 em 10x"',
            'meta'         => '"criar meta de viagem de R$ 5.000" ou "quero juntar 10k pra um carro"',
            'orcamento'    => '"criar orçamento de R$ 800 para alimentação" ou "não quero gastar mais de 500 com lazer"',
            'categoria'    => '"criar categoria Pets tipo despesa"',
            'subcategoria' => '"criar subcategoria Ração"',
            'conta'        => '"criar conta no Nubank" ou "adicionar conta corrente no Itaú com saldo de 500"',
            default        => '',
        };
    }
}
