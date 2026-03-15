<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\ContextCompressor;
use Application\Services\AI\PromptOptimizer;

/**
 * Handler para conversas gerais com o assistente de IA.
 * Sempre chama o LLM — é o handler de fallback padrão.
 *
 * Melhorias v2:
 *  - Saudações contextuais (bom dia/boa tarde/boa noite + nome do usuário)
 *  - Detecção de ação implícita na mensagem do usuário
 *  - Sugestões proativas baseadas no contexto financeiro
 */
class ChatHandler implements AIHandlerInterface
{
    private ?AIProvider $provider = null;

    /**
     * Padrões de mensagens triviais que podem ser respondidas sem LLM.
     * Formato: pattern => resposta template (com placeholders)
     */
    private const TRIVIAL_PATTERNS = [
        '^teste$|^test$|^testing$|^testando$' =>
        'Teste recebido! ✅ Estou funcionando. Como posso ajudar?',
        '^ok$|^beleza$|^blz$|^valeu$|^obrigad|^thanks|^thx' =>
        'Estou aqui se precisar de mais alguma coisa! 😊',
        'quem [eé] voc[eê]|o que voc[eê] faz|como funciona|qual seu nome|teu nome' =>
        'Sou o Lukra, assistente financeiro do Lukrato! 🤖 Posso ajudar com: registrar gastos e receitas, acompanhar metas e orçamentos, analisar seus gastos, e dar dicas financeiras. É só me dizer!',
    ];

    /**
     * Padrões de ação implícita na mensagem do usuário.
     * Se detectados, incluímos um "nudge" (suggestion) nos dados da resposta.
     */
    private const ACTION_HINTS = [
        // Menção de gasto/compra → sugerir registrar lançamento
        'gast[eio]|comprei|paguei|pag[ao]|cust[eo]u|torrei|larguei|meti\s+\d|soltei|boleto|fatura|prestação' =>
        ['action_hint' => 'create_lancamento', 'suggestion' => 'Quer que eu registre isso como um lançamento?'],
        // Menção de receita → sugerir registrar
        'ganhei|recebi|dep[oó]sit|entrou|sal[áa]rio|freelance|freela' =>
        ['action_hint' => 'create_lancamento_receita', 'suggestion' => 'Quer que eu registre essa receita?'],
        // Menção de objetivo/sonho → sugerir meta
        'quero comprar|sonho|quero viajar|juntar\s+dinheiro|economizar\s+para|guardar\s+para|objetivo' =>
        ['action_hint' => 'create_meta', 'suggestion' => 'Que tal criar uma meta pra isso? Eu posso te ajudar a acompanhar!'],
        // Reclamação de gasto excessivo → sugerir orçamento
        'gastando\s+muito|gasto\s+demais|preciso\s+economizar|estou\s+no\s+vermelho|apertad[oa]|sem\s+dinheiro|quebrad[oa]|endividad[oa]' =>
        ['action_hint' => 'create_orcamento', 'suggestion' => 'Já pensou em criar um orçamento pra controlar melhor? Posso te ajudar!'],
    ];

    public function setProvider(AIProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function supports(IntentType $intent): bool
    {
        return $intent === IntentType::CHAT;
    }

    public function handle(AIRequestDTO $request): AIResponseDTO
    {
        // Saudações contextuais (0 tokens) com nome do usuário
        $greetingResponse = $this->matchGreeting($request);
        if ($greetingResponse !== null) {
            return $greetingResponse;
        }

        // Responder mensagens triviais sem LLM (0 tokens)
        $trivialResponse = $this->matchTrivial($request->message);
        if ($trivialResponse !== null) {
            return AIResponseDTO::fromComputed(
                $trivialResponse,
                ['source' => 'trivial'],
                IntentType::CHAT,
            );
        }

        try {
            // Comprimir contexto baseado na mensagem
            $context = $request->context;
            if (!empty($context)) {
                $context = ContextCompressor::compress($context, $request->message);
                $context = PromptOptimizer::optimize($context);
            }

            // Chamar LLM via provider
            $response = $this->provider->chat($request->message, $context);

            if ($response === null || trim($response) === '') {
                return AIResponseDTO::fail(
                    'O assistente de IA está indisponível no momento. Tente novamente em instantes.',
                    IntentType::CHAT,
                );
            }

            // Detectar ação implícita na mensagem do usuário
            $actionHint = $this->detectActionHint($request->message);
            $data = ['response' => $response];
            if ($actionHint !== null) {
                $data['action_hint'] = $actionHint['action_hint'];
                $data['suggestion'] = $actionHint['suggestion'];
            }

            return AIResponseDTO::fromLLM(
                $response,
                $data,
                IntentType::CHAT,
            );
        } catch (\Throwable $e) {
            return AIResponseDTO::fail(
                'O assistente de IA está indisponível no momento. Tente novamente em instantes.',
                IntentType::CHAT,
            );
        }
    }

    /**
     * Saudações contextuais com horário e nome do usuário.
     */
    private function matchGreeting(AIRequestDTO $request): ?AIResponseDTO
    {
        $normalized = mb_strtolower(trim($request->message));

        if (mb_strlen($normalized) > 30) {
            return null;
        }

        if (!preg_match('/^(?:oi|ol[áa]|ola|hey|eai|e\s*ai|fala|bom\s*dia|boa\s*tarde|boa\s*noite|hello|hi)\b/iu', $normalized)) {
            return null;
        }

        $hora = (int) date('H');
        $saudacao = match (true) {
            $hora < 12  => 'Bom dia',
            $hora < 18  => 'Boa tarde',
            default     => 'Boa noite',
        };

        $nome = $request->context['usuario_nome'] ?? '';
        $nomeDisplay = $nome ? ", {$nome}" : '';

        // Adicionar dica contextual baseada no horário
        $dica = match (true) {
            $hora >= 6 && $hora < 10   => 'Que tal começar o dia revisando seus gastos de ontem?',
            $hora >= 10 && $hora < 14  => 'Como posso ajudar com suas finanças hoje?',
            $hora >= 14 && $hora < 18  => 'Precisa registrar algum gasto ou consultar algo?',
            $hora >= 18 && $hora < 22  => 'Quer ver como foram seus gastos hoje?',
            default                     => 'Em que posso ajudar?',
        };

        return AIResponseDTO::fromComputed(
            "{$saudacao}{$nomeDisplay}! 👋 {$dica}",
            ['source' => 'greeting_contextual'],
            IntentType::CHAT,
        );
    }

    /**
     * Verifica se a mensagem é trivial e retorna resposta template (0 tokens).
     */
    private function matchTrivial(string $message): ?string
    {
        $normalized = mb_strtolower(trim($message));

        if (mb_strlen($normalized) > 60) {
            return null;
        }

        foreach (self::TRIVIAL_PATTERNS as $pattern => $response) {
            if (preg_match('/' . $pattern . '/iu', $normalized)) {
                return $response;
            }
        }

        return null;
    }

    /**
     * Detecta ação implícita na mensagem do usuário.
     * Retorna hint para o frontend exibir um botão de ação rápida.
     */
    private function detectActionHint(string $message): ?array
    {
        $normalized = mb_strtolower(trim($message));

        foreach (self::ACTION_HINTS as $pattern => $hint) {
            if (preg_match('/' . $pattern . '/iu', $normalized)) {
                return $hint;
            }
        }

        return null;
    }
}
