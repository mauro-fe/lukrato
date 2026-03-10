<?php

declare(strict_types=1);

namespace Application\Services\AI\Rules;

use Application\Models\Categoria;
use Application\Models\UserCategoryRule;

/**
 * Motor de regras para categorização de lançamentos sem LLM.
 * Usa mapeamento keyword→categoria para resolver ~60-70% dos casos com 0 tokens.
 *
 * Fluxo de categorização (prioridade):
 * 1. Regras personalizadas do usuário (user_category_rules) — aprendidas de correções
 * 2. Regras globais (RULE_MAP) — padrões fixos por keyword
 * 3. null — quando nenhuma regra casa (fallback para LLM)
 */
class CategoryRuleEngine
{
    /**
     * Mapeamento de padrões regex para [categoria, subcategoria].
     * As chaves são regex (case-insensitive) e os valores são arrays [nome_categoria, nome_subcategoria|null].
     */
    private const RULE_MAP = [
        // ─── Transporte ─────────────────────────────────
        'uber(?!\s*eats)'                       => ['Transporte', 'Uber'],
        '\b99\b|noventa\s*e\s*nove|99app|99pop|99taxi' => ['Transporte', '99'],
        'cabify|indrive|indriver'                => ['Transporte', 'Cabify'],
        'taxi|táxi'                              => ['Transporte', 'Táxi'],
        'combustível|combustivel|gasolina|álcool|alcool|etanol|diesel|posto|shell|ipiranga|br\s*distribuidora|abastec' => ['Transporte', 'Combustível'],
        'estacionamento|zona\s*azul|estapar|vaga\s*de\s*garagem' => ['Transporte', 'Estacionamento'],
        'pedagio|pedágio|sem\s*parar|conectcar|veloe|move\s*mais' => ['Transporte', 'Pedágio'],
        'metro|metrô|ônibus|onibus|bilhete\s*único|bilhete\s*unico|sptrans|brt|vlt|trem|barca|ferr[yi]|van\b' => ['Transporte', 'Transporte Público'],
        'seguro\s*(do)?\s*carro|ipva|licenciamento|detran|dpvat|multa\s*(de)?\s*trânsito|multa\s*(de)?\s*transito|guincho|reboque|oficina\s*mecânica|oficina\s*mecanica|mecânico|mecanico|funilaria|borracharia|troca\s*de\s*[oó]leo|alinhamento|balanceamento|revisão\s*(do)?\s*carro' => ['Transporte', 'Manutenção Veículo'],

        // ─── Alimentação ────────────────────────────────
        'ifood|i\s*food'                         => ['Alimentação', 'Delivery'],
        'rappi'                                  => ['Alimentação', 'Delivery'],
        'uber\s*eats'                            => ['Alimentação', 'Delivery'],
        'delivery|entrega\s*de\s*comida|zé\s*delivery|ze\s*delivery' => ['Alimentação', 'Delivery'],
        'mercado|supermercado|carrefour|extra\b|pão\s*de\s*açúcar|atacadão|atacadao|assaí|assai|sam\'?s\s*club|makro|bigbox|mundial|dia\b|aldi|hortifruti|sacolão|sacolao|feira\b' => ['Alimentação', 'Supermercado'],
        'restaurante|almoço|almoco|jantar|lanchonete|cantina|rodízio|rodizio|self.?service|buffet|marmita|marmitex|quentinha|pizza|pizzaria|hambúrger|hamburger|burger|lanche|sushi|japones|japonês|churrascaria|pastel|pastelaria|salgado|esfiha|esfirra|coxinha|açaí|acai|sorvet|gelateria|doce|doceria' => ['Alimentação', 'Restaurantes'],
        'padaria|pão|confeitaria|bolo|café|cafeteria|cafézinho|starbucks|nespresso' => ['Alimentação', 'Padaria'],
        'açougue|acougue|carne|frigorif|peixaria|frutos\s*do\s*mar' => ['Alimentação', 'Açougue'],

        // ─── Moradia ────────────────────────────────────
        'aluguel(?!\s*recebido)|locação(?!\s*recebid)'   => ['Moradia', 'Aluguel'],
        'condomínio|condominio'                  => ['Moradia', 'Condomínio'],
        'iptu'                                   => ['Moradia', 'IPTU'],
        'energia|luz\b|elétrica|eletrica|cemig|enel|cpfl|celpe|coelba|equatorial|conta\s*de\s*luz' => ['Moradia', 'Energia'],
        'água|agua\b|saneamento|sabesp|copasa|cedae|cagece|conta\s*de\s*[áa]gua' => ['Moradia', 'Água'],
        'gás|gas\b|gás\s*natural|comgas|comgás|supergasbras|ultragaz|botijão|botijao' => ['Moradia', 'Gás'],
        'internet\b|fibra|banda\s*larga|wi-?fi|provedor' => ['Moradia', 'Internet'],
        'telefone|celular|plano\s*(de)?\s*celular|claro|vivo|tim\b|oi\b|recarga|crédito\s*celular|credito\s*celular' => ['Moradia', 'Telefone'],
        'seguro\s*(do)?\s*imóvel|seguro\s*(do)?\s*imovel|seguro\s*residencial' => ['Moradia', 'Seguro'],
        'diarista|faxina|faxineira|empregada|doméstica|domestica|jardineiro|piscineiro' => ['Moradia', 'Serviços Domésticos'],
        'móveis|moveis|móvel|movel|sofá|sofa|mesa|cadeira|guarda.?roupa|colchão|colchao|cama|estante|prateleira|tok\s*stok|etna|mobly|madeira\s*madeira' => ['Moradia', 'Móveis'],
        'eletrodoméstico|eletrodomestico|geladeira|fogão|fogao|microondas|máquina\s*de\s*lavar|maquina\s*de\s*lavar|aspirador|liquidificador|air\s*fryer|cafeteira|torradeira|ferro\s*de\s*passar' => ['Moradia', 'Eletrodomésticos'],
        'reforma|pedreiro|pintor|encanador|eletricista|obra|construção|construcao|material\s*de\s*construção|material\s*de\s*construcao|telha|cimento|tinta|leroy|merlin|c&c|telha' => ['Moradia', 'Reforma'],

        // ─── Saúde ──────────────────────────────────────
        'farmácia|farmacia|drogaria|drogasil|droga\s*raia|pacheco|pague\s*menos|medicamento|remédio|remedio|dipirona|paracetamol|ibuprofeno|anti.?biótico|antibiotico|anti.?inflamatório|antiinflamatorio|pomada|vitamina|suplemento' => ['Saúde', 'Farmácia'],
        'médico|medico|consulta\s*médica|consulta\s*medica|clínica|clinica|emergência|emergencia|pronto.?socorro|hospital|internação|internacao|cirurgia|anestesia' => ['Saúde', 'Médico'],
        'dentista|odonto|ortodont|implante\s*dentário|implante\s*dentario|clareamento|canal\b|obturação' => ['Saúde', 'Dentista'],
        'plano\s*(de)?\s*saúde|plano\s*(de)?\s*saude|unimed|amil|bradesco\s*saude|sulamerica\s*saude|hapvida|notredame|prevent\s*senior' => ['Saúde', 'Plano de Saúde'],
        'academia|gym|smart\s*fit|bluefit|body\s*tech|crossfit|pilates|musculação|musculacao|personal\s*trainer' => ['Saúde', 'Academia'],
        'exame|laborat|hemograma|raio.?x|ultrassom|tomografia|ressonância|ressonancia|endoscopia|colonoscopia|biópsia|biopsia' => ['Saúde', 'Exames'],
        'psicólogo|psicologo|psiquiatra|terapia|terapeuta|análise|analise|sessão\s*terapia|sessao\s*terapia' => ['Saúde', 'Terapia'],
        'óculos|oculos|lente|oftalmol|ótica|otica|oftalmologista' => ['Saúde', 'Oftalmologia'],
        'fisioterapia|fisioterapeuta|rpg\b|quiropraxia|quiroprata|osteopata|acupuntura' => ['Saúde', 'Fisioterapia'],

        // ─── Educação ───────────────────────────────────
        'faculdade|universidade|mensalidade\s*escol|escola|colégio|colegio|matrícula|matricula|material\s*escolar' => ['Educação', 'Mensalidade'],
        'curso|udemy|alura|coursera|rocketseat|plataforma\s*de\s*ensino|hotmart|domestika|skillshare|masterclass' => ['Educação', 'Cursos'],
        'livraria|livro|saraiva|amazon.*livro|kindle|e-?book' => ['Educação', 'Livros'],
        'idioma|inglês|ingles|espanhol|francês|frances|duolingo|cambly|english|open\s*english|wizard|fisk|ccaa|cultura\s*inglesa' => ['Educação', 'Idiomas'],

        // ─── Lazer ──────────────────────────────────────
        'cinema|ingresso|filme|cinemark|cinépolis|kinoplex|UCI|pipoca\s*(?:no)?\s*cinema' => ['Lazer', 'Cinema'],
        'viagem|hotel|hostel|airbnb|pousada|passagem\s*aérea|passagem\s*aerea|booking|decolar|voo\b|azul\b|gol\b|latam|mala\s*de\s*viagem|resort' => ['Lazer', 'Viagem'],
        'show|concerto|teatro|musical|espetáculo|espetaculo|sympla|eventim|ingresso\s*rápido|ingresso\s*rapido' => ['Lazer', 'Shows e Eventos'],
        'jogo|game|playstation|xbox|steam|nintendo|switch|ps[45]|gamer|controle|console' => ['Lazer', 'Jogos'],
        'bar\b|cerveja|chopp|happy\s*hour|drink|balada|festa|boate|club\b|pub\b|boteco|cachaça|cachaca|whisky|vinho|espumante|destilado' => ['Lazer', 'Bar e Bebidas'],
        'parque|zoológico|zoologico|aquário|aquario|museu|exposição|exposicao|praia|camping|trilha|passeio' => ['Lazer', 'Passeios'],
        'futebol|pelada|quadra|esporte|natação|natacao|surf|skate|bicicleta|corrida|maratona' => ['Lazer', 'Esportes'],

        // ─── Assinaturas ────────────────────────────────
        'netflix'                                => ['Assinaturas', 'Streaming'],
        'spotify|deezer|apple\s*music|tidal|youtube\s*music' => ['Assinaturas', 'Streaming'],
        'disney\+?|disney\s*plus'                => ['Assinaturas', 'Streaming'],
        'hbo|max\b|star\+?|star\s*plus|globoplay|paramount|prime\s*video|amazon\s*prime|crunchyroll|mubi|telecine' => ['Assinaturas', 'Streaming'],
        'chatgpt|openai|copilot|github|midjourney|canva|figma|notion|slack|trello|adobe|photoshop|illustrator|office\s*365|microsoft\s*365' => ['Assinaturas', 'Software'],
        'icloud|google\s*one|dropbox|onedrive'   => ['Assinaturas', 'Armazenamento'],
        'playstation\s*plus|ps\s*plus|xbox\s*game\s*pass|nintendo\s*online|ea\s*play' => ['Assinaturas', 'Jogos'],
        'jornal|revista|folha|estadão|globo|uol|valor\s*econômico|valor\s*economico|meio\b' => ['Assinaturas', 'Notícias'],

        // ─── Vestuário ──────────────────────────────────
        'roupa|vestuário|vestuario|camisa|calça|calca|sapato|tênis|tenis|renner|riachuelo|c&a|centauro|zara|shein|shopee|lojas\s*americanas|hering|marisa|netshoes|nike|adidas|puma|new\s*balance|havaianas|meia|cueca|calcinha|sutiã|sutia|jaqueta|casaco|blusa|bermuda|short|vestido|saia|chinelo|bota|sandália|sandalia' => ['Vestuário', null],

        // ─── Eletrônicos / Tech ─────────────────────────
        'celular\s*novo|smartphone|iphone|samsung|galaxy|xiaomi|motorola|pixel' => ['Eletrônicos', 'Smartphone'],
        'notebook|laptop|computador|pc\b|desktop|monitor|teclado|mouse|fone|headset|airpods|headphone|caixa\s*de\s*som|bluetooth|carregador|cabo\s*usb|pen\s*drive|ssd|hd\s*externo|web\s*cam|impressora' => ['Eletrônicos', 'Periféricos'],
        'tv\b|televisão|televisao|smart\s*tv|soundbar|home\s*theater|chromecast|fire\s*stick|apple\s*tv|roku' => ['Eletrônicos', 'TV e Home'],
        'tablet|ipad|kindle|e-?reader' => ['Eletrônicos', 'Tablet'],

        // ─── Receitas comuns ────────────────────────────
        'salário|salario|holerite|folha\s*de\s*pagamento|contracheque|13[ºo]|décimo\s*terceiro|decimo\s*terceiro' => ['Salário', null],
        'freelance|freela|projeto|consultoria|serviço\s*prestado|servico\s*prestado|bico|job' => ['Freelance', null],
        'aluguel\s*recebido|renda\s*de\s*aluguel|inquilino' => ['Investimentos', 'Renda de Aluguel'],
        'dividendo|rendimento|juros|fii|fundo\s*imobiliário|fundo\s*imobiliario|ação|ações|tesouro\s*direto|cdb|lci|lca|cdi|renda\s*fixa|renda\s*variável|renda\s*variavel|selic|cot[aã]|debênture|debenture|cripto|bitcoin|ethereum|btc\b|eth\b' => ['Investimentos', 'Rendimentos'],
        'pix\s*recebido|transferência\s*recebida|transferencia\s*recebida|depósito|deposito' => ['Outros', null],
        'venda|vendas|vendi|vendido|loja|comissão|comissao' => ['Vendas', null],
        'mesada|ajuda\s*de\s*custo|reembolso|restituição|restituicao|indenização|indenizacao' => ['Receitas Extras', null],

        // ─── Finanças / Cartão ──────────────────────────
        'fatura|anuidade|cartão|cartao|tarifa\s*bancária|tarifa\s*bancaria|iof|ted\b|doc\b|taxa\s*de\s*manutenção|taxa\s*de\s*manutencao|seguro\s*de\s*vida|previdência|previdencia|pgbl|vgbl|consórcio|consorcio|empréstimo|emprestimo|financiamento|prestação|prestacao|parcela\s*(?:do)?\s*(?:carro|moto|casa|apto|apartamento)' => ['Finanças', null],

        // ─── Serviços Públicos ──────────────────────────
        'imposto|taxa\s*(?:de)?\s*(?:servico|serviço)|tributo|darf|gru|guia|multa|irpf|ir\b|inss\b|fgts\b|das\b|simples\s*nacional|mei\b|cartório|cartorio|certidão|certidao|cnh\b|passaporte|rg\b|cpf\b' => ['Serviços Públicos', null],

        // ─── Pets ───────────────────────────────────────
        'pet\s*shop|ração|racao|veterinário|veterinario|banho\s*(e\s*tosa)?|petz|cobasi|gato|cachorro|petisco|antipulga|vacina\s*(?:do)?\s*(?:pet|gato|cachorro)|castração|castracao' => ['Pets', null],

        // ─── Cuidados Pessoais ──────────────────────────
        'cabeleireir|salão|salao|barbearia|manicure|estética|estetica|depilação|depilacao|sobrancelha|maquiagem|perfume|hidratante|protetor\s*solar|shampoo|condicionador|creme|skincare|botox' => ['Cuidados Pessoais', null],

        // ─── Presentes / Doações ────────────────────────
        'presente|gift|aniversário|aniversario|natal|dia\s*das\s*mães|dia\s*das\s*maes|dia\s*dos\s*pais|casamento|chá\s*de\s*bebê|cha\s*de\s*bebe' => ['Presentes', null],
        'doação|doacao|doei|caridade|ong|igreja|dízimo|dizimo|oferta|esmola' => ['Doações', null],

        // ─── Filhos / Educação Infantil ─────────────────
        'creche|berçário|bercario|babá|baba|escola\s*infantil|maternal|fralda|leite\s*(?:em\s*pó|em\s*po)|mamadeira|brinquedo|parquinho|pediatra' => ['Educação', 'Educação Infantil'],

        // ─── Casa / Manutenção ──────────────────────────
        'limpeza|produto\s*de\s*limpeza|detergente|desinfetante|sabão|sabao|amaciante|alvejante|esponja|pano\s*de\s*chão|vassoura|rodo|balde' => ['Moradia', 'Limpeza'],
    ];

