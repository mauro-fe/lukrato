<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Repositories\ParcelamentoRepository;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Services\LogService;
use Application\Enums\LogCategory;
use Application\Models\Lancamento;
use Application\Models\Parcelamento;

/**
 * Controller para gerenciar parcelamentos sem cartão de crédito.
 *
 * Parcelamento = grupo de N lancamentos mensais (parcelas) vinculados
 * a uma mesma compra/serviço, pagos pela conta bancária (não cartão).
 */
class ParcelamentosController
{
    private ParcelamentoRepository $parcelamentoRepo;
    private CategoriaRepository $categoriaRepo;
    private ContaRepository $contaRepo;

    public function __construct()
    {
        $this->parcelamentoRepo = new ParcelamentoRepository();
        $this->categoriaRepo = new CategoriaRepository();
        $this->contaRepo = new ContaRepository();
    }

    /**
     * Listar parcelamentos do usuário
     * GET /api/parcelamentos
     */
    public function index(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Não autenticado', 401);
            return;
        }

        $status = $_GET['status'] ?? null;
        $parcelamentos = $status
            ? $this->parcelamentoRepo->findByStatus($userId, $status)
            : $this->parcelamentoRepo->findByUsuario($userId);

        $result = $parcelamentos->map(function ($p) {
            $parcelas = $p->lancamentos->sortBy('data')->values();
            return [
                'id' => $p->id,
                'descricao' => $p->descricao,
                'valor_total' => (float)$p->valor_total,
                'valor_parcela' => $p->valor_parcela,
                'numero_parcelas' => (int)$p->numero_parcelas,
                'parcelas_pagas' => (int)$p->parcelas_pagas,
                'percentual_pago' => $p->percentual_pago,
                'status' => $p->status,
                'tipo' => $p->tipo,
                'categoria' => $p->categoria?->nome ?? null,
                'conta' => $p->conta?->nome ?? null,
                'data_criacao' => $p->data_criacao,
                'parcelas' => $parcelas->map(fn($l) => [
                    'id' => $l->id,
                    'numero_parcela' => $l->numero_parcela,
                    'data' => (string)$l->data,
                    'valor' => (float)$l->valor,
                    'pago' => (bool)$l->pago,
                    'descricao' => $l->descricao,
                ])->toArray(),
            ];
        });

