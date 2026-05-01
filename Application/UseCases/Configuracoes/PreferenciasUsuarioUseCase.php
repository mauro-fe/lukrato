<?php

declare(strict_types=1);

namespace Application\UseCases\Configuracoes;

use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;
use Throwable;

class PreferenciasUsuarioUseCase
{
    private const HELP_TUTORIAL_BASE_KEYS = [
        'dashboard',
        'lancamentos',
        'contas',
        'cartoes',
        'faturas',
        'categorias',
        'relatorios',
        'orcamento',
        'metas',
        'gamification',
        'billing',
        'perfil',
        'navigation',
    ];

    private const HELP_TUTORIAL_VARIANTS = [
        'desktop',
        'mobile',
    ];

    private const UI_PAGE_KEY_REGEX = '/^[a-z0-9][a-z0-9_-]{1,39}$/';
    private const UI_PREF_KEY_REGEX = '/^[a-zA-Z][a-zA-Z0-9_]{1,63}$/';
    private const UI_PREF_MAX_ITEMS = 100;
    private const ALLOWED_THEMES = ['light', 'dark', 'system'];

    /**
     * @return array{success:bool,message:string,data:array<string,mixed>,status:int}
     */
    public function showTheme(int $userId): array
    {
        try {
            $user = Usuario::find($userId);
            if (!$user) {
                return $this->error('Usuário não encontrado.', 404);
            }

            $theme = strtolower(trim((string) ($user->theme_preference ?? 'system')));
            if (!in_array($theme, self::ALLOWED_THEMES, true)) {
                $theme = 'system';
            }

            return $this->ok([
                'theme' => $theme,
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao buscar preferência de tema', ['exception' => $e->getMessage()]);

            return $this->error('Falha ao buscar preferência de tema.', 500);
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{success:bool,message:string,data:array<string,mixed>,status:int,errors?:array<string,mixed>}
     */
    public function updateTheme(int $userId, array $payload): array
    {
        $themeInput = null;

        try {
            $themeInput = strtolower(trim((string) ($payload['theme'] ?? '')));

            if (!in_array($themeInput, self::ALLOWED_THEMES, true)) {
                return $this->validation([
                    'theme' => 'Deve ser: light, dark ou system.',
                ]);
            }

            $user = Usuario::find($userId);
            if (!$user) {
                return $this->error('Usuário não encontrado.', 404);
            }

            $user->theme_preference = $themeInput;
            $user->save();

            return $this->ok([
                'message' => 'Preferência de tema atualizada.',
                'theme' => $user->theme_preference,
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao salvar preferência de tema', [
                'exception' => $e->getMessage(),
                'payload' => ['theme' => $themeInput],
            ]);

            return $this->error('Falha ao salvar preferência.', 500);
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{success:bool,message:string,data:array<string,mixed>,status:int,errors?:array<string,mixed>}
     */
    public function updateDisplayName(int $userId, array $payload): array
    {
        $displayNameInput = null;

        try {
            $displayNameInput = $payload['display_name'] ?? null;
            $displayName = trim((string) $displayNameInput);

            if ($displayName === '') {
                return $this->validation([
                    'display_name' => 'Digite como prefere ser chamado.',
                ]);
            }

            if (mb_strlen($displayName) < 2) {
                return $this->validation([
                    'display_name' => 'Use pelo menos 2 caracteres.',
                ]);
            }

            if (mb_strlen($displayName) > 80) {
                return $this->validation([
                    'display_name' => 'Use no máximo 80 caracteres.',
                ]);
            }

            $user = Usuario::find($userId);
            if (!$user) {
                return $this->error('Usuário não encontrado.', 404);
            }

            $user->nome = $displayName;
            $user->save();

            return $this->ok([
                'message' => 'Nome de exibição salvo.',
                'display_name' => $user->nome,
                'first_name' => $user->primeiro_nome,
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao salvar nome de exibição', [
                'exception' => $e->getMessage(),
                'payload' => ['display_name' => $displayNameInput],
            ]);

            return $this->error('Falha ao salvar nome de exibição.', 500);
        }
    }

    /**
     * @return array{success:bool,message:string,data:array<string,mixed>,status:int}
     */
    public function showHelpPreferences(int $userId): array
    {
        try {
            $user = Usuario::find($userId);
            if (!$user) {
                return $this->error('Usuário não encontrado.', 404);
            }

            $dashboardPreferences = $this->getDashboardPreferences($user);

            return $this->ok([
                'preferences' => $this->normalizeHelpPreferences($dashboardPreferences['help_center'] ?? []),
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao buscar preferências de ajuda', ['exception' => $e->getMessage()]);

            return $this->error('Falha ao buscar preferências de ajuda.', 500);
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{success:bool,message:string,data:array<string,mixed>,status:int,errors?:array<string,mixed>}
     */
    public function updateHelpPreferences(int $userId, array $payload): array
    {
        $actionInput = null;

        try {
            $actionInput = strtolower(trim((string) ($payload['action'] ?? '')));
            $pageInput = strtolower(trim((string) ($payload['page'] ?? '')));
            $versionInput = trim((string) ($payload['version'] ?? 'v1'));
            $valueInput = $payload['value'] ?? null;

            if (!in_array($actionInput, [
                'complete_tour',
                'dismiss_offer',
                'view_tips',
                'set_auto_offer',
                'reset_page',
                'reset_all',
            ], true)) {
                return $this->validation([
                    'action' => 'Ação de ajuda inválida.',
                ]);
            }

            if (in_array($actionInput, ['complete_tour', 'dismiss_offer', 'view_tips', 'reset_page'], true)) {
                if ($pageInput === '') {
                    return $this->validation([
                        'page' => 'Página obrigatória para esta ação.',
                    ]);
                }

                if (!$this->isValidHelpKey($pageInput)) {
                    return $this->validation([
                        'page' => 'Página de ajuda inválida.',
                    ]);
                }
            }

            $user = Usuario::find($userId);
            if (!$user) {
                return $this->error('Usuário não encontrado.', 404);
            }

            $dashboardPreferences = $this->getDashboardPreferences($user);
            $helpPreferences = $this->normalizeHelpPreferences($dashboardPreferences['help_center'] ?? []);

            switch ($actionInput) {
                case 'complete_tour':
                    $helpPreferences['tour_completed'][$pageInput] = $versionInput;
                    unset($helpPreferences['offer_dismissed'][$pageInput]);
                    break;

                case 'dismiss_offer':
                    $helpPreferences['offer_dismissed'][$pageInput] = $versionInput;
                    break;

                case 'view_tips':
                    $helpPreferences['tips_seen'][$pageInput] = $versionInput;
                    break;

                case 'set_auto_offer':
                    $helpPreferences['settings']['auto_offer'] = (bool) $valueInput;
                    break;

                case 'reset_page':
                    $baseKey = $this->getHelpBaseKey($pageInput);

                    unset($helpPreferences['tour_completed'][$baseKey]);
                    unset($helpPreferences['offer_dismissed'][$baseKey]);
                    unset($helpPreferences['tips_seen'][$baseKey]);

                    foreach (self::HELP_TUTORIAL_VARIANTS as $variant) {
                        $scopedKey = "{$baseKey}.{$variant}";
                        unset($helpPreferences['tour_completed'][$scopedKey]);
                        unset($helpPreferences['offer_dismissed'][$scopedKey]);
                        unset($helpPreferences['tips_seen'][$scopedKey]);
                    }
                    break;

                case 'reset_all':
                    $helpPreferences = [
                        'settings' => [
                            'auto_offer' => (bool) $helpPreferences['settings']['auto_offer'],
                        ],
                        'tour_completed' => [],
                        'offer_dismissed' => [],
                        'tips_seen' => [],
                    ];
                    break;
            }

            $this->persistHelpPreferences($user, $helpPreferences);

            return $this->ok(
                [
                    'preferences' => $this->normalizeHelpPreferences($helpPreferences),
                ],
                'Preferências de ajuda atualizadas'
            );
        } catch (Throwable $e) {
            LogService::error('Falha ao atualizar preferências de ajuda', [
                'exception' => $e->getMessage(),
                'payload' => ['action' => $actionInput],
            ]);

            return $this->error('Falha ao salvar preferências de ajuda.', 500);
        }
    }

    /**
     * @return array{success:bool,message:string,data:array<string,mixed>,status:int,errors?:array<string,mixed>}
     */
    public function showUiPreferences(int $userId, string $page): array
    {
        try {
            $pageKey = $this->normalizeUiPageKey($page);
            if ($pageKey === '') {
                return $this->validation([
                    'page' => 'Página de configuração inválida.',
                ]);
            }

            $user = Usuario::find($userId);
            if (!$user) {
                return $this->error('Usuário não encontrado.', 404);
            }

            return $this->ok([
                'page' => $pageKey,
                'preferences' => $this->getUiPagePreferences($user, $pageKey),
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao buscar preferências de interface', [
                'exception' => $e->getMessage(),
                'page' => $page,
            ]);

            return $this->error('Falha ao buscar preferências da página.', 500);
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{success:bool,message:string,data:array<string,mixed>,status:int,errors?:array<string,mixed>}
     */
    public function updateUiPreferences(int $userId, string $page, array $payload): array
    {
        try {
            $pageKey = $this->normalizeUiPageKey($page);
            if ($pageKey === '') {
                return $this->validation([
                    'page' => 'Página de configuração inválida.',
                ]);
            }

            $preferencesInput = $payload['preferences'] ?? $payload;
            if (!is_array($preferencesInput)) {
                return $this->validation([
                    'preferences' => 'Formato de preferências inválido.',
                ]);
            }

            $normalizedPreferences = $this->normalizeUiPreferencesPayload($preferencesInput);

            $user = Usuario::find($userId);
            if (!$user) {
                return $this->error('Usuário não encontrado.', 404);
            }

            $this->persistUiPagePreferences($user, $pageKey, $normalizedPreferences);

            return $this->ok(
                [
                    'page' => $pageKey,
                    'preferences' => $this->getUiPagePreferences($user, $pageKey),
                ],
                'Preferências de interface atualizadas'
            );
        } catch (Throwable $e) {
            LogService::error('Falha ao atualizar preferências de interface', [
                'exception' => $e->getMessage(),
                'page' => $page,
            ]);

            return $this->error('Falha ao salvar preferências da página.', 500);
        }
    }

    /**
     * @return array{success:bool,message:string,data:array<string,mixed>,status:int}
     */
    public function birthdayCheck(int $userId): array
    {
        try {
            $user = Usuario::find($userId);
            if (!$user) {
                return $this->error('Usuário não encontrado.', 404);
            }

            if (empty($user->data_nascimento)) {
                return $this->ok([
                    'is_birthday' => false,
                    'reason' => 'no_birthdate',
                ]);
            }

            $today = new \DateTimeImmutable('today');
            $birthDateValue = $user->data_nascimento;
            $birthDate = $birthDateValue instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($birthDateValue)
                : new \DateTimeImmutable((string) $birthDateValue);

            $isBirthday = (
                (int) $today->format('m') === (int) $birthDate->format('m')
                && (int) $today->format('d') === (int) $birthDate->format('d')
            );

            if ($isBirthday) {
                $age = (int) $today->diff($birthDate)->y;
                $nameParts = explode(' ', trim((string) $user->nome));
                $firstName = $nameParts[0];

                return $this->ok([
                    'is_birthday' => true,
                    'first_name' => $firstName,
                    'age' => $age,
                    'full_name' => $user->nome,
                ]);
            }

            return $this->ok([
                'is_birthday' => false,
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao verificar aniversário', ['exception' => $e->getMessage()]);

            return $this->ok(['is_birthday' => false]);
        }
    }

    /**
     * @param mixed $value
     * @return array<string,string>
     */
    private function normalizeHelpStateMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $page => $version) {
            $pageKey = strtolower(trim((string) $page));
            $versionKey = trim((string) $version);

            if ($pageKey === '' || $versionKey === '') {
                continue;
            }

            if (!$this->isValidHelpKey($pageKey)) {
                continue;
            }

            $normalized[$pageKey] = $versionKey;
        }

        return $normalized;
    }

    private function isValidHelpKey(string $key): bool
    {
        if (in_array($key, self::HELP_TUTORIAL_BASE_KEYS, true)) {
            return true;
        }

        foreach (self::HELP_TUTORIAL_BASE_KEYS as $baseKey) {
            foreach (self::HELP_TUTORIAL_VARIANTS as $variant) {
                if ($key === "{$baseKey}.{$variant}") {
                    return true;
                }
            }
        }

        return false;
    }

    private function getHelpBaseKey(string $key): string
    {
        $parts = explode('.', $key);
        return strtolower(trim($parts[0]));
    }

    /**
     * @param array<string,mixed>|mixed $value
     * @return array{
     *  settings:array{auto_offer:bool},
     *  tour_completed:array<string,string>,
     *  offer_dismissed:array<string,string>,
     *  tips_seen:array<string,string>
     * }
     */
    private function normalizeHelpPreferences(mixed $value): array
    {
        $help = is_array($value) ? $value : [];
        $settings = is_array($help['settings'] ?? null) ? $help['settings'] : [];

        return [
            'settings' => [
                'auto_offer' => array_key_exists('auto_offer', $settings)
                    ? (bool) $settings['auto_offer']
                    : true,
            ],
            'tour_completed' => $this->normalizeHelpStateMap($help['tour_completed'] ?? []),
            'offer_dismissed' => $this->normalizeHelpStateMap($help['offer_dismissed'] ?? []),
            'tips_seen' => $this->normalizeHelpStateMap($help['tips_seen'] ?? []),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function getDashboardPreferences(Usuario $user): array
    {
        return is_array($user->dashboard_preferences) ? $user->dashboard_preferences : [];
    }

    /**
     * @param array{
     *  settings:array{auto_offer:bool},
     *  tour_completed:array<string,string>,
     *  offer_dismissed:array<string,string>,
     *  tips_seen:array<string,string>
     * } $helpPreferences
     */
    private function persistHelpPreferences(Usuario $user, array $helpPreferences): void
    {
        $dashboardPreferences = $this->getDashboardPreferences($user);
        $dashboardPreferences['help_center'] = $this->normalizeHelpPreferences($helpPreferences);

        $user->dashboard_preferences = $dashboardPreferences;
        $user->save();
    }

    private function normalizeUiPageKey(string $page): string
    {
        $pageKey = strtolower(trim($page));

        if ($pageKey === '') {
            return '';
        }

        return preg_match(self::UI_PAGE_KEY_REGEX, $pageKey) === 1 ? $pageKey : '';
    }

    /**
     * @param array<string,mixed>|mixed $value
     * @return array<string,bool>
     */
    private function normalizeUiPreferencesPayload(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        $count = 0;

        foreach ($value as $key => $state) {
            if ($count >= self::UI_PREF_MAX_ITEMS) {
                break;
            }

            $prefKey = trim((string) $key);
            if ($prefKey === '') {
                continue;
            }

            if (preg_match(self::UI_PREF_KEY_REGEX, $prefKey) !== 1) {
                continue;
            }

            $normalized[$prefKey] = (bool) $state;
            $count++;
        }

        return $normalized;
    }

    /**
     * @return array<string,bool>
     */
    private function getUiPagePreferences(Usuario $user, string $pageKey): array
    {
        $dashboardPreferences = $this->getDashboardPreferences($user);
        $uiPages = is_array($dashboardPreferences['ui_pages'] ?? null)
            ? $dashboardPreferences['ui_pages']
            : [];

        return $this->normalizeUiPreferencesPayload($uiPages[$pageKey] ?? []);
    }

    /**
     * @param array<string,bool> $preferences
     */
    private function persistUiPagePreferences(Usuario $user, string $pageKey, array $preferences): void
    {
        $dashboardPreferences = $this->getDashboardPreferences($user);
        $uiPages = is_array($dashboardPreferences['ui_pages'] ?? null)
            ? $dashboardPreferences['ui_pages']
            : [];

        if ($preferences === []) {
            unset($uiPages[$pageKey]);
        } else {
            $uiPages[$pageKey] = $this->normalizeUiPreferencesPayload($preferences);
        }

        $dashboardPreferences['ui_pages'] = $uiPages;
        $user->dashboard_preferences = $dashboardPreferences;
        $user->save();
    }

    /**
     * @param array<string,mixed> $data
     * @return array{success:bool,message:string,data:array<string,mixed>,status:int}
     */
    private function ok(array $data, string $message = 'Success', int $status = 200): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'status' => $status,
        ];
    }

    /**
     * @return array{success:bool,message:string,data:array<string,mixed>,status:int}
     */
    private function error(string $message, int $status): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => [],
            'status' => $status,
        ];
    }

    /**
     * @param array<string,mixed> $errors
     * @return array{success:bool,message:string,data:array<string,mixed>,status:int,errors:array<string,mixed>}
     */
    private function validation(array $errors): array
    {
        return [
            'success' => false,
            'message' => 'Validation failed',
            'data' => [],
            'errors' => $errors,
            'status' => 422,
        ];
    }
}
