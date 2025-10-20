<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Agendamento;
use Application\Models\Lancamento;
use GUMP;
use Application\Services\FeatureGate;
use Application\Lib\Auth;

$user = Auth::user();
if (!$user->podeAcessar('scheduling')) {
    Response::forbidden('Agendamentos são exclusivos do plano Pro.');
}

class AgendamentoController extends BaseController
{
    private function moneyToCents(?string $str): ?int
    {
        if ($str === null || $str === '') {
            return null;
        }

        $s = preg_replace('/[^\d,.-]/', '', $str);
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (strpos($s, ',') !== false) {
            $s = str_replace(',', '.', $s);
        }

        $val = (float) $s;
        return (int) round($val * 100);
    }

    private function boolFromMixed($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? !empty($value);
    }

    private function getUserId(): int
    {
        return property_exists($this, 'userId') && $this->userId
            ? (int) $this->userId
            : (int) $this->userId;
    }

    public function store()
    {
        $this->requireAuthApi();

        $data = $_POST;

        $gump = new GUMP();
        $data = $gump->sanitize($data);

        $gump->validation_rules([
            'titulo'                 => 'required|min_len,3|max_len,160',
            'data_pagamento'         => 'required|date',
            'lembrar_antes_segundos' => 'integer|min_numeric,0',
            'canal_email'            => 'boolean',
            'canal_inapp'            => 'boolean',
            'valor_centavos'         => 'integer|min_numeric,0',
            'tipo'                   => 'required|contains_list,despesa;receita',
            'categoria_id'           => 'integer|min_numeric,1',
            'conta_id'               => 'integer|min_numeric,1',
            'recorrente'             => 'boolean',
            'recorrencia_freq'       => 'contains_list,diario;semanal;mensal;anual',
            'recorrencia_intervalo'  => 'integer|min_numeric,1',
            'recorrencia_fim'        => 'date',
        ]);

        $gump->filter_rules([
            'titulo'    => 'trim',
            'descricao' => 'trim',
            'moeda'     => 'trim|upper',
        ]);

        if (!$gump->run($data)) {
            Response::validationError($gump->get_errors_array());
            return;
        }

        $valorCentavos = isset($data['valor_centavos']) && $data['valor_centavos'] !== ''
            ? (int) $data['valor_centavos']
            : $this->moneyToCents($data['valor'] ?? $data['agValor'] ?? null);

        $dataPagamento = str_replace('T', ' ', $data['data_pagamento']);
        $lembrarSeg = (int) ($data['lembrar_antes_segundos'] ?? 0);
        $proximaExec = date('Y-m-d H:i:s', strtotime($dataPagamento) - $lembrarSeg);

        $novo = Agendamento::create([
            'user_id'                => $this->getUserId(),
            'conta_id'               => $data['conta_id']      ?? null,
            'categoria_id'           => $data['categoria_id']  ?? null,
            'titulo'                 => $data['titulo'],
            'descricao'              => $data['descricao']     ?? null,
            'tipo'                   => $data['tipo'],
            'valor_centavos'         => $valorCentavos,
            'moeda'                  => $data['moeda']         ?? 'BRL',
            'data_pagamento'         => $dataPagamento,
            'proxima_execucao'       => $proximaExec,
            'lembrar_antes_segundos' => $lembrarSeg,
            'canal_email'            => $this->boolFromMixed($data['canal_email'] ?? false),
            'canal_inapp'            => $this->boolFromMixed($data['canal_inapp'] ?? true),
            'recorrente'             => $this->boolFromMixed($data['recorrente'] ?? false),
            'recorrencia_freq'       => $data['recorrencia_freq']       ?? null,
            'recorrencia_intervalo'  => $data['recorrencia_intervalo']  ?? null,
            'recorrencia_fim'        => $data['recorrencia_fim']        ?? null,
            'status'                 => 'pendente',
        ]);

        Response::success(['agendamento' => $novo]);
    }

