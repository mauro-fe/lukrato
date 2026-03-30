<?php

declare(strict_types=1);

namespace Application\Services\Financeiro;

use Application\Models\Lancamento;
use Application\Models\Meta;
use Application\Repositories\MetaRepository;
use Application\Services\Plan\PlanLimitService;

class MetaService
{
    private MetaRepository $repo;
    private PlanLimitService $planLimit;
    private MetaProgressService $progressService;

    public function __construct(
        ?MetaRepository $repo = null,
        ?PlanLimitService $planLimit = null,
        ?MetaProgressService $progressService = null
    ) {
        $this->repo = $repo ?? new MetaRepository();
        $this->planLimit = $planLimit ?? new PlanLimitService();
        $this->progressService = $progressService ?? new MetaProgressService();
    }

    public function listar(int $userId, ?string $status = null): array
    {
        $metas = $this->repo->findByUser($userId, $status);

        return $metas->map(function (Meta $meta) use ($userId): array {
            $atualizada = $this->progressService->recalculateMeta($userId, (int) $meta->id) ?? $meta->fresh();
            return $atualizada->toApiArray();
        })->toArray();
    }

    public function obter(int $userId, int $id): ?array
    {
        $meta = $this->repo->findByIdAndUser($id, $userId);
        if (!$meta) {
            return null;
        }

        $atualizada = $this->progressService->recalculateMeta($userId, (int) $meta->id) ?? $meta->fresh();

        return $atualizada->toApiArray();
    }

    public function criar(int $userId, array $data): array
    {
        $this->planLimit->assertCanCreateMeta($userId);

        $valorInicial = $this->extractValorAlocado($data);
        $valorAlvo = round((float) ($data['valor_alvo'] ?? 0), 2);

        $payload = [
            'user_id' => $userId,
            'titulo' => $data['titulo'] ?? '',
            'descricao' => $data['descricao'] ?? null,
            'tipo' => $data['tipo'] ?? Meta::TIPO_ECONOMIA,
            'modelo' => $data['modelo'] ?? Meta::MODELO_RESERVA,
            'valor_alvo' => $valorAlvo,
            'valor_alocado' => $valorInicial,
            'valor_aporte_manual' => $valorInicial,
            'valor_realizado' => 0,
            'valor_atual' => $valorInicial,
            'data_inicio' => $data['data_inicio'] ?? date('Y-m-d'),
            'data_prazo' => $data['data_prazo'] ?? null,
            'icone' => $data['icone'] ?? 'fa-bullseye',
            'cor' => $data['cor'] ?? '#6366f1',
            'conta_id' => null,
            'prioridade' => $data['prioridade'] ?? Meta::PRIORIDADE_MEDIA,
            'status' => $valorInicial >= $valorAlvo ? Meta::STATUS_CONCLUIDA : Meta::STATUS_ATIVA,
        ];

        $meta = $this->repo->createForUser($userId, $payload);
        $atualizada = $this->progressService->recalculateMeta($userId, (int) $meta->id, true) ?? $meta->fresh();

        return $atualizada->toApiArray();
    }

