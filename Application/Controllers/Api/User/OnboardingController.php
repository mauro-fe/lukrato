<?php

namespace Application\Controllers\Api\User;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Conta;
use Application\Models\Categoria;
use Application\Models\Lancamento;
use Application\Models\Meta;
use Application\Models\OrcamentoCategoria;
use Application\Models\Usuario;

use Application\DTO\CreateContaDTO;
use Application\Services\Conta\ContaService;
use Application\Services\Infrastructure\LogService;
use Throwable;

class OnboardingController extends BaseController
{
    /**
     * GET /api/onboarding/status
     * Retorna status do onboarding do usuário
     */
    public function status(): void
    {
        $this->requireAuthApi();

        try {
            $user = Usuario::find($this->userId);

            if (!$user) {
                Response::error('Usuário não encontrado', 404);
                return;
            }

            $temConta = Conta::where('user_id', $this->userId)->exists();
            $temLancamento = Lancamento::where('user_id', $this->userId)->exists();

            Response::success([
                'tem_conta' => $temConta,
                'tem_lancamento' => $temLancamento,
                'onboarding_completo' => $temConta && $temLancamento,
            ]);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Erro ao verificar status do onboarding');
        }
    }

    /**
     * POST /api/onboarding/conta
     * Passo 1: Salvar a conta bancária
     */
    public function storeConta(): void
    {
        $this->requireAuth();

        $nome = trim($this->getPost('nome', ''));
        $instituicaoId = $this->getPost('instituicao_financeira_id');
        $saldoInicial = $this->getPost('saldo_inicial', '0,00');

        // Validação
        if (empty($nome)) {
            $this->setError('O nome da conta é obrigatório.');
            $this->redirect('onboarding');
            return;
        }

        if (empty($instituicaoId)) {
            $this->setError('A instituição financeira é obrigatória.');
            $this->redirect('onboarding');
            return;
        }

        // Parse saldo_inicial (formato BR: 1.234,56)
        $saldoInicial = str_replace(['R$', ' ', '.'], '', $saldoInicial);
        $saldoInicial = str_replace(',', '.', $saldoInicial);
        $saldoInicial = (float) $saldoInicial;

        try {
            $dto = new CreateContaDTO(
                userId: $this->userId,
                nome: $this->sanitize($nome),
                instituicaoFinanceiraId: (int) $instituicaoId,
                saldoInicial: $saldoInicial,
            );

            $service = new ContaService();
            $result = $service->criarConta($dto);

            if (!$result['success']) {
                $this->setError($result['message'] ?? 'Erro ao criar conta.');
                $this->redirect('onboarding');
                return;
            }

            // Redireciona para o passo 2
            $this->redirect('onboarding');
        } catch (Throwable $e) {
            LogService::error('Erro ao criar conta no onboarding', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
            $this->setError('Erro inesperado. Tente novamente.');
            $this->redirect('onboarding');
        }
    }

    /**
     * POST /api/onboarding/lancamento
     * Passo 2: Salvar o primeiro lançamento e completar onboarding
     */
    public function storeLancamento(): void
    {
        $this->requireAuth();

        $tipo = $this->getPost('tipo', 'despesa');
        $valor = $this->getPost('valor', '');
        $categoriaId = $this->getPost('categoria_id');
        $descricao = trim($this->getPost('descricao', ''));
        $contaId = $this->getPost('conta_id');

        // Validações
        if (empty($valor) || empty($categoriaId) || empty($descricao) || empty($contaId)) {
            $this->setError('Todos os campos são obrigatórios.');
            $this->redirect('onboarding');
            return;
        }

        if (!in_array($tipo, ['receita', 'despesa'], true)) {
            $this->setError('Tipo inválido.');
            $this->redirect('onboarding');
            return;
        }

        if (mb_strlen($descricao) > 190) {
            $this->setError('A descrição deve ter no máximo 190 caracteres.');
            $this->redirect('onboarding');
            return;
        }

        // Parse valor (formato BR)
        $valor = str_replace(['R$', ' ', '.'], '', $valor);
        $valor = str_replace(',', '.', $valor);
        $valor = round(abs((float) $valor), 2);

        if ($valor <= 0) {
            $this->setError('O valor deve ser maior que zero.');
            $this->redirect('onboarding');
            return;
        }

        // Verificar se conta pertence ao usuário
        $contaExiste = Conta::where('id', $contaId)
            ->where('user_id', $this->userId)
            ->exists();

        if (!$contaExiste) {
            $this->setError('Conta inválida.');
            $this->redirect('onboarding');
            return;
        }

        // Verificar se categoria pertence ao usuário
        $categoriaExiste = Categoria::where('id', $categoriaId)
            ->where('user_id', $this->userId)
            ->exists();

        if (!$categoriaExiste) {
            $this->setError('Categoria inválida.');
            $this->redirect('onboarding');
            return;
        }

        try {
            // Criar lançamento
            Lancamento::create([
                'user_id' => $this->userId,
                'tipo' => $tipo,
                'data' => date('Y-m-d'),
                'valor' => $valor,
                'descricao' => $this->sanitize($descricao),
                'categoria_id' => (int) $categoriaId,
                'conta_id' => (int) $contaId,
                'pago' => true,
                'afeta_caixa' => true,
                'data_pagamento' => date('Y-m-d'),
                'eh_transferencia' => false,
                'eh_saldo_inicial' => false,
            ]);

            // Marcar onboarding como completo
            $user = Usuario::find($this->userId);
            if ($user) {
                $user->onboarding_completed_at = now();
                $user->onboarding_mode = 'complete';
                $user->save();
            }

            // Flag para banner de parabéns no dashboard
            $_SESSION['onboarding_just_completed'] = true;

            $this->redirect('dashboard');
        } catch (Throwable $e) {
            LogService::error('Erro ao criar lançamento no onboarding', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
            $this->setError('Erro inesperado. Tente novamente.');
            $this->redirect('onboarding');
        }
    }

    /**
     * POST /api/onboarding/complete
     * Marca o onboarding como concluído (usado pelo botão "Explorar o Lukrato")
     */
    public function complete(): void
    {
        $this->requireAuthApi();

        try {
            $user = Usuario::find($this->userId);

            if (!$user) {
                Response::error('Usuário não encontrado', 404);
                return;
            }

            // Verificar se tem pelo menos uma conta
            $temConta = Conta::where('user_id', $this->userId)->exists();
            if (!$temConta) {
                Response::error('Você precisa criar pelo menos uma conta antes de continuar.', 422);
                return;
            }

            // Marcar onboarding como concluído (modo skipped = pulou o lançamento)
            $user->onboarding_completed_at = now();
            $user->onboarding_mode = 'skipped';
            $user->save();

            // Flag para confetti no dashboard
            $_SESSION['onboarding_just_completed'] = true;

            Response::success([
                'message' => 'Onboarding concluído!',
                'redirect' => BASE_URL . 'dashboard',
            ]);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Erro ao completar onboarding');
        }
    }

    /**
     * POST /api/onboarding/skip-tour
     * Marca o tour guiado como pulado
     */
    public function skipTour(): void
    {
        $this->requireAuthApi();

        try {
            $user = Usuario::find($this->userId);

            if (!$user) {
                Response::error('Usuário não encontrado', 404);
                return;
            }

            $user->skipOnboardingTour();

            Response::success(['message' => 'Tour pulado com sucesso.']);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Erro ao pular tour do onboarding');
        }
    }

    /**
     * POST /api/onboarding/reset
     * Reseta o estado do onboarding (útil para debug/admin)
     */
    public function reset(): void
    {
        $this->requireAuthApi();

        try {
            $user = Usuario::find($this->userId);

            if (!$user) {
                Response::error('Usuário não encontrado', 404);
                return;
            }

            $user->onboarding_completed_at = null;
            $user->onboarding_mode = null;
            $user->onboarding_tour_skipped_at = null;
            $user->save();

            Response::success(['message' => 'Onboarding resetado com sucesso.']);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Erro ao resetar onboarding');
        }
    }

    /**
     * GET /api/onboarding/checklist
     * Retorna status da checklist de primeiros passos (expandida para 8 items com pontos)
     */
    public function checklist(): void
    {
        $this->requireAuthApi();

        try {
            $userId = $this->userId;

            $contasCount     = Conta::where('user_id', $userId)->count();
            $lancamentosCount = Lancamento::where('user_id', $userId)
                ->where('eh_saldo_inicial', false)
                ->count();
            $categoriasCount = Categoria::where('user_id', $userId)->count();
            $hasMeta         = Meta::where('user_id', $userId)->exists();
            $hasOrcamento    = OrcamentoCategoria::where('user_id', $userId)->exists();
            $hasAgendamento  = Lancamento::where('user_id', $userId)->where('recorrente', true)->exists();

            // Novos items (expandidos)
            $hasCustomCategory = $categoriasCount > 0; // Categoria customizada (além das padrões)
            $explorouHealthScore = false; // TODO: Track health score views

            $items = [
                // TIER 1: Fundamentais (precisa fazer)
                [
                    'key'         => 'primeira_transacao',
                    'label'       => 'Adicionar primeira transação',
                    'description' => 'Registre seu primeiro lançamento',
                    'icon'        => 'plus-circle',
                    'color'       => '#10b981',
                    'href'        => 'lancamentos?tipo=receita',
                    'done'        => $lancamentosCount >= 1,
                    'points'      => 25,  // Pontos de gamificação
                    'priority'    => 1,   // Ordem de exibição
                ],
                [
                    'key'         => 'segunda_transacao',
                    'label'       => 'Adicionar segunda transação',
                    'description' => 'Construa o hábito',
                    'icon'        => 'plus-circle',
                    'color'       => '#3b82f6',
                    'href'        => 'lancamentos',
                    'done'        => $lancamentosCount >= 2,
                    'points'      => 25,
                    'priority'    => 2,
                ],

                // TIER 2: Importantes (melhor funcionamento)
                [
                    'key'         => 'explorar_health_score',
                    'label'       => 'Explorar Health Score',
                    'description' => 'Entenda sua saúde financeira',
                    'icon'        => 'heart-handshake',
                    'color'       => '#ec4899',
                    'href'        => 'dashboard#health-score',
                    'done'        => $explorouHealthScore, // Será atualizado via JS
                    'points'      => 10,
                    'priority'    => 3,
                ],
                [
                    'key'         => 'categoria_customizada',
                    'label'       => 'Criar categoria customizada',
                    'description' => 'Personalize suas categorias',
                    'icon'        => 'tags',
                    'color'       => '#8b5cf6',
                    'href'        => 'categorias',
                    'done'        => $hasCustomCategory,
                    'points'      => 20,
                    'priority'    => 4,
                ],

                // TIER 3: Avançados (mais features)
                [
                    'key'         => 'meta',
                    'label'       => 'Definir meta mensal',
                    'description' => 'Estabeleça um objetivo de poupança',
                    'icon'        => 'target',
                    'color'       => '#06b6d4',
                    'href'        => 'financas',
                    'done'        => $hasMeta,
                    'points'      => 15,
                    'priority'    => 5,
                ],
                [
                    'key'         => 'conta_conectada',
                    'label'       => 'Adicionar segunda conta/cartão',
                    'description' => 'Tenha visão completa do seu dinheiro',
                    'icon'        => 'wallet',
                    'color'       => '#f59e0b',
                    'href'        => 'contas',
                    'done'        => $contasCount >= 2,
                    'points'      => 50,
                    'priority'    => 6,
                ],
                [
                    'key'         => 'orcamento',
                    'label'       => 'Configurar orçamentos',
                    'description' => 'Controle seus gastos por categoria',
                    'icon'        => 'pie-chart',
                    'color'       => '#f97316',
                    'href'        => 'financas',
                    'done'        => $hasOrcamento,
                    'points'      => 20,
                    'priority'    => 7,
                ],
                [
                    'key'         => 'convidar_amigo',
                    'label'       => 'Convidar amigo (Referral)',
                    'description' => 'Compartilhe o Lukrato e ganhe bônus',
                    'icon'        => 'share-2',
                    'color'       => '#f43f5e',
                    'href'        => 'perfil?tab=referral',
                    'done'        => false, // TODO: Track referrals
                    'points'      => 100,  // Maior recompensa
                    'priority'    => 8,
                ],
            ];

            // Sort by priority
            usort($items, fn($a, $b) => $a['priority'] <=> $b['priority']);

            $doneCount = count(array_filter($items, fn($i) => $i['done']));
            $totalPoints = array_sum(array_map(fn($i) => $i['done'] ? $i['points'] : 0, $items));

            Response::success([
                'items'        => $items,
                'done_count'   => $doneCount,
                'total'        => count($items),
                'all_complete' => $doneCount === count($items),
                'total_points' => $totalPoints,  // Pontos já ganhos
                'potential_points' => array_sum(array_column($items, 'points')), // Total possível
            ]);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Erro ao buscar checklist do onboarding');
        }
    }
}
