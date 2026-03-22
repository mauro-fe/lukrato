<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Categoria;
use Application\Models\Conta;
use Application\Models\InstituicaoFinanceira;
use Application\Services\User\OnboardingProgressService;

class OnboardingController extends BaseController
{
    private OnboardingProgressService $progressService;

    public function __construct(?OnboardingProgressService $progressService = null)
    {
        parent::__construct();
        $this->progressService = $progressService ?? new OnboardingProgressService();
    }

    /**
     * Exibe o passo atual do onboarding
     * Passo 1: Criar conta (se não tem conta)
     * Passo 2: Criar lançamento (se já tem conta)
     *
     * Renderiza sem header/footer do admin.
     */
    public function index(): Response
    {
        $currentUser = $this->requireUser();

        if ($currentUser->onboarding_completed_at !== null) {
            return $this->buildRedirectResponse('dashboard');
        }

        $progress = $this->progressService->getProgress((int) $currentUser->id);

        if ($progress->isCompleted()) {
            $currentUser->onboarding_completed_at = $progress->onboarding_completed_at ?? now();
            $currentUser->save();

            return $this->buildRedirectResponse('dashboard');
        }

        if ($progress->has_conta && $progress->has_lancamento) {
            $currentUser->onboarding_completed_at = now();
            $currentUser->onboarding_mode = 'complete';
            $currentUser->save();
            $this->progressService->markCompleted((int) $currentUser->id);
            $_SESSION['onboarding_just_completed'] = true;

            return $this->buildRedirectResponse('dashboard');
        }

        $userTheme = 'dark';
        if (isset($currentUser->theme_preference)) {
            $userTheme = in_array($currentUser->theme_preference, ['light', 'dark'], true)
                ? $currentUser->theme_preference
                : 'dark';
        }

        if (!$progress->has_conta) {
            $instituicoes = InstituicaoFinanceira::orderBy('nome')->get();

            return $this->renderResponse('admin/onboarding/index', [
                'instituicoes' => $instituicoes,
                'userTheme' => $userTheme,
                'currentUser' => $currentUser,
            ]);
        }

        $conta = Conta::where('user_id', $this->userId)
            ->orderBy('id')
            ->first();

        if (!$conta) {
            $progress = $this->progressService->syncFromDatabase((int) $currentUser->id);

            if (!$progress->has_conta) {
                $instituicoes = InstituicaoFinanceira::orderBy('nome')->get();

                return $this->renderResponse('admin/onboarding/index', [
                    'instituicoes' => $instituicoes,
                    'userTheme' => $userTheme,
                    'currentUser' => $currentUser,
                ]);
            }

            $conta = Conta::where('user_id', $this->userId)
                ->orderBy('id')
                ->first();

            if (!$conta) {
                $instituicoes = InstituicaoFinanceira::orderBy('nome')->get();

                return $this->renderResponse('admin/onboarding/index', [
                    'instituicoes' => $instituicoes,
                    'userTheme' => $userTheme,
                    'currentUser' => $currentUser,
                ]);
            }
        }

        $categorias = Categoria::forUser($this->userId)
            ->orderBy('nome')
            ->get();

        return $this->renderResponse('admin/onboarding/lancamento', [
            'conta' => $conta,
            'categoriasDespesa' => $categorias->where('tipo', 'despesa')->values(),
            'categoriasReceita' => $categorias->where('tipo', 'receita')->values(),
            'userTheme' => $userTheme,
        ]);
    }
}
