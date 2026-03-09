<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;

class PlataformaCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period, ?int $userId = null): array
    {
        return [
            'plataforma' => [
                'nome'      => 'Lukrato',
                'descricao' => 'Sistema de gestão financeira pessoal completo',
                'planos'    => 'Gratuito, Pro (R$14,90/mês)',
                'funcionalidades' => [
                    'Lançamentos (receitas, despesas, transferências) com categorização',
                    'Contas bancárias com saldos e instituições financeiras',
                    'Cartões de crédito com limites, faturas e controle de parcelas',
                    'Parcelamentos com acompanhamento de parcelas',
                    'Recorrências (diária, semanal, mensal, anual) com geração automática',
                    'Categorias e subcategorias personalizáveis por usuário',
                    'Metas financeiras (economia, investimento, compra, viagem, etc.)',
                    'Orçamentos mensais por categoria com rollover',
                    'Dashboard com provisão, projeções e insights financeiros',
                    'Comparativos (mensal, anual, categorias, evolução)',
                    'Gamificação (pontos, 15 níveis, conquistas, streaks)',
                    'Sistema de indicações com recompensas',
                    'Notificações (email + in-app)',
                    'Lembretes de vencimento (lançamentos e faturas)',
                    'Campanhas de mensagens para usuários',
                    'Blog financeiro com categorias',
                    'Cupons de desconto com uso limitado',
                    'Assinaturas com gateway Asaas (boleto, pix, cartão)',
                    'Webhooks de pagamento automáticos',
                    'Login com Google OAuth',
                    'Verificação de email obrigatória',
                    'Painel administrativo completo (SysAdmin)',
                    'Logs de erro e monitoramento do sistema',
                    'Assistente IA com acesso total aos dados',
                ],
            ],
        ];
    }
}
