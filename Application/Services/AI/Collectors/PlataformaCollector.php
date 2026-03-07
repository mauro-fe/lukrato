<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;

class PlataformaCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period): array
    {
        return [
            'plataforma' => [
                'nome'      => 'Lukrato',
                'descricao' => 'Sistema de gestão financeira pessoal',
                'funcionalidades' => [
                    'Lançamentos (receitas, despesas, transferências)',
                    'Contas bancárias com saldos',
                    'Cartões de crédito com limites e faturas',
                    'Parcelamentos',
                    'Recorrências (diária, semanal, mensal, anual)',
                    'Categorias e subcategorias personalizáveis',
                    'Metas financeiras (economia, investimento, compra, etc.)',
                    'Orçamentos mensais por categoria com rollover',
                    'Dashboard com provisão e projeções',
                    'Insights financeiros automáticos',
                    'Comparativos (mensal, anual, categorias, evolução)',
                    'Gamificação (pontos, níveis 1-15, conquistas, streaks)',
                    'Sistema de indicações com recompensas',
                    'Notificações (email + in-app)',
                    'Campanhas de mensagens para usuários',
                    'Blog financeiro',
                    'Cupons de desconto',
                    'Planos: Gratuito, Pro Standard, Pro Premium',
                    'Lembretes de vencimento (lançamentos e faturas)',
                ],
            ],
        ];
    }
}
