/**
 * ============================================================================
 * LUKRATO — Card / Link-in-Bio Page (Vite Module)
 * ============================================================================
 * Animation, click tracking, share button, analytics timer.
 *
 * Substitui: public/assets/js/card.js
 * ============================================================================
 */

(function () {
    'use strict';

    /* ─── Click Tracking ──────────────────────────────────────────────────── */

    function setupClickTracking() {
        document.querySelectorAll('.lk-card-link').forEach(card => {
            card.addEventListener('click', async (e) => {
                const linkId = card.dataset.linkId;
                if (!linkId) return;

                try {
                    await fetch(`/api/card/click/${linkId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                } catch (err) {
                    console.warn('Click tracking failed:', err);
                }
            });
        });
    }

    /* ─── Intersection Observer — fade-in ─────────────────────────────────── */

    function setupAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.lk-card-link, .lk-card-header, .lk-card-social-links').forEach(el => {
            observer.observe(el);
        });
    }

    /* ─── Share Button ────────────────────────────────────────────────────── */

    function setupShare() {
        const btn = document.querySelector('.lk-card-share-btn');
        if (!btn) return;

        btn.addEventListener('click', async () => {
            const url = window.location.href;
            const title = document.title;

            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                    return;
                } catch (err) {
                    if (err.name === 'AbortError') return;
                }
            }

            try {
                await navigator.clipboard.writeText(url);
                showNotification('Link copiado!', 'success');
            } catch {
                const input = document.createElement('input');
                input.value = url;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                showNotification('Link copiado!', 'success');
            }
        });
    }

    /* ─── Notifications (local — standalone page) ─────────────────────────── */

    function showNotification(message, type = 'info') {
        const existing = document.querySelector('.lk-card-notification');
        if (existing) existing.remove();

        const div = document.createElement('div');
        div.className = `lk-card-notification lk-card-notification--${type}`;
        div.textContent = message;

        Object.assign(div.style, {
            position: 'fixed',
            bottom: '20px',
            left: '50%',
            transform: 'translateX(-50%)',
            padding: '12px 24px',
            borderRadius: '8px',
            color: '#fff',
            fontSize: '14px',
            fontWeight: '500',
            zIndex: '9999',
            opacity: '0',
            transition: 'opacity 0.3s ease',
            backgroundColor: type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'
        });

        document.body.appendChild(div);
        requestAnimationFrame(() => { div.style.opacity = '1'; });
        setTimeout(() => {
            div.style.opacity = '0';
            setTimeout(() => div.remove(), 300);
        }, 3000);
    }

    /* ─── Analytics Timer ─────────────────────────────────────────────────── */

    function setupAnalytics() {
        const slug = document.querySelector('[data-card-slug]');
        if (!slug) return;

        const cardSlug = slug.dataset.cardSlug;
        const startTime = Date.now();

        window.addEventListener('beforeunload', () => {
            const timeSpent = Math.round((Date.now() - startTime) / 1000);
            if (timeSpent < 2) return;

            const data = JSON.stringify({ slug: cardSlug, time_spent: timeSpent });
            if (navigator.sendBeacon) {
                navigator.sendBeacon('/api/card/analytics', new Blob([data], { type: 'application/json' }));
            }
        });
    }

    /* ─── Init ────────────────────────────────────────────────────────────── */

    function init() {
        setupClickTracking();
        setupAnimations();
        setupShare();
        setupAnalytics();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
