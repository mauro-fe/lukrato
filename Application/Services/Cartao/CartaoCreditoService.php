<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

use Application\Models\CartaoCredito;
use Application\Models\Conta;
use Application\DTO\CreateCartaoCreditoDTO;
use Application\DTO\UpdateCartaoCreditoDTO;
use Application\Validators\CartaoCreditoValidator;
use Application\Services\Infrastructure\LogService;
use Application\Services\User\OnboardingProgressService;
use Application\Enums\LogCategory;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

class CartaoCreditoService
{
    private CartaoCreditoValidator $validator;
    private OnboardingProgressService $onboardingProgressService;
    private CartaoLifecycleService $lifecycleService;
    private CartaoMonitoringService $monitoringService;

    public function __construct(
        ?CartaoCreditoValidator $validator = null,
        ?OnboardingProgressService $onboardingProgressService = null,
        ?CartaoLifecycleService $lifecycleService = null,
        ?CartaoMonitoringService $monitoringService = null
    ) {
        $this->validator = $validator ?? new CartaoCreditoValidator();
        $this->onboardingProgressService = $onboardingProgressService ?? new OnboardingProgressService();
        $this->lifecycleService = $lifecycleService ?? new CartaoLifecycleService($this->onboardingProgressService);
        $this->monitoringService = $monitoringService ?? new CartaoMonitoringService();
    }

    /**
     * Listar cartões do usuário
     */
    public function listarCartoes(
        int $userId,
        ?int $contaId = null,
        bool $apenasAtivos = true
    ): array {
        $query = CartaoCredito::forUser($userId)->with('conta.instituicaoFinanceira');

        if ($contaId) {
            $query->daConta($contaId);
        }

        if ($apenasAtivos) {
            $query->ativos();
        }

        return $query->orderBy('nome_cartao')->get()->toArray();
    }

    /**
     * Buscar cartão por ID
     */
    public function buscarCartao(int $cartaoId, int $userId): ?array
    {
        $cartao = CartaoCredito::forUser($userId)
            ->with('conta.instituicaoFinanceira')
            ->find($cartaoId);

        return $cartao?->toArray();
    }

