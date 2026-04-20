/**
 * Hero Headline — typewriter rotation for the highlighted phrase.
 */

export function init() {
    const root = document.querySelector('[data-hero-typewriter]');
    if (!root) return;

    const textEl = root.querySelector('[data-hero-typewriter-text]');
    if (!textEl) return;

    const phrases = JSON.parse(root.dataset.phrases || '[]').filter(Boolean);
    if (phrases.length === 0) return;

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const forceTypewriter = root.dataset.typewriterForce === 'true';

    if ((!forceTypewriter && prefersReducedMotion.matches) || phrases.length === 1) {
        textEl.textContent = phrases[0];
        root.setAttribute('aria-label', phrases[0]);
        return;
    }

    let phraseIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    let timerId = null;

    const TYPE_DELAY = 85;
    const DELETE_DELAY = 42;
    const HOLD_DELAY = 1350;
    const START_DELAY = 350;

    function clearTimer() {
        if (timerId) {
            window.clearTimeout(timerId);
            timerId = null;
        }
    }

    function schedule(delay) {
        clearTimer();
        timerId = window.setTimeout(tick, delay);
    }

    function tick() {
        const phrase = phrases[phraseIndex];

        if (!isDeleting) {
            charIndex += 1;
            textEl.textContent = phrase.slice(0, charIndex);

            if (charIndex === phrase.length) {
                root.setAttribute('aria-label', phrase);
                isDeleting = true;
                schedule(HOLD_DELAY);
                return;
            }

            schedule(TYPE_DELAY);
            return;
        }

        charIndex -= 1;
        textEl.textContent = phrase.slice(0, Math.max(charIndex, 0));

        if (charIndex === 0) {
            isDeleting = false;
            phraseIndex = (phraseIndex + 1) % phrases.length;
            schedule(TYPE_DELAY + 40);
            return;
        }

        schedule(DELETE_DELAY);
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearTimer();
            return;
        }

        schedule(TYPE_DELAY);
    });

    schedule(START_DELAY);
}