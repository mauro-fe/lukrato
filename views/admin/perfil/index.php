<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-perfil-index.css">

<div class="profile-page">
    <div class="profile-header">
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
        <button type="button" class="profile-tab active" data-tab="dados" role="tab"
            aria-selected="true" aria-controls="panel-dados">
            <span class="tab-icon">👤</span>
            <span class="tab-label">Dados Pessoais</span>
        </button>
        <button type="button" class="profile-tab" data-tab="endereco" role="tab"
            aria-selected="false" aria-controls="panel-endereco">
            <span class="tab-icon">📍</span>
            <span class="tab-label">Endereço</span>
        </button>
        <button type="button" class="profile-tab" data-tab="seguranca" role="tab"
            aria-selected="false" aria-controls="panel-seguranca">
            <span class="tab-icon">🔒</span>
            <span class="tab-label">Segurança</span>
        </button>
        <button type="button" class="profile-tab" data-tab="plano" role="tab"
            aria-selected="false" aria-controls="panel-plano">
            <span class="tab-icon">👑</span>
            <span class="tab-label">Plano & Indicação</span>
        </button>
        <button type="button" class="profile-tab tab-danger" data-tab="perigo" role="tab"
            aria-selected="false" aria-controls="panel-perigo">
            <span class="tab-icon">⚠️</span>
            <span class="tab-label">Zona de Perigo</span>
        </button>
    </nav>


    <form id="profileForm" autocomplete="off">
        <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>

        <!-- Tab: Dados Pessoais -->
        <div class="profile-tab-panel active" id="panel-dados" role="tabpanel" aria-labelledby="tab-dados">
        <div class="profile-section">
                <div class="section-header">
                    <div class="section-icon">👤</div>
                    <div class="section-header-text">
                        <h3>Dados Pessoais</h3>
                        <p>Informações básicas</p>
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="pencil" class="icon-label"></i> Nome Completo *</label>
                        <input class="form-input" id="nome" name="nome" type="text"
                            placeholder="Digite seu nome completo" required>
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="mail" class="icon-label"></i> E-mail *</label>
                        <input class="form-input" id="email" name="email" type="email" placeholder="seu@email.com"
                            required>
                    </div>
                </div>

                <!-- Código de Suporte -->
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="tag" class="icon-label"></i> Código de Suporte</label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input class="form-input" id="support_code" type="text" readonly
                                style="font-family:'JetBrains Mono',monospace;font-weight:600;letter-spacing:1.5px;color:var(--color-primary);background:var(--color-bg-secondary);cursor:default;max-width:220px;"
                                value="Carregando...">
                            <button type="button" class="btn-copy-support" onclick="copySupportCode()" title="Copiar código"
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
                        <label class="form-label"><i data-lucide="fingerprint" class="icon-label"></i> CPF</label>
                        <input class="form-input" id="cpf" name="cpf" type="text" inputmode="numeric" maxlength="14"
                            placeholder="000.000.000-00">
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="calendar" class="icon-label"></i> Nascimento</label>
                        <input class="form-input" id="data_nascimento" name="data_nascimento" type="date"
                            max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="smartphone" class="icon-label"></i> Telefone</label>
                        <input class="form-input" id="telefone" name="telefone" type="tel" inputmode="tel"
                            maxlength="15" placeholder="(00) 00000-0000">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="users" class="icon-label"></i> Gênero</label>
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
                    <span>💾 Salvar Dados Pessoais</span>
                </button>
            </div>
        </div><!-- /panel-dados -->

        <!-- Tab: Endereço -->
        <div class="profile-tab-panel" id="panel-endereco" role="tabpanel" aria-labelledby="tab-endereco">
            <div class="profile-section">
                <div class="section-header">
                    <div class="section-icon">📍</div>
                    <div class="section-header-text">
                        <h3>Endereço</h3>
                        <p>Informações de localização</p>
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="mail-open" class="icon-label"></i> CEP</label>
                        <input class="form-input" id="end_cep" name="endereco[cep]" type="text" inputmode="numeric"
                            placeholder="00000-000" maxlength="9">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="map" class="icon-label"></i> Estado</label>
                        <input class="form-input" id="end_estado" name="endereco[estado]" type="text" placeholder="SP"
                            maxlength="2" style="text-transform: uppercase;">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="building-2" class="icon-label"></i> Cidade</label>
                        <input class="form-input" id="end_cidade" name="endereco[cidade]" type="text"
                            placeholder="São Paulo">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="home" class="icon-label"></i> Bairro</label>
                        <input class="form-input" id="end_bairro" name="endereco[bairro]" type="text"
                            placeholder="Centro">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="route" class="icon-label"></i> Rua/Avenida</label>
                        <input class="form-input" id="end_rua" name="endereco[rua]" type="text"
                            placeholder="Rua das Flores">
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="hash" class="icon-label"></i> Número</label>
                        <input class="form-input" id="end_numero" name="endereco[numero]" type="text" placeholder="123">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="building" class="icon-label"></i> Complemento</label>
                        <input class="form-input" id="end_complemento" name="endereco[complemento]" type="text"
                            placeholder="Apto, Bloco (opcional)">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save" id="btn-save-endereco">
                    <span>💾 Salvar Endereço</span>
                </button>
            </div>
        </div><!-- /panel-endereco -->

        <!-- Tab: Segurança -->
        <div class="profile-tab-panel" id="panel-seguranca" role="tabpanel" aria-labelledby="tab-seguranca">
        <div class="profile-section">
            <div class="section-header">
                <div class="section-icon">🔒</div>
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
                    <label class="form-label"><i data-lucide="key-round" class="icon-label"></i> Senha Atual</label>
                    <input class="form-input" id="senha_atual" name="senha_atual" type="password"
                        placeholder="Digite sua senha atual" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label"><i data-lucide="lock" class="icon-label"></i> Nova Senha</label>
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
                            <div class="pwd-req" id="prof-req-length"><span class="req-icon"></span> 8+ caracteres</div>
                            <div class="pwd-req" id="prof-req-lower"><span class="req-icon"></span> Letra minúscula</div>
                            <div class="pwd-req" id="prof-req-upper"><span class="req-icon"></span> Letra maiúscula</div>
                            <div class="pwd-req" id="prof-req-number"><span class="req-icon"></span> Número</div>
                            <div class="pwd-req" id="prof-req-special"><span class="req-icon"></span> Caractere especial</div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><i data-lucide="circle-check" class="icon-label"></i> Confirmar Senha</label>
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
                <span>🔐 Alterar Senha</span>
            </button>
        </div>
        </div><!-- /panel-seguranca -->
    </form>

    <!-- Tab: Plano & Indicação -->
    <div class="profile-tab-panel" id="panel-plano" role="tabpanel" aria-labelledby="tab-plano">
    <!-- Seção de Plano -->
    <div class="profile-section plan-section">
        <div class="section-header">
            <div class="section-icon">👑</div>
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
                        Faça upgrade para desbloquear recursos avançados como importação automática, relatórios detalhados e
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
            <div class="section-icon">🎁</div>
            <div class="section-header-text">
                <h3>Indique Amigos</h3>
                <p>Ganhe dias de PRO por cada indicação</p>
            </div>
        </div>

        <div class="referral-section-content">
            <div class="referral-info">
                <div class="referral-reward-info">
                    <div class="reward-item">
                        <span class="reward-icon">👤</span>
                        <span class="reward-text">Você ganha <strong>15 dias</strong> de PRO</span>
                    </div>
                    <div class="reward-item">
                        <span class="reward-icon">👥</span>
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
                    <span class="limit-bar-count"><span id="limit-current">0</span> / <span id="limit-max">5</span></span>
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
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>WhatsApp</span>
                </button>
                <button type="button" class="btn-share telegram" id="btn-share-telegram"
                    title="Compartilhar no Telegram">
                    <i class="fa-brands fa-telegram"></i>
                    <span>Telegram</span>
                </button>
                <button type="button" class="btn-share instagram" id="btn-share-instagram"
                    title="Compartilhar no Instagram">
                    <i class="fa-brands fa-instagram"></i>
                    <span>Instagram</span>
                </button>
            </div>
        </div>
    </div>
    </div><!-- /panel-plano -->

    <!-- Tab: Zona de Perigo -->
    <div class="profile-tab-panel" id="panel-perigo" role="tabpanel" aria-labelledby="tab-perigo">
    <div class="profile-section danger-zone">
        <div class="section-header">
            <div class="section-icon">⚠️</div>
            <div class="section-header-text">
                <h3>Zona de Perigo</h3>
                <p>Ações irreversíveis com sua conta</p>
            </div>
        </div>

        <div class="danger-zone-content">
            <div class="danger-zone-info">
                <h4>🗑️ Excluir Conta</h4>
                <p>Esta ação é <strong>permanente e irreversível</strong>. Todos os seus dados serão removidos:</p>
                <ul>
                    <li>📊 Todos os lançamentos e histórico financeiro</li>
                    <li>💳 Contas e cartões cadastrados</li>
                    <li>📂 Categorias personalizadas</li>
                    <li>🎯 Metas e agendamentos</li>
                    <li>👤 Informações pessoais</li>
                    <li>💎 Plano PRO (se ativo) será cancelado automaticamente</li>
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

