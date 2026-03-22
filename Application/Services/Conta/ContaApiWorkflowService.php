<?php

declare(strict_types=1);

namespace Application\Services\Conta;

use Application\DTO\CreateContaDTO;
use Application\DTO\UpdateContaDTO;
use Application\Middlewares\CsrfMiddleware;
use Application\Models\InstituicaoFinanceira;
use Application\Services\Infrastructure\LogService;
use Application\Services\Plan\PlanLimitService;

class ContaApiWorkflowService
{
    public function __construct(
        private readonly ContaService $contaService = new ContaService(),
        private readonly PlanLimitService $planLimitService = new PlanLimitService()
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function listAccounts(int $userId, array $query): array
    {
        $archived = $this->resolveBoolean($query['archived'] ?? 0);
        $onlyActive = $this->resolveBoolean($query['only_active'] ?? ($archived ? 0 : 1));
        $withBalances = $this->resolveBoolean($query['with_balances'] ?? 0);
        $month = isset($query['month']) && trim((string) $query['month']) !== ''
            ? trim((string) $query['month'])
            : null;

        return [
            'success' => true,
            'data' => $this->contaService->listarContas(
                userId: $userId,
                arquivadas: $archived,
                apenasAtivas: $onlyActive,
                comSaldos: $withBalances,
                mes: $month
            ),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createAccount(int $userId, array $payload): array
    {
        $limitCheck = $this->planLimitService->canCreateConta($userId);
        if (!$limitCheck['allowed']) {
            LogService::warning('LIMITE - Tentativa de criar conta bloqueada', [
                'user_id' => $userId,
                'limite' => $limitCheck['limit'],
                'usado' => $limitCheck['used'],
            ]);

            return [
                'success' => false,
                'status' => 403,
                'message' => $limitCheck['message'],
                'errors' => [
                    'limit_reached' => true,
                    'upgrade_url' => $limitCheck['upgrade_url'],
                    'limit_info' => [
                        'limit' => $limitCheck['limit'],
                        'used' => $limitCheck['used'],
                        'remaining' => $limitCheck['remaining'],
                    ],
                ],
            ];
        }

        LogService::info('INICIO - Criacao de conta', [
            'user_id' => $userId,
            'request_id' => uniqid('req_'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100),
            'data_recebida' => $payload,
        ]);

        $dto = CreateContaDTO::fromArray($payload, $userId);

        LogService::info('DTO criado para nova conta', [
            'user_id' => $userId,
            'nome' => $dto->nome,
            'instituicao_id' => $dto->instituicaoFinanceiraId,
            'tipo_conta' => $dto->tipoConta,
            'saldo_inicial' => $dto->saldoInicial,
        ]);

        $result = $this->contaService->criarConta($dto);
        if (!$result['success']) {
            LogService::warning('ERRO ao criar conta', [
                'user_id' => $userId,
                'erro' => $result['message'],
                'errors' => $result['errors'] ?? null,
            ]);

            return [
                'success' => false,
                'status' => 422,
                'message' => $result['message'],
                'errors' => $result['errors'] ?? null,
            ];
        }

        LogService::info('SUCESSO - Conta criada', [
            'user_id' => $userId,
            'conta_id' => $result['id'],
            'nome' => $result['data']['nome'] ?? null,
        ]);

        return [
            'success' => true,
            'status' => 201,
            'data' => [
                'id' => $result['id'],
                'data' => $result['data'],
                'csrf_token' => CsrfMiddleware::generateToken('default'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateAccount(int $accountId, int $userId, array $payload): array
    {
        LogService::info('INICIO - Atualizacao de conta', [
            'user_id' => $userId,
            'conta_id' => $accountId,
            'data_recebida' => $payload,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
        ]);

        $dto = UpdateContaDTO::fromArray($payload);

        LogService::info('DTO criado para atualizacao', [
            'dto_array' => $dto->toArray(),
        ]);

        $result = $this->contaService->atualizarConta($accountId, $userId, $dto);
        if (!$result['success']) {
            LogService::warning('ERRO ao atualizar conta', [
                'user_id' => $userId,
                'conta_id' => $accountId,
                'erro' => $result['message'],
                'errors' => $result['errors'] ?? null,
            ]);

            return [
                'success' => false,
                'status' => $this->resolveAccountMutationStatus($result['message'] ?? null),
                'message' => $result['message'],
                'errors' => $result['errors'] ?? null,
            ];
        }

        LogService::info('SUCESSO - Conta atualizada', [
            'user_id' => $userId,
            'conta_id' => $accountId,
        ]);

        return [
            'success' => true,
            'data' => [
                'data' => $result['data'],
                'csrf_token' => CsrfMiddleware::generateToken('default'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function archiveAccount(int $accountId, int $userId): array
    {
        return $this->normalizeSimpleResult($this->contaService->arquivarConta($accountId, $userId));
    }

    /**
     * @return array<string, mixed>
     */
    public function restoreAccount(int $accountId, int $userId): array
    {
        return $this->normalizeSimpleResult($this->contaService->restaurarConta($accountId, $userId));
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function deleteAccount(int $accountId, int $userId, array $payload, array $query): array
    {
        $force = $this->resolveBoolean($query['force'] ?? 0)
            || filter_var($payload['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $result = $this->contaService->excluirConta($accountId, $userId, $force);

        if (!$result['success']) {
            $statusCode = isset($result['requires_confirmation']) && $result['requires_confirmation'] ? 422 : 404;

            return [
                'success' => false,
                'status' => $statusCode,
                'message' => $result['message'],
                'errors' => [
                    'requires_confirmation' => $result['requires_confirmation'] ?? false,
                    'counts' => $result['counts'] ?? null,
                ],
            ];
        }

        return [
            'success' => true,
            'data' => $result,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function listInstituicoes(?string $tipo = null): array
    {
        $query = InstituicaoFinanceira::ativas();

        if ($tipo !== null && $tipo !== '') {
            $query->porTipo($tipo);
        }

        $instituicoes = $query->orderBy('nome')->get()->map(static function ($inst): array {
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

        return [
            'success' => true,
            'data' => $instituicoes,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createInstituicao(array $payload): array
    {
        if (empty($payload['nome'])) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Nome da instituição é obrigatório',
            ];
        }

        $nome = trim((string) $payload['nome']);
        $tipo = $payload['tipo'] ?? 'outro';
        $corPrimaria = $payload['cor_primaria'] ?? '#757575';
        $corSecundaria = $payload['cor_secundaria'] ?? '#FFFFFF';

        if (InstituicaoFinanceira::where('nome', $nome)->exists()) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Já existe uma instituição com este nome',
            ];
        }

        $codigo = $this->generateUniqueCode($nome);

        $instituicao = InstituicaoFinanceira::create([
            'nome' => $nome,
            'codigo' => $codigo,
            'tipo' => $tipo,
            'cor_primaria' => $corPrimaria,
            'cor_secundaria' => $corSecundaria,
            'logo_path' => '/assets/img/banks/outro.svg',
            'ativo' => true,
        ]);

        return [
            'success' => true,
            'status' => 201,
            'message' => 'Instituição criada com sucesso!',
            'data' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
                'codigo' => $instituicao->codigo,
                'tipo' => $instituicao->tipo,
                'cor_primaria' => $instituicao->cor_primaria,
                'cor_secundaria' => $instituicao->cor_secundaria,
                'logo_url' => $instituicao->logo_url,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function normalizeSimpleResult(array $result): array
    {
        if (!$result['success']) {
            return [
                'success' => false,
                'status' => 404,
                'message' => $result['message'],
            ];
        }

        return [
            'success' => true,
            'data' => $result,
        ];
    }

    private function resolveBoolean(mixed $value): bool
    {
        return (int) $value === 1;
    }

    private function resolveAccountMutationStatus(mixed $message): int
    {
        if (!is_string($message) || $message === '') {
            return 422;
        }

        $normalized = mb_strtolower($message);

        if (
            str_contains($normalized, 'não encontrada')
            || str_contains($normalized, 'nao encontrada')
            || str_contains($normalized, 'não encontrado')
            || str_contains($normalized, 'nao encontrado')
        ) {
            return 404;
        }

        return 422;
    }

    private function generateUniqueCode(string $nome): string
    {
        $codigo = strtolower(trim($nome));
        $codigo = preg_replace('/[áàãâä]/u', 'a', $codigo);
        $codigo = preg_replace('/[éèêë]/u', 'e', $codigo);
        $codigo = preg_replace('/[íìîï]/u', 'i', $codigo);
        $codigo = preg_replace('/[óòõôö]/u', 'o', $codigo);
        $codigo = preg_replace('/[úùûü]/u', 'u', $codigo);
        $codigo = preg_replace('/[ç]/u', 'c', $codigo);
        $codigo = preg_replace('/[^a-z0-9]/', '_', $codigo);
        $codigo = preg_replace('/_+/', '_', $codigo);
        $codigo = trim((string) $codigo, '_');

        $baseCode = $codigo;
        $counter = 1;

        while (InstituicaoFinanceira::where('codigo', $codigo)->exists()) {
            $codigo = $baseCode . '_' . $counter;
            $counter++;
        }

        return $codigo;
    }
}
