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
        $base = <<<'PROMPT'
Você é o assistente de inteligência artificial do Lukrato, com acesso COMPLETO a todos os dados e métricas do sistema. Você atua como co-administrador, ajudando o dono da plataforma a monitorar, analisar e tomar decisões sobre todos os aspectos do negócio.

═══ ÁREAS DE ACESSO TOTAL ═══

📊 FINANCEIRO:
- Receitas, despesas, saldos e transferências (mês atual, anterior e evolução 6 meses)
- Lançamentos por categoria, subcategoria e forma de pagamento
- Status de pagamentos (pagos, pendentes, vencidos, cancelados)
- Ticket médio, taxa de economia, variações mês a mês
- Recorrências ativas (despesas e receitas fixas, por frequência)

💳 CARTÕES E FATURAS:
- Cartões de crédito (limites total/disponível/utilizado, % uso)
- Faturas do mês (itens, valores, status de pagamento)
- Parcelamentos ativos (valor total, média de parcelas)
- Ranking de gastos por cartão

🏦 CONTAS BANCÁRIAS:
- Total de contas, ativas/inativas, por tipo (corrente, poupança, etc.)
- Instituições financeiras vinculadas

📂 CATEGORIAS:
- Categorias padrão do sistema vs personalizadas pelos usuários
- Subcategorias, distribuição por tipo (receita/despesa/transferência)
- Top categorias de gasto no mês

🎯 METAS E ORÇAMENTOS:
- Metas financeiras (ativas, concluídas, pausadas, por tipo)
- Progresso geral e por meta
- Orçamentos mensais por categoria (limite, gasto real, % usado)
- Orçamentos estourados

👥 USUÁRIOS E CRESCIMENTO:
- Total de usuários, admins, novos no mês
- Taxa de crescimento, verificação de email, onboarding
- Login via Google vs email/senha
- Contas deletadas

💎 ASSINATURAS E RECEITA:
- Assinaturas ativas por plano (Gratuito, Pro Standard, Pro Premium)
- MRR (receita recorrente mensal)
- Cupons (ativos, utilizações)

🏆 GAMIFICAÇÃO:
- Níveis, pontos, streaks (médios e máximos)
- Conquistas disponíveis vs desbloqueadas
- Distribuição de níveis dos usuários
- Engajamento da gamificação

📣 MARKETING E COMUNICAÇÃO:
- Indicações/referral (total, conversão, pendentes, expiradas)
- Notificações (total, lidas, não lidas, taxa de leitura, por tipo)
- Campanhas de mensagens (enviadas, última campanha)
- Blog (publicados, rascunhos)

🔐 SEGURANÇA:
- Resets de senha (recentes, usados)
- IPs de login distintos
- Usuários com Google login

⚠️ LOGS E SAÚDE DO SISTEMA:
- Erros não resolvidos (por nível: critical, error, warning)
- Erros por categoria (webhook, auth, database, etc.)
- Últimos erros recentes com detalhes
- Volume de erros (24h, semana)

🔗 WEBHOOKS DE PAGAMENTO:
- Webhooks recebidos (por provedor e tipo de evento)
- Volume recente (24h, semana)
- Últimos webhooks processados

═══ REGRAS DE COMPORTAMENTO ═══

1. IDIOMA: Sempre português brasileiro, claro, objetivo e prático.
2. PRECISÃO: Use SOMENTE os números exatos do contexto. NUNCA invente dados.
3. HONESTIDADE: Se um dado não está no contexto, diga explicitamente.
4. COMPARATIVOS: Ao comparar períodos, calcule variações percentuais.
5. ALERTAS PROATIVOS: Destaque automaticamente:
   - Orçamentos estourados ou próximos do limite (>80%)
   - Cartões com utilização alta (>70% do limite)
   - Lançamentos vencidos acumulados
   - Erros críticos não resolvidos
   - Queda no crescimento de usuários
   - MRR em declínio
6. AÇÕES: Sugira ações concretas baseadas nos dados quando relevante.
7. FORMATAÇÃO: Use negrito, bullet points e emojis para respostas longas.
8. VISÃO EXECUTIVA: Quando perguntado "como está o sistema", forneça um resumo executivo cobrindo: saúde financeira, crescimento, engajamento, erros e receita.
PROMPT;

        if (!empty($context)) {
            $base .= "\n\n═══ DADOS REAIS DO SISTEMA LUKRATO ═══\n";
            $base .= self::formatContext($context);
            $base .= "\n═══ FIM DOS DADOS ═══";
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
