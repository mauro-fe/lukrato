<?php

declare(strict_types=1);

namespace Application\Services\Admin;

use Application\Models\Categoria;
use Application\Models\Conta;
use Application\Models\InstituicaoFinanceira;
use Application\Models\Usuario;
use Application\Services\User\OnboardingProgressService;

class OnboardingAdminViewService
{
    public function __construct(
        private readonly OnboardingProgressService $progressService = new OnboardingProgressService()
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(Usuario $currentUser): array
    {
        if ($currentUser->onboarding_completed_at !== null) {
            return ['redirect' => 'dashboard'];
        }

        $progress = $this->progressService->getProgress((int) $currentUser->id);

        if ($progress->isCompleted()) {
            $currentUser->onboarding_completed_at = $progress->onboarding_completed_at ?? now();
            $currentUser->onboarding_version = $currentUser->onboarding_version ?: 'v2';
            $currentUser->save();

            return ['redirect' => 'dashboard'];
        }

        if ($progress->has_conta && $progress->has_lancamento) {
            $currentUser->onboarding_completed_at = now();
            $currentUser->onboarding_mode = 'complete';
            $currentUser->onboarding_version = 'v2';
            $currentUser->save();
            $this->progressService->markCompleted((int) $currentUser->id);
            $_SESSION['onboarding_just_completed'] = true;

            return ['redirect' => 'dashboard'];
        }

        $baseUrl = $this->normalizeBaseUrl();
        $logoUrl = $this->buildLogoUrl($baseUrl);
        $conta = $progress->has_conta ? $this->fetchFirstAccount((int) $currentUser->id) : null;

        return [
            'pageTitle' => 'Lukrato - Bem-vindo',
            'baseUrl' => $baseUrl,
            'faviconUrl' => $logoUrl,
            'theme' => $this->resolveTheme($currentUser),
            'globalConfigJson' => $this->encodeScriptJson([
                'baseUrl' => $baseUrl,
                'logoUrl' => $logoUrl,
                'userName' => $this->resolveUserName($currentUser),
                'userId' => $currentUser->id ?? null,
                'csrfToken' => function_exists('csrf_token') ? csrf_token('default') : '',
            ]),
            'onboardingConfigJson' => $this->encodeScriptJson([
                'initialStep' => $this->resolveInitialStep($conta, (bool) $progress->has_lancamento),
                'goal' => $this->resolveGoal($currentUser),
                'instituicoes' => $this->buildInstitutionsConfig($this->fetchInstitutions()),
                'categorias' => $this->buildCategoriesConfig($this->fetchCategories((int) $currentUser->id)),
                'conta' => $this->buildAccountConfig($conta),
            ]),
        ];
    }

    /**
     * @return iterable<int, InstituicaoFinanceira>
     */
    protected function fetchInstitutions(): iterable
    {
        return InstituicaoFinanceira::orderBy('nome')->get();
    }

    /**
     * @return iterable<int, Categoria>
     */
    protected function fetchCategories(int $userId): iterable
    {
        return Categoria::forUser($userId)->orderBy('nome')->get();
    }

    protected function fetchFirstAccount(int $userId): ?Conta
    {
        return Conta::where('user_id', $userId)
            ->orderBy('id')
            ->first();
    }

    private function resolveTheme(Usuario $currentUser): string
    {
        $theme = (string) ($currentUser->theme_preference ?? 'dark');

        return in_array($theme, ['light', 'dark'], true) ? $theme : 'dark';
    }

    private function resolveUserName(Usuario $currentUser): string
    {
        return trim((string) ($currentUser->nome ?? ''));
    }

    private function resolveGoal(Usuario $currentUser): ?string
    {
        $goal = trim((string) ($currentUser->onboarding_goal ?? ''));

        return $goal !== '' ? $goal : null;
    }

    private function resolveInitialStep(?Conta $conta, bool $hasLancamento): string
    {
        if ($conta !== null && !$hasLancamento) {
            return 'transaction';
        }

        return 'welcome';
    }

    private function normalizeBaseUrl(): string
    {
        return rtrim(BASE_URL, '/') . '/';
    }

    private function buildLogoUrl(string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . '/assets/img/icone.png?v=1';
    }

    /**
     * @param iterable<int, InstituicaoFinanceira> $instituicoes
     * @return array<int, array<string, mixed>>
     */
    private function buildInstitutionsConfig(iterable $instituicoes): array
    {
        $config = [];

        foreach ($instituicoes as $instituicao) {
            $config[] = [
                'id' => (int) ($instituicao->id ?? 0),
                'nome' => (string) ($instituicao->nome ?? ''),
                'codigo' => (string) ($instituicao->codigo ?? ''),
                'tipo' => (string) ($instituicao->tipo ?? ''),
                'cor_primaria' => (string) ($instituicao->cor_primaria ?? '#e67e22'),
                'cor_secundaria' => (string) ($instituicao->cor_secundaria ?? '#ffffff'),
                'logo_url' => (string) ($instituicao->logo_url ?? ''),
            ];
        }

        return $config;
    }

    /**
     * @param iterable<int, Categoria> $categorias
     * @return array<int, array<string, mixed>>
     */
    private function buildCategoriesConfig(iterable $categorias): array
    {
        $config = [];

        foreach ($categorias as $categoria) {
            $config[] = [
                'id' => (int) ($categoria->id ?? 0),
                'nome' => (string) ($categoria->nome ?? ''),
                'tipo' => (string) ($categoria->tipo ?? ''),
            ];
        }

        return $config;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildAccountConfig(?Conta $conta): ?array
    {
        if ($conta === null) {
            return null;
        }

        $institutionName = trim((string) ($conta->instituicao ?? ''));
        if ($institutionName === '') {
            $institutionName = trim((string) ($conta->instituicaoFinanceira->nome ?? ''));
        }

        return [
            'id' => (int) ($conta->id ?? 0),
            'nome' => (string) ($conta->nome ?? ''),
            'saldo' => (float) ($conta->saldo_inicial ?? 0),
            'instituicao' => $institutionName,
            'instituicao_financeira_id' => isset($conta->instituicao_financeira_id)
                ? (int) $conta->instituicao_financeira_id
                : null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encodeScriptJson(array $payload): string
    {
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );

        return is_string($json) ? $json : '{}';
    }
}
