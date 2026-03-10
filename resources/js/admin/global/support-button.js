/**
 * ============================================================================
 * LUKRATO — Support Button + AI Chat Panel
 * ============================================================================
 * Painel flutuante com abas: Suporte (formulário) e Assistente IA (chat).
 * HTML: views/admin/partials/botao-suporte.php
 * CSS:  public/assets/css/modules/support-button.css
 * ============================================================================
 */
(function () {
    'use strict';

    // ── Config ─────────────────────────────────────────────────
    const BASE = (window.BASE_URL || document.querySelector('meta[name="base-url"]')?.content || '/').replace(/\/?$/, '/');
    const CSRF = () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (!token) console.warn('[Lukrato AI] CSRF token não encontrado. Requisições podem falhar.');
        return token;
    };
    const HEADERS = () => ({
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': CSRF(),
    });

    // ── DOM Elements ───────────────────────────────────────────
    const fabContainer = document.getElementById('lkFabContainer');
    const toggleBtn = document.getElementById('lkSupportToggle');
    const panel = document.getElementById('lkChatPanel');
    const closeBtn = document.getElementById('lkChatClose');
    const tabSupport = document.getElementById('tabSupport');
    const tabAI = document.getElementById('tabAI');
    const panelSupport = document.getElementById('panelSupport');
    const panelAI = document.getElementById('panelAI');
    const fabItemSupport = document.getElementById('fabItemSupport');
    const fabItemAI = document.getElementById('fabItemAI');

    // Support form
    const supportMsg = document.getElementById('supportPanelMessage');
    const btnSend = document.getElementById('btnSendSupport');

    // AI chat (may not exist for free tier)
    const aiMessages = document.getElementById('aiMessages');
    const aiInput = document.getElementById('aiChatInput');
    const aiSendBtn = document.getElementById('aiChatSend');
    const aiQuotaBar = document.getElementById('aiQuotaBar');
    const aiQuotaText = document.getElementById('aiQuotaText');
    const aiEmpty = document.getElementById('aiEmpty');

    if (!toggleBtn || !panel || !fabContainer) return;

    const planTier = toggleBtn.dataset.planTier || 'free';
    let currentConvId = null;
    let aiLoading = false;
    const isTouchDevice = () => 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    // ── Speed Dial helpers ─────────────────────────────────────
    function closeFab() {
        fabContainer.classList.remove('open');
    }

    function openPanel(tab) {
        closeFab();
        switchTab(tab);
        panel.classList.add('open');
        refreshIcons();
        if (tab === 'ai' && planTier !== 'free' && !currentConvId) {
            loadOrCreateConversation();
        }
    }

    // ── FAB main button (toggle speed dial on mobile / click) ──
    toggleBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (panel.classList.contains('open')) {
            panel.classList.remove('open');
            return;
        }
        fabContainer.classList.toggle('open');
        refreshIcons();
    });

    // ── Mini-button: Suporte ───────────────────────────────────
    if (fabItemSupport) {
        fabItemSupport.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openPanel('support');
        });
    }

    // ── Mini-button: Assistente IA ─────────────────────────────
    if (fabItemAI) {
        fabItemAI.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openPanel('ai');
        });
    }

    closeBtn.addEventListener('click', function () {
        panel.classList.remove('open');
        closeFab();
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
        // Close panel if open and click outside
        if (panel.classList.contains('open') &&
            !panel.contains(e.target) &&
            !fabContainer.contains(e.target)) {
            panel.classList.remove('open');
        }
        // Close speed dial if open and click outside
        if (fabContainer.classList.contains('open') &&
            !fabContainer.contains(e.target)) {
            closeFab();
        }
    });

    // ── Tab Switching ──────────────────────────────────────────
    function switchTab(tab) {
        const tabs = [tabSupport, tabAI];
        const panels = [panelSupport, panelAI];
        const idx = tab === 'ai' ? 1 : 0;

        tabs.forEach((t, i) => {
            t.classList.toggle('active', i === idx);
        });
        panels.forEach((p, i) => {
            p.classList.toggle('active', i === idx);
        });

        if (tab === 'ai' && planTier !== 'free') {
            if (!currentConvId) loadOrCreateConversation();
            if (aiInput) aiInput.focus();
        }
    }

    tabSupport.addEventListener('click', () => switchTab('support'));
    tabAI.addEventListener('click', () => switchTab('ai'));

    // ════════════════════════════════════════════════════════════
    //  SUPPORT FORM
    // ════════════════════════════════════════════════════════════
    if (btnSend && supportMsg) {
        btnSend.addEventListener('click', sendSupportMessage);
    }

    async function sendSupportMessage() {
        const msg = supportMsg.value.trim();
        const retorno = document.querySelector('input[name="retorno-panel"]:checked')?.value || 'email';

        if (!msg) {
            showToast('Por favor, escreva uma mensagem.', 'warning');
            supportMsg.focus();
            return;
        }
        if (msg.length < 10) {
            showToast('A mensagem precisa ter pelo menos 10 caracteres.', 'warning');
            supportMsg.focus();
            return;
        }

        btnSend.disabled = true;
        btnSend.innerHTML = '<i data-lucide="loader" style="width:14px;height:14px;" class="animate-spin"></i> Enviando...';
        refreshIcons();

        try {
            const res = await fetch(`${BASE}api/suporte/enviar`, {
                method: 'POST',
                headers: HEADERS(),
                body: JSON.stringify({ message: msg, retorno }),
            });
            const data = await res.json();

            if (data.success) {
                supportMsg.value = '';
                const canal = retorno === 'whatsapp' ? 'WhatsApp' : 'E-mail';
                showToast(`Mensagem enviada! Retornaremos via ${canal} em até 24h.`, 'success');
            } else {
                showToast(data.message || 'Erro ao enviar mensagem.', 'error');
            }
        } catch {
            showToast('Erro de conexão. Tente novamente.', 'error');
        } finally {
            btnSend.disabled = false;
            btnSend.innerHTML = '<i data-lucide="send" style="width:14px;height:14px;"></i> Enviar Mensagem';
            refreshIcons();
        }
    }

    // ════════════════════════════════════════════════════════════
    //  AI CHAT
    // ════════════════════════════════════════════════════════════
    if (planTier === 'free' || !aiMessages) {
        // Free tier — no AI chat logic needed
    } else {
        // Wire up AI events
        if (aiInput) {
            aiInput.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            });

            aiInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendAIMessage();
                }
            });
        }

        if (aiSendBtn) {
            aiSendBtn.addEventListener('click', sendAIMessage);
        }
    }

    // ── Load or create a conversation ──────────────────────────
    async function loadOrCreateConversation() {
        try {
            const res = await fetch(`${BASE}api/ai/conversations`, { headers: HEADERS() });
            const data = await res.json();

            if (data.success && data.data?.length > 0) {
                // Use the most recent conversation
                const sorted = data.data.sort((a, b) => new Date(b.updated_at || 0) - new Date(a.updated_at || 0));
                currentConvId = sorted[0].id;
                await loadMessages(currentConvId);
            } else {
                await createConversation();
            }
            loadQuota();
        } catch (e) {
            console.error('[Lukrato AI] Falha ao carregar conversas:', e);
            appendAIMessage('assistant', 'Não foi possível conectar ao assistente. Tente novamente.');
        }
    }

    async function createConversation() {
        try {
            const res = await fetch(`${BASE}api/ai/conversations`, {
                method: 'POST',
                headers: HEADERS(),
            });
            const data = await res.json();
            if (data.success && data.data?.id) {
                currentConvId = data.data.id;
            }
        } catch (e) {
            console.error('[Lukrato AI] Falha ao criar conversa:', e);
        }
    }

    async function loadMessages(convId) {
        try {
            const res = await fetch(`${BASE}api/ai/conversations/${encodeURIComponent(convId)}/messages`, {
                headers: HEADERS(),
            });
            const data = await res.json();

            if (data.success && data.data?.length > 0) {
                clearMessages();
                data.data.forEach(m => {
                    appendAIMessage(m.role, m.content);
                });
            }
        } catch (e) {
            console.error('[Lukrato AI] Falha ao carregar mensagens:', e);
        }
    }

    // ── Send AI message ────────────────────────────────────────
    async function sendAIMessage() {
        if (!aiInput || aiLoading) return;
        const message = aiInput.value.trim();
        if (!message || !currentConvId) return;

        aiLoading = true;
        if (aiSendBtn) aiSendBtn.disabled = true;
        aiInput.value = '';
        aiInput.style.height = 'auto';

        appendAIMessage('user', message);
        const typingEl = appendAIMessage('assistant', '● ● ●', true);

        try {
            const res = await fetch(`${BASE}api/ai/conversations/${encodeURIComponent(currentConvId)}/messages`, {
                method: 'POST',
                headers: HEADERS(),
                body: JSON.stringify({ message }),
                signal: AbortSignal.timeout(120000),
            });

            if (typingEl) typingEl.remove();

            if (res.status === 429) {
                appendAIMessage('assistant', 'Você atingiu o limite de mensagens do mês. Faça upgrade para continuar usando o assistente.');
                updateQuotaDisplay(0, true);
                return;
            }

            if (res.status === 403) {
                appendAIMessage('assistant', 'Seu plano não inclui acesso ao assistente IA. Faça upgrade para desbloquear.');
                return;
            }

            if (!res.ok) {
                let errMsg = `Erro ${res.status}`;
                try { const ed = await res.json(); errMsg = ed.message || errMsg; } catch { }
                appendAIMessage('assistant', errMsg);
                return;
            }

            const data = await res.json();

            const content = data.data?.assistant_message?.content
                || data.data?.content
                || data.data?.response;

            if (data.success && content) {
                appendAIMessage('assistant', content);

                // Verificar se há ação de confirmação pendente
                const aiData = data.data?.ai_data;
                if (aiData?.action === 'confirm' && aiData?.pending_id) {
                    appendConfirmationButtons(aiData.pending_id, aiData.accounts || []);
                }
            } else {
                appendAIMessage('assistant', data.message || 'Sem resposta do assistente.');
            }

            // Refresh quota after message
            loadQuota();
        } catch (err) {
            if (typingEl) typingEl.remove();
            const isTimeout = err?.name === 'TimeoutError';
            appendAIMessage('assistant', isTimeout
                ? 'A resposta demorou demais. Tente novamente em alguns instantes.'
                : 'Erro de conexão com o assistente.');
        } finally {
            aiLoading = false;
            if (aiSendBtn) aiSendBtn.disabled = false;
            if (aiInput) aiInput.focus();
        }
    }

    // ── Render AI message ──────────────────────────────────────
    function appendAIMessage(role, text, isTyping = false) {
        if (aiEmpty) aiEmpty.style.display = 'none';

        const wrap = document.createElement('div');
        wrap.className = `lk-ai-msg ${role}${isTyping ? ' typing' : ''}`;

        const icon = role === 'assistant' ? 'bot' : 'user';
        wrap.innerHTML = `
            <div class="avatar"><i data-lucide="${icon}" style="width:14px;height:14px;"></i></div>
            <div class="bubble">${formatText(text)}</div>`;

        aiMessages.appendChild(wrap);
        aiMessages.scrollTop = aiMessages.scrollHeight;
        refreshIcons();
        return wrap;
    }

    function clearMessages() {
        if (!aiMessages) return;
        aiMessages.innerHTML = '';
    }

    function formatText(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    // ── Confirmation Buttons ───────────────────────────────────
    function appendConfirmationButtons(pendingId, accounts) {
        const wrap = document.createElement('div');
        wrap.className = 'lk-ai-confirm-actions';

        let selectHtml = '';
        if (accounts.length > 1) {
            const opts = accounts.map(a =>
                `<option value="${a.id}">${a.nome}</option>`
            ).join('');
            selectHtml = `
                <select class="lk-ai-account-select">
                    <option value="" disabled selected>Selecione a conta</option>
                    ${opts}
                </select>`;
        }

        wrap.innerHTML = `
            ${selectHtml}
            <div class="lk-ai-confirm-btn-group">
                <button class="lk-ai-confirm-btn confirm" data-id="${pendingId}">
                    <i data-lucide="check" style="width:14px;height:14px;"></i> Confirmar
                </button>
                <button class="lk-ai-confirm-btn reject" data-id="${pendingId}">
                    <i data-lucide="x" style="width:14px;height:14px;"></i> Cancelar
                </button>
            </div>`;

        aiMessages.appendChild(wrap);
        aiMessages.scrollTop = aiMessages.scrollHeight;
        refreshIcons();

        wrap.querySelector('.confirm').addEventListener('click', () => handleConfirmAction(pendingId, wrap));
        wrap.querySelector('.reject').addEventListener('click', () => handleRejectAction(pendingId, wrap));
    }

    async function handleConfirmAction(pendingId, btnWrap) {
        const select = btnWrap.querySelector('.lk-ai-account-select');
        if (select && !select.value) {
            select.classList.add('lk-ai-select-error');
            select.focus();
            return;
        }

        disableConfirmButtons(btnWrap);
        const body = {};
        if (select?.value) body.conta_id = Number(select.value);

        try {
            const res = await fetch(`${BASE}api/ai/actions/${encodeURIComponent(pendingId)}/confirm`, {
                method: 'POST',
                headers: HEADERS(),
                body: JSON.stringify(body),
            });
            const data = await res.json();
            btnWrap.remove();
            appendAIMessage('assistant', data.data?.message || (data.success ? '✅ Ação confirmada!' : '⚠️ Erro ao confirmar.'));
        } catch {
            btnWrap.remove();
            appendAIMessage('assistant', 'Erro de conexão ao confirmar ação.');
        }
    }

    async function handleRejectAction(pendingId, btnWrap) {
        disableConfirmButtons(btnWrap);
        try {
            const res = await fetch(`${BASE}api/ai/actions/${encodeURIComponent(pendingId)}/reject`, {
                method: 'POST',
                headers: HEADERS(),
            });
            const data = await res.json();
            btnWrap.remove();
            appendAIMessage('assistant', data.data?.message || '❌ Ação cancelada.');
        } catch {
            btnWrap.remove();
            appendAIMessage('assistant', 'Erro de conexão ao cancelar ação.');
        }
    }

    function disableConfirmButtons(wrap) {
        wrap.querySelectorAll('button').forEach(btn => { btn.disabled = true; btn.style.opacity = '0.5'; });
    }

    // ── Quota ──────────────────────────────────────────────────
    async function loadQuota() {
        if (!aiQuotaText || planTier === 'ultra') return;

        try {
            const res = await fetch(`${BASE}api/ai/quota`, { headers: HEADERS() });
            const data = await res.json();

            if (data.success && data.data) {
                const q = data.data;
                if (q.unlimited) {
                    if (aiQuotaBar) aiQuotaBar.style.display = 'none';
                } else {
                    const remaining = q.remaining ?? 0;
                    const limit = q.limit ?? 100;
                    updateQuotaDisplay(remaining, remaining <= 0, limit);
                }
            }
        } catch {
            // Silent quota load failure
        }
    }

    function updateQuotaDisplay(remaining, exhausted, limit) {
        if (!aiQuotaText) return;
        if (exhausted) {
            aiQuotaText.className = 'quota-danger';
            aiQuotaText.textContent = `Limite atingido (${limit || 0} msgs/mês)`;
            if (aiInput) aiInput.disabled = true;
            if (aiSendBtn) aiSendBtn.disabled = true;
        } else {
            const pct = limit ? (remaining / limit * 100) : 100;
            aiQuotaText.className = pct <= 20 ? 'quota-warn' : '';
            aiQuotaText.textContent = `${remaining} de ${limit} mensagens restantes`;
        }
    }

    // ── Helpers ────────────────────────────────────────────────
    function refreshIcons() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function showToast(msg, type) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: msg,
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
            });
        }
    }
})();
