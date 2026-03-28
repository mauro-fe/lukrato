<?php

declare(strict_types=1);

namespace Application\Controllers\Api\User;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;
use Throwable;

enum ThemePreference: string
{
    case LIGHT = 'light';
    case DARK = 'dark';
    case SYSTEM = 'system';
}

class PreferenciaUsuarioController extends BaseController
{
    private const HELP_PAGE_KEYS = [
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
    ];

    private function getPayloadValue(string $key): mixed
    {
        $value = $this->getPost($key);
        if ($value !== null) {
            return $value;
        }

        $raw = file_get_contents('php://input') ?: '';
        if ($raw !== '') {
            $json = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($json[$key])) {
                return $json[$key];
            }
        }

        return null;
    }

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

            if (!in_array($pageKey, self::HELP_PAGE_KEYS, true)) {
                continue;
            }

            $normalized[$pageKey] = $versionKey;
        }

        return $normalized;
    }

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

    private function getDashboardPreferences(Usuario $user): array
    {
        return is_array($user->dashboard_preferences) ? $user->dashboard_preferences : [];
    }

    private function persistHelpPreferences(Usuario $user, array $helpPreferences): void
    {
        $dashboardPreferences = $this->getDashboardPreferences($user);
        $dashboardPreferences['help_center'] = $this->normalizeHelpPreferences($helpPreferences);

        $user->dashboard_preferences = $dashboardPreferences;
        $user->save();
    }

    public function show(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $user = Usuario::find($userId);
            if (!$user) {
                return Response::errorResponse('Usuário não encontrado.', 404);
            }

            $theme = ThemePreference::tryFrom($user->theme_preference ?? '') ?? ThemePreference::SYSTEM;

            return Response::successResponse([
                'theme' => $theme->value,
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao buscar preferência de tema', ['exception' => $e->getMessage()]);

            return Response::errorResponse('Falha ao buscar preferência de tema.', 500);
        }
    }

    public function update(): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $themeInput = null;

        try {
            $themeInput = $this->getPayloadValue('theme');
            $themeInput = is_string($themeInput) ? strtolower(trim($themeInput)) : null;

            $theme = ThemePreference::tryFrom($themeInput ?? '');

            if ($theme === null) {
                return Response::validationErrorResponse([
                    'theme' => 'Deve ser: light, dark ou system.',
                ]);
            }

            $user = Usuario::find($userId);
            if (!$user) {
                return Response::errorResponse('Usuário não encontrado.', 404);
            }

            $user->theme_preference = $theme->value;
            $user->save();

            return Response::successResponse([
                'message' => 'Preferência de tema atualizada.',
                'theme' => $user->theme_preference,
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao salvar preferência de tema', [
                'exception' => $e->getMessage(),
                'payload' => ['theme' => $themeInput],
            ]);

            return Response::errorResponse('Falha ao salvar preferência.', 500);
        }
    }

    /**
     * Verifica se hoje é aniversário do usuário
     * GET /api/user/birthday-check
     */
    /**
     * Atualiza o nome de exibicao usado no produto.
     * POST /api/user/display-name
     */
    public function updateDisplayName(): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $displayNameInput = null;

        try {
            $displayNameInput = $this->getPayloadValue('display_name');
            $displayName = trim((string) $displayNameInput);

            if ($displayName === '') {
                return Response::validationErrorResponse([
                    'display_name' => 'Digite como prefere ser chamado.',
                ]);
            }

            if (mb_strlen($displayName) < 2) {
                return Response::validationErrorResponse([
                    'display_name' => 'Use pelo menos 2 caracteres.',
                ]);
            }

            if (mb_strlen($displayName) > 80) {
                return Response::validationErrorResponse([
                    'display_name' => 'Use no máximo 80 caracteres.',
                ]);
            }

            $user = Usuario::find($userId);
            if (!$user) {
                return Response::errorResponse('Usuário não encontrado.', 404);
            }

            $user->nome = $displayName;
            $user->save();

            return Response::successResponse([
                'message' => 'Nome de exibição salvo.',
                'display_name' => $user->nome,
                'first_name' => $user->primeiro_nome,
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao salvar nome de exibição', [
                'exception' => $e->getMessage(),
                'payload' => ['display_name' => $displayNameInput],
            ]);

            return Response::errorResponse('Falha ao salvar nome de exibição.', 500);
        }
    }

    public function showHelpPreferences(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $user = Usuario::find($userId);
            if (!$user) {
                return Response::errorResponse('Usuario nao encontrado.', 404);
            }

            $dashboardPreferences = $this->getDashboardPreferences($user);

            return Response::successResponse([
                'preferences' => $this->normalizeHelpPreferences($dashboardPreferences['help_center'] ?? []),
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao buscar preferencias de ajuda', ['exception' => $e->getMessage()]);

            return Response::errorResponse('Falha ao buscar preferencias de ajuda.', 500);
        }
    }

    public function updateHelpPreferences(): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $actionInput = null;

        try {
            $actionInput = strtolower(trim((string) $this->getPayloadValue('action')));
            $pageInput = strtolower(trim((string) $this->getPayloadValue('page')));
            $versionInput = trim((string) ($this->getPayloadValue('version') ?? 'v1'));
            $valueInput = $this->getPayloadValue('value');

            if (!in_array($actionInput, [
                'complete_tour',
                'dismiss_offer',
                'view_tips',
                'set_auto_offer',
                'reset_page',
                'reset_all',
            ], true)) {
                return Response::validationErrorResponse([
                    'action' => 'Acao de ajuda invalida.',
                ]);
            }

            if (in_array($actionInput, ['complete_tour', 'dismiss_offer', 'view_tips', 'reset_page'], true)) {
                if ($pageInput === '') {
                    return Response::validationErrorResponse([
                        'page' => 'Pagina obrigatoria para esta acao.',
                    ]);
                }

                if (!in_array($pageInput, self::HELP_PAGE_KEYS, true)) {
                    return Response::validationErrorResponse([
                        'page' => 'Pagina de ajuda invalida.',
                    ]);
                }
            }

            $user = Usuario::find($userId);
            if (!$user) {
                return Response::errorResponse('Usuario nao encontrado.', 404);
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
                    unset($helpPreferences['tour_completed'][$pageInput]);
                    unset($helpPreferences['offer_dismissed'][$pageInput]);
                    unset($helpPreferences['tips_seen'][$pageInput]);
                    break;

                case 'reset_all':
                    $helpPreferences = [
                        'settings' => [
                            'auto_offer' => array_key_exists('auto_offer', $helpPreferences['settings'])
                                ? (bool) $helpPreferences['settings']['auto_offer']
                                : true,
                        ],
                        'tour_completed' => [],
                        'offer_dismissed' => [],
                        'tips_seen' => [],
                    ];
                    break;
            }

            $this->persistHelpPreferences($user, $helpPreferences);

            return Response::successResponse([
                'preferences' => $this->normalizeHelpPreferences($helpPreferences),
            ], 'Preferencias de ajuda atualizadas');
        } catch (Throwable $e) {
            LogService::error('Falha ao atualizar preferencias de ajuda', [
                'exception' => $e->getMessage(),
                'payload' => ['action' => $actionInput],
            ]);

            return Response::errorResponse('Falha ao salvar preferencias de ajuda.', 500);
        }
    }

    public function birthdayCheck(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $user = Usuario::find($userId);
            if (!$user) {
                return Response::errorResponse('Usuário não encontrado.', 404);
            }

            if (empty($user->data_nascimento)) {
                return Response::successResponse([
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
                $nameParts = explode(' ', trim($user->nome));
                $firstName = $nameParts[0] ?? 'Você';

                return Response::successResponse([
                    'is_birthday' => true,
                    'first_name' => $firstName,
                    'age' => $age,
                    'full_name' => $user->nome,
                ]);
            }

            return Response::successResponse([
                'is_birthday' => false,
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao verificar aniversário', ['exception' => $e->getMessage()]);

            return Response::successResponse(['is_birthday' => false]);
        }
    }
}
