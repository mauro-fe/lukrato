<?php
// Application/Controllers/Api/UserPreferenceController.php
namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Models\Usuario;
use GUMP;
use Exception;

class UserPreferenceController extends BaseController
{
    /** GET /api/user/theme -> { theme: "light"|"dark"|"system" } */
    public function show(): void
    {
        try {
            $this->requireAuth();

            /** @var Usuario|null $user */
            $user = Usuario::find($this->adminId);
            if (!$user) {
                $this->response
                    ->jsonBody(['error' => 'Usuário não encontrado.'], 404)
                    ->send();
                return;
            }

            $this->response->jsonBody([
                'theme' => $user->theme_preference ?? 'system',
            ])->send();
            // Application/Controllers/Api/UserPreferenceController.php (método update)
        } catch (\Throwable $e) {
            $this->response->jsonBody([
                'error' => 'Falha ao salvar preferência de tema.',
                'debug' => [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => $e->getTraceAsString(),
                ],
            ], 500)->send();
        }
    }

    /** POST /api/user/theme  body: { theme: "light"|"dark"|"system" } */
    public function update(): void
    {
        try {
            $this->requireAuth();

            // Aceita JSON OU form
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

            // Validação (definir regras ANTES do run)
            $gump = new GUMP();
            $gump->validation_rules([
                'theme' => ['required', 'contains_list,light;dark;system'],
            ]);
            $gump->filter_rules([
                'theme' => 'trim|sanitize_string',
            ]);

            $data = $gump->run(['theme' => $theme]);
            if (!$data) {
                $this->response
                    ->jsonBody([
                        'error'  => 'Validação falhou.',
                        'fields' => $gump->get_errors_array(),
                    ], 422)
                    ->send();
                return;
            }

            /** @var Usuario|null $user */
            $user = Usuario::find($this->adminId);
            if (!$user) {
                $this->response
                    ->jsonBody(['error' => 'Usuário não encontrado.'], 404)
                    ->send();
                return;
            }

            $user->theme_preference = $data['theme'];
            $user->save();

            $this->response->jsonBody([
                'message' => 'Preferência de tema atualizada.',
                'theme'   => $user->theme_preference,
            ])->send();
        } catch (Exception $e) {
            $this->response
                ->jsonBody(['error' => 'Falha ao salvar preferência de tema.'], 500)
                ->send();
        }
    }
}
