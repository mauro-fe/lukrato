<?php
// CSS: resources/css/admin/base.css (carregado via Vite)
// JS:  resources/js/admin/global/support-button.js (carregado via Vite bundle)
// Variaveis: $supportName, $supportEmail, $supportTel, $supportDdd, $planTier (via BaseController::renderAdmin)

$supportName  = $supportName  ?? '';
$supportEmail = $supportEmail ?? '';
$supportTel   = $supportTel   ?? '';
$supportDdd   = $supportDdd   ?? '';
$planTier     = $planTier     ?? 'free';
?>

<!-- FAB Speed Dial Container -->
<div class="lk-fab-container" id="lkFabContainer">
    <!-- Mini-botao: Assistente IA (mais longe do botao principal) -->
    <a href="#" class="lk-fab-item" data-action="ai" id="fabItemAI">
        <span class="lk-fab-label">Assistente IA</span>
        <span class="lk-fab-icon">
            <i data-lucide="bot"></i>
        </span>
    </a>
    <!-- Mini-botao: Suporte (mais perto do botao principal) -->
    <a href="#" class="lk-fab-item" data-action="support" id="fabItemSupport">
        <span class="lk-fab-label">Fale com o Suporte</span>
        <span class="lk-fab-icon">
            <i data-lucide="headphones"></i>
        </span>
    </a>
    <!-- Botao principal (fica embaixo de tudo) -->
    <a href="#" class="lk-support-button" title="Suporte & Assistente IA"
        data-support-name="<?= htmlspecialchars($supportName, ENT_QUOTES, 'UTF-8') ?>"
        data-support-email="<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>"
        data-support-tel="<?= htmlspecialchars($supportTel, ENT_QUOTES, 'UTF-8') ?>"
        data-support-cod="<?= htmlspecialchars($supportDdd, ENT_QUOTES, 'UTF-8') ?>"
        data-plan-tier="<?= htmlspecialchars($planTier, ENT_QUOTES, 'UTF-8') ?>" id="lkSupportToggle">
        <i data-lucide="message-square"></i>
    </a>
</div>

