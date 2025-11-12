<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\InvestimentoService; // Importa o Service
use Throwable;

class InvestimentosController extends BaseController
{
    private InvestimentoService $service;

    public function __construct()
    {
        parent::__construct();
        // Instancia o serviço
        $this->service = new InvestimentoService();
    }

    /** Resolve o ID do usuário logado (robusto). */
    private function resolveUserId(): ?int
    {
        return $this->userId ?? ($_SESSION['usuario_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['auth']['id'] ?? null);
    }

    /**
     * Página de Investimentos (cards + gráfico + lista + modal)
     */
    public function index(): void
    {
        try {
            $this->requireAuth();
            $userId = $this->resolveUserId();

            if ($userId === null) {
                Response::forbidden('Usuário não autenticado.'); // Ou $this->redirect('login');
                return;
            }

            // 1. Busca os dados usando o Serviço

            // Filtros (vazios por enquanto, mas poderiam vir do $_GET)
            $filters = ['order' => 'nome', 'dir' => 'asc'];

            // Busca investimentos já formatados com métricas
            $investments = $this->service->getInvestimentos($userId, $filters);

            // Busca estatísticas agregadas
            $stats = $this->service->getStats($userId);

            // Busca categorias para o modal
            $categories = $this->service->getCategorias()
                ->map(fn($c) => ['id' => (int)$c->id, 'nome' => (string)$c->nome])
                ->toArray();

            // 2. Prepara dados para o Gráfico (Pizza por Categoria)
            $byCategory = [];
            foreach ($investments as $i) {
                // Busca a categoria pelo ID (o service de categorias poderia ser mais robusto)
                $catNome = 'Sem Categoria';
                $catColor = '#6c757d';
                foreach ($categories as $c) {
                    if ($c['id'] === $i['categoria_id']) {
                        $catNome = $c['nome'];
                        // $catColor = $c['cor'] ?? '#6c757d'; // Se houver cor no modelo Categoria
                        break;
                    }
                }

                if (!isset($byCategory[$catNome])) {
                    $byCategory[$catNome] = ['category' => $catNome, 'value' => 0.0, 'color' => $catColor];
                }
                $byCategory[$catNome]['value'] += $i['valor_atual'];
            }
            $statsByCategory = array_values($byCategory);

            // 3. Monta o payload final para a view
            $data = [
                'pageTitle'        => 'Investimentos',
                'subTitle'         => 'Gerencie seus investimentos',
                'investments'      => $investments,
                'totalInvested'    => $stats['total_investido'],
                'currentValue'     => $stats['valor_atual'],
                'profit'           => $stats['lucro'],
                'profitPercentage' => $stats['rentabilidade'],
                'statsByCategory'  => $statsByCategory,
                'categories'       => $categories,
            ];

            // 4. Renderiza
            $this->render('admin/investimentos/index', $data, 'admin/partials/header', 'admin/partials/footer');
        } catch (Throwable $e) {
            // Em um App Admin, talvez seja melhor logar e mostrar uma página de erro
            $this->failAndLog($e, 'Erro ao carregar página de investimentos', 500);
        }
    }
}
