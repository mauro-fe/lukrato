<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Fatura;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Models\Lancamento;
use Application\Models\Parcelamento;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Services\Infrastructure\LogService;
use Application\Services\User\OnboardingProgressService;

class ParcelamentosController extends ApiController
{
    private ParcelamentoRepository $parcelamentoRepo;
    private CategoriaRepository $categoriaRepo;
    private ContaRepository $contaRepo;
    private OnboardingProgressService $progressService;

    public function __construct(
        ?ParcelamentoRepository $parcelamentoRepo = null,
        ?CategoriaRepository $categoriaRepo = null,
        ?ContaRepository $contaRepo = null,
        ?OnboardingProgressService $progressService = null
    ) {
        parent::__construct();
        $this->parcelamentoRepo = $parcelamentoRepo ?? new ParcelamentoRepository();
        $this->categoriaRepo = $categoriaRepo ?? new CategoriaRepository();
        $this->contaRepo = $contaRepo ?? new ContaRepository();
        $this->progressService = $progressService ?? new OnboardingProgressService();
    }

    public function index(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $status = $this->getStringQuery('status', '');
        $parcelamentos = $status
            ? $this->parcelamentoRepo->findByStatus($userId, $status)
            : $this->parcelamentoRepo->findByUsuario($userId);

        $result = $parcelamentos->map(function ($parcelamento) {
            $parcelas = $parcelamento->lancamentos->sortBy('data')->values();

            return [
                'id' => $parcelamento->id,
                'descricao' => $parcelamento->descricao,
                'valor_total' => (float) $parcelamento->valor_total,
                'valor_parcela' => $parcelamento->valor_parcela,
                'numero_parcelas' => (int) $parcelamento->numero_parcelas,
                'parcelas_pagas' => (int) $parcelamento->parcelas_pagas,
                'percentual_pago' => $parcelamento->percentual_pago,
                'status' => $parcelamento->status,
                'tipo' => $parcelamento->tipo,
                'categoria' => $parcelamento->categoria?->nome ?? null,
                'conta' => $parcelamento->conta?->nome ?? null,
                'data_criacao' => $parcelamento->data_criacao,
                'parcelas' => $parcelas->map(fn($lancamento) => [
                    'id' => $lancamento->id,
                    'numero_parcela' => $lancamento->numero_parcela,
                    'data' => (string) $lancamento->data,
                    'valor' => (float) $lancamento->valor,
                    'pago' => (bool) $lancamento->pago,
                    'data_pagamento' => $lancamento->data_pagamento ? (string) $lancamento->data_pagamento : null,
                    'descricao' => $lancamento->descricao,
                ])->toArray(),
            ];
        });

        return Response::successResponse($result->toArray());
    }

    public function show(int $id): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $parcelamento = $this->parcelamentoRepo->findWithLancamentos($id);
        if (!$parcelamento || (int) $parcelamento->user_id !== $userId) {
            return Response::errorResponse('Parcelamento não encontrado', 404);
        }

        $parcelas = $parcelamento->lancamentos->sortBy('data')->values();

