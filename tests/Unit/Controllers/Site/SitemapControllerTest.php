<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Site;

use Application\Controllers\Site\SitemapController;
use PHPUnit\Framework\TestCase;

class SitemapControllerTest extends TestCase
{
    public function testIndexReturnsXmlResponse(): void
    {
        $controller = new TestableSitemapController(
            [(object) ['slug' => 'economia']],
            [new FakeSitemapPost('como-poupar', 'uploads/capa.png', 'Como poupar', '2026-03-18')]
        );

        $response = $controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/xml; charset=UTF-8', $response->getHeaders()['Content-Type'] ?? null);
        $this->assertSame('noindex', $response->getHeaders()['X-Robots-Tag'] ?? null);
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $response->getContent());
        $this->assertStringContainsString(BASE_URL . 'blog/categoria/economia', $response->getContent());
        $this->assertStringContainsString(BASE_URL . 'blog/como-poupar', $response->getContent());
        $this->assertStringContainsString('<image:image>', $response->getContent());
    }
}

final class TestableSitemapController extends SitemapController
{
    public function __construct(
        private readonly iterable $categorias,
        private readonly iterable $posts
    ) {
    }

    protected function getCategorias(): iterable
    {
        return $this->categorias;
    }

    protected function getPosts(): iterable
    {
        return $this->posts;
    }
}

final class FakeSitemapPost
{
    public object $updated_at;

    public function __construct(
        public string $slug,
        public ?string $imagem_capa,
        public string $titulo,
        string $lastmod
    ) {
        $this->updated_at = new class ($lastmod) {
            public function __construct(private readonly string $lastmod)
            {
            }

            public function toDateString(): string
            {
                return $this->lastmod;
            }
        };
    }
}
