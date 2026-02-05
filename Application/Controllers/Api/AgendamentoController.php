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

    /**
     * Obter dados da requisição (JSON ou POST)
     */
    private function getRequestData(): array
    {
        // Tentar primeiro obter JSON do corpo da requisição
        $json = file_get_contents('php://input');
        if (!empty($json)) {
            $data = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $data;
            }
        }

        // Fallback para $_POST
        return $_POST;
    }

    public function store(): void
    {
        $this->requireAuthApi();

        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try {
            // Usar getRequestData() para suportar tanto JSON quanto FormData
            $data = $this->validator->sanitize($this->getRequestData());
            $data = $this->normalizeDataPagamento($data);

            LogService::info('Dados recebidos para criar agendamento.', [
                'data' => array_diff_key($data, ['csrf_token' => 1, '_token' => 1]),
                'user_id' => $this->getUserId()
            ]);

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
            $dtoArray = $dto->toArray();

            // Verificar se já existe um agendamento idêntico criado nos últimos 10 segundos
            // para evitar duplicação por retry ou duplo clique
            $recentDuplicate = Agendamento::where('user_id', $this->getUserId())
                ->where('titulo', $dtoArray['titulo'])
                ->where('valor_centavos', $dtoArray['valor_centavos'])
                ->where('data_pagamento', $dtoArray['data_pagamento'])
                ->where('tipo', $dtoArray['tipo'])
                ->where('created_at', '>=', date('Y-m-d H:i:s', time() - 10))
                ->first();

            if ($recentDuplicate) {
                LogService::warning('Agendamento duplicado detectado e ignorado.', [
                    'existing_id' => $recentDuplicate->id,
                    'user_id' => $this->getUserId()
                ]);

                // Retornar o agendamento existente como se fosse criado agora
                Response::success(['agendamento' => $recentDuplicate, 'duplicate_prevented' => true]);
                return;
            }

            $agendamento = $this->agendamentoRepo->create($dtoArray);

            LogService::info('Agendamento criado com sucesso.', [
                'agendamento_id' => $agendamento->id,
                'user_id' => $this->getUserId()
            ]);

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
            $query = Agendamento::with(['categoria:id,nome', 'conta:id,nome'])
                ->where('user_id', $this->getUserId())
                ->whereIn('status', [
                    AgendamentoStatus::PENDENTE->value,
                    AgendamentoStatus::NOTIFICADO->value,
                    AgendamentoStatus::CANCELADO->value
                ]);

            // Filtrar por mês se fornecido (formato: YYYY-MM)
            $month = $_GET['month'] ?? null;
            if ($month && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                $startDate = $month . '-01 00:00:00';
                $endDate = date('Y-m-t 23:59:59', strtotime($startDate));
                $query->whereBetween('data_pagamento', [$startDate, $endDate]);
            }

            $agendamentos = $query->orderBy('data_pagamento', 'asc')
                ->limit(100)
                ->get();

            // Adicionar status dinâmico a cada agendamento
            $agendamentos = $agendamentos->map(function ($agendamento) {
                $agendamentoArray = $agendamento->toArray();
                $agendamentoArray['status_dinamico'] = $this->service->calcularStatusDinamico($agendamento);
                return $agendamentoArray;
            });

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

    /**
     * Buscar um agendamento específico por ID
     */
    public function show(int $id): void
    {
        $this->requireAuthApi();

        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try {
            $agendamento = Agendamento::with(['categoria:id,nome', 'conta:id,nome'])
                ->where('user_id', $this->getUserId())
                ->where('id', $id)
                ->first();

            if (!$agendamento) {
                Response::notFound('Agendamento não encontrado.');
                return;
            }

            $agendamentoArray = $agendamento->toArray();
            $agendamentoArray['status_dinamico'] = $this->service->calcularStatusDinamico($agendamento);

            Response::success($agendamentoArray);
        } catch (Throwable $e) {
            LogService::error('Erro ao buscar agendamento.', [
                'error' => $e->getMessage(),
                'agendamento_id' => $id,
                'user_id' => $this->getUserId()
            ]);

            Response::error('Erro ao buscar agendamento.', 500);
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

    /**
     * EXECUTAR AGENDAMENTO - Cria lançamento e gerencia recorrência
     * 
     * POST /api/agendamentos/{id}/executar
     * 
     * Body (opcional):
     * - conta_id: int - Conta para debitar/creditar o lançamento
     * - forma_pagamento: string - Forma de pagamento (dinheiro, pix, cartao_debito, etc)
     */
    public function executar(int $id): void
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

            // Permitir executar agendamentos pendentes OU notificados (que receberam lembrete mas ainda não foram pagos)
            $statusExecutaveis = [AgendamentoStatus::PENDENTE->value, AgendamentoStatus::NOTIFICADO->value];
            if (!in_array($agendamento->status, $statusExecutaveis, true)) {
                Response::error('Somente agendamentos pendentes podem ser executados.', 400);
                return;
            }

            // Proteção contra execução duplicada (requisição duplicada/retry)
            // Se foi executado nos últimos 5 segundos, ignorar
            if ($agendamento->concluido_em) {
                $ultimaExecucao = strtotime($agendamento->concluido_em);
                if (time() - $ultimaExecucao < 5) {
                    LogService::warning('Execução duplicada detectada e ignorada', [
                        'agendamento_id' => $id,
                        'concluido_em' => $agendamento->concluido_em,
                        'user_id' => $this->getUserId()
                    ]);
                    // Retornar sucesso mesmo assim para não confundir o frontend
                    $agendamento->refresh()->load(['categoria:id,nome', 'conta:id,nome']);
                    Response::success([
                        'message' => 'Agendamento já foi executado.',
                        'agendamento' => $agendamento->toArray(),
                        'duplicate_prevented' => true,
                    ]);
                    return;
                }
            }

            // Obter dados opcionais do request
            $data = $this->getRequestData();
            $contaId = !empty($data['conta_id']) ? (int) $data['conta_id'] : null;
            $formaPagamento = !empty($data['forma_pagamento']) ? trim($data['forma_pagamento']) : null;

            // Executar agendamento (cria lançamento + gerencia recorrência/parcelamento)
            $resultado = $this->service->executarAgendamento($agendamento, $contaId, $formaPagamento);

            // Calcular status dinâmico para resposta
            $statusDinamico = $this->service->calcularStatusDinamico($resultado['agendamento']);
            $agendamentoData = $resultado['agendamento']->toArray();
            $agendamentoData['status_dinamico'] = $statusDinamico;

            // Montar mensagem apropriada
            if ($resultado['parcelado'] ?? false) {
                if ($resultado['finalizado'] ?? false) {
                    $mensagem = "Última parcela paga! ({$resultado['parcela_paga']}/{$resultado['total_parcelas']}) Agendamento finalizado.";
                } else {
                    $mensagem = "Parcela {$resultado['parcela_paga']}/{$resultado['total_parcelas']} paga! Próxima: " . date('d/m/Y', strtotime($resultado['proximaData']));
                }
            } elseif ($resultado['recorrente']) {
                $mensagem = 'Lançamento criado! Próxima ocorrência agendada para ' . date('d/m/Y', strtotime($resultado['proximaData']));
            } else {
                $mensagem = 'Agendamento executado com sucesso!';
            }

            Response::success([
                'message' => $mensagem,
                'agendamento' => $agendamentoData,
                'lancamento' => $resultado['lancamento'],
                'proxima_data' => $resultado['proximaData'],
                'recorrente' => $resultado['recorrente'],
                'parcelado' => $resultado['parcelado'] ?? false,
                'parcela_paga' => $resultado['parcela_paga'] ?? null,
                'total_parcelas' => $resultado['total_parcelas'] ?? null,
                'finalizado' => $resultado['finalizado'] ?? false,
            ]);

            LogService::info('Agendamento executado via endpoint', [
                'agendamento_id' => $id,
                'recorrente' => $resultado['recorrente'],
                'parcelado' => $resultado['parcelado'] ?? false,
                'conta_id' => $contaId,
                'forma_pagamento' => $formaPagamento,
                'user_id' => $this->getUserId(),
            ]);
        } catch (Throwable $e) {
            LogService::error('Erro ao executar agendamento.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'agendamento_id' => $id,
                'user_id' => $this->getUserId()
            ]);

            Response::error('Erro ao executar agendamento: ' . $e->getMessage(), 500);
        }
    }
}
