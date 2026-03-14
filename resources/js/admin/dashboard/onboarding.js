/**
 * ============================================================================
 * LUKRATO — Dashboard Onboarding Checklist (Expandido)
 * ============================================================================
 * Handles: fetch checklist data, render 8 items, gamification points,
 * celebrate achievements, track health score views
 * ============================================================================
 */

const BASE_URL = window.__LK_CONFIG?.baseUrl || window.BASE_URL || '/';

// Store previous state para detectar mudanças
let previousChecklistState = null;

export function initOnboardingChecklist() {
    const firstVisit = !!window.__lkFirstVisit;
    const SKIP_KEY = 'lk_checklist_skipped';
    const el = document.getElementById('onboardingChecklist');

    if (!el) return;

    // User explicitly skipped? Don't show anymore
    if (localStorage.getItem(SKIP_KEY) === '1') return;

    // Inicializar estado anterior
    previousChecklistState = localStorage.getItem('lk_checklist_state') ?
        JSON.parse(localStorage.getItem('lk_checklist_state')) : {};

    fetchAndRenderChecklist(el, firstVisit);

    // Refetch a cada 30 segundos para detectar mudanças
    setInterval(() => {
        fetchAndRenderChecklist(el, firstVisit);
    }, 30000);

    // Listen para eventos de data changed (transações, categorias, etc)
    document.addEventListener('lukrato:data-changed', () => {
        setTimeout(() => fetchAndRenderChecklist(el, firstVisit), 500);
    });

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

function fetchAndRenderChecklist(el, firstVisit) {
    fetch(BASE_URL + 'api/onboarding/checklist', {
        headers: { 'Accept': 'application/json' }
    })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success) return;
            const data = res.data;

            // All complete and not first visit → auto-hide after this visit
            if (data.all_complete && !firstVisit) return;

            renderChecklistExpanded(data, el, firstVisit);
            el.style.display = 'block';

            // Detectar mudanças e celebrar
            detectChecklistChanges(data);

            if (firstVisit) {
                // Set localStorage flags for gamification/state tracking
                localStorage.setItem('lukrato_onboarding_completed', 'true');
                localStorage.removeItem('lukrato_onboarding_in_progress');

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
}

function renderChecklistExpanded(data, el, firstVisit) {
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
            '<p style="margin-bottom: 12px;">Agora é só manter o controle das suas finanças</p>' +
            `<div class="lk-checklist-complete-points">🎉 ${data.total_points} pontos conquistados!</div>` +
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
        return a.done - b.done || a.priority - b.priority;
    });

    // Renderizar com novo template expandido
    box.innerHTML = sorted.map(function (item) {
        const isNew = !previousChecklistState[item.key];
        const wasCompleted = previousChecklistState[item.key] && !item.done;
        const isNowCompleted = !previousChecklistState[item.key] && item.done;

        return '<a href="' + BASE_URL + item.href + '" class="lk-checklist-item ' + (item.done ? 'done' : '') + '" data-item-key="' + item.key + '">' +
            '<div class="lk-checklist-check"><i data-lucide="check"></i></div>' +
            '<div class="lk-checklist-item-icon" style="background:color-mix(in srgb, ' + item.color + ' 15%, var(--color-surface));color:' + item.color + ';">' +
            '<i data-lucide="' + item.icon + '"></i>' +
            '</div>' +
            '<div class="lk-checklist-item-text">' +
            '<span class="lk-checklist-item-label">' + item.label + '</span>' +
            '<span class="lk-checklist-item-desc">' + item.description + '</span>' +
            '</div>' +
            '<div class="lk-checklist-item-points">' +
            (item.done ? '<span class="points-earned">+' + item.points + '⭐</span>' : '<span class="points-pending">+' + item.points + '⭐</span>') +
            '</div>' +
            '<i data-lucide="chevron-right" class="lk-checklist-item-arrow"></i>' +
            '</a>';
    }).join('');

    if (window.lucide) lucide.createIcons();

    // Add click handlers para tracking
    document.querySelectorAll('.lk-checklist-item').forEach(link => {
        link.addEventListener('click', function (e) {
            const key = this.getAttribute('data-item-key');
            // Mark as viewed but don't prevent navigation
            localStorage.setItem('lk_checklist_item_clicked_' + key, 'true');
        });
    });
}

function detectChecklistChanges(data) {
    const currentState = {};
    data.items.forEach(item => {
        currentState[item.key] = item.done;
    });

    // Comparar com estado anterior
    if (previousChecklistState) {
        data.items.forEach(item => {
            const wasNotDone = !previousChecklistState[item.key];
            const isNowDone = item.done;

            if (wasNotDone && isNowDone) {
                // Item foi completado agora!
                celebrateChecklistCompletion(item);
            }
        });
    }

    // Salvar novo estado
    localStorage.setItem('lk_checklist_state', JSON.stringify(currentState));
    previousChecklistState = currentState;
}

function celebrateChecklistCompletion(item) {
    // Toast de sucesso
    if (window.LK?.toast) {
        window.LK.toast.success(`🎉 Item desbloqueado: ${item.label}`);
    }

    // Confetti
    if (typeof confetti === 'function') {
        confetti({
            particleCount: 50,
            spread: 70,
            origin: { x: 0.5, y: 0.3 }
        });
    }

    // Dispatch custom event para gamification
    document.dispatchEvent(new CustomEvent('lukrato:checklist-item-completed', {
        detail: {
            key: item.key,
            label: item.label,
            points: item.points,
            icon: item.icon
        }
    }));
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
