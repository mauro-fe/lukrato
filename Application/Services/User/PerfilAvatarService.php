<?php

declare(strict_types=1);

namespace Application\Services\User;

use Application\Models\Usuario;

class PerfilAvatarService
{
    public function __construct(
        private readonly ?string $publicRoot = null
    ) {
    }

    /**
     * @param array<string, mixed>|null $file
     * @return array<string, mixed>
     */
    public function uploadAvatar(Usuario $user, ?array $file): array
    {
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Nenhuma imagem enviada ou erro no upload',
            ];
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string) $file['tmp_name']);

        if (!in_array($mime, $allowedMimes, true)) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Tipo de arquivo nÃ£o permitido. Use JPEG, PNG ou WebP.',
            ];
        }

        $maxSize = 2 * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) > $maxSize) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'A imagem nÃ£o pode ter mais de 2MB.',
            ];
        }

        $publicRoot = $this->resolvePublicRoot();
        $uploadDir = $publicRoot . '/assets/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $sourceImage = $this->createSourceAvatarImage($mime, (string) $file['tmp_name']);
        if (!$sourceImage) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Erro ao processar imagem',
            ];
        }

        $preparedImage = $this->prepareAvatarImage($sourceImage, $mime, (string) $file['tmp_name']);

        $filename = 'avatar_' . $user->id . '_' . uniqid() . '.webp';
        $filepath = $uploadDir . '/' . $filename;
        if (!imagewebp($preparedImage, $filepath, 85)) {
            imagedestroy($preparedImage);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'Nao foi possivel salvar a foto de perfil.',
            ];
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

        return [
            'success' => true,
            'data' => [
                'message' => 'Foto de perfil atualizada!',
                'avatar' => rtrim(BASE_URL, '/') . '/' . $relativePath,
                'avatar_settings' => $this->buildAvatarSettings($user),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function removeAvatar(Usuario $user): array
    {
        if ($user->avatar) {
            $filePath = $this->resolvePublicRoot() . '/' . $user->avatar;
            if (is_file($filePath)) {
                @unlink($filePath);
            }

            $user->avatar = null;
            $this->resetAvatarPreferences($user);
            $user->save();
        }

        return [
            'success' => true,
            'data' => [
                'message' => 'Foto de perfil removida',
                'avatar' => '',
                'avatar_settings' => $this->buildAvatarSettings($user),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateAvatarPreferences(Usuario $user, array $payload): array
    {
        $user->avatar_focus_x = $this->clampAvatarFocus($payload['position_x'] ?? 50);
        $user->avatar_focus_y = $this->clampAvatarFocus($payload['position_y'] ?? 50);
        $user->avatar_zoom = $this->clampAvatarZoom($payload['zoom'] ?? 1);
        $user->save();

        return [
            'success' => true,
            'data' => [
                'message' => 'Enquadramento da foto atualizado.',
                'avatar_settings' => $this->buildAvatarSettings($user),
            ],
        ];
    }

    private function resolvePublicRoot(): string
    {
        if ($this->publicRoot !== null && $this->publicRoot !== '') {
            return rtrim($this->publicRoot, '/\\');
        }

        return defined('PUBLIC_PATH') ? PUBLIC_PATH : BASE_PATH . '/public';
    }

    private function resetAvatarPreferences(Usuario $user): void
    {
        $user->avatar_focus_x = 50;
        $user->avatar_focus_y = 50;
        $user->avatar_zoom = 1.00;
    }

    /**
     * @return array<string, int|float>
     */
    private function buildAvatarSettings(Usuario $user): array
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
}
