<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Models\Meta;
use Application\Models\Lancamento;
use Application\Repositories\MetaRepository;

/**
 * Serviço de Metas Financeiras
 * 
 * Gerencia metas com automações: auto-sugestão de aportes,
 * auto-conclusão, templates pré-configurados.
 */
class MetaService
{
    private MetaRepository $repo;
    private PlanLimitService $planLimit;

    public function __construct()
    {
        $this->repo = new MetaRepository();
        $this->planLimit = new PlanLimitService();
    }

    /**
     * Listar metas do usuário com progresso calculado
     */
    public function listar(int $userId, ?string $status = null): array
    {
        $metas = $this->repo->findByUser($userId, $status);
        return $metas->map(fn(Meta $m) => $m->toApiArray())->toArray();
    }

    /**
     * Obter uma meta específica
     */
    public function obter(int $userId, int $id): ?array
    {
        $meta = $this->repo->findByIdAndUser($id, $userId);
        if (!$meta) return null;
        return $meta->toApiArray();
    }

    /**
     * Criar nova meta (com validação de limite do plano)
     *
     * @throws \DomainException
     */
    public function criar(int $userId, array $data): array
    {
        $this->planLimit->assertCanCreateMeta($userId);

        $data['user_id'] = $userId;
        $data['data_inicio'] = $data['data_inicio'] ?? date('Y-m-d');
        $data['status'] = Meta::STATUS_ATIVA;

        $meta = $this->repo->createForUser($userId, $data);
        return $meta->fresh()->toApiArray();
    }

    /**
     * Atualizar meta
     */
    public function atualizar(int $userId, int $id, array $data): ?array
    {
        $meta = $this->repo->findByIdAndUser($id, $userId);
        if (!$meta) return null;

        $meta->update($data);

        // Auto-concluir se atingiu o alvo
        if ($meta->valor_atual >= $meta->valor_alvo && $meta->status === Meta::STATUS_ATIVA) {
            $meta->update(['status' => Meta::STATUS_CONCLUIDA]);
        }

        return $meta->fresh()->toApiArray();
    }

    /**
     * Adicionar aporte (incrementa valor_atual)
     */
    public function adicionarAporte(int $userId, int $id, float $valor): ?array
    {
        $meta = $this->repo->findByIdAndUser($id, $userId);
        if (!$meta) return null;

        $novoValor = $meta->valor_atual + $valor;
        $this->repo->atualizarValor($id, $userId, $novoValor);

        return $this->repo->findByIdAndUser($id, $userId)->toApiArray();
    }

    /**
     * Remover meta
     */
    public function remover(int $userId, int $id): bool
    {
        return $this->repo->deleteForUser($id, $userId);
    }

    /**
     * Resumo das metas ativas para o dashboard
     */
    public function resumo(int $userId): array
    {
        $metas = $this->repo->findByUser($userId, Meta::STATUS_ATIVA);

        $totalAlvo = $metas->sum('valor_alvo');
        $totalAtual = $metas->sum('valor_atual');
        $atrasadas = $metas->filter(fn(Meta $m) => $m->isAtrasada())->count();
        $proximaConcluir = $metas->sortByDesc('progresso')->first();

        return [
            'total_metas'       => $metas->count(),
            'total_alvo'        => round($totalAlvo, 2),
            'total_atual'       => round($totalAtual, 2),
            'progresso_geral'   => $totalAlvo > 0 ? round(($totalAtual / $totalAlvo) * 100, 1) : 0,
            'atrasadas'         => $atrasadas,
            'proxima_concluir'  => $proximaConcluir?->toApiArray(),
        ];
    }

    /**
     * Templates de metas pré-configurados (one-click goals)
     */
    public function getTemplates(): array
    {
        return [
            [
                'titulo'    => 'Reserva de Emergência',
                'descricao' => 'Economize o equivalente a 6 meses de despesas para emergências',
                'tipo'      => Meta::TIPO_EMERGENCIA,
                'icone'     => 'fa-shield-halved',
                'cor'       => '#10b981',
                'sugestao'  => 'O ideal é ter 6x seus gastos mensais médios',
            ],
            [
                'titulo'    => 'Viagem dos Sonhos',
                'descricao' => 'Junte dinheiro para aquela viagem que você sempre quis',
                'tipo'      => Meta::TIPO_COMPRA,
                'icone'     => 'fa-plane',
                'cor'       => '#3b82f6',
                'sugestao'  => null,
            ],
            [
                'titulo'    => 'Quitar Dívida',
                'descricao' => 'Livre-se das dívidas e conquiste sua liberdade financeira',
                'tipo'      => Meta::TIPO_QUITACAO,
                'icone'     => 'fa-hand-holding-dollar',
                'cor'       => '#ef4444',
                'sugestao'  => null,
            ],
            [
                'titulo'    => 'Compra Planejada',
                'descricao' => 'Economize para comprar algo específico sem parcelar',
                'tipo'      => Meta::TIPO_COMPRA,
                'icone'     => 'fa-cart-shopping',
                'cor'       => '#8b5cf6',
                'sugestao'  => null,
            ],
            [
                'titulo'    => 'Investir Mais',
                'descricao' => 'Acumule capital para investir e fazer seu dinheiro trabalhar',
                'tipo'      => Meta::TIPO_INVESTIMENTO,
                'icone'     => 'fa-chart-line',
                'cor'       => '#f59e0b',
                'sugestao'  => null,
            ],
        ];
    }

    /**
     * Sugestão automática de valor para reserva de emergência
     * baseada na média de gastos mensais do usuário
     */
    public function sugerirReservaEmergencia(int $userId, int $mesesCobertura = 6): float
    {
        $mediaGastos = $this->calcularMediaGastosMensais($userId, 3);
        return round($mediaGastos * $mesesCobertura, 2);
    }

    /**
     * Calcula média de gastos mensais do usuário
     */
    private function calcularMediaGastosMensais(int $userId, int $meses = 3): float
    {
        $total = 0;
        $mesesComDados = 0;

        for ($i = 1; $i <= $meses; $i++) {
            $date = new \DateTime();
            $date->modify("-{$i} months");
            $m = (int) $date->format('m');
            $y = (int) $date->format('Y');

            $gasto = (float) Lancamento::where('user_id', $userId)
                ->where('tipo', 'despesa')
                ->where('eh_transferencia', 0)
                ->where(function ($q) use ($m, $y) {
                    $q->where(function ($q2) use ($m, $y) {
                        $q2->whereNotNull('data_competencia')
                            ->whereYear('data_competencia', $y)
                            ->whereMonth('data_competencia', $m);
                    })->orWhere(function ($q2) use ($m, $y) {
                        $q2->whereNull('data_competencia')
                            ->whereYear('data', $y)
                            ->whereMonth('data', $m);
                    });
                })
                ->sum('valor');

            if ($gasto > 0) {
                $total += $gasto;
                $mesesComDados++;
            }
        }

        return $mesesComDados > 0 ? round($total / $mesesComDados, 2) : 0;
    }
}
