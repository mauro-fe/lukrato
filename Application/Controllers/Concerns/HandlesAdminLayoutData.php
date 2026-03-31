<?php

declare(strict_types=1);

namespace Application\Controllers\Concerns;

use Application\Lib\Auth;
use Application\Models\BlogCategoria;
use Application\Models\Telefone;
use Application\Models\Usuario;

trait HandlesAdminLayoutData
{
    /**
     * Injeta automaticamente variáveis do layout admin (plano, role, tema, etc.)
     * Só sobrescreve se o controller NÃO passou o valor explicitamente.
     */
    protected function injectAdminLayoutData(array $data): array
    {
        $currentUser = $data['currentUser'] ?? Auth::user();
        $displayName = $this->resolveAdminDisplayName($currentUser);

        $isPro = $data['isPro'] ?? (
            $currentUser && method_exists($currentUser, 'isPro') && $currentUser->isPro()
        );

        $data['currentUser'] = $currentUser;
        $data['username'] = $data['username'] ?? $displayName;
        $data['isSysAdmin'] = $data['isSysAdmin'] ?? (((int) ($currentUser?->is_admin ?? 0)) === 1);
        $data['isPro'] = $isPro;
        $data['planTier'] = $data['planTier'] ?? ($currentUser && method_exists($currentUser, 'planTier') ? $currentUser->planTier() : 'free');
        $data['planLabel'] = $data['planLabel'] ?? match ($data['planTier']) {
            'ultra' => 'ULTRA',
            'pro' => 'PRO',
            default => 'FREE',
        };
        $data['showUpgradeCTA'] = $data['showUpgradeCTA'] ?? (!$isPro);

        if (!isset($data['userTheme'])) {
            $data['userTheme'] = 'dark';
            if ($currentUser && isset($currentUser->theme_preference)) {
                $data['userTheme'] = in_array($currentUser->theme_preference, ['light', 'dark'])
                    ? $currentUser->theme_preference
                    : 'dark';
            }
        }

        if (!isset($data['topNavFirstName'])) {
            $data['topNavFirstName'] = $this->resolveAdminFirstName($currentUser);
        }

        $data['currentBreadcrumbs'] = $data['currentBreadcrumbs'] ?? $this->resolveBreadcrumbs($data['menu'] ?? '');

        if (!isset($data['supportName'])) {
            $data['supportName'] = $displayName;
            $data['supportEmail'] = $currentUser->email ?? '';
            $userId = $currentUser->id_usuario ?? $currentUser->id ?? null;
            $telefoneModel = $userId ? Telefone::where('id_usuario', $userId)->first() : null;
            $data['supportTel'] = $telefoneModel?->numero ?? '';
            $data['supportDdd'] = $telefoneModel?->ddd?->codigo ?? '';
        }

        return $data;
    }

    protected function injectSiteLayoutData(array $data): array
    {
        if (!isset($data['headerBlogCategorias'])) {
            $data['headerBlogCategorias'] = BlogCategoria::ordenadas()->get();
        }

        return $data;
    }

    private function resolveAdminDisplayName(?Usuario $currentUser): string
    {
        $fullName = trim((string) ($currentUser?->nome ?? ''));

        return $fullName !== '' ? $fullName : $this->resolveEmailNickname($currentUser);
    }

    private function resolveAdminFirstName(?Usuario $currentUser): string
    {
        $fullName = trim((string) ($currentUser?->nome ?? ''));

        if ($fullName !== '') {
            return explode(' ', $fullName)[0] ?? '';
        }

        return $this->resolveEmailNickname($currentUser);
    }

    private function resolveEmailNickname(?Usuario $currentUser): string
    {
        $email = trim((string) ($currentUser?->email ?? ''));
        if ($email === '' || !str_contains($email, '@')) {
            return 'Você';
        }

        $localPart = strtolower((string) strstr($email, '@', true));
        $candidate = preg_split('/[._-]+/', $localPart)[0] ?? $localPart;
        $candidate = preg_replace('/\d+$/', '', $candidate ?? '') ?? '';
        $candidate = trim($candidate);

        if ($candidate === '') {
            return 'você';
        }

        return ucfirst($candidate);
    }

    private function resolveBreadcrumbs(string $menu): array
    {
        $map = [
            'dashboard' => [],
            'contas' => [['label' => 'Finanças', 'icon' => 'wallet']],
            'cartoes' => [['label' => 'Finanças', 'icon' => 'wallet']],
            'faturas' => [['label' => 'Finanças', 'icon' => 'wallet'], ['label' => 'Cartões', 'url' => 'cartoes', 'icon' => 'credit-card']],
            'categorias' => [['label' => 'Organização', 'icon' => 'folder']],
            'lancamentos' => [['label' => 'Finanças', 'icon' => 'wallet']],
            'relatorios' => [['label' => 'Análises', 'icon' => 'bar-chart-3']],
            'gamification' => [['label' => 'Perfil', 'icon' => 'user']],
            'perfil' => [],
            'configuracoes' => [['label' => 'Perfil', 'icon' => 'user']],
            'billing' => [['label' => 'Perfil', 'icon' => 'user']],
        ];

        return $map[$menu] ?? [];
    }
}
