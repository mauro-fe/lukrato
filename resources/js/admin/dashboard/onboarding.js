/**
 * ============================================================================
 * LUKRATO — Dashboard Onboarding Checklist
 * ============================================================================
 * Extraído de views/admin/dashboard/index.php
 * Handles: fetch checklist data, render items, dismiss, confetti celebration
 * ============================================================================
 */

const BASE_URL = window.__LK_CONFIG?.baseUrl || window.BASE_URL || '/';

export function initOnboardingChecklist() {
    const firstVisit = !!window.__lkFirstVisit;
    const SKIP_KEY = 'lk_checklist_skipped';
    const el = document.getElementById('onboardingChecklist');

    if (!el) return;

    // User explicitly skipped? Don't show anymore
    if (localStorage.getItem(SKIP_KEY) === '1') return;

    fetch(BASE_URL + 'api/onboarding/checklist', {
        headers: { 'Accept': 'application/json' }
    })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success) return;
            const data = res.data;

            // All complete and not first visit → auto-hide after this visit
            if (data.all_complete && !firstVisit) return;

            renderChecklist(data, el, firstVisit);
            el.style.display = 'block';

            if (firstVisit) {
                fireConfetti();
                setTimeout(function () {
                    if (typeof window.checkPendingAchievements === 'function') {
                        window.gamificationPaused = false;
                        window.checkPendingAchievements();
                    }
                }, 1500);
            }
        })
        .catch(function () { });

    // Dismiss → confirm skip
    const dismissBtn = document.getElementById('checklistDismiss');
    if (dismissBtn) {
        dismissBtn.addEventListener('click', function () {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Pular primeiros passos?',
                    text: 'Você pode sempre acessar essas funcionalidades pelo menu lateral.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-primary, #e67e22)',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sim, pular',
                    cancelButtonText: 'Continuar'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        localStorage.setItem(SKIP_KEY, '1');
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-16px)';
                        el.style.transition = 'all 0.3s ease';
                        setTimeout(function () { el.style.display = 'none'; }, 300);
                    }
                });
            } else {
                if (confirm('Pular primeiros passos? Você pode acessar tudo pelo menu lateral.')) {
                    localStorage.setItem(SKIP_KEY, '1');
                    el.style.display = 'none';
                }
            }
        });
    }
}

function renderChecklist(data, el, firstVisit) {
    const badge = document.getElementById('checklistBadge');
    const fill = document.getElementById('checklistProgressFill');
    const box = document.getElementById('checklistItems');

    if (badge) {
        badge.textContent = data.done_count + '/' + data.total;
        if (data.all_complete) badge.classList.add('complete');
    }

    const pct = (data.done_count / data.total) * 100;
    if (fill) {
        setTimeout(function () { fill.style.width = pct + '%'; }, 100);
    }

    if (!box) return;

    // All complete → celebration
    if (data.all_complete) {
        box.innerHTML =
            '<div class="lk-checklist-complete">' +
            '<div class="lk-checklist-complete-icon"><i data-lucide="party-popper" style="width:48px;height:48px;color:var(--color-success);"></i></div>' +
            '<h3>Parabéns! Você completou tudo</h3>' +
            '<p>Agora é só manter o controle das suas finanças</p>' +
            '</div>';

        if (firstVisit) {
            setTimeout(function () {
                el.style.opacity = '0';
                el.style.transform = 'translateY(-16px)';
                el.style.transition = 'all 0.5s ease';
                setTimeout(function () { el.style.display = 'none'; }, 500);
            }, 8000);
        }
        if (window.lucide) lucide.createIcons();
        return;
    }

    // Sort: pending first, done last
    const sorted = data.items.slice().sort(function (a, b) {
        return a.done - b.done;
    });

    box.innerHTML = sorted.map(function (item) {
        return '<a href="' + BASE_URL + item.href + '" class="lk-checklist-item ' + (item.done ? 'done' : '') + '">' +
            '<div class="lk-checklist-check"><i data-lucide="check"></i></div>' +
            '<div class="lk-checklist-item-icon" style="background:color-mix(in srgb, ' + item.color + ' 15%, var(--color-surface));color:' + item.color + ';">' +
            '<i data-lucide="' + item.icon + '"></i>' +
            '</div>' +
            '<div class="lk-checklist-item-text">' +
            '<span class="lk-checklist-item-label">' + item.label + '</span>' +
            '<span class="lk-checklist-item-desc">' + item.description + '</span>' +
            '</div>' +
            '<i data-lucide="chevron-right" class="lk-checklist-item-arrow"></i>' +
            '</a>';
    }).join('');

    if (window.lucide) lucide.createIcons();
}

function fireConfetti() {
    if (typeof confetti !== 'function') return;
    const duration = 3500;
    const end = Date.now() + duration;
    const defaults = { startVelocity: 35, spread: 360, ticks: 70, zIndex: 99999 };

    const interval = setInterval(function () {
        const timeLeft = end - Date.now();
        if (timeLeft <= 0) return clearInterval(interval);
        const count = 60 * (timeLeft / duration);
        try {
            confetti(Object.assign({}, defaults, {
                particleCount: count,
                origin: { x: Math.random() * 0.3 + 0.1, y: Math.random() - 0.2 }
            }));
            confetti(Object.assign({}, defaults, {
                particleCount: count,
                origin: { x: Math.random() * 0.3 + 0.6, y: Math.random() - 0.2 }
            }));
        } catch (e) {
            clearInterval(interval);
        }
    }, 200);
}
