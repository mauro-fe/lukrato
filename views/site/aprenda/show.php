<?php
/**
 * Artigo individual — /aprenda/{slug}
 * Mostra o artigo completo com SEO, schema.org, e relacionados.
 *
 * Variáveis: $post, $relacionados, $breadcrumbItems
 */
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/pages/aprenda.css">

<!-- Schema.org BlogPosting + BreadcrumbList -->
<script type="application/ld+json">
<?php
$wordCount = str_word_count(strip_tags($post->conteudo));
$readingMinutes = $post->tempo_leitura ?? max(1, (int) ceil($wordCount / 200));
echo json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(function ($item, $i) {
                $entry = [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $item['label'],
                ];
                if (!empty($item['url'])) {
                    $entry['item'] = $item['url'];
                }
                return $entry;
            }, $breadcrumbItems, array_keys($breadcrumbItems)),
        ],
        array_filter([
            '@type' => 'BlogPosting',
            'headline' => $post->titulo,
            'description' => $post->resumo ?: mb_substr(strip_tags($post->conteudo), 0, 160, 'UTF-8'),
            'image' => $post->imagem_capa ? rtrim(BASE_URL, '/') . '/' . $post->imagem_capa : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'wordCount' => $wordCount,
            'timeRequired' => 'PT' . $readingMinutes . 'M',
            'author' => [
                '@type' => 'Organization',
                'name' => 'Lukrato',
                'url' => rtrim(BASE_URL, '/'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Lukrato',
                'url' => rtrim(BASE_URL, '/'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => rtrim(BASE_URL, '/') . '/assets/img/logo.png',
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => rtrim(BASE_URL, '/') . '/aprenda/' . $post->slug,
            ],
            'articleSection' => $post->categoria?->nome,
            'inLanguage' => 'pt-BR',
        ]),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>
</script>

<!-- Breadcrumbs -->
<nav aria-label="Breadcrumb" class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <ol class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 flex-wrap">
            <?php foreach ($breadcrumbItems as $i => $item): ?>
                <?php if ($i > 0): ?>
                    <li aria-hidden="true"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i></li>
                <?php endif; ?>
                <?php if (!empty($item['url'])): ?>
                    <li><a href="<?= htmlspecialchars($item['url']) ?>" class="hover:text-primary transition-colors"><?= htmlspecialchars($item['label']) ?></a></li>
                <?php else: ?>
                    <li class="text-gray-900 dark:text-white font-medium truncate max-w-[200px] sm:max-w-none"><?= htmlspecialchars($item['label']) ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </div>
</nav>

<!-- Artigo -->
<article class="py-12 bg-white dark:bg-gray-900">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <header class="mb-8" data-aos="fade-up">
                <?php if ($post->categoria): ?>
                    <a href="<?= rtrim(BASE_URL, '/') ?>/aprenda/categoria/<?= htmlspecialchars($post->categoria->slug) ?>"
                        class="aprenda-post-cat inline-block mb-4">
                        <?= htmlspecialchars($post->categoria->nome) ?>
                    </a>
                <?php endif; ?>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 dark:text-white leading-tight mb-4">
                    <?= htmlspecialchars($post->titulo) ?>
                </h1>

                <?php if ($post->resumo): ?>
                    <p class="text-xl text-gray-600 dark:text-gray-300 leading-relaxed mb-6">
                        <?= htmlspecialchars($post->resumo) ?>
                    </p>
                <?php endif; ?>

                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 pb-6 border-b border-gray-200 dark:border-gray-700">
                    <?php if ($post->published_at): ?>
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-4 h-4" aria-hidden="true"></i>
                            <time datetime="<?= $post->published_at->toIso8601String() ?>">
                                <?= $post->published_at->format('d \d\e M, Y') ?>
                            </time>
                        </span>
                    <?php endif; ?>
                    <?php if ($post->tempo_leitura): ?>
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="clock" class="w-4 h-4" aria-hidden="true"></i>
                            <?= $post->tempo_leitura ?> min de leitura
                        </span>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Imagem de capa -->
            <?php if ($post->imagem_capa): ?>
                <figure class="mb-10" data-aos="fade-up">
                    <img src="<?= rtrim(BASE_URL, '/') ?>/<?= htmlspecialchars($post->imagem_capa) ?>"
                        alt="<?= htmlspecialchars($post->titulo) ?>"
                        class="w-full rounded-2xl shadow-lg"
                        loading="eager" width="800" height="450">
                </figure>
            <?php endif; ?>

            <!-- Conteúdo -->
            <div class="aprenda-prose" data-aos="fade-up">
                <?= $post->conteudo ?>
            </div>

            <!-- Compartilhar -->
            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Compartilhe este artigo:</p>
                <div class="flex items-center gap-3">
                    <?php
                    $shareUrl = urlencode(rtrim(BASE_URL, '/') . '/aprenda/' . $post->slug);
                    $shareTitle = urlencode($post->titulo);
                    ?>
                    <a href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>"
                        target="_blank" rel="noopener noreferrer"
                        class="aprenda-share-btn" aria-label="Compartilhar no WhatsApp" title="WhatsApp">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=<?= $shareTitle ?>&url=<?= $shareUrl ?>"
                        target="_blank" rel="noopener noreferrer"
                        class="aprenda-share-btn" aria-label="Compartilhar no X/Twitter" title="X/Twitter">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $shareUrl ?>"
                        target="_blank" rel="noopener noreferrer"
                        class="aprenda-share-btn" aria-label="Compartilhar no LinkedIn" title="LinkedIn">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <button onclick="navigator.clipboard.writeText(window.location.href).then(() => alert('Link copiado!'))"
                        class="aprenda-share-btn" aria-label="Copiar link" title="Copiar link">
                        <i data-lucide="link" class="w-5 h-5" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</article>

<!-- Relacionados -->
<?php if (count($relacionados) > 0): ?>
<section class="py-16 bg-gray-50 dark:bg-gray-800/50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-10" data-aos="fade-up">
            Artigos Relacionados
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-5xl mx-auto">
            <?php foreach ($relacionados as $i => $rel): ?>
                <article class="aprenda-post-card group" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
                    <a href="<?= rtrim(BASE_URL, '/') ?>/aprenda/<?= htmlspecialchars($rel->slug) ?>" class="block">
                        <?php if ($rel->imagem_capa): ?>
                            <div class="aprenda-post-img">
                                <img src="<?= rtrim(BASE_URL, '/') ?>/<?= htmlspecialchars($rel->imagem_capa) ?>"
                                    alt="<?= htmlspecialchars($rel->titulo) ?>"
                                    loading="lazy" width="400" height="225">
                            </div>
                        <?php else: ?>
                            <div class="aprenda-post-img aprenda-post-img-empty">
                                <i data-lucide="file-text" class="w-12 h-12 text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                            </div>
                        <?php endif; ?>
                        <div class="aprenda-post-body">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-primary transition-colors line-clamp-2">
                                <?= htmlspecialchars($rel->titulo) ?>
                            </h3>
                            <?php if ($rel->resumo): ?>
                                <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2 mt-2">
                                    <?= htmlspecialchars($rel->resumo) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="py-20 bg-gradient-to-r from-primary to-orange-600">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center" data-aos="fade-up">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">
            Coloque em prática!
        </h2>
        <p class="text-lg text-white/80 mb-8 max-w-2xl mx-auto">
            Organize suas finanças com o Lukrato e transforme o que você aprendeu em resultados reais.
        </p>
        <a href="<?= BASE_URL ?>login?tab=register"
            class="inline-flex items-center gap-2 px-8 py-4 bg-white text-primary font-bold rounded-full shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
            <span>Começar grátis</span>
            <i data-lucide="arrow-right" class="w-5 h-5" aria-hidden="true"></i>
        </a>
    </div>
</section>
