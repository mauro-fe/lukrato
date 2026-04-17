import { apiFetch, apiGet, apiPost, buildAppUrl, getErrorMessage } from '../shared/api.js';
import {
    resolveAiActionConfirmEndpoint,
    resolveAiActionRejectEndpoint,
    resolveAiConversationMessagesEndpoint,
    resolveAiConversationsEndpoint,
    resolveAiQuotaEndpoint,
} from '../api/endpoints/ai.js';
import { resolveSupportSendEndpoint } from '../api/endpoints/engagement.js';

/**
 * Support button + floating AI panel.
 * Preserves the existing DOM contract from views/admin/partials/botao-suporte.php.
 */
(function () {
    'use strict';

    const billingUrl = buildAppUrl('billing');

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

    const supportMsg = document.getElementById('supportPanelMessage');
    const btnSend = document.getElementById('btnSendSupport');

    const aiMessages = document.getElementById('aiMessages');
    const aiInput = document.getElementById('aiChatInput');
    const aiSendBtn = document.getElementById('aiChatSend');
    const aiQuotaBar = document.getElementById('aiQuotaBar');
    const aiQuotaText = document.getElementById('aiQuotaText');
    const aiEmpty = document.getElementById('aiEmpty');
    const aiAttachBtn = document.getElementById('aiAttachBtn');
    const aiFileInput = document.getElementById('aiFileInput');
    const aiNewConvBtn = document.getElementById('aiNewConversation');
    const aiExhaustedOverlay = document.getElementById('aiExhaustedOverlay');
    const aiInputRow = document.getElementById('aiInputRow');

    if (!toggleBtn || !panel || !fabContainer) {
        return;
    }

    const planTier = toggleBtn.dataset.planTier || 'free';
    let currentConvId = null;
    let aiLoading = false;
    let lastDateLabel = null;

    function refreshIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function showToast(message, type) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
            });
        }
    }

    function closeFab() {
        fabContainer.classList.remove('open');
    }

    function setAiRequestState(isLoading) {
        aiLoading = isLoading;

        if (aiSendBtn) aiSendBtn.disabled = isLoading;
        if (aiAttachBtn) aiAttachBtn.disabled = isLoading;
        if (aiNewConvBtn) aiNewConvBtn.disabled = isLoading;
    }

    function openPanel(tab) {
        closeFab();
        switchTab(tab);
        panel.classList.add('open');
        refreshIcons();

        if (tab === 'ai' && !currentConvId) {
            loadOrCreateConversation();
        }
    }

    function switchTab(tab) {
        const tabs = [tabSupport, tabAI];
        const panels = [panelSupport, panelAI];
        const idx = tab === 'ai' ? 1 : 0;

        tabs.forEach((item, index) => item.classList.toggle('active', index === idx));
        panels.forEach((item, index) => item.classList.toggle('active', index === idx));

        if (tab === 'ai') {
            if (!currentConvId) {
                loadOrCreateConversation();
            }
            if (aiInput) {
                aiInput.focus();
            }
        }
    }

    function clearMessages() {
        if (aiMessages) {
            aiMessages.innerHTML = '';
        }
        lastDateLabel = null;
    }

    function getTimeString(date) {
        const d = date instanceof Date ? date : new Date();
        return d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    function getDateLabel(date) {
        const d = date instanceof Date ? date : new Date();
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const target = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        const diff = Math.round((today - target) / 86400000);

        if (diff === 0) return 'Hoje';
        if (diff === 1) return 'Ontem';
        return d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });
    }

    function maybeInsertDaySeparator(date) {
        if (!aiMessages) return;
        const label = getDateLabel(date);
        if (label === lastDateLabel) return;
        lastDateLabel = label;

        const sep = document.createElement('div');
        sep.className = 'lk-ai-day-separator';
        sep.innerHTML = `<span>${label}</span>`;
        aiMessages.appendChild(sep);
    }

    function formatText(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    function appendAIMessage(role, text, isTyping = false, timestamp = null) {
        if (!aiMessages) {
            return null;
        }

        if (aiEmpty) {
            aiEmpty.style.display = 'none';
        }

        const now = timestamp ? new Date(timestamp) : new Date();
        if (!isTyping) {
            maybeInsertDaySeparator(now);
        }

        const wrap = document.createElement('div');
        wrap.className = `lk-ai-msg ${role}${isTyping ? ' typing' : ''}`;

        const time = getTimeString(now);
        const readReceipt = role === 'user' ? ' <span class="lk-ai-read-receipt">✓✓</span>' : '';
        const formattedText = isTyping ? text : formatText(text);

        if (role === 'assistant') {
            wrap.innerHTML = `
                <div class="avatar"><i data-lucide="bot" style="width:14px;height:14px;"></i></div>
                <div class="lk-ai-msg-stack">
                    <div class="bubble surface-card">
                        <div class="lk-ai-bubble-content">${formattedText}</div>
                    </div>
                    ${!isTyping ? `<div class="lk-ai-msg-time">${time}</div>` : ''}
                </div>`;
        } else {
            wrap.innerHTML = `
                <div class="lk-ai-msg-stack">
                    <div class="bubble bubble--user">
                        <div class="lk-ai-bubble-content">${formattedText}</div>
                    </div>
                    <div class="lk-ai-msg-time">${time}${readReceipt}</div>
                </div>`;
        }

        aiMessages.appendChild(wrap);
        aiMessages.scrollTop = aiMessages.scrollHeight;
        refreshIcons();
        return wrap;
    }

    function updateQuotaDisplay(remaining, exhausted, limit) {
        if (!aiQuotaText) {
            return;
        }

        if (exhausted) {
            aiQuotaText.className = 'quota-danger';
            aiQuotaText.textContent = `0 de ${limit || 0} restantes`;
            if (aiInput) aiInput.disabled = true;
            if (aiSendBtn) aiSendBtn.disabled = true;
            return;
        }

        const used = (limit || 0) - (remaining || 0);
        aiQuotaText.className = remaining <= 1 ? 'quota-warn' : '';
        aiQuotaText.textContent = `${used} de ${limit} mensagens usadas`;
        if (aiInput) aiInput.disabled = false;
        if (aiSendBtn) aiSendBtn.disabled = false;
    }

    function showExhaustedOverlay() {
        if (aiExhaustedOverlay) aiExhaustedOverlay.style.display = 'flex';
        if (aiInputRow) aiInputRow.style.pointerEvents = 'none';
        if (aiInputRow) aiInputRow.style.opacity = '0.4';
    }

    function hideExhaustedOverlay() {
        if (aiExhaustedOverlay) aiExhaustedOverlay.style.display = 'none';
        if (aiInputRow) aiInputRow.style.pointerEvents = '';
        if (aiInputRow) aiInputRow.style.opacity = '';
    }

    function handleAiRequestError(error, timeoutMessage) {
        const status = Number(error?.status || error?.data?.status || 0);
        const directMessage = String(error?.message || '');
        const isTimeout = directMessage.toLowerCase().includes('demorou demais');

        if (status === 429) {
            appendAIMessage(
                'assistant',
                `Você usou suas 5 mensagens de IA gratuitas este mês. <a href="${billingUrl}" style="color:var(--color-primary);font-weight:600;">Faça upgrade para o Pro</a> e tenha IA ilimitada.`
            );
            showExhaustedOverlay();
            updateQuotaDisplay(0, true);
            return;
        }

        if (status === 403) {
            appendAIMessage('assistant', 'Seu plano não inclui acesso ao assistente IA. Faça upgrade para desbloquear.');
            return;
        }

        appendAIMessage(
            'assistant',
            isTimeout ? timeoutMessage : getErrorMessage(error, 'Erro de conexão com o assistente.')
        );
    }

    async function loadQuota() {
        if (!aiQuotaText || planTier === 'ultra') {
            return;
        }

        try {
            const data = await apiGet(resolveAiQuotaEndpoint());

            if (data.success && data.data) {
                const chat = data.data.chat;

                if (!chat || chat.unlimited) {
                    if (aiQuotaBar) aiQuotaBar.style.display = 'none';
                    hideExhaustedOverlay();
                    return;
                }

                const remaining = chat.remaining ?? 0;
                const limit = chat.limit ?? 5;
                updateQuotaDisplay(remaining, remaining <= 0, limit);

                if (remaining <= 0) {
                    showExhaustedOverlay();
                } else {
                    hideExhaustedOverlay();
                }
            }
        } catch {
            // Silent quota failure keeps previous state.
        }
    }

    async function loadMessages(convId) {
        try {
            const data = await apiGet(resolveAiConversationMessagesEndpoint(convId));

            if (data.success && data.data?.length > 0) {
                clearMessages();
                data.data.forEach((message) => {
                    appendAIMessage(message.role, message.content, false, message.created_at);
                });
            }
        } catch (error) {
            console.error('[Lukrato AI] Falha ao carregar mensagens:', error);
        }
    }

    async function createConversation() {
        if (aiLoading) {
            return;
        }

        setAiRequestState(true);
        try {
            const data = await apiPost(resolveAiConversationsEndpoint(), {});
            if (data.success && data.data?.id) {
                currentConvId = data.data.id;
            }
        } catch (error) {
            console.error('[Lukrato AI] Falha ao criar conversa:', error);
        } finally {
            setAiRequestState(false);
        }
    }

    async function loadOrCreateConversation() {
        try {
            const data = await apiFetch(resolveAiConversationsEndpoint(), { method: 'GET' }, { timeout: 15000 });

            if (data.success && data.data?.length > 0) {
                const sorted = data.data
                    .slice()
                    .sort((a, b) => new Date(b.updated_at || 0) - new Date(a.updated_at || 0));

                currentConvId = sorted[0].id;
                await loadMessages(currentConvId);
            } else {
                await createConversation();
            }

            loadQuota();
        } catch (error) {
            console.error('[Lukrato AI] Falha ao carregar conversas:', error);
            appendAIMessage('assistant', getErrorMessage(error, 'Não foi possível conectar ao assistente. Tente novamente.'));
        }
    }

    async function sendSupportMessage() {
        const msg = supportMsg?.value.trim() || '';
        const retorno = document.querySelector('input[name="retorno-panel"]:checked')?.value || 'email';

        if (!msg) {
            showToast('Por favor, escreva uma mensagem.', 'warning');
            supportMsg?.focus();
            return;
        }

        if (msg.length < 10) {
            showToast('A mensagem precisa ter pelo menos 10 caracteres.', 'warning');
            supportMsg?.focus();
            return;
        }

        if (!btnSend) {
            return;
        }

        btnSend.disabled = true;
        btnSend.innerHTML = '<i data-lucide="loader" style="width:14px;height:14px;" class="animate-spin"></i> Enviando...';
        refreshIcons();

        try {
            const data = await apiPost(resolveSupportSendEndpoint(), { message: msg, retorno });

            if (data.success) {
                supportMsg.value = '';
                const canal = retorno === 'whatsapp' ? 'WhatsApp' : 'E-mail';
                showToast(`Mensagem enviada! Retornaremos via ${canal} em até 24h.`, 'success');
            } else {
                showToast(data.message || 'Erro ao enviar mensagem.', 'error');
            }
        } catch (error) {
            showToast(getErrorMessage(error, 'Erro de conexão. Tente novamente.'), 'error');
        } finally {
            btnSend.disabled = false;
            btnSend.innerHTML = '<i data-lucide="send" style="width:14px;height:14px;"></i> Enviar Mensagem';
            refreshIcons();
        }
    }

    async function sendAIMessage() {
        if (!aiInput || aiLoading) {
            return;
        }

        const message = aiInput.value.trim();
        if (!message) {
            return;
        }

        if (!currentConvId) {
            appendAIMessage('assistant', 'Não foi possível iniciar a conversa. Recarregue a página e tente novamente.');
            return;
        }

        setAiRequestState(true);
        aiInput.value = '';
        aiInput.style.height = 'auto';

        appendAIMessage('user', message);
        const typingEl = appendAIMessage('assistant', '<span class="lk-ai-typing"><span></span><span></span><span></span></span>', true);

        try {
            const data = await apiFetch(
                resolveAiConversationMessagesEndpoint(currentConvId),
                { method: 'POST', body: { message } },
                { timeout: 120000 }
            );

            typingEl?.remove();

            const content = data.data?.assistant_message?.content
                || data.data?.content
                || data.data?.response;

            if (data.success && content) {
                appendAIMessage('assistant', content);

                const aiData = data.data?.ai_data;
                if (aiData?.card) {
                    appendRichCard(aiData.card);
                }
                if (aiData?.action === 'confirm' && aiData?.pending_id) {
                    appendConfirmationButtons(aiData);
                }
            } else {
                appendAIMessage('assistant', data.message || 'Sem resposta do assistente.');
            }

            loadQuota();
        } catch (error) {
            typingEl?.remove();
            handleAiRequestError(error, 'A resposta demorou demais. Tente novamente em alguns instantes.');
        } finally {
            setAiRequestState(false);
            aiInput.focus();
        }
    }

    function appendConfirmationButtons(aiData) {
        if (!aiMessages) {
            return;
        }

        const pendingId = aiData.pending_id;
        const accounts = aiData.accounts || [];
        const categories = aiData.categories || [];
        const selectedContaId = aiData.selected_conta_id;
        const selectedCategoriaId = aiData.selected_categoria_id;

        const wrap = document.createElement('div');
        wrap.className = 'lk-ai-confirm-actions';

        const accountSelectHtml = accounts.length > 1
            ? `
                <select class="lk-ai-account-select">
                    ${accounts.map((account) => `
                        <option value="${account.id}"${account.id === selectedContaId ? ' selected' : ''}>${account.nome}</option>
                    `).join('')}
                </select>`
            : '';

        const categorySelectHtml = categories.length > 0
            ? `
                <select class="lk-ai-category-select">
                    <option value="">Sem categoria</option>
                    ${categories.map((category) => `
                        <option value="${category.id}"${category.id === selectedCategoriaId ? ' selected' : ''}>${category.nome}</option>
                    `).join('')}
                </select>`
            : '';

        wrap.innerHTML = `
            ${accountSelectHtml}
            ${categorySelectHtml}
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

        wrap.querySelector('.confirm')?.addEventListener('click', () => handleConfirmAction(pendingId, wrap));
        wrap.querySelector('.reject')?.addEventListener('click', () => handleRejectAction(pendingId, wrap));
    }

    function disableConfirmButtons(wrap) {
        wrap.querySelectorAll('button').forEach((button) => {
            button.disabled = true;
            button.style.opacity = '0.5';
        });
    }

    async function handleConfirmAction(pendingId, btnWrap) {
        disableConfirmButtons(btnWrap);

        const body = {};
        const accountSelect = btnWrap.querySelector('.lk-ai-account-select');
        const categorySelect = btnWrap.querySelector('.lk-ai-category-select');

        if (accountSelect?.value) {
            body.conta_id = Number(accountSelect.value);
        }
        if (categorySelect?.value) {
            body.categoria_id = Number(categorySelect.value);
        }

        try {
            const data = await apiPost(resolveAiActionConfirmEndpoint(pendingId), body);
            btnWrap.remove();
            appendAIMessage('assistant', data.data?.message || (data.success ? '✅ Ação confirmada!' : '⚠️ Erro ao confirmar.'));
        } catch (error) {
            btnWrap.remove();
            appendAIMessage('assistant', getErrorMessage(error, 'Erro de conexão ao confirmar ação.'));
        }
    }

    async function handleRejectAction(pendingId, btnWrap) {
        disableConfirmButtons(btnWrap);

        try {
            const data = await apiPost(resolveAiActionRejectEndpoint(pendingId), {});
            btnWrap.remove();
            appendAIMessage('assistant', data.data?.message || '❌ Ação cancelada.');
        } catch (error) {
            btnWrap.remove();
            appendAIMessage('assistant', getErrorMessage(error, 'Erro de conexão ao cancelar ação.'));
        }
    }

    async function sendAIMessageWithFile(file, label) {
        if (aiLoading || !currentConvId) {
            return;
        }

        setAiRequestState(true);

        const displayLabel = label || file.name;
        const textMsg = aiInput?.value.trim() || '';

        if (aiInput) {
            aiInput.value = '';
            aiInput.style.height = 'auto';
        }

        appendAIMessage('user', textMsg ? `📎 ${displayLabel}\n${textMsg}` : `📎 ${displayLabel}`);
        const typingEl = appendAIMessage('assistant', '<span class="lk-ai-typing"><span></span><span></span><span></span></span>', true);

        try {
            const formData = new FormData();
            formData.append('attachment', file);
            if (textMsg) {
                formData.append('message', textMsg);
            }

            const data = await apiFetch(
                resolveAiConversationMessagesEndpoint(currentConvId),
                { method: 'POST', body: formData },
                { timeout: 120000 }
            );

            typingEl?.remove();

            const content = data.data?.assistant_message?.content
                || data.data?.content
                || data.data?.response;

            if (data.success && content) {
                appendAIMessage('assistant', content);

                const aiData = data.data?.ai_data;
                if (aiData?.card) {
                    appendRichCard(aiData.card);
                }
                if (aiData?.action === 'confirm' && aiData?.pending_id) {
                    appendConfirmationButtons(aiData);
                }
            } else {
                appendAIMessage('assistant', data.message || 'Sem resposta do assistente.');
            }

            loadQuota();
        } catch (error) {
            typingEl?.remove();
            handleAiRequestError(error, 'A resposta demorou demais. Tente novamente.');
        } finally {
            setAiRequestState(false);
            aiInput?.focus();
        }
    }

    toggleBtn.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (panel.classList.contains('open')) {
            panel.classList.remove('open');
            return;
        }

        fabContainer.classList.toggle('open');
        refreshIcons();
    });

    fabItemSupport?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        openPanel('support');
    });

    fabItemAI?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        openPanel('ai');
    });

    closeBtn?.addEventListener('click', () => {
        currentConvId = null;
        clearMessages();
        if (aiEmpty) aiEmpty.style.display = '';
        panel.classList.remove('open');
        closeFab();
    });

    document.addEventListener('click', (event) => {
        if (panel.classList.contains('open') && !panel.contains(event.target) && !fabContainer.contains(event.target)) {
            panel.classList.remove('open');
        }

        if (fabContainer.classList.contains('open') && !fabContainer.contains(event.target)) {
            closeFab();
        }
    });

    tabSupport?.addEventListener('click', () => switchTab('support'));
    tabAI?.addEventListener('click', () => switchTab('ai'));

    if (btnSend && supportMsg) {
        btnSend.addEventListener('click', sendSupportMessage);
    }

    if (aiInput) {
        aiInput.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = `${Math.min(this.scrollHeight, 100)}px`;
        });

        aiInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendAIMessage();
            }
        });
    }

    aiSendBtn?.addEventListener('click', sendAIMessage);

    aiNewConvBtn?.addEventListener('click', async () => {
        if (aiLoading) {
            return;
        }

        currentConvId = null;
        clearMessages();
        if (aiEmpty) aiEmpty.style.display = '';
        await createConversation();
        aiInput?.focus();
        loadQuota();
    });

    if (aiAttachBtn && aiFileInput) {
        aiAttachBtn.addEventListener('click', () => aiFileInput.click());
        aiFileInput.addEventListener('change', async () => {
            const file = aiFileInput.files?.[0];
            aiFileInput.value = '';

            if (!file) {
                return;
            }

            const maxSize = 20 * 1024 * 1024;
            if (file.size > maxSize) {
                showToast('Arquivo excede o limite de 20MB.', 'warning');
                return;
            }

            await sendAIMessageWithFile(file);
        });
    }

    // Suggestion buttons (empty state)
    document.querySelectorAll('.lk-ai-suggestion[data-ai-message]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (aiLoading || !aiInput) return;
            const msg = btn.dataset.aiMessage;
            const mode = btn.dataset.aiMode;

            if (mode === 'send') {
                aiInput.value = msg;
                sendAIMessage();
            } else {
                aiInput.value = msg;
                aiInput.focus();
            }
        });
    });
})();
