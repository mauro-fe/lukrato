    <!-- Tab: Integrações -->
    <div class="profile-tab-panel" id="panel-integracoes" role="tabpanel" aria-labelledby="tab-integracoes">
        <!-- WhatsApp (oculto temporariamente — API ainda não disponível)
        <div class="profile-section surface-card surface-card--interactive">
            <div class="section-header">
                <div class="section-icon" style="background:#22c55e"><i data-lucide="message-circle" style="color:white"></i></div>
                <div class="section-header-text">
                    <h3>WhatsApp</h3>
                    <p>Registre lançamentos enviando mensagens pelo WhatsApp</p>
                </div>
            </div>

            <div class="integration-card" id="whatsapp-card">
                <div class="integration-status" id="whatsapp-status">
                    <span class="status-indicator not-linked"></span>
                    <span class="status-text">Carregando...</span>
                </div>

                <div class="integration-action" id="whatsapp-not-linked" style="display:none">
                    <div class="form-row cols-1">
                        <div class="form-group">
                            <label class="form-label"><i data-lucide="phone" class="icon-label" style="color:#22c55e"></i> Número do WhatsApp</label>
                            <input class="form-input" id="whatsapp-phone" type="tel" inputmode="tel" maxlength="20" placeholder="5511999999999">
                            <small style="color:var(--color-text-muted);font-size:12px;margin-top:4px;display:block;">
                                Formato: código do país + DDD + número (ex: 5511999999999)
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-integration" id="btn-whatsapp-link" style="--accent:#22c55e">
                        <i data-lucide="link"></i> Vincular WhatsApp
                    </button>
                </div>

                <div class="integration-action" id="whatsapp-verify" style="display:none">
                    <p class="integration-instructions" id="whatsapp-verify-msg"></p>
                    <div class="form-row cols-1">
                        <div class="form-group">
                            <label class="form-label"><i data-lucide="key-round" class="icon-label" style="color:#22c55e"></i> Código de Verificação</label>
                            <input class="form-input" id="whatsapp-code" type="text" inputmode="numeric" maxlength="6" placeholder="000000">
                        </div>
                    </div>
                    <button type="button" class="btn-integration" id="btn-whatsapp-verify" style="--accent:#22c55e">
                        <i data-lucide="check"></i> Verificar Código
                    </button>
                </div>

                <div class="integration-action" id="whatsapp-linked" style="display:none">
                    <p class="integration-linked-info">
                        <i data-lucide="check-circle" style="color:#22c55e;width:18px;height:18px;vertical-align:middle"></i>
                        Vinculado: <strong id="whatsapp-masked-phone"></strong>
                    </p>
                    <button type="button" class="btn-integration danger" id="btn-whatsapp-unlink">
                        <i data-lucide="unlink"></i> Desvincular
                    </button>
                </div>
            </div>
        </div>
        fim WhatsApp oculto -->

        <!-- Telegram -->
        <div class="profile-section surface-card surface-card--interactive">
            <div class="section-header">
                <div class="section-icon" style="background:#0ea5e9"><i data-lucide="send" style="color:white"></i></div>
                <div class="section-header-text">
                    <h3>Telegram</h3>
                    <p>Registre lançamentos enviando mensagens pelo Telegram</p>
                </div>
            </div>

            <div class="integration-card surface-control-box surface-control-box--interactive" id="telegram-card">
                <div class="integration-status" id="telegram-status">
                    <span class="status-indicator not-linked"></span>
                    <span class="status-text">Carregando...</span>
                </div>

                <!-- Estado: Não vinculado -->
                <div class="integration-action" id="telegram-not-linked" style="display:none">
                    <p class="integration-instructions">
                        Clique no botão abaixo para gerar um código. Depois, envie o código para o bot do Lukrato no Telegram.
                    </p>
                    <button type="button" class="btn-integration" id="btn-telegram-link" style="--accent:#0ea5e9">
                        <i data-lucide="link"></i> Vincular Telegram
                    </button>
                </div>

                <!-- Estado: Código gerado -->
                <div class="integration-action" id="telegram-code-generated" style="display:none">
                    <div class="integration-code-box telegram-link-flow">
                        <p class="integration-instructions">Envie este código para o bot:</p>
                        <ol class="telegram-link-steps">
                            <li>Abra o bot do Lukrato no Telegram</li>
                            <li>Envie o código de 6 dígitos abaixo</li>
                            <li>Volte aqui enquanto confirmamos o vínculo</li>
                        </ol>
                        <div class="profile-inline-copy-row profile-inline-copy-row--telegram">
                            <input class="form-input profile-code-input profile-code-input--telegram" id="telegram-code-display" type="text" readonly>
                            <button type="button" class="btn-copy-support surface-button surface-button--subtle surface-button--compact" id="btn-copy-telegram-code" title="Copiar código">
                                <i data-lucide="copy"></i>
                            </button>
                        </div>
                        <a id="telegram-bot-link" href="#" target="_blank" rel="noopener noreferrer" class="btn-integration" style="--accent:#0ea5e9;text-decoration:none;display:inline-flex">
                            <i data-lucide="external-link"></i> Abrir no Telegram
                        </a>
                        <div class="telegram-qr-wrapper" id="telegram-qr-wrapper" aria-live="polite">
                            <div class="telegram-qr-card">
                                <img id="telegram-qr-image" class="telegram-qr-image" src="" alt="QR code para abrir o bot do Telegram">
                                <small class="telegram-qr-hint">Se estiver no PC, escaneie com o celular.</small>
                            </div>
                        </div>
                        <div class="telegram-link-meta">
                            <span id="telegram-link-status-copy">Abra o bot, envie o código e aguarde a confirmação.</span>
                            <span id="telegram-link-countdown"></span>
                        </div>
                        <div class="telegram-link-actions">
                            <button type="button" class="btn-integration subtle" id="btn-telegram-regenerate" style="--accent:#0ea5e9">
                                <i data-lucide="refresh-cw"></i> Gerar novo código
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Estado: Vinculado -->
                <div class="integration-action" id="telegram-linked" style="display:none">
                    <p class="integration-linked-info">
                        <i data-lucide="check-circle" style="color:#0ea5e9;width:18px;height:18px;vertical-align:middle"></i>
                        Telegram vinculado
                    </p>
                    <small class="integration-linked-subtitle" id="telegram-linked-handle"></small>
                    <button type="button" class="btn-integration danger" id="btn-telegram-unlink">
                        <i data-lucide="unlink"></i> Desvincular
                    </button>
                </div>
            </div>
        </div>
    </div><!-- /panel-integracoes -->
