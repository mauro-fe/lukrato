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
 * Handler de conversa geral com payload estruturado para melhor UX no frontend.
 */
class ChatHandlerV2 implements AIHandlerInterface
{
    private ?AIProvider $provider = null;

    private const TRIVIAL_PATTERNS = [
        '^teste$|^test$|^testing$|^testando$' =>
        'Teste recebido! Estou funcionando. Como posso ajudar?',
        '^ok$|^beleza$|^blz$|^valeu$|^obrigad|^thanks|^thx' =>
        'Estou por aqui se quiser continuar.',
        'quem [eé] voc[eê]|o que voc[eê] faz|como funciona|qual seu nome|teu nome' =>
        'Sou o Lukra, assistente financeiro do Lukrato. Posso ajudar a registrar gastos e receitas, analisar seus gastos e organizar metas e orcamentos.',
    ];

    private const ACTION_HINTS = [
        'gast[eio]|comprei|paguei|pag[ao]|cust[eo]u|torrei|larguei|meti\s+\d|soltei|boleto|fatura|prestac' =>
        ['action_hint' => 'create_lancamento', 'suggestion' => 'Quer transformar isso em um lancamento agora?'],
        'ganhei|recebi|dep[oó]sit|entrou|sal[áa]rio|freelance|freela' =>
        ['action_hint' => 'create_lancamento_receita', 'suggestion' => 'Posso te ajudar a registrar essa receita.'],
        'quero comprar|sonho|quero viajar|juntar\s+dinheiro|economizar\s+para|guardar\s+para|objetivo' =>
        ['action_hint' => 'create_meta', 'suggestion' => 'Vale a pena transformar isso em uma meta acompanhavel.'],
        'gastando\s+muito|gasto\s+demais|preciso\s+economizar|estou\s+no\s+vermelho|apertad[oa]|sem\s+dinheiro|quebrad[oa]|endividad[oa]' =>
        ['action_hint' => 'create_orcamento', 'suggestion' => 'Um orcamento pode te dar mais controle sobre isso.'],
    ];

