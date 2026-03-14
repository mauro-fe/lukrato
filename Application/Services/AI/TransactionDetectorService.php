<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\Services\AI\NLP\NumberNormalizer;

/**
 * Serviço de detecção e extração de transações financeiras a partir de texto.
 *
 * Responsabilidade única: extrair descrição, valor, tipo (despesa/receita),
 * forma de pagamento e parcelamento de uma mensagem usando regex.
 * Sem IA, sem banco, sem cache — puro regex.
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
        // "parcelei 3000 em 12x no inter" / "parcelei R$ 1500 em 10x de geladeira"
        '/(?:parcelei|parcelo)\s+(?:r\$\s*)?(\d+[\.,]?\d*)\s+(?:em\s+)?(\d{1,2})\s*x\s*(?:no|na|de|em|com)?\s*(.+)/iu',

        // "12x de 99 geladeira" / "3x de 150 sapato"
        '/^(\d{1,2})\s*x\s+(?:de\s+)?(?:r\$\s*)?(\d+[\.,]?\d*)\s+(.+)/iu',

        // "comprei geladeira no cartão por 1500" / "comprei sapato no crédito 300"
        '/(?:comprei|gastei)\s+(.+?)\s+(?:no\s+(?:cart[ãa]o|cr[ée]dito|d[ée]bito))\s*(?:por\s+)?(?:r\$\s*)?(\d+[\.,]?\d*)/iu',

        // "gastei 40 no uber" / "paguei 32.50 de luz" / "comprei 120 de mercado" / "torrei 200 no shopping"
        // Agora cobre mais verbos: custou, cobrou, pagou, gastou, etc.
        '/(?:gastei|paguei|pago|comprei|torrei|larguei|meti|soltei|botei|raspei|queimei|detonei|estourei|custou|cobrou|pagou|gastou|comprou|saquei|sacou)\s+(?:r\$\s*)?(\d[\d.,]*)\s*(?:conto[s]?|pila[s]?|reais)?\s+(?:no|na|de|em|com|pro|pra|para)?\s*(.+)/iu',

        // "recebi 5000 de salário" / "ganhei 1500 freelance"
        '/(?:recebi|ganhei|entrou)\s+(?:r\$\s*)?(\d+[\.,]?\d*)\s*(?:conto[s]?|pila[s]?|reais)?\s+(?:de|do|da|em|com)?\s*(.+)/iu',

        // "mandei pix 200 pro joão" / "fiz pix de 150 pra maria"
        '/(?:mandei\s+pix|fiz\s+pix)\s*(?:de\s+)?(?:r\$\s*)?(\d+[\.,]?\d*)\s+(?:pro|pra|para|de|em|com)?\s*(.+)/iu',

        // "uber 32.50" / "ifood 45.00" (descrição + valor com decimais)
        '/^([a-záàâãéèêíïóôõúüç\s]{2,30}?)\s+(?:r\$\s*)?(\d+[\.,]\d{1,2})\s*(?:conto[s]?|pila[s]?|reais)?\s*$/iu',

        // "uber 32" / "mercado 120" / "gasolina 80" (descrição + valor inteiro)
        '/^([a-záàâãéèêíïóôõúüç\s]{2,30}?)\s+(?:r\$\s*)?(\d{1,6})\s*(?:conto[s]?|pila[s]?|reais)?\s*$/iu',

        // "40 uber" / "32.50 ifood" / "50 conto de luz" / "200 reais no mercado" (valor + descrição)
        '/^(?:r\$\s*)?(\d+[\.,]?\d*)\s*(?:conto[s]?|pila[s]?|reais)?\s+(.{2,})\s*$/iu',

        // "salário 5000" / "freela R$ 1.500" / "mesada 500"
        '/^(sal[áa]rio|freela|freelance|aluguel|mesada|renda)\s+(?:r\$\s*)?(\d+[\.,]?\d*)\s*$/iu',

        // "fatura de 800 do nubank" / "boleto de 230 da internet"
        '/(?:fatura|boleto|parcela|prestação|prestacao)\s+(?:de\s+)?(?:r\$\s*)?(\d+[\.,]?\d*)\s+(?:do|da|de|no|na)?\s*(.+)/iu',

        // "uns 30 de uber" / "uns 50 no ifood"
        '/(?:uns?\s+)(?:r\$\s*)?(\d+[\.,]?\d*)\s+(?:de|do|da|no|na|em)?\s*(.+)/iu',
    ];

    /**
     * Keywords que indicam receita.
     */
    private const INCOME_KEYWORDS = [
        'recebi',
        'ganhei',
        'entrou',
        'salário',
        'salario',
        'freelance',
        'freela',
        'rendimento',
        'dividendo',
        'reembolso',
        'devolução',
        'devolvido',
        'mesada',
        'aluguel recebido',
        'renda',
    ];

    /**
     * Keywords para detecção de forma de pagamento.
     */
    private const PAYMENT_METHOD_PATTERNS = [
        'cartao_credito' => '/\b(?:cart[ãa]o|cr[ée]dito|no\s+cart[ãa]o|no\s+cr[ée]dito|parcelei|parcelo|em\s+\d{1,2}\s*x|\d{1,2}\s*x\s+de)\b/iu',
        'cartao_debito'  => '/\b(?:d[ée]bito|no\s+d[ée]bito|cart[ãa]o\s+(?:de\s+)?d[ée]bito)\b/iu',
        'pix'            => '/\b(?:pix|mandei\s+pix|fiz\s+pix|via\s+pix)\b/iu',
        'boleto'         => '/\b(?:boleto|guia|GRU|DARF)\b/iu',
        'dinheiro'       => '/\b(?:dinheiro|cash|esp[ée]cie|em\s+m[ãa]os)\b/iu',
    ];

    /**
     * Padrões de parcelamento.
     * Captura grupo 1 = número de parcelas.
     */
    private const INSTALLMENT_PATTERNS = [
        '/(?:em\s+)?(\d{1,2})\s*x\b/iu',
        '/(\d{1,2})\s*(?:vezes|parcelas?)\b/iu',
        '/parcel(?:ei|ado|ar)\s+(?:em\s+)?(\d{1,2})/iu',
    ];

    /**
     * Padrões para detectar nome de cartão.
     * Ex: "no nubank", "no inter", "no itaú"
     */
    private const CARD_NAME_PATTERN =
    '/(?:no|na|do|da|pelo|pela)\s+(nubank|inter|ita[úu]|itau|bradesco|santander|bb|banco\s*do\s*brasil|sicredi|sicoob|c6|c6\s*bank|original|bmg|pan|neon|next|will|digio|picpay|pagbank|mercado\s*pago|ame|stone|safra|caixa|banrisul|btg)\b/iu';

    /**
     * Verifica se a mensagem contém um valor numérico que parece transação.
     * Helper rápido para o TransactionIntentRule.
     */
    public static function detectsValue(string $message): bool
    {
        return (bool) preg_match('/\b\d{1,6}(?:[.,]\d{1,2})?\b/', $message);
    }

    /**
     * Extrai dados de transação de uma mensagem.
     *
     * @return array{descricao: string, valor: float, tipo: string, data: string, forma_pagamento?: string, eh_parcelado?: bool, total_parcelas?: int, nome_cartao?: string}|null
     */
    public static function extract(string $message): ?array
    {
        $normalized = mb_strtolower(trim($message));

        if ($normalized === '' || mb_strlen($normalized) < 3) {
            return null;
        }

        // Pré-processar valores coloquiais
        $processedMessage = self::normalizeColloquialValues($message);

        foreach (self::EXTRACTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $processedMessage, $matches)) {
                $parsed = self::parseMatches($matches, $pattern, mb_strtolower(trim($processedMessage)));
                if ($parsed !== null) {
                    // Enriquecer com forma de pagamento, parcelamento e nome do cartão
                    $parsed = self::enrichWithPaymentInfo($parsed, $normalized);
                    return $parsed;
                }
            }
        }

        return null;
    }

    /**
     * Normaliza valores coloquiais no texto antes da extração.
     * Delega para NumberNormalizer que corrige "2 mil" → "2000", "duzentos" → "200", etc.
     */
    private static function normalizeColloquialValues(string $message): string
    {
        return NumberNormalizer::normalize($message);
    }

    /**
     * Enriquece resultado com informações de pagamento (cartão, parcelamento).
     */
    private static function enrichWithPaymentInfo(array $data, string $normalized): array
    {
        // Detectar forma de pagamento
        $data['forma_pagamento'] = self::detectPaymentMethod($normalized);

        // Detectar parcelamento
        $installments = self::detectInstallments($normalized);
        if ($installments !== null) {
            $data['eh_parcelado'] = true;
            $data['total_parcelas'] = $installments;
            // Se tem parcelamento, forma de pagamento é cartão de crédito
            if ($data['forma_pagamento'] === null) {
                $data['forma_pagamento'] = 'cartao_credito';
            }
        } else {
            $data['eh_parcelado'] = false;
            $data['total_parcelas'] = null;
        }

        // Detectar nome do cartão
        $cardName = self::detectCardName($normalized);
        if ($cardName !== null) {
            $data['nome_cartao'] = $cardName;
            // Se mencionou nome de banco/cartão, provavelmente é cartão de crédito
            if ($data['forma_pagamento'] === null) {
                $data['forma_pagamento'] = 'cartao_credito';
            }
        }

        return $data;
    }

    /**
     * Detecta a forma de pagamento na mensagem.
     */
    private static function detectPaymentMethod(string $message): ?string
    {
        foreach (self::PAYMENT_METHOD_PATTERNS as $method => $pattern) {
            if (preg_match($pattern, $message)) {
                return $method;
            }
        }
        return null;
    }

    /**
     * Detecta parcelamento e retorna número de parcelas.
     */
    private static function detectInstallments(string $message): ?int
    {
        foreach (self::INSTALLMENT_PATTERNS as $pattern) {
            if (preg_match($pattern, $message, $m)) {
                $parcelas = (int) $m[1];
                if ($parcelas >= 2 && $parcelas <= 48) {
                    return $parcelas;
                }
            }
        }
        return null;
    }

    /**
     * Detecta nome de banco/cartão na mensagem.
     */
    private static function detectCardName(string $message): ?string
    {
        if (preg_match(self::CARD_NAME_PATTERN, $message, $m)) {
            return mb_convert_case(trim($m[1]), MB_CASE_TITLE);
        }
        return null;
    }

    /**
     * Parseia os matches do regex em dados estruturados.
     */
    private static function parseMatches(array $matches, string $pattern, string $normalized): ?array
    {
        // Padrões de parcelamento têm 3 grupos: (valor)(parcelas)(desc) ou (parcelas)(valor)(desc)
        if (str_contains($pattern, 'parcelei|parcelo') || str_contains($pattern, '^(\d{1,2})\s*x')) {
            return self::parseInstallmentMatch($matches, $pattern, $normalized);
        }

        // Padrão de compra no cartão: (desc)(valor)
        if (str_contains($pattern, 'comprei|gastei') && str_contains($pattern, 'cart[ãa]o|cr[ée]dito')) {
            return self::parseCardPurchaseMatch($matches, $normalized);
        }

        // Padrão de fatura/boleto: (valor)(desc)
        if (str_contains($pattern, 'fatura|boleto')) {
            return self::parseFaturaMatch($matches, $normalized);
        }

        // Padrão de pix: (valor)(desc)
        if (str_contains($pattern, 'mandei\s+pix|fiz\s+pix')) {
            return self::parsePixMatch($matches, $normalized);
        }

        // Determinar qual grupo é valor e qual é descrição
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
        $descricao = preg_replace('/^(No|Na|De|Do|Da|Em|Com|Pro|Pra|Para|Uns?)\s+/u', '', $descricao);
        // Remover sufixos de valor coloquial que sobraram
        $descricao = preg_replace('/\s*(?:Conto[s]?|Pila[s]?|Reais)\s*$/iu', '', $descricao);
        $descricao = trim($descricao);

        if ($descricao === '') {
            return null;
        }

        return [
            'descricao' => $descricao,
            'valor'     => $valor,
            'tipo'      => $tipo,
            'data'      => self::detectDate($normalized),
        ];
    }

    /**
     * Parseia match de parcelamento.
     * "parcelei 3000 em 12x no inter" → valor=3000, parcelas=12, desc="Inter"
     * "12x de 99 geladeira" → valor_unitario=99, parcelas=12, desc="Geladeira"
     */
    private static function parseInstallmentMatch(array $matches, string $pattern, string $normalized): ?array
    {
        if (str_contains($pattern, '^(\d{1,2})\s*x')) {
            // "12x de 99 geladeira": (parcelas)(valor_unitario)(desc)
            $parcelas = (int) ($matches[1] ?? 0);
            $valorUnit = self::parseValue($matches[2] ?? '0');
            $descricao = trim($matches[3] ?? '');
            $valor = $valorUnit * $parcelas; // Valor total
        } else {
            // "parcelei 3000 em 12x geladeira": (valor)(parcelas)(desc)
            $valor = self::parseValue($matches[1] ?? '0');
            $parcelas = (int) ($matches[2] ?? 0);
            $descricao = trim($matches[3] ?? '');
        }

        if ($valor <= 0 || $descricao === '' || $parcelas < 2) {
            return null;
        }

        $descricao = mb_convert_case($descricao, MB_CASE_TITLE);
        $descricao = preg_replace('/^(No|Na|De|Do|Da|Em|Com|Pro|Pra|Para)\s+/u', '', $descricao);
        $descricao = trim($descricao);

        return [
            'descricao'      => $descricao,
            'valor'          => $valor,
            'tipo'           => 'despesa',
            'data'           => self::detectDate($normalized),
            'eh_parcelado'   => true,
            'total_parcelas' => $parcelas,
        ];
    }

    /**
     * Parseia match de compra no cartão.
     * "comprei geladeira no cartão por 1500" → desc="Geladeira", valor=1500
     */
    private static function parseCardPurchaseMatch(array $matches, string $normalized): ?array
    {
        $descricao = trim($matches[1] ?? '');
        $valor = self::parseValue($matches[2] ?? '0');

        if ($valor <= 0 || $descricao === '') {
            return null;
        }

        $descricao = mb_convert_case($descricao, MB_CASE_TITLE);
        $descricao = preg_replace('/\s+(No|Na|Do|Da|Pelo|Pela)\s*$/u', '', $descricao);
        $descricao = trim($descricao);

        return [
            'descricao'        => $descricao,
            'valor'            => $valor,
            'tipo'             => 'despesa',
            'data'             => self::detectDate($normalized),
            'forma_pagamento'  => 'cartao_credito',
        ];
    }

    /**
     * Parseia match de fatura/boleto.
     * "fatura de 800 do nubank" → valor=800, desc="Nubank"
     */
    private static function parseFaturaMatch(array $matches, string $normalized): ?array
    {
        $valor = self::parseValue($matches[1] ?? '0');
        $descricao = trim($matches[2] ?? '');

        if ($valor <= 0 || $descricao === '') {
            return null;
        }

        $descricao = mb_convert_case($descricao, MB_CASE_TITLE);
        $descricao = preg_replace('/^(Do|Da|De|No|Na)\s+/u', '', $descricao);
        $descricao = trim($descricao);

        return [
            'descricao' => $descricao,
            'valor'     => $valor,
            'tipo'      => 'despesa',
            'data'      => self::detectDate($normalized),
        ];
    }

    /**
     * Parseia match de pix.
     * "mandei pix 200 pro joão" → valor=200, desc="João"
     */
    private static function parsePixMatch(array $matches, string $normalized): ?array
    {
        $valor = self::parseValue($matches[1] ?? '0');
        $descricao = trim($matches[2] ?? '');

        if ($valor <= 0 || $descricao === '') {
            return null;
        }

        $descricao = mb_convert_case($descricao, MB_CASE_TITLE);
        $descricao = preg_replace('/^(Pro|Pra|Para|De|Do|Da)\s+/u', '', $descricao);
        $descricao = trim($descricao);

        return [
            'descricao'       => $descricao,
            'valor'           => $valor,
            'tipo'            => 'despesa',
            'data'            => self::detectDate($normalized),
            'forma_pagamento' => 'pix',
        ];
    }

    /**
     * Parseia string de valor para float.
     * Delega para NumberNormalizer::parseValue() para consistência.
     */
    private static function parseValue(string $raw): float
    {
        return NumberNormalizer::parseValue($raw);
    }

    /**
     * Detecta se é receita ou despesa.
     */
    private static function detectType(string $message): string
    {
        // Negation overrides income keywords: "nao recebi" = NOT income
        if (
            preg_match('/\bn[ãa]o\s+(?:recebi|ganhei|entrou|depositaram)/iu', $message)
            || preg_match('/ainda\s+n[ãa]o\s+(?:recebi|ganhei)/iu', $message)
            || preg_match('/n[ãa]o\s+(?:veio|caiu|entrou)/iu', $message)
        ) {
            return 'despesa';
        }

        foreach (self::INCOME_KEYWORDS as $keyword) {
            if (str_contains($message, $keyword)) {
                return 'receita';
            }
        }

        return 'despesa';
    }

    /**
     * Detecta data relativa na mensagem.
     */
    private static function detectDate(string $message): string
    {
        if (preg_match('/\bontem\b/iu', $message)) {
            return date('Y-m-d', strtotime('-1 day'));
        }
        if (preg_match('/\banteontem\b/iu', $message)) {
            return date('Y-m-d', strtotime('-2 days'));
        }
        return date('Y-m-d');
    }
}
