<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Enums\LancamentoTipo;
use Application\Models\OrcamentoCategoria;
use Application\Models\Lancamento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends BaseRepository<OrcamentoCategoria>
 */
class OrcamentoRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return OrcamentoCategoria::class;
    }

    /**
     * Busca orçamentos do usuário para um mês/ano
     *
     * @return Collection<int, OrcamentoCategoria>
     */
    public function findByUserAndMonth(int $userId, int $mes, int $ano): Collection
    {
        return $this->query()
            ->forUser($userId)
            ->doMes($mes, $ano)
            ->with('categoria')
            ->get();
    }

    /**
     * Busca orçamento por ID e user_id
     */
    public function findByIdAndUser(int $id, int $userId): ?Model
    {
        return $this->query()->where('id', $id)->where('user_id', $userId)->first();
    }

    /**
     * Busca orçamento de uma categoria específica no mês
     */
    public function findByCategoriaAndMonth(int $userId, int $categoriaId, int $mes, int $ano): ?Model
    {
        return $this->query()
            ->forUser($userId)
            ->where('categoria_id', $categoriaId)
            ->doMes($mes, $ano)
            ->first();
    }

    public function existsByCategoriaAndMonth(int $userId, int $categoriaId, int $mes, int $ano): bool
    {
        return $this->query()
            ->forUser($userId)
            ->where('categoria_id', $categoriaId)
            ->doMes($mes, $ano)
            ->exists();
    }

    /**
     * @param int[] $categoriaIds
     * @return int[]
     */
    public function getExistingCategoriaIdsForMonth(int $userId, int $mes, int $ano, array $categoriaIds): array
    {
        if ($categoriaIds === []) {
            return [];
        }

        return $this->query()
            ->forUser($userId)
            ->doMes($mes, $ano)
            ->whereIn('categoria_id', $categoriaIds)
            ->pluck('categoria_id')
            ->map(static fn(mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * Cria ou atualiza orçamento (upsert)
     */
    public function upsert(int $userId, int $categoriaId, int $mes, int $ano, array $data): Model
    {
        $existing = $this->findByCategoriaAndMonth($userId, $categoriaId, $mes, $ano);

        if ($existing) {
            $existing->update($data);
            return $existing->fresh();
        }

        return $this->create(array_merge($data, [
            'user_id'      => $userId,
            'categoria_id' => $categoriaId,
            'mes'          => $mes,
            'ano'          => $ano,
        ]));
    }

    /**
     * Remove orçamento do usuário
     */
    public function deleteForUser(int $id, int $userId): bool
    {
        $orc = $this->findByIdAndUser($id, $userId);
        if (!$orc) {
            return false;
        }

        return $orc->delete();
    }

    /**
     * Calcula o gasto real de uma categoria em um mês
     */
    public function getGastoReal(int $userId, int $categoriaId, int $mes, int $ano): float
    {
        return (float) $this->buildCategoriaDespesaQuery($userId, $categoriaId)
            ->whereYear('data_competencia', $ano)
            ->whereMonth('data_competencia', $mes)
            ->sum('valor');
    }

    /**
     * Calcula gasto real usando data quando data_competencia é null
     */
    public function getGastoRealComFallback(int $userId, int $categoriaId, int $mes, int $ano): float
    {
        $baseQuery = $this->buildCategoriaDespesaQuery($userId, $categoriaId);

        $comCompetencia = (float) (clone $baseQuery)
            ->whereNotNull('data_competencia')
            ->whereYear('data_competencia', $ano)
            ->whereMonth('data_competencia', $mes)
            ->sum('valor');

        $semCompetencia = (float) (clone $baseQuery)
            ->whereNull('data_competencia')
            ->whereYear('data', $ano)
            ->whereMonth('data', $mes)
            ->sum('valor');

        return $comCompetencia + $semCompetencia;
    }

    /**
     * Calcula a média de gastos dos últimos N meses para uma categoria
     */
    public function getMediaGastos(int $userId, int $categoriaId, int $meses = 3): float
    {
        $total = 0;
        $mesesComDados = 0;

        for ($i = 1; $i <= $meses; $i++) {
            $date = new \DateTime();
            $date->modify("-{$i} months");
            $m = (int) $date->format('m');
            $y = (int) $date->format('Y');

            $gasto = $this->getGastoRealComFallback($userId, $categoriaId, $m, $y);
            if ($gasto > 0) {
                $total += $gasto;
                $mesesComDados++;
            }
        }

        return $mesesComDados > 0 ? round($total / $mesesComDados, 2) : 0;
    }

    /**
     * Copiar orçamentos de um mês para outro
     */
    public function copiarMes(int $userId, int $mesOrigem, int $anoOrigem, int $mesDestino, int $anoDestino): int
    {
        $orcamentos = $this->findByUserAndMonth($userId, $mesOrigem, $anoOrigem);
        $count = 0;

        foreach ($orcamentos as $orc) {
            $existing = $this->findByCategoriaAndMonth($userId, $orc->categoria_id, $mesDestino, $anoDestino);
            if (!$existing) {
                $this->create([
                    'user_id'       => $userId,
                    'categoria_id'  => $orc->categoria_id,
                    'valor_limite'  => $orc->valor_limite,
                    'mes'           => $mesDestino,
                    'ano'           => $anoDestino,
                    'rollover'      => $orc->rollover,
                    'alerta_80'     => $orc->alerta_80,
                    'alerta_100'    => $orc->alerta_100,
                ]);
                $count++;
            }
        }

        return $count;
    }

    private function buildCategoriaDespesaQuery(int $userId, int $categoriaId): Builder
    {
        return Lancamento::query()
            ->where('user_id', $userId)
            ->where('categoria_id', $categoriaId)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0);
    }
}
