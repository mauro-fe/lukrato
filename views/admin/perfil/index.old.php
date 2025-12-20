<style>
    * {
        box-sizing: border-box;
    }

    .profile-container {
        max-width: 100%;
        margin: 0 auto;
        padding: 2rem 3rem;
    }

    @media (max-width: 1200px) {
        .profile-container {
            padding: 2rem 2rem;
        }
    }

    @media (max-width: 768px) {
        .profile-container {
            padding: 2rem 1rem;
        }
    }

    .profile-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .profile-title {
        font-size: 2.5rem;
        font-weight: 800;
        background: linear-gradient(135deg, #e67e22, #d35400);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.5rem;
    }

    .profile-subtitle {
        color: #7f8c8d;
        font-size: 1rem;
    }

    /* Layout em Grid */
    .profile-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 1024px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Card com Seções */
    .profile-section {
        background: rgba(240, 246, 252, 0.7);
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        padding: 2rem;
        margin-bottom: 2rem;
        border: 1px solid var(--glass-border);
        transition: all 0.3s ease;
        backdrop-filter: blur(12px) saturate(180%);
        -webkit-backdrop-filter: blur(12px) saturate(180%);
    }

    :root[data-theme="dark"] .profile-section {
        background: rgba(28, 44, 60, 0.7);
    }

    .profile-section:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--glass-border);
    }

    .section-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #e67e22, #d35400);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
    }

    .section-header-text h3 {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--color-text);
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .section-header-text p {
        color: var(--color-text-muted);
        font-size: 0.875rem;
        margin: 0.25rem 0 0 0;
    }

    /* Form Groups */
    .form-row {
        display: grid;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-row.cols-1 { grid-template-columns: 1fr; }
    .form-row.cols-2 { grid-template-columns: repeat(2, 1fr); }
    .form-row.cols-3 { grid-template-columns: repeat(3, 1fr); }

    @media (max-width: 768px) {
        .form-row.cols-2,
        .form-row.cols-3 {
            grid-template-columns: 1fr;
        }
    }

    .form-group {
        position: relative;
    }

    .form-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        color: #34495e;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-label .emoji {
        font-size: 1rem;
    }

    .input-wrapper {
        position: relative;
    }

    .form-input,
    .form-select {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid var(--glass-border);
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: var(--glass-bg);
        font-family: inherit;
        color: var(--color-text);
    }

    .form-input:hover {
        border-color: var(--color-surface-muted);
    }

    .form-input:focus,
    .form-select:focus {
        outline: none;
        border-color: #e67e22;
        background: var(--color-surface);
        box-shadow: 0 0 0 4px rgba(230, 126, 34, 0.08);
        transform: translateY(-1px);
    }

    .form-input.is-valid {
        border-color: #27ae60;
        background: #f0fdf4;
    }

    .form-input.is-invalid {
        border-color: #e74c3c;
        background: #fef2f2;
    }

    .form-input:disabled {
        background: #f5f5f5;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .input-hint {
        font-size: 0.75rem;
        color: #95a5a6;
        margin-top: 0.375rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .input-feedback {
        font-size: 0.75rem;
        margin-top: 0.375rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .input-feedback.success {
        color: #27ae60;
    }

    .input-feedback.error {
        color: #e74c3c;
    }

    /* Password Strength */
    .password-strength {
        margin-top: 0.5rem;
        height: 4px;
        background: var(--glass-border);
        border-radius: 4px;
        overflow: hidden;
    }

    .password-strength-bar {
        height: 100%;
        transition: all 0.3s ease;
        border-radius: 4px;
    }

    .password-strength.weak .password-strength-bar {
        width: 33%;
        background: #e74c3c;
    }

    .password-strength.medium .password-strength-bar {
        width: 66%;
        background: #f39c12;
    }

    .password-strength.strong .password-strength-bar {
        width: 100%;
        background: #27ae60;
    }

    /* Buttons */
    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 2px solid var(--glass-border);
    }

    .save-status {
        color: #95a5a6;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn {
        padding: 1rem 2.5rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #e67e22, #d35400);
        color: white;
        box-shadow: 0 4px 16px rgba(230, 126, 34, 0.4);
    }

    .btn-primary:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(230, 126, 34, 0.5);
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Zona de Perigo */
    .settings-danger {
        background: rgba(240, 246, 252, 0.7);
        border: 2px solid #fee2e2;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        backdrop-filter: blur(12px) saturate(180%);
        -webkit-backdrop-filter: blur(12px) saturate(180%);
    }

    :root[data-theme="dark"] .settings-danger {
        background: rgba(28, 44, 60, 0.7);
    }

    .settings-danger h2 {
        color: #e74c3c;
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .settings-danger p {
        color: #666;
        margin-bottom: 1.5rem;
        line-height: 1.7;
    }

    .lk-btn-danger {
        background: #e74c3c;
        color: white;
        border-radius: 12px;
        padding: 0.875rem 1.75rem;
        border: none;
        cursor: pointer;
        font-weight: 700;
        transition: all 0.3s ease;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .lk-btn-danger:hover {
        background: #c0392b;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
    }

    /* Modal */
    .lk-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 999;
        padding: 1rem;
    }

    .lk-modal.is-visible {
        display: flex;
    }

    .lk-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(8px);
    }

    .lk-modal-dialog {
        position: relative;
        max-width: 500px;
        width: 100%;
        background: var(--color-surface);
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
        z-index: 1;
        animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-30px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .lk-modal-header h3 {
        font-size: 1.75rem;
        font-weight: 800;
        color: #2c3e50;
        margin-bottom: 1rem;
    }

    .lk-modal-body p {
        color: #555;
        line-height: 1.7;
        margin-bottom: 1rem;
    }

    .lk-modal-warning-list {
        margin: 1.25rem 0 1.75rem;
        padding-left: 1.5rem;
        font-size: 0.9rem;
        color: #666;
        line-height: 2;
    }

    .field {
        margin-bottom: 1.5rem;
    }

    .field-label {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
        display: block;
    }

    .field input {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid var(--glass-border);
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: var(--glass-bg);
        color: var(--color-text);
    }

    .field input:focus {
        outline: none;
        border-color: #e67e22;
        box-shadow: 0 0 0 4px rgba(230, 126, 34, 0.08);
    }

    .lk-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 2px solid var(--glass-border);
    }

    .lk-btn {
        padding: 0.875rem 1.75rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .lk-btn-ghost {
        background: var(--glass-bg);
        color: var(--color-text);
    }

    .lk-btn-ghost:hover {
        background: var(--glass-border);
    }

    .form-loading {
        opacity: 0.6;
        pointer-events: none;
    }

    /* Loading Spinner */
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }
</style>

<?php
$pageTitle = $pageTitle ?? 'Meu Perfil';
$menu      = $menu ?? 'perfil';
?>

<div class="profile-container">
    <div class="profile-header">
        <h1 class="profile-title">Meu Perfil</h1>
        <p class="profile-subtitle">Gerencie suas informações pessoais e configurações de conta</p>
    </div>

    <form id="profileForm">
        <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>

        <div class="profile-grid">
            <!-- Dados Pessoais -->
            <div class="profile-section" data-aos="fade-up">
                <div class="section-header">
                    <div class="section-icon">👤</div>
                    <div class="section-header-text">
                        <h3>Dados Pessoais</h3>
                        <p>Informações básicas da sua conta</p>
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">✏️</span> Nome Completo *</label>
                        <input class="form-input" id="nome" name="nome" type="text" 
                               placeholder="Digite seu nome completo" required>
                        <div class="input-hint">💡 Este nome será exibido em seus relatórios</div>
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">📧</span> E-mail *</label>
                        <input class="form-input" id="email" name="email" type="email" 
                               placeholder="seu@email.com" required>
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🆔</span> CPF</label>
                        <input class="form-input" id="cpf" name="cpf" type="text" 
                               inputmode="numeric" maxlength="14" placeholder="000.000.000-00">
                        <div class="input-feedback" id="cpf-feedback"></div>
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">📅</span> Nascimento</label>
                        <input class="form-input" id="data_nascimento" name="data_nascimento" 
                               type="date" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">📱</span> Telefone</label>
                        <input class="form-input" id="telefone" name="telefone" type="tel" 
                               inputmode="tel" maxlength="15" placeholder="(00) 00000-0000">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">⚧️</span> Sexo</label>
                        <select class="form-select" name="sexo" id="sexo">
                            <option value="">Selecione</option>
                            <option value="M">Masculino</option>
                            <option value="F">Feminino</option>
                            <option value="O">Outro</option>
                            <option value="N">Prefiro não informar</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div class="profile-section" data-aos="fade-up" data-aos-delay="100">
                <div class="section-header">
                    <div class="section-icon">📍</div>
                    <div class="section-header-text">
                        <h3>Endereço</h3>
                        <p>Informações de localização</p>
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">📮</span> CEP</label>
                        <input class="form-input" id="end_cep" name="endereco[cep]" type="text" 
                               inputmode="numeric" placeholder="00000-000" maxlength="9">
                        <div class="input-hint">💡 Busca automática</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🗺️</span> Estado</label>
                        <input class="form-input" id="end_estado" name="endereco[estado]" type="text" 
                               placeholder="SP" maxlength="2" style="text-transform: uppercase;">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🏙️</span> Cidade</label>
                        <input class="form-input" id="end_cidade" name="endereco[cidade]" 
                               type="text" placeholder="São Paulo">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🏘️</span> Bairro</label>
                        <input class="form-input" id="end_bairro" name="endereco[bairro]" 
                               type="text" placeholder="Centro">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🛣️</span> Rua/Avenida</label>
                        <input class="form-input" id="end_rua" name="endereco[rua]" 
                               type="text" placeholder="Rua das Flores">
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🔢</span> Número</label>
                        <input class="form-input" id="end_numero" name="endereco[numero]" 
                               type="text" placeholder="123">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🏢</span> Complemento</label>
                        <input class="form-input" id="end_complemento" name="endereco[complemento]" 
                               type="text" placeholder="Apto, Bloco (opcional)">
                    </div>
                </div>
            </div>
        </div>

        <!-- Segurança (Full Width) -->
        <div class="profile-section" data-aos="fade-up" data-aos-delay="200">
            <div class="section-header">
                <div class="section-icon">🔒</div>
                <div class="section-header-text">
                    <h3>Segurança</h3>
                    <p>Altere sua senha de acesso</p>
                </div>
            </div>

            <div class="form-row cols-3">
                <div class="form-group">
                    <label class="form-label"><span class="emoji">🔑</span> Senha Atual</label>
                    <input class="form-input" id="senha_atual" name="senha_atual" 
                           type="password" placeholder="Digite sua senha atual" 
                           autocomplete="current-password">
                    <div class="input-hint">⚠️ Necessário apenas se quiser alterar a senha</div>
                </div>
                <div class="form-group">
                    <label class="form-label"><span class="emoji">🔐</span> Nova Senha</label>
                    <input class="form-input" id="nova_senha" name="nova_senha" 
                           type="password" placeholder="Mínimo 6 caracteres" 
                           autocomplete="new-password" minlength="6">
                    <div class="password-strength" id="password-strength" style="display:none;">
                        <div class="password-strength-bar"></div>
                    </div>
                    <div class="input-feedback" id="password-feedback"></div>
                </div>
                <div class="form-group">
                    <label class="form-label"><span class="emoji">✅</span> Confirmar Senha</label>
                    <input class="form-input" id="conf_senha" name="conf_senha" 
                           type="password" placeholder="Digite novamente" 
                           autocomplete="new-password" minlength="6">
                    <div class="input-feedback" id="confirm-feedback"></div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <div class="save-status" id="save-status">
                ✨ Alterações não salvas
            </div>
            <button type="submit" class="btn btn-primary" id="btn-save">
                <span>💾 Salvar Alterações</span>
            </button>
        </div>
    </form>
</div>

<section class="settings-section settings-danger" data-aos="fade-up">
    <h2>Excluir conta</h2>
    <p>
        Ao excluir sua conta, todos os seus dados financeiros serão apagados de forma permanente.
        Essa ação não pode ser desfeita.
    </p>

    <button type="button" class="lk-btn lk-btn-danger" id="btn-open-delete-account-modal">
        Excluir minha conta
    </button>
</section>


<!-- Modal de confirmação -->
<div class="lk-modal" id="delete-account-modal" aria-hidden="true">
    <div class="lk-modal-backdrop" data-close-modal></div>

    <div class="lk-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="deleteAccountTitle">
        <header class="lk-modal-header">
            <h3 id="deleteAccountTitle">Excluir conta</h3>
        </header>

        <div class="lk-modal-body">
            <p>
                Tem certeza que deseja <strong>excluir definitivamente</strong> sua conta?
            </p>
            <ul class="lk-modal-warning-list">
                <li>Seus lançamentos e categorias serão removidos.</li>
                <li>Você perderá acesso ao histórico financeiro.</li>
                <li>Esta ação <strong>não pode ser desfeita</strong>.</li>
            </ul>

            <form id="delete-account-form" method="POST" action="<?= BASE_URL ?>config/excluir-conta">
                <?php if (function_exists('csrf_input')): ?>
                    <?= csrf_input() ?>
                <?php endif; ?>

                <!-- Confirmação simples digitando "EXCLUIR" (opcional) -->
                <label class="field">
                    <span class="field-label">Digite <strong>EXCLUIR</strong> para confirmar:</span>
                    <input type="text" name="confirm_text" id="confirm_text" autocomplete="off">
                </label>

                <footer class="lk-modal-footer">
                    <button type="button" class="lk-btn lk-btn-ghost" data-close-modal>
                        Cancelar
                    </button>

                    <button type="submit" class="lk-btn lk-btn-danger" id="btn-confirm-delete">
                        Sim, excluir minha conta
                    </button>
                </footer>
            </form>
        </div>
    </div>
</div>
<script>
    (() => {
        const BASE = (() => {
            const meta = document.querySelector('meta[name="base-url"]')?.content || '';
            let base = meta;
            if (!base) {
                const m = location.pathname.match(/^(.*\/public\/)/);
                base = m ? (location.origin + m[1]) : (location.origin + '/');
            }
            if (base && !/\/public\/?$/.test(base)) {
                const m2 = location.pathname.match(/^(.*\/public\/)/);
                if (m2) base = location.origin + m2[1];
            }
            return base.replace(/\/?$/, '/');
        })();
        const API = `${BASE}api/`;
        const extractApiError = (payload, fallback = 'Falha ao salvar.') => {
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
        };

        const form = document.getElementById('profileForm');
        const inputAva = document.getElementById('avatarInput');
        const imgPrev = document.getElementById('avatarPreview');
        const btnCancel = document.getElementById('btnCancel');

        const fieldNome = document.getElementById('nome');
        const fieldEmail = document.getElementById('email');
        const fieldCpf = document.getElementById('cpf');
        const fieldData = document.getElementById('data_nascimento');
        const fieldTelefone = document.getElementById('telefone');
        const fieldSexo = document.getElementById('sexo');

        // 🚀 ADICIONADO: Campos de Endereço
        const fieldCep = document.getElementById('end_cep');
        const fieldRua = document.getElementById('end_rua');
        const fieldNumero = document.getElementById('end_numero');
        const fieldComplemento = document.getElementById('end_complemento');
        const fieldBairro = document.getElementById('end_bairro');
        const fieldCidade = document.getElementById('end_cidade');
        const fieldEstado = document.getElementById('end_estado');

        const placeholderAvatar = `${BASE}assets/img/avatar-placeholder.png`;
        const resolveAvatarUrl = (value) => {
            if (!value) return placeholderAvatar;
            if (/^https?:/i.test(value)) return value;
            return `${BASE}${String(value).replace(/^\//, '')}`;
        };

        // Validação de força da senha
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (strength <= 2) return 'weak';
            if (strength <= 3) return 'medium';
            return 'strong';
        }

        // Track form changes
        let formChanged = false;
        const allInputs = form?.querySelectorAll('input, select, textarea');
        allInputs?.forEach(input => {
            input.addEventListener('input', () => {
                formChanged = true;
                const saveStatus = document.getElementById('save-status');
                if (saveStatus) {
                    saveStatus.innerHTML = '⚠️ Alterações não salvas';
                    saveStatus.style.color = '#e67e22';
                }
            });
        });

        // Mascaras simples
        const onlyDigits = (s) => (s || '').replace(/\D+/g, '');

        function maskCPF(v) {
            v = onlyDigits(v).slice(0, 11);
            let out = '';
            if (v.length > 9) out = v.replace(/^(\d{3})(\d{3})(\d{3})(\d{0,2}).*/, '$1.$2.$3-$4');
            else if (v.length > 6) out = v.replace(/^(\d{3})(\d{3})(\d{0,3}).*/, '$1.$2.$3');
            else if (v.length > 3) out = v.replace(/^(\d{3})(\d{0,3}).*/, '$1.$2');
            else out = v;
            return out;
        }

        function validateCPF(cpf) {
            cpf = onlyDigits(cpf);
            if (cpf.length !== 11) return false;
            if (/^(\d)\1{10}$/.test(cpf)) return false;

            let sum = 0;
            for (let i = 0; i < 9; i++) {
                sum += parseInt(cpf.charAt(i)) * (10 - i);
            }
            let remainder = 11 - (sum % 11);
            let digit = remainder >= 10 ? 0 : remainder;
            if (digit !== parseInt(cpf.charAt(9))) return false;

            sum = 0;
            for (let i = 0; i < 10; i++) {
                sum += parseInt(cpf.charAt(i)) * (11 - i);
            }
            remainder = 11 - (sum % 11);
            digit = remainder >= 10 ? 0 : remainder;
            return digit === parseInt(cpf.charAt(10));
        }

        function maskPhone(v) {
            v = onlyDigits(v).slice(0, 11);
            if (v.length <= 10) {
                return v
                    .replace(/^(\d{0,2})/, '($1')
                    .replace(/^\((\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            }
            return v
                .replace(/^(\d{0,2})/, '($1')
                .replace(/^\((\d{2})(\d)/, '($1) $2')
                .replace(/(\d{5})(\d)/, '$1-$2');
        }

        function maskCEP(v) {
            v = onlyDigits(v).slice(0, 8);
            return v.replace(/^(\d{5})(\d{0,3}).*/, '$1-$2');
        }

        if (fieldCpf) {
            fieldCpf.addEventListener('input', () => {
                fieldCpf.value = maskCPF(fieldCpf.value);
                
                const cpfFeedback = document.getElementById('cpf-feedback');
                const cpfDigits = onlyDigits(fieldCpf.value);
                
                if (cpfDigits.length === 0) {
                    fieldCpf.classList.remove('is-valid', 'is-invalid');
                    cpfFeedback.innerHTML = '';
                } else if (cpfDigits.length === 11) {
                    if (validateCPF(cpfDigits)) {
                        fieldCpf.classList.remove('is-invalid');
                        fieldCpf.classList.add('is-valid');
                        cpfFeedback.innerHTML = '<span class="success">✓ CPF válido</span>';
                    } else {
                        fieldCpf.classList.remove('is-valid');
                        fieldCpf.classList.add('is-invalid');
                        cpfFeedback.innerHTML = '<span class="error">✗ CPF inválido</span>';
                    }
                } else {
                    fieldCpf.classList.remove('is-valid', 'is-invalid');
                    cpfFeedback.innerHTML = '';
                }
            });
        }

        if (fieldTelefone) {
            fieldTelefone.addEventListener('input', () => {
                fieldTelefone.value = maskPhone(fieldTelefone.value);
                
                const digits = onlyDigits(fieldTelefone.value);
                if (digits.length >= 10) {
                    fieldTelefone.classList.add('is-valid');
                } else {
                    fieldTelefone.classList.remove('is-valid');
                }
            });
        }

        if (fieldCep) {
            fieldCep.addEventListener('input', () => {
                fieldCep.value = maskCEP(fieldCep.value);
                
                const digits = onlyDigits(fieldCep.value);
                if (digits.length === 8) {
                    fieldCep.classList.add('is-valid');
                } else {
                    fieldCep.classList.remove('is-valid', 'is-invalid');
                }
            });

            // Auto-complete com ViaCEP
            fieldCep.addEventListener('blur', async () => {
                const cep = onlyDigits(fieldCep.value);
                if (cep.length !== 8) return;

                const fields = [fieldCep, fieldRua, fieldBairro, fieldCidade, fieldEstado];
                fields.forEach(f => f && (f.disabled = true));

                try {
                    const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await res.json();
                    if (data && !data.erro) {
                        if (fieldRua) fieldRua.value = data.logradouro || '';
                        if (fieldBairro) fieldBairro.value = data.bairro || '';
                        if (fieldCidade) fieldCidade.value = data.localidade || '';
                        if (fieldEstado) fieldEstado.value = data.uf || '';
                        if (fieldNumero) fieldNumero.focus();
                        
                        fieldCep.classList.add('is-valid');
                        fieldCep.classList.remove('is-invalid');
                    } else {
                        fieldCep.classList.add('is-invalid');
                        fieldCep.classList.remove('is-valid');
                    }
                } catch (e) {
                    console.error('Falha ao buscar CEP', e);
                    fieldCep.classList.add('is-invalid');
                } finally {
                    fields.forEach(f => f && (f.disabled = false));
                }
            });
        }


        inputAva?.addEventListener('change', () => {
            const f = inputAva.files?.[0];
            if (!f || !f.type.match(/^image\//)) return;
            const url = URL.createObjectURL(f);
            if (imgPrev) {
                imgPrev.src = url;
                imgPrev.onload = () => URL.revokeObjectURL(url);
            }
        });

        // Validação de senha em tempo real
        const novaSenhaField = document.getElementById('nova_senha');
        const confSenhaField = document.getElementById('conf_senha');
        const passwordStrength = document.getElementById('password-strength');
        const passwordFeedback = document.getElementById('password-feedback');
        const confirmFeedback = document.getElementById('confirm-feedback');

        if (novaSenhaField) {
            novaSenhaField.addEventListener('input', () => {
                const password = novaSenhaField.value;
                
                if (password.length === 0) {
                    passwordStrength.style.display = 'none';
                    passwordFeedback.innerHTML = '';
                    novaSenhaField.classList.remove('is-valid', 'is-invalid');
                    return;
                }
                
                passwordStrength.style.display = 'block';
                const strength = checkPasswordStrength(password);
                
                passwordStrength.className = 'password-strength ' + strength;
                
                if (strength === 'weak') {
                    passwordFeedback.innerHTML = '<span class="error">⚠️ Senha fraca - adicione mais caracteres</span>';
                    novaSenhaField.classList.remove('is-valid');
                    novaSenhaField.classList.add('is-invalid');
                } else if (strength === 'medium') {
                    passwordFeedback.innerHTML = '<span style="color: #f39c12;">💪 Senha média - pode melhorar</span>';
                    novaSenhaField.classList.remove('is-invalid');
                    novaSenhaField.classList.add('is-valid');
                } else {
                    passwordFeedback.innerHTML = '<span class="success">✓ Senha forte!</span>';
                    novaSenhaField.classList.remove('is-invalid');
                    novaSenhaField.classList.add('is-valid');
                }
                
                // Valida confirmação se já preenchida
                if (confSenhaField.value) {
                    validatePasswordConfirm();
                }
            });
        }

        function validatePasswordConfirm() {
            const password = novaSenhaField?.value || '';
            const confirm = confSenhaField?.value || '';
            
            if (!confirm) {
                confirmFeedback.innerHTML = '';
                confSenhaField.classList.remove('is-valid', 'is-invalid');
                return;
            }
            
            if (password === confirm) {
                confirmFeedback.innerHTML = '<span class="success">✓ Senhas coincidem</span>';
                confSenhaField.classList.remove('is-invalid');
                confSenhaField.classList.add('is-valid');
            } else {
                confirmFeedback.innerHTML = '<span class="error">✗ Senhas não coincidem</span>';
                confSenhaField.classList.remove('is-valid');
                confSenhaField.classList.add('is-invalid');
            }
        }

        if (confSenhaField) {
            confSenhaField.addEventListener('input', validatePasswordConfirm);
        }

        btnCancel?.addEventListener('click', () => {
            if (history.length > 1) {
                history.back();
            } else {
                location.href = BASE + 'dashboard';
            }
        });

        function validateBeforeSubmit(fd) {
            const rawCPF = (fd.get('cpf') || '').toString();
            if (rawCPF && onlyDigits(rawCPF).length !== 11) {
                throw new Error('CPF invalido. Verifique e tente novamente.');
            }

            const ns = (fd.get('nova_senha') || '').toString();
            const cs = (fd.get('conf_senha') || '').toString();
            if (ns || cs) {
                if (ns.length < 6) throw new Error('A nova senha deve ter ao menos 6 caracteres.');
                if (ns !== cs) throw new Error('A confirmacao de senha nao confere.');
            }

            const dn = (fd.get('data_nascimento') || '').toString();
            if (dn && new Date(dn) > new Date()) {
                throw new Error('A data de nascimento nao pode ser futura.');
            }
            const rawCEP = (fd.get('endereco[cep]') || '').toString();
            const cleanCEP = onlyDigits(rawCEP);
            if (cleanCEP && cleanCEP.length !== 8) {
                throw new Error('CEP invalido. O CEP deve ter 8 digitos.');
            }
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
                if (fieldCpf) fieldCpf.value = user.cpf || '';
                if (fieldData) fieldData.value = user.data_nascimento || '';
                if (fieldTelefone) fieldTelefone.value = user.telefone || '';
                if (fieldSexo) fieldSexo.value = user.sexo || '';
                if (imgPrev) imgPrev.src = resolveAvatarUrl(user.avatar);

                const endereco = user.endereco || {};
                if (fieldCep) fieldCep.value = maskCEP(endereco.cep || '');
                if (fieldRua) fieldRua.value = endereco.rua || '';
                if (fieldNumero) fieldNumero.value = endereco.numero || '';
                if (fieldComplemento) fieldComplemento.value = endereco.complemento || '';
                if (fieldBairro) fieldBairro.value = endereco.bairro || '';
                if (fieldCidade) fieldCidade.value = endereco.cidade || '';
                if (fieldEstado) fieldEstado.value = endereco.estado || '';
            } catch (err) {
                console.error(err);
                window.Swal?.fire?.({
                    icon: 'error',
                    title: 'Erro ao carregar',
                    text: err.message || 'Nao foi possivel carregar o perfil.',
                    confirmButtonColor: '#e74c3c'
                });
            }
        }

        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            form.classList.add('form-loading');
            const submitBtn = form.querySelector('.btn-primary');
            const originalContent = submitBtn?.innerHTML || '';
            if (submitBtn) submitBtn.innerHTML = '<span class="spinner"></span><span>Salvando...</span>';
            submitBtn.disabled = true;

            const fd = new FormData(form);

            try {
                validateBeforeSubmit(fd);

                const r = await fetch(`${API}perfil`, {
                    method: 'POST',
                    credentials: 'include',
                    body: fd
                });

                const j = await r.json().catch(() => null);
                if (!r.ok || j?.status === 'error') {
                    throw new Error(extractApiError(j, 'Falha ao salvar.'));
                }

                window.Swal?.fire?.({
                    icon: 'success',
                    title: 'Perfil atualizado!',
                    text: 'Suas informações foram salvas com sucesso.',
                    confirmButtonColor: '#e67e22',
                    timer: 2000
                });

                formChanged = false;
                const saveStatus = document.getElementById('save-status');
                if (saveStatus) {
                    saveStatus.innerHTML = '✓ Tudo salvo';
                    saveStatus.style.color = '#27ae60';
                }

                await loadProfile();
            } catch (err) {
                console.error(err);
                window.Swal?.fire?.({
                    icon: 'error',
                    title: 'Erro ao salvar',
                    text: err.message || 'Erro ao salvar perfil.',
                    confirmButtonColor: '#e74c3c'
                });
            } finally {
                form.classList.remove('form-loading');
                if (submitBtn) {
                    submitBtn.innerHTML = originalContent;
                    submitBtn.disabled = false;
                }
            }
        });

        loadProfile();
    })();
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('delete-account-modal');
        const openBtn = document.getElementById('btn-open-delete-account-modal');
        const form = document.getElementById('delete-account-form');

        if (!modal || !openBtn || !form) return;

        const BASE_URL = '<?= BASE_URL ?>';

        const toggleModal = (show) => {
            if (show) {
                modal.classList.add('is-visible');
                modal.setAttribute('aria-hidden', 'false');
            } else {
                modal.classList.remove('is-visible');
                modal.setAttribute('aria-hidden', 'true');
            }
        };

        openBtn.addEventListener('click', () => {
            toggleModal(true);
        });

        modal.addEventListener('click', (e) => {
            if (e.target.matches('[data-close-modal]')) {
                toggleModal(false);
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const confirmInput = document.getElementById('confirm_text');
            if (!confirmInput || confirmInput.value.trim().toUpperCase() !== 'EXCLUIR') {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Confirmação necessária',
                        text: 'Digite EXCLUIR para confirmar a exclusão da conta.'
                    });
                } else {
                    alert('Digite EXCLUIR para confirmar.');
                }
                return;
            }

            const formData = new FormData(form);

            // garante que a URL inclui o BASE_URL (e normaliza barras duplicadas)
            const actionAttr = form.getAttribute('action') || '';
            const relativeAction = actionAttr.replace(BASE_URL, '').replace(/^\/+/, '');
            const actionUrl = new URL(relativeAction || actionAttr, BASE_URL).toString();

            try {
                if (window.Swal) {
                    Swal.fire({
                        title: 'Excluindo conta...',
                        text: 'Estamos processando sua solicitação.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                }

                const response = await fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const contentType = response.headers.get('Content-Type') || '';

                let data;
                if (contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    console.error('Resposta não JSON da exclusão de conta:', text);
                    throw new Error('Resposta do servidor não está em JSON.');
                }

                // Lukrato: Response::success / Response::error
                const isSuccess = data.status === 'success';

                if (!response.ok || !isSuccess) {
                    const msg = data.message || 'Não foi possível excluir sua conta.';
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: msg,
                        });
                    } else {
                        alert(msg);
                    }
                    return;
                }

                // Sucesso
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Conta excluída',
                        text: data.message || 'Sua conta foi excluída com sucesso.',
                    }).then(() => {
                        window.location.href = BASE_URL + '/';
                    });
                } else {
                    alert(data.message || 'Conta excluída com sucesso.');
                    window.location.href = BASE_URL + '/';
                }

            } catch (err) {
                console.error('Erro ao excluir conta:', err);

                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro inesperado',
                        text: 'Tente novamente em instantes.',
                    });
                } else {
                    alert('Erro inesperado. Tente novamente.');
                }
            }
        });
    });
</script>