<script>
    (() => {
        'use strict';

        const BASE = (() => {
            const meta = document.querySelector('meta[name="base-url"]')?.content || '';
            return meta.replace(/\/?$/, '/');
        })();

        const API = `${BASE}api/`;
        const form = document.getElementById('profileForm');

        // ============================================
        // TAB SWITCHING
        // ============================================
        const tabs = document.querySelectorAll('.profile-tab');
        const panels = document.querySelectorAll('.profile-tab-panel');

        function switchTab(tabId) {
            tabs.forEach(t => {
                const isActive = t.dataset.tab === tabId;
                t.classList.toggle('active', isActive);
                t.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            panels.forEach(p => {
                p.classList.toggle('active', p.id === `panel-${tabId}`);
            });
            // Persist
            try { localStorage.setItem('perfil_tab', tabId); } catch(e) {}
            // Update hash without scroll
            history.replaceState(null, '', `#${tabId}`);
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', () => switchTab(tab.dataset.tab));
        });

        // Restore tab from hash or localStorage
        (() => {
            const hash = location.hash.replace('#', '');
            const validTabs = ['dados', 'endereco', 'seguranca', 'plano', 'perigo'];
            let initial = 'dados';
            if (hash && validTabs.includes(hash)) {
                initial = hash;
            } else {
                try {
                    const stored = localStorage.getItem('perfil_tab');
                    if (stored && validTabs.includes(stored)) initial = stored;
                } catch(e) {}
            }
            if (initial !== 'dados') switchTab(initial);
        })();

        // Campos do formulário
        const fieldNome = document.getElementById('nome');
        const fieldEmail = document.getElementById('email');
        const fieldCpf = document.getElementById('cpf');
        const fieldData = document.getElementById('data_nascimento');
        const fieldTelefone = document.getElementById('telefone');
        const fieldSexo = document.getElementById('sexo');
        const fieldCep = document.getElementById('end_cep');
        const fieldRua = document.getElementById('end_rua');
        const fieldNumero = document.getElementById('end_numero');
        const fieldComplemento = document.getElementById('end_complemento');
        const fieldBairro = document.getElementById('end_bairro');
        const fieldCidade = document.getElementById('end_cidade');
        const fieldEstado = document.getElementById('end_estado');

        function maskCEP(value) {
            const digits = value.replace(/\D/g, '');
            if (digits.length <= 5) return digits;
            return digits.substring(0, 5) + '-' + digits.substring(5, 8);
        }

        async function loadProfile() {
            if (!form) return;
            try {
                const res = await fetch(`${API}perfil`, {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const j = await res.json().catch(() => null);
                if (!res.ok || j?.status !== 'success') {
                    throw new Error(j?.message || 'Falha ao carregar perfil.');
                }

                const user = j?.data?.user || {};

                if (fieldNome) fieldNome.value = user.nome || '';
                if (fieldEmail) fieldEmail.value = user.email || '';

                // Código de suporte
                const supportCodeField = document.getElementById('support_code');
                if (supportCodeField) supportCodeField.value = user.support_code || '-';

                if (fieldCpf) fieldCpf.value = user.cpf || '';
                if (fieldData) fieldData.value = user.data_nascimento || '';
                if (fieldTelefone) fieldTelefone.value = user.telefone || '';
                if (fieldSexo) fieldSexo.value = user.sexo || '';

                const endereco = user.endereco || {};
                if (fieldCep) fieldCep.value = maskCEP(endereco.cep || '');
                if (fieldRua) fieldRua.value = endereco.rua || '';
                if (fieldNumero) fieldNumero.value = endereco.numero || '';
                if (fieldComplemento) fieldComplemento.value = endereco.complemento || '';
                if (fieldBairro) fieldBairro.value = endereco.bairro || '';
                if (fieldCidade) fieldCidade.value = endereco.cidade || '';
                if (fieldEstado) fieldEstado.value = endereco.estado || '';

            } catch (err) {
                console.error('Erro ao carregar perfil:', err);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro ao carregar',
                        text: err.message || 'Não foi possível carregar o perfil.',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            }
        }

        function extractApiError(payload, fallback = 'Falha ao salvar.') {
            if (!payload) return fallback;
            const {
                errors
            } = payload;
            if (errors) {
                if (typeof errors === 'string') return errors;
                if (Array.isArray(errors)) return errors.filter(Boolean).join('\n');
                if (typeof errors === 'object') {
                    const messages = [];
                    Object.values(errors).forEach((val) => {
                        if (Array.isArray(val)) {
                            messages.push(...val.filter(Boolean).map(String));
                        } else if (val) {
                            messages.push(String(val));
                        }
                    });
                    if (messages.length) return messages.join('\n');
                }
            }
            return payload.message || fallback;
        }

        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            form.classList.add('form-loading');

            const submitBtn = e.submitter || form.querySelector('.btn-save');
            const originalContent = submitBtn?.innerHTML || '';
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="spinner"></span><span>Salvando...</span>';
                submitBtn.disabled = true;
            }

            const fd = new FormData(form);

            try {
                const r = await fetch(`${API}perfil`, {
                    method: 'POST',
                    credentials: 'include',
                    body: fd,
                    headers: {
                        'Accept': 'application/json'
                    }
                });


                const j = await r.json().catch(() => null);
                if (!r.ok || j?.status === 'error') {
                    throw new Error(extractApiError(j, 'Falha ao salvar.'));
                }

                // 🎮 GAMIFICAÇÃO: Exibir conquistas se houver
                if (j?.data?.new_achievements && Array.isArray(j.data.new_achievements)) {
                    if (typeof window.notifyMultipleAchievements === 'function') {
                        window.notifyMultipleAchievements(j.data.new_achievements);
                    }
                }

                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Perfil atualizado!',
                        text: 'Suas informações foram salvas com sucesso.',
                        confirmButtonColor: '#e67e22',
                        timer: 2000
                    });
                }

                const saveStatus = document.getElementById('save-status');
                if (saveStatus) {
                    saveStatus.innerHTML = '✓ Tudo salvo';
                    saveStatus.style.color = '#27ae60';
                }

                await loadProfile();

            } catch (err) {
                console.error('Erro ao salvar:', err);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro ao salvar',
                        text: err.message || 'Erro ao salvar perfil.',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            } finally {
                form.classList.remove('form-loading');
                if (submitBtn) {
                    submitBtn.innerHTML = originalContent;
                    submitBtn.disabled = false;
                }
            }
        });

        // Botão de excluir conta
        const btnDelete = document.getElementById('btn-delete-account');
        if (btnDelete) {
            btnDelete.addEventListener('click', async () => {
                if (!window.Swal) {
                    if (!confirm(
                            'ATENÇÃO: Esta ação é irreversível! Deseja realmente excluir sua conta e todos os dados?'
                        )) return;
                } else {
                    const result = await Swal.fire({
                        title: '⚠️ Confirmar Exclusão de Conta',
                        html: `
                        <div style="text-align: left; padding: 1rem;">
                            <p style="font-size: 1.1rem; margin-bottom: 1rem;"><strong>Esta ação é permanente e irreversível!</strong></p>
                            <p style="margin-bottom: 0.5rem;">Ao confirmar, os seguintes dados serão <strong>permanentemente deletados</strong>:</p>
                            <ul style="margin: 1rem 0; padding-left: 1.5rem;">
                                <li>Todos os lançamentos e histórico financeiro</li>
                                <li>Contas e cartões cadastrados</li>
                                <li>Categorias personalizadas</li>
                                <li>Metas e agendamentos</li>
                                <li>Informações pessoais</li>
                                <li>Plano PRO (será cancelado automaticamente)</li>
                            </ul>
                            <p style="color: #e74c3c; font-weight: bold; margin-top: 1rem;">⚠️ Não será possível recuperar estes dados!</p>
                            <p style="color: #7f8c8d; font-size: 0.9rem; margin-top: 1rem;">📋 Após a exclusão, você precisará aguardar <strong>90 dias</strong> para criar uma nova conta com o mesmo email.</p>
                        </div>
                    `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e74c3c',
                        cancelButtonColor: '#95a5a6',
                        confirmButtonText: 'Sim, excluir minha conta',
                        cancelButtonText: 'Cancelar',
                        focusCancel: true
                    });

                    if (!result.isConfirmed) return;

                    // Segunda confirmação
                    const finalConfirm = await Swal.fire({
                        title: 'Última confirmação',
                        text: 'Digite "EXCLUIR" para confirmar a exclusão definitiva da sua conta',
                        input: 'text',
                        inputPlaceholder: 'Digite: EXCLUIR',
                        showCancelButton: true,
                        confirmButtonColor: '#e74c3c',
                        cancelButtonColor: '#95a5a6',
                        confirmButtonText: 'Confirmar Exclusão',
                        cancelButtonText: 'Cancelar',
                        inputValidator: (value) => {
                            if (value !== 'EXCLUIR') {
                                return 'Você precisa digitar "EXCLUIR" para confirmar';
                            }
                        }
                    });

                    if (!finalConfirm.isConfirmed) return;
                }

                try {
                    Swal.fire({
                        title: 'Excluindo conta...',
                        text: 'Por favor aguarde',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const res = await fetch(`${API}perfil/delete`, {
                        method: 'DELETE',
                        credentials: 'include',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')
                                ?.content || ''
                        }
                    });

                    const data = await res.json();

                    if (!res.ok || data.status !== 'success') {
                        throw new Error(data.message || 'Erro ao excluir conta');
                    }

                    await Swal.fire({
                        icon: 'success',
                        title: '✅ Conta excluída!',
                        html: `
                            <div style="text-align: center;">
                                <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Sua conta foi excluída com sucesso.</p>
                                <p style="color: #666;">Você será redirecionado para a página inicial...</p>
                            </div>
                        `,
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        allowOutsideClick: false
                    });

                    window.location.href = BASE + 'logout';

                } catch (err) {
                    console.error('Erro ao excluir conta:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: err.message || 'Não foi possível excluir a conta. Tente novamente.'
                    });
                }
            });
        }

        // ============================================
        // SISTEMA DE INDICAÇÃO
        // ============================================

        async function loadReferralStats() {
            try {
                const res = await fetch(`${API}referral/stats`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();

                if (res.ok && data.data) {
                    const stats = data.data;

                    // Atualiza código e link
                    const codeInput = document.getElementById('referral-code');
                    const linkInput = document.getElementById('referral-link');

                    if (codeInput) codeInput.value = stats.referral_code || '';
                    if (linkInput) linkInput.value = stats.referral_link || '';

                    // Atualiza estatísticas
                    document.getElementById('stat-total').textContent = stats.total_indicacoes || 0;
                    document.getElementById('stat-completed').textContent = stats.indicacoes_completadas || 0;
                    document.getElementById('stat-days').textContent = stats.dias_ganhos || 0;

                    // Atualiza barra de limite mensal
                    const current = stats.indicacoes_mes || 0;
                    const max = stats.limite_mensal || 5;
                    const remaining = stats.indicacoes_restantes ?? max;
                    const percentage = Math.min((current / max) * 100, 100);

                    document.getElementById('limit-current').textContent = current;
                    document.getElementById('limit-max').textContent = max;

                    const barFill = document.getElementById('limit-bar-fill');
                    const barHint = document.getElementById('limit-bar-hint');
                    const limitBar = document.getElementById('referral-limit-bar');

                    if (barFill) {
                        barFill.style.width = percentage + '%';

                        // Muda cor conforme enche
                        if (percentage >= 100) {
                            barFill.classList.add('full');
                            barFill.classList.remove('warning');
                        } else if (percentage >= 80) {
                            barFill.classList.add('warning');
                            barFill.classList.remove('full');
                        } else {
                            barFill.classList.remove('warning', 'full');
                        }
                    }

                    if (barHint) {
                        if (remaining === 0) {
                            barHint.textContent = '🔒 Limite atingido! Renova no próximo mês';
                            barHint.classList.add('limit-reached');
                        } else if (remaining === 1) {
                            barHint.textContent = '⚡ Última indicação disponível este mês';
                            barHint.classList.remove('limit-reached');
                        } else {
                            barHint.textContent = `Você pode indicar mais ${remaining} amigos este mês`;
                            barHint.classList.remove('limit-reached');
                        }
                    }
                }
            } catch (err) {
                console.error('Erro ao carregar estatísticas de indicação:', err);
            }
        }

        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const originalIcon = button.innerHTML;
                button.innerHTML = '<i data-lucide="check"></i>';
                button.classList.add('copied');

                setTimeout(() => {
                    button.innerHTML = originalIcon;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                console.error('Erro ao copiar:', err);
            });
        }

        // Botões de copiar
        document.getElementById('btn-copy-code')?.addEventListener('click', () => {
            const code = document.getElementById('referral-code')?.value;
            if (code) copyToClipboard(code, document.getElementById('btn-copy-code'));
        });

        document.getElementById('btn-copy-link')?.addEventListener('click', () => {
            const link = document.getElementById('referral-link')?.value;
            if (link) copyToClipboard(link, document.getElementById('btn-copy-link'));
        });

        // Botões de compartilhamento
        document.getElementById('btn-share-whatsapp')?.addEventListener('click', () => {
            const link = document.getElementById('referral-link')?.value;
            const text = encodeURIComponent(
                `🎁 Use meu código e ganhe 7 dias de PRO grátis no Lukrato!\n\n${link}`);
            window.open(`https://wa.me/?text=${text}`, '_blank');
        });

        document.getElementById('btn-share-telegram')?.addEventListener('click', () => {
            const link = document.getElementById('referral-link')?.value;
            const text = encodeURIComponent(`🎁 Use meu código e ganhe 7 dias de PRO grátis no Lukrato!`);
            window.open(`https://t.me/share/url?url=${encodeURIComponent(link)}&text=${text}`, '_blank');
        });

        document.getElementById('btn-share-instagram')?.addEventListener('click', () => {
            const link = document.getElementById('referral-link')?.value;
            navigator.clipboard.writeText(link).then(() => {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Link copiado!',
                        html: 'Cole nos seus Stories ou Direct do Instagram!',
                        confirmButtonColor: '#e67e22',
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    alert('Link copiado! Cole nos seus Stories ou Direct do Instagram.');
                }
            });
        });

        // Carregar estatísticas de indicação
        loadReferralStats();

        // Carregar perfil ao iniciar
        loadProfile();
    })();

    function copySupportCode() {
        const input = document.getElementById('support_code');
        const btn = document.getElementById('btn-copy-support');
        if (!input || !input.value) return;

        const originalIcon = btn.innerHTML;
        navigator.clipboard.writeText(input.value).then(() => {
            btn.innerHTML = '<i data-lucide="check"></i>';
            btn.style.color = '#22c55e';
            setTimeout(() => {
                btn.innerHTML = originalIcon;
                btn.style.color = '';
            }, 2000);
        }).catch(() => {
            input.select();
            document.execCommand('copy');
            btn.innerHTML = '<i data-lucide="check"></i>';
            btn.style.color = '#22c55e';
            setTimeout(() => {
                btn.innerHTML = originalIcon;
                btn.style.color = '';
            }, 2000);
        });
    }
