<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\InstituicaoFinanceira;
use Application\Services\ContaService;
use Application\DTO\CreateContaDTO;
use Application\DTO\UpdateContaDTO;
use Application\Middlewares\CsrfMiddleware;

class ContasControllerV2
{
    private ContaService $service;

    public function __construct()
    {
        $this->service = new ContaService();
    }

    private function getRequestPayload(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        if (empty($data) && strtolower($_SERVER['REQUEST_METHOD'] ?? '') === 'post') {
            $data = $_POST;
        }
        return $data;
    }

    private function addCsrfToResponse(array $response): array
    {
        $response['csrf_token'] = CsrfMiddleware::generateToken('default');
        return $response;
    }

    /**
     * GET /api/v2/contas
     * Listar contas do usuário
     */
    public function index(): void
    {
        $userId = Auth::id();

        $archived = (int) ($_GET['archived'] ?? 0) === 1;
        $onlyActive = (int) ($_GET['only_active'] ?? ($archived ? 0 : 1)) === 1;
        $withBalances = (int) ($_GET['with_balances'] ?? 0) === 1;
        $month = trim((string) ($_GET['month'] ?? date('Y-m')));

        $contas = $this->service->listarContas(
            userId: $userId,
            arquivadas: $archived,
            apenasAtivas: $onlyActive,
            comSaldos: $withBalances,
            mes: $month
        );

        Response::json($contas);
    }

    /**
     * POST /api/v2/contas
     * Criar nova conta
     */
    public function store(): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();

        // LOG: Início da criação
        \Application\Services\LogService::info('📥 INÍCIO - Criação de conta', [
            'user_id' => $userId,
            'request_id' => uniqid('req_'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100),
            'data_recebida' => $data
        ]);

        $dto = CreateContaDTO::fromArray($data, $userId);
        
        // LOG: DTO criado
        \Application\Services\LogService::info('📋 DTO criado para nova conta', [
            'user_id' => $userId,
            'nome' => $dto->nome,
            'instituicao_id' => $dto->instituicaoFinanceiraId,
            'tipo_conta' => $dto->tipoConta,
            'saldo_inicial' => $dto->saldoInicial
        ]);
        
        $resultado = $this->service->criarConta($dto);

        if (!$resultado['success']) {
            // LOG: Erro na criação
            \Application\Services\LogService::warning('❌ ERRO ao criar conta', [
                'user_id' => $userId,
                'erro' => $resultado['message'],
                'errors' => $resultado['errors'] ?? null
            ]);
            
            Response::json([
                'status' => 'error',
                'message' => $resultado['message'],
                'errors' => $resultado['errors'] ?? null,
            ], 422);
            return;
        }

        // LOG: Conta criada com sucesso
        \Application\Services\LogService::info('✅ SUCESSO - Conta criada', [
            'user_id' => $userId,
            'conta_id' => $resultado['id'],
            'nome' => $resultado['data']['nome'] ?? null
        ]);

        Response::json($this->addCsrfToResponse([
            'success' => true,
            'ok' => true,
            'id' => $resultado['id'],
            'data' => $resultado['data'],
        ]), 201);
    }

    /**
     * PUT /api/v2/contas/{id}
     * Atualizar conta
     */
    public function update(int $id): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();

        $dto = UpdateContaDTO::fromArray($data);
        $resultado = $this->service->atualizarConta($id, $userId, $dto);

        if (!$resultado['success']) {
            Response::json([
                'status' => 'error',
                'message' => $resultado['message'],
                'errors' => $resultado['errors'] ?? null,
            ], isset($resultado['message']) && str_contains($resultado['message'], 'não encontrada') ? 404 : 422);
            return;
        }

        Response::json($this->addCsrfToResponse([
            'success' => true,
            'ok' => true,
            'data' => $resultado['data'],
        ]));
    }

    /**
     * POST /api/v2/contas/{id}/archive
     * Arquivar conta
     */
    public function archive(int $id): void
    {
        $userId = Auth::id();
        $resultado = $this->service->arquivarConta($id, $userId);

        if (!$resultado['success']) {
            Response::json(['status' => 'error', 'message' => $resultado['message']], 404);
            return;
        }

        Response::json($resultado);
    }

    /**
     * POST /api/v2/contas/{id}/restore
     * Restaurar conta
     */
    public function restore(int $id): void
    {
        $userId = Auth::id();
        $resultado = $this->service->restaurarConta($id, $userId);

        if (!$resultado['success']) {
            Response::json(['status' => 'error', 'message' => $resultado['message']], 404);
            return;
        }

        Response::json($resultado);
    }

    /**
     * DELETE /api/v2/contas/{id}
     * Excluir conta
     */
    public function destroy(int $id): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();
        $force = (int) ($_GET['force'] ?? 0) === 1 || filter_var($data['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $resultado = $this->service->excluirConta($id, $userId, $force);

        if (!$resultado['success']) {
            $statusCode = isset($resultado['requires_confirmation']) && $resultado['requires_confirmation'] ? 422 : 404;
            Response::json([
                'status' => $resultado['requires_confirmation'] ?? false ? 'confirm_delete' : 'error',
                'message' => $resultado['message'],
                'counts' => $resultado['counts'] ?? null,
            ], $statusCode);
            return;
        }

        Response::json($resultado);
    }

    /**
     * GET /api/v2/instituicoes
     * Listar instituições financeiras disponíveis
     */
    public function instituicoes(): void
    {
        $tipo = isset($_GET['tipo']) ? trim((string) $_GET['tipo']) : null;

        $query = InstituicaoFinanceira::ativas();

        if ($tipo) {
            $query->porTipo($tipo);
        }

        $instituicoes = $query->orderBy('nome')->get()->map(function ($inst) {
            return [
                'id' => $inst->id,
                'nome' => $inst->nome,
                'codigo' => $inst->codigo,
                'tipo' => $inst->tipo,
                'cor_primaria' => $inst->cor_primaria,
                'cor_secundaria' => $inst->cor_secundaria,
                'logo_url' => $inst->logo_url,
            ];
        });

        Response::json($instituicoes);
    }
}