    /**
     * Tenta categorizar a descrição usando regras (0 tokens).
     *
     * Prioridade: regras do usuário (aprendidas) > regras globais (RULE_MAP).
     *
     * @param string   $description  Descrição do lançamento
     * @param int|null $userId       ID do usuário (para buscar categorias personalizadas)
     * @return array|null ['categoria' => string, 'subcategoria' => ?string, 'categoria_id' => ?int, 'subcategoria_id' => ?int, 'confidence' => string]
     */
    public static function match(string $description, ?int $userId = null): ?array
    {
        $normalized = mb_strtolower(trim($description));

        if ($normalized === '') {
            return null;
        }

        // 1. Tentar regras personalizadas do usuário (aprendidas de correções)
        if ($userId !== null) {
            $userMatch = self::matchUserRules($normalized, $userId);
            if ($userMatch !== null) {
                return $userMatch;
            }
        }

        // 2. Tentar regras globais (RULE_MAP)
        foreach (self::RULE_MAP as $pattern => $mapping) {
            if (preg_match('/' . $pattern . '/iu', $normalized)) {
                [$categoriaNome, $subcategoriaNome] = $mapping;

                // Tentar resolver IDs reais do banco
                $ids = self::resolveIds($categoriaNome, $subcategoriaNome, $userId);

                return [
                    'categoria'        => $categoriaNome,
                    'subcategoria'     => $subcategoriaNome,
                    'categoria_id'     => $ids['categoria_id'],
                    'subcategoria_id'  => $ids['subcategoria_id'],
                    'confidence'       => 'rule',
                ];
            }
        }

        return null;
    }

