<?php

/**
 * Hub principal — /blog
 * Mostra categorias e artigos recentes.
 */
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/pages/aprenda.css">

<!-- Hero -->
<section class="aprenda-hero">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-20">
        <div class="text-center max-w-3xl mx-auto" data-aos="fade-up">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-orange-50 dark:bg-white/10 border border-orange-100 dark:border-white/20 rounded-full mb-6">
                <i data-lucide="book-open" class="w-4 h-4 text-primary" aria-hidden="true"></i>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Educação Financeira</span>
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white leading-tight mb-4">
                Aprenda sobre
                <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">Finanças Pessoais</span>
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                Artigos práticos para você organizar seu dinheiro, economizar mais, investir melhor e conquistar sua liberdade financeira.
            </p>
        </div>
    </div>
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute top-20 left-10 w-96 h-96 bg-orange-100 dark:bg-orange-900/20 rounded-full filter blur-3xl opacity-30"></div>
        <div class="absolute bottom-20 right-10 w-80 h-80 bg-gray-100 dark:bg-gray-800/30 rounded-full filter blur-3xl opacity-40"></div>
    </div>
</section>

<!-- Categorias -->
<section class="py-16 bg-white dark:bg-gray-900">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white text-center mb-10" data-aos="fade-up">
            Explore por Categoria
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6" data-aos="fade-up" data-aos-delay="100">
            <?php foreach ($categorias as $cat): ?>
                <a href="<?= rtrim(BASE_URL, '/') ?>/blog/categoria/<?= htmlspecialchars($cat->slug) ?>"
                    class="aprenda-cat-card group">
                    <div class="aprenda-cat-icon">
                        <i data-lucide="<?= htmlspecialchars($cat->icone ?? 'folder') ?>" aria-hidden="true"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary transition-colors">
                        <?= htmlspecialchars($cat->nome) ?>
                    </h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        <?= $cat->posts_count ?? 0 ?> artigo<?= ($cat->posts_count ?? 0) !== 1 ? 's' : '' ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Artigos Recentes -->
<?php if (count($recentes) > 0): ?>
    <section class="py-16 bg-gray-50 dark:bg-gray-800/50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white text-center mb-10" data-aos="fade-up">
                Artigos Recentes
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($recentes as $i => $post): ?>
                    <article class="aprenda-post-card group" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
                        <a href="<?= rtrim(BASE_URL, '/') ?>/blog/<?= htmlspecialchars($post->slug) ?>" class="block">
                            <?php if ($post->imagem_capa): ?>
                                <div class="aprenda-post-img">
                                    <img src="<?= rtrim(BASE_URL, '/') ?>/<?= htmlspecialchars($post->imagem_capa) ?>"
                                        alt="<?= htmlspecialchars($post->titulo) ?>"
                                        loading="lazy" width="400" height="225">
                                </div>
                            <?php else: ?>
                                <div class="aprenda-post-img aprenda-post-img-empty">
                                    <i data-lucide="file-text" class="w-12 h-12 text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                                </div>
                            <?php endif; ?>

                            <div class="aprenda-post-body">
                                <?php if ($post->categoria): ?>
                                    <span class="aprenda-post-cat">
                                        <?= htmlspecialchars($post->categoria->nome) ?>
                                    </span>
                                <?php endif; ?>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-primary transition-colors line-clamp-2">
                                    <?= htmlspecialchars($post->titulo) ?>
                                </h3>
                                <?php if ($post->resumo): ?>
                                    <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2 mt-2">
                                        <?= htmlspecialchars($post->resumo) ?>
                                    </p>
                                <?php endif; ?>
                                <div class="aprenda-post-meta">
                                    <?php if ($post->tempo_leitura): ?>
                                        <span><i data-lucide="clock" class="w-3.5 h-3.5" aria-hidden="true"></i> <?= $post->tempo_leitura ?> min de leitura</span>
                                    <?php endif; ?>
                                    <?php if ($post->published_at): ?>
                                        <span><i data-lucide="calendar" class="w-3.5 h-3.5" aria-hidden="true"></i> <?= $post->published_at->format('d/m/Y') ?></span>
                                    <?php endif; ?>
                                </div>
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
            Pronto para controlar suas finanças?
        </h2>
        <p class="text-lg text-white/80 mb-8 max-w-2xl mx-auto">
            Coloque em prática o que você aprendeu. Comece a organizar seu dinheiro com o Lukrato — é grátis!
        </p>
        <a href="<?= BASE_URL ?>login?tab=register"
            class="inline-flex items-center gap-2 px-8 py-4 bg-white text-primary font-bold rounded-full shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
            <span>Começar grátis</span>
            <i data-lucide="arrow-right" class="w-5 h-5" aria-hidden="true"></i>
        </a>
    </div>
</section>