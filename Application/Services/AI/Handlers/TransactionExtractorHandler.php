<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Services\AI\AIService;
use Application\Services\AI\PromptBuilder;
use Application\Services\AI\Rules\CategoryRuleEngine;

/**
 * Handler para extração de transações financeiras a partir de linguagem natural.
 * Usado principalmente no WhatsApp: "gastei 40 no uber", "ifood 32.50", "salário 5000".
 *
 * Abordagem híbrida: regex para padrões simples, LLM para ambíguos.
 */
class TransactionExtractorHandler implements AIHandlerInterface
{
    /**
     * Padrões regex para extração direta (0 tokens).
     * Cada padrão captura: (valor) e (descrição).
     */
    private const EXTRACTION_PATTERNS = [
        // "gastei 40 no uber" / "paguei 32.50 de luz"
        '/(?:gastei|paguei|pago|comprei)\s+(?:r\$\s*)?(\d+[\.,]?\d*)\s+(?:no|na|de|em|com|pro|pra|para)?\s*(.+)/iu',

        // "recebi 5000 de salário" / "ganhei 1500 freelance"
        '/(?:recebi|ganhei|entrou)\s+(?:r\$\s*)?(\d+[\.,]?\d*)\s+(?:de|do|da|em|com)?\s*(.+)/iu',

        // "uber 40" / "ifood 32.50" (descrição + valor)
        '/^([a-záàâãéèêíïóôõúüç\s]{2,}?)\s+(?:r\$\s*)?(\d+[\.,]\d{2})\s*$/iu',

        // "uber 40" sem decimais
        '/^([a-záàâãéèêíïóôõúüç\s]{2,}?)\s+(?:r\$\s*)?(\d+)\s*$/iu',

        // "40 uber" / "32.50 ifood" (valor + descrição)
        '/^(?:r\$\s*)?(\d+[\.,]?\d*)\s+(.{2,})\s*$/iu',

        // "salário 5000" / "salário R$ 5.000"
        '/^(sal[áa]rio|freela|freelance|aluguel|mesada)\s+(?:r\$\s*)?(\d+[\.,]?\d*)\s*$/iu',
    ];

    /**
     * Keywords que indicam receita.
     */
    private const INCOME_KEYWORDS = [
        'recebi', 'ganhei', 'entrou', 'salário', 'salario', 'freelance', 'freela',
        'rendimento', 'dividendo', 'reembolso', 'devolução', 'devolvido', 'mesada',
        'aluguel recebido', 'renda',
    ];

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

        // Pass 1: Regex extraction (0 tokens)
        $extracted = $this->extractByRegex($message);

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

            return AIResponseDTO::fromRule(
                $this->formatConfirmation($result),
                $result,
                IntentType::EXTRACT_TRANSACTION,
            );
        }

        // Pass 2: LLM extraction
        return $this->extractWithAI($message, $request);
    }

    /**
     * Tenta extrair transação via regex.
     */
    private function extractByRegex(string $message): ?array
    {
        $normalized = mb_strtolower(trim($message));

        foreach (self::EXTRACTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return $this->parseMatches($matches, $pattern, $normalized);
            }
        }

        return null;
    }

    /**
     * Parseia os matches do regex em dados estruturados.
     */
    private function parseMatches(array $matches, string $pattern, string $normalized): ?array
    {
        // Determinar qual grupo é valor e qual é descrição
        // Padrões 1-2: grupo 1 = valor, grupo 2 = descrição
        // Padrões 3-4: grupo 1 = descrição, grupo 2 = valor
        // Padrão 5: grupo 1 = valor, grupo 2 = descrição
        // Padrão 6: grupo 1 = descrição, grupo 2 = valor

        $isDescFirst = str_contains($pattern, '^([a-z');

        if ($isDescFirst) {
            $descricao = trim($matches[1] ?? '');
            $valorStr  = trim($matches[2] ?? '');
        } else {
            $valorStr  = trim($matches[1] ?? '');
            $descricao = trim($matches[2] ?? '');
        }

        if ($descricao === '' || $valorStr === '') {
            return null;
        }

        // Sanitizar valor
        $valor = $this->parseValue($valorStr);
        if ($valor <= 0) {
            return null;
        }

        // Determinar tipo (receita/despesa)
        $tipo = $this->detectType($normalized);

        // Capitalizar descrição
        $descricao = mb_convert_case(trim($descricao), MB_CASE_TITLE);

        // Remover preposições soltas no início/fim da descrição
        $descricao = preg_replace('/^(no|na|de|do|da|em|com|pro|pra|para)\s+/iu', '', $descricao);
        $descricao = trim($descricao);

        return [
            'descricao' => $descricao,
            'valor'     => $valor,
            'tipo'      => $tipo,
            'data'      => date('Y-m-d'),
        ];
    }

    /**
     * Parseia string de valor para float.
     */
    private function parseValue(string $raw): float
    {
        // Remover R$, espaços
        $clean = preg_replace('/[rR]\$\s*/', '', $raw);
        // Se tem ponto e vírgula, o último separador é decimal
        $clean = str_replace('.', '', $clean); // remove separador de milhar
        $clean = str_replace(',', '.', $clean); // vírgula → ponto
        return (float) $clean;
    }

    /**
     * Detecta se é receita ou despesa.
     */
    private function detectType(string $message): string
    {
        foreach (self::INCOME_KEYWORDS as $keyword) {
            if (str_contains($message, $keyword)) {
                return 'receita';
            }
        }

        return 'despesa';
    }

    /**
     * Extração via LLM quando regex falha.
     */
    private function extractWithAI(string $message, AIRequestDTO $request): AIResponseDTO
    {
        try {
            $ai = new AIService();

            $systemPrompt = PromptBuilder::transactionExtractionSystem();
            $userPrompt   = PromptBuilder::transactionExtractionUser($message);

            // Usar chat com contexto mínimo (o prompt de sistema já instrui)
            $response = $ai->chat($userPrompt, []);

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

            return AIResponseDTO::fromLLM(
                $this->formatConfirmation($data),
                $data,
                IntentType::EXTRACT_TRANSACTION,
            );
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
