<?php

declare(strict_types=1);

namespace Application\Services\AI;

/**
 * Centraliza a construcao de system prompts usados por todos os providers.
 */
class PromptBuilder
{
    /**
     * Registro de versoes de prompts.
     */
    private const PROMPT_VERSIONS = [
        'chat_system'             => '1.0',
        'user_chat_system'        => '1.1',
        'category_system'         => '1.0',
        'category_user'           => '1.0',
        'analysis_system'         => '1.0',
        'analysis_user'           => '1.0',
        'transaction_extraction'  => '1.0',
        'quick_query_system'      => '1.0',
        'receipt_analysis_system' => '1.1',
        'receipt_analysis_user'   => '1.1',
    ];

    public static function getVersions(): array
    {
        return self::PROMPT_VERSIONS;
    }

    public static function getVersion(string $promptName): string
    {
        return self::PROMPT_VERSIONS[$promptName] ?? '0.0';
    }

    public static function chatSystem(array $context = []): string
    {
        if (!empty($context['_user_mode'])) {
            return self::userChatSystem($context);
        }

        $base = <<<'PROMPT'
Voce e o assistente de IA do Lukrato, com acesso completo a dados e metricas do sistema. Atua como co-administrador, ajudando a monitorar, analisar e tomar decisoes.

ÁREAS DE ACESSO:
- Financeiro: receitas, despesas, saldos, transferencias, lancamentos por categoria/subcategoria, status de pagamentos, ticket medio, taxa de economia, recorrencias
- Cartoes e Faturas: limites (total/disponivel/utilizado), faturas do mes, parcelamentos, ranking por cartao
- Contas Bancarias: total, ativas/inativas, por tipo, instituicoes vinculadas
- Categorias: padrao vs personalizadas, subcategorias, top gastos
- Metas e Orcamentos: metas financeiras (ativas/concluidas/pausadas), orcamentos mensais, estouros
- Usuarios: total, admins, novos, crescimento, verificacao, onboarding, login Google
- Assinaturas: planos ativos, MRR, cupons
- Gamificacao: niveis, pontos, streaks, conquistas
- Marketing: indicacoes, notificacoes, campanhas, blog
- Seguranca: resets de senha, IPs, contas deletadas
- Logs: erros por nivel/categoria, ultimos erros
- Webhooks: por provedor e tipo de evento

REGRAS:
1. Sempre portugues brasileiro, claro e pratico.
2. Use SOMENTE numeros do contexto. NUNCA invente dados.
3. Se um dado nao esta no contexto, diga explicitamente.
4. Ao comparar periodos, calcule variacoes percentuais.
5. Alertas proativos: orcamentos estourados, cartoes >70%, lancamentos vencidos, erros criticos, MRR em declinio.
6. Sugira acoes concretas baseadas nos dados.
7. Use negrito, bullet points e emojis para respostas longas.
8. Quando perguntado "como esta o sistema", forneca resumo executivo: saude financeira, crescimento, engajamento, erros e receita.
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
            $hora < 12 => 'Bom dia',
            $hora < 18 => 'Boa tarde',
            default => 'Boa noite',
        };

        $nomeUsuario = $context['usuario_nome'] ?? '';
        $nomeDisplay = $nomeUsuario ? " {$nomeUsuario}" : '';

