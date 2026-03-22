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
