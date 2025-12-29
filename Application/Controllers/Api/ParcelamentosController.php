<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\ParcelamentoService;
use Application\Models\Parcelamento;
use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Controller para gerenciar parcelamentos via API
 */
class ParcelamentosController extends BaseController
{
    private ParcelamentoService $service;

    public function __construct(?ParcelamentoService $service = null)
    {
        $this->service = $service ?? new ParcelamentoService();
    }

    /**
     * Lista todos os parcelamentos do usuário
     * GET /api/parcelamentos
     */
    public function index(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Usuário não autenticado', 401);
            return;
        }

        $status = $_GET['status'] ?? null;
        $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : null;
        $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : null;

        $resultado = $this->service->listar($userId, $status, $mes, $ano);

        if (!$resultado['success']) {
            Response::error($resultado['message'], 500);
            return;
        }

        Response::success([
            'parcelamentos' => $resultado['parcelamentos']->map(function ($parcelamento) {
                return $this->formatParcelamento($parcelamento, true);
            }),
        ]);
    }

    /**
     * Busca um parcelamento específico
     * GET /api/parcelamentos/:id
     */
    public function show(int $id = 0): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Usuário não autenticado', 401);
            return;
        }

        if ($id <= 0) {
            Response::error('ID inválido', 400);
            return;
        }

        $resultado = $this->service->buscar($id, $userId);

        if (!$resultado['success']) {
            Response::error($resultado['message'], 404);
            return;
        }

        Response::success([
            'parcelamento' => $this->formatParcelamento($resultado['parcelamento'], true),
        ]);
    }

    /**
     * Cria um novo parcelamento
     * POST /api/parcelamentos
     */
    /**
     * Cria um novo parcelamento
     * POST /api/parcelamentos
     * 
     * IMPORTANTE: Este método cria:
     * 1. Um registro em `parcelamentos` (cabeçalho)
     * 2. N registros em `lancamentos` (cada parcela)
     * 
     * `lancamentos` é a fonte da verdade para saldo/relatórios
     * `parcelamentos` é apenas para agrupamento visual
     */
    public function store(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Usuário não autenticado', 401);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?? [];

        // Extrair dados compatíveis com ambos os formatos (parcelamentos e lançamentos)
        $descricao = $payload['descricao'] ?? '';
        $valorTotal = $payload['valor_total'] ?? $payload['valor'] ?? 0;
        $numeroParcelas = $payload['numero_parcelas'] ?? 0;
        $categoriaId = $payload['categoria_id'] ?? null;
        $contaId = $payload['conta_id'] ?? null;
        $cartaoCreditoId = $payload['cartao_credito_id'] ?? null;
        $tipo = $payload['tipo'] ?? 'saida';
        $dataCriacao = $payload['data_criacao'] ?? $payload['data'] ?? date('Y-m-d');

        // Validações
        $errors = [];

        if (empty($descricao)) {
            $errors['descricao'] = 'Descrição é obrigatória';
        }

        if ($valorTotal <= 0) {
            $errors['valor_total'] = 'Valor total deve ser maior que zero';
        }

        if ($numeroParcelas < 2) {
            $errors['numero_parcelas'] = 'Número de parcelas deve ser maior ou igual a 2';
        }

        if (!$categoriaId) {
            $errors['categoria_id'] = 'Categoria é obrigatória';
        }

        if (!$contaId && !$cartaoCreditoId) {
            $errors['conta'] = 'Conta ou Cartão de Crédito é obrigatório';
        }

        if (!empty($errors)) {
            Response::error('Dados inválidos', 422, $errors);
            return;
        }

        try {
            DB::beginTransaction();

            $valorParcela = $valorTotal / $numeroParcelas;

            // 1. CRIAR CABEÇALHO em `parcelamentos`
            $parcelamento = DB::table('parcelamentos')->insertGetId([
                'user_id' => $userId,
                'descricao' => $descricao,
                'valor_total' => $valorTotal,
                'numero_parcelas' => $numeroParcelas,
                'parcelas_pagas' => 0,
                'categoria_id' => $categoriaId,
                'conta_id' => $contaId,
                'cartao_credito_id' => $cartaoCreditoId,
                'tipo' => $tipo,
                'status' => 'ativo',
                'data_criacao' => $dataCriacao,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // 2. CRIAR LANÇAMENTOS INDIVIDUAIS em `lancamentos`
            // CADA PARCELA = UM LANÇAMENTO (fonte da verdade)
            $dataAtual = new \DateTime($dataCriacao);
            $lancamentosCriados = [];

            for ($i = 1; $i <= $numeroParcelas; $i++) {
                $lancamentoId = DB::table('lancamentos')->insertGetId([
                    'user_id' => $userId,
                    'descricao' => $descricao . " ({$i}/{$numeroParcelas})",
                    'valor' => round($valorParcela, 2),
                    'data' => $dataAtual->format('Y-m-d'),
                    'tipo' => $tipo,
                    'categoria_id' => $categoriaId,
                    'conta_id' => $contaId,
                    'cartao_credito_id' => $cartaoCreditoId,
                    'parcelamento_id' => $parcelamento, // LINK COM CABEÇALHO
                    'numero_parcela' => $i,
                    'pago' => false,
                    'recorrente' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                $lancamentosCriados[] = [
                    'id' => $lancamentoId,
                    'parcela' => $i,
                    'valor' => round($valorParcela, 2),
                    'data' => $dataAtual->format('Y-m-d')
                ];

                // Avançar para o próximo mês
                $dataAtual->modify('+1 month');
            }

            DB::commit();

            $totalParcelas = count($lancamentosCriados);
            Response::success([
                'parcelamento' => [
                    'id' => $parcelamento,
                    'descricao' => $descricao,
                    'valor_total' => $valorTotal,
                    'numero_parcelas' => $numeroParcelas,
                    'tipo' => $tipo
                ],
                'lancamentos_criados' => $lancamentosCriados,
                'total_parcelas' => $totalParcelas,
            ], "✅ Parcelamento criado! {$totalParcelas} lançamentos individuais foram gerados.", 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Response::error('Erro ao criar parcelamento: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancela um parcelamento
     * DELETE /api/parcelamentos/:id
     */
    public function destroy(int $id = 0): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Usuário não autenticado', 401);
            return;
        }

        if ($id <= 0) {
            Response::error('ID inválido', 400);
            return;
        }

        $resultado = $this->service->cancelar($id, $userId);

        if (!$resultado['success']) {
            Response::error($resultado['message'], 422);
            return;
        }

        Response::success([], $resultado['message']);
    }

    /**
     * Marca uma parcela como paga/não paga
     * PUT /api/parcelamentos/parcelas/:id/pagar
     */
    public function marcarParcelaPaga(int $id = 0): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Usuário não autenticado', 401);
            return;
        }

        $lancamentoId = $id;
        if ($lancamentoId <= 0) {
            Response::error('ID inválido', 400);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $pago = (bool)($payload['pago'] ?? true);

        // Buscar o lançamento (parcela)
        $lancamento = Lancamento::where('id', $lancamentoId)
            ->where('user_id', $userId)
            ->whereNotNull('parcelamento_id')
            ->first();

        if (!$lancamento) {
            Response::error('Parcela não encontrada', 404);
            return;
        }

        // Atualizar status
        $lancamento->pago = $pago ? 1 : 0;
        $lancamento->save();

        // Atualizar contador de parcelas pagas no parcelamento
        if ($lancamento->parcelamento_id) {
            $parcelamento = Parcelamento::find($lancamento->parcelamento_id);
            if ($parcelamento) {
                $parcelasPagas = Lancamento::where('parcelamento_id', $parcelamento->id)
                    ->where('pago', true)
                    ->count();

                $parcelamento->parcelas_pagas = $parcelasPagas;

                // Se todas foram pagas, marcar como concluído
                if ($parcelasPagas >= $parcelamento->numero_parcelas) {
                    $parcelamento->status = 'concluido';
                }

                $parcelamento->save();
            }
        }

        Response::success([
            'lancamento' => [
                'id' => $lancamento->id,
                'pago' => (bool)$lancamento->pago,
            ],
        ], $pago ? 'Parcela marcada como paga' : 'Parcela desmarcada');
    }

    /**
     * Formata um parcelamento para resposta
     */
    private function formatParcelamento(Parcelamento $parcelamento, bool $incluirParcelas = false): array
    {
        // Se os lançamentos foram carregados, calcular dinamicamente
        $parcelasPagas = $parcelamento->parcelas_pagas;
        $percentualPago = 0;
        $valorRestante = $parcelamento->valor_total;

        if ($parcelamento->relationLoaded('lancamentos')) {
            $parcelasPagas = $parcelamento->lancamentos->where('pago', true)->count();
            $totalParcelas = $parcelamento->lancamentos->count();
            $percentualPago = $totalParcelas > 0 ? ($parcelasPagas / $totalParcelas) * 100 : 0;
            $valorPago = $parcelamento->lancamentos->where('pago', true)->sum('valor');
            $valorRestante = $parcelamento->valor_total - $valorPago;
        } else {
            $percentualPago = $parcelamento->percentualPago;
            $valorRestante = $parcelamento->valorRestante;
        }

        $data = [
            'id' => $parcelamento->id,
            'descricao' => $parcelamento->descricao,
            'valor_total' => $parcelamento->valor_total,
            'valor_parcela' => $parcelamento->valorParcela,
            'numero_parcelas' => $parcelamento->numero_parcelas,
            'parcelas_pagas' => $parcelasPagas,
            'percentual_pago' => $percentualPago,
            'valor_restante' => $valorRestante,
            'tipo' => $parcelamento->tipo,
            'status' => $parcelamento->status,
            'data_criacao' => $parcelamento->data_criacao->format('Y-m-d'),
            'categoria' => $parcelamento->categoria ? [
                'id' => $parcelamento->categoria->id,
                'nome' => $parcelamento->categoria->nome,
                'icone' => $parcelamento->categoria->icone,
                'cor' => $parcelamento->categoria->cor,
            ] : null,
            'conta' => $parcelamento->conta ? [
                'id' => $parcelamento->conta->id,
                'nome' => $parcelamento->conta->nome,
                'tipo' => $parcelamento->conta->tipo,
            ] : null,
        ];

        if ($incluirParcelas && $parcelamento->relationLoaded('lancamentos')) {
            $data['parcelas'] = $parcelamento->lancamentos->map(function ($lancamento) {
                return [
                    'id' => $lancamento->id,
                    'numero_parcela' => $lancamento->numero_parcela,
                    'descricao' => $lancamento->descricao,
                    'valor' => $lancamento->valor,
                    'data' => $lancamento->data->format('Y-m-d'),
                    'pago' => (bool)$lancamento->pago,
                ];
            })->toArray();
        }

        return $data;
    }
}
