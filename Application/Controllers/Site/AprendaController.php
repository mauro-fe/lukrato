<?php

namespace Application\Controllers\Site;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\BlogCategoria;
use Application\Models\BlogPost;
use Application\Repositories\BlogPostRepository;

class AprendaController extends BaseController
{
    private BlogPostRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new BlogPostRepository();
    }

    /**
     * Hub principal - /blog
     */
    public function index(): Response
    {
        $categorias = BlogCategoria::ordenadas()
            ->withCount(['postsPublicados as posts_count'])
            ->get();

        $recentes = BlogPost::publicados()
            ->recentes()
            ->with('categoria')
            ->limit(6)
            ->get();

        return $this->renderResponse(
            'site/aprenda/index',
            [
                'pageTitle' => 'Aprenda sobre Finanças Pessoais | Dicas e Guias Gratuitos | Lukrato',
                'pageDescription' => 'Artigos educativos sobre finanças pessoais: como organizar finanças pessoais, economizar dinheiro, sair das dívidas e controlar gastos. Guias gratuitos do Lukrato.',
                'pageKeywords' => 'finanças pessoais, educação financeira, como economizar dinheiro, controle de gastos, como organizar finanças pessoais 2026, planilha de gastos mensais gratuita, dicas financeiras, orçamento pessoal, planejamento financeiro',
                'canonicalUrl' => rtrim(BASE_URL, '/') . '/blog',
                'categorias' => $categorias,
                'recentes' => $recentes,
                'breadcrumbItems' => [
                    ['label' => 'Início', 'url' => BASE_URL],
                    ['label' => 'Aprenda', 'url' => null],
                ],
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }

    /**
     * Lista artigos de uma categoria - /blog/categoria/{slug}
     */
    public function categoria($slug): Response
    {
        $categoria = BlogCategoria::where('slug', $slug)->first();

        if (!$categoria) {
            return $this->renderResponse('errors/404', [], 'site/partials/header', 'site/partials/footer')
                ->setStatusCode(404);
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 12;

        $result = $this->repo->listByCategoria($categoria->id, $perPage, $page);
        $totalPages = (int) ceil($result['total'] / $perPage);
        $catBaseUrl = rtrim(BASE_URL, '/') . '/blog/categoria/' . $categoria->slug;

        return $this->renderResponse(
            'site/aprenda/categoria',
            [
                'pageTitle' => $categoria->nome . ' - Guia Completo | Aprenda | Lukrato',
                'pageDescription' => "Artigos e guias completos sobre {$categoria->nome}. Aprenda sobre finanças pessoais, dicas práticas e estratégias com o Lukrato.",
                'pageKeywords' => $this->getCategoryKeywords($categoria->slug),
                'canonicalUrl' => $catBaseUrl . ($page > 1 ? '?page=' . $page : ''),
                'categoria' => $categoria,
                'posts' => $result['items'],
                'total' => $result['total'],
                'page' => $page,
                'totalPages' => $totalPages,
                'paginationPrev' => $page > 1 ? $catBaseUrl . ($page > 2 ? '?page=' . ($page - 1) : '') : null,
                'paginationNext' => $page < $totalPages ? $catBaseUrl . '?page=' . ($page + 1) : null,
                'breadcrumbItems' => [
                    ['label' => 'Início', 'url' => BASE_URL],
                    ['label' => 'Aprenda', 'url' => rtrim(BASE_URL, '/') . '/blog'],
                    ['label' => $categoria->nome, 'url' => null],
                ],
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }

    /**
     * Exibe artigo individual - /blog/{slug}
     */
    public function show($slug): Response
    {
        $post = $this->repo->findPublishedBySlug($slug);

        if (!$post) {
            return $this->renderResponse('errors/404', [], 'site/partials/header', 'site/partials/footer')
                ->setStatusCode(404);
        }

        $relacionados = $this->repo->findRelated(
            $post->id,
            $post->blog_categoria_id,
            4
        );

        $allowedLayouts = ['sidebar', 'bottom'];
        $layout = in_array($_GET['layout'] ?? '', $allowedLayouts, true)
            ? $_GET['layout']
            : 'bottom';

        return $this->renderResponse(
            'site/aprenda/show',
            [
                'pageTitle' => $post->effective_meta_title,
                'pageDescription' => $post->effective_meta_description,
                'canonicalUrl' => rtrim(BASE_URL, '/') . '/blog/' . $post->slug,
                'pageImage' => $post->imagem_capa_url ?? (BASE_URL . 'assets/img/og-image.png'),
                'pageType' => 'article',
                'pageImageAlt' => $post->titulo,
                'articlePublishedTime' => $post->published_at?->toIso8601String(),
                'articleModifiedTime' => $post->updated_at?->toIso8601String(),
                'articleSection' => $post->categoria?->nome,
                'pageKeywords' => $this->getArticleKeywords($post),
                'post' => $post,
                'relacionados' => $relacionados,
                'relatedLayout' => $layout,
                'breadcrumbItems' => [
                    ['label' => 'Início', 'url' => BASE_URL],
                    ['label' => 'Aprenda', 'url' => rtrim(BASE_URL, '/') . '/blog'],
                    ['label' => $post->categoria?->nome ?? 'Artigo', 'url' => $post->categoria ? rtrim(BASE_URL, '/') . '/blog/categoria/' . $post->categoria->slug : null],
                    ['label' => $post->titulo, 'url' => null],
                ],
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }

    private function getCategoryKeywords(string $slug): string
    {
        $map = [
            'comecar-com-financas' => 'como organizar finanças pessoais 2026, educação financeira, orçamento pessoal, como começar a controlar gastos, finanças para iniciantes, planejamento financeiro pessoal',
            'economizar-dinheiro' => 'como economizar dinheiro, dicas para economizar, planilha de gastos mensais gratuita, reserva de emergência, gastos invisíveis, reduzir despesas',
            'dividas' => 'como sair das dívidas, como controlar gastos do cartão de crédito, negociação de dívidas, cartão de crédito sem dívida, quitar dívidas',
            'ferramentas' => 'app de controle financeiro gratuito brasileiro, planilha de gastos mensais gratuita, métodos de controle financeiro, regra 50-30-20, ferramentas finanças pessoais',
        ];

        return $map[$slug] ?? 'finanças pessoais, controle financeiro, educação financeira';
    }

    private function getArticleKeywords(BlogPost $post): string
    {
        $keywords = [];

        if ($post->categoria) {
            $keywords[] = mb_strtolower($post->categoria->nome);
        }

        $titulo = mb_strtolower($post->titulo);
        $stopWords = ['como', 'para', 'que', 'com', 'sem', 'seu', 'sua', 'das', 'dos', 'por', 'mais', 'uma', 'não'];
        $words = preg_split('/\s+/', $titulo);

        foreach ($words as $word) {
            $clean = trim($word, '.:,;!?');
            if (mb_strlen($clean) >= 4 && !in_array($clean, $stopWords, true)) {
                $keywords[] = $clean;
            }
        }

        $keywords[] = 'finanças pessoais';
        $keywords[] = 'lukrato';

        return implode(', ', array_unique($keywords));
    }
}