        Response::success($result->toArray());
    }

    /**
     * Mostrar parcelamento específico
     * GET /api/parcelamentos/{id}
     */
    public function show(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Não autenticado', 401);
            return;
        }

        $p = $this->parcelamentoRepo->findWithLancamentos($id);
        if (!$p || (int)$p->user_id !== $userId) {
            Response::error('Parcelamento não encontrado', 404);
            return;
        }

        $parcelas = $p->lancamentos->sortBy('data')->values();

        Response::success([
            'id' => $p->id,
            'descricao' => $p->descricao,
            'valor_total' => (float)$p->valor_total,
            'valor_parcela' => $p->valor_parcela,
            'numero_parcelas' => (int)$p->numero_parcelas,
            'parcelas_pagas' => (int)$p->parcelas_pagas,
            'percentual_pago' => $p->percentual_pago,
            'status' => $p->status,
            'tipo' => $p->tipo,
            'categoria' => $p->categoria?->nome ?? null,
            'conta' => $p->conta?->nome ?? null,
            'data_criacao' => $p->data_criacao,
            'parcelas' => $parcelas->map(fn($l) => [
                'id' => $l->id,
                'numero_parcela' => $l->numero_parcela,
                'data' => (string)$l->data,
                'valor' => (float)$l->valor,
                'pago' => (bool)$l->pago,
                'descricao' => $l->descricao,
            ])->toArray(),
        ]);
    }

    /**
     * Criar novo parcelamento (sem cartão de crédito)
     * POST /api/parcelamentos
     *
     * Payload: { descricao, valor_total, numero_parcelas, categoria_id?, conta_id, tipo, data_criacao }
     */
    public function store(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Não autenticado', 401);
            return;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json ?: '', true);

        if (!$data) {
            Response::error('Dados inválidos', 400);
            return;
        }

        // Validações
        $errors = [];

        $descricao = trim($data['descricao'] ?? '');
        if ($descricao === '') {
            $errors['descricao'] = 'Descrição é obrigatória.';
        }

        $valorTotal = (float)($data['valor_total'] ?? 0);
        if ($valorTotal <= 0) {
            $errors['valor_total'] = 'Valor total deve ser maior que zero.';
        }

        $numeroParcelas = (int)($data['numero_parcelas'] ?? 0);
        if ($numeroParcelas < 2 || $numeroParcelas > 120) {
            $errors['numero_parcelas'] = 'Número de parcelas deve ser entre 2 e 120.';
        }

        $contaId = (int)($data['conta_id'] ?? 0);
        if ($contaId <= 0) {
            $errors['conta_id'] = 'Conta é obrigatória.';
        } elseif (!$this->contaRepo->belongsToUser($contaId, $userId)) {
            $errors['conta_id'] = 'Conta inválida.';
        }

        $categoriaId = null;
        if (!empty($data['categoria_id'])) {
            $categoriaId = (int)$data['categoria_id'];
            if (!$this->categoriaRepo->belongsToUser($categoriaId, $userId)) {
                $errors['categoria_id'] = 'Categoria inválida.';
            }
        }

        $tipo = strtolower(trim($data['tipo'] ?? 'despesa'));
        if (!in_array($tipo, ['receita', 'despesa'], true)) {
            $tipo = 'despesa';
        }

        $dataCriacao = $data['data_criacao'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataCriacao)) {
            $errors['data_criacao'] = 'Data inválida (YYYY-MM-DD).';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // Campos de lembrete (opcional)
        $lembrarAntesSegundos = null;
        $canalEmail = false;
        $canalInapp = false;
        if (!empty($data['lembrar_antes_segundos'])) {
            $lembrarAntesSegundos = (int)$data['lembrar_antes_segundos'];
            $canalEmail = (bool)($data['canal_email'] ?? false);
            $canalInapp = (bool)($data['canal_inapp'] ?? true);
        }

        try {
            $parcelamentoData = [
                'user_id' => $userId,
                'descricao' => mb_substr($descricao, 0, 190),
                'valor_total' => $valorTotal,
                'numero_parcelas' => $numeroParcelas,
                'parcelas_pagas' => 0,
                'categoria_id' => $categoriaId,
                'conta_id' => $contaId,
                'tipo' => ($tipo === 'receita') ? 'entrada' : 'saida',
                'status' => Parcelamento::STATUS_ATIVO,
                'data_criacao' => $dataCriacao,
            ];

            $parcelamento = Parcelamento::create($parcelamentoData);

            // Gerar parcelas (lancamentos)
            $valorParcela = round($valorTotal / $numeroParcelas, 2);
            $somaParcelas = $valorParcela * ($numeroParcelas - 1);
            $valorUltima = round($valorTotal - $somaParcelas, 2);

            $dataPrimeira = new \DateTime($dataCriacao);

            for ($i = 1; $i <= $numeroParcelas; $i++) {
                $dataVencimento = clone $dataPrimeira;
                if ($i > 1) {
                    $dataVencimento->modify('+' . ($i - 1) . ' months');
                }

                $valorDessa = ($i === $numeroParcelas) ? $valorUltima : $valorParcela;

                Lancamento::create([
                    'user_id' => $userId,
                    'tipo' => $tipo,
                    'data' => $dataVencimento->format('Y-m-d'),
                    'categoria_id' => $categoriaId,
                    'conta_id' => $contaId,
                    'descricao' => $descricao . " ({$i}/{$numeroParcelas})",
                    'valor' => $valorDessa,
                    'eh_transferencia' => false,
                    'eh_saldo_inicial' => false,
                    'parcelamento_id' => $parcelamento->id,
                    'numero_parcela' => $i,
                    'pago' => false,
                    'origem_tipo' => Lancamento::ORIGEM_PARCELAMENTO,
                    // Lembretes
                    'lembrar_antes_segundos' => $lembrarAntesSegundos,
                    'canal_email' => $canalEmail,
                    'canal_inapp' => $canalInapp,
                ]);
            }

            $parcelamento = $parcelamento->fresh('lancamentos');

            LogService::info('[PARCELAMENTO] Parcelamento criado', [
                'parcelamento_id' => $parcelamento->id,
                'user_id' => $userId,
                'parcelas' => $numeroParcelas,
                'valor_total' => $valorTotal,
            ]);

            Response::success([
                'id' => $parcelamento->id,
                'descricao' => $parcelamento->descricao,
                'valor_total' => (float)$parcelamento->valor_total,
                'numero_parcelas' => (int)$parcelamento->numero_parcelas,
                'total_criados' => $numeroParcelas,
                'message' => "Parcelamento criado: {$numeroParcelas} parcelas geradas",
            ], 201);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::LANCAMENTO, [
                'action' => 'create_parcelamento',
                'user_id' => $userId,
            ]);
            Response::error('Erro ao criar parcelamento: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Excluir parcelamento
     * DELETE /api/parcelamentos/{id}
     *
     * Comportamento:
     * - ?scope=all     → Exclui todas as parcelas (pagas e pendentes) + header
     * - ?scope=future  → Exclui apenas parcelas futuras não pagas
     * - Default        → Exclui apenas parcelas não pagas + cancela header
     */
    public function destroy(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Não autenticado', 401);
            return;
        }

        $scope = $_GET['scope'] ?? 'unpaid';

        $parcelamento = Parcelamento::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$parcelamento) {
            Response::error('Parcelamento não encontrado', 404);
            return;
        }

        try {
            $hoje = date('Y-m-d');

            if ($scope === 'all') {
                // Excluir TODAS as parcelas (pagas e não pagas)
                $excluidos = Lancamento::where('parcelamento_id', $id)
                    ->where('user_id', $userId)
                    ->delete();

                $parcelamento->delete();

                Response::success([
                    'message' => "Parcelamento excluído completamente ({$excluidos} parcelas removidas)",
                    'itens_excluidos' => $excluidos,
                ]);
            } elseif ($scope === 'future') {
                // Excluir apenas parcelas futuras não pagas
                $excluidos = Lancamento::where('parcelamento_id', $id)
                    ->where('user_id', $userId)
                    ->where('pago', false)
                    ->where('data', '>', $hoje)
                    ->delete();

                // Atualizar header
                $restantes = Lancamento::where('parcelamento_id', $id)->count();
                if ($restantes === 0) {
                    $parcelamento->delete();
                } else {
                    $parcelamento->numero_parcelas = $restantes;
                    $parcelamento->save();
                    $this->parcelamentoRepo->atualizarParcelasPagas($id);
                }

                Response::success([
                    'message' => "{$excluidos} parcelas futuras removidas",
                    'itens_excluidos' => $excluidos,
                ]);
            } else {
                // Default: excluir não pagas + cancelar
                $excluidos = Lancamento::where('parcelamento_id', $id)
                    ->where('user_id', $userId)
                    ->where('pago', false)
                    ->delete();

                $parcelamento->status = Parcelamento::STATUS_CANCELADO;
                $parcelamento->save();

                Response::success([
                    'message' => "Parcelamento cancelado ({$excluidos} parcelas pendentes removidas)",
                    'itens_excluidos' => $excluidos,
                ]);
            }
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::LANCAMENTO, [
                'action' => 'delete_parcelamento',
                'parcelamento_id' => $id,
            ]);
            Response::error('Erro ao excluir parcelamento', 500);
        }
    }
}