    /**
     * Verifica regras personalizadas do usuário (aprendidas).
     */
    private static function matchUserRules(string $normalizedDesc, int $userId): ?array
    {
        try {
            $rule = UserCategoryRule::findMatch($normalizedDesc, $userId);
            if ($rule === null) {
                return null;
            }

            // Carregar nomes da categoria/subcategoria
            $categoriaNome = null;
            $subcategoriaNome = null;

            $categoria = Categoria::find($rule->categoria_id);
            if ($categoria) {
                $categoriaNome = $categoria->nome;
            }

            if ($rule->subcategoria_id) {
                $sub = Categoria::find($rule->subcategoria_id);
                if ($sub) {
                    $subcategoriaNome = $sub->nome;
                }
            }

            return [
                'categoria'        => $categoriaNome ?? 'Outros',
                'subcategoria'     => $subcategoriaNome,
                'categoria_id'     => $rule->categoria_id,
                'subcategoria_id'  => $rule->subcategoria_id,
                'confidence'       => 'user_rule',
                'user_rule_id'     => $rule->id,
            ];
        } catch (\Throwable) {
            // Falha silenciosa — cair para regras globais
            return null;
        }
    }

    /**
     * Registra uma regra aprendida quando o usuário corrige a categoria de um lançamento.
     *
     * @param int      $userId          ID do usuário
     * @param string   $description     Descrição original do lançamento
     * @param int      $categoriaId     ID da categoria correta
     * @param int|null $subcategoriaId  ID da subcategoria correta
     * @param string   $source          'correction' | 'confirmed' | 'manual'
     */
    public static function learn(
        int $userId,
        string $description,
        int $categoriaId,
        ?int $subcategoriaId = null,
        string $source = 'correction'
    ): void {
        try {
            // Extrair a palavra-chave mais relevante da descrição (a mais longa)
            $keywords = self::extractKeywords($description);
            foreach ($keywords as $keyword) {
                UserCategoryRule::learn($userId, $keyword, $categoriaId, $subcategoriaId, $source);
            }
        } catch (\Throwable) {
            // Falha silenciosa — categorização adaptativa é best-effort
        }
    }