    private const CAPABILITY_PATTERNS = [
        '/\b(?:voc[eê]|vc|tu)\b.{0,40}\b(?:consegue|pode|nao consegue|não consegue|nao pode|não pode)\b.{0,60}\b(?:lan[cç]ar|registrar|anotar)\b/iu',
        '/\b(?:lan[cç]a|registre|registra|anota)\b.{0,30}\b(?:pra mim|por mim)\b/iu',
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
        $greetingResponse = $this->matchGreeting($request);
        if ($greetingResponse !== null) {
            return $greetingResponse;
        }

        $trivialResponse = $this->matchTrivial($request->message);
        if ($trivialResponse !== null) {
            return AIResponseDTO::fromComputed(
                $trivialResponse,
                [
                    'source' => 'trivial',
                    'quick_replies' => $this->getDefaultQuickReplies(),
                ],
                IntentType::CHAT,
            );
        }

        $capabilityResponse = $this->matchCapabilityQuestion($request->message);
        if ($capabilityResponse !== null) {
            return $capabilityResponse;
        }

        try {
            $context = $request->context;
            if (!empty($context)) {
                $context = ContextCompressor::compress($context, $request->message);
                $context = PromptOptimizer::optimize($context);
            }

            $response = $this->provider?->chat($request->message, $context);
            if ($response === null || trim($response) === '') {
                return AIResponseDTO::fail(
                    'O assistente de IA esta indisponivel no momento. Tente novamente em instantes.',
                    IntentType::CHAT,
                );
            }

            $actionHint = $this->detectActionHint($request->message);
            $data = [
                'response' => $response,
                'quick_replies' => $actionHint !== null
                    ? $this->buildQuickRepliesForAction($actionHint['action_hint'], $request->message)
                    : $this->getDefaultQuickReplies(),
            ];

            if ($actionHint !== null) {
                $data['action_hint'] = $actionHint['action_hint'];
                $data['suggestion'] = $actionHint['suggestion'];
            }

            return AIResponseDTO::fromLLM($response, $data, IntentType::CHAT);
        } catch (\Throwable) {
            return AIResponseDTO::fail(
                'O assistente de IA esta indisponivel no momento. Tente novamente em instantes.',
                IntentType::CHAT,
            );
        }
    }

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
            $hora < 12 => 'Bom dia',
            $hora < 18 => 'Boa tarde',
            default => 'Boa noite',
        };

        $nome = $request->context['usuario_nome'] ?? '';
        $nomeDisplay = $nome ? ", {$nome}" : '';
        $dica = match (true) {
            $hora >= 6 && $hora < 10 => 'Que tal revisar seus gastos de ontem?',
            $hora >= 10 && $hora < 14 => 'Como posso ajudar com suas financas hoje?',
            $hora >= 14 && $hora < 18 => 'Precisa registrar algum gasto ou consultar algo?',
            $hora >= 18 && $hora < 22 => 'Quer ver como foram seus gastos hoje?',
            default => 'Em que posso ajudar?',
        };

        return AIResponseDTO::fromComputed(
            "{$saudacao}{$nomeDisplay}! {$dica}",
            [
                'source' => 'greeting_contextual',
                'quick_replies' => $this->getDefaultQuickReplies(),
            ],
            IntentType::CHAT,
        );
    }

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

    private function matchCapabilityQuestion(string $message): ?AIResponseDTO
    {
        $normalized = mb_strtolower(trim($message));
        if ($normalized === '') {
            return null;
        }

        foreach (self::CAPABILITY_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized) !== 1) {
                continue;
            }

            return AIResponseDTO::fromComputed(
                'Consigo sim. Me mande a transação em uma frase, como "mercado 30 hoje" ou "recebi freelance 500 ontem". Se faltar algum dado, eu pergunto so o que falta.',
                [
                    'source' => 'capability_transaction',
                    'action_hint' => 'create_lancamento',
                    'suggestion' => 'Se quiser, posso começar por um gasto ou uma receita agora.',
                    'quick_replies' => $this->getTransactionCaptureQuickReplies(),
                ],
                IntentType::CHAT,
            );
        }

        return null;
    }

    /**
     * @return array<int, array{label:string,message:string,mode:string}>
     */
    private function getDefaultQuickReplies(): array
    {
        return [
            ['label' => 'Registrar gasto', 'message' => 'quero registrar um gasto', 'mode' => 'fill'],
            ['label' => 'Ver gastos do mês', 'message' => 'quanto gastei este mês?', 'mode' => 'send'],
            ['label' => 'Criar meta', 'message' => 'quero criar uma meta', 'mode' => 'fill'],
        ];
    }

    /**
     * @return array<int, array{label:string,message:string,mode:string}>
     */
    private function getTransactionCaptureQuickReplies(): array
    {
        return [
            ['label' => 'Registrar gasto', 'message' => 'quero registrar um gasto', 'mode' => 'fill'],
            ['label' => 'Registrar receita', 'message' => 'quero registrar uma receita', 'mode' => 'fill'],
            ['label' => 'Ver exemplos', 'message' => '/help', 'mode' => 'send'],
        ];
    }

    /**
     * @return array<int, array{label:string,message:string,mode:string}>
     */
    private function buildQuickRepliesForAction(string $actionHint, string $message): array
    {
        $normalizedMessage = trim($message) !== '' ? trim($message) : 'isso';

        return match ($actionHint) {
            'create_lancamento' => [
                ['label' => 'Registrar agora', 'message' => "registre este gasto: {$normalizedMessage}", 'mode' => 'fill'],
                ['label' => 'Ver gastos do mês', 'message' => 'quanto gastei este mês?', 'mode' => 'send'],
            ],
            'create_lancamento_receita' => [
                ['label' => 'Registrar receita', 'message' => "registre esta receita: {$normalizedMessage}", 'mode' => 'fill'],
                ['label' => 'Ver saldo atual', 'message' => 'qual e o meu saldo atual?', 'mode' => 'send'],
            ],
            'create_meta' => [
                ['label' => 'Criar meta', 'message' => "quero criar uma meta para {$normalizedMessage}", 'mode' => 'fill'],
                ['label' => 'Planejar valor mensal', 'message' => 'me ajude a planejar quanto guardar por mês', 'mode' => 'send'],
            ],
            'create_orcamento' => [
                ['label' => 'Criar orcamento', 'message' => 'quero criar um orcamento para controlar isso', 'mode' => 'fill'],
                ['label' => 'Analisar categoria', 'message' => 'em que categoria estou gastando mais?', 'mode' => 'send'],
            ],
            default => $this->getDefaultQuickReplies(),
        };
    }

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
