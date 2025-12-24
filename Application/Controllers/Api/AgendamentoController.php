<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Agendamento;
use Application\Services\AgendamentoService;
use Application\Services\LogService;
use Application\Lib\Auth;
use Application\Enums\AgendamentoStatus;
use Application\Validators\AgendamentoValidator;
use Application\DTO\CreateAgendamentoDTO;
use Application\DTO\UpdateAgendamentoDTO;
use Application\Repositories\AgendamentoRepository;
use GUMP;
use DateTimeImmutable;
use Throwable;
use ValueError;

class AgendamentoController extends BaseController
{
    private readonly GUMP $validator;
    private readonly AgendamentoService $service;
    private readonly AgendamentoRepository $agendamentoRepo;

    public function __construct()
    {
        parent::__construct();
        $this->validator = new GUMP();
        $this->service = new AgendamentoService();
        $this->agendamentoRepo = new AgendamentoRepository();
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

    public function store(): void
    {
        $this->requireAuthApi();
        
        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try {
            $data = $this->validator->sanitize($_POST);
            $data = $this->normalizeDataPagamento($data);

            // Validar com AgendamentoValidator
            $errors = AgendamentoValidator::validateCreate($data);
            if (!empty($errors)) {
                LogService::warning('Falha de validação ao criar agendamento.', [
                    'errors' => $errors,
                    'user_id' => $this->getUserId()
                ]);

                Response::validationError($errors);
                return;
            }

            // Criar DTO e salvar
            $dto = CreateAgendamentoDTO::fromRequest($this->getUserId(), $data);
            $agendamento = $this->agendamentoRepo->create($dto->toArray());

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

            // Validar com AgendamentoValidator
            $errors = AgendamentoValidator::validateUpdate($data);
            if (!empty($errors)) {
                LogService::warning('Falha de validação ao atualizar agendamento.', [
                    'errors' => $errors,
                    'user_id' => $this->getUserId(),
                    'agendamento_id' => $id,
                ]);

                Response::validationError($errors);
                return;
            }

            // Criar DTO
            $dto = UpdateAgendamentoDTO::fromRequest($data);

            // Recalcular próxima execução se necessário
            $dataPagamento = $dto->data_pagamento ?? $agendamento->data_pagamento;
            $lembrarSegundos = $dto->lembrar_antes_segundos ?? $agendamento->lembrar_antes_segundos;
            
            if ($dto->data_pagamento !== null || $dto->lembrar_antes_segundos !== null) {
                $dto = $dto->withProximaExecucao($dataPagamento, $lembrarSegundos);
            }

            // Atualizar
            $agendamento->update($dto->toArray());
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
