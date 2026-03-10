(function () {
    'use strict';

    // ── elementos ──────────────────────────────────────────────
    const messagesEl = document.getElementById('chatMessages');
    const emptyEl = document.getElementById('chatEmpty');
    const inputEl = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSend');
    const statusDot = document.getElementById('statusDot');
    const statusText = document.getElementById('statusText');

    const BASE = (window.BASE_URL || document.querySelector('meta[name="base-url"]')?.content || '/').replace(/\/?$/, '/');
    let isLoading = false;

    // ── status do serviço IA ────────────────────────────────────
    async function checkServiceHealth() {
        try {
            const res = await fetch(`${BASE}api/sysadmin/ai/health-proxy`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: AbortSignal.timeout(8000),
            });
            const data = await res.json();
            if (data.success) {
                setStatus(true, 'Online');
            } else {
                setStatus(false, 'Offline');
            }
        } catch {
            setStatus(false, 'Offline');
        }
    }

    function setStatus(online, label) {
        statusDot.className = `dot ${online ? 'green' : 'red'}`;
        statusText.textContent = label;
        const style = getComputedStyle(document.documentElement);
        statusText.style.color = online ?
            style.getPropertyValue('--color-success').trim() :
            style.getPropertyValue('--color-danger').trim();
    }

    const MAX_DOM_MESSAGES = 200;

    // ── renderizar mensagem ─────────────────────────────────────
    function appendMessage(role, text, isTyping = false) {
        if (emptyEl) emptyEl.remove();

        const wrap = document.createElement('div');
        wrap.className = `chat-msg ${role}${isTyping ? ' typing' : ''}`;

        const icon = role === 'ai' ? 'bot' : 'user';
        wrap.innerHTML = `
            <div class="avatar"><i data-lucide="${icon}" style="width:16px;height:16px;"></i></div>
            <div class="bubble">${formatText(text)}</div>`;

        messagesEl.appendChild(wrap);

        // Evitar memory leak: limitar mensagens no DOM
        while (messagesEl.children.length > MAX_DOM_MESSAGES) {
            messagesEl.removeChild(messagesEl.firstChild);
        }

        messagesEl.scrollTop = messagesEl.scrollHeight;

        if (typeof lucide !== 'undefined') lucide.createIcons();
        return wrap;
    }

    function formatText(text) {
        return text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    // ── enviar mensagem ─────────────────────────────────────────
    async function sendMessage() {
        const message = inputEl.value.trim();
        if (!message || isLoading) return;

        isLoading = true;
        sendBtn.disabled = true;
        inputEl.value = '';
        inputEl.style.height = 'auto';

        appendMessage('user', message);

        const typingEl = appendMessage('ai', '● ● ●', true);

        try {
            const res = await fetch(`${BASE}api/sysadmin/ai/chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    message
                }),
                signal: AbortSignal.timeout(120000),
            });

            typingEl.remove();

            if (!res.ok) {
                let errMsg = `Erro HTTP ${res.status}`;
                try {
                    const errData = await res.json();
                    errMsg = errData.message || errData.errors?.csrf_token || errMsg;
                } catch { }
                appendMessage('ai', errMsg);
                return;
            }

            const data = await res.json();

            if (data.success && data.data?.response) {
                appendMessage('ai', data.data.response);
                setStatus(true, 'Online');
            } else {
                appendMessage('ai', data.message || 'O assistente não retornou uma resposta.');
            }
        } catch (err) {
            typingEl.remove();
            const isTimeout = err?.name === 'TimeoutError';
            appendMessage('ai', isTimeout ?
                'A resposta demorou demais. O modelo local pode estar sobrecarregado — tente novamente.' :
                'Não foi possível conectar ao assistente de IA. Verifique a configuração da OPENAI_API_KEY no .env.'
            );
            setStatus(false, 'Offline');
        } finally {
            isLoading = false;
            sendBtn.disabled = false;
            inputEl.focus();
        }
    }

    // ── auto-resize textarea ────────────────────────────────────
    inputEl.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // ── Enter para enviar ───────────────────────────────────────
    inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    sendBtn.addEventListener('click', sendMessage);

    // ── perguntas rápidas ───────────────────────────────────────
    document.querySelectorAll('.quick-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            inputEl.value = this.dataset.prompt;
            inputEl.dispatchEvent(new Event('input'));
            sendMessage();
        });
    });

    // ── Side Quota ──────────────────────────────────────────────
    async function loadSideQuota() {
        const statusEl = document.getElementById('sideQuotaStatus');
        const detailsEl = document.getElementById('sideQuotaDetails');
        const msgEl = document.getElementById('sideQuotaMsg');
        if (!statusEl) return;

        function fmtN(n) {
            return (n || 0).toLocaleString('pt-BR');
        }

        function barCol(pct) {
            if (pct > 50) return 'var(--color-success)';
            if (pct > 20) return 'var(--color-warning)';
            return 'var(--color-danger)';
        }

        try {
            const res = await fetch(`${BASE}api/sysadmin/ai/quota`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: AbortSignal.timeout(15000),
            });
            const json = await res.json();
            const d = json.data;
            if (!d) {
                statusEl.textContent = 'Erro';
                return;
            }

            const labels = {
                active: 'Ativo',
                quota_exceeded: 'Quota Excedida',
                invalid_key: 'Chave Inválida',
                error: 'Erro'
            };
            const colors = {
                active: 'var(--color-success)',
                quota_exceeded: 'var(--color-danger)',
                invalid_key: 'var(--color-danger)',
                error: 'var(--color-warning)'
            };

            statusEl.textContent = labels[d.status] || d.status;
            statusEl.style.color = colors[d.status] || '';

            if (d.status === 'active') {
                detailsEl.style.display = '';

                const reqPct = d.requests_limit > 0 ? (d.requests_remaining / d.requests_limit * 100) : 0;
                document.getElementById('sideReqBar').style.width = reqPct + '%';
                document.getElementById('sideReqBar').style.background = barCol(reqPct);
                document.getElementById('sideReqVal').textContent = fmtN(d.requests_remaining) + ' / ' + fmtN(d.requests_limit);

                const tokPct = d.tokens_limit > 0 ? (d.tokens_remaining / d.tokens_limit * 100) : 0;
                document.getElementById('sideTokBar').style.width = tokPct + '%';
                document.getElementById('sideTokBar').style.background = barCol(tokPct);
                document.getElementById('sideTokVal').textContent = fmtN(d.tokens_remaining) + ' / ' + fmtN(d.tokens_limit);
            }

            if (d.message && d.status !== 'active') {
                msgEl.textContent = d.message;
                msgEl.style.display = '';
            }
        } catch {
            statusEl.textContent = 'Erro';
            statusEl.style.color = 'var(--color-danger)';
        }
    }

    // ── Recent logs sidebar ─────────────────────────────────────
    async function loadRecentLogs() {
        const container = document.getElementById('recentLogs');
        if (!container) return;

        try {
            const res = await fetch(`${BASE}api/sysadmin/ai/logs/summary?hours=24`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const json = await res.json();
            if (!json.success || !json.data.recentes?.length) {
                container.innerHTML = '<div style="font-size:var(--font-size-xs);color:var(--color-text-muted);text-align:center;padding:.5rem 0;">Nenhuma interação recente</div>';
                return;
            }

            const typeLabels = {
                chat: 'Chat',
                suggest_category: 'Sugestão',
                analyze_spending: 'Análise',
                categorize: 'Categorização',
                analyze: 'Análise (novo)',
                quick_query: 'Consulta Rápida',
                extract_transaction: 'Extração'
            };
            let html = '';
            json.data.recentes.forEach(log => {
                const d = new Date(log.created_at);
                const time = d.toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                const label = typeLabels[log.type] || log.type;
                const statusColor = log.success ? 'var(--color-success)' : 'var(--color-danger)';
                html += `<div class="status-row"><span style="display:flex;align-items:center;gap:.3rem;"><span class="dot" style="background:${statusColor};"></span>${label}</span><span>${log.tokens_total || '—'} tok · ${time}</span></div>`;
            });
            html += `<div class="status-row" style="margin-top:.25rem;"><span>Total (24h)</span><span style="font-weight:600;">${json.data.total} chamadas</span></div>`;
            container.innerHTML = html;
        } catch {
            container.innerHTML = '<div style="font-size:var(--font-size-xs);color:var(--color-text-muted);text-align:center;padding:.5rem 0;">Erro ao carregar</div>';
        }
    }

    // ── init ────────────────────────────────────────────────────
    checkServiceHealth();
    loadSideQuota();
    loadRecentLogs();
    inputEl.focus();
})();
