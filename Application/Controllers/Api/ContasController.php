<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Conta;
use Application\Lib\Auth;
use Application\Models\Lancamento;
use Application\Services\ContaBalanceService;
use Application\Services\SaldoInicialService;
use Application\Formatters\ContaResponseFormatter;
use Application\Repositories\ContaRepository;
use Application\DTOs\Requests\CreateContaDTO;
use Application\DTOs\Requests\UpdateContaDTO;
use Application\Validators\ContaValidator;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Capsule\Manager;
use Throwable;

class ContasController extends BaseController
{
    private ContaRepository $contaRepo;
    private SaldoInicialService $saldoService;

    public function __construct()
    {
        parent::__construct();
        $this->contaRepo = new ContaRepository();
        $this->saldoService = new SaldoInicialService();
    }
    public function index(): void
    {
        $userId = Auth::id();

        $archived     = (int)($_GET['archived'] ?? 0) === 1;
        $onlyActive   = (int)($_GET['only_active'] ?? ($archived ? 0 : 1)) === 1;
        $withBalances = (int)($_GET['with_balances'] ?? 0) === 1;
        $month        = trim((string)($_GET['month'] ?? date('Y-m')));

        if ($archived) {
            $rows = $this->contaRepo->findArchived($userId);
        } elseif ($onlyActive) {
            $rows = $this->contaRepo->findActive($userId);
        } else {
            $rows = $this->contaRepo->findByUser($userId);
        }

        $ids = $rows->pluck('id')->all();

        $extras = [];
        $saldoIniciais = [];

        if ($rows->count()) {
            $balanceService = new ContaBalanceService($userId, $ids, $month);

            $saldoIniciais = $balanceService->getInitialBalances();

            if ($withBalances) {
                $extras = $balanceService->calculateFinalBalances($saldoIniciais);
            }
        }

        Response::json(ContaResponseFormatter::formatCollection($rows, $extras, $saldoIniciais));
    }

    public function store(): void
    {
        $userId = Auth::id();
        $payload = $this->getRequestPayload();

        // Validar dados
        $errors = ContaValidator::validateCreate($payload);
        if (!empty($errors)) {
            Response::json(['status' => 'error', 'errors' => $errors], 422);
            return;
        }

        // Criar DTO
        $dto = CreateContaDTO::fromRequest($userId, $payload);
        $saldoInicial = (float)($payload['saldo_inicial'] ?? 0);

        DB::beginTransaction();
        try {
            // Criar conta
            $conta = $this->contaRepo->create($dto->toArray());

            // Criar lançamento de saldo inicial se necessário
            if (abs($saldoInicial) > 0.00001) {
                $this->saldoService->createOrUpdate(
                    $userId,
                    $conta->id,
                    $dto->nome,
                    $saldoInicial
                );
            }

            DB::commit();
            Response::json(['ok' => true, 'id' => (int) $conta->id], 201);
        } catch (Throwable $e) {
            DB::rollBack();
            Response::json(['status' => 'error', 'message' => 'Falha ao criar conta: ' . $e->getMessage()], 500);
        }
    }

    public function update(int $id): void
    {
        $userId = Auth::id();

        $conta = $this->contaRepo->findByIdAndUser($id, $userId);
        if (!$conta) {
            Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
            return;
        }

        $payload = $this->getRequestPayload();

        // Mesclar dados existentes com novos dados
        $data = [
            'nome'        => $payload['nome'] ?? $conta->nome,
            'instituicao' => $payload['instituicao'] ?? $conta->instituicao,
            'moeda'       => $payload['moeda'] ?? $conta->moeda,
            'tipo_id'     => $payload['tipo_id'] ?? $conta->tipo_id,
        ];

        // Validar dados
        $errors = ContaValidator::validateUpdate($data);
        if (!empty($errors)) {
            Response::json(['status' => 'error', 'errors' => $errors], 422);
            return;
        }

        // Criar DTO
        $dto = UpdateContaDTO::fromRequest($data);

        DB::beginTransaction();
        try {
            // Atualizar conta
            $this->contaRepo->update($conta->id, $dto->toArray());

            // Atualizar saldo inicial se fornecido
            if (array_key_exists('saldo_inicial', $payload)) {
                $novoSaldo = (float) $payload['saldo_inicial'];
                $this->saldoService->createOrUpdate(
                    $userId,
                    $conta->id,
                    $dto->nome ?? $conta->nome,
                    $novoSaldo
                );
            }

            // Atualizar ativo se fornecido
            if (array_key_exists('ativo', $payload)) {
                $conta->ativo = (int)($payload['ativo'] ?? 0);
                $conta->save();
            }

            DB::commit();
            
            // Recarregar conta para pegar dados atualizados
            $contaAtualizada = $this->contaRepo->find($conta->id);
            Response::json(['ok' => true, 'ativo' => (bool)$contaAtualizada->ativo]);
        } catch (Throwable $e) {
            DB::rollBack();
            Response::json(['status' => 'error', 'message' => 'Falha ao atualizar: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): void
    {
        $this->archive($id);
    }


    public function archive(int $id): void
    {
        $userId = Auth::id();

        try {
            $this->contaRepo->archive($id, $userId);
            Response::json(['ok' => true, 'message' => 'Conta arquivada.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
        }
    }


    public function restore(int $id): void
    {
        $userId = Auth::id();
        
        try {
            $this->contaRepo->restore($id, $userId);
            Response::json(['ok' => true, 'message' => 'Conta restaurada.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
        }
    }


    public function hardDelete(int $id): void
    {
        $uid = Auth::id();
        $conta = Conta::forUser($uid)->find($id);

        if (!$conta) {
            Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
            return;
        }

        $payload = $this->getRequestPayload();

        $force = (int)($_GET['force'] ?? 0) === 1 || filter_var($payload['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $countOrig = Lancamento::where('user_id', $uid)->where('conta_id', $id)->count();
        $countDest = Lancamento::where('user_id', $uid)->where('conta_id_destino', $id)->count();
        $totalLanc = $countOrig + $countDest;

        if ($totalLanc > 0 && !$force) {
            Response::json([
                'status'       => 'confirm_delete',
                'message'      => 'Esta conta possui lançamentos vinculados. Deseja excluir a conta e TODOS os lançamentos vinculados?',
                'counts'       => [
                    'origem'   => $countOrig,
                    'destino'  => $countDest,
                    'total'    => $totalLanc,
                ],
                'suggestion'   => 'Reenvie a requisição com ?force=1 ou JSON {"force": true} para confirmar a exclusão permanente dos lançamentos.',
            ], 422);
            return;
        }

        if ($totalLanc > 0 && !$force) {
            $conta->ativo = 0;
            $conta->save();
            Response::json(['ok' => true, 'archived' => true, 'message' => 'Conta arquivada em vez de excluída permanentemente.']);
            return;
        }

        Manager::connection()->transaction(function () use ($uid, $id, $conta, $totalLanc) {
            Lancamento::where('user_id', $uid)->where('conta_id', $id)->delete();
            Lancamento::where('user_id', $uid)->where('conta_id_destino', $id)->delete();

            $conta->delete();
        });

        Response::json([
            'ok'                    => true,
            'deleted'               => true,
            'deleted_lancamentos'   => $totalLanc,
            'message'               => 'Conta e lançamentos vinculados excluídos definitivamente.',
        ]);
    }
}
