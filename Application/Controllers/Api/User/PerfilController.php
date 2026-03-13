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

    public function uploadAvatar(): void
    {
        try {
            $user = Auth::user();
            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                Response::error('Nenhuma imagem enviada ou erro no upload', 400);
                return;
            }

            $file = $_FILES['avatar'];

            // Validar tipo MIME
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);

            if (!in_array($mime, $allowedMimes)) {
                Response::error('Tipo de arquivo não permitido. Use JPEG, PNG ou WebP.', 400);
                return;
            }

            // Validar tamanho (max 2MB)
            $maxSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                Response::error('A imagem não pode ter mais de 2MB.', 400);
                return;
            }

            // Criar diretório se não existe
            $uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/assets/uploads/avatars';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Carregar imagem com GD
            $sourceImage = match ($mime) {
                'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
                'image/png'  => imagecreatefrompng($file['tmp_name']),
                'image/webp' => imagecreatefromwebp($file['tmp_name']),
                default      => false,
            };

            if (!$sourceImage) {
                Response::error('Erro ao processar imagem', 500);
                return;
            }

            // Redimensionar para 256x256 (crop quadrado centralizado)
            $srcW = imagesx($sourceImage);
            $srcH = imagesy($sourceImage);
            $size = min($srcW, $srcH);
            $srcX = (int) (($srcW - $size) / 2);
            $srcY = (int) (($srcH - $size) / 2);

            $resized = imagecreatetruecolor(256, 256);
            imagecopyresampled($resized, $sourceImage, 0, 0, $srcX, $srcY, 256, 256, $size, $size);
            imagedestroy($sourceImage);

            // Deletar avatar antigo se existir
            if ($user->avatar) {
                $oldPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $user->avatar;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Salvar como WebP
            $filename = 'avatar_' . $user->id . '_' . uniqid() . '.webp';
            $filepath = $uploadDir . '/' . $filename;
            imagewebp($resized, $filepath, 85);
            imagedestroy($resized);

            // Atualizar no banco
            $relativePath = 'assets/uploads/avatars/' . $filename;
            $user->avatar = $relativePath;
            $user->save();

            Response::success([
                'message' => 'Foto de perfil atualizada!',
                'avatar'  => rtrim(BASE_URL, '/') . '/' . $relativePath,
            ]);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'upload_avatar',
                'user_id' => Auth::user()?->id,
            ]);
            Response::error('Erro ao enviar foto de perfil', 500);
        }
    }

    public function removeAvatar(): void
    {
        try {
            $user = Auth::user();
            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            if ($user->avatar) {
                $filePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $user->avatar;
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
                $user->avatar = null;
                $user->save();
            }

            Response::success([
                'message' => 'Foto de perfil removida',
                'avatar'  => '',
            ]);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'remove_avatar',
                'user_id' => Auth::user()?->id,
            ]);
            Response::error('Erro ao remover foto de perfil', 500);
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
