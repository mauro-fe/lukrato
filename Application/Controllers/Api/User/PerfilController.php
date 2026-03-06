<?php

namespace Application\Controllers\Api\User;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\User\PerfilService;
use Application\Services\Gamification\AchievementService;
use Application\DTO\PerfilUpdateDTO;
use Application\Validators\PerfilValidator;
use Application\Providers\PerfilControllerFactory;
use Application\Services\Infrastructure\LogService;
use Application\Enums\LogCategory;
use Application\Validators\PasswordStrengthValidator;
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
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'show_perfil',
                'user_id' => Auth::user()?->id,
            ]);
            Response::error('Erro interno', 500);
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

            // Verificar conquista de perfil completo
            $achievementService = new AchievementService();
            $newAchievements = $achievementService->checkAndUnlockAchievements($user->id, 'profile_update');

            Response::success([
                'message' => 'Perfil atualizado com sucesso',
                'user' => $perfilAtualizado,
                'new_achievements' => $newAchievements,
            ]);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'update_perfil',
                'user_id' => Auth::user()?->id,
            ]);

            $statusCode = $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                ? 404
                : 500;

            Response::error('Erro interno ao atualizar perfil', $statusCode);
        }
    }

    public function updatePassword(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            $senhaAtual = $_POST['senha_atual'] ?? '';
            $novaSenha = $_POST['nova_senha'] ?? '';
            $confSenha = $_POST['conf_senha'] ?? '';

            // Validações
            if ($senhaAtual === '' || $novaSenha === '' || $confSenha === '') {
                Response::validationError(['senha' => 'Todos os campos de senha são obrigatórios.']);
                return;
            }

            if (!password_verify($senhaAtual, $user->senha)) {
                Response::validationError(['senha_atual' => 'Senha atual incorreta.']);
                return;
            }

            $passwordErrors = PasswordStrengthValidator::validate($novaSenha);
            if (!empty($passwordErrors)) {
                Response::validationError(['nova_senha' => implode(' ', $passwordErrors)]);
                return;
            }

            if ($novaSenha !== $confSenha) {
                Response::validationError(['conf_senha' => 'As senhas não coincidem.']);
                return;
            }

            // Atualiza senha (o hook saving do modelo faz o hash automaticamente)
            $user->senha = $novaSenha;
            $user->save();

            Response::success([
                'message' => 'Senha alterada com sucesso',
            ]);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'update_password',
                'user_id' => Auth::user()?->id,
            ]);
            Response::error('Erro ao alterar senha', 500);
        }
    }

    public function updateTheme(): void
    {
        try {
            $user = Auth::user();
            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            // Tentar obter theme de $_POST primeiro, depois de JSON body
            $theme = $_POST['theme'] ?? null;

            if ($theme === null) {
                $rawInput = file_get_contents('php://input');

                if ($rawInput) {
                    $jsonData = json_decode($rawInput, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['theme'])) {
                        $theme = $jsonData['theme'];
                    }
                }
            }

            // Validar tema
            if (!in_array($theme, ['light', 'dark'], true)) {
                Response::error('Tema inválido. Use "light" ou "dark"', 400);
                return;
            }

            // Atualizar tema
            $user->theme_preference = $theme;
            $user->save();


            Response::success([
                'message' => 'Tema atualizado com sucesso',
                'theme' => $theme,
            ]);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'update_theme',
                'user_id' => Auth::user()?->id,
            ]);
            Response::error('Erro ao atualizar tema', 500);
        }
    }

    public function delete(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            // Deletar todos os dados do usuário
            $this->perfilService->deletarConta($user->id);

            // Fazer logout
            Auth::logout();

            Response::success([
                'message' => 'Conta excluída com sucesso',
            ]);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'delete_account',
                'user_id' => Auth::user()?->id,
            ]);
            Response::error('Erro ao excluir conta', 500);
        }
    }
}
