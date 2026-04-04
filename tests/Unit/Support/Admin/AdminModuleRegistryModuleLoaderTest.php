<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Admin;

use Application\Support\Admin\AdminModuleRegistry;
use PHPUnit\Framework\TestCase;

class AdminModuleRegistryModuleLoaderTest extends TestCase
{
    private ?string $tempModulesPath = null;

    protected function tearDown(): void
    {
        $this->resetRegistryCache();
        $this->clearModulesPathOverride();

        if (is_string($this->tempModulesPath) && $this->tempModulesPath !== '') {
            $this->deleteDirectory($this->tempModulesPath);
        }

        $this->tempModulesPath = null;
        parent::tearDown();
    }

    public function testLoadsDefinitionsFromModuleFilesSupportingSingleAndListPayloads(): void
    {
        $modulesPath = $this->createTempModulesPath('loader-valid');

        $this->writeModuleFile(
            $modulesPath,
            'AaaSingle',
            [
                'key' => 'single_module',
                'label' => 'Single Module',
                'group' => 'Test',
                'placement' => 'hidden',
                'order' => 10,
            ]
        );

        $this->writeModuleFile(
            $modulesPath,
            'BbbList',
            [
                [
                    'key' => 'list_alpha',
                    'label' => 'List Alpha',
                    'group' => 'Test',
                    'placement' => 'hidden',
                    'order' => 20,
                ],
                [
                    'key' => 'list_beta',
                    'label' => 'List Beta',
                    'group' => 'Test',
                    'placement' => 'hidden',
                    'order' => 30,
                ],
            ]
        );

        $this->setModulesPathOverride($modulesPath);
        $modules = AdminModuleRegistry::all();

        $this->assertSame(['single_module', 'list_alpha', 'list_beta'], array_keys($modules));
    }

    public function testThrowsWhenModuleFilePayloadIsInvalid(): void
    {
        $modulesPath = $this->createTempModulesPath('loader-invalid-payload');
        $this->writeModuleFile($modulesPath, 'InvalidPayload', 'not-an-array');

        $this->setModulesPathOverride($modulesPath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid admin module file payload');

        AdminModuleRegistry::all();
    }

    public function testThrowsWhenModuleFileListContainsInvalidDefinition(): void
    {
        $modulesPath = $this->createTempModulesPath('loader-invalid-item');
        $this->writeModuleFile(
            $modulesPath,
            'InvalidDefinition',
            [
                [
                    'key' => 'ok_item',
                    'placement' => 'hidden',
                ],
                'invalid-item',
            ]
        );

        $this->setModulesPathOverride($modulesPath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid admin module definition');

        AdminModuleRegistry::all();
    }

    public function testThrowsWhenDuplicateModuleKeysAreLoaded(): void
    {
        $modulesPath = $this->createTempModulesPath('loader-duplicate-key');

        $this->writeModuleFile(
            $modulesPath,
            'First',
            [
                'key' => 'duplicated_key',
                'placement' => 'hidden',
            ]
        );

        $this->writeModuleFile(
            $modulesPath,
            'Second',
            [
                'key' => 'duplicated_key',
                'placement' => 'hidden',
            ]
        );

        $this->setModulesPathOverride($modulesPath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Duplicate admin module key detected');

        AdminModuleRegistry::all();
    }

    private function createTempModulesPath(string $suffix): string
    {
        $path = BASE_PATH . '/tests/.runtime/admin-modules-' . $suffix . '-' . bin2hex(random_bytes(5));
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $this->tempModulesPath = $path;

        return $path;
    }

    private function writeModuleFile(string $modulesPath, string $folderName, mixed $payload): void
    {
        $dir = $modulesPath . DIRECTORY_SEPARATOR . $folderName;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = $dir . DIRECTORY_SEPARATOR . 'module.php';
        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($payload, true) . ";\n";
        file_put_contents($file, $contents);
    }

    private function setModulesPathOverride(string $path): void
    {
        putenv('ADMIN_MODULES_PATH=' . $path);
        $_ENV['ADMIN_MODULES_PATH'] = $path;
        $this->resetRegistryCache();
    }

    private function clearModulesPathOverride(): void
    {
        putenv('ADMIN_MODULES_PATH');
        unset($_ENV['ADMIN_MODULES_PATH']);
    }

    private function resetRegistryCache(): void
    {
        $property = new \ReflectionProperty(AdminModuleRegistry::class, 'modules');
        $property->setAccessible(true);
        $property->setValue(null, null);
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

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $this->deleteDirectory($fullPath);
                continue;
            }

            @unlink($fullPath);
        }

        @rmdir($path);
    }
}

