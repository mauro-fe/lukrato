/**
 * Gallery — Modal slider with keyboard / swipe support
 */

export function init() {
    const modalId = 'func-gallery';
    const modal = document.getElementById(modalId);
    if (!modal) return;

    const openButtons  = document.querySelectorAll(`[data-open="${modalId}"]`);
    const closeButtons = modal.querySelectorAll(`[data-close="${modalId}"]`);

    const track  = modal.querySelector('.lk-gallery-track');
    const slides = track ? Array.from(track.querySelectorAll('img')) : [];

    const prevBtn = modal.querySelector('.lk-gallery-prev');
    const nextBtn = modal.querySelector('.lk-gallery-next');

    const titleEl = modal.querySelector('#lkGalleryTitle');
    const descEl  = modal.querySelector('#lkGalleryDesc');
    const countEl = modal.querySelector('#lkGalleryCount');

    const gallery = modal.querySelector('.lk-gallery');

    let currentIndex = 0;
    let startX = 0;
    let isDragging = false;

    function openModal() {
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => updateGallery(true));
    }

    function closeModal() {
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    function updateMeta() {
        if (!slides.length) return;
        const s = slides[currentIndex];
        const t = s.dataset.title || s.alt || 'Tela';
        const d = s.dataset.desc || '';
        if (titleEl) titleEl.textContent = t;
        if (descEl)  descEl.textContent  = d;
        if (countEl) countEl.textContent  = `${currentIndex + 1}/${slides.length}`;
    }

    function updateGallery(skipAnim = false) {
        if (!track || slides.length === 0) return;
        const width = slides[0].clientWidth;
        if (skipAnim) track.style.transition = 'none';
        track.style.transform = `translateX(-${currentIndex * width}px)`;
        updateMeta();
        if (skipAnim) {
            requestAnimationFrame(() => { track.style.transition = 'transform 0.28s ease'; });
        }
    }

    function goNext() { if (!slides.length) return; currentIndex = (currentIndex + 1) % slides.length; updateGallery(); }
    function goPrev() { if (!slides.length) return; currentIndex = (currentIndex - 1 + slides.length) % slides.length; updateGallery(); }

    openButtons.forEach(btn  => btn.addEventListener('click', openModal));
    closeButtons.forEach(btn => btn.addEventListener('click', closeModal));
    if (prevBtn) prevBtn.addEventListener('click', goPrev);
    if (nextBtn) nextBtn.addEventListener('click', goNext);
    slides.forEach(img => img.addEventListener('click', goNext));

    document.addEventListener('keydown', (e) => {
        if (!modal.classList.contains('is-open')) return;
        if (e.key === 'Escape')     closeModal();
        if (e.key === 'ArrowRight') goNext();
        if (e.key === 'ArrowLeft')  goPrev();
    });

    if (gallery) {
        gallery.addEventListener('touchstart', (e) => {
            if (!modal.classList.contains('is-open')) return;
            startX = e.touches[0].clientX;
            isDragging = true;
        }, { passive: true });

        gallery.addEventListener('touchmove', () => {}, { passive: true });

        gallery.addEventListener('touchend', (e) => {
            if (!isDragging) return;
            const diff = e.changedTouches[0].clientX - startX;
            if (Math.abs(diff) > 45) { diff < 0 ? goNext() : goPrev(); }
            isDragging = false;
        });
    }

    window.addEventListener('resize', () => updateGallery(true));
    updateMeta();
}
