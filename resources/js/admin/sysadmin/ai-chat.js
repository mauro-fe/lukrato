import '../../../css/admin/sysadmin/ai-chat.css';
import { apiGet, apiPost, getBaseUrl, getErrorMessage } from '../shared/api.js';

(function () {
    'use strict';

    const messagesEl = document.getElementById('chatMessages');
    const emptyEl = document.getElementById('chatEmpty');
    const inputEl = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSend');
    const statusDot = document.getElementById('statusDot');
    const statusText = document.getElementById('statusText');

    if (!messagesEl || !inputEl || !sendBtn || !statusDot || !statusText) {
        return;
    }

    const BASE = getBaseUrl();
    const MAX_DOM_MESSAGES = 200;
    let isLoading = false;

    async function checkServiceHealth() {
        try {
            const data = await apiGet(`${BASE}api/sysadmin/ai/health-proxy`);
            setStatus(Boolean(data?.success), data?.success ? 'Online' : 'Offline');
        } catch {
            setStatus(false, 'Offline');
        }
    }

    function setStatus(online, label) {
        statusDot.className = `dot ${online ? 'green' : 'red'}`;
        statusText.textContent = label;

        const style = getComputedStyle(document.documentElement);
        statusText.style.color = online
            ? style.getPropertyValue('--color-success').trim()
            : style.getPropertyValue('--color-danger').trim();
    }

    function appendMessage(role, text, isTyping = false) {
        if (emptyEl) {
            emptyEl.remove();
        }

        const wrap = document.createElement('div');
        wrap.className = `chat-msg ${role}${isTyping ? ' typing' : ''}`;

        const icon = role === 'ai' ? 'bot' : 'user';
        wrap.innerHTML = `
            <div class="avatar"><i data-lucide="${icon}" style="width:16px;height:16px;"></i></div>
            <div class="bubble surface-card">${formatText(text)}</div>
        `;

        messagesEl.appendChild(wrap);

        while (messagesEl.children.length > MAX_DOM_MESSAGES) {
            messagesEl.removeChild(messagesEl.firstChild);
        }

        messagesEl.scrollTop = messagesEl.scrollHeight;

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        return wrap;
    }

    function formatText(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    async function sendMessage() {
        const message = inputEl.value.trim();
        if (!message || isLoading) {
            return;
        }

        isLoading = true;
        sendBtn.disabled = true;
        inputEl.value = '';
        inputEl.style.height = 'auto';

        appendMessage('user', message);
        const typingEl = appendMessage('ai', '... ', true);

        try {
            const data = await apiPost(`${BASE}api/sysadmin/ai/chat`, { message });

            typingEl.remove();

            if (data?.success && data?.data?.response) {
                appendMessage('ai', data.data.response);
                setStatus(true, 'Online');
                return;
            }

            appendMessage('ai', data?.message || 'O assistente nao retornou uma resposta.');
        } catch (error) {
            typingEl.remove();

            const timeout = error?.name === 'AbortError' || error?.name === 'TimeoutError';
            appendMessage(
                'ai',
                timeout
                    ? 'A resposta demorou demais. O modelo local pode estar sobrecarregado; tente novamente.'
                    : getErrorMessage(error, 'Nao foi possivel conectar ao assistente de IA.')
            );
            setStatus(false, 'Offline');
        } finally {
            isLoading = false;
            sendBtn.disabled = false;
            inputEl.focus();
        }
    }

    inputEl.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = `${Math.min(this.scrollHeight, 120)}px`;
    });

    inputEl.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });

    sendBtn.addEventListener('click', sendMessage);

    document.querySelectorAll('.quick-btn').forEach((button) => {
        button.addEventListener('click', function () {
            inputEl.value = this.dataset.prompt || '';
            inputEl.dispatchEvent(new Event('input'));
            sendMessage();
        });
    });

    async function loadSideQuota() {
        const statusEl = document.getElementById('sideQuotaStatus');
        const detailsEl = document.getElementById('sideQuotaDetails');
        const msgEl = document.getElementById('sideQuotaMsg');
        if (!statusEl || !detailsEl || !msgEl) {
            return;
        }

        const fmtN = (value) => Number(value || 0).toLocaleString('pt-BR');
        const barCol = (pct) => {
            if (pct > 50) return 'var(--color-success)';
            if (pct > 20) return 'var(--color-warning)';
            return 'var(--color-danger)';
        };

        try {
            const json = await apiGet(`${BASE}api/sysadmin/ai/quota`);
            const quota = json?.data;
            if (!quota) {
                statusEl.textContent = 'Erro';
                return;
            }

            const labels = {
                active: 'Ativo',
                quota_exceeded: 'Quota Excedida',
                invalid_key: 'Chave Invalida',
                error: 'Erro',
            };
            const colors = {
                active: 'var(--color-success)',
                quota_exceeded: 'var(--color-danger)',
                invalid_key: 'var(--color-danger)',
                error: 'var(--color-warning)',
            };

            statusEl.textContent = labels[quota.status] || quota.status;
            statusEl.style.color = colors[quota.status] || '';

            if (quota.status === 'active') {
                detailsEl.style.display = '';

                const reqPct = quota.requests_limit > 0 ? (quota.requests_remaining / quota.requests_limit) * 100 : 0;
                document.getElementById('sideReqBar').style.width = `${reqPct}%`;
                document.getElementById('sideReqBar').style.background = barCol(reqPct);
                document.getElementById('sideReqVal').textContent = `${fmtN(quota.requests_remaining)} / ${fmtN(quota.requests_limit)}`;

                const tokPct = quota.tokens_limit > 0 ? (quota.tokens_remaining / quota.tokens_limit) * 100 : 0;
                document.getElementById('sideTokBar').style.width = `${tokPct}%`;
                document.getElementById('sideTokBar').style.background = barCol(tokPct);
                document.getElementById('sideTokVal').textContent = `${fmtN(quota.tokens_remaining)} / ${fmtN(quota.tokens_limit)}`;
            }

            if (quota.message && quota.status !== 'active') {
                msgEl.textContent = quota.message;
                msgEl.style.display = '';
            }
        } catch {
            statusEl.textContent = 'Erro';
            statusEl.style.color = 'var(--color-danger)';
        }
    }

    async function loadRecentLogs() {
        const container = document.getElementById('recentLogs');
        if (!container) {
            return;
        }

        try {
            const json = await apiGet(`${BASE}api/sysadmin/ai/logs/summary`, { hours: 24 });
            if (!json.success || !json.data?.recentes?.length) {
                container.innerHTML = '<div style="font-size:var(--font-size-xs);color:var(--color-text-muted);text-align:center;padding:.5rem 0;">Nenhuma interacao recente</div>';
                return;
            }

            const typeLabels = {
                chat: 'Chat',
                suggest_category: 'Sugestao',
                analyze_spending: 'Analise',
                categorize: 'Categorizacao',
                analyze: 'Analise (novo)',
                quick_query: 'Consulta Rapida',
                extract_transaction: 'Extracao',
            };

            let html = '';
            json.data.recentes.forEach((log) => {
                const date = new Date(log.created_at);
                const time = date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                const label = typeLabels[log.type] || log.type;
                const statusColor = log.success ? 'var(--color-success)' : 'var(--color-danger)';

                html += `<div class="status-row"><span style="display:flex;align-items:center;gap:.3rem;"><span class="dot" style="background:${statusColor};"></span>${label}</span><span>${log.tokens_total || '-'} tok · ${time}</span></div>`;
            });

            html += `<div class="status-row" style="margin-top:.25rem;"><span>Total (24h)</span><span style="font-weight:600;">${json.data.total} chamadas</span></div>`;
            container.innerHTML = html;
        } catch {
            container.innerHTML = '<div style="font-size:var(--font-size-xs);color:var(--color-text-muted);text-align:center;padding:.5rem 0;">Erro ao carregar</div>';
        }
    }

    checkServiceHealth();
    loadSideQuota();
    loadRecentLogs();
    inputEl.focus();
})();
