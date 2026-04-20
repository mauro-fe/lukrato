<?php include __DIR__ . '/gallery-slides.php'; ?>

<!-- MODAL / GALERIA -->
<div id="galleryModal" aria-hidden="true" class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
    style="display: none;">

    <!-- Backdrop -->
    <button type="button" class="absolute inset-0 bg-black/80 backdrop-blur-sm" data-close="galleryModal"
        aria-label="Fechar galeria"></button>

    <!-- Modal Content -->
    <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden">

        <!-- Close button -->
        <button type="button" data-close="galleryModal"
            class="absolute top-4 right-4 z-20 w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-lg hover:bg-gray-100 transition-colors">
            <i data-lucide="x" class="text-xl text-gray-700"></i>
        </button>

        <div class="p-6 sm:p-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Veja o Lukrato por dentro</h3>

            <!-- Gallery -->
            <div class="relative lk-gallery">
                <!-- Images -->
                <div class="lk-gallery-viewport relative aspect-video bg-gray-100 rounded-xl overflow-hidden mb-6">
                    <div class="lk-gallery-track flex h-full transition-transform duration-300 ease-out">
                        <?php foreach ($landingGallerySlides as $slide): ?>
                            <div class="lk-gallery-slide flex h-full min-w-full items-center justify-center p-4 sm:p-6">
                                <img src="<?= htmlspecialchars($slide['src']) ?>"
                                    data-src="<?= htmlspecialchars($slide['src']) ?>"
                                    <?php if (!empty($slide['darkSrc'])): ?>data-dark-src="<?= htmlspecialchars($slide['darkSrc']) ?>"
                                    <?php endif; ?> data-theme-image-light="<?= htmlspecialchars($slide['src']) ?>"
                                    <?php if (!empty($slide['darkSrc'])): ?>data-theme-image-dark="<?= htmlspecialchars($slide['darkSrc']) ?>"
                                    <?php endif; ?> data-title="<?= htmlspecialchars($slide['title']) ?>"
                                    data-desc="<?= htmlspecialchars($slide['desc']) ?>"
                                    alt="<?= htmlspecialchars($slide['title']) ?> do Lukrato"
                                    class="max-h-full w-full object-contain" loading="lazy" />
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Navigation Arrows -->
                <button type="button"
                    class="lk-gallery-prev absolute left-2 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white rounded-full shadow-lg hover:bg-gray-100 transition-all hover:scale-110 z-10"
                    aria-label="Ver print anterior">
                    <i data-lucide="chevron-left" class="text-gray-700"></i>
                </button>

                <button type="button"
                    class="lk-gallery-next absolute right-2 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white rounded-full shadow-lg hover:bg-gray-100 transition-all hover:scale-110 z-10"
                    aria-label="Ver próximo print">
                    <i data-lucide="chevron-right" class="text-gray-700"></i>
                </button>
            </div>

            <!-- Meta Info -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <div class="flex-1">
                    <h4 id="lkGalleryTitle" class="text-lg font-semibold text-gray-900">
                        <?= htmlspecialchars($landingGallerySlides[0]['title'] ?? 'Tela') ?>
                    </h4>
                    <p id="lkGalleryDesc" class="text-sm text-gray-600">
                        <?= htmlspecialchars($landingGallerySlides[0]['desc'] ?? '') ?>
                    </p>
                </div>
                <div id="lkGalleryCount" class="text-sm font-medium text-gray-500">
                    1/<?= $landingGalleryCount ?>
                </div>
            </div>

            <!-- Thumbnail dots -->
            <div class="flex justify-center gap-2 mt-4">
                <?php foreach ($landingGallerySlides as $index => $slide): ?>
                    <button type="button" data-gallery-dot data-index="<?= $index ?>"
                        class="h-2 rounded-full transition-all <?= $index === 0 ? 'bg-primary w-8' : 'w-2 bg-gray-300 hover:bg-gray-400' ?>"
                        aria-label="Ir para o print <?= $index + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>