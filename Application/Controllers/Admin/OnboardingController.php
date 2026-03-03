<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;
use Application\Models\Conta;
use Application\Models\Categoria;
use Application\Models\Lancamento;
use Application\Models\InstituicaoFinanceira;

class OnboardingController extends BaseController
{
    /**
     * Exibe o passo atual do onboarding
     * Passo 1: Criar conta (se não tem conta)
     * Passo 2: Criar lançamento (se já tem conta)
     * 
     * Renderiza SEM header/footer do admin (tela limpa, sem distração)
     */
    public function index()
    {
        $this->requireAuth();

        $currentUser = Auth::user();
        $temConta = Conta::where('user_id', $this->userId)->exists();
        $temLancamento = Lancamento::where('user_id', $this->userId)->exists();

        // Se já completou/pulou o onboarding, vai pro dashboard
        if ($currentUser->onboarding_completed_at !== null) {
            $this->redirect('dashboard');
            return;
        }

        // Se já tem conta e lançamento mas não marcou completo, marcar agora
        if ($temConta && $temLancamento) {
            $currentUser->onboarding_completed_at = now();
            $currentUser->onboarding_mode = 'complete';
            $currentUser->save();
            $_SESSION['onboarding_just_completed'] = true;
            $this->redirect('dashboard');
            return;
        }

        // Dados comuns para o tema
        $userTheme = 'dark';
        if ($currentUser && isset($currentUser->theme_preference)) {
            $userTheme = in_array($currentUser->theme_preference, ['light', 'dark'])
                ? $currentUser->theme_preference
                : 'dark';
        }

        // Passo 1: criar conta
        if (!$temConta) {
            $instituicoes = InstituicaoFinanceira::orderBy('nome')->get();

            return $this->render('admin/onboarding/index', [
                'instituicoes' => $instituicoes,
                'userTheme' => $userTheme,
                'currentUser' => $currentUser,
            ]);
        }

        // Passo 2: form de lançamento (com opção de pular)
        $conta = Conta::where('user_id', $this->userId)->first();
        $categoriasDespesa = Categoria::forUser($this->userId)->despesas()->orderBy('nome')->get();
        $categoriasReceita = Categoria::forUser($this->userId)->receitas()->orderBy('nome')->get();

        return $this->render('admin/onboarding/lancamento', [
            'conta' => $conta,
            'categoriasDespesa' => $categoriasDespesa,
            'categoriasReceita' => $categoriasReceita,
            'userTheme' => $userTheme,
        ]);
    }
}
