<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use Application\Models\Sexo;
use Application\Repositories\UsuarioRepository;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;

class UsuarioRepositoryTest extends TestCase
{
    private UsuarioRepository $repository;

    /** @var int[] */
    private array $cleanupUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new UsuarioRepository();
    }

    protected function tearDown(): void
    {
        if ($this->cleanupUserIds !== []) {
            Capsule::table('usuarios')->whereIn('id', $this->cleanupUserIds)->delete();
        }

        parent::tearDown();
    }

    public function testUpdateMapsAccentedSexoToNaoBinario(): void
    {
        $userId = $this->createUser('sexo-map');

        $updated = $this->repository->update($userId, [
            'nome' => 'Usuario Sexo',
            'email' => 'sexo-map-updated@example.com',
            'data_nascimento' => '1991-04-15',
            'sexo' => 'Não binário',
        ]);

        $this->assertNotNull($updated->id_sexo);

        $sexo = Sexo::find((int) $updated->id_sexo);
        $this->assertNotNull($sexo);
        $this->assertSame('Nao-binario', $sexo->nm_sexo);
    }

    public function testEmailLookupIsCaseInsensitive(): void
    {
        $firstUserId = $this->createUser('email-main');
        $secondUserId = $this->createUser('email-other');

        $hasPendingEmailColumn = Capsule::schema()->hasColumn('usuarios', 'pending_email');

        $updateData = [
            'email' => 'CaseSensitive@Test.com',
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($hasPendingEmailColumn) {
            $updateData['pending_email'] = 'Pending@Test.com';
        }

        Capsule::table('usuarios')
            ->where('id', $firstUserId)
            ->update($updateData);

        $this->assertTrue($this->repository->emailExists('casesensitive@test.com', $secondUserId));
        $this->assertFalse($this->repository->emailExists('casesensitive@test.com', $firstUserId));

        $foundByEmail = $this->repository->findByEmailOrPending('CASESENSITIVE@test.com');
        $this->assertNotNull($foundByEmail);
        $this->assertSame($firstUserId, (int) $foundByEmail->id);

        if ($hasPendingEmailColumn) {
            $foundByPending = $this->repository->findByEmailOrPending('pending@test.com');
            $this->assertNotNull($foundByPending);
            $this->assertSame($firstUserId, (int) $foundByPending->id);
        }
    }

    private function createUser(string $prefix): int
    {
        $email = $prefix . '-' . bin2hex(random_bytes(6)) . '@example.com';
        $userId = (int) Capsule::table('usuarios')->insertGetId([
            'nome' => 'Teste Usuario',
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->cleanupUserIds[] = $userId;

        return $userId;
    }
}
