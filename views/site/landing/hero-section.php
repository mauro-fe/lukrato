<?php
include __DIR__ . '/gallery-slides.php';
$heroSlides = $landingGallerySlides;
$firstHeroSlide = $heroSlides[0] ?? null;
$heroSlidesCount = count($heroSlides);
$heroHeadlinePhrases = [
    'sem perceber',
    'aos poucos',
    'todo mês',
    'no automático',
];
$heroHeadlinePhrasesJson = htmlspecialchars(
    json_encode($heroHeadlinePhrases, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    ENT_QUOTES,
    'UTF-8'
);
?>

<!-- Hero Section — Impacto emocional imediato -->
<section
    class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-b from-white via-orange-50/40 to-white"
    aria-label="Seção principal - Controle financeiro pessoal" itemscope itemtype="https://schema.org/WebPageElement">
    <!-- Background Decorations (sutil) -->
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute top-20 left-10 w-96 h-96 bg-orange-100 rounded-full filter blur-3xl opacity-30"></div>
        <div class="absolute bottom-20 right-10 w-80 h-80 bg-gray-100 rounded-full filter blur-3xl opacity-40"></div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-20">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            <!-- Conteúdo Principal -->
            <article class="text-center lg:text-left space-y-6" data-aos="fade-up">
                <!-- Badge — Prova social imediata -->
                <div
                    class="inline-flex items-center gap-2 px-4 py-2 bg-orange-50 border border-orange-100 rounded-full">
                    <i data-lucide="users" class="w-4 h-4 text-primary" aria-hidden="true"></i>
                    <span class="text-sm font-medium text-gray-700">+1.000 pessoas já organizando suas finanças</span>
                </div>

                <!-- Título Principal — Ataca dor real -->
                <!--
                    A/B Test Variações:
                    B: "Saiba exatamente para onde cada real vai"
                    C: "Seu dinheiro está sumindo. Descubra para onde."
                -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
                    Pare de perder dinheiro
                    <span class="mt-1 block bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                        <span data-hero-typewriter data-phrases="<?= $heroHeadlinePhrasesJson ?>"
                            data-typewriter-force="true" aria-label="sem perceber"
                            class="inline-flex min-h-[1.15em] min-w-[12.5ch] max-w-full items-center justify-center whitespace-nowrap text-center sm:min-w-[15ch] sm:justify-start sm:text-left">
                            <span data-hero-typewriter-text class="whitespace-nowrap">sem perceber</span>
                            <span aria-hidden="true"
                                class="ml-1 inline-block h-[0.9em] w-[2px] rounded-full bg-current/80 animate-pulse"></span>
                        </span>
                    </span>
                </h1>

                <!-- Subtítulo — Benefício + IA + simplicidade -->
                <p class="text-lg sm:text-xl text-gray-900 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    O Lukrato mostra em segundos se você está gastando mais do que ganha — e te ajuda a virar o jogo com
                    IA, sem planilha e sem complicação.
                </p>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start pt-2">
                    <div class="flex flex-col items-center sm:items-start">
                        <a href="<?= BASE_URL ?>login"
                            class="group inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-lg shadow-orange-500/20 hover:shadow-xl hover:shadow-orange-500/30 hover:scale-[1.03] active:scale-[0.98] transition-all duration-300"
                            title="Criar conta gratuita no Lukrato" aria-label="Quero organizar meu dinheiro">
                            Quero organizar meu dinheiro
                            <i data-lucide="arrow-right"
                                class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform"
                                aria-hidden="true"></i>
                        </a>
                        <span class="text-xs text-gray-400 mt-2 font-medium">Grátis · Sem cartão · Leva 1 minuto</span>
                    </div>

                    <a href="#como-funciona"
                        class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:border-primary/40 hover:text-primary shadow-sm hover:shadow-md transition-all duration-300"
                        title="Ver como funciona o Lukrato" aria-label="Entenda como funciona">
                        <i data-lucide="play-circle" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Ver como funciona em 2 minutos
                    </a>
                </div>

                <!-- Trust badges -->
                <div class="flex flex-wrap items-center gap-4 pt-4 justify-center lg:justify-start">
                    <div class="flex items-center gap-2 text-gray-500 text-sm">
                        <i data-lucide="circle-check" class="w-4 h-4 text-green-500" aria-hidden="true"></i>
                        <span>Sem cartão de crédito</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-500 text-sm">
                        <i data-lucide="circle-check" class="w-4 h-4 text-green-500" aria-hidden="true"></i>
                        <span>Cadastro em 1 minuto</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-500 text-sm">
                        <i data-lucide="circle-check" class="w-4 h-4 text-green-500" aria-hidden="true"></i>
                        <span>Dados protegidos (LGPD)</span>
                    </div>
                </div>
            </article>

            <!-- Imagem / Mockup -->
            <?php if ($firstHeroSlide): ?>
            <figure class="relative" data-aos="fade-up" data-aos-delay="150" data-hero-carousel data-autoplay-ms="4600"
                data-autoplay-force="true">
                <div
                    class="relative rounded-[28px] border border-gray-100 bg-white/96 p-3 shadow-xl lg:p-4 dark:border-white/10 dark:bg-[#10263b]">
                    <div class="mb-3 flex items-start justify-between gap-2 sm:mb-4 sm:gap-3">
                        <div
                            class="inline-flex max-w-[11rem] flex-none flex-col self-start rounded-[18px] border border-orange-100 bg-orange-50 px-2.5 py-2 shadow-sm sm:max-w-[12.5rem] sm:rounded-2xl sm:px-4 sm:py-3 lg:max-w-[14rem] dark:border-white/10 dark:bg-white/5 dark:shadow-none">
                            <p class="text-[9px] font-semibold uppercase tracking-[0.18em] text-primary/80 sm:text-[10px] sm:tracking-[0.22em] dark:text-orange-200"
                                data-hero-current-eyebrow>
                                <?= htmlspecialchars($firstHeroSlide['eyebrow']) ?>
                            </p>
                            <p class="mt-0.5 text-xs font-semibold leading-tight text-gray-900 sm:mt-1 sm:text-base dark:text-white"
                                data-hero-current-title>
                                <?= htmlspecialchars($firstHeroSlide['title']) ?>
                            </p>
                        </div>

                        <button type="button" data-open="galleryModal" data-hero-open
                            class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-full border border-gray-200 bg-white px-2.5 py-2 text-[10px] font-semibold leading-none text-gray-700 shadow-sm transition-all duration-300 hover:border-primary/30 hover:text-primary sm:gap-2 sm:px-4 sm:text-sm dark:border-white/10 dark:bg-slate-950/72 dark:text-white dark:shadow-lg dark:hover:bg-slate-950/82"
                            title="Abrir print atual em tela cheia" aria-label="Abrir print atual em tela cheia">
                            <i data-lucide="expand" class="h-4 w-4" aria-hidden="true"></i>
                            Tela cheia
                        </button>
                    </div>

                    <div
                        class="relative aspect-[16/10] overflow-hidden rounded-[24px] border border-gray-200/80 bg-slate-950 dark:border-white/10">
                        <div class="flex h-full w-full will-change-transform" data-hero-track
                            style="transform: translate3d(0, 0, 0); transition: transform 0.72s cubic-bezier(0.22, 1, 0.36, 1);">
                            <?php foreach ($heroSlides as $index => $slide): ?>
                            <div class="min-w-full h-full" data-hero-slide
                                data-title="<?= htmlspecialchars($slide['title']) ?>"
                                data-eyebrow="<?= htmlspecialchars($slide['eyebrow']) ?>"
                                aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>">
                                <img src="<?= htmlspecialchars($slide['src']) ?>"
                                    data-theme-image-light="<?= htmlspecialchars($slide['src']) ?>"
                                    data-theme-image-dark="<?= htmlspecialchars($slide['darkSrc']) ?>"
                                    alt="<?= htmlspecialchars($slide['title']) ?> do Lukrato"
                                    class="block h-full w-full rounded-xl bg-white object-contain"
                                    loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                                    <?= $index === 0 ? 'fetchpriority="high"' : '' ?> width="800" height="500" />
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($heroSlidesCount > 1): ?>
                    <div class="mt-3 flex items-center justify-end sm:mt-4">
                        <div
                            class="inline-flex max-w-full items-center gap-2 rounded-full border border-gray-200 bg-white px-2.5 py-2 text-gray-700 shadow-sm sm:gap-3 sm:px-3 dark:border-white/10 dark:bg-slate-950/72 dark:text-white dark:shadow-lg">
                            <button type="button" data-hero-prev data-hero-nav
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-gray-50 text-gray-600 transition-all duration-300 hover:border-primary/30 hover:text-primary sm:h-9 sm:w-9 dark:border-white/10 dark:bg-white/5 dark:text-white/80 dark:hover:bg-white/10"
                                aria-label="Mostrar imagem anterior do hero">
                                <i data-lucide="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
                            </button>

                            <div class="flex items-center gap-1.5 sm:gap-2" aria-label="Telas do sistema no hero">
                                <?php foreach ($heroSlides as $index => $slide): ?>
                                <?php $heroDotClasses = $index === 0
                                                ? 'w-6 bg-primary sm:w-8'
                                                : 'w-2 bg-gray-300 hover:bg-gray-400 sm:w-2.5 dark:bg-white/35 dark:hover:bg-white/55'; ?>
                                <button type="button" data-hero-dot data-index="<?= $index ?>"
                                    class="min-h-0 min-w-0 h-2 rounded-full transition-all duration-300 sm:h-2.5 <?= $heroDotClasses ?>"
                                    aria-label="Mostrar <?= htmlspecialchars($slide['title']) ?> no hero"
                                    aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"></button>
                                <?php endforeach; ?>
                            </div>
                            <span class="text-[11px] font-medium text-gray-500 sm:text-xs dark:text-white/70"
                                data-hero-current-count>
                                1/<?= $heroSlidesCount ?>
                            </span>

                            <button type="button" data-hero-next data-hero-nav
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-gray-50 text-gray-600 transition-all duration-300 hover:border-primary/30 hover:text-primary sm:h-9 sm:w-9 dark:border-white/10 dark:bg-white/5 dark:text-white/80 dark:hover:bg-white/10"
                                aria-label="Mostrar próxima imagem do hero">
                                <i data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <figcaption class="sr-only">Carrossel de telas do aplicativo Lukrato para controle financeiro pessoal.
                </figcaption>
            </figure>
            <?php endif; ?>
        </div>
    </div>

    <!-- Seta de scroll suave -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 hidden lg:block" data-aos="fade-up" data-aos-delay="800">
        <a href="#como-funciona"
            class="flex flex-col items-center gap-2 text-gray-400 hover:text-primary transition-colors animate-bounce">
            <span class="text-sm font-medium">Descubra mais</span>
            <i data-lucide="chevron-down" class="w-5 h-5"></i>
        </a>
    </div>
</section>