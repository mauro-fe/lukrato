<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use Application\Repositories\TelefoneRepository;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;

class TelefoneRepositoryTest extends TestCase
{
    private TelefoneRepository $repository;

    /** @var int[] */
    private array $cleanupUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new TelefoneRepository();
    }

    protected function tearDown(): void
    {
        if ($this->cleanupUserIds !== []) {
            Capsule::table('telefones')->whereIn('id_usuario', $this->cleanupUserIds)->delete();
            Capsule::table('usuarios')->whereIn('id', $this->cleanupUserIds)->delete();
        }

        $this->cleanupUserIds = [];

        parent::tearDown();
    }

    public function testUpdateOrCreateCreatesAndUpdatesSingleRow(): void
    {
        $userId = $this->createUser();

        $this->repository->updateOrCreate($userId, '98', '999111222');
        $created = $this->repository->getByUserId($userId);

        $this->assertNotNull($created);
        $this->assertSame('999111222', $created->numero);
        $this->assertNotNull($created->id_ddd);
        $firstId = (int) $created->id_telefone;

        $this->repository->updateOrCreate($userId, '97', '111222333');
        $updated = $this->repository->getByUserId($userId);

        $this->assertNotNull($updated);
        $this->assertSame($firstId, (int) $updated->id_telefone);
        $this->assertSame('111222333', $updated->numero);
        $this->assertSame(
            1,
            Capsule::table('telefones')->where('id_usuario', $userId)->count()
        );
    }

    public function testDeleteRemovesPhoneFromUser(): void
    {
        $userId = $this->createUser();

        $this->repository->updateOrCreate($userId, '96', '555444333');
        $this->assertNotNull($this->repository->getByUserId($userId));

        $this->repository->delete($userId);

        $this->assertNull($this->repository->getByUserId($userId));
    }

    public function testGetDddByIdReturnsNullWhenIdIsNull(): void
    {
        $this->assertNull($this->repository->getDddById(null));
    }

    private function createUser(): int
    {
        $email = 'fone-test-' . bin2hex(random_bytes(6)) . '@example.com';
        $userId = (int) Capsule::table('usuarios')->insertGetId([
            'nome' => 'Teste Telefone',
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->cleanupUserIds[] = $userId;

        return $userId;
    }
}