    public function atualizar(int $userId, int $id, array $data): ?array
    {
        $meta = $this->repo->findByIdAndUser($id, $userId);
        if (!$meta) {
            return null;
        }

        $updateData = [];
        foreach (['titulo', 'descricao', 'tipo', 'modelo', 'valor_alvo', 'data_inicio', 'data_prazo', 'icone', 'cor', 'prioridade', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $meta->update($updateData);
        }

        $meta = $this->progressService->recalculateMeta($userId, $id) ?? $meta->fresh();

        return $meta?->toApiArray();
    }

    public function adicionarAporte(int $userId, int $id, float $valor): ?array
    {
        throw new \DomainException('Aporte manual em meta foi descontinuado. Use um lancamento vinculado a meta.');
    }

    public function remover(int $userId, int $id): bool
    {
        return $this->repo->deleteForUser($id, $userId);
    }

    public function resumo(int $userId): array
    {
        $todasMetas = $this->repo->findByUser($userId);
        foreach ($todasMetas as $meta) {
            $this->progressService->recalculateMeta($userId, (int) $meta->id);
        }

        $ativas = $this->repo->findByUser($userId, Meta::STATUS_ATIVA);

        $totalAlvo = $ativas->sum('valor_alvo');
        $totalAtual = $ativas->sum(function (Meta $meta): float {
            if (($meta->modelo ?? Meta::MODELO_RESERVA) === Meta::MODELO_REALIZACAO) {
                return (float) $meta->valor_alocado + (float) ($meta->valor_realizado ?? 0);
            }

            return (float) $meta->valor_alocado;
        });
        $atrasadas = $ativas->filter(fn(Meta $meta) => $meta->isAtrasada())->count();
        $proximaConcluir = $ativas->sortByDesc('progresso')->first();

        return [
            'total_metas' => $ativas->count(),
            'total_alvo' => round($totalAlvo, 2),
            'total_atual' => round($totalAtual, 2),
            'progresso_geral' => $totalAlvo > 0 ? round(($totalAtual / $totalAlvo) * 100, 1) : 0,
            'atrasadas' => $atrasadas,
            'proxima_concluir' => $proximaConcluir?->toApiArray(),
        ];
    }

    public function getTemplates(): array
    {
        return [
            [
                'titulo' => 'Reserva de Emergencia',
                'descricao' => '6 meses de despesas guardados para imprevistos - a base de qualquer planejamento',
                'tipo' => Meta::TIPO_EMERGENCIA,
                'icone' => 'shield',
                'cor' => '#10b981',
                'sugestao' => 'O ideal e ter 6x seus gastos mensais medios',
            ],
            [
                'titulo' => 'Trocar de Celular',
                'descricao' => 'Junte para comprar a vista e evitar juros de parcelamento',
                'tipo' => Meta::TIPO_COMPRA,
                'icone' => 'smartphone',
                'cor' => '#8b5cf6',
                'valor_sugerido' => 3000,
                'sugestao' => null,
            ],
            [
                'titulo' => 'Viagem de Ferias',
                'descricao' => 'Planeje com antecedencia e viaje sem apertar o orcamento',
                'tipo' => Meta::TIPO_VIAGEM,
                'icone' => 'plane',
                'cor' => '#3b82f6',
                'valor_sugerido' => 5000,
                'sugestao' => null,
            ],
            [
                'titulo' => 'Quitar Divida',
                'descricao' => 'Livre-se dos juros e reconquiste sua liberdade financeira',
                'tipo' => Meta::TIPO_QUITACAO,
                'icone' => 'hand-coins',
                'cor' => '#ef4444',
                'sugestao' => null,
            ],
            [
                'titulo' => 'Entrada do Carro',
                'descricao' => 'Quanto maior a entrada, menores as parcelas e os juros',
                'tipo' => Meta::TIPO_VEICULO,
                'icone' => 'car',
                'cor' => '#f59e0b',
                'valor_sugerido' => 15000,
                'sugestao' => null,
            ],
            [
                'titulo' => 'Entrada do Imovel',
                'descricao' => 'Junte para a entrada e reduza o valor financiado',
                'tipo' => Meta::TIPO_MORADIA,
                'icone' => 'house',
                'cor' => '#14b8a6',
                'valor_sugerido' => 50000,
                'sugestao' => null,
            ],
            [
                'titulo' => 'Curso / Faculdade',
                'descricao' => 'Invista em voce - educacao e o melhor retorno a longo prazo',
                'tipo' => Meta::TIPO_EDUCACAO,
                'icone' => 'graduation-cap',
                'cor' => '#6366f1',
                'valor_sugerido' => 5000,
                'sugestao' => null,
            ],
            [
                'titulo' => 'Montar Negocio Proprio',
                'descricao' => 'Capital inicial para tirar sua ideia do papel',
                'tipo' => Meta::TIPO_NEGOCIO,
                'icone' => 'store',
                'cor' => '#ec4899',
                'valor_sugerido' => 10000,
                'sugestao' => null,
            ],
            [
                'titulo' => 'Fundo para os Filhos',
                'descricao' => 'Garanta o futuro dos seus filhos com planejamento desde cedo',
                'tipo' => Meta::TIPO_ECONOMIA,
                'icone' => 'baby',
                'cor' => '#3b82f6',
                'valor_sugerido' => 20000,
                'sugestao' => null,
            ],
        ];
    }

    public function sugerirReservaEmergencia(int $userId, int $mesesCobertura = 6): float
    {
        $mediaGastos = $this->calcularMediaGastosMensais($userId, 3);
        return round($mediaGastos * $mesesCobertura, 2);
    }

    private function calcularMediaGastosMensais(int $userId, int $meses = 3): float
    {
        $total = 0;
        $mesesComDados = 0;

        for ($i = 1; $i <= $meses; $i++) {
            $date = new \DateTime();
            $date->modify("-{$i} months");
            $mes = (int) $date->format('m');
            $ano = (int) $date->format('Y');

            $gasto = (float) Lancamento::where('user_id', $userId)
                ->where('tipo', 'despesa')
                ->where('eh_transferencia', 0)
                ->where(function ($query) use ($mes, $ano) {
                    $query->where(function ($withCompetencia) use ($mes, $ano) {
                        $withCompetencia->whereNotNull('data_competencia')
                            ->whereYear('data_competencia', $ano)
                            ->whereMonth('data_competencia', $mes);
                    })->orWhere(function ($withoutCompetencia) use ($mes, $ano) {
                        $withoutCompetencia->whereNull('data_competencia')
                            ->whereYear('data', $ano)
                            ->whereMonth('data', $mes);
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

    private function extractValorAlocado(array $data, bool $defaultZero = true): ?float
    {
        $raw = $data['valor_alocado'] ?? $data['valor_atual'] ?? null;
        if ($raw === null || $raw === '') {
            return $defaultZero ? 0.0 : null;
        }

        return round(max(0, (float) $raw), 2);
    }
}
