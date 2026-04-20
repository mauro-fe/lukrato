/**
 * Gallery — Inline showcase + modal slider for landing screenshots
 */

function getCurrentTheme() {
    return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
}

function resolveSlideSrc(slide) {
    return getCurrentTheme() === 'dark' && slide.darkSrc ? slide.darkSrc : slide.src;
}

function readSlideData(element) {
    return {
        src: element.dataset.src || element.getAttribute('src') || '',
        darkSrc: element.dataset.darkSrc || '',
        title: element.dataset.title || element.getAttribute('alt') || 'Tela',
        eyebrow: element.dataset.eyebrow || '',
        desc: element.dataset.desc || '',
    };
}

export function init() {
    const modal = document.getElementById('galleryModal') || document.getElementById('func-gallery');
    const showcase = document.querySelector('[data-gallery-showcase]');

    if (!modal && !showcase) return;

    const modalId = modal ? modal.id : 'galleryModal';
    const openButtons = document.querySelectorAll(`[data-open="${modalId}"]`);
    const closeButtons = modal ? modal.querySelectorAll(`[data-close="${modalId}"]`) : [];

    const showcaseButtons = showcase ? Array.from(showcase.querySelectorAll('[data-gallery-select]')) : [];
    const showcaseImage = showcase ? showcase.querySelector('[data-gallery-current-image]') : null;
    const showcaseEyebrow = showcase ? showcase.querySelector('[data-gallery-current-eyebrow]') : null;
    const showcaseTitle = showcase ? showcase.querySelector('[data-gallery-current-title]') : null;
    const showcaseDesc = showcase ? showcase.querySelector('[data-gallery-current-desc]') : null;
    const showcaseCount = showcase ? showcase.querySelector('[data-gallery-current-count]') : null;

    const viewport = modal ? modal.querySelector('.lk-gallery-viewport') : null;
    const track = modal ? modal.querySelector('.lk-gallery-track') : null;
    const modalSlides = track ? Array.from(track.querySelectorAll('.lk-gallery-slide img')) : [];
    const dots = modal ? Array.from(modal.querySelectorAll('[data-gallery-dot]')) : [];
    const prevBtn = modal ? modal.querySelector('.lk-gallery-prev') : null;
    const nextBtn = modal ? modal.querySelector('.lk-gallery-next') : null;
    const titleEl = modal ? modal.querySelector('#lkGalleryTitle') : null;
    const descEl = modal ? modal.querySelector('#lkGalleryDesc') : null;
    const countEl = modal ? modal.querySelector('#lkGalleryCount') : null;
    const gallery = modal ? modal.querySelector('.lk-gallery') : null;

    const slideElements = showcaseButtons.length ? showcaseButtons : modalSlides;
    const totalSlides = slideElements.length;

    if (!totalSlides) return;

    let currentIndex = 0;
    let startX = 0;
    let isDragging = false;

    function getSlide(index) {
        const safeIndex = ((index % totalSlides) + totalSlides) % totalSlides;
        return readSlideData(slideElements[safeIndex]);
    }

    function setShowcaseState() {
        if (!showcase) return;

        const slide = getSlide(currentIndex);

        if (showcaseEyebrow) showcaseEyebrow.textContent = slide.eyebrow;
        if (showcaseTitle) showcaseTitle.textContent = slide.title;
        if (showcaseDesc) showcaseDesc.textContent = slide.desc;
        if (showcaseCount) showcaseCount.textContent = `${currentIndex + 1}/${totalSlides}`;

        if (showcaseImage) {
            const nextSrc = resolveSlideSrc(slide);
            showcaseImage.setAttribute('src', nextSrc);
            showcaseImage.setAttribute('alt', `${slide.title} do Lukrato`);
            showcaseImage.setAttribute('data-theme-image-light', slide.src);

            if (slide.darkSrc) {
                showcaseImage.setAttribute('data-theme-image-dark', slide.darkSrc);
            } else {
                showcaseImage.removeAttribute('data-theme-image-dark');
            }
        }

        showcaseButtons.forEach((button, index) => {
            const isActive = index === currentIndex;
            const thumb = button.querySelector('.lk-gallery-thumb');
            const eyebrow = button.querySelector('.lk-gallery-option-eyebrow');

            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            button.classList.toggle('border-orange-200', isActive);
            button.classList.toggle('bg-orange-50/70', isActive);
            button.classList.toggle('shadow-sm', isActive);
            button.classList.toggle('border-gray-200', !isActive);
            button.classList.toggle('bg-white', !isActive);

            if (thumb) {
                thumb.classList.toggle('border-orange-200', isActive);
                thumb.classList.toggle('border-gray-200', !isActive);
            }

            if (eyebrow) {
                eyebrow.classList.toggle('text-primary', isActive);
                eyebrow.classList.toggle('text-gray-400', !isActive);
            }
        });
    }

    function setModalState(skipAnimation = false) {
        if (!modal || !track || !viewport || modalSlides.length === 0) return;

        const slide = getSlide(currentIndex);
        const width = viewport.clientWidth;
        if (skipAnimation) track.style.transition = 'none';
        track.style.transform = `translateX(-${currentIndex * width}px)`;

        if (titleEl) titleEl.textContent = slide.title;
        if (descEl) descEl.textContent = slide.desc;
        if (countEl) countEl.textContent = `${currentIndex + 1}/${totalSlides}`;

        dots.forEach((dot, index) => {
            const isActive = index === currentIndex;
            dot.classList.toggle('bg-primary', isActive);
            dot.classList.toggle('w-8', isActive);
            dot.classList.toggle('bg-gray-300', !isActive);
            dot.classList.toggle('hover:bg-gray-400', !isActive);
            dot.classList.toggle('w-2', !isActive);
        });

        if (skipAnimation) {
            requestAnimationFrame(() => {
                track.style.transition = 'transform 0.3s ease';
            });
        }
    }

    function syncAll(skipAnimation = false) {
        setShowcaseState();
        setModalState(skipAnimation);
    }

    function setIndex(index, skipAnimation = false) {
        currentIndex = ((index % totalSlides) + totalSlides) % totalSlides;
        syncAll(skipAnimation);
    }

    function openModal(index = currentIndex) {
        if (!modal) return;

        setIndex(index, true);
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modal) return;

        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function isModalOpen() {
        return Boolean(modal) && modal.getAttribute('aria-hidden') === 'false';
    }

    function goNext() {
        setIndex(currentIndex + 1);
    }

    function goPrev() {
        setIndex(currentIndex - 1);
    }

    showcaseButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const index = Number.parseInt(button.dataset.index || '0', 10);
            setIndex(Number.isNaN(index) ? 0 : index);
        });
    });

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const buttonIndex = Number.parseInt(button.dataset.galleryIndex || `${currentIndex}`, 10);
            openModal(Number.isNaN(buttonIndex) ? currentIndex : buttonIndex);
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            const index = Number.parseInt(dot.dataset.index || '0', 10);
            setIndex(Number.isNaN(index) ? 0 : index);
        });
    });

    if (prevBtn) prevBtn.addEventListener('click', goPrev);
    if (nextBtn) nextBtn.addEventListener('click', goNext);

    modalSlides.forEach((image, index) => {
        image.addEventListener('click', () => {
            setIndex(index);
            goNext();
        });
    });

    document.addEventListener('keydown', (event) => {
        if (!isModalOpen()) return;
        if (event.key === 'Escape') closeModal();
        if (event.key === 'ArrowRight') goNext();
        if (event.key === 'ArrowLeft') goPrev();
    });

    if (gallery) {
        gallery.addEventListener('touchstart', (event) => {
            if (!isModalOpen()) return;
            startX = event.touches[0].clientX;
            isDragging = true;
        }, { passive: true });

        gallery.addEventListener('touchend', (event) => {
            if (!isDragging) return;

            const diff = event.changedTouches[0].clientX - startX;
            if (Math.abs(diff) > 45) {
                diff < 0 ? goNext() : goPrev();
            }
            isDragging = false;
        });
    }

    window.addEventListener('resize', () => {
        if (isModalOpen()) {
            setModalState(true);
        }
    });

    document.addEventListener('lukrato:theme-changed', () => {
        setShowcaseState();
    });

    syncAll(true);
}
