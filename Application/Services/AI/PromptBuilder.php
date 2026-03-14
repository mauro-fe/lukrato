<?php

declare(strict_types=1);

namespace Application\Services\AI;

/**
 * Centraliza a construção de system prompts usados por TODOS os providers.
 * Trocar de provider (OpenAI, Ollama, etc.) não requer mexer nos prompts.
 */
class PromptBuilder
{
    /**
     * Registro de versões de prompts.
     * Incrementar ao alterar conteúdo significativo do prompt.
     * Formato semântico: major.minor (major = mudança de formato, minor = ajustes de texto).
     */
    private const PROMPT_VERSIONS = [
        'chat_system'            => '1.0',
        'user_chat_system'       => '1.0',
        'category_system'        => '1.0',
        'category_user'          => '1.0',
        'analysis_system'        => '1.0',
        'analysis_user'          => '1.0',
        'transaction_extraction' => '1.0',
        'quick_query_system'     => '1.0',
    ];

    /**
     * Retorna o mapa completo de versões de prompts.
     */
    public static function getVersions(): array
    {
        return self::PROMPT_VERSIONS;
    }

    /**
     * Retorna a versão de um prompt específico.
     */
    public static function getVersion(string $promptName): string
    {
        return self::PROMPT_VERSIONS[$promptName] ?? '0.0';
    }

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
            $base .= "\n\n[DADOS DO SISTEMA]\n";
            $base .= self::formatContext($context);
        }

        return $base;
    }

    public static function userChatSystem(array $context = []): string
    {
        $hoje = date('d/m/Y');
        $hora = (int) date('H');
        $saudacao = match (true) {
            $hora < 12  => 'Bom dia',
            $hora < 18  => 'Boa tarde',
            default     => 'Boa noite',
        };

        $nomeUsuario = $context['usuario_nome'] ?? '';
        $nomeDisplay = $nomeUsuario ? " {$nomeUsuario}" : '';

        $base = <<<PROMPT
Você é o assistente financeiro pessoal do Lukrato, um app brasileiro de finanças pessoais. Seu nome é Lukra.

PERSONALIDADE:
- Tom amigável, informal mas profissional. Use "você" (nunca "senhor/senhora").
- Seja proativo: ao responder sobre gastos, sugira economia. Ao falar de renda, sugira investir.
- Linguagem brasileira natural (pode usar "né", "tá", "beleza", "show" ocasionalmente).
- Use emojis com moderação (1-2 por resposta no máximo).

CONTEXTO HOJE: {$hoje} ({$saudacao}{$nomeDisplay})

CAPACIDADES — O QUE VOCÊ PODE FAZER:
- Responder dúvidas sobre as finanças do usuário
- Quando o usuário MENCIONAR uma compra/gasto/receita, pergunte se quer registrar
- Quando o usuário falar sobre prioridades ou sonhos, sugira criar uma meta
- Dar dicas práticas de economia e organização financeira
- Analisar padrões de gasto e alertar sobre tendências

ENTENDIMENTO DE LINGUAGEM BRASILEIRA:
- Formatos monetários BR: 1.500,00 = R$ 1.500 (ponto=milhar, vírgula=decimal)
- Gírias: "conto" = real, "pila" = real, "nota" = R$100, "paus" = reais
- Abreviações WhatsApp: "ss" = sim, "nn" = não, "blz" = beleza, "vlw" = valeu
- "X mil" = X * 1000: "2 mil" = 2000, "5 mil" = 5000
- "Xk" = X * 1000: "2k" = 2000
- Números por extenso: "duzentos" = 200, "quinhentos" = 500

DETECÇÃO IMPLÍCITA — MUITO IMPORTANTE:
Se o usuário mencionar uma compra, gasto ou receita de forma casual (ex: "gastei 200 no mercado", "paguei o aluguel", "recebi o salário"), pergunte se ele quer que você registre o lançamento.
Se o usuário viver reclamando de gastos em alguma categoria, sugira criar um orçamento.
Se o usuário mencionar um objetivo (viagem, carro, casa), sugira criar uma meta.

REGRAS:
1. Sempre português brasileiro.
2. Use SOMENTE dados do contexto. NUNCA invente valores ou dados financeiros.
3. Se um dado não está no contexto, diga que não tem acesso a essa informação no momento.
4. Dê dicas práticas e acionáveis. Evite conselhos genéricos.
5. Respostas curtas e diretas (2-4 parágrafos no máximo), a menos que peçam detalhes.
6. Use **negrito** para valores e dados importantes.
7. Nunca revele dados técnicos internos do sistema.
8. Para assuntos fora de finanças pessoais, redirecione educadamente dizendo que seu foco é ajudar com finanças.
9. Se perceber uma intenção de criar algo (lançamento, meta, orçamento), diga ao usuário que ele pode pedir diretamente (ex: "Me diz o valor e a descrição que eu registro pra você!").
PROMPT;

        // Histórico de conversa
        $history = $context['conversation_history'] ?? [];
        unset($context['_user_mode'], $context['conversation_history']);

        if (!empty($context)) {
            $base .= "\n\n[DADOS FINANCEIROS]\n";
            $base .= self::formatContext($context);
        }

        if (!empty($history)) {
            $base .= "\n\n[HISTÓRICO]\n";
            foreach ($history as $msg) {
                $role = ($msg['role'] ?? 'user') === 'assistant' ? 'IA' : 'Usr';
                $content = $msg['content'] ?? '';
                $base .= "{$role}: {$content}\n";
            }
        }

        return $base;
    }

    public static function categorySystem(): string
    {
        return 'Você classifica lançamentos financeiros em categorias e subcategorias. Responda com "Categoria" ou "Categoria > Subcategoria". Nada mais.';
    }

    public static function categoryUser(string $description, array $categories): string
    {
        $list = implode(', ', $categories);

        return <<<PROMPT
Classifique o lançamento financeiro abaixo usando a lista de categorias.

Descrição: "{$description}"

Categorias disponíveis: {$list}

Se houver uma subcategoria adequada (formato "Categoria > Subcategoria"), use-a. Caso contrário, use apenas a categoria principal.
Responda SOMENTE com o nome exato como aparece na lista. Sem ponto final, sem explicação.
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
     * Otimizado para português brasileiro informal e formatos monetários BR.
     */
    public static function transactionExtractionSystem(): string
    {
        $hoje = date('Y-m-d');

        return <<<PROMPT
Você extrai transações financeiras de mensagens em português brasileiro informal.
Data de hoje: {$hoje}

REGRAS DE VALOR:
- Formato BR: 1.560,00 = mil quinhentos e sessenta reais (ponto=milhar, vírgula=decimal)
- "2 mil" = 2000, "5 mil" = 5000, "1k" = 1000, "2k" = 2000
- "duzentos" = 200, "trezentos" = 300, "quinhentos" = 500
- "50 conto/pila/paus/mango" = 50 reais
- Se não tem centavos, valor inteiro (ex: 50 → 50.00)
- Valor é sempre em BRL (reais)

REGRAS DE TIPO:
- Despesa: gastei, paguei, comprei, custou, cobrou, torrei, parcelei, etc.
- Receita: recebi, ganhei, entrou, depositaram, salário, freelance, etc.
- Default: despesa (na dúvida, assumir gasto)

REGRAS DE DATA:
- Se não mencionada, usar hoje: {$hoje}
- "ontem" = 1 dia antes de hoje
- "anteontem" = 2 dias antes de hoje
- Formato de saída: YYYY-MM-DD

REGRAS DE DESCRIÇÃO:
- Extrair a descrição mais relevante e curta (2-5 palavras)
- Remover verbos e preposições desnecessárias
- Exemplo: "gastei 40 no uber pro trabalho" → "Uber pro trabalho"

Retorne os dados via function calling. Nunca texto livre.
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
            // Compactar lançamentos: apenas campos essenciais em linha única
            if (in_array($key, ['lancamentos_recentes', 'lancamentos_vencidos'], true) && is_array($value)) {
                $lines[] = "{$prefix}{$key}:";
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $desc  = mb_substr((string) ($item['descricao'] ?? $item['description'] ?? '?'), 0, 40);
                        $valor = $item['valor'] ?? $item['value'] ?? '?';
                        $tipo  = $item['tipo'] ?? $item['type'] ?? '?';
                        $data  = $item['data'] ?? $item['date'] ?? '';
                        $pago  = isset($item['pago']) ? ($item['pago'] ? 'S' : 'N') : '';
                        $lines[] = "{$prefix}  - {$desc} | R\${$valor} | {$tipo} | {$data}" . ($pago !== '' ? " | pg:{$pago}" : '');
                    }
                }
                continue;
            }

            // Compactar recorrências
            if ($key === 'recorrencias_ativas' && is_array($value)) {
                $lines[] = "{$prefix}{$key}:";
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $desc  = mb_substr((string) ($item['descricao'] ?? '?'), 0, 35);
                        $valor = $item['valor'] ?? '?';
                        $freq  = $item['frequencia'] ?? $item['frequency'] ?? '';
                        $lines[] = "{$prefix}  - {$desc} | R\${$valor}" . ($freq ? " | {$freq}" : '');
                    }
                }
                continue;
            }

            $label = ucwords(str_replace('_', ' ', $key));

            if (is_array($value) && !array_is_list($value)) {
                $lines[] = "{$prefix}{$label}:";
                $lines[] = self::formatContext($value, $indent + 1);
            } elseif (is_array($value)) {
                $lines[] = "{$prefix}{$label}:";
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $parts   = array_map(fn($k, $v) => "{$k}:{$v}", array_keys($item), array_values($item));
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
