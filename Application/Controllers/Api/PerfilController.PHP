<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\PerfilService;
use Application\DTOs\PerfilUpdateDTO;
use Application\Validators\PerfilValidator;
use Application\Providers\PerfilControllerFactory;
use Throwable;


class PerfilController
{
    private PerfilService $perfilService;
    private PerfilValidator $validator;

    public function __construct(
        ?PerfilService $perfilService = null,
        ?PerfilValidator $validator = null
    ) {
        if ($perfilService !== null && $validator !== null) {
            $this->perfilService = $perfilService;
            $this->validator = $validator;
            return;
        }

        [
            $this->perfilService,
            $this->validator
        ] = PerfilControllerFactory::buildDependencies();
    }


    public function show(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            $perfil = $this->perfilService->obterPerfil($user->id);

            if (!$perfil) {
                Response::error('Usuário não encontrado', 404);
                return;
            }

            Response::success([
                'user' => $perfil,
            ], 'Perfil carregado');
        } catch (Throwable $e) {
            Response::error('Erro interno', 500, [
                'exception' => $e->getMessage()
            ]);
        }
    }


    public function update(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            $dto = PerfilUpdateDTO::fromRequest($_POST);

            $errors = $this->validator->validate($dto, $user->id);

            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            $perfilAtualizado = $this->perfilService->atualizarPerfil($user->id, $dto);

            Response::success([
                'message' => 'Perfil atualizado com sucesso',
                'user' => $perfilAtualizado,
            ]);
        } catch (Throwable $e) {
            $statusCode = $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                ? 404
                : 500;

            Response::error(
                'Erro interno ao atualizar perfil',
                $statusCode,
                ['exception' => $e->getMessage()]
            );
        }
    }

    public function updateTheme(): void
    {
        try {
            error_log("🎨 [TEMA] Requisição recebida - POST: " . json_encode($_POST));

            $user = Auth::user();

            if (!$user) {
                error_log("🎨 [TEMA] Erro: Usuário não autenticado");
                Response::error('Não autenticado', 401);
                return;
            }

            error_log("🎨 [TEMA] Usuário autenticado: ID={$user->id}, Nome={$user->nome}");

            $theme = $_POST['theme'] ?? null;
            error_log("🎨 [TEMA] Tema recebido: " . var_export($theme, true));

            // Validar tema
            if (!in_array($theme, ['light', 'dark'], true)) {
                error_log("🎨 [TEMA] Erro: Tema inválido - {$theme}");
                Response::error('Tema inválido. Use "light" ou "dark"', 400);
                return;
            }

            // Atualizar tema
            $temaAnterior = $user->theme_preference;
            $user->theme_preference = $theme;
            $user->save();

            error_log("🎨 [TEMA] Tema atualizado com sucesso: {$temaAnterior} -> {$theme}");

            Response::success([
                'message' => 'Tema atualizado com sucesso',
                'theme' => $theme,
            ]);
        } catch (Throwable $e) {
            error_log("🎨 [TEMA] ERRO: " . $e->getMessage());
            error_log("🎨 [TEMA] Stack: " . $e->getTraceAsString());
            Response::error(
                'Erro ao atualizar tema',
                500,
                ['exception' => $e->getMessage()]
            );
        }
    }
}
