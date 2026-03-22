<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use Application\Repositories\DocumentoRepository;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;

class DocumentoRepositoryTest extends TestCase
{
    private DocumentoRepository $repository;

    /** @var int[] */
    private array $cleanupUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DocumentoRepository();
    }

    protected function tearDown(): void
    {
        if ($this->cleanupUserIds !== []) {
            Capsule::table('documentos')->whereIn('id_usuario', $this->cleanupUserIds)->delete();
            Capsule::table('usuarios')->whereIn('id', $this->cleanupUserIds)->delete();
        }

        $this->cleanupUserIds = [];

        parent::tearDown();
    }

    public function testUpdateOrCreateCpfStoresHashAndEncryptedWithoutPlainText(): void
    {
        $userId = $this->createUser();
        $cpf = '52998224725';

        $this->repository->updateOrCreateCpf($userId, $cpf);

        $documento = Capsule::table('documentos')
            ->where('id_usuario', $userId)
            ->first();

        $this->assertNotNull($documento);
        $this->assertNull($documento->numero);
        $this->assertSame(hash('sha256', $cpf), $documento->cpf_hash);
        $this->assertIsString($documento->cpf_encrypted);
        $this->assertNotSame($cpf, $documento->cpf_encrypted);
        $this->assertSame($cpf, $this->repository->getCpf($userId));
        $this->assertTrue($this->repository->hasCpf($userId));
    }

    public function testCpfExistsUsesHashBasedLookup(): void
    {
        $firstUserId = $this->createUser();
        $secondUserId = $this->createUser();
        $cpf = '52998224725';

        $this->repository->updateOrCreateCpf($firstUserId, $cpf);

        $this->assertTrue($this->repository->cpfExists($cpf, $secondUserId));
        $this->assertFalse($this->repository->cpfExists($cpf, $firstUserId));
    }

    private function createUser(): int
    {
        $email = 'cpf-test-' . bin2hex(random_bytes(6)) . '@example.com';
        $userId = (int) Capsule::table('usuarios')->insertGetId([
            'nome' => 'Teste CPF',
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->cleanupUserIds[] = $userId;

        return $userId;
    }
}
