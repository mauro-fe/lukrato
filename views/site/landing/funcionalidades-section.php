<?php
include __DIR__ . '/gallery-slides.php';
$firstLandingGallerySlide = $landingGallerySlides[0] ?? null;
?>

<!-- Seção de Funcionalidades -->
<section id="funcionalidades" class="relative py-16 md:py-24 overflow-hidden bg-gradient-to-b from-gray-50 to-white"
    aria-labelledby="funcionalidades-titulo">

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

        <!-- Header -->
        <header class="lk-header-card max-w-3xl mx-auto text-center mb-14" data-aos="fade-up">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-orange-50 rounded-full mb-4">
                <i data-lucide="layout-dashboard" class="w-5 h-5 text-primary"></i>
                <span class="text-sm font-semibold text-primary">Funcionalidades</span>
            </div>
            <h2 id="funcionalidades-titulo"
                class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight mb-4">
                Tudo num só lugar.
                <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                    Sem complicação.
                </span>
            </h2>
            <p class="text-lg sm:text-xl text-gray-600 leading-relaxed">
                Ferramentas que fazem sentido para quem quer organizar o dinheiro de verdade.
            </p>
        </header>

        <!-- Grid de funcionalidades -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 max-w-6xl mx-auto mb-12">

            <!-- Card 1 - Dashboard -->
            <article
                class="group bg-white rounded-2xl p-7 shadow-sm border border-gray-100 hover:shadow-lg hover:border-orange-100 transition-all duration-300"
                data-aos="fade-up" data-aos-delay="0">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-orange-600 flex items-center justify-center text-white mb-5"
                    aria-hidden="true">
                    <i data-lucide="line-chart" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-2">Visão completa do seu dinheiro</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Saldo, receitas, despesas e gráficos. Tudo o que importa numa única tela.
                </p>
            </article>

            <!-- Card 2 - Lançamentos -->
            <article
                class="group bg-white rounded-2xl p-7 shadow-sm border border-gray-100 hover:shadow-lg hover:border-orange-100 transition-all duration-300"
                data-aos="fade-up" data-aos-delay="100">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-secondary to-gray-700 flex items-center justify-center text-white mb-5"
                    aria-hidden="true">
                    <i data-lucide="receipt" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-2">Registre tudo: receitas, despesas, parcelas</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Recorrências, parcelamentos e marcação de pago. Controle cada movimentação.
                </p>
            </article>

            <!-- Card 3 - Cartões de crédito -->
            <article
                class="group bg-white rounded-2xl p-7 shadow-sm border border-gray-100 hover:shadow-lg hover:border-orange-100 transition-all duration-300"
                data-aos="fade-up" data-aos-delay="200">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white mb-5"
                    aria-hidden="true">
                    <i data-lucide="credit-card" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-2">Controle total dos seus cartões</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Faturas, parcelas, recorrências e limites. Nunca mais perca o controle.
                </p>
            </article>

            <!-- Card 4 - Relatórios -->
            <article
                class="group bg-white rounded-2xl p-7 shadow-sm border border-gray-100 hover:shadow-lg hover:border-orange-100 transition-all duration-300"
                data-aos="fade-up" data-aos-delay="300">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white mb-5"
                    aria-hidden="true">
                    <i data-lucide="pie-chart" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-2">Gráficos que mostram a verdade</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Comparativos e insights visuais sobre seus hábitos. Descubra para onde vai seu dinheiro.
                </p>
            </article>

            <!-- Card 5 - Metas financeiras -->
            <article
                class="group bg-white rounded-2xl p-7 shadow-sm border border-gray-100 hover:shadow-lg hover:border-orange-100 transition-all duration-300"
                data-aos="fade-up" data-aos-delay="400">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white mb-5"
                    aria-hidden="true">
                    <i data-lucide="target" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-2">Defina metas e veja seu progresso</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Viagem, reserva, compra dos sonhos. Acompanhe cada aporte em tempo real.
                </p>
            </article>

            <!-- Card 6 - Orçamentos -->
            <article
                class="group bg-white rounded-2xl p-7 shadow-sm border border-gray-100 hover:shadow-lg hover:border-orange-100 transition-all duration-300"
                data-aos="fade-up" data-aos-delay="500">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-warning to-yellow-600 flex items-center justify-center text-white mb-5"
                    aria-hidden="true">
                    <i data-lucide="wallet" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-2">Saiba quando parar de gastar</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Limites por categoria com alertas automáticos. Seu orçamento sempre no controle.
                </p>
            </article>

        </div>

        <?php if ($firstLandingGallerySlide): ?>
            <!-- Showcase de prints -->
            <div id="prints-do-sistema" class="relative max-w-6xl mx-auto mb-14" data-aos="fade-up" data-aos-delay="150"
                data-gallery-showcase>
                <div
                    class="absolute inset-x-16 top-1/2 h-32 -translate-y-1/2 rounded-full bg-orange-200/50 blur-3xl pointer-events-none">
                </div>

                <div class="relative max-w-3xl mx-auto text-center mb-8 lg:mb-10">
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-4 py-2 text-sm font-semibold text-primary">
                        <i data-lucide="images" class="w-4 h-4" aria-hidden="true"></i>
                        Veja o sistema por dentro
                    </div>

                    <h3 class="mt-5 text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 leading-tight">
                        Telas reais para mostrar onde você ganha clareza no dia a dia.
                    </h3>

                    <p class="mt-4 text-base sm:text-lg text-gray-600 leading-relaxed">
                        Em vez de esconder o produto, a landing mostra as áreas que mais ajudam você a controlar saldo,
                        gastos e decisões.
                    </p>
                </div>

                <div class="relative max-w-5xl mx-auto">
                    <div
                        class="relative overflow-hidden rounded-[32px] border border-slate-200 bg-slate-950 p-4 sm:p-6 lg:p-8 shadow-[0_30px_100px_-40px_rgba(15,23,42,0.45)]">
                        <div
                            class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(249,115,22,0.24),_transparent_35%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.16),_transparent_35%)]">
                        </div>

                        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between pb-5">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-orange-200"
                                    data-gallery-current-eyebrow><?= htmlspecialchars($firstLandingGallerySlide['eyebrow']) ?></p>
                                <h3 class="mt-2 text-2xl sm:text-3xl font-semibold text-white"
                                    data-gallery-current-title><?= htmlspecialchars($firstLandingGallerySlide['title']) ?></h3>
                            </div>

                            <button type="button" data-open="galleryModal"
                                class="inline-flex items-center gap-2 self-start rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition-all duration-300 hover:bg-white/15"
                                title="Abrir print atual em tela cheia" aria-label="Abrir print atual em tela cheia">
                                <i data-lucide="expand" class="w-4 h-4" aria-hidden="true"></i>
                                Tela cheia
                            </button>
                        </div>

                        <button type="button" data-open="galleryModal"
                            class="relative block w-full text-left group" title="Abrir print atual em tela cheia"
                            aria-label="Abrir print atual em tela cheia">
                            <div
                                class="rounded-[24px] border border-white/10 bg-white/95 p-2 lg:p-3 shadow-2xl shadow-black/30 transition-transform duration-300 group-hover:scale-[1.01]">
                                <img src="<?= htmlspecialchars($firstLandingGallerySlide['src']) ?>"
                                    alt="<?= htmlspecialchars($firstLandingGallerySlide['title']) ?> do Lukrato"
                                    data-gallery-current-image
                                    data-theme-image-light="<?= htmlspecialchars($firstLandingGallerySlide['src']) ?>"
                                    <?php if (!empty($firstLandingGallerySlide['darkSrc'])): ?>data-theme-image-dark="<?= htmlspecialchars($firstLandingGallerySlide['darkSrc']) ?>" <?php endif; ?>
                                    class="w-full h-auto rounded-[18px]" loading="lazy" />
                            </div>
                        </button>

                        <div
                            class="relative mt-4 flex flex-col gap-3 border-t border-white/10 pt-4 text-sm text-slate-300 sm:flex-row sm:items-center sm:justify-between">
                            <p class="max-w-2xl leading-relaxed" data-gallery-current-desc>
                                <?= htmlspecialchars($firstLandingGallerySlide['desc']) ?>
                            </p>
                            <span class="shrink-0 text-slate-400" data-gallery-current-count>
                                1/<?= $landingGalleryCount ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 <?= $landingGalleryGridClass ?>">
                    <?php foreach ($landingGallerySlides as $index => $slide): ?>
                        <button type="button"
                            class="lk-gallery-option group rounded-2xl border p-3 text-left transition-all duration-300 <?= $index === 0 ? 'border-orange-200 bg-orange-50/70 shadow-sm' : 'border-gray-200 bg-white hover:border-orange-100 hover:bg-gray-50' ?>"
                            data-gallery-select data-index="<?= $index ?>"
                            data-src="<?= htmlspecialchars($slide['src']) ?>"
                            <?php if (!empty($slide['darkSrc'])): ?>data-dark-src="<?= htmlspecialchars($slide['darkSrc']) ?>" <?php endif; ?>
                            data-title="<?= htmlspecialchars($slide['title']) ?>"
                            data-eyebrow="<?= htmlspecialchars($slide['eyebrow']) ?>"
                            data-desc="<?= htmlspecialchars($slide['desc']) ?>"
                            aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>">
                            <div
                                class="lk-gallery-thumb overflow-hidden rounded-xl border <?= $index === 0 ? 'border-orange-200' : 'border-gray-200' ?>">
                                <img src="<?= htmlspecialchars($slide['src']) ?>"
                                    alt="<?= htmlspecialchars($slide['title']) ?> do Lukrato"
                                    data-theme-image-light="<?= htmlspecialchars($slide['src']) ?>"
                                    <?php if (!empty($slide['darkSrc'])): ?>data-theme-image-dark="<?= htmlspecialchars($slide['darkSrc']) ?>" <?php endif; ?>
                                    class="h-24 w-full bg-white object-contain p-2" loading="lazy" />
                            </div>

                            <div class="mt-3 min-w-0">
                                <p class="lk-gallery-option-eyebrow text-[11px] font-semibold uppercase tracking-[0.18em] <?= $index === 0 ? 'text-primary' : 'text-gray-400' ?>">
                                    <?= htmlspecialchars($slide['eyebrow']) ?>
                                </p>
                                <h4 class="mt-1 text-sm font-semibold text-gray-900">
                                    <?= htmlspecialchars($slide['title']) ?>
                                </h4>
                                <p class="mt-1 text-xs leading-relaxed text-gray-600">
                                    <?= htmlspecialchars($slide['desc']) ?>
                                </p>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="text-center" data-aos="fade-up">
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <div class="flex flex-col items-center">
                    <a href="<?= BASE_URL ?>login"
                        class="group inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-lg shadow-orange-500/20 hover:shadow-xl hover:shadow-orange-500/30 hover:scale-[1.03] active:scale-[0.98] transition-all duration-300"
                        title="Experimentar o Lukrato agora" aria-label="Experimentar agora">
                        Experimentar agora
                        <i data-lucide="arrow-right" class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform"
                            aria-hidden="true"></i>
                    </a>
                    <span class="text-xs text-gray-400 mt-2 font-medium">Leva menos de 1 minuto</span>
                </div>

                <button type="button" id="openGalleryBtn" data-open="galleryModal"
                    class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:border-primary/40 hover:text-primary hover:shadow-md transition-all duration-300"
                    title="Abrir galeria em tela cheia" aria-label="Abrir galeria de imagens do sistema em tela cheia">
                    <i data-lucide="images" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Abrir galeria em tela cheia
                </button>
            </div>
        </div>

    </div>
</section>