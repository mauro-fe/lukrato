<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bundles/sysadmin-modern.css.php?v=<?= time() ?>">

<style>
    /* ─── Layout ─── */
    .ai-page {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-6);
    }

    .ai-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: var(--spacing-4);
    }

    .ai-header .header-content h1 {
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        font-size: var(--font-size-2xl);
        font-weight: 700;
        margin: 0;
    }

    .ai-header .header-content p {
        color: var(--color-text-muted);
        margin: .25rem 0 0;
        font-size: var(--font-size-sm);
    }

    .ai-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .75rem;
        border-radius: 999px;
        font-size: var(--font-size-xs);
        font-weight: 600;
        background: var(--blue-100);
        color: var(--blue-700);
    }

    .ai-badge.ollama {
        background: rgba(46, 204, 113, .15);
        color: var(--color-success);
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .5rem 1rem;
        border-radius: var(--radius-sm);
        border: 1px solid var(--color-card-border);
        color: var(--color-text);
        font-size: var(--font-size-sm);
        text-decoration: none;
        transition: background var(--transition-fast);
    }

    .btn-back:hover {
        background: var(--color-surface-muted) !important;
    }

    /* ─── Grid ─── */
    .ai-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 1.25rem;
    }

    @media (max-width: 900px) {
        .ai-grid {
            grid-template-columns: 1fr;
        }
    }

    /* ─── Chat Card ─── */
    .chat-card {
        background: var(--glass-bg);
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-lg);
        display: flex;
        flex-direction: column;
        height: 600px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .chat-title {
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        padding: var(--spacing-4) var(--spacing-5);
        border-bottom: 1px solid var(--color-card-border);
        font-weight: 600;
        font-size: var(--font-size-base);
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: var(--spacing-4) var(--spacing-5);
        display: flex;
        flex-direction: column;
        gap: var(--spacing-3);
    }

    .chat-msg {
        display: flex;
        gap: .6rem;
        max-width: 92%;
    }

    .chat-msg.user {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .chat-msg .avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: var(--font-size-xs);
    }

    .chat-msg.ai .avatar {
        background: var(--blue-100);
        color: var(--blue-700);
    }

    .chat-msg.user .avatar {
        background: rgba(124, 58, 237, .12);
        color: #7c3aed;
    }

    .chat-msg .bubble {
        padding: .6rem .9rem;
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        line-height: 1.5;
    }

    .chat-msg.ai .bubble {
        background: var(--color-surface-muted);
        color: var(--color-text);
        border-bottom-left-radius: .2rem;
    }

    .chat-msg.user .bubble {
        background: var(--blue-600);
        color: #fff;
        border-bottom-right-radius: .2rem;
    }

    .chat-msg .bubble p {
        margin: 0 0 .4rem;
    }

    .chat-msg .bubble p:last-child {
        margin: 0;
    }

    .chat-msg.ai.typing .bubble {
        color: var(--color-text-muted);
        font-style: italic;
    }

    .chat-input-row {
        display: flex;
        gap: var(--spacing-2);
        padding: .875rem var(--spacing-4);
        border-top: 1px solid var(--color-card-border);
    }

    .chat-input-row textarea {
        flex: 1;
        resize: none;
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-sm);
        padding: .55rem .875rem;
        font-size: var(--font-size-sm);
        font-family: var(--font-primary);
        line-height: 1.4;
        max-height: 120px;
        overflow-y: auto;
        outline: none;
        background: var(--color-bg);
        color: var(--color-text);
    }

    .chat-input-row textarea:focus {
        border-color: var(--blue-500);
        box-shadow: 0 0 0 3px rgba(95, 151, 238, .18);
    }

    .chat-input-row button {
        padding: .55rem 1.1rem;
        background: var(--blue-600);
        color: #fff;
        border: none;
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: var(--font-size-base);
        display: flex;
        align-items: center;
        gap: .35rem;
        transition: background var(--transition-fast);
    }

    .chat-input-row button:hover {
        background: var(--blue-700);
    }

    .chat-input-row button:disabled {
        background: var(--blue-300);
        cursor: not-allowed;
    }

    .chat-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex: 1;
        gap: var(--spacing-2);
        color: var(--color-text-muted);
        font-size: var(--font-size-sm);
        text-align: center;
        padding: var(--spacing-8);
    }

    .chat-empty i {
        width: 40px;
        height: 40px;
    }

    /* ─── Side Panel ─── */
    .side-card {
        background: var(--glass-bg);
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-lg);
        padding: var(--spacing-5);
        box-shadow: var(--shadow-sm);
    }

    .side-card h3 {
        font-size: var(--font-size-base);
        font-weight: 600;
        margin: 0 0 .875rem;
        display: flex;
        align-items: center;
        gap: .4rem;
    }

    .quick-btn {
        display: flex;
        align-items: flex-start;
        gap: .6rem;
        width: 100%;
        text-align: left;
        background: var(--color-bg);
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-sm);
        padding: .6rem .875rem;
        cursor: pointer;
        font-size: var(--font-size-xs);
        line-height: 1.4;
        transition: background var(--transition-fast), border-color var(--transition-fast);
        margin-bottom: var(--spacing-2);
        color: var(--color-text);
    }

    .quick-btn:hover {
        background: var(--color-bg) !important;
        border-color: var(--blue-200);
    }

    .quick-btn i {
        flex-shrink: 0;
        color: var(--blue-600);
        margin-top: 1px;
    }

    .divider {
        border: none;
        border-top: 1px solid var(--color-card-border);
        margin: var(--spacing-4) 0;
    }

    .status-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
        margin-bottom: .35rem;
    }

    .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: .35rem;
    }

    .dot.green {
        background: var(--color-success);
    }

    .dot.red {
        background: var(--color-danger);
    }

    .dot.yellow {
        background: var(--color-warning);
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1
        }

        50% {
            opacity: .4
        }
    }
