<?php
// Application/Controllers/Api/UserPreferenceController.php
namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Models\Usuario;
use Application\Services\LogService;
use GUMP;

class UserPreferenceController extends BaseController
{
    /** GET /api/user/theme -> { theme } */
    public function show(): void
    {
        try {
            $this->requireAuth();

            /** @var Usuario|null $user */
            $user = Usuario::find($this->adminId);
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

    /** POST /api/user/theme  body: { theme: "light"|"dark"|"system" } */
    public function update(): void
    {
        try {
            $this->requireAuth();

            // Lê JSON ou form
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

            // Normaliza
            $theme = is_string($theme) ? strtolower(trim($theme)) : null;

            // ✅ Validação: whitelist simples
            $allowed = ['light', 'dark', 'system'];
            if (!in_array($theme, $allowed, true)) {
                $this->response->jsonBody([
                    'error'  => 'Validação falhou.',
                    'fields' => ['theme' => 'Deve ser: light, dark ou system.'],
                ], 422)->send();
                return;
            }

            // (Opcional) mantenha GUMP só pra filtros (se quiser)
            // $gump = new GUMP();
            // $gump->filter_rules(['theme' => 'trim|sanitize_string']);
            // $data = $gump->run(['theme' => $theme]);
            // $theme = $data['theme'] ?? $theme;

            /** @var Usuario|null $user */
            $user = Usuario::find($this->adminId);
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
            // loga com contexto (mantém teu helper se você já adicionou)
            $this->failAndLog(
                $e,
                'Falha ao salvar preferência de tema.',
                500,
                ['payload' => ['theme' => $theme ?? null]]
            );
        }
    }
}