</script>

<script>
// Real-time password strength + confirm match (Profile)
(function() {
    var pwd = document.getElementById('nova_senha');
    var confirm = document.getElementById('conf_senha');
    var panel = document.getElementById('pwdStrengthProfile');
    var barFill = document.getElementById('pwdBarFillProfile');
    var levelEl = document.getElementById('pwdLevelProfile');
    var matchEl = document.getElementById('pwdMatchProfile');
    if (!pwd || !confirm || !panel) return;

    var rules = [
        { id: 'prof-req-length',  test: function(v) { return v.length >= 8; } },
        { id: 'prof-req-lower',   test: function(v) { return /[a-z]/.test(v); } },
        { id: 'prof-req-upper',   test: function(v) { return /[A-Z]/.test(v); } },
        { id: 'prof-req-number',  test: function(v) { return /[0-9]/.test(v); } },
        { id: 'prof-req-special', test: function(v) { return /[^a-zA-Z0-9]/.test(v); } }
    ];

    var levels = [
        { cls: '',   label: '' },
        { cls: 's1', label: 'Muito fraca' },
        { cls: 's2', label: 'Fraca' },
        { cls: 's3', label: 'Razoável' },
        { cls: 's4', label: 'Boa' },
        { cls: 's5', label: 'Forte' }
    ];

    pwd.addEventListener('focus', function() {
        panel.classList.add('visible');
    });

    pwd.addEventListener('input', function() {
        var val = pwd.value;
        var score = 0;

        if (!val) {
            panel.classList.remove('visible');
            barFill.className = 'pwd-bar-fill';
            levelEl.className = 'pwd-level';
            levelEl.textContent = '';
            rules.forEach(function(rule) {
                var el = document.getElementById(rule.id);
                if (el) el.classList.remove('pass');
            });
            return;
        }

        panel.classList.add('visible');

        rules.forEach(function(rule) {
            var el = document.getElementById(rule.id);
            var passed = rule.test(val);
            if (el) el.classList.toggle('pass', passed);
            if (passed) score++;
        });

        barFill.className = 'pwd-bar-fill' + (score > 0 ? ' s' + score : '');
        levelEl.className = 'pwd-level' + (score > 0 ? ' s' + score : '');
        levelEl.textContent = levels[score].label;

        if (confirm.value) checkMatch();
    });

    function checkMatch() {
        var pVal = pwd.value;
        var cVal = confirm.value;
        if (!cVal) {
            matchEl.classList.remove('visible');
            return;
        }
        matchEl.classList.add('visible');
        var ok = pVal === cVal;
        matchEl.classList.toggle('match', ok);
        matchEl.classList.toggle('no-match', !ok);
        var icon = matchEl.querySelector('.match-icon');
        var text = matchEl.querySelector('.match-text');
        icon.innerHTML = ok ? '<i data-lucide="check"></i>' : '<i data-lucide="x"></i>';
        text.textContent = ok ? 'Senhas coincidem' : 'Senhas não coincidem';
    }

    confirm.addEventListener('input', checkMatch);
    pwd.addEventListener('input', function() {
        if (confirm.value) checkMatch();
    });
})();
</script>

<script src="<?= BASE_URL ?>assets/js/admin-profile-edit.js"></script>