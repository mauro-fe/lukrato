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

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
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
            // Em produção PUBLIC_PATH aponta para ~/public_html (document root)
            // Em dev BASE_PATH/public é usado como fallback
            $publicRoot = defined('PUBLIC_PATH') ? PUBLIC_PATH : BASE_PATH . '/public';
            $uploadDir = $publicRoot . '/assets/uploads/avatars';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $sourceImage = $this->createSourceAvatarImage($mime, $file['tmp_name']);

            if (!$sourceImage) {
                Response::error('Erro ao processar imagem', 500);
                return;
            }

            $preparedImage = $this->prepareAvatarImage($sourceImage, $mime, $file['tmp_name']);

            // Salvar como WebP (ANTES de deletar o antigo para não perder em caso de falha)
            $filename = 'avatar_' . $user->id . '_' . uniqid() . '.webp';
            $filepath = $uploadDir . '/' . $filename;
            if (!imagewebp($preparedImage, $filepath, 85)) {
                imagedestroy($preparedImage);
                Response::error('Nao foi possivel salvar a foto de perfil.', 500);
                return;
            }
            imagedestroy($preparedImage);

            // Deletar avatar antigo APÓS salvar o novo com sucesso
            $oldAvatarPath = $user->avatar;
            if ($oldAvatarPath) {
                $oldPath = $publicRoot . '/' . $oldAvatarPath;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Atualizar no banco
            $relativePath = 'assets/uploads/avatars/' . $filename;
            $user->avatar = $relativePath;
            $user->avatar_focus_x = 50;
            $user->avatar_focus_y = 50;
            $user->avatar_zoom = 1.00;
            $user->save();

            Response::success([
                'message' => 'Foto de perfil atualizada!',
                'avatar'  => rtrim(BASE_URL, '/') . '/' . $relativePath,
                'avatar_settings' => $this->buildAvatarSettings($user),
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
                $publicRoot = defined('PUBLIC_PATH') ? PUBLIC_PATH : BASE_PATH . '/public';
                $filePath = $publicRoot . '/' . $user->avatar;
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
                $user->avatar = null;
                $user->avatar_focus_x = 50;
                $user->avatar_focus_y = 50;
                $user->avatar_zoom = 1.00;
                $user->save();
            }

            Response::success([
                'message' => 'Foto de perfil removida',
                'avatar'  => '',
                'avatar_settings' => $this->buildAvatarSettings($user),
            ]);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'remove_avatar',
                'user_id' => Auth::user()?->id,
            ]);
            Response::error('Erro ao remover foto de perfil', 500);
        }
    }

    public function updateAvatarPreferences(): void
    {
        try {
            $user = Auth::user();
            if (!$user) {
                Response::error('Nao autenticado', 401);
                return;
            }

            $payload = $_POST;

            if ($payload === []) {
                $rawInput = file_get_contents('php://input');
                if ($rawInput) {
                    $decoded = json_decode($rawInput, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $payload = $decoded;
                    }
                }
            }

            $user->avatar_focus_x = $this->clampAvatarFocus($payload['position_x'] ?? 50);
            $user->avatar_focus_y = $this->clampAvatarFocus($payload['position_y'] ?? 50);
            $user->avatar_zoom = $this->clampAvatarZoom($payload['zoom'] ?? 1);
            $user->save();

            Response::success([
                'message' => 'Enquadramento da foto atualizado.',
                'avatar_settings' => $this->buildAvatarSettings($user),
            ]);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'update_avatar_preferences',
                'user_id' => Auth::user()?->id,
            ]);
            Response::error('Erro ao atualizar o enquadramento da foto.', 500);
        }
    }

    private function buildAvatarSettings(object $user): array
    {
        return [
            'position_x' => $this->clampAvatarFocus($user->avatar_focus_x ?? 50),
            'position_y' => $this->clampAvatarFocus($user->avatar_focus_y ?? 50),
            'zoom' => $this->clampAvatarZoom($user->avatar_zoom ?? 1),
        ];
    }

    private function clampAvatarFocus(mixed $value): int
    {
        return max(0, min(100, (int) round((float) $value)));
    }

    private function clampAvatarZoom(mixed $value): float
    {
        return max(1.0, min(2.0, round((float) $value, 2)));
    }

    private function createSourceAvatarImage(string $mime, string $tmpPath)
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmpPath),
            'image/png'  => @imagecreatefrompng($tmpPath),
            'image/webp' => @imagecreatefromwebp($tmpPath),
            default      => false,
        };

        if ($image && in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        return $image;
    }

    private function prepareAvatarImage($sourceImage, string $mime, string $tmpPath)
    {
        $orientedImage = $this->normalizeAvatarOrientation($sourceImage, $mime, $tmpPath);

        $sourceWidth = imagesx($orientedImage);
        $sourceHeight = imagesy($orientedImage);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            return $orientedImage;
        }

        $maxEdge = 1024;
        $scale = min($maxEdge / $sourceWidth, $maxEdge / $sourceHeight, 1);
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));

        if ($targetWidth === $sourceWidth && $targetHeight === $sourceHeight) {
            return $orientedImage;
        }

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagecopyresampled($canvas, $orientedImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        imagedestroy($orientedImage);

        return $canvas;
    }

    private function normalizeAvatarOrientation($image, string $mime, string $tmpPath)
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($tmpPath);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated !== $image) {
            imagedestroy($image);
        }

        return $rotated ?: $image;
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
