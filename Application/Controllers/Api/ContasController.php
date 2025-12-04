<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Models\Conta;
use Application\Lib\Auth;
use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Capsule\Manager;
use DateTimeImmutable;
use Throwable;
use ValueError;

enum LancamentoTipo: string
{
    case DESPESA = 'despesa';
    case RECEITA = 'receita';
}

enum Moeda: string
{
    case BRL = 'BRL';
    case USD = 'USD';
    case EUR = 'EUR';

    public static function listValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}

class ContasBalanceService
{
    private int $userId;
    private array $accountIds;
    private string $endDate;

    public function __construct(int $userId, array $accountIds, string $month)
    {
        $this->userId = $userId;
        $this->accountIds = $accountIds;

        $dt = \DateTime::createFromFormat('Y-m', $month);
        if (!$dt || $dt->format('Y-m') !== $month) {
            $dt = new \DateTime(date('Y-m') . '-01');
        }
        $this->endDate = (new DateTimeImmutable($dt->format('Y-m-01')))
            ->modify('last day of this month')
            ->format('Y-m-d');
    }

    public function getInitialBalances(): array
    {
        if (empty($this->accountIds)) return [];

        return Lancamento::where('user_id', $this->userId)
            ->whereIn('conta_id', $this->accountIds)
            ->where('eh_saldo_inicial', 1)
            ->selectRaw("
                conta_id,
                SUM(
                    CASE
                        WHEN tipo = ?
                            THEN -valor
                        ELSE valor
                    END
                ) as total
            ", [LancamentoTipo::DESPESA->value])
            ->groupBy('conta_id')
            ->pluck('total', 'conta_id')
            ->all();
    }


    public function calculateFinalBalances(array $initialBalances): array
    {
        if (empty($this->accountIds)) return [];


        $rec = Lancamento::where('user_id', $this->userId)
            ->whereIn('conta_id', $this->accountIds)
            ->where('eh_transferencia', 0)
            ->where('eh_saldo_inicial', 0)
            ->where('data', '<=', $this->endDate)
            ->where('tipo', LancamentoTipo::RECEITA->value)
            ->selectRaw('conta_id, SUM(valor) as tot')
            ->groupBy('conta_id')->pluck('tot', 'conta_id')->all();

        $des = Lancamento::where('user_id', $this->userId)
            ->whereIn('conta_id', $this->accountIds)
            ->where('eh_transferencia', 0)
            ->where('eh_saldo_inicial', 0)
            ->where('data', '<=', $this->endDate)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->selectRaw('conta_id, SUM(valor) as tot')
            ->groupBy('conta_id')->pluck('tot', 'conta_id')->all();

        $tin = Lancamento::where('user_id', $this->userId)
            ->whereIn('conta_id_destino', $this->accountIds)
            ->where('eh_transferencia', 1)
            ->where('data', '<=', $this->endDate)
            ->selectRaw('conta_id_destino as cid, SUM(valor) as tot')
            ->groupBy('cid')->pluck('tot', 'cid')->all();

        $tout = Lancamento::where('user_id', $this->userId)
            ->whereIn('conta_id', $this->accountIds)
            ->where('eh_transferencia', 1)
            ->where('data', '<=', $this->endDate)
            ->selectRaw('conta_id as cid, SUM(valor) as tot')
            ->groupBy('cid')->pluck('tot', 'cid')->all();

        $extras = [];
        foreach ($this->accountIds as $cid) {
            $r = (float)($rec[$cid] ?? 0);
            $d = (float)($des[$cid] ?? 0);
            $i = (float)($tin[$cid] ?? 0);
            $o = (float)($tout[$cid] ?? 0);
            $si = (float)($initialBalances[$cid] ?? 0);

            $saldoAtual = $si + $r - $d + $i - $o;

            $extras[$cid] = [
                'saldoAtual'    => $saldoAtual,
                'entradasTotal' => $r + $i,
                'saidasTotal'   => $d + $o,
                'saldoInicial'  => $si,
            ];
        }

        return $extras;
    }
}


class ContasController
{

    private function getRequestPayload(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        if (empty($data) && strtolower($_SERVER['REQUEST_METHOD'] ?? '') === 'post') {
            $data = $_POST;
        }
        return $data;
    }


    public function index(): void
    {
        $userId = Auth::id();

        $archived     = (int)($_GET['archived'] ?? 0) === 1;
        $onlyActive   = (int)($_GET['only_active'] ?? ($archived ? 0 : 1)) === 1;
        $withBalances = (int)($_GET['with_balances'] ?? 0) === 1;
        $month        = trim((string)($_GET['month'] ?? date('Y-m')));

        $q = Conta::forUser($userId);
        if ($archived) {
            $q->arquivadas();
        } elseif ($onlyActive) {
            $q->ativas();
        }

        $rows = $q->orderBy('nome')->get();
        $ids = $rows->pluck('id')->all();

        $extras = [];
        $saldoIniciais = [];

        if ($rows->count()) {
            $balanceService = new ContasBalanceService($userId, $ids, $month);

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

        $conta = Conta::forUser($userId)->find($id);
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

        $conta = Conta::forUser($userId)->find($id);

        if (!$conta) {
            Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
            return;
        }
        $conta->ativo = 0;
        $conta->save();
        Response::json(['ok' => true, 'message' => 'Conta arquivada.']);
    }


    public function restore(int $id): void
    {
        $userId = Auth::id();
        $conta = Conta::forUser($userId)->find($id);

        if (!$conta) {
            Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
            return;
        }
        $conta->ativo = 1;
        $conta->save();
        Response::json(['ok' => true, 'message' => 'Conta restaurada.']);
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