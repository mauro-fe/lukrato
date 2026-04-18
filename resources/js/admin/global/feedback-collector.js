/**
 * ============================================================================
 * LUKRATO — User Feedback Collector (Global)
 * ============================================================================
 * Micro feedback, NPS mensal, botao sugestao, AI feedback.
 * IIFE global, expoe window.LKUserFeedback
 * ============================================================================
 */
import { apiGet, apiPost } from '../shared/api.js';
import {
    resolveFeedbackCanMicroEndpoint,
    resolveFeedbackCheckNpsEndpoint,
    resolveFeedbackEndpoint,
} from '../api/endpoints/engagement.js';
import { getRuntimeConfig } from './runtime-config.js';

(function () {
    'use strict';

    const userId = () => getRuntimeConfig().userId;

    // ========================================================================
    // API HELPERS
    // ========================================================================

    async function postFeedback(data) {
        try {
            const json = await apiPost(resolveFeedbackEndpoint(), data);
            return json.success === true;
        } catch {
            return false;
        }
    }

    async function apiCheck(endpoint) {
        try {
            const json = await apiGet(endpoint);
            return json.data ?? json;
        } catch {
            return {};
        }
    }

    // ========================================================================
    // 1. MICRO FEEDBACK
    // ========================================================================

    async function showMicroFeedback(contexto, question) {
        if (!userId()) return;

        const sessionKey = `lk_micro_fb_${contexto}`;
        if (sessionStorage.getItem(sessionKey)) return;

        try {
            const check = await apiCheck(`${resolveFeedbackCanMicroEndpoint()}?contexto=${encodeURIComponent(contexto)}`);
            if (!check.can_show) return;
        } catch { return; }

        // Remove existing widget if any
        document.querySelector('.lk-micro-feedback')?.remove();

        const widget = document.createElement('div');
        widget.className = 'lk-micro-feedback';
        widget.innerHTML = `
            <button class="lk-micro-feedback__close" aria-label="Fechar">&times;</button>
            <div class="lk-micro-feedback__question">${escapeHtml(question || 'Como foi sua experiencia?')}</div>
            <div class="lk-micro-feedback__actions">
                <button class="lk-micro-feedback__btn lk-micro-feedback__btn--positive" data-rating="1">
                    👍 Sim
                </button>
                <button class="lk-micro-feedback__btn lk-micro-feedback__btn--negative" data-rating="0">
                    👎 Nao
                </button>
            </div>
        `;

        document.body.appendChild(widget);

        // Close button
        widget.querySelector('.lk-micro-feedback__close').addEventListener('click', () => closeMicroWidget(widget));

        // Auto-dismiss after 15s
        const autoDismiss = setTimeout(() => closeMicroWidget(widget), 15000);

        // Rating buttons
        widget.querySelectorAll('.lk-micro-feedback__btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                clearTimeout(autoDismiss);
                const rating = parseInt(btn.dataset.rating);

                if (rating === 1) {
                    // Positive - send immediately
                    sessionStorage.setItem(sessionKey, '1');
                    await postFeedback({ tipo_feedback: 'acao', contexto, rating });
                    showMicroThanks(widget);
                } else {
                    // Negative - show comment field
                    widget.querySelector('.lk-micro-feedback__actions').style.display = 'none';
                    const commentHtml = `
                        <div class="lk-micro-feedback__comment">
                            <textarea placeholder="O que podemos melhorar?" maxlength="2000" rows="2"></textarea>
                            <button class="lk-micro-feedback__comment-send">Enviar</button>
                        </div>
                    `;
                    widget.insertAdjacentHTML('beforeend', commentHtml);

                    widget.querySelector('.lk-micro-feedback__comment-send').addEventListener('click', async () => {
                        const comment = widget.querySelector('textarea').value.trim();
                        sessionStorage.setItem(sessionKey, '1');
                        await postFeedback({
                            tipo_feedback: 'acao',
                            contexto,
                            rating: 0,
                            comentario: comment || null,
                        });
                        showMicroThanks(widget);
                    });
                }
            });
        });
    }

    function showMicroThanks(widget) {
        widget.innerHTML = '<div class="lk-micro-feedback__thanks">Obrigado pelo feedback! 🙏</div>';
        setTimeout(() => closeMicroWidget(widget), 1800);
    }

    function closeMicroWidget(widget) {
        if (!widget || !widget.parentNode) return;
        widget.classList.add('lk-fb-closing');
        setTimeout(() => widget.remove(), 250);
    }

    // ========================================================================
    // 2. NPS MENSAL
    // ========================================================================

    function initNps() {
        // Check localStorage first (avoid unnecessary API call)
        const dismissed = localStorage.getItem('lk_nps_dismissed_at');
        if (dismissed) {
            const daysSince = (Date.now() - parseInt(dismissed)) / (1000 * 60 * 60 * 24);
            if (daysSince < 30) return;
        }

        // Delay 5 seconds to not interrupt initial page load
        setTimeout(async () => {
            if (!userId()) return;

            try {
                const check = await apiCheck(resolveFeedbackCheckNpsEndpoint());
                if (!check.show_nps) return;
            } catch { return; }

            showNpsModal();
        }, 5000);
    }

    function showNpsModal() {
        if (typeof Swal === 'undefined') return;

        let selectedScore = null;

        const scaleButtons = Array.from({ length: 11 }, (_, i) =>
            `<button type="button" class="lk-nps-scale__btn" data-score="${i}">${i}</button>`
        ).join('');

        Swal.fire({
            title: 'O que acha do Lukrato?',
            html: `
                <div class="lk-nps-container">
                    <p style="font-size:0.85rem;color:var(--color-text-muted,#94a3b8);margin-bottom:4px;">
                        De 0 a 10, quanto você recomendaria o Lukrato para um amigo?
                    </p>
                    <div class="lk-nps-scale">${scaleButtons}</div>
                    <div class="lk-nps-labels">
                        <span>Nada provavel</span>
                        <span>Muito provavel</span>
                    </div>
                    <div class="lk-nps-comment" style="display:none;">
                        <textarea placeholder="Quer compartilhar algo mais? (opcional)" maxlength="2000" rows="2"></textarea>
                    </div>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'Enviar',
            confirmButtonColor: '#e67e22',
            showCancelButton: true,
            cancelButtonText: 'Agora nao',
            customClass: { popup: 'lk-swal-nps' },
            allowOutsideClick: false,
            preConfirm: () => {
                if (selectedScore === null) {
                    Swal.showValidationMessage('Selecione uma nota de 0 a 10');
                    return false;
                }
                const comment = Swal.getPopup().querySelector('.lk-nps-comment textarea')?.value?.trim() || null;
                return { score: selectedScore, comment };
            },
            didOpen: () => {
                const popup = Swal.getPopup();

                popup.querySelectorAll('.lk-nps-scale__btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        selectedScore = parseInt(btn.dataset.score);
                        popup.querySelectorAll('.lk-nps-scale__btn').forEach(b => b.classList.remove('selected'));
                        btn.classList.add('selected');
                        popup.querySelector('.lk-nps-comment').style.display = 'block';
                    });
                });
            },
        }).then(async (result) => {
            localStorage.setItem('lk_nps_dismissed_at', String(Date.now()));

            if (result.isConfirmed && result.value) {
                await postFeedback({
                    tipo_feedback: 'nps',
                    rating: result.value.score,
                    comentario: result.value.comment,
                });

                if (typeof LK?.toast?.success === 'function') {
                    LK.toast.success('Obrigado pelo feedback!');
                }
            }
        });
    }

    // ========================================================================
    // 3. BOTAO FIXO DE SUGESTAO
    // ========================================================================

    function initSuggestionButton() {
        if (document.body?.dataset.lkSuggestionButtonBound === 'true') {
            return;
        }

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('#sidebarSuggestionBtn');
            if (!btn) return;

            e.preventDefault();
            showSuggestionModal();
        });

        if (document.body) {
            document.body.dataset.lkSuggestionButtonBound = 'true';
        }
    }

    function showSuggestionModal() {
        if (typeof Swal === 'undefined') return;

        let selectedRating = 0;
        let selectedType = null;

        const isDark = (document.documentElement.getAttribute('data-theme') || 'dark') === 'dark';
        const swalTheme = {
            background: isDark ? '#1e293b' : '#ffffff',
            color: isDark ? '#f1f5f9' : '#1e293b',
            confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#e67e22',
        };

        const types = [
            { id: 'sugestao', label: 'Sugestão', icon: 'lightbulb' },
            { id: 'critica', label: 'Crítica', icon: 'alert-triangle' },
            { id: 'elogio', label: 'Elogio', icon: 'heart' },
            { id: 'bug', label: 'Bug', icon: 'bug' },
        ];

        const typeChips = types.map(t =>
            `<button type="button" class="lk-sg-chip" data-type="${t.id}"><i data-lucide="${t.icon}"></i>${t.label}</button>`
        ).join('');

        const stars = Array.from({ length: 5 }, (_, i) =>
            `<svg class="lk-star-rating__star" data-star="${i + 1}" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`
        ).join('');

        Swal.fire({
            title: 'Envie seu feedback',
            html: `
                <div class="lk-sg-modal">
                    <p class="lk-sg-subtitle">Sua opinião nos ajuda a melhorar o Lukrato.</p>
                    <div class="lk-sg-section">
                        <label class="lk-sg-label">Tipo</label>
                        <div class="lk-sg-chips">${typeChips}</div>
                    </div>
                    <div class="lk-sg-section">
                        <label class="lk-sg-label">Avaliação</label>
                        <div class="lk-star-rating">${stars}</div>
                    </div>
                    <div class="lk-sg-section">
                        <label class="lk-sg-label" for="lkSuggestionText">Mensagem</label>
                        <textarea id="lkSuggestionText" class="lk-sg-textarea" placeholder="Descreva sua sugestão, crítica ou elogio..." maxlength="2000" rows="4"></textarea>
                    </div>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: '<i data-lucide="send"></i> Enviar feedback',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            ...swalTheme,
            customClass: {
                popup: 'lk-swal-popup lk-swal-suggestion',
            },
            preConfirm: () => {
                const comment = document.getElementById('lkSuggestionText')?.value?.trim() || null;
                if (selectedRating === 0 && !comment && !selectedType) {
                    Swal.showValidationMessage('Preencha pelo menos um campo');
                    return false;
                }
                return { rating: selectedRating || null, comment, type: selectedType };
            },
            didOpen: () => {
                const popup = Swal.getPopup();
                if (window.lucide) lucide.createIcons({ nodes: [popup] });

                // Type chip selection
                popup.querySelectorAll('.lk-sg-chip').forEach(chip => {
                    chip.addEventListener('click', () => {
                        popup.querySelectorAll('.lk-sg-chip').forEach(c => c.classList.remove('active'));
                        chip.classList.add('active');
                        selectedType = chip.dataset.type;
                    });
                });

                // Star rating
                popup.querySelectorAll('.lk-star-rating__star').forEach(star => {
                    star.addEventListener('click', () => {
                        selectedRating = parseInt(star.dataset.star);
                        popup.querySelectorAll('.lk-star-rating__star').forEach((s, idx) => {
                            s.classList.toggle('active', idx < selectedRating);
                            s.style.fill = idx < selectedRating ? 'currentColor' : 'none';
                        });
                    });

                    star.addEventListener('mouseenter', () => {
                        const hoverVal = parseInt(star.dataset.star);
                        popup.querySelectorAll('.lk-star-rating__star').forEach((s, idx) => {
                            s.style.color = idx < hoverVal ? '#f59e0b' : '';
                        });
                    });
                });

                popup.querySelector('.lk-star-rating')?.addEventListener('mouseleave', () => {
                    popup.querySelectorAll('.lk-star-rating__star').forEach((s, idx) => {
                        s.style.color = idx < selectedRating ? '#f59e0b' : '';
                    });
                });
            },
        }).then(async (result) => {
            if (result.isConfirmed && result.value) {
                const ok = await postFeedback({
                    tipo_feedback: result.value.type || 'sugestao',
                    rating: result.value.rating,
                    comentario: result.value.comment,
                });

                if (ok && typeof LK?.toast?.success === 'function') {
                    LK.toast.success('Obrigado pelo feedback!');
                } else if (!ok && typeof LK?.toast?.error === 'function') {
                    LK.toast.error('Limite de feedback atingido hoje.');
                }
            }
        });
    }

    // ========================================================================
    // 4. AI FEEDBACK
    // ========================================================================

    function showAiFeedback() {
        if (!userId()) return;
        if (sessionStorage.getItem('lk_ai_feedback_shown')) return;

        if (typeof Swal === 'undefined') return;

        sessionStorage.setItem('lk_ai_feedback_shown', '1');

        let selectedRating = null;

        Swal.fire({
            title: 'O assistente ajudou?',
            html: `
                <div style="display:flex;gap:10px;justify-content:center;margin:8px 0;">
                    <button type="button" class="lk-ai-feedback__btn" data-rating="2" style="padding:8px 16px;font-size:0.9rem;">👍 Muito</button>
                    <button type="button" class="lk-ai-feedback__btn" data-rating="1" style="padding:8px 16px;font-size:0.9rem;">😐 Mais ou menos</button>
                    <button type="button" class="lk-ai-feedback__btn" data-rating="0" style="padding:8px 16px;font-size:0.9rem;">👎 Nao ajudou</button>
                </div>
                <div id="lkAiCommentBox" style="display:none;margin-top:12px;">
                    <textarea id="lkAiComment" placeholder="O que podemos melhorar no assistente?" maxlength="2000" rows="2"
                        style="width:100%;padding:10px 12px;border:1px solid var(--glass-border,rgba(255,255,255,0.15));border-radius:8px;background:var(--color-bg,#0f172a);color:var(--color-text,#e2e8f0);font-size:0.8rem;font-family:inherit;resize:vertical;"></textarea>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'Enviar',
            confirmButtonColor: '#e67e22',
            showCancelButton: true,
            cancelButtonText: 'Pular',
            preConfirm: () => {
                if (selectedRating === null) {
                    Swal.showValidationMessage('Selecione uma opcao');
                    return false;
                }
                const comment = document.getElementById('lkAiComment')?.value?.trim() || null;
                return { rating: selectedRating, comment };
            },
            didOpen: () => {
                const popup = Swal.getPopup();
                popup.querySelectorAll('.lk-ai-feedback__btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        selectedRating = parseInt(btn.dataset.rating);
                        popup.querySelectorAll('.lk-ai-feedback__btn').forEach(b => {
                            b.style.background = '';
                            b.style.color = '';
                            b.style.borderColor = '';
                        });
                        btn.style.background = 'var(--color-primary, #e67e22)';
                        btn.style.color = '#fff';
                        btn.style.borderColor = 'var(--color-primary, #e67e22)';

                        // Show comment for negative ratings
                        const commentBox = popup.querySelector('#lkAiCommentBox');
                        if (commentBox) {
                            commentBox.style.display = selectedRating <= 1 ? 'block' : 'none';
                        }
                    });
                });
            },
        }).then(async (result) => {
            if (result.isConfirmed && result.value) {
                await postFeedback({
                    tipo_feedback: 'assistente_ia',
                    rating: result.value.rating,
                    comentario: result.value.comment,
                });

                if (typeof LK?.toast?.success === 'function') {
                    LK.toast.success('Obrigado!');
                }
            }
        });
    }

    // ========================================================================
    // UTILITIES
    // ========================================================================

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ========================================================================
    // INIT
    // ========================================================================

    function boot() {
        if (!userId()) return;

        initNps();
        initSuggestionButton();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }

    // ========================================================================
    // PUBLIC API
    // ========================================================================

    window.LKUserFeedback = {
        showMicroFeedback,
        showAiFeedback,
    };

})();
