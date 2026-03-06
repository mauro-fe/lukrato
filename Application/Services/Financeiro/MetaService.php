<?php

declare(strict_types=1);

namespace Application\Services\Financeiro;

use Application\Models\Meta;
use Application\Models\Lancamento;
use Application\Repositories\MetaRepository;
use Application\Services\Conta\ContaService;
use Application\Services\Plan\PlanLimitService;

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
    private ContaService $contaService;

    public function __construct()
    {
        $this->repo = new MetaRepository();
        $this->planLimit = new PlanLimitService();
        $this->contaService = new ContaService();
    }

    /**
     * Listar metas do usuário com progresso calculado
     */
    public function listar(int $userId, ?string $status = null): array
    {
        $metas = $this->repo->findByUser($userId, $status);
        return $metas->map(fn(Meta $m) => $this->syncContaSaldo($m, $userId)->toApiArray())->toArray();
    }

    /**
     * Obter uma meta específica
     */
    public function obter(int $userId, int $id): ?array
    {
        $meta = $this->repo->findByIdAndUser($id, $userId);
        if (!$meta) return null;
        return $this->syncContaSaldo($meta, $userId)->toApiArray();
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

        // Se vinculada a conta, inicializar valor_atual com saldo atual da conta
        if (!empty($data['conta_id'])) {
            $data['valor_atual'] = $this->contaService->getSaldoAtual((int) $data['conta_id'], $userId);
        }

        $meta = $this->repo->createForUser($userId, $data);
        return $this->syncContaSaldo($meta->fresh(), $userId)->toApiArray();
    }

    /**
     * Atualizar meta
     */
    public function atualizar(int $userId, int $id, array $data): ?array
    {
        $meta = $this->repo->findByIdAndUser($id, $userId);
        if (!$meta) return null;

        $meta->update($data);

        // Auto-concluir se atingiu o alvo (apenas metas sem conta vinculada)
        if (!$meta->conta_id && $meta->valor_atual >= $meta->valor_alvo && $meta->status === Meta::STATUS_ATIVA) {
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

        if ($meta->conta_id) {
            throw new \DomainException('Esta meta está vinculada a uma conta e o progresso é calculado automaticamente.');
        }

        $novoValor = $meta->valor_atual + $valor;
        $this->repo->atualizarValor($id, $userId, $novoValor);

        $metaAtualizada = $this->repo->findByIdAndUser($id, $userId);

        // Auto-concluir se atingiu o alvo via aporte
        if ($metaAtualizada && $novoValor >= $metaAtualizada->valor_alvo && $metaAtualizada->status === Meta::STATUS_ATIVA) {
            $metaAtualizada->update(['status' => Meta::STATUS_CONCLUIDA]);
        }

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
        // Sincronizar saldos de todas as metas vinculadas a contas antes de calcular
        $todasMetas = $this->repo->findByUser($userId);
        foreach ($todasMetas as $meta) {
            $this->syncContaSaldo($meta, $userId);
        }

        // Recarregar após sync (status pode ter mudado)
        $ativas = $this->repo->findByUser($userId, Meta::STATUS_ATIVA);

        $totalAlvo = $ativas->sum('valor_alvo');
        $totalAtual = $ativas->sum('valor_atual');
        $atrasadas = $ativas->filter(fn(Meta $m) => $m->isAtrasada())->count();
        $proximaConcluir = $ativas->sortByDesc('progresso')->first();

        return [
            'total_metas'       => $ativas->count(),
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
                'descricao' => '6 meses de despesas guardados para imprevistos — a base de qualquer planejamento',
                'tipo'      => Meta::TIPO_EMERGENCIA,
                'icone'     => 'shield',
                'cor'       => '#10b981',
                'sugestao'  => 'O ideal é ter 6x seus gastos mensais médios',
            ],
            [
                'titulo'    => 'Trocar de Celular',
                'descricao' => 'Junte para comprar à vista e evitar juros de parcelamento',
                'tipo'      => Meta::TIPO_COMPRA,
                'icone'     => 'smartphone',
                'cor'       => '#8b5cf6',
                'valor_sugerido' => 3000,
                'sugestao'  => null,
            ],
            [
                'titulo'    => 'Viagem de Férias',
                'descricao' => 'Planeje com antecedência e viaje sem apertar o orçamento',
                'tipo'      => Meta::TIPO_VIAGEM,
                'icone'     => 'plane',
                'cor'       => '#3b82f6',
                'valor_sugerido' => 5000,
                'sugestao'  => null,
            ],
            [
                'titulo'    => 'Quitar Dívida',
                'descricao' => 'Livre-se dos juros e reconquiste sua liberdade financeira',
                'tipo'      => Meta::TIPO_QUITACAO,
                'icone'     => 'hand-coins',
                'cor'       => '#ef4444',
                'sugestao'  => null,
            ],
            [
                'titulo'    => 'Entrada do Carro',
                'descricao' => 'Quanto maior a entrada, menores as parcelas e os juros',
                'tipo'      => Meta::TIPO_VEICULO,
                'icone'     => 'car',
                'cor'       => '#f59e0b',
                'valor_sugerido' => 15000,
                'sugestao'  => null,
            ],
            [
                'titulo'    => 'Entrada do Imóvel',
                'descricao' => 'Junte para a entrada e reduza o valor financiado',
                'tipo'      => Meta::TIPO_MORADIA,
                'icone'     => 'house',
                'cor'       => '#14b8a6',
                'valor_sugerido' => 50000,
                'sugestao'  => null,
            ],
            [
                'titulo'    => 'Curso / Faculdade',
                'descricao' => 'Invista em você — educação é o melhor retorno a longo prazo',
                'tipo'      => Meta::TIPO_EDUCACAO,
                'icone'     => 'graduation-cap',
                'cor'       => '#6366f1',
                'valor_sugerido' => 5000,
                'sugestao'  => null,
            ],
            [
                'titulo'    => 'Montar Negócio Próprio',
                'descricao' => 'Capital inicial para tirar sua ideia do papel',
                'tipo'      => Meta::TIPO_NEGOCIO,
                'icone'     => 'store',
                'cor'       => '#ec4899',
                'valor_sugerido' => 10000,
                'sugestao'  => null,
            ],
            [
                'titulo'    => 'Primeiro Milhão',
                'descricao' => 'A jornada começa com o primeiro passo — e com disciplina',
                'tipo'      => Meta::TIPO_INVESTIMENTO,
                'icone'     => 'gem',
                'cor'       => '#f59e0b',
                'valor_sugerido' => 1000000,
                'sugestao'  => null,
            ],
            [
                'titulo'    => 'Fundo para os Filhos',
                'descricao' => 'Garanta o futuro dos seus filhos com planejamento desde cedo',
                'tipo'      => Meta::TIPO_ECONOMIA,
                'icone'     => 'baby',
                'cor'       => '#3b82f6',
                'valor_sugerido' => 20000,
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
    /**
     * Sincroniza valor_atual da meta com o saldo atual da conta vinculada.
     * Atualiza no banco e retorna a meta (possivelmente modificada).
     */
    private function syncContaSaldo(Meta $meta, int $userId): Meta
    {
        if (!$meta->conta_id) return $meta;

        $saldo = $this->contaService->getSaldoAtual($meta->conta_id, $userId);

        // Apenas persiste se mudou (evita writes desnecessários)
        if (abs($meta->valor_atual - $saldo) > 0.001) {
            $meta->valor_atual = $saldo;
            $meta->save();

            // Auto-concluir se atingiu o alvo
            if ($saldo >= $meta->valor_alvo && $meta->status === Meta::STATUS_ATIVA) {
                $meta->update(['status' => Meta::STATUS_CONCLUIDA]);
            }

            // Reverter para ativa se saldo caiu abaixo do alvo (meta vinculada a conta)
            if ($saldo < $meta->valor_alvo && $meta->status === Meta::STATUS_CONCLUIDA) {
                $meta->update(['status' => Meta::STATUS_ATIVA]);
            }
        }

        return $meta;
    }

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
