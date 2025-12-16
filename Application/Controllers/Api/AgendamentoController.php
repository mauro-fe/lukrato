<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Agendamento;
use Application\Services\AgendamentoService;
use Application\Services\LogService;
use Application\Lib\Auth;
use GUMP;
use DateTimeImmutable;
use Throwable;
use ValueError;

enum AgendamentoStatus: string
{
    case PENDENTE = 'pendente';
    case CONCLUIDO = 'concluido';
    case CANCELADO = 'cancelado';
}

enum TipoLancamento: string
{
    case DESPESA = 'despesa';
    case RECEITA = 'receita';
}

class AgendamentoController extends BaseController
{
    private readonly GUMP $validator;
    private readonly AgendamentoService $service;

    public function __construct()
    {
        parent::__construct();
        $this->validator = new GUMP();
        $this->service = new AgendamentoService();
    }

    private function ensureSchedulingAccess(): bool
    {
        $user = Auth::user();
        
        if (!$user || (method_exists($user, 'podeAcessar') && !$user->podeAcessar('scheduling'))) {
            Response::forbidden('Agendamentos são exclusivos do plano Pro.');
            return false;
        }

        return true;
    }

    private function normalizeDataPagamento(array $data): array
    {
        if (!isset($data['data_pagamento'])) {
            return $data;
        }

        $raw = (string) $data['data_pagamento'];
        if ($raw === '') {
            return $data;
        }

        $sanitized = str_replace('T', ' ', $raw);

        try {
            $dt = new DateTimeImmutable($sanitized);
            $data['data_pagamento'] = $dt->format('Y-m-d H:i:s');
        } catch (Throwable) {
            $data['data_pagamento'] = $sanitized;
        }

        return $data;
    }

    private function getUserId(): int
    {
        return (int) (property_exists($this, 'userId') ? $this->userId : Auth::user()->id);
    }

    private function moneyToCents(?string $str): ?int
    {
        if (empty($str)) {
            return null;
        }

        $s = preg_replace('/[^\d,.-]/', '', $str);

        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }

