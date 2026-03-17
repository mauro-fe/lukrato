<?php

namespace Application\Controllers\Api\User;

use Application\Controllers\BaseController;
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

class PerfilController extends BaseController
{
    private PerfilService $perfilService;
    private PerfilValidator $validator;

    public function __construct(
        ?PerfilService $perfilService = null,
        ?PerfilValidator $validator = null
    ) {
        parent::__construct();

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
            $user = $this->resolveAuthenticatedUser('Não autenticado');
            if (!$user) {
                return;
            }

            $this->releaseSession();

            $perfil = $this->perfilService->obterPerfil($user->id);

            if (!$perfil) {
                Response::error('Usuário não encontrado', 404);
                return;
            }

            Response::success([
                'user' => $perfil,
            ], 'Perfil carregado');
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'show_perfil');
            Response::error('Erro interno', 500);
        }
    }

    public function update(): void
    {
        try {
            $user = $this->resolveAuthenticatedUser('Não autenticado');
            if (!$user) {
                return;
            }

            $dto = PerfilUpdateDTO::fromRequest($this->getPostOrJsonPayload());

            $errors = $this->validator->validate($dto, $user->id);

            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            $perfilAtualizado = $this->perfilService->atualizarPerfil($user->id, $dto);

            $achievementService = new AchievementService();
            $newAchievements = $achievementService->checkAndUnlockAchievements($user->id, 'profile_update');

            Response::success([
                'message' => 'Perfil atualizado com sucesso',
                'user' => $perfilAtualizado,
                'new_achievements' => $newAchievements,
            ]);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'update_perfil');

            $statusCode = $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                ? 404
                : 500;

            Response::error('Erro interno ao atualizar perfil', $statusCode);
        }
    }

    public function updatePassword(): void
    {
        try {
            $user = $this->resolveAuthenticatedUser('Não autenticado');
            if (!$user) {
                return;
            }

            $payload = $this->getPostOrJsonPayload();
            $senhaAtual = $payload['senha_atual'] ?? '';
            $novaSenha = $payload['nova_senha'] ?? '';
            $confSenha = $payload['conf_senha'] ?? '';

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

            $user->senha = $novaSenha;
            $user->save();

            Response::success([
                'message' => 'Senha alterada com sucesso',
            ]);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'update_password');
            Response::error('Erro ao alterar senha', 500);
        }
    }

    public function updateTheme(): void
    {
        try {
            $user = $this->resolveAuthenticatedUser('Não autenticado');
            if (!$user) {
                return;
            }

            $payload = $this->getPostOrJsonPayload();
            $theme = $payload['theme'] ?? null;

            if (!in_array($theme, ['light', 'dark'], true)) {
                Response::error('Tema inválido. Use "light" ou "dark"', 400);
                return;
            }

            $user->theme_preference = $theme;
            $user->save();

            Response::success([
                'message' => 'Tema atualizado com sucesso',
                'theme' => $theme,
            ]);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'update_theme');
            Response::error('Erro ao atualizar tema', 500);
        }
    }

    public function uploadAvatar(): void
    {
        try {
            $user = $this->resolveAuthenticatedUser('Não autenticado');
            if (!$user) {
                return;
            }

            if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                Response::error('Nenhuma imagem enviada ou erro no upload', 400);
                return;
            }

            $file = $_FILES['avatar'];

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo  = new \finfo(FILEINFO_MIME_TYPE);
            $mime   = $finfo->file($file['tmp_name']);

            if (!in_array($mime, $allowedMimes, true)) {
                Response::error('Tipo de arquivo não permitido. Use JPEG, PNG ou WebP.', 400);
                return;
            }

            $maxSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                Response::error('A imagem não pode ter mais de 2MB.', 400);
                return;
            }

            $publicRoot = $this->resolvePublicRoot();
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

            $filename = 'avatar_' . $user->id . '_' . uniqid() . '.webp';
            $filepath = $uploadDir . '/' . $filename;
            if (!imagewebp($preparedImage, $filepath, 85)) {
                imagedestroy($preparedImage);
                Response::error('Nao foi possivel salvar a foto de perfil.', 500);
                return;
            }
            imagedestroy($preparedImage);

            $oldAvatarPath = $user->avatar;
            if ($oldAvatarPath) {
                $oldPath = $publicRoot . '/' . $oldAvatarPath;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $relativePath = 'assets/uploads/avatars/' . $filename;
            $user->avatar = $relativePath;
            $this->resetAvatarPreferences($user);
            $user->save();

            Response::success([
                'message' => 'Foto de perfil atualizada!',
                'avatar' => rtrim(BASE_URL, '/') . '/' . $relativePath,
                'avatar_settings' => $this->buildAvatarSettings($user),
            ]);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'upload_avatar');
            Response::error('Erro ao enviar foto de perfil', 500);
        }
    }

    public function removeAvatar(): void
    {
        try {
            $user = $this->resolveAuthenticatedUser('Não autenticado');
            if (!$user) {
                return;
            }

            if ($user->avatar) {
                $publicRoot = $this->resolvePublicRoot();
                $filePath = $publicRoot . '/' . $user->avatar;
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
                $user->avatar = null;
                $this->resetAvatarPreferences($user);
                $user->save();
            }

            Response::success([
                'message' => 'Foto de perfil removida',
                'avatar' => '',
                'avatar_settings' => $this->buildAvatarSettings($user),
            ]);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'remove_avatar');
            Response::error('Erro ao remover foto de perfil', 500);
        }
    }

    public function updateAvatarPreferences(): void
    {
        try {
            $user = $this->resolveAuthenticatedUser('Nao autenticado');
            if (!$user) {
                return;
            }

            $payload = $this->getPostOrJsonPayload();

            $user->avatar_focus_x = $this->clampAvatarFocus($payload['position_x'] ?? 50);
            $user->avatar_focus_y = $this->clampAvatarFocus($payload['position_y'] ?? 50);
            $user->avatar_zoom = $this->clampAvatarZoom($payload['zoom'] ?? 1);
            $user->save();

            Response::success([
                'message' => 'Enquadramento da foto atualizado.',
                'avatar_settings' => $this->buildAvatarSettings($user),
            ]);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'update_avatar_preferences');
            Response::error('Erro ao atualizar o enquadramento da foto.', 500);
        }
    }

    private function resolveAuthenticatedUser(string $message): ?object
    {
        $user = Auth::user();

        if (!$user) {
            Response::error($message, 401);
            return null;
        }

        return $user;
    }

    private function getPostOrJsonPayload(): array
    {
        if ($_POST !== []) {
            return $_POST;
        }

        $payload = $this->getJson();
        return is_array($payload) ? $payload : [];
    }

    private function resolvePublicRoot(): string
    {
        return defined('PUBLIC_PATH') ? PUBLIC_PATH : BASE_PATH . '/public';
    }

    private function resetAvatarPreferences(object $user): void
    {
        $user->avatar_focus_x = 50;
        $user->avatar_focus_y = 50;
        $user->avatar_zoom = 1.00;
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
            'image/png' => @imagecreatefrompng($tmpPath),
            'image/webp' => @imagecreatefromwebp($tmpPath),
            default => false,
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
            $user = $this->resolveAuthenticatedUser('Não autenticado');
            if (!$user) {
                return;
            }

            $this->perfilService->deletarConta($user->id);

            Auth::logout();

            Response::success([
                'message' => 'Conta excluída com sucesso',
            ]);
        } catch (Throwable $e) {
            $this->logPerfilException($e, 'delete_account');
            Response::error('Erro ao excluir conta', 500);
        }
    }

    private function logPerfilException(Throwable $e, string $action): void
    {
        LogService::captureException($e, LogCategory::AUTH, [
            'action' => $action,
            'user_id' => Auth::user()?->id,
        ]);
    }
}
