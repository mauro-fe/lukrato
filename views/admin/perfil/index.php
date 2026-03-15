<!-- CSS carregado via loadPageCss() no header -->

<div class="profile-page">
    <div class="profile-header">
        <!-- Avatar Upload -->
        <?php $avatarUrl = $currentUser?->avatar ? rtrim(BASE_URL, '/') . '/' . $currentUser->avatar : ''; ?>
        <div class="profile-avatar-wrapper">
            <div class="profile-avatar" id="profileAvatar">
                <span class="avatar-initials" id="avatarInitials" <?= $avatarUrl ? 'style="display:none"' : '' ?>><?= mb_substr($topNavFirstName ?: 'U', 0, 1) ?></span>
                <img class="avatar-img" id="avatarImg" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Foto de perfil" <?= $avatarUrl ? '' : 'style="display:none"' ?>>
            </div>
            <button type="button" class="avatar-edit-btn" id="avatarEditBtn" title="Alterar foto de perfil">
                <i data-lucide="camera"></i>
            </button>
            <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp" hidden>
        </div>

        <div class="profile-header-top">
            <h1 class="profile-title">Meu Perfil</h1>

            <button type="button" class="lk-info" data-lk-tooltip-title="Perfil completo"
                data-lk-tooltip="Manter seus dados sempre completos ajuda na segurança da conta, recuperação de acesso, faturamento e melhor funcionamento do Lukrato."
                aria-label="Ajuda: Perfil completo">
                <i data-lucide="info" aria-hidden="true"></i>
            </button>
        </div>

        <p class="profile-subtitle">
            Gerencie suas informações pessoais e configurações de conta
        </p>
    </div>

    <!-- Tab Navigation -->
    <nav class="profile-tabs" role="tablist" aria-label="Seções do perfil">
        <button type="button" class="profile-tab active" data-tab="dados" role="tab" aria-selected="true"
            aria-controls="panel-dados">
            <span class="tab-icon"><i data-lucide="user" style="color:#3b82f6"></i></span>
            <span class="tab-label">Dados Pessoais</span>
        </button>
        <button type="button" class="profile-tab" data-tab="endereco" role="tab" aria-selected="false"
            aria-controls="panel-endereco">
            <span class="tab-icon"><i data-lucide="map-pin" style="color:#ef4444"></i></span>
            <span class="tab-label">Endereço</span>
        </button>
        <button type="button" class="profile-tab" data-tab="seguranca" role="tab" aria-selected="false"
            aria-controls="panel-seguranca">
            <span class="tab-icon"><i data-lucide="lock" style="color:#f59e0b"></i></span>
            <span class="tab-label">Segurança</span>
        </button>
        <button type="button" class="profile-tab" data-tab="plano" role="tab" aria-selected="false"
            aria-controls="panel-plano">
            <span class="tab-icon"><i data-lucide="crown" style="color:#f59e0b"></i></span>
            <span class="tab-label">Plano & Indicação</span>
        </button>
        <button type="button" class="profile-tab" data-tab="integracoes" role="tab" aria-selected="false"
            aria-controls="panel-integracoes">
            <span class="tab-icon"><i data-lucide="plug" style="color:#0ea5e9"></i></span>
            <span class="tab-label">Integrações</span>
        </button>
        <button type="button" class="profile-tab tab-danger" data-tab="perigo" role="tab" aria-selected="false"
            aria-controls="panel-perigo">
            <span class="tab-icon"><i data-lucide="triangle-alert" style="color:#ef4444"></i></span>
            <span class="tab-label">Zona de Perigo</span>
        </button>
    </nav>


    <form id="profileForm" autocomplete="off">
        <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>

        <!-- Tab: Dados Pessoais -->
        <div class="profile-tab-panel active" id="panel-dados" role="tabpanel" aria-labelledby="tab-dados">
            <div class="profile-section">
                <div class="section-header">
                    <div class="section-icon"><i data-lucide="user" style="color: white"></i></div>
                    <div class="section-header-text">
                        <h3>Dados Pessoais</h3>
                        <p>Informações básicas</p>
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="pencil" class="icon-label" style="color:#6366f1"></i>
                            Nome Completo
                            *</label>
                        <input class="form-input" id="nome" name="nome" type="text"
                            placeholder="Digite seu nome completo" required>
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="mail" class="icon-label" style="color:#3b82f6"></i>
                            E-mail *</label>
                        <input class="form-input" id="email" name="email" type="email" placeholder="seu@email.com"
                            required>
                    </div>
                </div>

                <!-- Código de Suporte -->
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="tag" class="icon-label" style="color:#f97316"></i>
                            Código de Suporte</label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input class="form-input" id="support_code" type="text" readonly
                                style="font-family:var(--font-mono);font-weight:600;letter-spacing:1.5px;color:var(--color-primary);background:var(--color-bg-secondary);cursor:default;max-width:220px;"
                                value="Carregando...">
                            <button type="button" class="btn-copy-support" id="btn-copy-support" onclick="copySupportCode()"
                                title="Copiar código"
                                style="padding:8px 12px;border:1px solid var(--color-border);border-radius:8px;background:var(--color-bg);cursor:pointer;color:var(--color-text-muted);transition:all .2s;">
                                <i data-lucide="copy"></i>
                            </button>
                        </div>
                        <small style="color:var(--color-text-muted);font-size:12px;margin-top:4px;display:block;">
                            Use este código ao entrar em contato com o suporte
                        </small>
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="fingerprint" class="icon-label"
                                style="color:#8b5cf6"></i> CPF</label>
                        <input class="form-input" id="cpf" name="cpf" type="text" inputmode="numeric" maxlength="14"
                            placeholder="000.000.000-00">
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="calendar" class="icon-label"
                                style="color:#0ea5e9"></i> Nascimento</label>
                        <input class="form-input" id="data_nascimento" name="data_nascimento" type="date"
                            max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="smartphone" class="icon-label"
                                style="color:#6366f1"></i> Telefone</label>
                        <input class="form-input" id="telefone" name="telefone" type="tel" inputmode="tel"
                            maxlength="15" placeholder="(00) 00000-0000">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="users" class="icon-label" style="color:#14b8a6"></i>
                            Gênero</label>
                        <select class="form-select" name="sexo" id="sexo">
                            <option value="">Selecione</option>
                            <option value="M">Masculino</option>
                            <option value="F">Feminino</option>
                            <option value="O">Outro</option>
                            <option value="NB">Não-binário</option>
                            <option value="N">Prefiro não informar</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save" id="btn-save-dados">
                    <span><i data-lucide="save"></i> Salvar Dados Pessoais</span>
                </button>
            </div>
        </div><!-- /panel-dados -->

        <!-- Tab: Endereço -->
        <div class="profile-tab-panel" id="panel-endereco" role="tabpanel" aria-labelledby="tab-endereco">
            <div class="profile-section">
                <div class="section-header">
                    <div class="section-icon"><i data-lucide="map-pin" style="color:white"></i></div>
                    <div class="section-header-text">
                        <h3>Endereço</h3>
                        <p>Informações de localização</p>
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="mail-open" class="icon-label"
                                style="color:#f97316"></i> CEP</label>
                        <input class="form-input" id="end_cep" name="endereco[cep]" type="text" inputmode="numeric"
                            placeholder="00000-000" maxlength="9">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="map" class="icon-label" style="color:#22c55e"></i>
                            Estado</label>
                        <input class="form-input" id="end_estado" name="endereco[estado]" type="text" placeholder="SP"
                            maxlength="2" style="text-transform: uppercase;">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="building-2" class="icon-label"
                                style="color:#64748b"></i> Cidade</label>
                        <input class="form-input" id="end_cidade" name="endereco[cidade]" type="text"
                            placeholder="São Paulo">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="home" class="icon-label" style="color:#f97316"></i>
                            Bairro</label>
                        <input class="form-input" id="end_bairro" name="endereco[bairro]" type="text"
                            placeholder="Centro">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="route" class="icon-label" style="color:#3b82f6"></i>
                            Rua/Avenida</label>
                        <input class="form-input" id="end_rua" name="endereco[rua]" type="text"
                            placeholder="Rua das Flores">
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="hash" class="icon-label" style="color:#6366f1"></i>
                            Número</label>
                        <input class="form-input" id="end_numero" name="endereco[numero]" type="text" placeholder="123">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="building" class="icon-label"
                                style="color:#64748b"></i> Complemento</label>
                        <input class="form-input" id="end_complemento" name="endereco[complemento]" type="text"
                            placeholder="Apto, Bloco (opcional)">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save" id="btn-save-endereco">
                    <span><i data-lucide="save"></i> Salvar Endereço</span>
                </button>
            </div>
        </div><!-- /panel-endereco -->

        <!-- Tab: Segurança -->
        <div class="profile-tab-panel" id="panel-seguranca" role="tabpanel" aria-labelledby="tab-seguranca">
            <div class="profile-section">
                <div class="section-header">
                    <div class="section-icon"><i data-lucide="lock" style="color:white"></i></div>
                    <div class="section-header-text">
                        <h3>Segurança</h3>
                        <p>Altere sua senha de acesso</p>
                    </div>
                </div>

                <!-- Hidden fields to trick browser autofill -->
                <input type="text" name="_fake_user" style="display:none" tabindex="-1" aria-hidden="true">
                <input type="password" name="_fake_pass" style="display:none" tabindex="-1" aria-hidden="true">

                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="key-round" class="icon-label"
                                style="color:#f59e0b"></i> Senha Atual</label>
                        <input class="form-input" id="senha_atual" name="senha_atual" type="password"
                            placeholder="Digite sua senha atual" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="lock" class="icon-label" style="color:#f59e0b"></i>
                            Nova Senha</label>
                        <input class="form-input" id="nova_senha" name="nova_senha" type="password"
                            placeholder="Mínimo 8 caracteres" autocomplete="off" minlength="8">
                        <div class="pwd-strength" id="pwdStrengthProfile">
                            <div class="pwd-bar-label">
                                <span>Força da senha</span>
                                <span class="pwd-level" id="pwdLevelProfile"></span>
                            </div>
                            <div class="pwd-bar-wrap">
                                <div class="pwd-bar-fill" id="pwdBarFillProfile"></div>
                            </div>
                            <div class="pwd-divider"></div>
                            <div class="pwd-reqs">
                                <div class="pwd-req" id="prof-req-length"><span class="req-icon"></span> 8+ caracteres
                                </div>
                                <div class="pwd-req" id="prof-req-lower"><span class="req-icon"></span> Letra minúscula
                                </div>
                                <div class="pwd-req" id="prof-req-upper"><span class="req-icon"></span> Letra maiúscula
                                </div>
                                <div class="pwd-req" id="prof-req-number"><span class="req-icon"></span> Número</div>
                                <div class="pwd-req" id="prof-req-special"><span class="req-icon"></span> Caractere
                                    especial</div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="circle-check" class="icon-label"
                                style="color:#22c55e"></i> Confirmar
                            Senha</label>
                        <input class="form-input" id="conf_senha" name="conf_senha" type="password"
                            placeholder="Digite novamente" autocomplete="off" minlength="8">
                        <div class="pwd-match" id="pwdMatchProfile">
                            <span class="match-icon"><i data-lucide="check"></i></span>
                            <span class="match-text"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save" id="btn-save-seguranca">
                    <span><i data-lucide="lock-keyhole"></i> Alterar Senha</span>
                </button>
            </div>
        </div><!-- /panel-seguranca -->
    </form>

    <!-- Tab: Plano & Indicação -->
    <div class="profile-tab-panel" id="panel-plano" role="tabpanel" aria-labelledby="tab-plano">
        <!-- Seção de Plano -->
        <div class="profile-section plan-section">
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
                    <div class="current-plan <?= $planClass ?>">
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
                <a href="<?= BASE_URL ?>billing" class="btn-manage-plan <?= $planClass ?>">
                    <i data-lucide="<?= $isPro ? 'settings' : 'rocket' ?>"></i>
                    <span><?= $isPro ? 'Gerenciar Plano' : 'Fazer Upgrade' ?></span>
                </a>
            </div>
        </div>

        <!-- Seção de Indicação -->
        <div class="profile-section referral-section">
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
                        <div class="reward-item">
                            <span class="reward-icon"><i data-lucide="user" style="color:#3b82f6"></i></span>
                            <span class="reward-text">Você ganha <strong>15 dias</strong> de PRO</span>
                        </div>
                        <div class="reward-item">
                            <span class="reward-icon"><i data-lucide="users" style="color:#14b8a6"></i></span>
                            <span class="reward-text">Seu amigo ganha <strong>7 dias</strong> de PRO</span>
                        </div>
                    </div>
                </div>

                <div class="referral-container">
                    <div class="referral-code-container">
                        <label class="referral-label">Seu código de indicação:</label>
                        <div class="referral-code-box">
                            <input type="text" id="referral-code" class="referral-code-input" readonly
                                value="Carregando...">
                            <button type="button" class="btn-copy-code" id="btn-copy-code" title="Copiar código">
                                <i data-lucide="copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="referral-link-container">
                        <label class="referral-label">Ou compartilhe seu link:</label>
                        <div class="referral-link-box">
                            <input type="text" id="referral-link" class="referral-link-input" readonly
                                value="Carregando...">
                            <button type="button" class="btn-copy-link" id="btn-copy-link" title="Copiar link">
                                <i data-lucide="copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Barra de limite mensal -->
                <div class="referral-limit-bar" id="referral-limit-bar">
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
                    <div class="stat-item">
                        <span class="stat-value" id="stat-total">-</span>
                        <span class="stat-label">Total</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" id="stat-completed">-</span>
                        <span class="stat-label">Completadas</span>
                    </div>
                    <div class="stat-item">
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

    <!-- Tab: Integrações -->
    <div class="profile-tab-panel" id="panel-integracoes" role="tabpanel" aria-labelledby="tab-integracoes">
        <!-- WhatsApp (oculto temporariamente — API ainda não disponível)
        <div class="profile-section">
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
        <div class="profile-section">
            <div class="section-header">
                <div class="section-icon" style="background:#0ea5e9"><i data-lucide="send" style="color:white"></i></div>
                <div class="section-header-text">
                    <h3>Telegram</h3>
                    <p>Registre lançamentos enviando mensagens pelo Telegram</p>
                </div>
            </div>

            <div class="integration-card" id="telegram-card">
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
                        <div style="display:flex;align-items:center;gap:8px;margin:12px 0">
                            <input class="form-input" id="telegram-code-display" type="text" readonly
                                style="font-family:var(--font-mono);font-weight:700;font-size:1.5rem;text-align:center;letter-spacing:4px;max-width:200px;color:var(--color-primary);background:var(--color-bg-secondary)">
                            <button type="button" class="btn-copy-support" id="btn-copy-telegram-code" title="Copiar código"
                                style="padding:8px 12px;border:1px solid var(--color-border);border-radius:8px;background:var(--color-bg);cursor:pointer;color:var(--color-text-muted);transition:all .2s;">
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
                        <small style="color:var(--color-text-muted);font-size:12px;margin-top:8px;display:block;">
                            O código expira em 10 minutos
                        </small>
                    </div>
                </div>

                <!-- Estado: Vinculado -->
                <div class="integration-action" id="telegram-linked" style="display:none">
                    <p class="integration-linked-info">
                        <i data-lucide="check-circle" style="color:#0ea5e9;width:18px;height:18px;vertical-align:middle"></i>
                        Telegram vinculado
                    </p>
                    <button type="button" class="btn-integration danger" id="btn-telegram-unlink">
                        <i data-lucide="unlink"></i> Desvincular
                    </button>
                </div>
            </div>
        </div>
    </div><!-- /panel-integracoes -->

    <!-- Tab: Zona de Perigo -->
    <div class="profile-tab-panel" id="panel-perigo" role="tabpanel" aria-labelledby="tab-perigo">
        <div class="profile-section danger-zone">
            <div class="section-header">
                <div class="section-icon"><i data-lucide="triangle-alert" style="color:white"></i></div>
                <div class="section-header-text">
                    <h3>Zona de Perigo</h3>
                    <p>Ações irreversíveis com sua conta</p>
                </div>
            </div>

            <div class="danger-zone-content">
                <div class="danger-zone-info">
                    <h4><i data-lucide="trash-2"
                            style="width:18px;height:18px;display:inline-block;vertical-align:middle;color:#ef4444"></i>
                        Excluir
                        Conta</h4>
                    <p>Esta ação é <strong>permanente e irreversível</strong>. Todos os seus dados serão removidos:</p>
                    <ul>
                        <li><i data-lucide="bar-chart-3"
                                style="width:15px;height:15px;display:inline-block;vertical-align:middle;color:#06b6d4"></i>
                            Todos os
                            lançamentos e histórico financeiro</li>
                        <li><i data-lucide="credit-card"
                                style="width:15px;height:15px;display:inline-block;vertical-align:middle;color:#0ea5e9"></i>
                            Contas e
                            cartões cadastrados</li>
                        <li><i data-lucide="folder-open"
                                style="width:15px;height:15px;display:inline-block;vertical-align:middle;color:#f59e0b"></i>
                            Categorias personalizadas</li>
                        <li><i data-lucide="target"
                                style="width:15px;height:15px;display:inline-block;vertical-align:middle;color:#ef4444"></i>
                            Metas e
                            agendamentos</li>
                        <li><i data-lucide="user"
                                style="width:15px;height:15px;display:inline-block;vertical-align:middle;color:#3b82f6"></i>
                            Informações pessoais</li>
                        <li><i data-lucide="gem"
                                style="width:15px;height:15px;display:inline-block;vertical-align:middle;color:#a855f7"></i>
                            Plano
                            PRO (se ativo) será cancelado automaticamente</li>
                    </ul>
                </div>
                <button type="button" class="btn-delete-account" id="btn-delete-account">
                    <i data-lucide="trash-2"></i>
                    <span>Excluir Minha Conta</span>
                </button>
            </div>
        </div>
    </div><!-- /panel-perigo -->
</div>



<!-- JS carregado via Vite (loadPageJs) -->
