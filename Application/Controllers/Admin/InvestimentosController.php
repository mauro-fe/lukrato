<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Models\Investimento;
use Application\Models\CategoriaInvestimento;

class InvestimentosController extends BaseController
{
    /**
     * Página de Investimentos (cards + gráfico + lista + modal)
     */
    public function index(): void
    {
        $this->requireAuth();

        // ID do usuário logado (robusto)
        $userId = $this->userId ?? ($_SESSION['usuario_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['auth']['id'] ?? null);

        // Consulta base
        $q = Investimento::with('categoria')->orderBy('nome', 'asc');

        // Filtra por usuário se houver id (evita WHERE user_id = NULL)
        if ($userId !== null) {
            $q->where('user_id', (int)$userId);
        }

        $models = $q->get();

        // Monta dados para a view
        $investments   = [];
        $totalInvested = 0.0;
        $currentValue  = 0.0;

        foreach ($models as $m) {
            $qty  = (float)($m->quantidade ?? 0);
            $pm   = (float)($m->preco_medio ?? 0);
            // usa preco_atual quando existir; senão, usa pm para os cards/gráfico
            $pnow = $m->preco_atual !== null ? (float)$m->preco_atual : $pm;
            $cat  = $m->categoria;

            $investments[] = [
                'id'            => (int)$m->id,
                'name'          => (string)$m->nome,
                'ticker'        => $m->ticker ?: null,
                'quantity'      => $qty,
                'avg_price'     => $pm,
                'current_price' => $m->preco_atual !== null ? (float)$m->preco_atual : null, // null para a tabela
                'category_name' => $cat?->nome ?? '—',
                'color'         => $cat?->cor  ?? '#6c757d',
            ];

            $totalInvested += $qty * $pm;
            $currentValue  += $qty * $pnow;
        }

        $profit           = $currentValue - $totalInvested;
        $profitPercentage = $totalInvested > 0 ? (($profit / $totalInvested) * 100) : 0.0;

        // Gráfico (pizza): soma por categoria usando current_price || avg_price
        $by = [];
        foreach ($investments as $i) {
            $cat   = $i['category_name'];
            $color = $i['color'];
            $val   = $i['quantity'] * ($i['current_price'] ?? $i['avg_price']);
            if (!isset($by[$cat])) {
                $by[$cat] = ['category' => $cat, 'value' => 0.0, 'color' => $color];
            }
            $by[$cat]['value'] += $val;
        }
        $statsByCategory = array_values($by);

        // Select de categorias no modal
        $categories = CategoriaInvestimento::orderBy('nome', 'asc')
            ->get(['id', 'nome'])
            ->map(fn($c) => ['id' => (int)$c->id, 'nome' => (string)$c->nome])
            ->toArray();

        // Títulos
        $pageTitle = 'Investimentos';
        $subTitle  = 'Gerencie seus investimentos';

        $data = compact(
            'pageTitle',
            'subTitle',
            'investments',
            'totalInvested',
            'currentValue',
            'profit',
            'profitPercentage',
            'statsByCategory',
            'categories'
        );

        // Render
        $this->render('admin/investimentos/index', $data, 'admin/partials/header', 'admin/partials/footer');
    }
}
