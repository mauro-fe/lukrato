<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\InstituicaoFinanceira;
use Application\Services\ContaService;
use Application\DTO\CreateContaDTO;
use Application\DTO\UpdateContaDTO;
use Application\Middlewares\CsrfMiddleware;
use Application\Services\LogService;
use Application\Services\PlanLimitService;


class ContasController
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
     * GET /api/contas
     * Listar contas do usuário
     */
    public function index(): void
    {
        try {
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
        } catch (\Throwable $e) {
            LogService::error('Erro ao listar contas', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            Response::json(['error' => 'Erro ao carregar contas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v2/contas
     * Criar nova conta
     */
    public function store(): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();

        // 🔒 VERIFICAR LIMITE DO PLANO
        $planLimitService = new PlanLimitService();
        $limitCheck = $planLimitService->canCreateConta($userId);

        if (!$limitCheck['allowed']) {
            LogService::warning('🚫 LIMITE - Tentativa de criar conta bloqueada', [
                'user_id' => $userId,
                'limite' => $limitCheck['limit'],
                'usado' => $limitCheck['used']
            ]);

            Response::json([
                'status' => 'error',
                'message' => $limitCheck['message'],
                'limit_reached' => true,
                'upgrade_url' => $limitCheck['upgrade_url'],
                'limit_info' => [
                    'limit' => $limitCheck['limit'],
                    'used' => $limitCheck['used'],
                    'remaining' => $limitCheck['remaining']
                ]
            ], 403);
            return;
        }

        // LOG: Início da criação
        LogService::info('📥 INÍCIO - Criação de conta', [
            'user_id' => $userId,
            'request_id' => uniqid('req_'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100),
            'data_recebida' => $data
        ]);

        $dto = CreateContaDTO::fromArray($data, $userId);

        // LOG: DTO criado
        LogService::info('📋 DTO criado para nova conta', [
            'user_id' => $userId,
            'nome' => $dto->nome,
            'instituicao_id' => $dto->instituicaoFinanceiraId,
            'tipo_conta' => $dto->tipoConta,
            'saldo_inicial' => $dto->saldoInicial
        ]);

        $resultado = $this->service->criarConta($dto);

        if (!$resultado['success']) {
            // LOG: Erro na criação
            LogService::warning('❌ ERRO ao criar conta', [
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
        LogService::info('✅ SUCESSO - Conta criada', [
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

        // LOG: Dados recebidos
        LogService::info('📝 INÍCIO - Atualização de conta', [
            'user_id' => $userId,
            'conta_id' => $id,
            'data_recebida' => $data,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'
        ]);

        $dto = UpdateContaDTO::fromArray($data);

        // LOG: DTO criado
        LogService::info('📋 DTO criado para atualização', [
            'dto_array' => $dto->toArray()
        ]);

        $resultado = $this->service->atualizarConta($id, $userId, $dto);

        if (!$resultado['success']) {
            LogService::warning('❌ ERRO ao atualizar conta', [
                'user_id' => $userId,
                'conta_id' => $id,
                'erro' => $resultado['message'],
                'errors' => $resultado['errors'] ?? null
            ]);

            Response::json([
                'status' => 'error',
                'message' => $resultado['message'],
                'errors' => $resultado['errors'] ?? null,
            ], isset($resultado['message']) && str_contains($resultado['message'], 'não encontrada') ? 404 : 422);
            return;
        }

        // LOG: Sucesso
        LogService::info('✅ SUCESSO - Conta atualizada', [
            'user_id' => $userId,
            'conta_id' => $id
        ]);

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
     * POST /api/accounts/{id}/delete
     * Exclusão permanente de conta (hard delete)
     * Alias para destroy com suporte a POST
     */
    public function hardDelete(int $id): void
    {
        $this->destroy($id);
    }

    /**
     * GET /api/contas/instituicoes
     * Listar instituições financeiras disponíveis
     */
    public function instituicoes(): void
    {
        try {
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
        } catch (\Throwable $e) {
            LogService::error('Erro ao listar instituições', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            Response::json(['error' => 'Erro ao carregar instituições: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/instituicoes
     * Criar nova instituição financeira personalizada
     */
    public function createInstituicao(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];

            // Validações
            if (empty($data['nome'])) {
                Response::json(['error' => 'Nome da instituição é obrigatório'], 400);
                return;
            }

            $nome = trim($data['nome']);
            $tipo = $data['tipo'] ?? 'outro';
            $corPrimaria = $data['cor_primaria'] ?? '#757575';
            $corSecundaria = $data['cor_secundaria'] ?? '#FFFFFF';

            // Gerar código único baseado no nome
            $codigo = $this->generateUniqueCode($nome);

            // Verificar se já existe com o mesmo nome
            $exists = InstituicaoFinanceira::where('nome', $nome)->exists();
            if ($exists) {
                Response::json(['error' => 'Já existe uma instituição com este nome'], 400);
                return;
            }

            // Criar a instituição
            $instituicao = InstituicaoFinanceira::create([
                'nome' => $nome,
                'codigo' => $codigo,
                'tipo' => $tipo,
                'cor_primaria' => $corPrimaria,
                'cor_secundaria' => $corSecundaria,
                'logo_path' => '/assets/img/banks/outro.svg', // Logo padrão
                'ativo' => true,
            ]);

            Response::json([
                'success' => true,
                'message' => 'Instituição criada com sucesso!',
                'data' => [
                    'id' => $instituicao->id,
                    'nome' => $instituicao->nome,
                    'codigo' => $instituicao->codigo,
                    'tipo' => $instituicao->tipo,
                    'cor_primaria' => $instituicao->cor_primaria,
                    'cor_secundaria' => $instituicao->cor_secundaria,
                    'logo_url' => $instituicao->logo_url,
                ]
            ], 201);
        } catch (\Throwable $e) {
            LogService::error('Erro ao criar instituição', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            Response::json(['error' => 'Erro ao criar instituição: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Gerar código único para instituição
     */
    private function generateUniqueCode(string $nome): string
    {
        // Converter para minúsculas e remover acentos
        $codigo = strtolower(trim($nome));
        $codigo = preg_replace('/[áàãâä]/u', 'a', $codigo);
        $codigo = preg_replace('/[éèêë]/u', 'e', $codigo);
        $codigo = preg_replace('/[íìîï]/u', 'i', $codigo);
        $codigo = preg_replace('/[óòõôö]/u', 'o', $codigo);
        $codigo = preg_replace('/[úùûü]/u', 'u', $codigo);
        $codigo = preg_replace('/[ç]/u', 'c', $codigo);
        // Remover caracteres especiais e substituir espaços por underscore
        $codigo = preg_replace('/[^a-z0-9]/', '_', $codigo);
        $codigo = preg_replace('/_+/', '_', $codigo);
        $codigo = trim($codigo, '_');

        // Se já existe, adicionar sufixo numérico
        $baseCode = $codigo;
        $counter = 1;
        while (InstituicaoFinanceira::where('codigo', $codigo)->exists()) {
            $codigo = $baseCode . '_' . $counter;
            $counter++;
        }

        return $codigo;
    }
}
