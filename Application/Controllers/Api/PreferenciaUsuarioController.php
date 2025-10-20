<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Models\Usuario;

class PreferenciaUsuarioController extends BaseController
{
    public function show(): void
    {
        try {
            $this->requireAuth();

            $user = Usuario::find($this->userId);
            if (!$user) {
                $this->response->jsonBody(['error' => 'Usuário não encontrado.'], 404)->send();
                return;
            }

            $this->response->jsonBody([
                'theme' => $user->theme_preference ?? 'system',
            ])->send();
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Falha ao buscar preferência de tema.', 500);
        }
    }

    public function update(): void
    {
        try {
            $this->requireAuth();

            $theme = $this->getPost('theme');
            if ($theme === null) {
                $raw = file_get_contents('php://input') ?: '';
                if ($raw !== '') {
                    $json = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($json['theme'])) {
                        $theme = $json['theme'];
                    }
                }
            }

            $theme = is_string($theme) ? strtolower(trim($theme)) : null;

            $allowed = ['light', 'dark', 'system'];
            if (!in_array($theme, $allowed, true)) {
                $this->response->jsonBody([
                    'error'  => 'Validação falhou.',
                    'fields' => ['theme' => 'Deve ser: light, dark ou system.'],
                ], 422)->send();
                return;
            }


            $user = Usuario::find($this->userId);
            if (!$user) {
                $this->response->jsonBody(['error' => 'Usuário não encontrado.'], 404)->send();
                return;
            }

            $user->theme_preference = $theme;
            $user->save();

            $this->response->jsonBody([
                'message' => 'Preferência de tema atualizada.',
                'theme'   => $user->theme_preference,
            ])->send();
        } catch (\Throwable $e) {
            $this->failAndLog(
                $e,
                'Falha ao salvar preferência de tema.',
                500,
                ['payload' => ['theme' => $theme ?? null]]
            );
        }
    }
}
