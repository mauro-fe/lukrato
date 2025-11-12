<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Agendamento;
use Application\Services\AgendamentoService;
use Application\Lib\Auth;
use Application\Services\LogService;
use GUMP;


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

// --- Controller Refatorado ---

class AgendamentoController extends BaseController
{
    // Injecao de dependencia simplificada via Construtor e Propriedades Promovidas (PHP 8.0)
    public function __construct(
        private readonly GUMP $validator = new GUMP(),
        private readonly AgendamentoService $service = new AgendamentoService(),
        // Propriedade herdada ou injetada no BaseController (exemplo)
        // private int $userId
    ) {
        // Inicializacoes do BaseController ou outras dependencias se necessario
        parent::__construct();
    }

    /**
     * Verifica se o usuário logado pode acessar o módulo de agendamentos.
     */
    private function ensureSchedulingAccess(): bool
    {
        $user = Auth::user();
        if (!$user || (method_exists($user, 'podeAcessar') && !$user->podeAcessar('scheduling'))) {
            Response::forbidden('Agendamentos são exclusivos do plano Pro.');
            return false;
        }
        return true;
    }
    /**
     * Converte um valor monetário em string (ex: "1.234,56") para centavos (int).
     */
    private function moneyToCents(?string $str): ?int
    {
        if (empty($str)) {
            return null;
        }

        // 1. Remove tudo que não for dígito, ponto ou vírgula.
        $s = preg_replace('/[^\d,.-]/', '', $str);

        // 2. Padroniza para formato de ponto flutuante americano (ponto como separador decimal).
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            // Caso com ponto (milhar) e vírgula (decimal): remove ponto, troca vírgula por ponto.
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (strpos($s, ',') !== false) {
            // Caso com apenas vírgula (decimal): troca vírgula por ponto.
            $s = str_replace(',', '.', $s);
        }

        // 3. Converte para float e depois para int (centavos).
        $val = (float) $s;
        return (int) round($val * 100);
    }

    /**
     * Converte um valor misto (string, int, bool) em booleano de forma robusta.
     *
     * @param mixed $value Valor recebido do formulário (string, int, bool etc).
     * @return bool
     */
    private function boolFromMixed(mixed $value): bool
    {
        // Usa a conversão nativa do PHP, que é mais clara.
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? !empty($value);
    }

    /**
     * Obtém o ID do usuário autenticado.
     * Usa o operador Nullsafe '?' para lidar com a propriedade 'userId' (PHP 8.0).
     */
    private function getUserId(): int
    {
        // Simplificado: Assumindo que o BaseController ou Auth já define $this->userId como int.
        // Se a lógica anterior fosse necessária, ficaria:
        // return (int) ($this->userId ?? Auth::user()->id);

        // Mantendo a lógica original, mas refatorando para clareza:
        return (int) (property_exists($this, 'userId') ? $this->userId : Auth::user()->id);
    }

    // --- Métodos de API (Endpoints) ---
    // -----------------------------------

