<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Conta;
use Application\Lib\Auth;
use Application\Models\Lancamento;
use Application\Services\ContaBalanceService;
use Application\Repositories\ContaRepository;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Capsule\Manager;
use Application\Enums\LancamentoTipo;
use Application\Enums\Moeda;
use Throwable;
use ValueError;


class ContasController extends BaseController
{
    private ContaRepository $contaRepo;

    public function __construct()
    {
        parent::__construct();
        $this->contaRepo = new ContaRepository();
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

        Response::json($rows->map(function (Conta $c) use ($extras, $saldoIniciais) {
            $cid = (int)$c->id;
            $x = $extras[$cid] ?? null;
            $initial = (float)($saldoIniciais[$cid] ?? 0);

            return [
                'id'             => $cid,
                'nome'           => (string)$c->nome,
                'instituicao'    => (string)($c->instituicao ?? ''),
                'moeda'          => (string)($c->moeda ?? 'BRL'),
                'saldoInicial'   => $initial,
                'saldoAtual'     => $x['saldoAtual'] ?? null,
                'entradasTotal'  => $x['entradasTotal'] ?? 0.0,
                'saidasTotal'    => $x['saidasTotal'] ?? 0.0,
                'ativo'          => (bool)$c->ativo,
                'arquivada'      => !(bool)$c->ativo,
            ];
        })->all());
    }

    public function store(): void
    {
        $data = $this->getRequestPayload();

        $nome = trim((string)($data['nome'] ?? ''));
        if ($nome === '') {
            Response::json(['status' => 'error', 'message' => 'Nome obrigatório.'], 422);
            return;
        }

        $moeda = strtoupper(trim((string)($data['moeda'] ?? 'BRL')));
        try {
            $moeda = Moeda::from($moeda)->value;
        } catch (ValueError) {
            $moeda = Moeda::BRL->value;
        }

        $tipoId = isset($data['tipo_id']) && $data['tipo_id'] !== '' ? (int)$data['tipo_id'] : null;
        $saldoInicial = (float)($data['saldo_inicial'] ?? 0);
        $userId = Auth::id();

        DB::beginTransaction();
        try {
            $conta = new Conta([
                'user_id'     => $userId,
                'nome'        => $nome,
                'instituicao' => $data['instituicao'] ?? null,
                'moeda'       => $moeda,
                'tipo_id'     => $tipoId,
                'ativo'       => 1,
            ]);
            $conta->save();

            if (abs($saldoInicial) > 0.00001) {
                $isReceita = $saldoInicial >= 0;

                Lancamento::create([
                    'user_id'           => $userId,
                    'tipo'              => $isReceita ? LancamentoTipo::RECEITA->value : LancamentoTipo::DESPESA->value,
                    'data'              => date('Y-m-d'),
                    'categoria_id'      => null,
                    'conta_id'          => $conta->id,
                    'conta_id_destino'  => null,
                    'descricao'         => 'Saldo inicial da conta ' . $nome,
                    'observacao'        => null,
                    'valor'             => abs($saldoInicial),
                    'eh_transferencia'  => 0,
                    'eh_saldo_inicial'  => 1,
                ]);
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

        $data = $this->getRequestPayload();

        $data = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $data);

        $conta->nome        = $data['nome'] ?? $conta->nome;
        $conta->instituicao = $data['instituicao'] ?? $conta->instituicao;

        if (array_key_exists('moeda', $data)) {
            $moeda = strtoupper($data['moeda']);
            try {
                $conta->moeda = Moeda::from($moeda)->value;
            } catch (ValueError) {
            }
        }

        if (array_key_exists('ativo', $data)) {
            $conta->ativo = (int)($data['ativo'] ?? 0);
        }

        DB::beginTransaction();
        try {
            $conta->save();

            if (array_key_exists('saldo_inicial', $data)) {
                $novoSaldo = (float) $data['saldo_inicial'];

                $lanc = Lancamento::where('user_id', $userId)
                    ->where('conta_id', $conta->id)
                    ->where('eh_transferencia', 0)
                    ->where('eh_saldo_inicial', 1)
                    ->first();

                if (abs($novoSaldo) <= 0.00001) {
                    if ($lanc) $lanc->delete();
                } else {
                    $isReceita = $novoSaldo >= 0;

                    $payloadLancamento = [
                        'user_id'           => $userId,
                        'tipo'              => $isReceita ? LancamentoTipo::RECEITA->value : LancamentoTipo::DESPESA->value,
                        'data'              => $lanc?->data?->format('Y-m-d') ?? date('Y-m-d'),
                        'categoria_id'      => null,
                        'conta_id'          => $conta->id,
                        'conta_id_destino'  => null,
                        'descricao'         => 'Saldo inicial da conta ' . ($conta->nome ?? ''),
                        'observacao'        => null,
                        'valor'             => abs($novoSaldo),
                        'eh_transferencia'  => 0,
                        'eh_saldo_inicial'  => 1,
                    ];

                    if ($lanc) {
                        $lanc->fill($payloadLancamento)->save();
                    } else {
                        Lancamento::create($payloadLancamento);
                    }
                }
            }

            DB::commit();
            Response::json(['ok' => true, 'ativo' => (bool)$conta->ativo]);
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
