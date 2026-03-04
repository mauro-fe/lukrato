<?php

namespace Application\Controllers\Site;

use Application\Models\BlogCategoria;
use Application\Models\BlogPost;

/**
 * SitemapController
 * Gera sitemap.xml dinâmico com todas as páginas públicas + blog.
 */
class SitemapController
{
    public function index(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex');

        $baseUrl = rtrim(BASE_URL, '/');

        // ── Páginas estáticas ──
        $staticPages = [
            ['loc' => $baseUrl . '/',            'changefreq' => 'weekly',  'priority' => '1.0'],
            ['loc' => $baseUrl . '/login',       'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => $baseUrl . '/termos',      'changefreq' => 'yearly',  'priority' => '0.3'],
            ['loc' => $baseUrl . '/privacidade', 'changefreq' => 'yearly',  'priority' => '0.3'],
            ['loc' => $baseUrl . '/lgpd',        'changefreq' => 'yearly',  'priority' => '0.3'],
        ];

        // ── Hub do blog ──
        $blogHub = [
            'loc'        => $baseUrl . '/aprenda',
            'changefreq' => 'daily',
            'priority'   => '0.9',
        ];

        // ── Categorias do blog ──
        $categorias = BlogCategoria::ordenadas()->get();
        $categoryEntries = [];
        foreach ($categorias as $cat) {
            $categoryEntries[] = [
                'loc'        => $baseUrl . '/aprenda/categoria/' . $cat->slug,
                'changefreq' => 'weekly',
                'priority'   => '0.7',
            ];
        }

        // ── Artigos publicados ──
        $posts = BlogPost::publicados()
            ->recentes()
            ->select(['slug', 'updated_at', 'imagem_capa', 'titulo'])
            ->get();

        $postEntries = [];
        foreach ($posts as $post) {
            $entry = [
                'loc'        => $baseUrl . '/aprenda/' . $post->slug,
                'lastmod'    => $post->updated_at?->toDateString(),
                'changefreq' => 'monthly',
                'priority'   => '0.8',
            ];
            if ($post->imagem_capa) {
                $entry['image'] = [
                    'loc'   => $baseUrl . '/' . $post->imagem_capa,
                    'title' => $post->titulo,
                ];
            }
            $postEntries[] = $entry;
        }

        // ── Gerar XML ──
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        // Páginas estáticas
        foreach ($staticPages as $page) {
            $this->writeUrl($page);
        }

        // Hub
        $this->writeUrl($blogHub);

        // Categorias
        foreach ($categoryEntries as $cat) {
            $this->writeUrl($cat);
        }

        // Artigos
        foreach ($postEntries as $post) {
            $this->writeUrl($post);
        }

        echo '</urlset>' . "\n";
        exit;
    }

    /**
     * Escreve uma entrada <url> no XML.
     */
    private function writeUrl(array $entry): void
    {
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($entry['loc']) . "</loc>\n";

        if (!empty($entry['lastmod'])) {
            echo "    <lastmod>" . htmlspecialchars($entry['lastmod']) . "</lastmod>\n";
        }
        if (!empty($entry['changefreq'])) {
            echo "    <changefreq>" . htmlspecialchars($entry['changefreq']) . "</changefreq>\n";
        }
        if (!empty($entry['priority'])) {
            echo "    <priority>" . htmlspecialchars($entry['priority']) . "</priority>\n";
        }
        if (!empty($entry['image'])) {
            echo "    <image:image>\n";
            echo "      <image:loc>" . htmlspecialchars($entry['image']['loc']) . "</image:loc>\n";
            echo "      <image:title>" . htmlspecialchars($entry['image']['title']) . "</image:title>\n";
            echo "    </image:image>\n";
        }

        echo "  </url>\n";
    }
}