    /**
     * Cria um novo agendamento.
     */
    public function store(): void
    {
        $this->requireAuthApi();
        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try { // <-- ADICIONADO TRY
            $data = $_POST;
            $data = $this->validator->sanitize($data);

            $this->validator->validation_rules([
                'titulo'                 => 'required|min_len,3|max_len,160',
                'data_pagamento'         => 'required|date',
                'lembrar_antes_segundos' => 'integer|min_numeric,0',
                'canal_email'            => 'boolean',
                'canal_inapp'            => 'boolean',
                'valor_centavos'         => 'integer|min_numeric,0',
                'tipo'                   => 'required|contains_list,' . TipoLancamento::DESPESA->value . ';' . TipoLancamento::RECEITA->value,
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

            if (!$this->validator->run($data)) {
                $errors = $this->validator->get_errors_array();

                // --- LOG (WARNING) ---
                LogService::warning('Falha de validação ao criar agendamento.', [
                    'errors' => $errors,
                    'user_id' => $this->getUserId()
                ]);

                Response::validationError($errors);
                return;
            }

            // 1. Calcula o valor em centavos
            $valorCentavos = $data['valor_centavos'] ?? null;
            if ($valorCentavos === null || $valorCentavos === '') {
                $valorCentavos = $this->moneyToCents($data['valor'] ?? $data['agValor'] ?? null);
            }
            $valorCentavos = (int) $valorCentavos; // Garante o tipo INT

            // 2. Calcula a próxima execução
            $dataPagamento = str_replace('T', ' ', (string) $data['data_pagamento']);
            $lembrarSeg = (int) ($data['lembrar_antes_segundos'] ?? 0);

            // Uso de DateTime nativo do PHP para manipulação de data/hora é mais robusto
            $proximaExec = (new \DateTimeImmutable($dataPagamento))
                ->modify("-$lembrarSeg seconds")
                ->format('Y-m-d H:i:s');


            // 3. Criação do Agendamento
            $novo = Agendamento::create([
                'user_id'                => $this->getUserId(),
                'conta_id'               => $data['conta_id']           ?? null,
                'categoria_id'           => $data['categoria_id']       ?? null,
                'titulo'                 => $data['titulo'],
                'descricao'              => $data['descricao']          ?? null,
                'tipo'                   => $data['tipo'],
                'valor_centavos'         => $valorCentavos,
                'moeda'                  => $data['moeda']              ?? 'BRL',
                'data_pagamento'         => $dataPagamento,
                'proxima_execucao'       => $proximaExec,
                'lembrar_antes_segundos' => $lembrarSeg,
                'canal_email'            => $this->boolFromMixed($data['canal_email'] ?? false),
                'canal_inapp'            => $this->boolFromMixed($data['canal_inapp'] ?? true),
                'recorrente'             => $this->boolFromMixed($data['recorrente'] ?? false),
                'recorrencia_freq'       => $data['recorrencia_freq']   ?? null,
                'recorrencia_intervalo'  => $data['recorrencia_intervalo'] ?? null,
                'recorrencia_fim'        => $data['recorrencia_fim']    ?? null,
                'status'                 => AgendamentoStatus::PENDENTE->value,
            ]);

            Response::success(['agendamento' => $novo]);
        } catch (\Throwable $e) { // <-- ADICIONADO CATCH
            // --- LOG (ERROR) ---
            LogService::error('Erro inesperado ao criar agendamento.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->getUserId()
            ]);
            Response::error('Erro ao processar sua solicitação.', 500);
        }
    }

    /**
     * Lista os agendamentos do usuário.
     */
    public function index(): void
    {
        $this->requireAuthApi();
        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try { // <-- ADICIONADO TRY
            $itens = Agendamento::with(['categoria:id,nome', 'conta:id,nome'])
                ->where('user_id', $this->getUserId())
                ->orderBy('data_pagamento', 'asc')
                ->limit(100)
                ->get();

            Response::success(['itens' => $itens]);
        } catch (\Throwable $e) { // <-- ADICIONADO CATCH
            // --- LOG (ERROR) ---
            LogService::error('Erro inesperado ao listar agendamentos.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->getUserId()
            ]);
            Response::error('Erro ao buscar agendamentos.', 500);
        }
    }

