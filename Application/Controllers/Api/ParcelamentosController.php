<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\ParcelamentoService;
use Application\Models\Parcelamento;

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

        $resultado = $this->service->listar($userId, $status);

        if (!$resultado['success']) {
            Response::error($resultado['message'], 500);
            return;
        }

        Response::success([
            'parcelamentos' => $resultado['parcelamentos']->map(function ($parcelamento) {
                return $this->formatParcelamento($parcelamento);
            }),
        ]);
    }

    /**
     * Busca um parcelamento específico
     * GET /api/parcelamentos/:id
     */
    public function show(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Usuário não autenticado', 401);
            return;
        }

        $id = (int)($this->params['id'] ?? 0);
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
    public function store(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Usuário não autenticado', 401);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?? [];

        // Extrair dados compatíveis com ambos os formatos
        $descricao = $payload['descricao'] ?? '';
        $valorTotal = $payload['valor_total'] ?? $payload['valor'] ?? 0;
        $numeroParcelas = $payload['numero_parcelas'] ?? 0;
        $categoriaId = $payload['categoria_id'] ?? null;
        $contaId = $payload['conta_id'] ?? null;
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

        if (!$contaId) {
            $errors['conta_id'] = 'Conta é obrigatória';
        }

        if (!empty($errors)) {
            Response::error('Dados inválidos', 422, $errors);
            return;
        }

        // Preparar dados padronizados
        $dadosPadronizados = [
            'descricao' => $descricao,
            'valor_total' => (float)$valorTotal,
            'numero_parcelas' => (int)$numeroParcelas,
            'categoria_id' => (int)$categoriaId,
            'conta_id' => (int)$contaId,
            'tipo' => $tipo,
            'data_criacao' => $dataCriacao,
        ];

        // Criar parcelamento
        $resultado = $this->service->criar($userId, $dadosPadronizados);

        if (!$resultado['success']) {
            Response::error($resultado['message'], 422);
            return;
        }

        $totalParcelas = count($resultado['parcelas']);
        Response::success([
            'parcelamento' => $this->formatParcelamento($resultado['parcelamento'], true),
            'total_parcelas' => $totalParcelas,
        ], "Parcelamento criado com sucesso! {$totalParcelas} parcelas geradas.", 201);
    }

    /**
     * Cancela um parcelamento
     * DELETE /api/parcelamentos/:id
     */
    public function destroy(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Usuário não autenticado', 401);
            return;
        }

        $id = (int)($this->params['id'] ?? 0);
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
    public function marcarPaga(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Usuário não autenticado', 401);
            return;
        }

        $lancamentoId = (int)($this->params['id'] ?? 0);
        if ($lancamentoId <= 0) {
            Response::error('ID inválido', 400);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $pago = (bool)($payload['pago'] ?? true);

        $resultado = $this->service->marcarParcelaPaga($lancamentoId, $userId, $pago);

        if (!$resultado['success']) {
            Response::error($resultado['message'], 422);
            return;
        }

        Response::success([
            'lancamento' => [
                'id' => $resultado['lancamento']->id,
                'pago' => $resultado['lancamento']->pago,
            ],
        ], $resultado['message']);
    }

    /**
     * Formata um parcelamento para resposta
     */
    private function formatParcelamento(Parcelamento $parcelamento, bool $incluirParcelas = false): array
    {
        $data = [
            'id' => $parcelamento->id,
            'descricao' => $parcelamento->descricao,
            'valor_total' => $parcelamento->valor_total,
            'valor_parcela' => $parcelamento->valorParcela,
            'numero_parcelas' => $parcelamento->numero_parcelas,
            'parcelas_pagas' => $parcelamento->parcelas_pagas,
            'percentual_pago' => $parcelamento->percentualPago,
            'valor_restante' => $parcelamento->valorRestante,
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
