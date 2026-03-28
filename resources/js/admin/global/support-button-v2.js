/**
 * Support + Assistente IA
 */
import { apiFetch, apiGet, apiPost, getErrorMessage, logClientError } from '../shared/api.js';
import { toastError, toastSuccess, toastWarning } from '../shared/ui.js';
import { escapeHtml as sharedEscapeHtml } from '../shared/utils.js';

(function () {
    'use strict';

    const dom = {
        fabContainer: document.getElementById('lkFabContainer'),
        toggleBtn: document.getElementById('lkSupportToggle'),
        panel: document.getElementById('lkChatPanel'),
        closeBtn: document.getElementById('lkChatClose'),
        tabSupport: document.getElementById('tabSupport'),
        tabAI: document.getElementById('tabAI'),
        panelSupport: document.getElementById('panelSupport'),
        panelAI: document.getElementById('panelAI'),
        fabItemSupport: document.getElementById('fabItemSupport'),
        fabItemAI: document.getElementById('fabItemAI'),
        supportMsg: document.getElementById('supportPanelMessage'),
        btnSendSupport: document.getElementById('btnSendSupport'),
        aiMessages: document.getElementById('aiMessages'),
        aiInput: document.getElementById('aiChatInput'),
        aiSendBtn: document.getElementById('aiChatSend'),
        aiAttachBtn: document.getElementById('aiAttachBtn'),
        aiFileInput: document.getElementById('aiFileInput'),
        aiMicBtn: document.getElementById('aiMicBtn'),
        aiQuotaBar: document.getElementById('aiQuotaBar'),
        aiQuotaText: document.getElementById('aiQuotaText'),
        aiEmpty: document.getElementById('aiEmpty'),
        aiStatus: document.getElementById('aiStatus'),
        aiComposerHint: document.getElementById('aiComposerHint'),
        aiExhaustedOverlay: document.getElementById('aiExhaustedOverlay'),
        aiInputShell: document.querySelector('.lk-ai-input-shell'),
        aiInputRow: document.getElementById('aiInputRow'),
        aiNewConversation: document.getElementById('aiNewConversation'),
    };

    if (!dom.toggleBtn || !dom.panel || !dom.fabContainer) return;

    const state = {
        planTier: dom.toggleBtn.dataset.planTier || 'free',
        currentConvId: null,
        aiLoading: false,
        aiBootPromise: null,
        hasLoadedConversation: false,
        lastSubmittedText: '',
        mediaRecorder: null,
        audioChunks: [],
        isRecording: false,
        emptyTemplate: dom.aiEmpty?.outerHTML || '',
    };

    const ALLOWED_ATTACHMENT_TYPES = new Set([
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ]);

    const VIDEO_EXTENSIONS = new Set(['mp4', 'mov', 'avi', 'mkv', 'webm']);

    function refreshIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function showToast(message, type) {
        if (type === 'success') return toastSuccess(message);
        if (type === 'warning') return toastWarning(message);
        if (type === 'error') return toastError(message);
        return toastSuccess(message);
    }

    function isVideoFile(file) {
        if (!file) return false;
        if (String(file.type || '').startsWith('video/')) return true;

        const name = String(file.name || '');
        const extension = name.includes('.') ? name.split('.').pop().toLowerCase() : '';
        return VIDEO_EXTENSIONS.has(extension);
    }

    function isAllowedAttachment(file) {
        if (!file) return false;
        if (ALLOWED_ATTACHMENT_TYPES.has(String(file.type || '').toLowerCase())) return true;

        const name = String(file.name || '');
        const extension = name.includes('.') ? name.split('.').pop().toLowerCase() : '';
        return ['jpg', 'jpeg', 'png', 'webp', 'pdf'].includes(extension);
    }

    function closeFab() {
        dom.fabContainer.classList.remove('open');
    }

    function setAIStatus(message, tone = 'neutral') {
        if (!dom.aiStatus) return;
        dom.aiStatus.textContent = message;
        dom.aiStatus.className = `lk-ai-status is-${tone}`;
    }

    function focusAIInput() {
        if (dom.aiInput && !dom.aiInput.disabled) {
            dom.aiInput.focus();
        }
    }

    function autoResizeInput() {
        if (!dom.aiInput) return;
        dom.aiInput.style.height = 'auto';
        dom.aiInput.style.height = `${Math.min(dom.aiInput.scrollHeight, 100)}px`;
    }

    function updateComposerState() {
        const disabled = Boolean(dom.aiInput?.disabled) || state.aiLoading;
        const hasText = Boolean(dom.aiInput?.value.trim());

        if (dom.aiSendBtn) dom.aiSendBtn.disabled = disabled || !hasText;
        if (dom.aiAttachBtn) dom.aiAttachBtn.disabled = disabled;
        if (dom.aiMicBtn) dom.aiMicBtn.disabled = disabled;
    }

    function restoreEmptyState() {
        if (!dom.aiMessages || !state.emptyTemplate) return;
        dom.aiMessages.innerHTML = state.emptyTemplate;
        dom.aiEmpty = document.getElementById('aiEmpty');
        refreshIcons();
    }

    function clearMessages(showEmpty = false) {
        if (!dom.aiMessages) return;

        if (showEmpty) {
            restoreEmptyState();
        } else {
            dom.aiMessages.innerHTML = '';
            dom.aiEmpty = null;
        }
    }

    function setEmptyStateVisible(visible) {
        if (!visible) {
            if (dom.aiEmpty) dom.aiEmpty.style.display = 'none';
            return;
        }

        if (!document.getElementById('aiEmpty')) {
            restoreEmptyState();
        }

        dom.aiEmpty = document.getElementById('aiEmpty');
        if (dom.aiEmpty) dom.aiEmpty.style.display = '';
    }

    function scrollMessagesToBottom() {
        if (dom.aiMessages) {
            dom.aiMessages.scrollTop = dom.aiMessages.scrollHeight;
        }
    }

    function openPanel(tab) {
        closeFab();
        dom.panel.classList.add('open');
        switchTab(tab);
        refreshIcons();
    }

    function closePanel() {
        dom.panel.classList.remove('open');
        closeFab();
        setAIStatus('Conversa pausada. Pode continuar daqui depois.', 'neutral');
    }

    function switchTab(tab) {
        const isAI = tab === 'ai';
        [dom.tabSupport, dom.tabAI].forEach((button, index) => {
            if (!button) return;
            const active = isAI ? index === 1 : index === 0;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        [dom.panelSupport, dom.panelAI].forEach((panel, index) => {
            if (!panel) return;
            panel.classList.toggle('active', isAI ? index === 1 : index === 0);
        });

        if (isAI) {
            void bootAIConversation();
            focusAIInput();
        }
    }

    const escapeHtml = sharedEscapeHtml;

    function escapeAttribute(value) {
        return escapeHtml(value).replace(/`/g, '&#96;');
    }

    function formatInline(text) {
        const escaped = escapeHtml(text);
        const withBold = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        return withBold.replace(/(https?:\/\/[^\s<]+)/g, function (url) {
            const safeUrl = escapeAttribute(url);
            return `<a href="${safeUrl}" target="_blank" rel="noopener noreferrer">${safeUrl}</a>`;
        });
    }

    function formatText(text) {
        const raw = String(text ?? '').trim();
        if (!raw) return '<p></p>';

        return raw.split(/\n{2,}/).filter(Boolean).map((block) => {
            const lines = block.split('\n').filter((line) => line.trim() !== '');
            const isList = lines.length > 1 && lines.every((line) => /^\s*(?:[-*]|\d+[.)])\s+/.test(line));

            if (isList) {
                return `<ul>${lines.map((line) => {
                    const item = line.replace(/^\s*(?:[-*]|\d+[.)])\s+/, '');
                    return `<li>${formatInline(item)}</li>`;
                }).join('')}</ul>`;
            }

            return `<p>${lines.map((line) => formatInline(line)).join('<br>')}</p>`;
        }).join('');
    }

    function normalizeQuickReplies(replies) {
        if (!Array.isArray(replies)) return [];

        return replies
            .map((reply) => ({
                label: String(reply?.label || '').trim(),
                message: String(reply?.message || '').trim(),
                mode: reply?.mode === 'send' ? 'send' : 'fill',
            }))
            .filter((reply) => reply.label && reply.message);
    }

    function renderQuickReplies(replies) {
        if (!Array.isArray(replies) || replies.length === 0) return '';

        return `
            <div class="lk-ai-suggestions">
                ${replies.map((reply) => `
                    <button
                        type="button"
                        class="lk-ai-chip lk-ai-chip--inline"
                        data-ai-message="${escapeAttribute(reply.message)}"
                        data-ai-mode="${escapeAttribute(reply.mode)}"
                    >
                        ${escapeHtml(reply.label)}
                    </button>
                `).join('')}
            </div>
        `;
    }

    const aiTimeFormatter = new Intl.DateTimeFormat('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
    });

    const aiDayFormatter = new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'short',
    });

    function normalizeMessageDate(value) {
        if (!value) return new Date();

        const date = value instanceof Date ? value : new Date(value);
        return Number.isNaN(date.getTime()) ? new Date() : date;
    }

    function getDayKey(date) {
        return [
            date.getFullYear(),
            String(date.getMonth() + 1).padStart(2, '0'),
            String(date.getDate()).padStart(2, '0'),
        ].join('-');
    }

    function isSameCalendarDay(a, b) {
        return getDayKey(a) === getDayKey(b);
    }

    function formatMessageDayLabel(date) {
        const today = new Date();
        if (isSameCalendarDay(date, today)) {
            return 'Hoje';
        }

        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);
        if (isSameCalendarDay(date, yesterday)) {
            return 'Ontem';
        }

        return aiDayFormatter.format(date).replace('.', '');
    }

    function formatMessageTime(value) {
        return aiTimeFormatter.format(normalizeMessageDate(value));
    }

    function getLastRenderedDayKey() {
        if (!dom.aiMessages) return '';
        const separators = dom.aiMessages.querySelectorAll('.lk-ai-day-separator');
        const last = separators[separators.length - 1];
        return last?.dataset.dayKey || '';
    }

    function ensureDayDivider(dateValue) {
        if (!dom.aiMessages) return normalizeMessageDate(dateValue);

        const date = normalizeMessageDate(dateValue);
        const dayKey = getDayKey(date);

        if (getLastRenderedDayKey() === dayKey) {
            return date;
        }

        const divider = document.createElement('div');
        divider.className = 'lk-ai-day-separator';
        divider.dataset.dayKey = dayKey;
        divider.innerHTML = `<span>${formatMessageDayLabel(date)}</span>`;
        dom.aiMessages.appendChild(divider);

        return date;
    }

    function resolveMessageTone(role, text, options = {}) {
        if (options.tone) return options.tone;
        if (role === 'user') return 'user';

        const normalized = String(text || '').toLowerCase();
        if (/(sucesso|registrad[oa]|confirmad[oa]|criad[oa]|salv[oa]|pronta para usar|adicionad[oa])/.test(normalized)) {
            return 'success';
        }

        if (/(nao consegui|não consegui|erro|falha|limite|indisponivel|expirad[oa])/.test(normalized)) {
            return 'danger';
        }

        if (/(deseja|posso|quer|confirme|selecione|escolha)/.test(normalized)) {
            return 'action';
        }

        return 'default';
    }

    function buildContextualCtas(role, text, options = {}) {
        if (Array.isArray(options.ctas) && options.ctas.length > 0) {
            return options.ctas;
        }

        if (role !== 'assistant') {
            return [];
        }

        const normalized = String(text || '').toLowerCase();
        const isSuccess = /(sucesso|registrad[oa]|confirmad[oa]|criad[oa]|salv[oa]|adicionad[oa])/.test(normalized);

        if (!isSuccess) {
            return [];
        }

        if (/(receita|despesa|lancamento|transacao|gasto|entrada)/.test(normalized)) {
            return [{ label: 'Ver lancamentos', href: '/lancamentos' }];
        }

        if (/meta/.test(normalized)) {
            return [{ label: 'Abrir metas', href: '/metas' }];
        }

        if (/categoria/.test(normalized)) {
            return [{ label: 'Abrir categorias', href: '/categorias' }];
        }

        if (/(saldo|dashboard|resumo)/.test(normalized)) {
            return [{ label: 'Ver dashboard', href: '/dashboard' }];
        }

        return [];
    }

    function renderMessageCtas(ctas) {
        if (!Array.isArray(ctas) || ctas.length === 0) {
            return '';
        }

        return `
            <div class="lk-ai-message-actions">
                ${ctas.map((cta) => {
            const label = escapeHtml(cta.label || 'Abrir');
            if (cta.href) {
                return `
                            <a href="${escapeAttribute(cta.href)}" class="lk-ai-message-cta">
                                ${label}
                            </a>
                        `;
            }

            return `
                        <button
                            type="button"
                            class="lk-ai-message-cta"
                            data-ai-message="${escapeAttribute(cta.message || '')}"
                            data-ai-mode="${escapeAttribute(cta.mode || 'fill')}"
                        >
                            ${label}
                        </button>
                    `;
        }).join('')}
            </div>
        `;
    }

    function renderTypingMarkup() {
        return `
            <div class="lk-ai-typing" aria-label="Assistente digitando">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
    }

    function appendAIMessage(role, text, isTyping = false, options = {}) {
        if (!dom.aiMessages) return null;

        setEmptyStateVisible(false);
        const createdAt = ensureDayDivider(options.createdAt || new Date());

        const wrapper = document.createElement('div');
        wrapper.className = `lk-ai-msg ${role}${isTyping ? ' typing' : ''}`;
        wrapper.dataset.role = role;

        const helperText = !isTyping && options.helperText
            ? `<div class="lk-ai-msg-helper">${escapeHtml(options.helperText)}</div>`
            : '';
        const quickReplies = !isTyping ? renderQuickReplies(options.quickReplies) : '';
        const ctas = !isTyping ? renderMessageCtas(buildContextualCtas(role, text, options)) : '';
        const bubbleTone = resolveMessageTone(role, text, options);
        const timestamp = !isTyping
            ? `<div class="lk-ai-msg-time">${formatMessageTime(createdAt)}</div>`
            : '';
        const contentMarkup = isTyping
            ? renderTypingMarkup()
            : `<div class="lk-ai-bubble-content">${formatText(text)}</div>`;

        wrapper.innerHTML = `
            <div class="avatar"><i data-lucide="${role === 'assistant' ? 'bot' : 'user'}" style="width:14px;height:14px;"></i></div>
            <div class="lk-ai-msg-stack">
                <div class="bubble bubble--${bubbleTone}">
                    ${contentMarkup}
                    ${helperText}
                    ${ctas}
                    ${quickReplies}
                </div>
                ${timestamp}
            </div>
        `;

        dom.aiMessages.appendChild(wrapper);
        scrollMessagesToBottom();
        refreshIcons();
        return wrapper;
    }

    function applyAIActionChip(message, mode = 'fill') {
        if (!message || !dom.aiInput) return;

        openPanel('ai');
        dom.aiInput.value = message;
        autoResizeInput();
        updateComposerState();

        if (mode === 'send') {
            void sendAIMessage();
            return;
        }

        setAIStatus('Atalho aplicado. Revise a mensagem ou envie direto.', 'success');
        focusAIInput();
    }

    async function sendSupportMessage() {
        const message = dom.supportMsg?.value.trim() || '';
        const retorno = document.querySelector('input[name="retorno-panel"]:checked')?.value || 'email';

        if (!message) {
            showToast('Por favor, escreva uma mensagem.', 'warning');
            dom.supportMsg?.focus();
            return;
        }

        if (message.length < 10) {
            showToast('A mensagem precisa ter pelo menos 10 caracteres.', 'warning');
            dom.supportMsg?.focus();
            return;
        }

        if (dom.btnSendSupport) {
            dom.btnSendSupport.disabled = true;
            dom.btnSendSupport.innerHTML = '<i data-lucide="loader" style="width:14px;height:14px;" class="animate-spin"></i> Enviando...';
            refreshIcons();
        }

        try {
            const data = await apiPost('api/suporte/enviar', { message, retorno });

            if (data.success) {
                if (dom.supportMsg) dom.supportMsg.value = '';
                const canal = retorno === 'whatsapp' ? 'WhatsApp' : 'E-mail';
                showToast(`Mensagem enviada! Retornaremos via ${canal} em ate 24h.`, 'success');
            } else {
                showToast(data.message || 'Erro ao enviar mensagem.', 'error');
            }
        } catch (error) {
            logClientError('[Lukrato AI] Falha ao enviar suporte', error, 'Falha ao enviar suporte');
            showToast(getErrorMessage(error, 'Erro de conexao. Tente novamente.'), 'error');
        } finally {
            if (dom.btnSendSupport) {
                dom.btnSendSupport.disabled = false;
                dom.btnSendSupport.innerHTML = '<i data-lucide="send" style="width:14px;height:14px;"></i> Enviar Mensagem';
                refreshIcons();
            }
        }
    }

    function buildQuickRepliesFromHint(aiData, sourceMessage) {
        const normalized = String(sourceMessage || '').trim() || 'isso';

        switch (aiData?.action_hint) {
        case 'create_lancamento':
            return [
                { label: 'Registrar agora', message: `registre este gasto: ${normalized}`, mode: 'fill' },
                { label: 'Ver gastos do mes', message: 'quanto gastei este mes?', mode: 'send' },
            ];
        case 'create_lancamento_receita':
            return [
                { label: 'Registrar receita', message: `registre esta receita: ${normalized}`, mode: 'fill' },
                { label: 'Ver saldo atual', message: 'qual e o meu saldo atual?', mode: 'send' },
            ];
        case 'create_meta':
            return [
                { label: 'Criar meta', message: `quero criar uma meta para ${normalized}`, mode: 'fill' },
                { label: 'Planejar valor mensal', message: 'me ajude a planejar quanto guardar por mes', mode: 'send' },
            ];
        case 'create_orcamento':
            return [
                { label: 'Criar orcamento', message: 'quero criar um orcamento para controlar isso', mode: 'fill' },
                { label: 'Analisar categoria', message: 'em que categoria estou gastando mais?', mode: 'send' },
            ];
        default:
            return [];
        }
    }

    function getRetryQuickReplies() {
        if (!state.lastSubmittedText) return [];
        return [
            { label: 'Tentar novamente', message: state.lastSubmittedText, mode: 'send' },
        ];
    }

    function buildMessageOptions(aiData, sourceMessage, helperText = '') {
        const quickReplies = normalizeQuickReplies(aiData?.quick_replies);

        return {
            helperText: helperText || aiData?.suggestion || '',
            quickReplies: quickReplies.length > 0 ? quickReplies : buildQuickRepliesFromHint(aiData, sourceMessage),
        };
    }

    async function bootAIConversation(force = false) {
        if (!dom.aiMessages) return null;

        if (force) {
            state.hasLoadedConversation = false;
        }

        if (state.aiBootPromise) {
            return state.aiBootPromise;
        }

        if (state.hasLoadedConversation) {
            return state.currentConvId;
        }

        state.hasLoadedConversation = true;
        setAIStatus('Carregando historico...', 'loading');

        state.aiBootPromise = (async () => {
            await loadOrCreateConversation();
            return state.currentConvId;
        })();

        try {
            return await state.aiBootPromise;
        } finally {
            state.aiBootPromise = null;
            updateComposerState();
        }
    }

    async function loadOrCreateConversation() {
        try {
            const data = await apiFetch('api/ai/conversations', { method: 'GET' }, { timeout: 15000 });

            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                const [latest] = data.data.sort((a, b) => new Date(b.updated_at || 0) - new Date(a.updated_at || 0));
                state.currentConvId = latest.id;
                await loadMessages(state.currentConvId);
            } else {
                const created = await createConversation();
                clearMessages(true);
                setAIStatus(
                    created
                        ? 'Conversa iniciada. Pode mandar sua mensagem ou usar um atalho.'
                        : 'Nao consegui iniciar a conversa agora.',
                    created ? 'success' : 'error'
                );
            }

            await loadQuota();
        } catch (error) {
            logClientError('[Lukrato AI] Falha ao carregar conversas', error, 'Falha ao carregar conversas');
            clearMessages(true);
            appendAIMessage('assistant', 'Nao foi possivel conectar ao assistente. Tente novamente.', false, {
                quickReplies: [{ label: 'Tentar de novo', message: 'oi', mode: 'send' }],
            });
            setAIStatus('Nao consegui carregar o historico agora.', 'error');
        }
    }

    async function createConversation() {
        try {
            const data = await apiPost('api/ai/conversations', {});

            if (data.success && data.data?.id) {
                state.currentConvId = data.data.id;
                return true;
            }
        } catch (error) {
            logClientError('[Lukrato AI] Falha ao criar conversa', error, 'Falha ao criar conversa');
        }

        return false;
    }

    async function loadMessages(convId) {
        try {
            const data = await apiGet(`api/ai/conversations/${encodeURIComponent(convId)}/messages`);

            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                clearMessages(false);
                data.data.forEach((message) => appendAIMessage(message.role, message.content, false, {
                    createdAt: message.created_at,
                }));
                setAIStatus('Conversa retomada. Pode continuar de onde parou.', 'success');
            } else {
                clearMessages(true);
                setAIStatus('Conversa pronta. Escolha um atalho ou escreva do seu jeito.', 'neutral');
            }
        } catch (error) {
            logClientError('[Lukrato AI] Falha ao carregar mensagens', error, 'Falha ao carregar mensagens');
            setAIStatus('Nao consegui recuperar as mensagens agora.', 'error');
        }
    }

    async function ensureConversation() {
        if (state.currentConvId) return true;

        const created = await createConversation();
        if (!created) {
            setAIStatus('Nao consegui iniciar a conversa agora.', 'error');
        }
        return created;
    }

    function beginAIRequest() {
        state.aiLoading = true;
        setAIStatus('Lukra esta analisando sua mensagem...', 'loading');
        updateComposerState();
    }

    function finishAIRequest() {
        state.aiLoading = false;
        updateComposerState();
        focusAIInput();
    }

    async function handleAssistantResponse(res, sourceMessage, typingEl, helperText = '') {
        if (typingEl) typingEl.remove();

        if (res.status === 429) {
            appendAIMessage('assistant', 'Voce usou suas mensagens de IA deste mes. Faça upgrade para continuar.');
            showExhaustedOverlay();
            updateQuotaDisplay(0, true, 5);
            setAIStatus('Seu limite mensal de IA acabou.', 'error');
            return;
        }

        if (res.status === 403) {
            appendAIMessage('assistant', 'Seu plano atual nao inclui acesso ao assistente IA.');
            setAIStatus('Assistente indisponivel no seu plano atual.', 'error');
            return;
        }

        if (!res.ok) {
            let errorMessage = `Erro ${res.status}`;
            try {
                const errorData = await res.json();
                errorMessage = errorData.message || errorMessage;
            } catch (_) {
                // noop
            }

            appendAIMessage('assistant', errorMessage, false, {
                quickReplies: getRetryQuickReplies(),
            });
            setAIStatus('A resposta falhou. Voce pode tentar novamente.', 'error');
            return;
        }

        const data = await res.json();
        const content = data.data?.assistant_message?.content
            || data.data?.content
            || data.data?.response
            || data.message
            || 'Sem resposta do assistente.';
        const aiData = data.data?.ai_data || {};
        const derivedHelper = data.data?.derived_message
            ? 'Arquivo analisado e convertido em texto para a IA.'
            : helperText;

        appendAIMessage('assistant', content, false, {
            ...buildMessageOptions(aiData, sourceMessage, derivedHelper),
            createdAt: data.data?.assistant_message?.created_at || new Date(),
        });

        if (aiData?.action === 'confirm' && aiData?.pending_id) {
            appendConfirmationButtons(aiData);
        }

        setAIStatus('Resposta pronta. Voce pode continuar por texto ou usar um atalho.', 'success');
        await loadQuota();
    }

    async function sendAIMessage() {
        if (!dom.aiInput || state.aiLoading) return;

        const message = dom.aiInput.value.trim();
        if (!message) return;

        const hasConversation = await ensureConversation();
        if (!hasConversation) {
            appendAIMessage('assistant', 'Nao consegui iniciar a conversa agora. Tente novamente.', false, {
                quickReplies: [{ label: 'Tentar de novo', message: 'oi', mode: 'send' }],
            });
            return;
        }

        state.lastSubmittedText = message;
        beginAIRequest();

        dom.aiInput.value = '';
        autoResizeInput();
        updateComposerState();

        appendAIMessage('user', message, false, { createdAt: new Date() });
        const typingEl = appendAIMessage('assistant', '● ● ●', true);

        try {
            const data = await apiFetch(`api/ai/conversations/${encodeURIComponent(state.currentConvId)}/messages`, {
                method: 'POST',
                body: { message },
            }, { timeout: 120000 });

            const res = { status: 200, ok: true, json: async () => data };
            await handleAssistantResponse(res, message, typingEl);
        } catch (error) {
            if (typingEl) typingEl.remove();
            const timeout = /demorou demais|timeout|excedeu o tempo/i.test(String(error?.message || ''));
            appendAIMessage(
                'assistant',
                timeout
                    ? 'A resposta demorou demais. Tente novamente em alguns instantes.'
                    : getErrorMessage(error, 'Erro de conexao com o assistente.'),
                false,
                { quickReplies: getRetryQuickReplies() }
            );
            setAIStatus(timeout ? 'A resposta excedeu o tempo limite.' : 'Falha de conexao com o assistente.', 'error');
        } finally {
            finishAIRequest();
        }
    }

    async function sendAIMessageWithFile(file, label) {
        if (state.aiLoading) return;

        const hasConversation = await ensureConversation();
        if (!hasConversation) {
            appendAIMessage('assistant', 'Nao consegui iniciar a conversa agora. Tente novamente.');
            return;
        }

        beginAIRequest();

        const displayLabel = label || file.name;
        const textMsg = dom.aiInput?.value.trim() || '';

        if (dom.aiInput) {
            dom.aiInput.value = '';
            autoResizeInput();
        }

        appendAIMessage('user', textMsg ? `Anexo: ${displayLabel}\n${textMsg}` : `Anexo: ${displayLabel}`, false, {
            createdAt: new Date(),
        });
        const typingEl = appendAIMessage('assistant', '● ● ●', true);

        try {
            const formData = new FormData();
            formData.append('attachment', file);
            if (textMsg) formData.append('message', textMsg);

            const data = await apiFetch(`api/ai/conversations/${encodeURIComponent(state.currentConvId)}/messages`, {
                method: 'POST',
                body: formData,
            }, { timeout: 120000 });

            const res = { status: 200, ok: true, json: async () => data };
            await handleAssistantResponse(res, textMsg || `analise o arquivo ${displayLabel}`, typingEl, 'Anexo enviado para analise.');
        } catch (error) {
            if (typingEl) typingEl.remove();
            const timeout = /demorou demais|timeout|excedeu o tempo/i.test(String(error?.message || ''));
            appendAIMessage('assistant', timeout ? 'A resposta demorou demais. Tente novamente.' : getErrorMessage(error, 'Erro de conexao com o assistente.'));
            setAIStatus(timeout ? 'A analise do arquivo excedeu o tempo limite.' : getErrorMessage(error, 'Falha ao enviar o arquivo.'), 'error');
        } finally {
            finishAIRequest();
        }
    }

    function appendConfirmationButtons(aiData) {
        if (!dom.aiMessages) return;

        const pendingId = aiData.pending_id;
        const accounts = Array.isArray(aiData.accounts) ? aiData.accounts : [];
        const categories = Array.isArray(aiData.categories) ? aiData.categories : [];

        const wrap = document.createElement('div');
        wrap.className = 'lk-ai-confirm-actions';

        const accountSelectHtml = accounts.length > 1
            ? `
                <select class="lk-ai-account-select">
                    ${accounts.map((account) => `
                        <option value="${escapeAttribute(account.id)}"${account.id === aiData.selected_conta_id ? ' selected' : ''}>
                            ${escapeHtml(account.nome)}
                        </option>
                    `).join('')}
                </select>
            `
            : '';

        const categorySelectHtml = categories.length > 0
            ? `
                <select class="lk-ai-category-select">
                    <option value="">Sem categoria</option>
                    ${categories.map((category) => `
                        <option value="${escapeAttribute(category.id)}"${category.id === aiData.selected_categoria_id ? ' selected' : ''}>
                            ${escapeHtml(category.nome)}
                        </option>
                    `).join('')}
                </select>
            `
            : '';

        wrap.innerHTML = `
            ${accountSelectHtml}
            ${categorySelectHtml}
            <div class="lk-ai-confirm-btn-group">
                <button class="lk-ai-confirm-btn confirm" type="button" data-id="${escapeAttribute(pendingId)}">
                    <i data-lucide="check" style="width:14px;height:14px;"></i> Confirmar
                </button>
                <button class="lk-ai-confirm-btn reject" type="button" data-id="${escapeAttribute(pendingId)}">
                    <i data-lucide="x" style="width:14px;height:14px;"></i> Cancelar
                </button>
            </div>
        `;

        dom.aiMessages.appendChild(wrap);
        scrollMessagesToBottom();
        refreshIcons();

        wrap.querySelector('.confirm')?.addEventListener('click', () => handleConfirmAction(pendingId, wrap));
        wrap.querySelector('.reject')?.addEventListener('click', () => handleRejectAction(pendingId, wrap));
    }

    function disableConfirmButtons(wrapper) {
        wrapper.querySelectorAll('button').forEach((button) => {
            button.disabled = true;
            button.style.opacity = '0.5';
        });
    }

    async function handleConfirmAction(pendingId, wrapper) {
        disableConfirmButtons(wrapper);
        const payload = {};

        const accountSelect = wrapper.querySelector('.lk-ai-account-select');
        if (accountSelect?.value) payload.conta_id = Number(accountSelect.value);

        const categorySelect = wrapper.querySelector('.lk-ai-category-select');
        if (categorySelect?.value) payload.categoria_id = Number(categorySelect.value);

        try {
            const data = await apiFetch(`api/ai/actions/${encodeURIComponent(pendingId)}/confirm`, {
                method: 'POST',
                body: payload,
            });

            wrapper.remove();
            appendAIMessage('assistant', data.data?.message || (data.success ? 'Acao confirmada com sucesso.' : 'Erro ao confirmar a acao.'), false, {
                tone: data.success ? 'success' : 'danger',
                createdAt: new Date(),
            });
            setAIStatus(data.success ? 'Acao confirmada.' : 'Falha ao confirmar a acao.', data.success ? 'success' : 'error');
        } catch (error) {
            logClientError('[Lukrato AI] Erro ao confirmar ação', error, 'Falha ao confirmar ação');
            wrapper.remove();
            appendAIMessage('assistant', getErrorMessage(error, 'Erro de conexao ao confirmar a acao.'), false, {
                tone: 'danger',
                createdAt: new Date(),
            });
            setAIStatus(getErrorMessage(error, 'Falha ao confirmar a acao.'), 'error');
        }
    }

    async function handleRejectAction(pendingId, wrapper) {
        disableConfirmButtons(wrapper);

        try {
            const data = await apiPost(`api/ai/actions/${encodeURIComponent(pendingId)}/reject`, {});

            wrapper.remove();
            appendAIMessage('assistant', data.data?.message || 'Acao cancelada.', false, {
                createdAt: new Date(),
            });
            setAIStatus('Acao cancelada.', 'success');
        } catch (error) {
            logClientError('[Lukrato AI] Erro ao cancelar ação', error, 'Falha ao cancelar ação');
            wrapper.remove();
            appendAIMessage('assistant', getErrorMessage(error, 'Erro de conexao ao cancelar a acao.'), false, {
                tone: 'danger',
                createdAt: new Date(),
            });
            setAIStatus(getErrorMessage(error, 'Falha ao cancelar a acao.'), 'error');
        }
    }

    async function loadQuota() {
        if (!dom.aiQuotaText || state.planTier === 'ultra') return;

        try {
            const data = await apiGet('api/ai/quota');
            const chat = data.data?.chat;

            if (!data.success || !chat) return;

            if (chat.unlimited) {
                if (dom.aiQuotaBar) dom.aiQuotaBar.style.display = 'none';
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
        } catch (error) {
            logClientError('[Lukrato AI] Falha ao carregar quota', error, 'Falha ao carregar quota');
        }
    }

    function updateQuotaDisplay(remaining, exhausted, limit) {
        if (!dom.aiQuotaText) return;

        if (exhausted) {
            dom.aiQuotaText.className = 'quota-danger';
            dom.aiQuotaText.textContent = `Limite atingido (${limit || 0} msgs/mes)`;
            if (dom.aiInput) dom.aiInput.disabled = true;
        } else {
            const pct = limit ? (remaining / limit) * 100 : 100;
            dom.aiQuotaText.className = pct <= 20 ? 'quota-warn' : '';
            dom.aiQuotaText.textContent = `${remaining} de ${limit} mensagens restantes`;
            if (dom.aiInput) dom.aiInput.disabled = false;
        }

        updateComposerState();
    }

    function showExhaustedOverlay() {
        if (dom.aiExhaustedOverlay) dom.aiExhaustedOverlay.style.display = 'flex';
        if (dom.aiInputShell) dom.aiInputShell.style.display = 'none';
        else if (dom.aiInputRow) dom.aiInputRow.style.display = 'none';
        if (dom.aiComposerHint) dom.aiComposerHint.style.display = 'none';
        updateComposerState();
    }

    function hideExhaustedOverlay() {
        if (dom.aiExhaustedOverlay) dom.aiExhaustedOverlay.style.display = 'none';
        if (dom.aiInputShell) dom.aiInputShell.style.display = '';
        else if (dom.aiInputRow) dom.aiInputRow.style.display = '';
        if (dom.aiComposerHint) dom.aiComposerHint.style.display = '';
        updateComposerState();
    }

    function getSupportedAudioMime() {
        const candidates = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/ogg;codecs=opus',
            'audio/ogg',
        ];

        if (typeof MediaRecorder === 'undefined') {
            return null;
        }

        for (const mime of candidates) {
            if (MediaRecorder.isTypeSupported(mime)) {
                return mime;
            }
        }

        return 'audio/webm';
    }

    async function startRecording() {
        if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
            showToast('Gravacao de audio nao e suportada neste navegador.', 'warning');
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const mimeType = getSupportedAudioMime();

            state.mediaRecorder = mimeType
                ? new MediaRecorder(stream, { mimeType })
                : new MediaRecorder(stream);
            state.audioChunks = [];

            state.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    state.audioChunks.push(event.data);
                }
            };

            state.mediaRecorder.onstop = async () => {
                stream.getTracks().forEach((track) => track.stop());
                const currentMime = state.mediaRecorder?.mimeType || mimeType || 'audio/webm';
                const extension = currentMime.includes('ogg') ? 'ogg' : 'webm';
                const blob = new Blob(state.audioChunks, { type: currentMime });
                const file = new File([blob], `audio.${extension}`, { type: currentMime });
                await sendAIMessageWithFile(file, 'Audio');
            };

            state.mediaRecorder.start();
            state.isRecording = true;

            if (dom.aiMicBtn) {
                dom.aiMicBtn.classList.add('recording');
                dom.aiMicBtn.title = 'Parar gravacao';
            }

            setAIStatus('Gravando audio... toque de novo para parar.', 'loading');
        } catch (error) {
            logClientError('[Lukrato AI] Erro ao acessar microfone', error, 'Falha ao acessar microfone');
            showToast('Nao foi possivel acessar o microfone. Verifique as permissoes.', 'error');
            setAIStatus('Microfone indisponivel no momento.', 'error');
        }
    }

    function stopRecording() {
        if (state.mediaRecorder && state.mediaRecorder.state !== 'inactive') {
            state.mediaRecorder.stop();
        }

        state.isRecording = false;

        if (dom.aiMicBtn) {
            dom.aiMicBtn.classList.remove('recording');
            dom.aiMicBtn.title = 'Gravar audio';
        }

        setAIStatus('Enviando audio para analise...', 'loading');
    }

    dom.toggleBtn.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        if (dom.panel.classList.contains('open')) {
            closePanel();
            return;
        }

        dom.fabContainer.classList.toggle('open');
        refreshIcons();
    });

    dom.fabItemSupport?.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        openPanel('support');
    });

    dom.fabItemAI?.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        openPanel('ai');
    });

    dom.closeBtn?.addEventListener('click', closePanel);
    dom.tabSupport?.addEventListener('click', () => switchTab('support'));
    dom.tabAI?.addEventListener('click', () => switchTab('ai'));
    dom.btnSendSupport?.addEventListener('click', sendSupportMessage);

    dom.aiNewConversation?.addEventListener('click', async function () {
        if (state.aiLoading) return;

        state.currentConvId = null;
        state.lastSubmittedText = '';
        state.hasLoadedConversation = true;
        clearMessages(true);
        setAIStatus('Nova conversa pronta. Escolha um atalho ou escreva do seu jeito.', 'success');
        await createConversation();
        focusAIInput();
        await loadQuota();
        updateComposerState();
    });

    document.addEventListener('click', function (event) {
        if (dom.panel.classList.contains('open')
            && !dom.panel.contains(event.target)
            && !dom.fabContainer.contains(event.target)) {
            closePanel();
        }

        if (dom.fabContainer.classList.contains('open') && !dom.fabContainer.contains(event.target)) {
            closeFab();
        }
    });

    dom.panelAI?.addEventListener('click', function (event) {
        const chip = event.target.closest('[data-ai-message]');
        if (!chip) return;

        event.preventDefault();
        applyAIActionChip(chip.dataset.aiMessage || '', chip.dataset.aiMode || 'fill');
    });

    if (dom.aiMessages) {
        setAIStatus('Feche o painel sem medo: a conversa continua daqui.', 'neutral');

        dom.aiInput?.addEventListener('input', function () {
            autoResizeInput();
            updateComposerState();
        });

        dom.aiInput?.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                void sendAIMessage();
            }
        });

        dom.aiSendBtn?.addEventListener('click', sendAIMessage);

        dom.aiAttachBtn?.addEventListener('click', () => dom.aiFileInput?.click());
        dom.aiFileInput?.addEventListener('change', async function () {
            const file = dom.aiFileInput?.files?.[0];
            if (dom.aiFileInput) dom.aiFileInput.value = '';
            if (!file) return;

            if (isVideoFile(file)) {
                showToast('Video nao e suportado. Envie imagem, PDF ou audio.', 'warning');
                return;
            }

            if (!isAllowedAttachment(file)) {
                showToast('Tipo de arquivo nao suportado. Envie imagem ou PDF.', 'warning');
                return;
            }

            if (file.size > 20 * 1024 * 1024) {
                showToast('Arquivo excede o limite de 20MB.', 'warning');
                return;
            }

            await sendAIMessageWithFile(file);
        });

        dom.aiMicBtn?.addEventListener('click', async function () {
            if (state.isRecording) {
                stopRecording();
            } else {
                await startRecording();
            }
        });

        updateComposerState();
    }
})();
