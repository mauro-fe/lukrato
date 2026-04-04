    <!-- Tab: Plano & Indicação -->
    <div class="profile-tab-panel" id="panel-plano" role="tabpanel" aria-labelledby="tab-plano">
        <!-- Seção de Plano -->
        <div class="profile-section plan-section surface-card surface-card--interactive">
            <div class="section-header">
                <div class="section-icon"><i data-lucide="crown" style="color:white"></i></div>
                <div class="section-header-text">
                    <h3>Meu Plano</h3>
                    <p>Gerencie sua assinatura</p>
                </div>
            </div>

            <div class="plan-section-content">
                <div class="plan-info">
                    <?php
                    $isPro = isset($currentUser) && method_exists($currentUser, 'isPro') && $currentUser->isPro();
                    $planName = $isPro ? 'PRO' : 'Gratuito';
                    $planIcon = $isPro ? 'crown' : 'leaf';
                    $planClass = $isPro ? 'pro' : 'free';
                    ?>
                    <div class="current-plan surface-chip <?= $isPro ? 'surface-chip--pro' : 'surface-chip--success' ?> <?= $planClass ?>">
                        <i data-lucide="<?= $planIcon ?>"></i>
                        <span class="plan-name">Plano <?= $planName ?></span>
                    </div>
                    <p class="plan-description">
                        <?php if ($isPro): ?>
                            Você tem acesso a todos os recursos premium do Lukrato.
                        <?php else: ?>
                            Faça upgrade para desbloquear recursos avançados como importação automática, relatórios
                            detalhados e
                            muito mais.
                        <?php endif; ?>
                    </p>
                </div>
                <a href="<?= BASE_URL ?>billing" class="btn-manage-plan surface-button <?= $isPro ? 'surface-button--subtle' : 'surface-button--upgrade' ?> <?= $planClass ?>">
                    <i data-lucide="<?= $isPro ? 'settings' : 'rocket' ?>"></i>
                    <span><?= $isPro ? 'Gerenciar Plano' : 'Fazer Upgrade' ?></span>
                </a>
            </div>
        </div>

        <!-- Seção de Indicação -->
        <div class="profile-section referral-section surface-card surface-card--interactive">
            <div class="section-header">
                <div class="section-icon"><i data-lucide="gift" style="color:white"></i></div>
                <div class="section-header-text">
                    <h3>Indique Amigos</h3>
                    <p>Ganhe dias de PRO por cada indicação</p>
                </div>
            </div>

            <div class="referral-section-content">
                <div class="referral-info">
                    <div class="referral-reward-info">
                        <div class="reward-item surface-control-box surface-control-box--interactive">
                            <span class="reward-icon"><i data-lucide="user" style="color:#3b82f6"></i></span>
                            <span class="reward-text">Você ganha <strong>15 dias</strong> de PRO</span>
                        </div>
                        <div class="reward-item surface-control-box surface-control-box--interactive">
                            <span class="reward-icon"><i data-lucide="users" style="color:#14b8a6"></i></span>
                            <span class="reward-text">Seu amigo ganha <strong>7 dias</strong> de PRO</span>
                        </div>
                    </div>
                </div>

                <div class="referral-container">
                    <div class="referral-code-container">
                        <label class="referral-label">Seu código de indicação:</label>
                        <div class="referral-code-box surface-control-box">
                            <input type="text" id="referral-code" class="referral-code-input" readonly
                                value="Carregando...">
                            <button type="button" class="btn-copy-code surface-button surface-button--subtle surface-button--compact" id="btn-copy-code" title="Copiar código">
                                <i data-lucide="copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="referral-link-container">
                        <label class="referral-label">Ou compartilhe seu link:</label>
                        <div class="referral-link-box surface-control-box">
                            <input type="text" id="referral-link" class="referral-link-input" readonly
                                value="Carregando...">
                            <button type="button" class="btn-copy-link surface-button surface-button--subtle surface-button--compact" id="btn-copy-link" title="Copiar link">
                                <i data-lucide="copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Barra de limite mensal -->
                <div class="referral-limit-bar surface-card surface-card--clip" id="referral-limit-bar">
                    <div class="limit-bar-header">
                        <span class="limit-bar-title">Indicações este mês</span>
                        <span class="limit-bar-count"><span id="limit-current">0</span> / <span
                                id="limit-max">5</span></span>
                    </div>
                    <div class="limit-bar-track">
                        <div class="limit-bar-fill" id="limit-bar-fill" style="width: 0%"></div>
                    </div>
                    <span class="limit-bar-hint" id="limit-bar-hint">Você pode indicar mais 5 amigos este mês</span>
                </div>

                <div class="referral-stats" id="referral-stats">
                    <div class="stat-item surface-card surface-card--interactive">
                        <span class="stat-value" id="stat-total">-</span>
                        <span class="stat-label">Total</span>
                    </div>
                    <div class="stat-item surface-card surface-card--interactive">
                        <span class="stat-value" id="stat-completed">-</span>
                        <span class="stat-label">Completadas</span>
                    </div>
                    <div class="stat-item surface-card surface-card--interactive">
                        <span class="stat-value" id="stat-days">-</span>
                        <span class="stat-label">Dias ganhos</span>
                    </div>
                </div>

                <div class="referral-share-buttons">
                    <button type="button" class="btn-share whatsapp" id="btn-share-whatsapp"
                        title="Compartilhar no WhatsApp">
                        <i data-lucide="message-circle" style="color:#22c55e"></i>
                        <span>WhatsApp</span>
                    </button>
                    <button type="button" class="btn-share telegram" id="btn-share-telegram"
                        title="Compartilhar no Telegram">
                        <i data-lucide="send" style="color:#0ea5e9"></i>
                        <span>Telegram</span>
                    </button>
                    <button type="button" class="btn-share instagram" id="btn-share-instagram"
                        title="Compartilhar no Instagram">
                        <i data-lucide="camera" style="color:#ec4899"></i>
                        <span>Instagram</span>
                    </button>
                </div>
            </div>
        </div>
    </div><!-- /panel-plano -->
