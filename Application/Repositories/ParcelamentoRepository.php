<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Models\Parcelamento;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para operações com parcelamentos.
 */
class ParcelamentoRepository extends BaseRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getModelClass(): string
    {
        return Parcelamento::class;
    }

    /**
     * Busca parcelamentos de um usuário específico.
     * 
     * @param int $usuarioId
     * @return Collection
     */
    public function findByUsuario(int $usuarioId): Collection
    {
        return $this->query()
            ->where('usuario_id', $usuarioId)
            ->with(['categoria', 'conta', 'lancamentos'])
            ->orderBy('data_criacao', 'desc')
            ->get();
    }

    /**
     * Busca parcelamentos ativos de um usuário.
     * 
     * @param int $usuarioId
     * @return Collection
     */
    public function findAtivos(int $usuarioId): Collection
    {
        return $this->query()
            ->where('usuario_id', $usuarioId)
            ->where('status', Parcelamento::STATUS_ATIVO)
            ->with(['categoria', 'conta'])
            ->orderBy('data_criacao', 'desc')
            ->get();
    }

    /**
     * Busca parcelamentos por status.
     * 
     * @param int $usuarioId
     * @param string $status
     * @return Collection
     */
    public function findByStatus(int $usuarioId, string $status): Collection
    {
        return $this->query()
            ->where('usuario_id', $usuarioId)
            ->where('status', $status)
            ->with(['categoria', 'conta'])
            ->orderBy('data_criacao', 'desc')
            ->get();
    }

    /**
     * Busca um parcelamento com todos os seus lançamentos.
     * 
     * @param int $id
     * @return Parcelamento|null
     */
    public function findWithLancamentos(int $id): ?Parcelamento
    {
        return $this->query()
            ->with(['lancamentos', 'categoria', 'conta'])
            ->find($id);
    }

    /**
     * Cria um novo parcelamento com suas parcelas.
     * 
     * @param array $data
     * @return Parcelamento
     */
    public function createWithParcelas(array $data): Parcelamento
    {
        $parcelamento = $this->create($data);

        // Gerar os lançamentos (parcelas)
        $valorParcela = $data['valor_total'] / $data['numero_parcelas'];
        $dataPrimeiraParcela = new \DateTime($data['data_criacao']);

        for ($i = 1; $i <= $data['numero_parcelas']; $i++) {
            $dataVencimento = clone $dataPrimeiraParcela;
            $dataVencimento->modify('+' . ($i - 1) . ' month');

            $parcelamento->lancamentos()->create([
                'user_id' => $data['usuario_id'],
                'tipo' => $data['tipo'] === 'entrada' ? 'receita' : 'despesa',
                'data' => $dataVencimento->format('Y-m-d'),
                'categoria_id' => $data['categoria_id'],
                'conta_id' => $data['conta_id'],
                'descricao' => $data['descricao'] . " (Parcela {$i}/{$data['numero_parcelas']})",
                'valor' => $valorParcela,
                'eh_transferencia' => false,
                'eh_saldo_inicial' => false,
                'numero_parcela' => $i,
                'pago' => false,
            ]);
        }

        return $parcelamento->fresh('lancamentos');
    }

    /**
     * Cancela um parcelamento e todas as suas parcelas não pagas.
     * 
     * @param int $id
     * @return bool
     */
    public function cancelar(int $id): bool
    {
        $parcelamento = $this->findWithLancamentos($id);

        if (!$parcelamento) {
            return false;
        }

        // Deletar parcelas não pagas
        $parcelamento->lancamentos()
            ->where('pago', false)
            ->delete();

        // Atualizar status
        $parcelamento->update(['status' => Parcelamento::STATUS_CANCELADO]);

        return true;
    }

    /**
     * Atualiza o contador de parcelas pagas.
     * 
     * @param int $id
     * @return void
     */
    public function atualizarParcelasPagas(int $id): void
    {
        $parcelamento = $this->findWithLancamentos($id);

        if (!$parcelamento) {
            return;
        }

        $parcelasPagas = $parcelamento->lancamentos()
            ->where('pago', true)
            ->count();

        $parcelamento->update(['parcelas_pagas' => $parcelasPagas]);

        // Se todas as parcelas foram pagas, marcar como concluído
        if ($parcelasPagas >= $parcelamento->numero_parcelas) {
            $parcelamento->update(['status' => Parcelamento::STATUS_CONCLUIDO]);
        }
    }
}
