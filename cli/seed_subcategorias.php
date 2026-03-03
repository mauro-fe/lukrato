<?php

/**
 * Seed de subcategorias globais (sem user_id) para as categorias padrão do sistema.
 *
 * Essas subcategorias ficam disponíveis para todos os usuários.
 * Rode apenas uma vez após a migration de subcategorias.
 *
 * Uso: php cli/seed_subcategorias.php
 */

require __DIR__ . '/../bootstrap.php';

use Application\Models\Categoria;

// ============================================================
// Mapeamento: nome da categoria pai => subcategorias
// ============================================================

$subcategoriasDespesa = [
    'Alimentação' => [
        ['nome' => 'Restaurantes',   'icone' => 'utensils'],
        ['nome' => 'Supermercado',   'icone' => 'shopping-cart'],
        ['nome' => 'Delivery',       'icone' => 'bike'],
        ['nome' => 'Padaria',        'icone' => 'croissant'],
        ['nome' => 'Lanches',        'icone' => 'sandwich'],
    ],
    'Transporte' => [
        ['nome' => 'Combustível',    'icone' => 'fuel'],
        ['nome' => 'Uber / 99',     'icone' => 'car'],
        ['nome' => 'Transporte Público', 'icone' => 'bus'],
        ['nome' => 'Estacionamento', 'icone' => 'parking-circle'],
        ['nome' => 'Manutenção Veículo', 'icone' => 'wrench'],
    ],
    'Moradia' => [
        ['nome' => 'Aluguel',        'icone' => 'home'],
        ['nome' => 'Condomínio',     'icone' => 'building'],
        ['nome' => 'Energia',        'icone' => 'zap'],
        ['nome' => 'Água',           'icone' => 'droplets'],
        ['nome' => 'Internet',       'icone' => 'wifi'],
        ['nome' => 'Gás',            'icone' => 'flame'],
    ],
    'Saúde' => [
        ['nome' => 'Farmácia',       'icone' => 'pill'],
        ['nome' => 'Consultas',      'icone' => 'stethoscope'],
        ['nome' => 'Plano de Saúde', 'icone' => 'heart-pulse'],
        ['nome' => 'Academia',       'icone' => 'dumbbell'],
    ],
    'Educação' => [
        ['nome' => 'Cursos',         'icone' => 'graduation-cap'],
        ['nome' => 'Livros',         'icone' => 'book-open'],
        ['nome' => 'Mensalidade',    'icone' => 'school'],
    ],
    'Lazer' => [
        ['nome' => 'Cinema',         'icone' => 'popcorn'],
        ['nome' => 'Viagens',        'icone' => 'plane'],
        ['nome' => 'Jogos',          'icone' => 'gamepad-2'],
        ['nome' => 'Streaming',      'icone' => 'tv'],
    ],
    'Compras' => [
        ['nome' => 'Roupas',         'icone' => 'shirt'],
        ['nome' => 'Eletrônicos',    'icone' => 'smartphone'],
        ['nome' => 'Casa e Decoração', 'icone' => 'sofa'],
    ],
    'Contas' => [
        ['nome' => 'Telefone',       'icone' => 'phone'],
        ['nome' => 'Impostos',       'icone' => 'landmark'],
        ['nome' => 'Seguros',        'icone' => 'shield-check'],
    ],
    'Assinaturas' => [
        ['nome' => 'Música',         'icone' => 'music'],
        ['nome' => 'Armazenamento',  'icone' => 'cloud'],
        ['nome' => 'Software',       'icone' => 'laptop'],
    ],
];

$subcategoriasReceita = [
    'Salário' => [
        ['nome' => 'Salário Fixo',     'icone' => 'banknote'],
        ['nome' => 'Hora Extra',       'icone' => 'clock'],
        ['nome' => '13º Salário',      'icone' => 'gift'],
    ],
    'Freelance' => [
        ['nome' => 'Projetos',         'icone' => 'briefcase'],
        ['nome' => 'Consultoria',      'icone' => 'message-square'],
    ],
    'Investimentos' => [
        ['nome' => 'Dividendos',       'icone' => 'trending-up'],
        ['nome' => 'Renda Fixa',       'icone' => 'landmark'],
        ['nome' => 'Ações',            'icone' => 'bar-chart-2'],
    ],
];

// ============================================================
// Execução
// ============================================================

echo "=== Seed de Subcategorias Globais ===\n\n";

$totalCreated = 0;
$totalSkipped = 0;

/**
 * Processa um grupo de subcategorias para um determinado tipo.
 */
function seedSubcategorias(array $map, string $tipo, int &$created, int &$skipped): void
{
    echo strtoupper($tipo) . ":\n";

    foreach ($map as $parentNome => $subcategorias) {
        // Buscar categoria pai global (sem user_id) ou qualquer instância como referência
        $parent = Categoria::whereNull('user_id')
            ->whereNull('parent_id')
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($parentNome)])
            ->where('tipo', $tipo)
            ->first();

        // Se não existir global, tentar buscar como referência (para pegar o ID)
        if (!$parent) {
            $parent = Categoria::whereNull('parent_id')
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($parentNome)])
                ->where('tipo', $tipo)
                ->first();
        }

        if (!$parent) {
            echo "  ⚠️  Categoria pai '{$parentNome}' ({$tipo}) não encontrada — pulando\n";
            $skipped += count($subcategorias);
            continue;
        }

        echo "  📁 {$parentNome} (ID: {$parent->id})\n";

        foreach ($subcategorias as $sub) {
            // Verificar se já existe
            $exists = Categoria::where('parent_id', $parent->id)
                ->whereNull('user_id')
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($sub['nome'])])
                ->exists();

            if ($exists) {
                echo "    ⏭️  {$sub['nome']} — já existe\n";
                $skipped++;
                continue;
            }

            Categoria::create([
                'nome'      => $sub['nome'],
                'icone'     => $sub['icone'] ?? null,
                'tipo'      => $parent->tipo,
                'user_id'   => null, // global
                'parent_id' => $parent->id,
            ]);

            echo "    ✅ {$sub['nome']}\n";
            $created++;
        }
    }

    echo "\n";
}

seedSubcategorias($subcategoriasDespesa, 'despesa', $totalCreated, $totalSkipped);
seedSubcategorias($subcategoriasReceita, 'receita', $totalCreated, $totalSkipped);

echo "=== Resultado ===\n";
echo "✅ Subcategorias criadas: {$totalCreated}\n";
echo "⏭️  Já existentes (puladas): {$totalSkipped}\n";

$totalGlobal = Categoria::whereNull('user_id')->whereNotNull('parent_id')->count();
echo "📊 Total de subcategorias globais no banco: {$totalGlobal}\n";