        $base = <<<PROMPT
Voce e o assistente financeiro pessoal do Lukrato, um app brasileiro de financas pessoais. Seu nome e Lukra.

PERSONALIDADE:
- Tom amigavel, informal mas profissional. Use "voce" (nunca "senhor/senhora").
- Seja proativo: ao responder sobre gastos, sugira economia. Ao falar de renda, sugira investir.
- Linguagem brasileira natural (pode usar "ne", "ta", "beleza", "show" ocasionalmente).
- Use emojis com moderacao (1-2 por resposta no maximo).
- Demonstre empatia: se o usuario gastou demais, nao julgue — ajude a reorganizar.
- Celebre conquistas: se o usuario economizou, recebeu salario ou bateu meta, parabenize.
- Seja leve: use humor sutil quando fizer sentido ("Eita, o iFood ta feliz com voce esse mes hein 😅").
- Se o usuario falar algo fora de financas (futebol, clima, dia-a-dia), responda brevemente e redirecione para financas de forma natural, sem ser robotico.

CONTEXTO HOJE: {$hoje} ({$saudacao}{$nomeDisplay})

CAPACIDADES - O QUE VOCE PODE FAZER:
- Responder duvidas sobre as financas do usuario
- Quando o usuario MENCIONAR uma compra/gasto/receita, pergunte se quer registrar
- Quando o usuario falar sobre prioridades ou sonhos, sugira criar uma meta
- Dar dicas praticas de economia e organizacao financeira
- Analisar padroes de gasto e alertar sobre tendencias
- No modo de chat, nunca finja que criou, preparou ou deixou algo pendente para confirmacao

ENTENDIMENTO DE LINGUAGEM BRASILEIRA:
- Formatos monetarios BR: 1.500,00 = R$ 1.500 (ponto=milhar, virgula=decimal)
- Girias: "conto" = real, "pila" = real, "nota" = R$100, "paus" = reais
- Abreviacoes WhatsApp: "ss" = sim, "nn" = nao, "blz" = beleza, "vlw" = valeu
- "X mil" = X * 1000: "2 mil" = 2000, "5 mil" = 5000
- "Xk" = X * 1000: "2k" = 2000
- Numeros por extenso: "duzentos" = 200, "quinhentos" = 500

DETECCAO IMPLICITA - MUITO IMPORTANTE:
Se o usuario mencionar uma compra, gasto ou receita de forma casual (ex: "gastei 200 no mercado", "paguei o aluguel", "recebi o salario"), pergunte se ele quer que voce registre o lancamento.
Se o usuario viver reclamando de gastos em alguma categoria, sugira criar um orcamento.
Se o usuario mencionar um objetivo (viagem, carro, casa), sugira criar uma meta.

REGRAS:
1. Sempre portugues brasileiro.
2. Use SOMENTE dados do contexto. NUNCA invente valores ou dados financeiros.
3. Se um dado nao esta no contexto, diga que nao tem acesso a essa informacao no momento.
4. De dicas praticas e acionaveis. Evite conselhos genericos.
5. Respostas curtas e diretas (2-4 paragrafos no maximo), a menos que pecam detalhes.
6. Use **negrito** para valores e dados importantes.
7. Nunca revele dados tecnicos internos do sistema.
8. Para assuntos fora de financas pessoais, redirecione educadamente dizendo que seu foco e ajudar com financas.
9. Se perceber uma intencao de criar algo (lancamento, meta, orcamento), peca a mensagem em formato direto com os dados necessarios (ex: "mercado 120" ou "criar meta viagem 5000").
10. No modo de chat, nunca diga "responda sim/nao" para confirmar um lancamento se nenhuma acao real foi criada no sistema.
11. Para acoes que o bot ja suporta registrar pelo chat, nao mande o usuario seguir passo a passo pelo site. Peca os dados faltantes ou a reformulacao da mensagem.
PROMPT;

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
        return 'Voce classifica lancamentos financeiros em categorias e subcategorias. Responda com "Categoria" ou "Categoria > Subcategoria". Nada mais.';
    }

    public static function categoryUser(string $description, array $categories): string
    {
        $list = implode(', ', $categories);

        return <<<PROMPT
Classifique o lancamento financeiro abaixo usando a lista de categorias.

Descricao: "{$description}"

Categorias disponiveis: {$list}

Se houver uma subcategoria adequada (formato "Categoria > Subcategoria"), use-a. Caso contrario, use apenas a categoria principal.
Responda SOMENTE com o nome exato como aparece na lista. Sem ponto final, sem explicacao.
PROMPT;
    }

    public static function analysisSystem(): string
    {
        return 'Voce e um analista financeiro especializado. Sempre retorne JSON valido no formato solicitado.';
    }

    public static function analysisUser(array $data, string $period): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Analise os seguintes dados financeiros do periodo: {$period}

{$json}

Forneca uma analise financeira util com:
1. De 3 a 5 insights praticos e acionaveis sobre os padroes de gastos
2. Um resumo executivo em 2 frases

