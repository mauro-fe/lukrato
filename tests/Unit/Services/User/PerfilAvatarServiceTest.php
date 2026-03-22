<?php

declare(strict_types=1);

namespace Tests\Unit\Services\User;

use Application\Models\Usuario;
use Application\Services\User\PerfilAvatarService;
use PHPUnit\Framework\TestCase;

class PerfilAvatarServiceTest extends TestCase
{
    private string $publicRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicRoot = sys_get_temp_dir() . '/lukrato-perfil-avatar-' . uniqid();
        mkdir($this->publicRoot . '/assets/uploads/avatars', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->publicRoot);

        parent::tearDown();
    }

    public function testUploadAvatarReturnsBadRequestWhenFileIsMissing(): void
    {
        $service = new PerfilAvatarService($this->publicRoot);

        $user = new class extends Usuario {
            public function save(array $options = []): bool
            {
                return true;
            }
        };

        $result = $service->uploadAvatar($user, null);

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('Nenhuma imagem enviada ou erro no upload', $result['message']);
    }

    public function testRemoveAvatarClearsFileAndResetsPreferences(): void
    {
        $filePath = $this->publicRoot . '/assets/uploads/avatars/avatar_old.webp';
        file_put_contents($filePath, 'avatar');

        $user = new class extends Usuario {
            public bool $saved = false;

            public function save(array $options = []): bool
            {
                $this->saved = true;

                return true;
            }
        };
        $user->avatar = 'assets/uploads/avatars/avatar_old.webp';
        $user->avatar_focus_x = 11;
        $user->avatar_focus_y = 22;
        $user->avatar_zoom = 1.75;

        $service = new PerfilAvatarService($this->publicRoot);
        $result = $service->removeAvatar($user);

        $this->assertTrue($result['success']);
        $this->assertSame('', $result['data']['avatar']);
        $this->assertSame([
            'position_x' => 50,
            'position_y' => 50,
            'zoom' => 1.0,
        ], $result['data']['avatar_settings']);
        $this->assertNull($user->avatar);
        $this->assertTrue($user->saved);
        $this->assertFileDoesNotExist($filePath);
    }

    public function testUpdateAvatarPreferencesClampsPayloadValues(): void
    {
        $user = new class extends Usuario {
            public bool $saved = false;

            public function save(array $options = []): bool
            {
                $this->saved = true;

                return true;
            }
        };

        $service = new PerfilAvatarService($this->publicRoot);
        $result = $service->updateAvatarPreferences($user, [
            'position_x' => -50,
            'position_y' => 150,
            'zoom' => 8,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame([
            'position_x' => 0,
            'position_y' => 100,
            'zoom' => 2.0,
        ], $result['data']['avatar_settings']);
        $this->assertSame(0, $user->avatar_focus_x);
        $this->assertSame(100, $user->avatar_focus_y);
        $this->assertSame(2.0, $user->avatar_zoom);
        $this->assertTrue($user->saved);
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;
            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