    /**
     * Confirma que uma sugestão de regra de usuário estava correta.
     * Incrementa usage_count para fortalecer a regra.
     */
    public static function confirmUserRule(int $ruleId): void
    {
        try {
            $rule = UserCategoryRule::find($ruleId);
            if ($rule) {
                $rule->confirm();
            }
        } catch (\Throwable) {
            // Falha silenciosa
        }
    }

    /**
     * Extrai palavras-chave relevantes de uma descrição para aprendizado.
     * Retorna 1-3 keywords significativas (>= 3 caracteres, não são stopwords).
     *
     * @return string[]
     */
    private static function extractKeywords(string $description): array
    {
        $normalized = mb_strtolower(trim($description));

        // Remove valores monetários e números
        $cleaned = preg_replace('/r?\$[\s]?\d[\d.,]*/i', '', $normalized);
        $cleaned = preg_replace('/\b\d+([.,]\d+)?\b/', '', $cleaned);

        // Tokenizar
        $tokens = preg_split('/[\s,;:\-\/\(\)]+/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);

        // Stopwords em pt-BR
        $stopwords = [
            'de', 'do', 'da', 'dos', 'das', 'em', 'no', 'na', 'nos', 'nas',
            'um', 'uma', 'uns', 'umas', 'o', 'a', 'os', 'as', 'para', 'por',
            'com', 'sem', 'que', 'se', 'ou', 'ao', 'e', 'mais', 'pra', 'pro',
            'meu', 'minha', 'meus', 'minhas', 'seu', 'sua',
            'paguei', 'gastei', 'comprei', 'recebi', // verbos de ação genéricos
        ];

        // Filtrar stopwords e tokens muito curtos
        $keywords = [];
        foreach ($tokens as $token) {
            if (mb_strlen($token) >= 3 && !in_array($token, $stopwords, true)) {
                $keywords[] = $token;
            }
        }

        // Retornar até 3 keywords, priorizando as mais longas (mais específicas)
        usort($keywords, fn($a, $b) => mb_strlen($b) - mb_strlen($a));

        return array_slice(array_unique($keywords), 0, 3);
    }

    /**
     * Resolve IDs da categoria e subcategoria no banco de dados.
     */
    private static function resolveIds(string $categoriaNome, ?string $subcategoriaNome, ?int $userId): array
    {
        $result = ['categoria_id' => null, 'subcategoria_id' => null];

        try {
            // Buscar categoria raiz pelo nome
            $query = Categoria::query()
                ->whereNull('parent_id')
                ->where('nome', 'LIKE', $categoriaNome);

            if ($userId !== null) {
                $query->where(function ($q) use ($userId) {
                    $q->whereNull('user_id')->orWhere('user_id', $userId);
                });
            }

            $categoria = $query->first();

            if ($categoria) {
                $result['categoria_id'] = $categoria->id;

                // Buscar subcategoria se informada
                if ($subcategoriaNome !== null) {
                    $sub = Categoria::query()
                        ->where('parent_id', $categoria->id)
                        ->where('nome', 'LIKE', $subcategoriaNome)
                        ->first();

                    if ($sub) {
                        $result['subcategoria_id'] = $sub->id;
                    }
                }
            }
        } catch (\Throwable) {
            // Falha silenciosa — retorna IDs null, os nomes ainda são úteis
        }

        return $result;
    }

    /**
     * Retorna todos os padrões registrados (para testes/debug).
     *
     * @return array<string, array{0: string, 1: ?string}>
     */
    public static function getRules(): array
    {
        return self::RULE_MAP;
    }
}
