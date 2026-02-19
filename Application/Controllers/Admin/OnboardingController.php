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

        // Se já tem conta e lançamento, onboarding não é necessário
        $temConta = Conta::where('user_id', $this->userId)->exists();
        $temLancamento = Lancamento::where('user_id', $this->userId)->exists();

        if ($temConta && $temLancamento) {
            $this->redirect('dashboard');
            return;
        }

        // Dados comuns para o tema
        $currentUser = Auth::user();
        $userTheme = 'dark';
        if ($currentUser && isset($currentUser->theme_preference)) {
            $userTheme = in_array($currentUser->theme_preference, ['light', 'dark'])
                ? $currentUser->theme_preference
                : 'dark';
        }

        if (!$temConta) {
            $instituicoes = InstituicaoFinanceira::orderBy('nome')->get();

            return $this->render('admin/onboarding/index', [
                'instituicoes' => $instituicoes,
                'userTheme' => $userTheme,
            ]);
        }

        // Passo 2: form de lançamento
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
