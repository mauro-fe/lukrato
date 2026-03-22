<?php

namespace Application\Controllers\Site;

use Application\Core\Response;
use Application\Models\BlogCategoria;
use Application\Models\BlogPost;

/**
 * SitemapController
 * Gera sitemap.xml dinamico com todas as paginas publicas + blog.
 */
class SitemapController
{
    public function index(): Response
    {
        $baseUrl = rtrim(BASE_URL, '/');

        $staticPages = [
            ['loc' => $baseUrl . '/', 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => $baseUrl . '/login', 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => $baseUrl . '/termos', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => $baseUrl . '/privacidade', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => $baseUrl . '/lgpd', 'changefreq' => 'yearly', 'priority' => '0.3'],
        ];

        $blogHub = [
            'loc' => $baseUrl . '/blog',
            'changefreq' => 'daily',
            'priority' => '0.9',
        ];

        $categoryEntries = [];
        foreach ($this->getCategorias() as $categoria) {
            $categoryEntries[] = [
                'loc' => $baseUrl . '/blog/categoria/' . $categoria->slug,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        $postEntries = [];
        foreach ($this->getPosts() as $post) {
            $entry = [
                'loc' => $baseUrl . '/blog/' . $post->slug,
                'lastmod' => $post->updated_at?->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ];

            if ($post->imagem_capa) {
                $entry['image'] = [
                    'loc' => $baseUrl . '/' . $post->imagem_capa,
                    'title' => $post->titulo,
                ];
            }

            $postEntries[] = $entry;
        }

        return Response::htmlResponse($this->buildXml($staticPages, $blogHub, $categoryEntries, $postEntries))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex');
    }

    protected function getCategorias(): iterable
    {
        return BlogCategoria::ordenadas()->get();
    }

    protected function getPosts(): iterable
    {
        return BlogPost::publicados()
            ->recentes()
            ->select(['slug', 'updated_at', 'imagem_capa', 'titulo'])
            ->get();
    }

    private function buildXml(array $staticPages, array $blogHub, array $categoryEntries, array $postEntries): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"',
            '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">',
        ];

        foreach ($staticPages as $page) {
            $lines[] = $this->buildUrlXml($page);
        }

        $lines[] = $this->buildUrlXml($blogHub);

        foreach ($categoryEntries as $category) {
            $lines[] = $this->buildUrlXml($category);
        }

        foreach ($postEntries as $post) {
            $lines[] = $this->buildUrlXml($post);
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines) . "\n";
    }

    private function buildUrlXml(array $entry): string
    {
        $lines = [
            '  <url>',
            '    <loc>' . htmlspecialchars((string) $entry['loc']) . '</loc>',
        ];

        if (!empty($entry['lastmod'])) {
            $lines[] = '    <lastmod>' . htmlspecialchars((string) $entry['lastmod']) . '</lastmod>';
        }

        if (!empty($entry['changefreq'])) {
            $lines[] = '    <changefreq>' . htmlspecialchars((string) $entry['changefreq']) . '</changefreq>';
        }

        if (!empty($entry['priority'])) {
            $lines[] = '    <priority>' . htmlspecialchars((string) $entry['priority']) . '</priority>';
        }

        if (!empty($entry['image'])) {
            $lines[] = '    <image:image>';
            $lines[] = '      <image:loc>' . htmlspecialchars((string) $entry['image']['loc']) . '</image:loc>';
            $lines[] = '      <image:title>' . htmlspecialchars((string) $entry['image']['title']) . '</image:title>';
            $lines[] = '    </image:image>';
        }

        $lines[] = '  </url>';

        return implode("\n", $lines);
    }
}
