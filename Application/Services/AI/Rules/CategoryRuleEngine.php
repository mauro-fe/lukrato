<?php

declare(strict_types=1);

namespace Application\Services\AI\Rules;

use Application\Models\Categoria;

/**
 * Motor de regras para categorização de lançamentos sem LLM.
 * Usa mapeamento keyword→categoria para resolver ~60-70% dos casos com 0 tokens.
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
        '\b99\b|noventa\s*e\s*nove|99app|99taxi' => ['Transporte', '99'],
        'cabify'                                 => ['Transporte', 'Cabify'],
        'taxi|táxi'                              => ['Transporte', 'Táxi'],
        'combustível|combustivel|gasolina|álcool|alcool|etanol|diesel|posto|shell|ipiranga|br\s*distribuidora' => ['Transporte', 'Combustível'],
        'estacionamento|zona\s*azul|estapar'     => ['Transporte', 'Estacionamento'],
        'pedagio|pedágio|sem\s*parar|conectcar'  => ['Transporte', 'Pedágio'],
        'metro|metrô|ônibus|onibus|bilhete\s*único|bilhete\s*unico|sptrans|brt|vlt' => ['Transporte', 'Transporte Público'],

        // ─── Alimentação ────────────────────────────────
        'ifood|i\s*food'                         => ['Alimentação', 'Delivery'],
        'rappi'                                  => ['Alimentação', 'Delivery'],
        'uber\s*eats'                            => ['Alimentação', 'Delivery'],
        'delivery|entrega\s*de\s*comida'         => ['Alimentação', 'Delivery'],
        'mercado|supermercado|carrefour|extra\b|pão\s*de\s*açúcar|atacadão|atacadao|assaí|assai|sam\'?s\s*club' => ['Alimentação', 'Supermercado'],
        'restaurante|almoço|almoco|jantar|lanchonete|cantina|rodízio|rodizio' => ['Alimentação', 'Restaurantes'],
        'padaria|pão|confeitaria|bolo'           => ['Alimentação', 'Padaria'],
        'açougue|acougue|carne|frigorif'         => ['Alimentação', 'Açougue'],

        // ─── Moradia ────────────────────────────────────
        'aluguel|locação'                        => ['Moradia', 'Aluguel'],
        'condomínio|condominio'                  => ['Moradia', 'Condomínio'],
        'iptu'                                   => ['Moradia', 'IPTU'],
        'energia|luz|elétrica|eletrica|cemig|enel|cpfl|celpe|coelba|equatorial' => ['Moradia', 'Energia'],
        'água|agua|saneamento|sabesp|copasa|cedae|cagece'  => ['Moradia', 'Água'],
        'gás|gas\b|gás\s*natural|comgas|comgás'            => ['Moradia', 'Gás'],
        'internet\b|fibra|banda\s*larga|wi-?fi|provedor'   => ['Moradia', 'Internet'],
        'telefone|celular|plano\s*(de)?\s*celular|claro|vivo|tim\b|oi\b' => ['Moradia', 'Telefone'],

        // ─── Saúde ──────────────────────────────────────
        'farmácia|farmacia|drogaria|drogasil|droga\s*raia|pacheco|pague\s*menos|medicamento|remédio|remedio' => ['Saúde', 'Farmácia'],
        'médico|medico|consulta\s*médica|consulta\s*medica|clínica|clinica' => ['Saúde', 'Médico'],
        'dentista|odonto|ortodont'               => ['Saúde', 'Dentista'],
        'plano\s*(de)?\s*saúde|plano\s*(de)?\s*saude|unimed|amil|bradesco\s*saude|sulamerica\s*saude' => ['Saúde', 'Plano de Saúde'],
        'academia|gym|smart\s*fit|bluefit|body\s*tech' => ['Saúde', 'Academia'],
        'exame|laborat|hemograma|raio.?x|ultrassom|tomografia|ressonância' => ['Saúde', 'Exames'],
        'psicólogo|psicologo|psiquiatra|terapia|terapeuta' => ['Saúde', 'Terapia'],

        // ─── Educação ───────────────────────────────────
        'faculdade|universidade|mensalidade\s*escol|escola|colégio|colegio' => ['Educação', 'Mensalidade'],
        'curso|udemy|alura|coursera|rocketseat|plataforma\s*de\s*ensino' => ['Educação', 'Cursos'],
        'livraria|livro|saraiva|amazon.*livro'   => ['Educação', 'Livros'],

        // ─── Lazer ──────────────────────────────────────
        'cinema|ingresso|filme'                  => ['Lazer', 'Cinema'],
        'viagem|hotel|hostel|airbnb|pousada|passagem\s*aérea|passagem\s*aerea|booking|decolar' => ['Lazer', 'Viagem'],
        'show|concerto|teatro|musical|espetáculo' => ['Lazer', 'Shows e Eventos'],
        'jogo|game|playstation|xbox|steam|nintendo' => ['Lazer', 'Jogos'],
        'bar\b|cerveja|chopp|happy\s*hour|drink|balada|festa' => ['Lazer', 'Bar e Bebidas'],

        // ─── Assinaturas ────────────────────────────────
        'netflix'                                => ['Assinaturas', 'Streaming'],
        'spotify|deezer|apple\s*music|tidal|youtube\s*music' => ['Assinaturas', 'Streaming'],
        'disney\+?|disney\s*plus'                => ['Assinaturas', 'Streaming'],
        'hbo|max\b|star\+?|star\s*plus|globoplay|paramount|prime\s*video|amazon\s*prime' => ['Assinaturas', 'Streaming'],
        'chatgpt|openai|copilot|github|midjourney|canva' => ['Assinaturas', 'Software'],
        'icloud|google\s*one|dropbox|onedrive'   => ['Assinaturas', 'Armazenamento'],

        // ─── Vestuário ──────────────────────────────────
        'roupa|vestuário|vestuario|camisa|calça|calca|sapato|tênis|tenis|renner|riachuelo|c&a|centauro|zara|shein|shopee' => ['Vestuário', null],

        // ─── Receitas comuns ────────────────────────────
        'salário|salario|holerite|folha\s*de\s*pagamento|contracheque' => ['Salário', null],
        'freelance|freela|projeto|consultoria|serviço\s*prestado' => ['Freelance', null],
        'aluguel\s*recebido|renda\s*de\s*aluguel|inquilino' => ['Investimentos', 'Renda de Aluguel'],
        'dividendo|rendimento|juros|fii|fundo\s*imobiliário|ação|ações|tesouro\s*direto|cdb|lci|lca|cdi' => ['Investimentos', 'Rendimentos'],
        'pix\s*recebido|transferência\s*recebida|depósito|deposito' => ['Outros', null],

        // ─── Serviços Públicos ──────────────────────────
        'imposto|taxa|tributo|darf|gru|guia|multa' => ['Serviços Públicos', null],
    ];

    /**
     * Tenta categorizar a descrição usando regras (0 tokens).
     *
     * @param string   $description  Descrição do lançamento
     * @param int|null $userId       ID do usuário (para buscar categorias personalizadas)
     * @return array|null ['categoria' => string, 'subcategoria' => ?string, 'categoria_id' => ?int, 'subcategoria_id' => ?int]
     */
    public static function match(string $description, ?int $userId = null): ?array
    {
        $normalized = mb_strtolower(trim($description));

        if ($normalized === '') {
            return null;
        }

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
