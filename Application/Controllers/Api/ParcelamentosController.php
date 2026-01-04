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

        if ($cartaoCreditoId && $contaId) {
            $errors['origem'] = 'Informe apenas conta OU cartão, não ambos';
        }

        if (!$cartaoCreditoId && !$contaId) {
            $errors['origem'] = 'Informe uma conta ou um cartão de crédito';
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

        // Atualizar status básico na parcela
        $previouslyPaid = (bool)$lancamento->pago;
        $lancamento->pago = $pago ? 1 : 0;
        // Registrar data de pagamento na parcela
        $lancamento->data_pagamento = $pago ? date('Y-m-d') : null;

        // Se marcou como pago agora e ainda não estava pago, e a data da parcela
        // for diferente da data do pagamento (pagamento adiantado), criaremos
        // um lançamento financeiro real com data = hoje para refletir o movimento.
        if ($pago && !$previouslyPaid) {
            $today = date('Y-m-d');
            if ($lancamento->data->format('Y-m-d') !== $today) {
                // Criar um lançamento 'real' representando o pagamento hoje
                $newId = DB::table('lancamentos')->insertGetId([
                    'user_id' => $userId,
                    'descricao' => 'Pagamento antecipado: ' . $lancamento->descricao,
                    'valor' => $lancamento->valor,
                    'data' => $today,
                    'tipo' => $lancamento->tipo,
                    'categoria_id' => $lancamento->categoria_id,
                    'conta_id' => $lancamento->conta_id,
                    'conta_id_destino' => $lancamento->conta_id_destino ?? null,
                    'eh_parcelado' => 0,
                    'pago' => 1,
                    'data_pagamento' => $today,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Se desmarcou pagamento, remover lançamento financeiro criado anteriormente
        if (!$pago && $previouslyPaid) {
            // Remover lançamento criado para o pagamento adiantado.
            // Como não usamos mais `lancamento_pai_id`, buscamos pelo padrão de descrição,
            // data, valor e usuário para identificar o registro criado.
            $descricaoPattern = 'Pagamento antecipado: ' . $lancamento->descricao;
            $today = date('Y-m-d');
            DB::table('lancamentos')
                ->where('user_id', $userId)
                ->where('descricao', $descricaoPattern)
                ->where('data', $today)
                ->where('valor', $lancamento->valor)
                ->delete();
        }

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
                'data_pagamento' => $lancamento->data_pagamento,
            ],
        ], $pago ? 'Parcela marcada como paga' : 'Parcela desmarcada');
    }

    /**
     * Formata um parcelamento para resposta (suporta Parcelamento model e stdClass de cartão)
     */
    private function formatParcelamento($parcelamento, bool $incluirParcelas = false): array
    {
        // Determina se é parcelamento de cartão (stdClass) ou normal (Model)
        $isCartao = is_object($parcelamento) && isset($parcelamento->is_cartao) && $parcelamento->is_cartao;

        // Se os lançamentos foram carregados, calcular dinamicamente
        $parcelasPagas = $parcelamento->parcelas_pagas ?? 0;
        $percentualPago = 0;
        $valorRestante = $parcelamento->valor_total ?? 0;
        $totalParcelas = $parcelamento->numero_parcelas ?? 0;

        // Para parcelamentos normais (Model)
        if (!$isCartao && is_object($parcelamento) && method_exists($parcelamento, 'relationLoaded')) {
            if ($parcelamento->relationLoaded('lancamentos')) {
                $parcelasPagas = $parcelamento->lancamentos->where('pago', true)->count();
                $totalParcelas = $parcelamento->lancamentos->count();
                $percentualPago = $totalParcelas > 0 ? ($parcelasPagas / $totalParcelas) * 100 : 0;
                $valorPago = $parcelamento->lancamentos->where('pago', true)->sum('valor');
                $valorRestante = $parcelamento->valor_total - $valorPago;
            } else {
                $percentualPago = $parcelamento->percentualPago ?? 0;
                $valorRestante = $parcelamento->valorRestante ?? 0;
            }
        } else {
            // Para parcelamentos de cartão (stdClass)
            $percentualPago = $totalParcelas > 0 ? ($parcelasPagas / $totalParcelas) * 100 : 0;
            $valorPago = isset($parcelamento->lancamentos) ?
                collect($parcelamento->lancamentos)->where('pago', true)->sum('valor') : 0;
            $valorRestante = $parcelamento->valor_total - $valorPago;
        }

        // Valor da parcela
        $valorParcela = $totalParcelas > 0 ? ($parcelamento->valor_total / $totalParcelas) : 0;

        // Data de criação - usar created_at se data_criacao for inválida
        $dataCriacao = $parcelamento->data_criacao;

        // Se for objeto Carbon, tentar formatar
        if (is_object($dataCriacao) && method_exists($dataCriacao, 'format')) {
            try {
                $dataCriacao = $dataCriacao->format('Y-m-d');
            } catch (\Exception $e) {
                // Se falhar, usar created_at
                $dataCriacao = null;
            }
        }

        // Verificar se é uma data inválida (0000-00-00 ou similar) ou nula
        if (!$dataCriacao || $dataCriacao === '0000-00-00' || strpos($dataCriacao, '0000') === 0 || strpos($dataCriacao, '-0001') !== false) {
            // Usar created_at como fallback
            $createdAt = $parcelamento->created_at ?? date('Y-m-d');
            if (is_object($createdAt) && method_exists($createdAt, 'format')) {
                $dataCriacao = $createdAt->format('Y-m-d');
            } elseif (is_string($createdAt) && strlen($createdAt) > 10) {
                $dataCriacao = substr($createdAt, 0, 10);
            } else {
                $dataCriacao = date('Y-m-d');
            }
        }

        $data = [
            'id' => $parcelamento->id,
            'descricao' => $parcelamento->descricao,
            'valor_total' => (float)$parcelamento->valor_total,
            'valor_parcela' => (float)$valorParcela,
            'numero_parcelas' => (int)$totalParcelas,
            'parcelas_pagas' => (int)$parcelasPagas,
            'percentual_pago' => round($percentualPago, 2),
            'valor_restante' => (float)$valorRestante,
            'tipo' => $parcelamento->tipo ?? 'saida',
            'status' => $parcelamento->status ?? 'ativo',
            'data_criacao' => $dataCriacao,
            'is_cartao' => $isCartao,
        ];

        // Adicionar categoria
        $categoria = $parcelamento->categoria ?? null;
        if ($categoria) {
            $data['categoria'] = [
                'id' => $categoria->id,
                'nome' => $categoria->nome,
                'icone' => $categoria->icone ?? null,
                'cor' => $categoria->cor ?? null,
            ];
        } else {
            $data['categoria'] = null;
        }

        // Adicionar conta ou cartão
        if ($isCartao && isset($parcelamento->cartaoCredito)) {
            $data['cartao'] = [
                'id' => $parcelamento->cartaoCredito->id,
                'nome' => $parcelamento->cartaoCredito->nome_cartao,
                'bandeira' => $parcelamento->cartaoCredito->bandeira ?? null,
            ];
            $data['conta'] = null;
        } else {
            $conta = $parcelamento->conta ?? null;
            if ($conta) {
                $data['conta'] = [
                    'id' => $conta->id,
                    'nome' => $conta->nome,
                    'tipo' => $conta->tipo ?? null,
                ];
            } else {
                $data['conta'] = null;
            }
            $data['cartao'] = null;
        }

        // Incluir parcelas se solicitado
        if ($incluirParcelas && isset($parcelamento->lancamentos)) {
            $lancamentos = is_array($parcelamento->lancamentos) || $parcelamento->lancamentos instanceof \Traversable
                ? $parcelamento->lancamentos
                : [];

            $data['parcelas'] = collect($lancamentos)->map(function ($lancamento) {
                $dataLanc = $lancamento->data ?? null;
                if (is_object($dataLanc) && method_exists($dataLanc, 'format')) {
                    $dataLanc = $dataLanc->format('Y-m-d');
                }

                $dataPag = $lancamento->data_pagamento ?? null;
                if (is_object($dataPag) && method_exists($dataPag, 'format')) {
                    $dataPag = $dataPag->format('Y-m-d');
                }

                // Extrair número da parcela da descrição se não existir
                $numeroParcela = $lancamento->numero_parcela ?? $lancamento->parcela_atual ?? null;
                if (!$numeroParcela && isset($lancamento->descricao)) {
                    if (preg_match('/\((\d+)\/\d+\)/', $lancamento->descricao, $matches)) {
                        $numeroParcela = (int)$matches[1];
                    }
                }

                return [
                    'id' => $lancamento->id,
                    'numero_parcela' => $numeroParcela,
                    'descricao' => $lancamento->descricao,
                    'valor' => (float)$lancamento->valor,
                    'data' => $dataLanc,
                    'pago' => (bool)($lancamento->pago ?? false),
                    'data_pagamento' => $dataPag,
                ];
            })->toArray();
        }

        return $data;
    }
}
