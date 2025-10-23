<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Models\Investimento;
use Application\Models\CategoriaInvestimento;

class InvestimentosController extends BaseController
{
    /**
     * Dashboard de Investimentos (cards + gráfico + lista + modal)
     */
    public function index(): void
    {
        $this->requireAuth();
        $userId = $this->userId;

        // 1) Carrega investimentos do usuário (com categoria)
        $models = Investimento::with('categoria')
            ->where('user_id', $userId)                 // troque para 'usuario_id' se necessário
            ->orderBy('nome', 'asc')
            ->get();

        // 2) Converte para o formato que a view usa na tabela
        $investments   = [];
        $totalInvested = 0.0;
        $currentValue  = 0.0;

        foreach ($models as $m) {
            $qty   = (float)($m->quantidade ?? 0);
            $pm    = (float)($m->preco_medio ?? 0);
            $pnow  = (float)($m->preco_atual ?? 0);
            $cat   = $m->categoria;

            $investments[] = [
                'id'            => (int)$m->id,
                'name'          => (string)$m->nome,
                'ticker'        => $m->ticker ?: null,
                'quantity'      => $qty,
                'avg_price'     => $pm,
                'current_price' => $m->preco_atual !== null ? $pnow : null,
                'category_name' => $cat?->nome ?? '—',
                'color'         => $cat?->cor  ?? '#6c757d',
            ];

            $totalInvested += $qty * $pm;
            $currentValue  += $qty * $pnow;
        }

        // 3) Cards
        $profit           = $currentValue - $totalInvested;
        $profitPercentage = $totalInvested > 0 ? (($profit / $totalInvested) * 100) : 0.0;

        // 4) Dados do gráfico (pizza) — soma valor atual por categoria
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
        $statsByCategory = array_values($by); // array de ['category','value','color']

        // 5) Categorias para o modal (select)
        $categories = CategoriaInvestimento::orderBy('nome', 'asc')
            ->get(['id', 'nome'])
            ->map(fn($c) => ['id' => (int)$c->id, 'nome' => (string)$c->nome])
            ->toArray();

        // 6) Títulos da página
        $pageTitle = 'Investimentos';
        $subTitle  = 'Gerencie seus investimentos';

        // 7) Pacote de dados para a view
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

        // 8) Render com seu header/footer padrão
        $this->render(
            'admin/investimentos/index',
            $data,
            'admin/partials/header',
            'admin/partials/footer'
        );
    }

    /**
     * (Opcional) Página separada para criação
     * Mantive para compatibilidade caso você use rota /investimentos/novo
     */
    public function archived(): void
    {
        $this->requireAuth();

        // se quiser uma página separada, renderize o form aqui;
        // como estamos usando modal, pode só redirecionar pra index.
        $this->render(
            'admin/investimentos/index',
            ['pageTitle' => 'Investimentos', 'subTitle' => 'Gerencie seus investimentos'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