    /**
     * Criar novo cartão de crédito
     */
    public function criarCartao(CreateCartaoCreditoDTO $dto): array
    {
        $data = $dto->toArray();

        if (!$this->validator->validateCreate($data)) {
            return [
                'success' => false,
                'errors' => $this->validator->getErrors(),
                'message' => $this->validator->getFirstError(),
            ];
        }

        // Validar que limite_disponivel não seja maior que limite_total
        $limiteTotal = (float) $data['limite_total'];
        $limiteDisponivel = (float) ($data['limite_disponivel'] ?? $limiteTotal);

        if ($limiteDisponivel > $limiteTotal) {
            return [
                'success' => false,
                'message' => 'Limite disponível não pode ser maior que o limite total.',
            ];
        }

        // Verificar se a conta pertence ao usuário
        $conta = Conta::forUser($dto->userId)->find($dto->contaId);
        if (!$conta) {
            return [
                'success' => false,
                'message' => 'Conta não encontrada ou não pertence ao usuário.',
            ];
        }

        DB::beginTransaction();
        try {
            $cartao = new CartaoCredito($data);
            $cartao->save();

            // Garantir que o limite_disponivel esteja sincronizado com
            // possíveis lançamentos já existentes vinculados ao cartão.
            $cartao->atualizarLimiteDisponivel();

            DB::commit();

            return [
                'success' => true,
                'data' => $cartao->fresh()->load('conta.instituicaoFinanceira')->toArray(),
                'id' => $cartao->id,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'criar_cartao',
                'user_id' => $dto->userId,
            ]);
            return [
                'success' => false,
                'message' => 'Erro ao criar cartão.',
            ];
        }
    }

    /**
     * Atualizar cartão existente
     */
    public function atualizarCartao(int $cartaoId, int $userId, UpdateCartaoCreditoDTO $dto): array
    {
        $cartao = CartaoCredito::forUser($userId)->find($cartaoId);

        if (!$cartao) {
            return [
                'success' => false,
                'message' => 'Cartão não encontrado.',
            ];
        }

        $data = $dto->toArray();

        if (!empty($data) && !$this->validator->validateUpdate($data)) {
            return [
                'success' => false,
                'errors' => $this->validator->getErrors(),
                'message' => $this->validator->getFirstError(),
            ];
        }

        // Validar limites se foram alterados
        if (isset($data['limite_total']) || isset($data['limite_disponivel'])) {
            $limiteTotal = (float) ($data['limite_total'] ?? $cartao->limite_total);
            $limiteDisponivel = (float) ($data['limite_disponivel'] ?? $cartao->limite_disponivel);

            if ($limiteDisponivel > $limiteTotal) {
                return [
                    'success' => false,
                    'message' => 'Limite disponível não pode ser maior que o limite total.',
                ];
            }
        }

        DB::beginTransaction();
        try {
            // Atualizar campos usando fill() ou atribuição direta
            $cartao->fill($data);

            // Se o limite total foi alterado, recalcular limite disponível
            if (isset($data['limite_total'])) {
                $cartao->atualizarLimiteDisponivel();
            }

            $cartao->save();

            DB::commit();

            return [
                'success' => true,
                'data' => $cartao->fresh()->load('conta.instituicaoFinanceira')->toArray(),
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'atualizar_cartao',
                'cartao_id' => $cartaoId,
                'user_id' => $userId,
            ]);
            return [
                'success' => false,
                'message' => 'Erro ao atualizar cartão.',
            ];
        }
    }

    /**
     * Desativar cartão
     */
    public function desativarCartao(int $cartaoId, int $userId): array
    {
        return $this->lifecycleService->desativarCartao($cartaoId, $userId);
    }

    /**
     * Reativar cartão
     */
    public function reativarCartao(int $cartaoId, int $userId): array
    {
        return $this->lifecycleService->reativarCartao($cartaoId, $userId);
    }

    /**
     * Arquivar cartão (soft delete)
     */
    public function arquivarCartao(int $cartaoId, int $userId): array
    {
        return $this->lifecycleService->arquivarCartao($cartaoId, $userId);
    }

    /**
     * Restaurar cartão arquivado
     */
    public function restaurarCartao(int $cartaoId, int $userId): array
    {
        return $this->lifecycleService->restaurarCartao($cartaoId, $userId);
    }

    /**
     * Listar cartões arquivados
     */
    public function listarCartoesArquivados(int $userId): array
    {
        return $this->lifecycleService->listarCartoesArquivados($userId);
    }

    /**
     * Excluir cartão permanentemente (somente de cartões arquivados)
     */
    public function excluirCartaoPermanente(int $cartaoId, int $userId, bool $force = false): array
    {
        return $this->lifecycleService->excluirCartaoPermanente($cartaoId, $userId, $force);
    }

    /**
     * Excluir cartão (método antigo - mantido por compatibilidade)
     * @deprecated Use arquivarCartao() seguido de excluirCartaoPermanente()
     */
    public function excluirCartao(int $cartaoId, int $userId, bool $force = false): array
    {
        // Por segurança, redireciona para arquivar
        return $this->arquivarCartao($cartaoId, $userId);
    }

    /**
     * Atualizar limite disponível de um cartão
     */
    public function atualizarLimiteDisponivel(int $cartaoId, int $userId): array
    {
        return $this->monitoringService->atualizarLimiteDisponivel($cartaoId, $userId);
    }

    /**
     * Obter resumo de todos os cartões do usuário
     */
    public function obterResumo(int $userId): array
    {
        return $this->monitoringService->obterResumo($userId);
    }

    /**
     * Verificar cartões com limite baixo (< 20%)
     */
    public function verificarLimitesBaixos(int $userId): array
    {
        return $this->monitoringService->verificarLimitesBaixos($userId);
    }

    /**
     * Validar integridade dos limites dos cartões
     * Verifica se limite_utilizado corresponde à soma de lançamentos não pagos
     */
    public function validarIntegridadeLimites(int $userId, bool $corrigirAutomaticamente = false): array
    {
        return $this->monitoringService->validarIntegridadeLimites($userId, $corrigirAutomaticamente);
    }
}
