<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Conta;
use Application\Models\Categoria;
use Application\Models\Lancamento;
use Application\Models\Usuario;
use Application\Services\ContaService;
use Application\DTO\CreateContaDTO;
use Application\Services\LogService;
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
                'eh_transferencia' => false,
                'eh_saldo_inicial' => false,
            ]);

            // Marcar onboarding como completo
            $user = Usuario::find($this->userId);
            if ($user) {
                $user->onboarding_completed_at = now();
                $user->onboarding_mode = 'self';
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
}