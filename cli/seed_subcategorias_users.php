<?php

/**
 * Script retroativo: cria subcategorias padrão (is_seeded=true) para todos os
 * usuários existentes que ainda não as possuem.
 *
 * - Reutiliza o mesmo mapa de subcategorias definido em AuthService.
 * - Busca a categoria-pai de cada usuário pelo nome+tipo; se não existir, pula.
 * - Verifica duplicatas pelo nome da subcategoria dentro do mesmo parent_id.
 * - Cada usuário é processado dentro de uma transação.
 *
 * Uso: php cli/seed_subcategorias_users.php [--dry-run]
 */

require __DIR__ . '/../bootstrap.php';

use Application\Models\Categoria;
use Application\Models\Usuario;
use Application\Services\Auth\AuthService;
use Illuminate\Database\Capsule\Manager as DB;

$dryRun = in_array('--dry-run', $argv ?? [], true);

echo "============================================\n";
echo "  SEED RETROATIVO DE SUBCATEGORIAS\n";
echo $dryRun ? "  *** MODO DRY-RUN (nada será gravado) ***\n" : '';
echo "============================================\n\n";

// ── Obter mapa de subcategorias (mesmo usado no registro) ──
$subcategoriasPadrao = AuthService::getSubcategoriasPadrao();

// Descobrir tipo de cada grupo a partir das chaves
// Categorias de despesa
$categoriasDespesaNomes = [
    'Moradia', 'Alimentação', 'Transporte', 'Contas e Serviços',
    'Saúde', 'Educação', 'Lazer', 'Compras', 'Assinaturas',
];
// Categorias de receita
$categoriasReceitaNomes = ['Salário', 'Freelance', 'Investimentos'];

// Montar mapa nome => tipo
$tipoMap = [];
foreach ($categoriasDespesaNomes as $n) {
    $tipoMap[$n] = 'despesa';
}
foreach ($categoriasReceitaNomes as $n) {
    $tipoMap[$n] = 'receita';
}

// ── Processar usuários ──
$usuarios = Usuario::all();
$totalUsuarios = $usuarios->count();
echo "Usuários encontrados: {$totalUsuarios}\n\n";

$totalCriadas   = 0;
$totalPuladas   = 0;
$usuariosAfetados = 0;

foreach ($usuarios as $index => $user) {
    $num = $index + 1;
    echo "[{$num}/{$totalUsuarios}] Usuário #{$user->id} ({$user->nome})";

    $criadasUser = 0;
    $puladasUser = 0;

    try {
        $callback = function () use ($user, $subcategoriasPadrao, $tipoMap, $dryRun, &$criadasUser, &$puladasUser) {
            // Buscar categorias-pai do usuário indexadas por nome
            $categoriasUser = Categoria::where('user_id', $user->id)
                ->whereNull('parent_id')
                ->get()
                ->keyBy('nome');

            foreach ($subcategoriasPadrao as $parentNome => $subs) {
                $tipo = $tipoMap[$parentNome] ?? null;
                if (!$tipo) {
                    continue;
                }

                /** @var Categoria|null $parent */
                $parent = $categoriasUser->get($parentNome);
                if (!$parent) {
                    // Usuário não tem essa categoria-pai, pular
                    continue;
                }

                foreach ($subs as $sub) {
                    // Verificar se já existe subcategoria com mesmo nome nessa pai
                    $exists = Categoria::where('user_id', $user->id)
                        ->where('parent_id', $parent->id)
                        ->where('nome', $sub['nome'])
                        ->exists();

                    if ($exists) {
                        $puladasUser++;
                        continue;
                    }

                    if (!$dryRun) {
                        Categoria::create([
                            'nome'      => $sub['nome'],
                            'icone'     => $sub['icone'],
                            'tipo'      => $tipo,
                            'user_id'   => $user->id,
                            'parent_id' => $parent->id,
                            'is_seeded' => true,
                        ]);
                    }
                    $criadasUser++;
                }
            }
        };

        if ($dryRun) {
            $callback();
        } else {
            DB::connection()->transaction($callback);
        }

        echo " → +{$criadasUser} criadas, {$puladasUser} já existiam\n";

        $totalCriadas += $criadasUser;
        $totalPuladas += $puladasUser;
        if ($criadasUser > 0) {
            $usuariosAfetados++;
        }
    } catch (Throwable $e) {
        echo " → ERRO: {$e->getMessage()}\n";
    }
}

// ── Marcar categorias-pai existentes como is_seeded ──
if (!$dryRun) {
    $updatedRoots = Categoria::whereNotNull('user_id')
        ->whereNull('parent_id')
        ->where(function ($q) {
            $q->where('is_seeded', false)->orWhereNull('is_seeded');
        })
        ->update(['is_seeded' => true]);

    echo "\nCategorias-pai marcadas como is_seeded: {$updatedRoots}\n";
}

echo "\n============================================\n";
echo "  RESULTADO\n";
echo "============================================\n";
echo "Usuários processados: {$totalUsuarios}\n";
echo "Usuários afetados:    {$usuariosAfetados}\n";
echo "Subcategorias criadas: {$totalCriadas}\n";
echo "Subcategorias puladas: {$totalPuladas} (já existiam)\n";
if ($dryRun) {
    echo "\n⚠  DRY-RUN: nenhuma alteração foi gravada.\n";
    echo "   Rode sem --dry-run para aplicar.\n";
}
echo "\n✅ Concluído!\n";