    /**
     * Atualiza o status de um agendamento.
     *
     * @param int $id O ID do agendamento.
     */
    public function updateStatus(int $id): void
    {
        $this->requireAuthApi();
        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try { // <-- ADICIONADO TRY GLOBAL
            $agendamento = Agendamento::where('user_id', $this->getUserId())
                ->where('id', $id)
                ->first();

            if (!$agendamento) {
                // --- LOG (WARNING) ---
                LogService::warning('Agendamento não encontrado para updateStatus.', [
                    'agendamento_id' => $id,
                    'user_id' => $this->getUserId()
                ]);
                Response::notFound('Agendamento nao encontrado.');
                return;
            }

            $newStatusString = strtolower(trim($_POST['status'] ?? ''));

            // Validação usando o Enum
            try {
                $newStatus = AgendamentoStatus::from($newStatusString);
            } catch (\ValueError $e) { // <-- Este é um erro de validação
                // --- LOG (WARNING) ---
                LogService::warning('Status inválido para agendamento.', [
                    'status_enviado' => $newStatusString,
                    'error' => $e->getMessage(),
                    'agendamento_id' => $id,
                    'user_id' => $this->getUserId()
                ]);
                Response::validationError(['status' => 'Status invalido. Status permitidos: ' . implode(', ', array_column(AgendamentoStatus::cases(), 'value'))]);
                return;
            }

            $previousStatus = $agendamento->status;
            $lancamento = null;
            $payload = ['status' => $newStatus->value];

            if ($newStatus === AgendamentoStatus::CONCLUIDO) {
                $payload['concluido_em'] = date('Y-m-d H:i:s');

                // Lógica para criar lançamento, movida para o Service
                if ($previousStatus !== AgendamentoStatus::CONCLUIDO->value) {
                    try {
                        // Chamada ao Service (AgendamentoService)
                        $lancamento = $this->service->createLancamentoFromAgendamento($agendamento);
                    } catch (\RuntimeException $e) { // <-- Erro crítico específico
                        // --- LOG (ERROR) ---
                        LogService::error('Falha ao gerar lançamento do agendamento.', [
                            'error' => $e->getMessage(),
                            'agendamento_id' => $id,
                            'user_id' => $this->getUserId()
                        ]);
                        Response::error('Falha ao gerar lancamento: ' . $e->getMessage(), 500);
                        return;
                    }
                }
            } elseif ($newStatus === AgendamentoStatus::CANCELADO || $newStatus === AgendamentoStatus::PENDENTE) {
                $payload['concluido_em'] = null;
            }

            $agendamento->update($payload);
            $agendamento->refresh()->load(['categoria:id,nome', 'conta:id,nome']);

            Response::success([
                'agendamento' => $agendamento,
                'lancamento'  => $lancamento,
            ]);
        } catch (\Throwable $e) { // <-- ADICIONADO CATCH GLOBAL
            // --- LOG (ERROR) ---
            LogService::error('Erro inesperado no updateStatus do agendamento.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'agendamento_id' => $id,
                'user_id' => $this->getUserId()
            ]);
            Response::error('Erro ao atualizar agendamento.', 500);
        }
    }

    /**
     * Cancela um agendamento. (Método duplicado, mas mantido para o endpoint `cancel`)
     *
     * @param int $id O ID do agendamento.
     */
    public function cancel(int $id): void
    {
        $this->requireAuthApi();
        if (!$this->ensureSchedulingAccess()) {
            return;
        }

        try { // <-- ADICIONADO TRY
            $agendamento = Agendamento::where('user_id', $this->getUserId())
                ->where('id', $id)
                ->first();

            if (!$agendamento) {
                // --- LOG (WARNING) ---
                LogService::warning('Agendamento não encontrado para cancelamento.', [
                    'agendamento_id' => $id,
                    'user_id' => $this->getUserId()
                ]);
                Response::notFound('Agendamento nao encontrado.');
                return;
            }

            // Uso do método updateStatus para consolidar a lógica de atualização
            $agendamento->update([
                'status'       => AgendamentoStatus::CANCELADO->value,
                'concluido_em' => null,
            ]);

            $agendamento->refresh()->load(['categoria:id,nome', 'conta:id,nome']);

            Response::success(['agendamento' => $agendamento]);
        } catch (\Throwable $e) { // <-- ADICIONADO CATCH
            // --- LOG (ERROR) ---
            LogService::error('Erro inesperado ao cancelar agendamento.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'agendamento_id' => $id,
                'user_id' => $this->getUserId()
            ]);
            Response::error('Erro ao cancelar agendamento.', 500);
        }
    }

    // O método 'createLancamentoFromAgendamento' foi movido para a classe AgendamentoService
}