<!-- Painel flutuante com abas -->
<div class="lk-chat-panel" id="lkChatPanel" role="dialog" aria-label="Suporte e Assistente IA">
    <div class="lk-chat-header">
        <div class="lk-chat-tabs">
            <button class="lk-chat-tab active" data-tab="support" id="tabSupport" type="button" aria-controls="panelSupport">
                <i data-lucide="headphones" style="width:14px;height:14px;"></i>
                Suporte
            </button>
            <button class="lk-chat-tab" data-tab="ai" id="tabAI" type="button" aria-controls="panelAI">
                <i data-lucide="bot" style="width:14px;height:14px;"></i>
                Assistente IA
            </button>
        </div>
        <button class="lk-chat-close" id="lkChatClose" type="button" aria-label="Fechar painel">
            <i data-lucide="x" style="width:16px;height:16px;"></i>
        </button>
    </div>

    <!-- Aba: Suporte -->
    <div class="lk-chat-body lk-tab-content active" id="panelSupport">
        <div class="lk-support-form-panel">
            <div class="lk-support-info">
                <div class="lk-support-info-label">Enviando como:</div>
                <div class="lk-support-info-name" id="sfName"><?= htmlspecialchars($supportName, ENT_QUOTES, 'UTF-8') ?></div>
                <?php if ($supportEmail): ?>
                    <div class="lk-support-info-email">
                        <i data-lucide="mail"></i>
                        <?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="lk-preference-label">
                <i data-lucide="reply"></i>
                Como prefere receber o retorno?
            </div>
            <div class="lk-contact-preference">
                <label class="lk-radio">
                    <input type="radio" name="retorno-panel" value="whatsapp">
                    <span class="whats">
                        <i class="fab fa-whatsapp" style="width:0!important;height:0!important;"></i>
                        WhatsApp
                    </span>
                </label>
                <label class="lk-radio">
                    <input type="radio" name="retorno-panel" value="email" checked>
                    <span>
                        <i data-lucide="mail"></i>
                        E-mail
                    </span>
                </label>
            </div>

            <textarea id="supportPanelMessage" class="lk-chat-textarea"
                placeholder="Descreva sua duvida, problema ou sugestao..." rows="4"></textarea>

            <button class="lk-chat-send-support" id="btnSendSupport">
                <i data-lucide="send" style="width:14px;height:14px;"></i>
                Enviar mensagem
            </button>
        </div>
    </div>

    <!-- Aba: Assistente IA -->
    <div class="lk-chat-body lk-tab-content" id="panelAI">
        <div class="lk-ai-shell">
            <div class="lk-ai-hero">
                <div class="lk-ai-hero-main">
                    <div class="lk-ai-hero-icon">
                        <i data-lucide="sparkles" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="lk-ai-hero-copy">
                        <div class="lk-ai-hero-title">Assistente IA</div>
                        <div class="lk-ai-hero-presence">
                            <span class="lk-ai-online-dot"></span>
                            Online
                        </div>
                    </div>
                </div>
                <button class="lk-ai-new-conv-btn lk-ai-new-conv-btn--hero" id="aiNewConversation" type="button" title="Iniciar nova conversa">
                    <i data-lucide="message-square-plus" style="width:14px;height:14px;"></i>
                    Nova conversa
                </button>
            </div>

            <div class="lk-ai-shortcuts" aria-label="Atalhos do assistente">
                <button type="button" class="lk-ai-chip lk-ai-chip--shortcut" data-ai-message="me mostre um resumo financeiro" data-ai-mode="send">
                    <i data-lucide="bar-chart-3" style="width:13px;height:13px;"></i>
                    Resumo financeiro
                </button>
                <button type="button" class="lk-ai-chip lk-ai-chip--shortcut" data-ai-message="quero registrar um gasto" data-ai-mode="fill">
                    <i data-lucide="receipt" style="width:13px;height:13px;"></i>
                    Registrar gasto
                </button>
                <button type="button" class="lk-ai-chip lk-ai-chip--shortcut" data-ai-message="quero criar uma meta" data-ai-mode="fill">
                    <i data-lucide="target" style="width:13px;height:13px;"></i>
                    Criar meta
                </button>
                <button type="button" class="lk-ai-chip lk-ai-chip--shortcut" data-ai-message="qual e o meu saldo atual?" data-ai-mode="send">
                    <i data-lucide="wallet" style="width:13px;height:13px;"></i>
                    Ver saldo
                </button>
            </div>

            <div class="lk-ai-chat-area" id="aiChatArea">
                <div class="lk-ai-messages" id="aiMessages" aria-live="polite" aria-label="Mensagens do assistente">
                    <div class="lk-ai-empty" id="aiEmpty">
                        <div class="lk-ai-empty-orb">
                            <i data-lucide="bot" style="width:32px;height:32px;"></i>
                        </div>
                        <div class="lk-ai-empty-copy">
                            <h3>Seu copiloto financeiro</h3>
                            <p>Posso registrar gastos, analisar o mes, criar metas e ler comprovantes.</p>
                        </div>
                        <div class="lk-ai-starter-grid" id="aiStarterPrompts">
                            <button type="button" class="lk-ai-chip" data-ai-message="quanto gastei este mes?" data-ai-mode="send">
                                Ver meus gastos do mes
                            </button>
                            <button type="button" class="lk-ai-chip" data-ai-message="quero registrar um gasto" data-ai-mode="fill">
                                Registrar um gasto
                            </button>
                            <button type="button" class="lk-ai-chip" data-ai-message="quero criar uma meta" data-ai-mode="fill">
                                Criar uma meta
                            </button>
                            <button type="button" class="lk-ai-chip" data-ai-message="me ajude a analisar minhas financas" data-ai-mode="send">
                                Analisar minhas financas
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lk-ai-input-area">
                <?php if ($planTier !== 'ultra'): ?>
                    <div class="lk-ai-quota-bar" id="aiQuotaBar">
                        <span id="aiQuotaText">Carregando...</span>
                    </div>
                <?php endif; ?>

                <div class="lk-ai-exhausted-overlay" id="aiExhaustedOverlay" style="display:none;">
                    <i data-lucide="lock" style="width:18px;height:18px;color:var(--color-primary);"></i>
                    <span>Voce usou suas 5 mensagens gratuitas deste mes.</span>
                    <a href="/billing" class="lk-ai-upgrade-btn lk-ai-upgrade-btn--sm">
                        <i data-lucide="zap" style="width:14px;height:14px;"></i>
                        Fazer upgrade para Pro
                    </a>
                </div>

                <div class="lk-ai-status" id="aiStatus" aria-live="polite">
                    Feche o painel sem medo: a conversa continua daqui.
                </div>

                <div class="lk-ai-input-shell">
                    <div class="lk-ai-input-row" id="aiInputRow">
                        <button class="lk-ai-media-btn" id="aiAttachBtn" type="button" title="Enviar imagem ou PDF" aria-label="Enviar imagem ou PDF">
                            <i data-lucide="paperclip" style="width:16px;height:16px;"></i>
                        </button>
                        <input type="file" id="aiFileInput" accept="image/jpeg,image/png,image/webp,application/pdf" style="display:none;">
                        <textarea id="aiChatInput" class="lk-ai-textarea" placeholder="Digite sua pergunta ou comando..."
                            rows="1" aria-label="Mensagem para o assistente IA" aria-describedby="aiComposerHint aiStatus"></textarea>
                        <button class="lk-ai-media-btn" id="aiMicBtn" type="button" title="Gravar audio" aria-label="Gravar audio">
                            <i data-lucide="mic" style="width:16px;height:16px;"></i>
                        </button>
                        <button class="lk-ai-send-btn" id="aiChatSend" type="button" aria-label="Enviar mensagem">
                            <i data-lucide="send" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                </div>

                <div class="lk-ai-composer-hint" id="aiComposerHint">
                    Pressione Enter para enviar. Shift + Enter para nova linha.
                </div>

                <div class="lk-ai-trust-row" aria-label="Diferenciais do assistente">
                    <div class="lk-ai-trust-pill">
                        <i data-lucide="shield-check" style="width:13px;height:13px;"></i>
                        Seguro
                    </div>
                    <div class="lk-ai-trust-pill">
                        <i data-lucide="zap" style="width:13px;height:13px;"></i>
                        Inteligente
                    </div>
                    <div class="lk-ai-trust-pill">
                        <i data-lucide="pointer" style="width:13px;height:13px;"></i>
                        Acoes
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