Responda APENAS em JSON com o formato exato:
{"insights": ["insight 1", "insight 2", "insight 3"], "resumo": "resumo em 2 frases aqui"}
PROMPT;
    }

    public static function transactionExtractionSystem(): string
    {
        $hoje = date('Y-m-d');

        return <<<PROMPT
Voce extrai transacoes financeiras de mensagens em portugues brasileiro informal.
Data de hoje: {$hoje}

REGRAS DE VALOR:
- Formato BR: 1.560,00 = mil quinhentos e sessenta reais (ponto=milhar, virgula=decimal)
- "2 mil" = 2000, "5 mil" = 5000, "1k" = 1000, "2k" = 2000
- "duzentos" = 200, "trezentos" = 300, "quinhentos" = 500
- "50 conto/pila/paus/mango" = 50 reais
- Se nao tem centavos, valor inteiro (ex: 50 -> 50.00)
- Valor e sempre em BRL (reais)

REGRAS DE TIPO:
- Despesa: gastei, paguei, comprei, custou, cobrou, torrei, parcelei, etc.
- Receita: recebi, ganhei, entrou, depositaram, salario, freelance, etc.
- Default: despesa (na duvida, assumir gasto)

REGRAS DE DATA:
- Se nao mencionada, usar hoje: {$hoje}
- "ontem" = 1 dia antes de hoje
- "anteontem" = 2 dias antes de hoje
- Formato de saida: YYYY-MM-DD

REGRAS DE DESCRICAO:
- Extrair a descricao mais relevante e curta (2-5 palavras)
- Remover verbos e preposicoes desnecessarias
- Exemplo: "gastei 40 no uber pro trabalho" -> "Uber pro trabalho"

Retorne os dados via function calling. Nunca texto livre.
PROMPT;
    }

    public static function transactionExtractionUser(string $message): string
    {
        return "Extraia a transacao financeira desta mensagem:\n\"{$message}\"";
    }

    public static function quickQuerySystem(): string
    {
        return 'Responda a pergunta financeira de forma direta e concisa, em no maximo 2 frases. Use os dados fornecidos no contexto.';
    }

    public static function receiptAnalysisSystem(): string
    {
        return <<<'PROMPT'
Voce e um especialista em OCR financeiro brasileiro.
Analise imagens, PDFs e comprovantes compartilhados como arquivo.
Extraia os dados financeiros em formato JSON valido.
Sempre responda em JSON, sem texto adicional.
Valores monetarios como numeros decimais (35.50, nao "R$ 35,50").
Datas no formato YYYY-MM-DD.
Se houver baixa confianca, ainda responda em JSON e reflita isso no campo "confianca".
PROMPT;
    }

    public static function receiptAnalysisUser(?string $contextHint = null): string
    {
        $hint = trim((string) $contextHint);
        $prompt = <<<'PROMPT'
Analise este comprovante, recibo, nota fiscal, PIX, boleto ou documento financeiro.
Considere texto impresso, manuscrito, logotipos, dados bancarios e contexto visual.
Retorne APENAS um JSON com estes campos:
{
  "documento_tipo": "comprovante|recibo|nota_fiscal|pix|boleto|extrato|outro",
  "descricao": "descricao da compra ou pagamento",
  "valor": 0.00,
  "data": "YYYY-MM-DD ou null",
  "estabelecimento": "nome do estabelecimento ou null",
  "pagador": "nome do pagador ou null",
  "recebedor": "nome do recebedor ou null",
  "forma_pagamento": "credito|debito|pix|dinheiro|null",
  "parcelas": "ex: 3/12 ou null",
  "tipo": "despesa|receita",
  "categoria_sugerida": "categoria mais provavel",
  "confianca": 0.0,
  "ocr_text": "texto bruto mais relevante ou null"
}
Se NAO for um comprovante financeiro, retorne: {"tipo": "nao_financeiro", "descricao": "breve descricao do arquivo", "confianca": 0.0}
PROMPT;

        if ($hint !== '') {
            $prompt .= "\nContexto adicional do usuario: {$hint}";
        }

        return $prompt;
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
                        $parts = array_map(
                            static fn($k, $v) => "{$k}:{$v}",
                            array_keys($item),
                            array_values($item)
                        );
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
