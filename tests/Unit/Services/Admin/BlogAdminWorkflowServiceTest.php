<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use Application\Repositories\BlogPostRepository;
use Application\Services\Admin\BlogAdminWorkflowService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class BlogAdminWorkflowServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreatePostReturnsValidationErrorsWhenPayloadIsEmpty(): void
    {
        $service = new BlogAdminWorkflowService(Mockery::mock(BlogPostRepository::class));

        $result = $service->createPost([]);

        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['status']);
        $this->assertSame('Validation failed', $result['message']);
        $this->assertSame([
            'titulo' => 'O título é obrigatório.',
            'conteudo' => 'O conteúdo é obrigatório.',
        ], $result['errors']);
    }

    public function testUploadImageReturnsBadRequestWhenFileIsMissing(): void
    {
        $service = new BlogAdminWorkflowService(Mockery::mock(BlogPostRepository::class));

        $result = $service->uploadImage([], 'C:/xampp/htdocs/lukrato/public', 'http://localhost');

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('Nenhuma imagem enviada ou erro no upload', $result['message']);
    }
}
