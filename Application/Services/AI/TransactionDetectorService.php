<?php

declare(strict_types=1);

namespace Application\Services\AI;

/**
 * Serviço de detecção e extração de transações financeiras a partir de texto.
 *
 * Responsabilidade única: extrair descrição, valor e tipo (despesa/receita)
 * de uma mensagem usando regex. Sem IA, sem banco, sem cache — puro regex.
 *
 * Usado por:
 *  - TransactionIntentRule (para verificar se mensagem contém valor)
 *  - TransactionExtractorHandler (para extrair dados estruturados)
 *  - WhatsAppWebhookController (para processar mensagens do WhatsApp)
 */
class TransactionDetectorService
{
    /**
     * Padrões regex para extração direta (0 tokens).
     * Cada padrão captura: (valor) e (descrição).
     * Ordem importa: padrões mais específicos primeiro.
     */
    private const EXTRACTION_PATTERNS = [
        // "gastei 40 no uber" / "paguei 32.50 de luz" / "comprei 120 de mercado"
        '/(?:gastei|paguei|pago|comprei)\s+(?:r\$\s*)?(\d+[\.,]?\d*)\s+(?:no|na|de|em|com|pro|pra|para)?\s*(.+)/iu',

        // "recebi 5000 de salário" / "ganhei 1500 freelance"
        '/(?:recebi|ganhei|entrou)\s+(?:r\$\s*)?(\d+[\.,]?\d*)\s+(?:de|do|da|em|com)?\s*(.+)/iu',

        // "uber 32.50" / "ifood 45.00" (descrição + valor com decimais)
        '/^([a-záàâãéèêíïóôõúüç\s]{2,20}?)\s+(?:r\$\s*)?(\d+[\.,]\d{1,2})\s*$/iu',

        // "uber 32" / "mercado 120" / "gasolina 80" (descrição + valor inteiro)
        '/^([a-záàâãéèêíïóôõúüç\s]{2,20}?)\s+(?:r\$\s*)?(\d{1,5})\s*$/iu',

        // "40 uber" / "32.50 ifood" (valor + descrição)
        '/^(?:r\$\s*)?(\d+[\.,]?\d*)\s+(.{2,})\s*$/iu',

        // "salário 5000" / "freela R$ 1.500"
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

    /**
     * Verifica se a mensagem contém um valor numérico que parece transação.
     * Helper rápido para o TransactionIntentRule.
     */
    public static function detectsValue(string $message): bool
    {
        return (bool) preg_match('/\b\d{1,5}(?:[.,]\d{1,2})?\b/', $message);
    }

    /**
     * Extrai dados de transação de uma mensagem.
     *
     * @return array{descricao: string, valor: float, tipo: string, data: string}|null
     */
    public static function extract(string $message): ?array
    {
        $normalized = mb_strtolower(trim($message));

        if ($normalized === '' || mb_strlen($normalized) < 3) {
            return null;
        }

        foreach (self::EXTRACTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $parsed = self::parseMatches($matches, $pattern, $normalized);
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

        return null;
    }

    /**
     * Parseia os matches do regex em dados estruturados.
     */
    private static function parseMatches(array $matches, string $pattern, string $normalized): ?array
    {
        // Determinar qual grupo é valor e qual é descrição
        // Padrões com ^([a-z → grupo 1 = descrição, grupo 2 = valor
        // Demais → grupo 1 = valor, grupo 2 = descrição
        $isDescFirst = str_contains($pattern, '^([a-z') || str_contains($pattern, '^(sal[');

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
        $valor = self::parseValue($valorStr);
        if ($valor <= 0) {
            return null;
        }

        // Determinar tipo (receita/despesa)
        $tipo = self::detectType($normalized);

        // Capitalizar descrição
        $descricao = mb_convert_case(trim($descricao), MB_CASE_TITLE);

        // Remover preposições soltas no início da descrição
        $descricao = preg_replace('/^(No|Na|De|Do|Da|Em|Com|Pro|Pra|Para)\s+/u', '', $descricao);
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
    private static function parseValue(string $raw): float
    {
        // Remover R$, espaços
        $clean = preg_replace('/[rR]\$\s*/', '', $raw);

        // Detectar formato: se tem ponto E vírgula, ponto é milhar
        if (str_contains($clean, '.') && str_contains($clean, ',')) {
            $clean = str_replace('.', '', $clean);  // remove milhar
            $clean = str_replace(',', '.', $clean);  // vírgula → ponto decimal
        } elseif (str_contains($clean, ',')) {
            $clean = str_replace(',', '.', $clean);  // vírgula → ponto decimal
        }
        // Se só tem ponto com 1-2 dígitos depois → já é decimal
        // Se só tem ponto com 3+ dígitos depois → é milhar
        elseif (preg_match('/\.(\d{3,})$/', $clean)) {
            $clean = str_replace('.', '', $clean);
        }

        return (float) $clean;
    }

    /**
     * Detecta se é receita ou despesa.
     */
    private static function detectType(string $message): string
    {
        foreach (self::INCOME_KEYWORDS as $keyword) {
            if (str_contains($message, $keyword)) {
                return 'receita';
            }
        }

        return 'despesa';
    }
}
