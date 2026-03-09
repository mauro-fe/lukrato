<?php

declare(strict_types=1);

namespace Application\Services\AI;

/**
 * Centraliza a construção de system prompts usados por TODOS os providers.
 * Trocar de provider (OpenAI, Ollama, etc.) não requer mexer nos prompts.
 */
class PromptBuilder
{
    public static function chatSystem(array $context = []): string
    {
        // Redirecionar para prompt de usuário quando for chat de usuário
        if (!empty($context['_user_mode'])) {
            return self::userChatSystem($context);
        }

        $base = <<<'PROMPT'
Você é o assistente de IA do Lukrato, com acesso completo a dados e métricas do sistema. Atua como co-administrador, ajudando a monitorar, analisar e tomar decisões.

ÁREAS DE ACESSO:
- Financeiro: receitas, despesas, saldos, transferências, lançamentos por categoria/subcategoria, status de pagamentos, ticket médio, taxa de economia, recorrências
- Cartões e Faturas: limites (total/disponível/utilizado), faturas do mês, parcelamentos, ranking por cartão
- Contas Bancárias: total, ativas/inativas, por tipo, instituições vinculadas
- Categorias: padrão vs personalizadas, subcategorias, top gastos
- Metas e Orçamentos: metas financeiras (ativas/concluídas/pausadas), orçamentos mensais, estouros
- Usuários: total, admins, novos, crescimento, verificação, onboarding, login Google
- Assinaturas: planos ativos, MRR, cupons
- Gamificação: níveis, pontos, streaks, conquistas
- Marketing: indicações, notificações, campanhas, blog
- Segurança: resets de senha, IPs, contas deletadas
- Logs: erros por nível/categoria, últimos erros
- Webhooks: por provedor e tipo de evento

REGRAS:
1. Sempre português brasileiro, claro e prático.
2. Use SOMENTE números do contexto. NUNCA invente dados.
3. Se um dado não está no contexto, diga explicitamente.
4. Ao comparar períodos, calcule variações percentuais.
5. Alertas proativos: orçamentos estourados, cartões >70%, lançamentos vencidos, erros críticos, MRR em declínio.
6. Sugira ações concretas baseadas nos dados.
7. Use negrito, bullet points e emojis para respostas longas.
8. Quando perguntado "como está o sistema", forneça resumo executivo: saúde financeira, crescimento, engajamento, erros e receita.
PROMPT;

        if (!empty($context)) {
            $base .= "\n\n═══ DADOS REAIS DO SISTEMA LUKRATO ═══\n";
            $base .= self::formatContext($context);
            $base .= "\n═══ FIM DOS DADOS ═══";
        }

        return $base;
    }

    public static function userChatSystem(array $context = []): string
    {
        $base = <<<'PROMPT'
Você é o assistente financeiro pessoal do Lukrato. Ajuda o usuário a entender suas finanças, tirar dúvidas e receber insights sobre seus gastos, receitas, metas e orçamentos.

REGRAS:
1. Sempre português brasileiro, tom amigável e prático.
2. Use SOMENTE dados do contexto. NUNCA invente valores.
3. Se um dado não está no contexto, diga que não tem acesso a essa informação no momento.
4. Dê dicas práticas e acionáveis de finanças pessoais.
5. Respostas curtas e diretas, a menos que o usuário peça detalhes.
6. Use negrito e bullet points para respostas longas.
7. Nunca revele dados técnicos internos do sistema.
8. Para assuntos fora de finanças pessoais, redirecione educadamente.
PROMPT;

        // Histórico de conversa
        $history = $context['conversation_history'] ?? [];
        unset($context['_user_mode'], $context['conversation_history']);

        if (!empty($context)) {
            $base .= "\n\n═══ DADOS FINANCEIROS DO USUÁRIO ═══\n";
            $base .= self::formatContext($context);
            $base .= "\n═══ FIM DOS DADOS ═══";
        }

        if (!empty($history)) {
            $base .= "\n\n═══ HISTÓRICO DA CONVERSA ═══\n";
            foreach ($history as $msg) {
                $role = ($msg['role'] ?? 'user') === 'assistant' ? 'Assistente' : 'Usuário';
                $content = $msg['content'] ?? '';
                $base .= "{$role}: {$content}\n";
            }
            $base .= "═══ FIM DO HISTÓRICO ═══";
        }

        return $base;
    }

