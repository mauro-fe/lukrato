<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\InvestimentoService;
use Throwable;

class InvestimentosController extends BaseController
{
    private InvestimentoService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new InvestimentoService();
    }

    private function resolveUserId(): ?int
    {
        return $this->userId ?? ($_SESSION['usuario_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['auth']['id'] ?? null);
    }


    public function index(): void
    {
        try {
            $this->requireAuth();
            $userId = $this->resolveUserId();

            if ($userId === null) {
                Response::forbidden('Usuário não autenticado.');
                return;
            }

            $filters = ['order' => 'nome', 'dir' => 'asc'];

            $investments = $this->service->getInvestimentos($userId, $filters);

            $stats = $this->service->getStats($userId);

            $categories = $this->service->getCategorias()
                ->map(fn($c) => [
                    'id'   => (int)$c->id,
                    'nome' => (string)$c->nome,
                    'cor'  => $c->cor ?: '#6c757d',
                ])
                ->toArray();

            $byCategory = [];
            foreach ($investments as $i) {
                $catNome = 'Sem Categoria';
                $catColor = '#6c757d';
                foreach ($categories as $c) {
                    if ($c['id'] === $i['categoria_id']) {
                        $catNome = $c['nome'];
                        $catColor = $c['cor'] ?? '#6c757d';
                        break;
                    }
                }

                if (!isset($byCategory[$catNome])) {
                    $byCategory[$catNome] = ['category' => $catNome, 'value' => 0.0, 'color' => $catColor];
                }
                $byCategory[$catNome]['value'] += $i['valor_atual'];
            }
            $statsByCategory = array_values($byCategory);

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

            $this->render('admin/investimentos/index', $data, 'admin/partials/header', 'admin/partials/footer');
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Erro ao carregar página de investimentos', 500);
        }
    }
}