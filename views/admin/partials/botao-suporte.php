<?php
// CSS: public/assets/css/modules/support-button.css (carregado via header.php)
// JS:  resources/js/admin/global/support-button.js (carregado via Vite bundle)
// Variáveis: $supportName, $supportEmail, $supportTel, $supportDdd, $planTier (via BaseController::renderAdmin)

$supportName  = $supportName  ?? '';
$supportEmail = $supportEmail ?? '';
$supportTel   = $supportTel   ?? '';
$supportDdd   = $supportDdd   ?? '';
$planTier     = $planTier     ?? 'free';
?>

<!-- FAB Speed Dial Container -->
<div class="lk-fab-container" id="lkFabContainer">
    <!-- Mini-botão: Assistente IA (mais longe do botão principal) -->
    <a href="#" class="lk-fab-item" data-action="ai" id="fabItemAI">
        <span class="lk-fab-label">Assistente IA</span>
        <span class="lk-fab-icon">
            <i data-lucide="bot"></i>
        </span>
    </a>
    <!-- Mini-botão: Suporte (mais perto do botão principal) -->
    <a href="#" class="lk-fab-item" data-action="support" id="fabItemSupport">
        <span class="lk-fab-label">Fale com o Suporte</span>
        <span class="lk-fab-icon">
            <i data-lucide="headphones"></i>
        </span>
    </a>
    <!-- Botão principal (fica embaixo de tudo) -->
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
<div class="lk-chat-panel" id="lkChatPanel">
    <!-- Header com abas -->
    <div class="lk-chat-header">
        <div class="lk-chat-tabs">
            <button class="lk-chat-tab active" data-tab="support" id="tabSupport">
                <i data-lucide="headphones" style="width:14px;height:14px;"></i>
                Suporte
            </button>
            <button class="lk-chat-tab" data-tab="ai" id="tabAI">
                <i data-lucide="bot" style="width:14px;height:14px;"></i>
                Assistente IA
            </button>
        </div>
        <button class="lk-chat-close" id="lkChatClose">
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
                    <div class="lk-support-info-email"><i data-lucide="mail"></i>
                        <?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="lk-preference-label">
                <i data-lucide="reply"></i>
                Como prefere receber o retorno?
            </div>
            <div class="lk-contact-preference">
                <label class="lk-radio">
                    <input type="radio" name="retorno-panel" value="whatsapp">
                    <span class="whats"><i class="fab fa-whatsapp"
                            style="width:0!important;height:0!important;"></i>WhatsApp</span>
                </label>
                <label class="lk-radio">
                    <input type="radio" name="retorno-panel" value="email" checked>
                    <span><i data-lucide="mail"></i>E-mail</span>
                </label>
            </div>

            <textarea id="supportPanelMessage" class="lk-chat-textarea"
                placeholder="Descreva sua dúvida, problema ou sugestão... 😊" rows="4"></textarea>

            <button class="lk-chat-send-support" id="btnSendSupport">
                <i data-lucide="send" style="width:14px;height:14px;"></i>
                Enviar Mensagem
            </button>
        </div>
    </div>

    <!-- Aba: Assistente IA -->
    <div class="lk-chat-body lk-tab-content" id="panelAI">
        <?php if ($planTier === 'free'): ?>
            <!-- Overlay de upgrade para free -->
            <div class="lk-ai-upgrade-overlay" id="aiUpgradeOverlay">
                <div class="lk-ai-upgrade-content">
                    <i data-lucide="sparkles" style="width:48px;height:48px;color:var(--color-primary);"></i>
                    <h3>Assistente IA</h3>
                    <p>Converse com a IA sobre suas finanças, tire dúvidas e receba insights personalizados.</p>
                    <a href="/billing" class="lk-ai-upgrade-btn">
                        <i data-lucide="zap" style="width:16px;height:16px;"></i>
                        Fazer Upgrade para Pro
                    </a>
                    <span class="lk-ai-upgrade-hint">A partir de R$ 14,90/mês</span>
                </div>
            </div>
        <?php else: ?>
            <!-- Chat de IA -->
            <div class="lk-ai-chat-area" id="aiChatArea">
                <div class="lk-ai-messages" id="aiMessages">
                    <div class="lk-ai-empty" id="aiEmpty">
                        <i data-lucide="bot" style="width:36px;height:36px;color:var(--color-primary);opacity:0.5;"></i>
                        <p>Olá! Sou seu assistente financeiro.<br>Como posso ajudar?</p>
                    </div>
                </div>
            </div>
            <div class="lk-ai-input-area">
                <?php if ($planTier === 'pro'): ?>
                    <div class="lk-ai-quota-bar" id="aiQuotaBar">
                        <span id="aiQuotaText">Carregando...</span>
                    </div>
                <?php endif; ?>
                <div class="lk-ai-input-row">
                    <textarea id="aiChatInput" class="lk-ai-textarea" placeholder="Digite sua pergunta..."
                        rows="1"></textarea>
                    <button class="lk-ai-send-btn" id="aiChatSend">
                        <i data-lucide="send" style="width:16px;height:16px;"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>