    public static function categorySystem(): string
    {
        return 'Você classifica lançamentos financeiros em categorias. Responda apenas com o nome da categoria.';
    }

    public static function categoryUser(string $description, array $categories): string
    {
        $list = implode(', ', $categories);

        return <<<PROMPT
Classifique o lançamento financeiro abaixo em UMA das categorias da lista.

Descrição: "{$description}"

Categorias: {$list}

Responda SOMENTE com o nome exato de uma categoria. Sem ponto final, sem explicação.
PROMPT;
    }

    public static function analysisSystem(): string
    {
        return 'Você é um analista financeiro especializado. Sempre retorne JSON válido no formato solicitado.';
    }

    public static function analysisUser(array $data, string $period): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Analise os seguintes dados financeiros do período: {$period}

{$json}

Forneça uma análise financeira útil com:
1. De 3 a 5 insights práticos e acionáveis sobre os padrões de gastos
2. Um resumo executivo em 2 frases

Responda APENAS em JSON com o formato exato:
{"insights": ["insight 1", "insight 2", "insight 3"], "resumo": "resumo em 2 frases aqui"}
PROMPT;
    }

    /**
     * System prompt para extração de transação a partir de linguagem natural.
     */
    public static function transactionExtractionSystem(): string
    {
        return <<<'PROMPT'
Você extrai dados de transações financeiras a partir de mensagens em linguagem natural.
Retorne APENAS um JSON válido no formato: {"descricao": "string", "valor": number, "tipo": "receita|despesa", "categoria_sugerida": "string|null"}
Se a mensagem indicar ganho/recebimento, tipo = "receita". Caso contrário, tipo = "despesa".
Não inclua texto adicional. Apenas o JSON.
PROMPT;
    }

    /**
     * User prompt para extração de transação.
     */
    public static function transactionExtractionUser(string $message): string
    {
        return "Extraia a transação financeira desta mensagem:\n\"{$message}\"";
    }

    /**
     * System prompt para consultas rápidas via LLM (fallback do QuickQueryHandler).
     */
    public static function quickQuerySystem(): string
    {
        return 'Responda a pergunta financeira de forma direta e concisa, em no máximo 2 frases. Use os dados fornecidos no contexto.';
    }

    public static function defaultCategories(): array
    {
        return [
            'Alimentação',
            'Transporte',
            'Moradia',
            'Saúde',
            'Educação',
            'Lazer',
            'Vestuário',
            'Investimentos',
            'Salário',
            'Freelance',
            'Assinaturas',
            'Serviços Públicos',
            'Outros',
        ];
    }

    private static function formatContext(array $ctx, int $indent = 0): string
    {
        $lines  = [];
        $prefix = str_repeat('  ', $indent);

        foreach ($ctx as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));

            if (is_array($value) && !array_is_list($value)) {
                $lines[] = "{$prefix}{$label}:";
                $lines[] = self::formatContext($value, $indent + 1);
            } elseif (is_array($value)) {
                $lines[] = "{$prefix}{$label}:";
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $parts   = array_map(fn($k, $v) => "{$k}: {$v}", array_keys($item), array_values($item));
                        $lines[] = "{$prefix}  - " . implode(', ', $parts);
                    } else {
                        $lines[] = "{$prefix}  - {$item}";
                    }
                }
            } else {
                $lines[] = "{$prefix}{$label}: {$value}";
            }
        }

        return implode("\n", $lines);
    }
}
