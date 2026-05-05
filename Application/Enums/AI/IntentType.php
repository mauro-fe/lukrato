<?php

declare(strict_types=1);

namespace Application\Enums\AI;

/**
 * Tipos de intenção detectados pelo IntentRouter.
 * Cada intent é roteado para um Handler especializado.
 */
enum IntentType: string
{
    /** Conversa geral com o assistente */
    case CHAT = 'chat';

    /** Sugestão de categoria/subcategoria para lançamento */
    case CATEGORIZE = 'categorize';

    /** Análise financeira com insights e resumo */
    case ANALYZE = 'analyze';

    /** Extração de transação a partir de linguagem natural */
    case EXTRACT_TRANSACTION = 'extract_transaction';

    /** Consulta rápida respondível com dados pré-computados */
    case QUICK_QUERY = 'quick_query';

    /** Criação de entidade (lançamento, meta, orçamento, categoria) */
    case CREATE_ENTITY = 'create_entity';

    /** Confirmação ou rejeição de ação pendente */
    case CONFIRM_ACTION = 'confirm_action';

    /** Pagamento de fatura de cartão */
    case PAY_FATURA = 'pay_fatura';

    /**
     * Retorna todos os valores possíveis do enum.
     *
     * @return list<string>
     */
    public static function listValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Verifica se um valor é válido.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::listValues(), true);
    }

    /**
     * Tenta criar a partir de string, retorna null se inválido.
     */
    public static function tryFromString(string $value): ?self
    {
        return self::tryFrom(strtolower(trim($value)));
    }
}