        return Response::successResponse([
            'id' => $parcelamento->id,
            'descricao' => $parcelamento->descricao,
            'valor_total' => (float) $parcelamento->valor_total,
            'valor_parcela' => $parcelamento->valor_parcela,
            'numero_parcelas' => (int) $parcelamento->numero_parcelas,
            'parcelas_pagas' => (int) $parcelamento->parcelas_pagas,
            'percentual_pago' => $parcelamento->percentual_pago,
            'status' => $parcelamento->status,
            'tipo' => $parcelamento->tipo,
            'categoria' => $parcelamento->categoria?->nome ?? null,
            'conta' => $parcelamento->conta?->nome ?? null,
            'data_criacao' => $parcelamento->data_criacao,
            'parcelas' => $parcelas->map(fn($lancamento) => [
                'id' => $lancamento->id,
                'numero_parcela' => $lancamento->numero_parcela,
                'data' => (string) $lancamento->data,
                'valor' => (float) $lancamento->valor,
                'pago' => (bool) $lancamento->pago,
                'data_pagamento' => $lancamento->data_pagamento ? (string) $lancamento->data_pagamento : null,
                'descricao' => $lancamento->descricao,
            ])->toArray(),
        ]);
    }

    public function store(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        $data = $this->getJson();

        if (!$data) {
            return Response::errorResponse('Dados inválidos', 400);
        }

        $errors = [];

        $descricao = trim($data['descricao'] ?? '');
        if ($descricao === '') {
            $errors['descricao'] = 'Descrição é obrigatória.';
        }

        $valorTotal = (float) ($data['valor_total'] ?? 0);
        if ($valorTotal <= 0) {
            $errors['valor_total'] = 'Valor total deve ser maior que zero.';
        }

        $numeroParcelas = (int) ($data['numero_parcelas'] ?? 0);
        if ($numeroParcelas < 2 || $numeroParcelas > 120) {
            $errors['numero_parcelas'] = 'Número de parcelas deve ser entre 2 e 120.';
        }

        $contaId = (int) ($data['conta_id'] ?? 0);
        if ($contaId <= 0) {
            $errors['conta_id'] = 'Conta é obrigatória.';
        } elseif (!$this->contaRepo->belongsToUser($contaId, $userId)) {
            $errors['conta_id'] = 'Conta inválida.';
        }

        $categoriaId = null;
        if (!empty($data['categoria_id'])) {
            $categoriaId = (int) $data['categoria_id'];
            if (!$this->categoriaRepo->belongsToUser($categoriaId, $userId)) {
                $errors['categoria_id'] = 'Categoria inválida.';
            }
        }

        $subcategoriaId = null;
        if (!empty($data['subcategoria_id'])) {
            $subcategoriaId = (int) $data['subcategoria_id'];
            if (!$this->categoriaRepo->belongsToUser($subcategoriaId, $userId)) {
                $subcategoriaId = null;
            }
        }

        $formaPagamento = $data['forma_pagamento'] ?? null;
        if ($formaPagamento !== null && !in_array($formaPagamento, ['pix', 'cartao_credito', 'cartao_debito', 'dinheiro', 'boleto', 'transferencia', 'deposito'], true)) {
            $formaPagamento = null;
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
            return Response::validationErrorResponse($errors);
        }

        $lembrarAntesSegundos = null;
        $canalEmail = false;
        $canalInapp = false;
        if (!empty($data['lembrar_antes_segundos'])) {
            $lembrarAntesSegundos = (int) $data['lembrar_antes_segundos'];
            $canalEmail = (bool) ($data['canal_email'] ?? false);
            $canalInapp = (bool) ($data['canal_inapp'] ?? true);
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
                'tipo' => $tipo === 'receita' ? 'entrada' : 'saida',
                'status' => Parcelamento::STATUS_ATIVO,
                'data_criacao' => $dataCriacao,
            ];

            $parcelamento = Parcelamento::create($parcelamentoData);

            $valorParcela = round($valorTotal / $numeroParcelas, 2);
            $somaParcelas = $valorParcela * ($numeroParcelas - 1);
            $valorUltima = round($valorTotal - $somaParcelas, 2);

            $dataPrimeira = new \DateTime($dataCriacao);

            for ($i = 1; $i <= $numeroParcelas; $i++) {
                $dataVencimento = clone $dataPrimeira;
                if ($i > 1) {
                    $dataVencimento->modify('+' . ($i - 1) . ' months');
                }

                $valorDessa = $i === $numeroParcelas ? $valorUltima : $valorParcela;

                Lancamento::create([
                    'user_id' => $userId,
                    'tipo' => $tipo,
                    'data' => $dataVencimento->format('Y-m-d'),
                    'categoria_id' => $categoriaId,
                    'subcategoria_id' => $subcategoriaId,
                    'conta_id' => $contaId,
                    'descricao' => $descricao . " ({$i}/{$numeroParcelas})",
                    'valor' => $valorDessa,
                    'forma_pagamento' => $formaPagamento,
                    'eh_transferencia' => false,
                    'eh_saldo_inicial' => false,
                    'parcelamento_id' => $parcelamento->id,
                    'numero_parcela' => $i,
                    'total_parcelas' => $numeroParcelas,
                    'pago' => false,
                    'afeta_caixa' => false,
                    'origem_tipo' => Lancamento::ORIGEM_PARCELAMENTO,
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

            return Response::successResponse([
                'id' => $parcelamento->id,
                'descricao' => $parcelamento->descricao,
                'valor_total' => (float) $parcelamento->valor_total,
                'numero_parcelas' => (int) $parcelamento->numero_parcelas,
                'total_criados' => $numeroParcelas,
                'message' => "Parcelamento criado: {$numeroParcelas} parcelas geradas",
            ], 'Parcelamento criado com sucesso', 201);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::LANCAMENTO, [
                'action' => 'create_parcelamento',
                'user_id' => $userId,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao criar parcelamento.');
        }
    }

    public function destroy(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        $scope = $this->getStringQuery('scope', 'unpaid');

        $parcelamento = Parcelamento::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$parcelamento) {
            return Response::errorResponse('Parcelamento não encontrado', 404);
        }

        try {
            $hoje = date('Y-m-d');

            if ($scope === 'all') {
                $excluidos = Lancamento::where('parcelamento_id', $id)
                    ->where('user_id', $userId)
                    ->delete();

                $parcelamento->delete();
                $this->syncOnboardingStateAfterDeletion($userId);

                return Response::successResponse([
                    'message' => "Parcelamento excluído completamente ({$excluidos} parcelas removidas)",
                    'itens_excluidos' => $excluidos,
                ]);
            }

            if ($scope === 'future') {
                $excluidos = Lancamento::where('parcelamento_id', $id)
                    ->where('user_id', $userId)
                    ->where('pago', false)
                    ->where('data', '>', $hoje)
                    ->delete();

                $restantes = Lancamento::where('parcelamento_id', $id)->count();
                if ($restantes === 0) {
                    $parcelamento->delete();
                } else {
                    $parcelamento->numero_parcelas = $restantes;
                    $parcelamento->save();
                    $this->parcelamentoRepo->atualizarParcelasPagas($id);
                }

                $this->syncOnboardingStateAfterDeletion($userId);

                return Response::successResponse([
                    'message' => "{$excluidos} parcelas futuras removidas",
                    'itens_excluidos' => $excluidos,
                ]);
            }

            $excluidos = Lancamento::where('parcelamento_id', $id)
                ->where('user_id', $userId)
                ->where('pago', false)
                ->delete();

            $parcelamento->status = Parcelamento::STATUS_CANCELADO;
            $parcelamento->save();
            $this->syncOnboardingStateAfterDeletion($userId);

            return Response::successResponse([
                'message' => "Parcelamento cancelado ({$excluidos} parcelas pendentes removidas)",
                'itens_excluidos' => $excluidos,
            ]);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::LANCAMENTO, [
                'action' => 'delete_parcelamento',
                'parcelamento_id' => $id,
            ]);

            return Response::errorResponse('Erro ao excluir parcelamento', 500);
        }
    }

    private function syncOnboardingStateAfterDeletion(int $userId): void
    {
        try {
            $this->progressService->resyncState($userId);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::GENERAL, [
                'action' => 'sync_onboarding_after_parcelamento_delete',
                'user_id' => $userId,
            ]);
        }
    }
}
