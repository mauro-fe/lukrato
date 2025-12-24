<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/perfil-modern.css">

<div class="profile-page">
    <div class="profile-header">
        <h1 class="profile-title">Meu Perfil</h1>
        <p class="profile-subtitle">Gerencie suas informações pessoais e configurações de conta</p>
    </div>

    <form id="profileForm">
        <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>

        <div class="profile-grid">
            <div class="profile-section" data-aos="fade-up">
                <div class="section-header">
                    <div class="section-icon">👤</div>
                    <div class="section-header-text">
                        <h3>Dados Pessoais</h3>
                        <p>Informações básicas</p>
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">✏️</span> Nome Completo *</label>
                        <input class="form-input" id="nome" name="nome" type="text" placeholder="Digite seu nome completo" required>
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">📧</span> E-mail *</label>
                        <input class="form-input" id="email" name="email" type="email" placeholder="seu@email.com" required>
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🆔</span> CPF</label>
                        <input class="form-input" id="cpf" name="cpf" type="text" inputmode="numeric" maxlength="14" placeholder="000.000.000-00">
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">📅</span> Nascimento</label>
                        <input class="form-input" id="data_nascimento" name="data_nascimento" type="date" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">📱</span> Telefone</label>
                        <input class="form-input" id="telefone" name="telefone" type="tel" inputmode="tel" maxlength="15" placeholder="(00) 00000-0000">
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
                        <input class="form-input" id="end_cep" name="endereco[cep]" type="text" inputmode="numeric" placeholder="00000-000" maxlength="9">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🗺️</span> Estado</label>
                        <input class="form-input" id="end_estado" name="endereco[estado]" type="text" placeholder="SP" maxlength="2" style="text-transform: uppercase;">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🏙️</span> Cidade</label>
                        <input class="form-input" id="end_cidade" name="endereco[cidade]" type="text" placeholder="São Paulo">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🏘️</span> Bairro</label>
                        <input class="form-input" id="end_bairro" name="endereco[bairro]" type="text" placeholder="Centro">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🛣️</span> Rua/Avenida</label>
                        <input class="form-input" id="end_rua" name="endereco[rua]" type="text" placeholder="Rua das Flores">
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🔢</span> Número</label>
                        <input class="form-input" id="end_numero" name="endereco[numero]" type="text" placeholder="123">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><span class="emoji">🏢</span> Complemento</label>
                        <input class="form-input" id="end_complemento" name="endereco[complemento]" type="text" placeholder="Apto, Bloco (opcional)">
                    </div>
                </div>
            </div>
        </div>

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
                    <input class="form-input" id="senha_atual" name="senha_atual" type="password" placeholder="Digite sua senha atual" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label class="form-label"><span class="emoji">🔐</span> Nova Senha</label>
                    <input class="form-input" id="nova_senha" name="nova_senha" type="password" placeholder="Mínimo 6 caracteres" autocomplete="new-password" minlength="6">
                    <div class="password-strength" id="password-strength" style="display:none;">
                        <div class="password-strength-bar"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><span class="emoji">✅</span> Confirmar Senha</label>
                    <input class="form-input" id="conf_senha" name="conf_senha" type="password" placeholder="Digite novamente" autocomplete="new-password" minlength="6">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <div class="save-status" id="save-status">✨ Alterações não salvas</div>
            <button type="submit" class="btn-save" id="btn-save">
                <span>💾 Salvar Alterações</span>
            </button>
        </div>
    </form>
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
                headers: { 'Accept': 'application/json' }
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
        const { errors } = payload;
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
        
        const submitBtn = document.getElementById('btn-save');
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
                body: fd
            });
            
            const j = await r.json().catch(() => null);
            if (!r.ok || j?.status === 'error') {
                throw new Error(extractApiError(j, 'Falha ao salvar.'));
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
    
    // Carregar perfil ao iniciar
    loadProfile();
})();
</script>

<script src="<?= BASE_URL ?>assets/js/admin-profile-edit.js"></script>
