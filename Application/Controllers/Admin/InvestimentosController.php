<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Models\Investimento;
use Application\Models\CategoriaInvestimento;
use Application\Lib\Auth;
use Application\Core\Response;

class InvestimentosController extends BaseController
{
    /** Resolve o ID do usuário logado (mantido para compatibilidade com o Admin). */
    private function resolveUserId(): ?int
    {
        return $this->userId
            ?? ($_SESSION['user']['id'] ?? null)
            ?? ($_SESSION['auth']['id'] ?? null)
            ?? ($_SESSION['usuario_id'] ?? null);
    }
    
    /**
     * Calcula as métricas de rentabilidade de um investimento. (Duplicado para fins de Admin Controller)
     */
    private function calculateInvestmentMetrics(Investimento $i): array
    {
        $valorInvestido = (float)$i->quantidade * (float)$i->preco_medio;
        $valorAtual     = (float)$i->quantidade * (float)($i->preco_atual ?? $i->preco_medio ?? 0);
        $lucro          = $valorAtual - $valorInvestido;
        
        return [
            'valor_atual_calculado' => $valorAtual,
            'valor_investido'       => $valorInvestido,
            'lucro'                 => $lucro,
            'rentabilidade_percent' => $valorInvestido > 0 ? (($lucro / $valorInvestido) * 100) : 0.0,
        ];
    }
    
    /**
     * Constrói todos os dados necessários (listas, totais, gráficos) para a View.
     */
    private function buildViewData(int $userId): array
    {
        $models = Investimento::with('categoria', 'conta')
            ->where('user_id', $userId)
            ->orderBy('nome', 'asc')
            ->get();

        $investments = [];
        $totalInvested = 0.0;
        $currentValue = 0.0;
        $statsByCategoryRaw = [];

        foreach ($models as $m) {
            $metrics = $this->calculateInvestmentMetrics($m);
            
            $qty  = (float)($m->quantidade ?? 0);
            $pm   = (float)($m->preco_medio ?? 0);
            $pnow = $metrics['valor_atual_calculado'] / $qty; // Preço atual usado para cálculo

            $investments[] = [
                'id'              => (int)$m->id,
                'name'            => (string)$m->nome,
                'ticker'          => $m->ticker ?: null,
                'quantity'        => $qty,
                'avg_price'       => $pm,
                'current_price'   => $m->preco_atual !== null ? (float)$m->preco_atual : null, // Original Price
                'category_name'   => $m->categoria?->nome ?? '—',
                'category_id'     => $m->categoria_id,
                'color'           => $m->categoria?->cor ?? '#6c757d',
                'valor_atual_row' => $metrics['valor_atual_calculado'], // Valor usado no gráfico/total
            ];

            $totalInvested += $metrics['valor_investido'];
            $currentValue  += $metrics['valor_atual_calculado'];
            
            // Agregação para o gráfico
            $catName = $m->categoria?->nome ?? 'Sem Categoria';
            if (!isset($statsByCategoryRaw[$catName])) {
                $statsByCategoryRaw[$catName] = [
                    'category' => $catName, 
                    'value' => 0.0, 
                    'color' => $m->categoria?->cor ?? '#6c757d'
                ];
            }
            $statsByCategoryRaw[$catName]['value'] += $metrics['valor_atual_calculado'];
        }

        $profit = $currentValue - $totalInvested;
        $profitPercentage = $totalInvested > 0 ? (($profit / $totalInvested) * 100) : 0.0;
        
        $categories = CategoriaInvestimento::orderBy('nome', 'asc')
            ->get(['id', 'nome'])
            ->map(fn($c) => ['id' => (int)$c->id, 'nome' => (string)$c->nome])
            ->toArray();

        return [
            'investments'      => $investments,
            'totalInvested'    => round($totalInvested, 2),
            'currentValue'     => round($currentValue, 2),
            'profit'           => round($profit, 2),
            'profitPercentage' => round($profitPercentage, 2),
            'statsByCategory'  => array_values($statsByCategoryRaw),
            'categories'       => $categories,
        ];
    }
    
    /**
     * Página de Investimentos (cards + gráfico + lista + modal)
     */
    public function index(): void
    {
        $this->requireAuth();
        $userId = $this->resolveUserId();
        
        if ($userId === null) {
             Response::forbidden('Usuário não autenticado.');
             return;
        }

        $data = $this->buildViewData((int)$userId);
        
        $data['pageTitle'] = 'Investimentos';
        $data['subTitle']  = 'Gerencie seus investimentos';
        
        // Render
        $this->render(
            'admin/investimentos/index', 
            $data, 
            'admin/partials/header', 
            'admin/partials/footer'
        );
    }
}