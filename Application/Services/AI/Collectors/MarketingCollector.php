<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\BlogPost;
use Application\Models\Cupom;
use Application\Models\Indicacao;
use Application\Models\MessageCampaign;
use Application\Models\Notification;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class MarketingCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period): array
    {
        return [
            'indicacoes'   => $this->indicacoes(),
            'notificacoes' => $this->notificacoes(),
            'campanhas'    => $this->campanhas(),
            'cupons'       => $this->cupons(),
            'blog'         => $this->blog(),
        ];
    }

    private function indicacoes(): array
    {
        $total     = Indicacao::count();
        $completas = Indicacao::where('status', 'completed')->count();

        return [
            'total'          => $total,
            'pendentes'      => Indicacao::where('status', 'pending')->count(),
            'completas'      => $completas,
            'expiradas'      => Indicacao::where('status', 'expired')->count(),
            'taxa_conversao' => $total > 0 ? round(($completas / $total) * 100, 1) : 0,
        ];
    }

    private function notificacoes(): array
    {
        $total = Notification::count();
        $lidas = Notification::where('is_read', 1)->count();

        $porTipo = Notification::select('type', DB::raw('COUNT(*) as qtd'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn($r) => [$r->type => (int) $r->qtd])
            ->toArray();

        return [
            'total'        => $total,
            'lidas'        => $lidas,
            'nao_lidas'    => $total - $lidas,
            'taxa_leitura' => $total > 0 ? round(($lidas / $total) * 100, 1) : 0,
            'por_tipo'     => $porTipo,
        ];
    }

    private function campanhas(): array
    {
        $ultima = MessageCampaign::where('status', 'sent')->orderByDesc('sent_at')->first();

        return [
            'total'           => MessageCampaign::count(),
            'enviadas'        => MessageCampaign::where('status', 'sent')->count(),
            'ultima_campanha' => $ultima ? [
                'titulo'          => $ultima->title,
                'enviada_em'      => $ultima->sent_at,
                'destinatarios'   => $ultima->total_recipients,
                'emails_enviados' => $ultima->emails_sent,
            ] : null,
        ];
    }

    private function cupons(): array
    {
        return [
            'total'      => Cupom::count(),
            'ativos'     => Cupom::where('ativo', 1)->count(),
            'total_usos' => (int) Cupom::sum('uso_atual'),
        ];
    }

    private function blog(): array
    {
        return [
            'total'      => BlogPost::count(),
            'publicados' => BlogPost::where('status', 'published')->count(),
            'rascunhos'  => BlogPost::where('status', 'draft')->count(),
        ];
    }
}
