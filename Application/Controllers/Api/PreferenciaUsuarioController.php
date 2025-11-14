<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\LogService; 
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

    public function show(): void
    {
        try {
            $this->requireAuth();

            $user = Usuario::find($this->userId);
            if (!$user) {
                Response::error('Usuário não encontrado.', 404);
                return;
            }

            $theme = ThemePreference::tryFrom($user->theme_preference ?? '') ?? ThemePreference::SYSTEM;

            Response::success([
                'theme' => $theme->value,
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao buscar preferência de tema', ['exception' => $e->getMessage()]);
            Response::error('Falha ao buscar preferência de tema.', 500);
        }
    }

    public function update(): void
    {
        $themeInput = null;
        try {
            $this->requireAuth();

            $themeInput = $this->getPayloadValue('theme');
            $themeInput = is_string($themeInput) ? strtolower(trim($themeInput)) : null;

            $theme = ThemePreference::tryFrom($themeInput ?? '');

            if ($theme === null) {
                Response::validationError([
                    'theme' => 'Deve ser: light, dark ou system.'
                ]);
                return;
            }

            $user = Usuario::find($this->userId);
            if (!$user) {
                Response::error('Usuário não encontrado.', 404);
                return;
            }

            $user->theme_preference = $theme->value;
            $user->save();

            Response::success([
                'message' => 'Preferência de tema atualizada.',
                'theme'   => $user->theme_preference,
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao salvar preferência de tema', [
                'exception' => $e->getMessage(),
                'payload' => ['theme' => $themeInput]
            ]);
            Response::error('Falha ao salvar preferência.', 500);
        }
    }
}
