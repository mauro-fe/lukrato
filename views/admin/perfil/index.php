<?php
$pageTitle = $pageTitle ?? 'Meu Perfil';
$menu      = $menu ?? 'perfil';
?>

<div class="profile-container">

    <div class="profile-card mt-5" data-aos="fade-up">
        <p class="profile-subtitle">Atualize seus dados da conta</p>

        <form id="profileForm" class="profile-form">
            <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>

            <div class="form-grid">
                <div class="form-group" data-aos="fade-up-right" data-aos-delay="100">
                    <label class="form-label" for="nome">Nome completo</label>
                    <input class="form-input" id="nome" name="nome" type="text" value="" required>
                </div>
                <div class="form-group" data-aos="fade-up" data-aos-delay="200">
                    <label class=" form-label" for="email">E-mail</label>
                    <input class="form-input" id="email" name="email" type="email" value="" required>
                </div>

                <div class="form-group" data-aos="fade-up-left" data-aos-delay="300">
                    <label class="form-label" for="cpf">CPF</label>
                    <input class="form-input" id="cpf" name="cpf" type="text" inputmode="numeric" maxlength="14"
                        placeholder="000.000.000-00" value="">
                </div>
                <div class="form-group" data-aos="fade-up-right" data-aos-delay="400">
                    <label class="form-label" for="data_nascimento">Data de nascimento</label>
                    <input class="form-input" id="data_nascimento" name="data_nascimento" type="date" value=""
                        max="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group" data-aos="fade-up" data-aos-delay="500">
                    <label class="form-label" for="telefone">Telefone</label>
                    <input class="form-input" id="telefone" name="telefone" type="tel" inputmode="tel" maxlength="15"
                        placeholder="(00) 00000-0000" value="">
                </div>
                <div class="form-group" data-aos="fade-up-left" data-aos-delay="600">
                    <label class="form-label" for="sexo">Sexo</label>
                    <select class="form-input form-select" name="sexo" id="sexo">
                        <option value="" selected>Selecione</option>
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                        <option value="O">Outro</option>
                        <option value="N">Prefiro nao informar</option>
                    </select>
                </div>
            </div>

            <hr class="form-divider">

            <h3 class="section-title" data-aos="fade-up" data-aos-delay="650">Endereço</h3>
            <div class="form-grid">
                <div class="form-group" data-aos="fade-up-right" data-aos-delay="700">
                    <label class="form-label" for="end_cep">CEP</label>
                    <input class="form-input" id="end_cep" name="endereco[cep]" type="text" inputmode="numeric"
                        placeholder="00000-000" maxlength="9">
                </div>
                <div class="form-group" data-aos="fade-up" data-aos-delay="750">
                    <label class="form-label" for="end_rua">Rua</label>
                    <input class="form-input" id="end_rua" name="endereco[rua]" type="text"
                        placeholder="Ex: Rua das Flores">
                </div>
                <div class="form-group" data-aos="fade-up-left" data-aos-delay="800">
                    <label class="form-label" for="end_numero">Número</label>
                    <input class="form-input" id="end_numero" name="endereco[numero]" type="text" placeholder="Ex: 123">
                </div>
                <div class="form-group" data-aos="fade-up-right" data-aos-delay="850">
                    <label class="form-label" for="end_complemento">Complemento</label>
                    <input class="form-input" id="end_complemento" name="endereco[complemento]" type="text"
                        placeholder="Ex: Apto 101">
                </div>
                <div class="form-group" data-aos="fade-up" data-aos-delay="900">
                    <label class="form-label" for="end_bairro">Bairro</label>
                    <input class="form-input" id="end_bairro" name="endereco[bairro]" type="text"
                        placeholder="Ex: Centro">
                </div>
                <div class="form-group" data-aos="fade-up-left" data-aos-delay="950">
                    <label class="form-label" for="end_cidade">Cidade</label>
                    <input class="form-input" id="end_cidade" name="endereco[cidade]" type="text"
                        placeholder="Ex: São Paulo">
                </div>
                <div class="form-group" data-aos="fade-up" data-aos-delay="1000">
                    <label class="form-label" for="end_estado">Estado (UF)</label>
                    <input class="form-input" id="end_estado" name="endereco[estado]" type="text" placeholder="Ex: SP"
                        maxlength="2">
                </div>
            </div>
            <hr class="form-divider">

            <h3 class="section-title" data-aos="fade-up" data-aos-delay="700">Alterar senha</h3>
            <div class="password-grid">
                <div class="form-group" data-aos="fade-up-right" data-aos-delay="750">
                    <label class="form-label" for="senha_atual">Senha atual</label>
                    <input class="form-input" id="senha_atual" name="senha_atual" type="password"
                        autocomplete="current-password">
                </div>
                <div class="form-group" data-aos="fade-up" data-aos-delay="800">
                    <label class="form-label" for="nova_senha">Nova senha</label>
                    <input class="form-input" id="nova_senha" name="nova_senha" type="password"
                        autocomplete="new-password" minlength="6">
                </div>
                <div class="form-group" data-aos="fade-up-left" data-aos-delay="850">
                    <label class="form-label" for="conf_senha">Confirmar senha</label>
                    <input class="form-input" id="conf_senha" name="conf_senha" type="password"
                        autocomplete="new-password" minlength="6">
                </div>
            </div>

            <div class="form-actions">
                <div data-aos="fade-up-left" data-aos-delay="900">
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>
            </div>
        </form>
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
            });
        }

        if (fieldTelefone) {
            fieldTelefone.addEventListener('input', () => {
                fieldTelefone.value = maskPhone(fieldTelefone.value);
            });
        }

        if (fieldCep) {
            fieldCep.addEventListener('input', () => {
                fieldCep.value = maskCEP(fieldCep.value);
            });

            // Auto-complete com ViaCEP
            fieldCep.addEventListener('blur', async () => {
                const cep = onlyDigits(fieldCep.value);
                if (cep.length !== 8) return;

                fieldCep.disabled = true;
                fieldRua.disabled = true;
                fieldBairro.disabled = true;
                fieldCidade.disabled = true;
                fieldEstado.disabled = true;

                try {
                    const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await res.json();
                    if (data && !data.erro) {
                        if (fieldRua) fieldRua.value = data.logradouro || '';
                        if (fieldBairro) fieldBairro.value = data.bairro || '';
                        if (fieldCidade) fieldCidade.value = data.localidade || '';
                        if (fieldEstado) fieldEstado.value = data.uf || '';
                        if (fieldNumero) fieldNumero.focus(); // Foca no número
                    }
                } catch (e) {
                    console.error('Falha ao buscar CEP', e);
                } finally {
                    fieldCep.disabled = false;
                    fieldRua.disabled = false;
                    fieldBairro.disabled = false;
                    fieldCidade.disabled = false;
                    fieldEstado.disabled = false;
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
            const originalText = submitBtn?.textContent || '';
            if (submitBtn) submitBtn.textContent = 'Salvando...';

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
                    title: 'Perfil atualizado com sucesso!',
                    text: 'Suas informacoes foram salvas.',
                    confirmButtonColor: '#e67e22'
                });

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
                if (submitBtn) submitBtn.textContent = originalText;
            }
        });

        loadProfile();
    })();
</script>