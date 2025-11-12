<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response; // Usa a classe Response padrão
use Application\Models\Usuario;
use Application\Services\LogService; // Para log de erro
use Throwable;

/**
 * Enum para as opções de tema (PHP 8.1+)
 */
enum ThemePreference: string
{
    case LIGHT = 'light';
    case DARK = 'dark';
    case SYSTEM = 'system';
}

class PreferenciaUsuarioController extends BaseController
{
    /**
     * Tenta obter um valor do $_POST ou do corpo JSON da requisição.
     */
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

    /**
     * Retorna a preferência de tema do usuário.
     */
    public function show(): void
    {
        try {
            $this->requireAuth();

            /** @var Usuario|null $user */
            $user = Usuario::find($this->userId);
            if (!$user) {
                Response::error('Usuário não encontrado.', 404);
                return;
            }

            // Valida o tema salvo ou usa 'system' como padrão
            $theme = ThemePreference::tryFrom($user->theme_preference ?? '') ?? ThemePreference::SYSTEM;

            Response::success([
                'theme' => $theme->value,
            ]);
        } catch (Throwable $e) {
            LogService::error('Falha ao buscar preferência de tema', ['exception' => $e->getMessage()]);
            Response::error('Falha ao buscar preferência de tema.', 500);
        }
    }

    /**
     * Atualiza a preferência de tema do usuário.
     */
    public function update(): void
    {
        $themeInput = null;
        try {
            $this->requireAuth();

            $themeInput = $this->getPayloadValue('theme');
            $themeInput = is_string($themeInput) ? strtolower(trim($themeInput)) : null;

            // Validação usando o Enum
            $theme = ThemePreference::tryFrom($themeInput ?? '');

            if ($theme === null) {
                Response::validationError([
                    'theme' => 'Deve ser: light, dark ou system.'
                ]);
                return;
            }

            /** @var Usuario|null $user */
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