</style>

<div class="sysadmin-container ai-page">

    <!-- Header -->
    <div class="ai-header">
        <div class="header-content">
            <h1>
                <i data-lucide="bot"></i>
                Assistente IA
            </h1>
            <p>Chat inteligente e ferramentas de análise financeira</p>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem;">
            <span class="ai-badge <?= strtolower($aiProvider) === 'ollama' ? 'ollama' : '' ?>">
                <i data-lucide="cpu" style="width:13px;height:13px;"></i>
                <?= htmlspecialchars($aiProvider) ?> · <?= htmlspecialchars($aiModel) ?>
            </span>
            <a href="<?= BASE_URL ?>sysadmin" class="btn-back">
                <i data-lucide="arrow-left"></i>
                Voltar ao Painel
            </a>
        </div>
    </div>

    <!-- Grid: Chat + Side Panel -->
    <div class="ai-grid">

        <!-- Chat -->
        <div class="chat-card">
            <div class="chat-title">
                <i data-lucide="message-circle" style="color:var(--blue-600)"></i>
                Chat Assistente
            </div>

            <div class="chat-messages" id="chatMessages">
                <div class="chat-empty" id="chatEmpty">
                    <i data-lucide="bot"></i>
                    <strong>Olá! Sou seu assistente financeiro.</strong>
                    <span>Pergunte sobre métricas, usuários, padrões de gastos ou qualquer coisa do sistema.</span>
                </div>
            </div>

            <div class="chat-input-row">
                <textarea id="chatInput"
                    placeholder="Digite sua pergunta... (Enter para enviar, Shift+Enter para nova linha)"
                    rows="1"></textarea>
                <button id="chatSend" title="Enviar">
                    <i data-lucide="send" style="width:16px;height:16px;"></i>
                </button>
            </div>
        </div>

        <!-- Side Panel -->
        <div style="display:flex;flex-direction:column;gap:1.25rem;">

            <!-- Perguntas Rápidas -->
            <div class="side-card">
                <h3><i data-lucide="zap" style="color:var(--color-warning);width:16px;height:16px;"></i> Perguntas
                    Rápidas</h3>

                <button class="quick-btn"
                    data-prompt="Quantos usuários temos no total? Qual foi o crescimento este mês?">
                    <i data-lucide="users" style="width:14px;height:14px;"></i>
                    Crescimento de usuários
                </button>
                <button class="quick-btn"
                    data-prompt="Quais são as métricas mais importantes que devo acompanhar num sistema de finanças pessoais?">
                    <i data-lucide="bar-chart-2" style="width:14px;height:14px;"></i>
                    Métricas importantes do sistema
                </button>
                <button class="quick-btn"
                    data-prompt="Me dê dicas de como melhorar o engajamento dos usuários numa app de finanças pessoais.">
                    <i data-lucide="trending-up" style="width:14px;height:14px;"></i>
                    Aumentar engajamento
                </button>
                <button class="quick-btn"
                    data-prompt="Quais funcionalidades de IA seriam mais úteis para usuários de um app de controle financeiro pessoal?">
                    <i data-lucide="lightbulb" style="width:14px;height:14px;"></i>
                    Ideias de features com IA
                </button>
                <button class="quick-btn"
                    data-prompt="Explique como funciona o modelo de scoring financeiro e o que posso fazer para ajudar meus usuários a melhorarem seus hábitos.">
                    <i data-lucide="star" style="width:14px;height:14px;"></i>
                    Scoring financeiro
                </button>
            </div>

            <!-- Status do Serviço -->
            <div class="side-card">
                <h3><i data-lucide="activity" style="color:var(--color-success);width:16px;height:16px;"></i> Status do
                    Serviço</h3>

                <div class="status-row">
                    <span><span class="dot yellow" id="statusDot"></span> Serviço IA</span>
                    <span id="statusText" style="font-weight:600;">Verificando...</span>
                </div>
                <div class="status-row">
                    <span>Modelo</span>
                    <span><?= htmlspecialchars($aiModel) ?></span>
                </div>
                <div class="status-row">
                    <span>Provider</span>
                    <span><?= htmlspecialchars($aiProvider) ?></span>
                </div>

                <hr class="divider">

                <!-- Quota inline -->
                <div id="sideQuota">
                    <div class="status-row">
                        <span><i data-lucide="gauge" style="width:12px;height:12px;vertical-align:middle;margin-right:.2rem;"></i> Quota</span>
                        <span id="sideQuotaStatus" style="font-weight:600;font-size:.7rem;">Verificando...</span>
                    </div>
                    <div id="sideQuotaDetails" style="display:none;">
                        <div style="margin:.4rem 0 .2rem;font-size:.7rem;color:var(--color-text-muted);">Requisições</div>
                        <div style="background:var(--color-surface-muted);border-radius:999px;height:6px;overflow:hidden;">
                            <div id="sideReqBar" style="height:100%;border-radius:999px;width:0%;background:var(--color-success);transition:width .4s ease;"></div>
                        </div>
                        <div style="font-size:.65rem;color:var(--color-text-muted);margin-top:.15rem;"><strong id="sideReqVal">—</strong></div>

                        <div style="margin:.4rem 0 .2rem;font-size:.7rem;color:var(--color-text-muted);">Tokens</div>
                        <div style="background:var(--color-surface-muted);border-radius:999px;height:6px;overflow:hidden;">
                            <div id="sideTokBar" style="height:100%;border-radius:999px;width:0%;background:var(--color-success);transition:width .4s ease;"></div>
                        </div>
                        <div style="font-size:.65rem;color:var(--color-text-muted);margin-top:.15rem;"><strong id="sideTokVal">—</strong></div>
                    </div>
                    <div id="sideQuotaMsg" style="display:none;font-size:.7rem;color:var(--color-danger);margin-top:.3rem;word-break:break-word;"></div>
                </div>

                <hr class="divider">

                <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);line-height:1.5;">
                    <strong>Endpoints disponíveis:</strong><br>
                    <code style="font-size:.75rem;">POST /api/sysadmin/ai/chat</code><br>
                    <code style="font-size:.75rem;">POST /api/sysadmin/ai/suggest-category</code><br>
                    <code style="font-size:.75rem;">POST /api/sysadmin/ai/analyze-spending</code>
                </div>
            </div>

            <!-- Últimas Interações -->
            <div class="side-card">
                <h3><i data-lucide="file-text" style="color:var(--blue-600);width:16px;height:16px;"></i> Últimas Interações</h3>
                <div id="recentLogs" style="display:flex;flex-direction:column;gap:.4rem;">
                    <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);text-align:center;padding:.5rem 0;">Carregando...</div>
                </div>
                <hr class="divider">
                <a href="<?= BASE_URL ?>sysadmin/ai/logs" style="display:flex;align-items:center;justify-content:center;gap:.4rem;font-size:var(--font-size-xs);font-weight:600;color:var(--blue-600);text-decoration:none;">
                    <i data-lucide="external-link" style="width:13px;height:13px;"></i>
                    Ver todos os logs
                </a>
            </div>

        </div>
    </div>
</div>

<script>
    (function() {
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
                    } catch {}
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
        inputEl.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // ── Enter para enviar ───────────────────────────────────────
        inputEl.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        sendBtn.addEventListener('click', sendMessage);

        // ── perguntas rápidas ───────────────────────────────────────
        document.querySelectorAll('.quick-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                inputEl.value = this.dataset.prompt;
                inputEl.dispatchEvent(new Event('input'));
                sendMessage();
            });
        });

        // ── init ────────────────────────────────────────────────────
        checkServiceHealth();
        loadSideQuota();
        loadRecentLogs();
        inputEl.focus();
    })();

    // ── Side Quota ──────────────────────────────────────────────
    async function loadSideQuota() {
        const BASE = (window.BASE_URL || document.querySelector('meta[name="base-url"]')?.content || '/').replace(/\/?$/, '/');
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
        const BASE = (window.BASE_URL || document.querySelector('meta[name="base-url"]')?.content || '/').replace(/\/?$/, '/');
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
                analyze_spending: 'Análise'
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
</script>