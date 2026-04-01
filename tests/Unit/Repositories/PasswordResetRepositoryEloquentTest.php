<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use Application\Repositories\PasswordResetRepositoryEloquent;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;

class PasswordResetRepositoryEloquentTest extends TestCase
{
    private PasswordResetRepositoryEloquent $repository;

    /** @var string[] */
    private array $cleanupEmails = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PasswordResetRepositoryEloquent();
    }

    protected function tearDown(): void
    {
        if ($this->cleanupEmails !== []) {
            Capsule::table('password_resets')
                ->whereIn('email', $this->cleanupEmails)
                ->delete();
        }

        $this->cleanupEmails = [];

        parent::tearDown();
    }

    public function testCreateFindAndMarkAsUsedLifecycle(): void
    {
        $email = 'reset-' . bin2hex(random_bytes(6)) . '@example.com';
        $selector = bin2hex(random_bytes(8));
        $tokenHash = hash('sha256', random_bytes(12));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $this->cleanupEmails[] = $email;

        $created = $this->repository->create($email, $selector, $tokenHash, $expiresAt);
        $this->assertSame($email, $created->email);
        $this->assertSame($selector, $created->selector);
        $this->assertSame($tokenHash, $created->token_hash);

        $foundBySelector = $this->repository->findValidSelector($selector);
        $this->assertNotNull($foundBySelector);
        $this->assertSame($email, $foundBySelector->email);

        $foundByHash = $this->repository->findValidTokenHash($tokenHash);
        $this->assertNotNull($foundByHash);
        $this->assertSame($email, $foundByHash->email);

        $this->repository->markAsUsed($foundBySelector);

        $this->assertNull($this->repository->findValidSelector($selector));
        $this->assertNull($this->repository->findValidTokenHash($tokenHash));

        $row = Capsule::table('password_resets')
            ->where('email', $email)
            ->first();

        $this->assertNotNull($row);
        $this->assertNull($row->selector);
        $this->assertNull($row->token_hash);
        $this->assertNotNull($row->used_at);
    }

    public function testDeleteExistingTokensRemovesAllRowsForEmail(): void
    {
        $email = 'delete-reset-' . bin2hex(random_bytes(6)) . '@example.com';
        $this->cleanupEmails[] = $email;

        $this->repository->create(
            $email,
            bin2hex(random_bytes(8)),
            hash('sha256', random_bytes(12)),
            date('Y-m-d H:i:s', time() + 3600)
        );
        $this->repository->create(
            $email,
            bin2hex(random_bytes(8)),
            hash('sha256', random_bytes(12)),
            date('Y-m-d H:i:s', time() + 3600)
        );

        $this->assertGreaterThan(
            0,
            Capsule::table('password_resets')->where('email', $email)->count()
        );

        $this->repository->deleteExistingTokens($email);

        $this->assertSame(
            0,
            Capsule::table('password_resets')->where('email', $email)->count()
        );
    }
}
