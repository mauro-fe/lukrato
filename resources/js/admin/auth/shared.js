/**
 * ============================================================================
 * LUKRATO — Auth Shared Utilities
 * ============================================================================
 * Particles, confetti, toggle-password — used by login, forgot-password,
 * reset-password pages.
 *
 * Substitui: public/assets/js/auth/auth-shared.js
 * ============================================================================
*/

import '../global/lucide-init.js';

// ── Particles ──────────────────────────────────────────────────────────────

export function createParticles() {
    const container = document.getElementById('particles');
    if (!container) return;

    for (let i = 0; i < 20; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 8 + 's';
        particle.style.animationDuration = (Math.random() * 4 + 6) + 's';
        container.appendChild(particle);
    }
}

// ── Confetti celebration ───────────────────────────────────────────────────

export function createConfetti(count = 40) {
    const colors = ['#e67e22', '#f39c12', '#79e6a0', '#7aa7ff'];

    // Inject keyframes once
    if (!document.getElementById('confetti-style')) {
        const style = document.createElement('style');
        style.id = 'confetti-style';
        style.textContent =
            '@keyframes confettiFall{to{transform:translateY(100vh) rotate(' +
            (Math.random() * 360) + 'deg);opacity:0}}';
        document.head.appendChild(style);
    }

    for (let i = 0; i < count; i++) {
        const el = document.createElement('div');
        el.style.cssText =
            'position:fixed;width:10px;height:10px;border-radius:50%;' +
            'pointer-events:none;z-index:9999;top:-10px;' +
            'background:' + colors[Math.floor(Math.random() * colors.length)] + ';' +
            'left:' + Math.random() * 100 + '%;' +
            'animation:confettiFall ' + (Math.random() * 2 + 2) + 's ease-out forwards';
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }
}

// ── Toggle password visibility ─────────────────────────────────────────────

export function initTogglePassword() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.toggle-password');
        if (!btn) return;

        const targetId = btn.dataset.target;
        const input = document.getElementById(targetId);
        if (!input) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        const oldIcon = btn.querySelector('svg, i');
        if (oldIcon) {
            const newIcon = document.createElement('i');
            newIcon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
            oldIcon.replaceWith(newIcon);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    });
}

// ── Base URL helper ────────────────────────────────────────────────────────

export function getBaseUrl() {
    const meta = document.querySelector('meta[name="base-url"]');
    return meta ? meta.content.replace(/\/?$/, '/') : '/';
}