        $valor = (float) $s;
        return (int) round($valor * 100);
    }

    private function boolFromMixed(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? !empty($value);
    }

    private function calcularProximaExecucao(string $dataPagamento, int $lembrarSegundos): string
    {
        $dataPagamento = str_replace('T', ' ', $dataPagamento);
        
        return (new DateTimeImmutable($dataPagamento))
            ->modify("-{$lembrarSegundos} seconds")
            ->format('Y-m-d H:i:s');
    }

    private function setValidationRules(): void
    {
        $tiposPermitidos = implode(';', [TipoLancamento::DESPESA->value, TipoLancamento::RECEITA->value]);
        
        $this->validator->validation_rules([
            'titulo'                 => 'required|min_len,3|max_len,160',
            'data_pagamento'         => 'required|date',
            'lembrar_antes_segundos' => 'integer|min_numeric,0',
            'canal_email'            => 'boolean',
            'canal_inapp'            => 'boolean',
            'valor_centavos'         => 'integer|min_numeric,0',
            'tipo'                   => "required|contains_list,{$tiposPermitidos}",
            'categoria_id'           => 'integer|min_numeric,1',
            'conta_id'               => 'integer|min_numeric,1',
            'recorrente'             => 'boolean',
            'recorrencia_freq'       => 'contains_list,diario;semanal;mensal;anual',
            'recorrencia_intervalo'  => 'integer|min_numeric,1',
            'recorrencia_fim'        => 'date',
        ]);

        $this->validator->filter_rules([
            'titulo'    => 'trim',
            'descricao' => 'trim',
            'moeda'     => 'trim|upper',
        ]);
    }

    private function prepararDadosAgendamento(array $data): array
    {
        $valorCentavos = $data['valor_centavos'] ?? null;
        
        if ($valorCentavos === null || $valorCentavos === '') {
            $valorCentavos = $this->moneyToCents($data['valor'] ?? $data['agValor'] ?? null);
        }

        $lembrarSegundos = (int) ($data['lembrar_antes_segundos'] ?? 0);
        $proximaExecucao = $this->calcularProximaExecucao($data['data_pagamento'], $lembrarSegundos);

        return [
            'user_id'                => $this->getUserId(),
            'conta_id'               => $data['conta_id'] ?? null,
            'categoria_id'           => $data['categoria_id'] ?? null,
            'titulo'                 => $data['titulo'],
            'descricao'              => $data['descricao'] ?? null,
            'tipo'                   => $data['tipo'],
            'valor_centavos'         => (int) $valorCentavos,
            'moeda'                  => $data['moeda'] ?? 'BRL',
            'data_pagamento'         => $data['data_pagamento'],
            'proxima_execucao'       => $proximaExecucao,
            'lembrar_antes_segundos' => $lembrarSegundos,
            'canal_email'            => $this->boolFromMixed($data['canal_email'] ?? false),
            'canal_inapp'            => $this->boolFromMixed($data['canal_inapp'] ?? true),
            'recorrente'             => $this->boolFromMixed($data['recorrente'] ?? false),
            'recorrencia_freq'       => $data['recorrencia_freq'] ?? null,
            'recorrencia_intervalo'  => $data['recorrencia_intervalo'] ?? null,
            'recorrencia_fim'        => $data['recorrencia_fim'] ?? null,
            'status'                 => AgendamentoStatus::PENDENTE->value,
        ];
    }

    public function store(): void
    {
        $this->requireAuthApi();
        
        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try {
            $data = $this->validator->sanitize($_POST);
            $data = $this->normalizeDataPagamento($data);
            $this->setValidationRules();

            if (!$this->validator->run($data)) {
                $errors = $this->validator->get_errors_array();

                LogService::warning('Falha de validação ao criar agendamento.', [
                    'errors' => $errors,
                    'user_id' => $this->getUserId()
                ]);

                Response::validationError($errors);
                return;
            }

            $dadosAgendamento = $this->prepararDadosAgendamento($data);
            $agendamento = Agendamento::create($dadosAgendamento);

            Response::success(['agendamento' => $agendamento]);
            
        } catch (Throwable $e) {
            LogService::error('Erro inesperado ao criar agendamento.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->getUserId()
            ]);
            
            Response::error('Erro ao processar sua solicitação.', 500);
        }
    }

    public function index(): void
    {
        $this->requireAuthApi();
        
        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try {
            $agendamentos = Agendamento::with(['categoria:id,nome', 'conta:id,nome'])
                ->where('user_id', $this->getUserId())
                ->whereIn('status', [AgendamentoStatus::PENDENTE->value, AgendamentoStatus::CANCELADO->value])
                ->orderBy('data_pagamento', 'asc')
                ->limit(100)
                ->get();

            Response::success(['itens' => $agendamentos]);
            
        } catch (Throwable $e) {
            LogService::error('Erro inesperado ao listar agendamentos.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->getUserId()
            ]);
            
            Response::error('Erro ao buscar agendamentos.', 500);
        }
    }

    public function update(int $id): void
    {
        $this->requireAuthApi();

        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try {
            $agendamento = $this->buscarAgendamentoOuFalhar($id);
            if (!$agendamento) {
                return;
            }

            if ($agendamento->status !== AgendamentoStatus::PENDENTE->value) {
                Response::error('Somente agendamentos pendentes podem ser editados.', 400);
                return;
            }

            $data = $this->validator->sanitize($_POST);
            $data = $this->normalizeDataPagamento($data);
            $this->setValidationRules();

            if (!$this->validator->run($data)) {
                $errors = $this->validator->get_errors_array();

                LogService::warning('Falha de validaÇõÇœo ao atualizar agendamento.', [
                    'errors' => $errors,
                    'user_id' => $this->getUserId(),
                    'agendamento_id' => $id,
                ]);

                Response::validationError($errors);
                return;
            }

            $dadosAgendamento = $this->prepararDadosAgendamento($data);

            $payload = [
                'titulo'                 => $dadosAgendamento['titulo'],
                'data_pagamento'         => $dadosAgendamento['data_pagamento'],
                'lembrar_antes_segundos' => $dadosAgendamento['lembrar_antes_segundos'],
                'tipo'                   => $dadosAgendamento['tipo'],
                'categoria_id'           => $dadosAgendamento['categoria_id'],
                'conta_id'               => $dadosAgendamento['conta_id'],
                'valor_centavos'         => $dadosAgendamento['valor_centavos'],
                'descricao'              => $dadosAgendamento['descricao'],
                'recorrente'             => $dadosAgendamento['recorrente'],
                'canal_inapp'            => $dadosAgendamento['canal_inapp'],
                'canal_email'            => $dadosAgendamento['canal_email'],
                'proxima_execucao'       => $dadosAgendamento['proxima_execucao'],
                'moeda'                  => $dadosAgendamento['moeda'] ?? 'BRL',
            ];

            $agendamento->update($payload);
            $agendamento->refresh()->load(['categoria:id,nome', 'conta:id,nome']);

            Response::success(['agendamento' => $agendamento]);
        } catch (Throwable $e) {
            LogService::error('Erro inesperado ao atualizar agendamento.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'agendamento_id' => $id,
                'user_id' => $this->getUserId()
            ]);

            Response::error('Erro ao atualizar agendamento.', 500);
        }
    }

    private function buscarAgendamentoOuFalhar(int $id): ?Agendamento
    {
        $agendamento = Agendamento::where('user_id', $this->getUserId())
            ->where('id', $id)
            ->first();

        if (!$agendamento) {
            LogService::warning('Agendamento não encontrado.', [
                'agendamento_id' => $id,
                'user_id' => $this->getUserId()
            ]);
            
            Response::notFound('Agendamento não encontrado.');
        }

        return $agendamento;
    }

    private function validarStatus(string $statusString, int $agendamentoId): ?AgendamentoStatus
    {
        try {
            return AgendamentoStatus::from(strtolower(trim($statusString)));
            
        } catch (ValueError $e) {
            $statusPermitidos = implode(', ', array_column(AgendamentoStatus::cases(), 'value'));
            
            LogService::warning('Status inválido para agendamento.', [
                'status_enviado' => $statusString,
                'error' => $e->getMessage(),
                'agendamento_id' => $agendamentoId,
                'user_id' => $this->getUserId()
            ]);

            Response::validationError([
                'status' => "Status inválido. Valores permitidos: {$statusPermitidos}"
            ]);
            
            return null;
        }
    }

    private function processarConclusao(Agendamento $agendamento, string $statusAnterior): array
    {
        $payload = [
            'status' => AgendamentoStatus::CONCLUIDO->value,
            'concluido_em' => date('Y-m-d H:i:s'),
        ];

        $lancamento = null;

        if ($statusAnterior !== AgendamentoStatus::CONCLUIDO->value) {
            try {
                $lancamento = $this->service->createLancamentoFromAgendamento($agendamento);
                
            } catch (Throwable $e) {
                LogService::error('Falha ao gerar lançamento do agendamento.', [
                    'error' => $e->getMessage(),
                    'agendamento_id' => $agendamento->id,
                    'user_id' => $this->getUserId()
                ]);
                
                Response::error('Falha ao gerar lançamento: ' . $e->getMessage(), 500);
                exit;
            }
        }

        return ['payload' => $payload, 'lancamento' => $lancamento];
    }

    public function updateStatus(int $id): void
    {
        $this->requireAuthApi();
        
        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try {
            $agendamento = $this->buscarAgendamentoOuFalhar($id);
            if (!$agendamento) {
                return;
            }

            $novoStatus = $this->validarStatus($_POST['status'] ?? '', $id);
            if (!$novoStatus) {
                return;
            }

            $statusAnterior = $agendamento->status;
            $lancamento = null;

            if ($novoStatus === AgendamentoStatus::CONCLUIDO) {
                $resultado = $this->processarConclusao($agendamento, $statusAnterior);
                $payload = $resultado['payload'];
                $lancamento = $resultado['lancamento'];
                
            } else {
                $payload = [
                    'status' => $novoStatus->value,
                    'concluido_em' => null,
                ];
            }

            $agendamento->update($payload);
            $agendamento->refresh()->load(['categoria:id,nome', 'conta:id,nome']);

            Response::success([
                'agendamento' => $agendamento,
                'lancamento' => $lancamento,
            ]);
            
        } catch (Throwable $e) {
            LogService::error('Erro inesperado no updateStatus do agendamento.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'agendamento_id' => $id,
                'user_id' => $this->getUserId()
            ]);
            
            Response::error('Erro ao atualizar agendamento.', 500);
        }
    }

    public function cancel(int $id): void
    {
        $this->requireAuthApi();
        
        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try {
            $agendamento = $this->buscarAgendamentoOuFalhar($id);
            if (!$agendamento) {
                return;
            }

            if ($agendamento->status !== AgendamentoStatus::PENDENTE->value) {
                Response::error('Somente agendamentos pendentes podem ser cancelados.', 400);
                return;
            }

            $agendamento->update([
                'status' => AgendamentoStatus::CANCELADO->value,
                'concluido_em' => null,
            ]);

            $agendamento->refresh()->load(['categoria:id,nome', 'conta:id,nome']);

            Response::success(['agendamento' => $agendamento]);
            
        } catch (Throwable $e) {
            LogService::error('Erro inesperado ao cancelar agendamento.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'agendamento_id' => $id,
                'user_id' => $this->getUserId()
            ]);
            
            Response::error('Erro ao cancelar agendamento.', 500);
        }
    }

    public function restore(int $id): void
    {
        $this->requireAuthApi();

        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try {
            $agendamento = $this->buscarAgendamentoOuFalhar($id);
            if (!$agendamento) {
                return;
            }

            if ($agendamento->status !== AgendamentoStatus::CANCELADO->value) {
                Response::error('Somente agendamentos cancelados podem ser reativados.', 400);
                return;
            }

            $agendamento->update([
                'status' => AgendamentoStatus::PENDENTE->value,
                'concluido_em' => null,
            ]);

            $agendamento->refresh()->load(['categoria:id,nome', 'conta:id,nome']);

            Response::success(['agendamento' => $agendamento]);
        } catch (Throwable $e) {
            LogService::error('Erro inesperado ao reativar agendamento.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'agendamento_id' => $id,
                'user_id' => $this->getUserId()
            ]);

            Response::error('Erro ao reativar agendamento.', 500);
        }
    }
}
