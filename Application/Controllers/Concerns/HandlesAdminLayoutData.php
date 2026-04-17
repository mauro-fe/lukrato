<?php

declare(strict_types=1);

namespace Application\Controllers\Concerns;

use Application\Lib\Auth;
use Application\Models\BlogCategoria;
use Application\Models\Telefone;
use Application\Models\Usuario;
use Application\Support\Admin\AdminModuleRegistry;

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
        $currentViewPath = trim((string) ($data['currentViewPath'] ?? ''), '/');

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
                $data['userTheme'] = in_array($currentUser->theme_preference, ['light', 'dark'], true)
                    ? $currentUser->theme_preference
                    : 'dark';
            }
        }

        if (!isset($data['topNavFirstName'])) {
            $data['topNavFirstName'] = $this->resolveAdminFirstName($currentUser);
        }

        $data['currentBreadcrumbs'] = $data['currentBreadcrumbs'] ?? (
            $currentViewPath !== ''
            ? AdminModuleRegistry::resolveBreadcrumbsByViewContext($currentViewPath, $data)
            : []
        );

        if (!isset($data['supportName'])) {
            $data['supportName'] = $displayName;
            $data['supportEmail'] = $currentUser->email ?? '';
            $userId = $currentUser->id_usuario ?? $currentUser->id ?? null;
            $telefoneModel = $userId ? Telefone::where('id_usuario', $userId)->first() : null;
            $data['supportTel'] = $telefoneModel?->numero ?? '';
            $data['supportDdd'] = $telefoneModel?->ddd?->codigo ?? '';
        }

        $currentViewId = trim((string) ($data['currentViewId'] ?? ''), '-');
        if ($currentViewId === '' && $currentViewPath !== '') {
            $currentViewId = trim(str_replace('/', '-', $currentViewPath), '-');
        }

        $contextData = [
            'menu' => is_string($data['menu'] ?? null) ? $data['menu'] : null,
        ];

        $currentPageJsViewId = trim((string) (
            $data['currentPageJsViewId']
            ?? (
                $currentViewId !== ''
                ? AdminModuleRegistry::resolvePageJsViewId($currentViewId, $contextData)
                : ''
            )
        ));
        if ($currentPageJsViewId === '') {
            $currentPageJsViewId = $currentViewId;
        }

        $bundle = [
            'pageJsViewId' => $currentPageJsViewId,
            'viteEntry' => $currentPageJsViewId !== ''
                ? AdminModuleRegistry::resolveViteEntryByViewId($currentPageJsViewId)
                : null,
            'cssEntry' => $currentViewId !== ''
                ? AdminModuleRegistry::resolveCssEntryByViewId($currentViewId)
                : null,
        ];

        $sidebarModules = AdminModuleRegistry::groupedSidebarModules(
            (bool) $data['isSysAdmin'],
            (bool) $data['isPro']
        );
        $footerModules = AdminModuleRegistry::footerModules(
            (bool) $data['isSysAdmin'],
            (bool) $data['isPro']
        );

        $data['currentViewPath'] = $currentViewPath;
        $data['currentViewId'] = $currentViewId;
        $data['currentPageJsViewId'] = $currentPageJsViewId;
        $data['bundle'] = $data['bundle'] ?? $bundle;
        $data['sidebarModules'] = $data['sidebarModules'] ?? $sidebarModules;
        $data['footerModules'] = $data['footerModules'] ?? $footerModules;
        $data['adminPageContext'] = $data['adminPageContext'] ?? [
            'currentMenu' => $data['menu'] ?? null,
            'currentViewId' => $currentViewId,
            'currentViewPath' => $currentViewPath,
            'breadcrumbs' => $data['currentBreadcrumbs'],
            'sidebar' => $data['sidebarModules'],
            'footerModules' => $data['footerModules'],
            'bundle' => $data['bundle'],
        ];
        $data['adminRuntimeConfig'] = $data['adminRuntimeConfig'] ?? $this->buildAdminRuntimeConfig($data);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function buildAdminRuntimeConfig(array $data): array
    {
        $currentUser = $data['currentUser'] ?? null;
        $dashboardPreferencesRaw = $currentUser?->dashboard_preferences ?? null;
        $dashboardPreferences = is_array($dashboardPreferencesRaw)
            ? $dashboardPreferencesRaw
            : [];

        return [
            'baseUrl' => rtrim(BASE_URL, '/') . '/',
            'apiBaseUrl' => rtrim(BASE_URL, '/') . '/',
            'csrfTtl' => (int) \Application\Middlewares\CsrfMiddleware::TOKEN_TTL,
            'isPro' => (bool) ($data['isPro'] ?? false),
            'isSysAdmin' => (bool) ($data['isSysAdmin'] ?? false),
            'planTier' => (string) ($data['planTier'] ?? 'free'),
            'planLabel' => (string) ($data['planLabel'] ?? 'FREE'),
            'showUpgradeCTA' => (bool) ($data['showUpgradeCTA'] ?? true),
            'userTheme' => (string) ($data['userTheme'] ?? 'dark'),
            'userId' => $currentUser?->id ?? $currentUser?->id_usuario ?? null,
            'username' => (string) ($data['username'] ?? $this->resolveAdminDisplayName($currentUser)),
            'userEmail' => (string) ($currentUser?->email ?? ''),
            'currentMenu' => (string) (($data['menu'] ?? '') ?: 'dashboard'),
            'currentViewId' => (string) ($data['currentViewId'] ?? ''),
            'currentViewPath' => (string) ($data['currentViewPath'] ?? ''),
            'bundle' => is_array($data['bundle'] ?? null) ? $data['bundle'] : [],
            'needsDisplayNamePrompt' => trim((string) ($currentUser?->nome ?? '')) === '',
            'tourCompleted' => !empty($currentUser?->tour_completed_at),
            'helpCenter' => $this->normalizeAdminHelpCenterPreferences($dashboardPreferences['help_center'] ?? null),
            'userAvatar' => $currentUser?->avatar
                ? rtrim(BASE_URL, '/') . '/' . ltrim((string) $currentUser->avatar, '/')
                : '',
            'userAvatarSettings' => $this->buildAdminAvatarSettings($currentUser),
            'pageContext' => is_array($data['adminPageContext'] ?? null) ? $data['adminPageContext'] : null,
        ];
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

    /**
     * @return array<string, mixed>
     */
    private function normalizeAdminHelpCenterPreferences(mixed $rawPreferences): array
    {
        $rawHelpCenterPreferences = is_array($rawPreferences)
            ? $rawPreferences
            : [];
        $rawHelpSettings = is_array($rawHelpCenterPreferences['settings'] ?? null)
            ? $rawHelpCenterPreferences['settings']
            : [];

        return [
            'settings' => [
                'auto_offer' => array_key_exists('auto_offer', $rawHelpSettings)
                    ? (bool) $rawHelpSettings['auto_offer']
                    : true,
            ],
            'tour_completed' => is_array($rawHelpCenterPreferences['tour_completed'] ?? null)
                ? $rawHelpCenterPreferences['tour_completed']
                : [],
            'offer_dismissed' => is_array($rawHelpCenterPreferences['offer_dismissed'] ?? null)
                ? $rawHelpCenterPreferences['offer_dismissed']
                : [],
            'tips_seen' => is_array($rawHelpCenterPreferences['tips_seen'] ?? null)
                ? $rawHelpCenterPreferences['tips_seen']
                : [],
        ];
    }

    /**
     * @return array{position_x:int,position_y:int,zoom:float}
     */
    private function buildAdminAvatarSettings(?Usuario $currentUser): array
    {
        return [
            'position_x' => max(0, min(100, (int) ($currentUser?->avatar_focus_x ?? 50))),
            'position_y' => max(0, min(100, (int) ($currentUser?->avatar_focus_y ?? 50))),
            'zoom' => max(1, min(2, round((float) ($currentUser?->avatar_zoom ?? 1), 2))),
        ];
    }
}
