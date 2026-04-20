/**
 * Hero Carousel — rotates the hero mockup through landing screenshots.
 */

export function init() {
    const carousels = Array.from(document.querySelectorAll('[data-hero-carousel]'));
    if (carousels.length === 0) return;

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const transitionValue = 'transform 0.72s cubic-bezier(0.22, 1, 0.36, 1)';

    carousels.forEach((carousel) => {
        if (carousel.dataset.heroCarouselReady === 'true') return;

        const track = carousel.querySelector('[data-hero-track]');
        const originalSlides = Array.from(carousel.querySelectorAll('[data-hero-slide]'));
        const dots = Array.from(carousel.querySelectorAll('[data-hero-dot]'));
        const prevButton = carousel.querySelector('[data-hero-prev]');
        const nextButton = carousel.querySelector('[data-hero-next]');
        const currentEyebrow = carousel.querySelector('[data-hero-current-eyebrow]');
        const currentTitle = carousel.querySelector('[data-hero-current-title]');
        const currentCount = carousel.querySelector('[data-hero-current-count]');
        const autoplayMs = Number.parseInt(carousel.dataset.autoplayMs || '4600', 10);
        const forceAutoplay = carousel.dataset.autoplayForce === 'true';

        if (!track || originalSlides.length <= 1) return;

        carousel.dataset.heroCarouselReady = 'true';

        let currentLogicalIndex = originalSlides.findIndex((slide) => slide.getAttribute('aria-hidden') === 'false');
        if (currentLogicalIndex < 0) currentLogicalIndex = 0;

        const firstClone = originalSlides[0].cloneNode(true);
        const lastClone = originalSlides[originalSlides.length - 1].cloneNode(true);
        firstClone.dataset.heroClone = 'true';
        lastClone.dataset.heroClone = 'true';
        track.insertBefore(lastClone, track.firstChild);
        track.appendChild(firstClone);

        const trackSlides = Array.from(track.querySelectorAll('[data-hero-slide]'));
        let currentTrackIndex = currentLogicalIndex + 1;

        let intervalId = null;
        let isTransitioning = false;

        function normaliseLogicalIndex(index) {
            return ((index % originalSlides.length) + originalSlides.length) % originalSlides.length;
        }

        function getLogicalIndex(trackIndex) {
            if (trackIndex === 0) return originalSlides.length - 1;
            if (trackIndex === trackSlides.length - 1) return 0;
            return trackIndex - 1;
        }

        function updateMeta(nextIndex) {
            const activeSlide = originalSlides[nextIndex];
            if (currentEyebrow) currentEyebrow.textContent = activeSlide.dataset.eyebrow || '';
            if (currentTitle) currentTitle.textContent = activeSlide.dataset.title || '';
            if (currentCount) currentCount.textContent = `${nextIndex + 1}/${originalSlides.length}`;
        }

        function updateDots(nextIndex) {
            dots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === nextIndex;
                dot.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                dot.classList.toggle('w-6', isActive);
                dot.classList.toggle('sm:w-8', isActive);
                dot.classList.toggle('bg-primary', isActive);
                dot.classList.toggle('w-2', !isActive);
                dot.classList.toggle('sm:w-2.5', !isActive);
                dot.classList.toggle('bg-gray-300', !isActive);
                dot.classList.toggle('hover:bg-gray-400', !isActive);
            });
        }

        function updateAccessibility(activeTrackIndex) {
            trackSlides.forEach((slide, trackIndex) => {
                slide.setAttribute('aria-hidden', trackIndex === activeTrackIndex ? 'false' : 'true');
            });
        }

        function setTrackPosition(nextTrackIndex, immediate = false) {
            if (immediate) {
                track.style.transition = 'none';
                track.style.transform = `translate3d(-${nextTrackIndex * 100}%, 0, 0)`;
                track.getBoundingClientRect();
                track.style.transition = transitionValue;
                return;
            }

            track.style.transition = transitionValue;
            track.style.transform = `translate3d(-${nextTrackIndex * 100}%, 0, 0)`;
        }

        function syncUi(activeTrackIndex, nextIndex) {
            updateAccessibility(activeTrackIndex);
            updateMeta(nextIndex);
            updateDots(nextIndex);
        }

        function setActiveSlide(nextIndex, immediate = false) {
            currentLogicalIndex = normaliseLogicalIndex(nextIndex);
            currentTrackIndex = currentLogicalIndex + 1;
            syncUi(currentTrackIndex, currentLogicalIndex);
            setTrackPosition(currentTrackIndex, immediate);
        }

        function moveTrack(nextTrackIndex) {
            if (isTransitioning) return;

            isTransitioning = true;
            currentTrackIndex = nextTrackIndex;
            currentLogicalIndex = getLogicalIndex(currentTrackIndex);
            syncUi(currentTrackIndex, currentLogicalIndex);
            setTrackPosition(currentTrackIndex);
        }

        function stopAutoplay() {
            if (intervalId) {
                window.clearInterval(intervalId);
                intervalId = null;
            }
        }

        function startAutoplay() {
            if ((!forceAutoplay && prefersReducedMotion.matches) || originalSlides.length <= 1) return;
            stopAutoplay();
            intervalId = window.setInterval(() => {
                moveTrack(currentTrackIndex + 1);
            }, autoplayMs);
        }

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                const index = Number.parseInt(dot.dataset.index || '0', 10);
                setActiveSlide(Number.isNaN(index) ? 0 : index);
                startAutoplay();
            });
        });

        prevButton?.addEventListener('click', () => {
            moveTrack(currentTrackIndex - 1);
            startAutoplay();
        });

        nextButton?.addEventListener('click', () => {
            moveTrack(currentTrackIndex + 1);
            startAutoplay();
        });

        track.addEventListener('transitionend', (event) => {
            if (event.propertyName !== 'transform') return;

            if (currentTrackIndex === 0) {
                currentTrackIndex = originalSlides.length;
                setTrackPosition(currentTrackIndex, true);
            } else if (currentTrackIndex === trackSlides.length - 1) {
                currentTrackIndex = 1;
                setTrackPosition(currentTrackIndex, true);
            }

            currentLogicalIndex = getLogicalIndex(currentTrackIndex);
            syncUi(currentTrackIndex, currentLogicalIndex);
            isTransitioning = false;
        });

        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);
        carousel.addEventListener('focusin', stopAutoplay);
        carousel.addEventListener('focusout', startAutoplay);

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopAutoplay();
            } else {
                startAutoplay();
            }
        });

        setActiveSlide(currentLogicalIndex, true);
        startAutoplay();
    });
}