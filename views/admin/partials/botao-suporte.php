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
<div class="lk-chat-panel surface-card" id="lkChatPanel" role="dialog" aria-label="Suporte e Assistente IA">
    <div class="lk-chat-header">
        <div class="lk-chat-tabs">
            <button class="lk-chat-tab active" data-tab="support" id="tabSupport" type="button"
                aria-controls="panelSupport">
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
                <div class="lk-support-info-name" id="sfName"><?= htmlspecialchars($supportName, ENT_QUOTES, 'UTF-8') ?>
                </div>
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
            <!-- Header compacto -->
            <div class="lk-ai-header">
                <div class="lk-ai-header-info">
                    <div class="lk-ai-header-icon">
                        <i data-lucide="bot" style="width:14px;height:14px;"></i>
                    </div>
                    <div class="lk-ai-header-text">
                        <span class="lk-ai-header-name">Assistente IA</span>
                        <span class="lk-ai-header-status"><span class="lk-ai-online-dot"></span>Online</span>
                    </div>
                </div>
                <button class="lk-ai-new-conv-btn" id="aiNewConversation" type="button" title="Nova conversa">
                    <i data-lucide="plus" style="width:14px;height:14px;"></i>
                </button>
            </div>

            <!-- Area de mensagens -->
            <div class="lk-ai-chat-area" id="aiChatArea">
                <div class="lk-ai-messages" id="aiMessages" aria-live="polite" aria-label="Mensagens do assistente">
                    <div class="lk-ai-empty" id="aiEmpty">
                        <div class="lk-ai-empty-icon">
                            <i data-lucide="bot" style="width:28px;height:28px;"></i>
                        </div>
                        <p class="lk-ai-empty-text">Como o Luki pode ajudar hoje?</p>
                        <p class="lk-ai-empty-subtitle">Seu assistente financeiro pessoal</p>
                        <div class="lk-ai-suggestions" id="aiStarterPrompts">
                            <button type="button" class="lk-ai-suggestion surface-card"
                                data-ai-message="qual e o meu saldo atual?" data-ai-mode="send">
                                <i data-lucide="wallet" style="width:13px;height:13px;"></i>
                                Ver meu saldo
                            </button>
                            <button type="button" class="lk-ai-suggestion surface-card"
                                data-ai-message="quero registrar um gasto" data-ai-mode="fill">
                                <i data-lucide="receipt" style="width:13px;height:13px;"></i>
                                Registrar um gasto
                            </button>
                            <button type="button" class="lk-ai-suggestion surface-card"
                                data-ai-message="quero criar uma meta" data-ai-mode="fill">
                                <i data-lucide="target" style="width:13px;height:13px;"></i>
                                Criar uma meta
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input area -->
            <div class="lk-ai-input-area">
                <?php if ($planTier !== 'ultra'): ?>
                    <div class="lk-ai-quota-bar" id="aiQuotaBar">
                        <span id="aiQuotaText"></span>
                    </div>
                <?php endif; ?>

                <div class="lk-ai-exhausted-overlay" id="aiExhaustedOverlay" style="display:none;">
                    <span>Limite de mensagens atingido</span>
                    <a href="/billing" class="lk-ai-upgrade-link">Fazer upgrade</a>
                </div>

                <div class="lk-ai-input-row" id="aiInputRow">
                    <button class="lk-ai-media-btn" id="aiAttachBtn" type="button" title="Anexar arquivo"
                        aria-label="Anexar arquivo">
                        <i data-lucide="paperclip" style="width:16px;height:16px;"></i>
                    </button>
                    <button class="lk-ai-media-btn" id="aiMicBtn" type="button" title="Gravar audio"
                        aria-label="Gravar audio">
                        <i data-lucide="mic" style="width:16px;height:16px;"></i>
                    </button>
                    <input type="file" id="aiFileInput" accept="image/jpeg,image/png,image/webp,application/pdf"
                        style="display:none;">
                    <textarea id="aiChatInput" class="lk-ai-textarea" placeholder="Pergunte algo..." rows="1"
                        aria-label="Mensagem para o assistente IA"></textarea>
                    <button class="lk-ai-send-btn" id="aiChatSend" type="button" aria-label="Enviar mensagem">
                        <i data-lucide="arrow-up" style="width:16px;height:16px;"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
