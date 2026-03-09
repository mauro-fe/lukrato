<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bundles/sysadmin-modern.css.php?v=<?= time() ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/pages/ai-chat.css?v=<?= time() ?>">

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

<!-- JS carregado via Vite (loadPageJs) -->