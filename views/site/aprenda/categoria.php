<?php

/**
 * Listagem por categoria — /blog/categoria/{slug}
 * Mostra artigos de uma categoria com paginação.
 *
 * Variáveis: $categoria, $posts, $page, $totalPages, $breadcrumbItems
 */
?>

<?= function_exists('vite_css') ? vite_css('site-aprenda') : '' ?>

<!-- Breadcrumbs -->
<nav aria-label="Breadcrumb" class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700" style="padding-top: 5rem;">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <ol class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <?php foreach ($breadcrumbItems as $i => $item): ?>
                <?php if ($i > 0): ?>
                    <li aria-hidden="true"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i></li>
                <?php endif; ?>
                <?php if (!empty($item['url'])): ?>
                    <li><a href="<?= htmlspecialchars($item['url']) ?>"
                            class="hover:text-primary transition-colors"><?= htmlspecialchars($item['label']) ?></a></li>
                <?php else: ?>
                    <li class="text-gray-900 dark:text-white font-medium"><?= htmlspecialchars($item['label']) ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </div>
</nav>

<!-- Header da Categoria -->
<section class="py-12 bg-white dark:bg-gray-900">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto" data-aos="fade-up">
            <div
                class="inline-flex items-center justify-center w-16 h-16 bg-orange-50 dark:bg-orange-900/20 rounded-2xl mb-4">
                <i data-lucide="<?= htmlspecialchars($categoria->icone ?? 'folder') ?>" class="w-8 h-8 text-primary"
                    aria-hidden="true"></i>
            </div>
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-3">
                <?= htmlspecialchars($categoria->nome) ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                <?= count($posts) > 0
                    ? 'Explore nossos artigos sobre ' . mb_strtolower($categoria->nome, 'UTF-8') . '.'
                    : 'Nenhum artigo publicado nesta categoria ainda.' ?>
            </p>
        </div>
    </div>
</section>

<!-- Artigos -->
<?php if (count($posts) > 0): ?>
    <section class="py-12 bg-gray-50 dark:bg-gray-800/50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($posts as $i => $post): ?>
                    <article class="aprenda-post-card group" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
                        <a href="<?= rtrim(BASE_URL, '/') ?>/blog/<?= htmlspecialchars($post->slug) ?>" class="block">
                            <?php if ($post->imagem_capa): ?>
                                <div class="aprenda-post-img">
                                    <img src="<?= rtrim(BASE_URL, '/') ?>/<?= htmlspecialchars($post->imagem_capa) ?>"
                                        alt="<?= htmlspecialchars($post->titulo) ?>" loading="lazy" width="400" height="225">
                                </div>
                            <?php else: ?>
                                <div class="aprenda-post-img aprenda-post-img-empty">
                                    <i data-lucide="file-text" class="w-12 h-12 text-gray-300 dark:text-gray-600"
                                        aria-hidden="true"></i>
                                </div>
                            <?php endif; ?>

                            <div class="aprenda-post-body">
                                <h2
                                    class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-primary transition-colors line-clamp-2">
                                    <?= htmlspecialchars($post->titulo) ?>
                                </h2>
                                <?php if ($post->resumo): ?>
                                    <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2 mt-2">
                                        <?= htmlspecialchars($post->resumo) ?>
                                    </p>
                                <?php endif; ?>
                                <div class="aprenda-post-meta">
                                    <?php if ($post->tempo_leitura): ?>
                                        <span><i data-lucide="clock" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                            <?= $post->tempo_leitura ?> min</span>
                                    <?php endif; ?>
                                    <?php if ($post->published_at): ?>
                                        <span><i data-lucide="calendar" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                            <?= $post->published_at->format('d/m/Y') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
                <nav class="aprenda-pagination" aria-label="Paginação">
                    <?php if ($page > 1): ?>
                        <a href="<?= rtrim(BASE_URL, '/') ?>/blog/categoria/<?= $categoria->slug ?>?page=<?= $page - 1 ?>"
                            class="aprenda-page-btn" aria-label="Página anterior">
                            <i data-lucide="chevron-left" class="w-4 h-4" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    if ($start > 1) {
                        echo '<a href="' . rtrim(BASE_URL, '/') . '/blog/categoria/' . $categoria->slug . '?page=1" class="aprenda-page-btn">1</a>';
                        if ($start > 2) echo '<span class="aprenda-page-dots">…</span>';
                    }
                    for ($p = $start; $p <= $end; $p++) {
                        $active = $p === $page ? ' aprenda-page-active' : '';
                        echo '<a href="' . rtrim(BASE_URL, '/') . '/blog/categoria/' . $categoria->slug . '?page=' . $p . '" class="aprenda-page-btn' . $active . '">' . $p . '</a>';
                    }
                    if ($end < $totalPages) {
                        if ($end < $totalPages - 1) echo '<span class="aprenda-page-dots">…</span>';
                        echo '<a href="' . rtrim(BASE_URL, '/') . '/blog/categoria/' . $categoria->slug . '?page=' . $totalPages . '" class="aprenda-page-btn">' . $totalPages . '</a>';
                    }
                    ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= rtrim(BASE_URL, '/') ?>/blog/categoria/<?= $categoria->slug ?>?page=<?= $page + 1 ?>"
                            class="aprenda-page-btn" aria-label="Próxima página">
                            <i data-lucide="chevron-right" class="w-4 h-4" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </section>
<?php else: ?>
    <section class="py-20 bg-gray-50 dark:bg-gray-800/50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <i data-lucide="book-x" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" aria-hidden="true"></i>
            <p class="text-gray-500 dark:text-gray-400 mb-6">Em breve publicaremos artigos nesta categoria.</p>
            <a href="<?= rtrim(BASE_URL, '/') ?>/blog"
                class="inline-flex items-center gap-2 text-primary font-medium hover:underline">
                <i data-lucide="arrow-left" class="w-4 h-4" aria-hidden="true"></i> Voltar para Aprenda
            </a>
        </div>
    </section>
<?php endif; ?>
