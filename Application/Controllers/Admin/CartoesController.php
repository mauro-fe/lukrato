<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class CartoesController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/cartoes/index',
            [
                'pageTitle' => 'Cartões de Crédito',
                'subTitle' => 'Gerencie seus cartões e controle seus gastos',
            ]
        );
    }

    public function archived(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/cartoes/arquivadas',
            [
                'pageTitle' => 'Cartões Arquivados',
                'subTitle' => 'Gerencie seus cartões arquivados',
            ]
        );
    }

    public function show(int $id): Response
    {
        $this->requireUserId();
        $monthInput = (string) $this->getQuery('mes', '');
        $normalizedMonth = $this->normalizeYearMonth($monthInput, date('Y-m'));
        $origin = strtolower(trim((string) $this->getQuery('origem', '')));
        $fromReports = $origin === 'relatorios';

        return $this->renderAdminResponse(
            'admin/cartoes/show',
            [
                'pageTitle' => 'Detalhes do Cartão',
                'subTitle' => 'Acompanhe fatura, evolução e parcelamentos',
                'cartaoId' => $id,
                'currentMonth' => $normalizedMonth['month'],
                'backUrl' => BASE_URL . ($fromReports ? 'relatorios' : 'cartoes'),
                'backLabel' => $fromReports ? 'Voltar para relatórios' : 'Voltar para cartões',
            ]
        );
    }
}