    public function index()
    {
        $this->requireAuthApi();

        $itens = Agendamento::with(['categoria:id,nome', 'conta:id,nome'])
            ->where('user_id', $this->getUserId())
            ->orderBy('data_pagamento', 'asc')
            ->limit(100)
            ->get();

        Response::success(['itens' => $itens]);
    }

    public function updateStatus($id)
    {
        $this->requireAuthApi();

        $agendamento = Agendamento::where('user_id', $this->getUserId())
            ->where('id', $id)
            ->first();

        if (!$agendamento) {
            Response::notFound('Agendamento nao encontrado.');
            return;
        }

        $status = strtolower(trim($_POST['status'] ?? ''));
        $allowed = ['pendente', 'concluido', 'cancelado'];
        if (!in_array($status, $allowed, true)) {
            Response::validationError(['status' => 'Status invalido.']);
            return;
        }

        $previousStatus = $agendamento->status ?? null;

        $payload = ['status' => $status];
        $payload['concluido_em'] = $status === 'concluido'
            ? date('Y-m-d H:i:s')
            : null;

        $lancamento = null;
        if ($status === 'concluido' && $previousStatus !== 'concluido') {
            try {
                $lancamento = $this->createLancamentoFromAgendamento($agendamento);
            } catch (\Throwable $e) {
                Response::error('Falha ao gerar lancamento: ' . $e->getMessage(), 500);
                return;
            }
        }

        $agendamento->update($payload);
        $agendamento->refresh()->load(['categoria:id,nome', 'conta:id,nome']);

        Response::success([
            'agendamento' => $agendamento,
            'lancamento'  => $lancamento,
        ]);
    }

    public function cancel($id)
    {
        $this->requireAuthApi();

        $agendamento = Agendamento::where('user_id', $this->getUserId())
            ->where('id', $id)
            ->first();

        if (!$agendamento) {
            Response::notFound('Agendamento nao encontrado.');
            return;
        }

        $agendamento->update([
            'status'       => 'cancelado',
            'concluido_em' => null,
        ]);

        $agendamento->refresh()->load(['categoria:id,nome', 'conta:id,nome']);

        Response::success(['agendamento' => $agendamento]);
    }

    private function createLancamentoFromAgendamento(Agendamento $agendamento): ?Lancamento
    {
        $userId = $agendamento->user_id ?? $this->getUserId();
        if (!$userId) {
            throw new \RuntimeException('Usuario nao definido para o agendamento.');
        }

        $valorCentavos = $agendamento->valor_centavos;
        $valor = $valorCentavos !== null ? round(((int) $valorCentavos) / 100, 2) : 0.0;
        if ($valor <= 0) {
            throw new \RuntimeException('Valor do agendamento deve ser maior que zero.');
        }

        $descricao = trim((string) ($agendamento->titulo ?? ''));
        if ($descricao === '') {
            $descricao = 'Agendamento';
        }

        $observacaoBase = trim((string) ($agendamento->descricao ?? ''));
        $observacao = $observacaoBase !== ''
            ? $observacaoBase . ' (Agendamento #' . $agendamento->id . ')'
            : 'Gerado automaticamente do agendamento #' . $agendamento->id;

        $dataPagamento = $agendamento->data_pagamento;
        if ($dataPagamento instanceof \DateTimeInterface) {
            $data = $dataPagamento->format('Y-m-d');
        } elseif (!empty($dataPagamento)) {
            $dt = date_create($dataPagamento);
            $data = $dt ? $dt->format('Y-m-d') : date('Y-m-d');
        } else {
            $data = date('Y-m-d');
        }

        $existing = Lancamento::where('user_id', $userId)
            ->where('observacao', $observacao)
            ->first();
        if ($existing) {
            return $existing;
        }

        return Lancamento::create([
            'user_id'          => $userId,
            'tipo'             => $agendamento->tipo ?? Lancamento::TIPO_DESPESA,
            'data'             => $data,
            'categoria_id'     => $agendamento->categoria_id,
            'conta_id'         => $agendamento->conta_id,
            'descricao'        => $descricao,
            'observacao'       => $observacao,
            'valor'            => $valor,
            'eh_transferencia' => 0,
            'eh_saldo_inicial' => 0,
        ]);
    }
}
