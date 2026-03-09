<?php

declare(strict_types=1);

namespace Application\Services\AI;

/**
 * Seleciona apenas as seções de contexto relevantes para a pergunta do usuário.
 * Reduz ~40-60% dos tokens de input em chamadas ao LLM.
 *
 * Se a pergunta for genérica (ex: "como está o sistema"), retorna tudo.
 */
class ContextCompressor
{
    /**
     * Mapeamento de keywords para as chaves de contexto que devem ser incluídas.
     * Chaves sempre incluídas: data_atual, mes_atual, dia_da_semana.
     */
    private const KEYWORD_MAP = [
        // Financeiro
        'receita|despesa|saldo|gasto|financeiro|dinheiro|economi|lucro|prejuízo|variação' => ['financeiro', 'lancamentos_status', 'evolucao_6_meses'],
        'lançamento|lancamento|transação|transacao|pagamento|pendente|vencido' => ['financeiro', 'lancamentos', 'lancamentos_status'],
        'categoria|subcategoria|classificar|classificação' => ['categorias', 'financeiro'],
        'cartão|cartao|crédito|credito|fatura|limite|parcela' => ['cartoes_credito', 'faturas', 'parcelas'],
        'conta|banco|bancária|bancaria|instituição|poupança|corrente' => ['contas'],
        'meta|objetivo|orçamento|orcamento|budget|estourado' => ['metas', 'orcamentos'],
        'recorrência|recorrencia|fixa|fixo|mensal|semanal' => ['recorrencias'],
        // Usuários
        'usuário|usuario|user|crescimento|cadastro|onboarding|verificação' => ['usuarios'],
        // Assinaturas
        'assinatura|plano|premium|pro|mrr|receita recorrente|cupom|cupons' => ['assinaturas'],
        // Gamificação
        'gamificação|gamificacao|nível|nivel|ponto|streak|conquista|achievement' => ['gamificacao'],
        // Marketing
        'marketing|indicação|indicacao|referral|notificação|notificacao|campanha|blog' => ['marketing'],
        // Segurança
        'segurança|seguranca|senha|reset|ip|login|google' => ['seguranca'],
        // Logs / Sistema
        'erro|log|saúde|saude|sistema|health|critical|warning' => ['logs_sistema'],
        // Webhooks
        'webhook|pagamento online|stripe|provedor' => ['webhooks'],
    ];

    /** Chaves que sempre são incluídas (metadados leves). */
    private const ALWAYS_INCLUDE = ['data_atual', 'mes_atual', 'dia_da_semana'];

    /** Expressões que indicam que o usuário quer uma visão geral. */
    private const GENERIC_PATTERNS = [
        'como está|como esta|visão geral|resumo|executivo|overview|tudo|geral|dashboard|painel|status do sistema',
    ];

    /**
     * Filtra o contexto completo, retornando apenas seções relevantes.
     *
     * @param array  $fullContext Contexto completo do SystemContextService::gather()
     * @param string $userMessage Mensagem do usuário
     * @return array Contexto filtrado
     */
    public static function compress(array $fullContext, string $userMessage): array
    {
        $message = mb_strtolower($userMessage);

        // Se a pergunta for genérica, retorna tudo
        foreach (self::GENERIC_PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/u', $message)) {
                return $fullContext;
            }
        }

        // Detectar quais chaves de contexto são relevantes
        $relevantKeys = [];
        foreach (self::KEYWORD_MAP as $pattern => $contextKeys) {
            if (preg_match('/' . $pattern . '/u', $message)) {
                $relevantKeys = array_merge($relevantKeys, $contextKeys);
            }
        }

        // Se nenhuma keyword matchou, retorna tudo (safe fallback)
        if (empty($relevantKeys)) {
            return $fullContext;
        }

        $relevantKeys = array_unique(array_merge(self::ALWAYS_INCLUDE, $relevantKeys));

        // Filtrar contexto
        $compressed = [];
        foreach ($fullContext as $key => $value) {
            if (in_array($key, $relevantKeys, true)) {
                $compressed[$key] = $value;
            }
        }

        return $compressed;
    }
}
