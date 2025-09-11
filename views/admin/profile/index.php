<?php
$pageTitle = $pageTitle ?? 'Meu Perfil';
$menu      = $menu ?? 'perfil';
$user      = $user ?? null;

$avatarUrl = $user && !empty($user->avatar)
    ? rtrim(BASE_URL, '/') . '/' . $user->avatar
    : rtrim(BASE_URL, '/') . '/assets/img/avatar-placeholder.png';

function esc($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
/** @var \Application\Models\Usuario|null $user */

$rawNasc = $user?->data_nascimento ?? null;
$dtNasc  = '';
if (!empty($rawNasc)) {
    $ts = strtotime((string)$rawNasc);
    if ($ts) $dtNasc = date('Y-m-d', $ts);
}

if (!empty($user->data_nascimento)) {
    $ts = strtotime((string)$user->data_nascimento);
    if ($ts) $dtNasc = date('Y-m-d', $ts);
}
?>

<style>
/* Profile Specific Styles */


.profile-card {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-backdrop);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-8);
    box-shadow: var(--shadow-lg);
    transition: all var(--transition-normal);
}

.profile-card:hover {
    box-shadow: var(--shadow-xl);
    transform: translateY(-2px);
}

.profile-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
    padding-bottom: var(--spacing-6);
    border-bottom: 1px solid var(--glass-border);
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--laranja);
    box-shadow: var(--shadow-md);
    transition: all var(--transition-normal);
}

.profile-avatar:hover {
    transform: scale(1.05);
    box-shadow: var(--shadow-lg);
}

.profile-title {
    margin: 0;
    font-size: var(--font-size-3xl);
    font-weight: 700;
    color: var(--branco);
    font-family: var(--font-primary);
}

.profile-subtitle {
    margin: var(--spacing-1) 0 0 0;
    font-size: var(--font-size-base);
    color: var(--cinza);
    font-family: var(--font-primary);
}

.profile-form {
    margin-top: var(--spacing-6);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.form-label {
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--branco);
    font-family: var(--font-primary);
}

.form-input {
    padding: var(--spacing-4);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-md);
    background: var(--glass-bg);
    color: var(--branco);
    font-size: var(--font-size-base);
    font-family: var(--font-primary);
    transition: all var(--transition-fast);
    backdrop-filter: var(--glass-backdrop);
}

.form-input:focus {
    outline: none;
    border-color: var(--laranja);
    box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.1);
    background: rgba(255, 255, 255, 0.08);
}

.form-input::placeholder {
    color: var(--cinza);
}

.form-select {
    padding: var(--spacing-4);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-md);
    background: var(--glass-bg);
    color: var(--branco);
    font-size: var(--font-size-base);
    font-family: var(--font-primary);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.form-select:focus {
    outline: none;
    border-color: var(--laranja);
    box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.1);
}

.form-divider {
    border: none;
    height: 1px;
    background: var(--glass-border);
    margin: var(--spacing-8) 0;
}

.section-title {
    font-size: var(--font-size-xl);
    font-weight: 600;
    color: var(--branco);
    margin-bottom: var(--spacing-6);
    font-family: var(--font-primary);
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
}

.section-title::before {
    content: '';
    width: 4px;
    height: 24px;
    background: var(--laranja);
    border-radius: var(--radius-sm);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-4);
    margin-top: var(--spacing-8);
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--glass-border);
}

.btn {
    padding: var(--spacing-4) var(--spacing-6);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    font-weight: 600;
    font-family: var(--font-primary);
    cursor: pointer;
    transition: all var(--transition-fast);
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
}

.btn-ghost {
    background: transparent;
    color: var(--cinza);
    border: 1px solid var(--glass-border);
}

.btn-ghost:hover {
    background: var(--glass-bg);
    color: var(--branco);
    transform: translateY(-1px);
}

.btn-primary {
    background: var(--laranja);
    color: var(--branco);
    box-shadow: var(--shadow-md);
}

.btn-primary:hover {
    background: #d35400;
    box-shadow: var(--shadow-lg);
    transform: translateY(-1px);
}

.btn-primary:active {
    transform: translateY(0);
}

/* Password Grid */
.password-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-6);
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-container {
        padding: var(--spacing-4);
    }

    .profile-card {
        padding: var(--spacing-6);
    }

    .profile-header {
        flex-direction: column;
        text-align: center;
        gap: var(--spacing-4);
    }

    .form-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-4);
    }

    .password-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}

/* Loading State */
.form-loading {
    opacity: 0.7;
    pointer-events: none;
}

.form-loading .btn-primary {
    background: var(--cinza);
    cursor: not-allowed;
}
</style>

<div class="profile-container container">
    <div class="profile-header">
        <div>
            <h1 class="profile-title">Meu Perfil</h1>
            <p class="profile-subtitle">Atualize seus dados da conta</p>
        </div>
    </div>
    <div class="profile-card">
        <form id="profileForm" class="profile-form">
            <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>

            <!-- DADOS BÁSICOS -->
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Nome completo</label>
                    <input class="form-input" name="nome" type="text" value="<?= esc($user->nome ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">E-mail</label>
                    <input class="form-input" name="email" type="email" value="<?= esc($user->email ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">CPF</label>
                    <input class="form-input" id="cpf" name="cpf" type="text" inputmode="numeric" maxlength="14"
                        placeholder="000.000.000-00" value="<?= esc($user->cpf ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Data de nascimento</label>
                    <input class="form-input" id="data_nascimento" name="data_nascimento" type="date"
                        value="<?= esc($dtNasc) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Telefone</label>
                    <input class="form-input" id="telefone" name="telefone" type="tel" inputmode="tel" maxlength="15"
                        placeholder="(00) 00000-0000" value="<?= esc($user->telefone ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Sexo</label>
                    <select class="form-input form-select" name="sexo" id="sexo">
                        <?php $sx = $user->sexo ?? ''; ?>
                        <option value="" <?= $sx === '' ? 'selected' : '' ?>>Selecione</option>
                        <option value="M" <?= $sx === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= $sx === 'F' ? 'selected' : '' ?>>Feminino</option>
                        <option value="O" <?= $sx === 'O' ? 'selected' : '' ?>>Outro</option>
                        <option value="N" <?= $sx === 'N' ? 'selected' : '' ?>>Prefiro não informar</option>
                    </select>
                </div>
            </div>

            <hr class="form-divider">

            <!-- TROCA DE SENHA -->
            <h3 class="section-title">Alterar senha</h3>
            <div class="password-grid">
                <div class="form-group">
                    <label class="form-label">Senha atual</label>
                    <input class="form-input" name="senha_atual" type="password" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label class="form-label">Nova senha</label>
                    <input class="form-input" name="nova_senha" type="password" autocomplete="new-password"
                        minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmar senha</label>
                    <input class="form-input" name="conf_senha" type="password" autocomplete="new-password"
                        minlength="6">
                </div>
            </div>

            <!-- Upload de avatar (oculto) -->
            <div class="form-group" style="display: none;">
                <label class="form-label">Avatar</label>
                <input class="form-input" type="file" id="avatarInput" name="avatar" accept="image/*">
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-ghost" id="btnCancel">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar alterações</button>
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

    const form = document.getElementById('profileForm');
    const inputAva = document.getElementById('avatarInput');
    const imgPrev = document.getElementById('avatarPreview');
    const btnCancel = document.getElementById('btnCancel');

    // ===== Máscaras simples (sem libs) =====
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
        } else {
            return v
                .replace(/^(\d{0,2})/, '($1')
                .replace(/^\((\d{2})(\d)/, '($1) $2')
                .replace(/(\d{5})(\d)/, '$1-$2');
        }
    }

    const $cpf = document.getElementById('cpf');
    const $tel = document.getElementById('telefone');

    if ($cpf) {
        $cpf.addEventListener('input', () => $cpf.value = maskCPF($cpf.value));
        if ($cpf.value) $cpf.value = maskCPF($cpf.value);
    }

    if ($tel) {
        $tel.addEventListener('input', () => $tel.value = maskPhone($tel.value));
        if ($tel.value) $tel.value = maskPhone($tel.value);
    }

    // Preview do avatar
    inputAva?.addEventListener('change', () => {
        const f = inputAva.files?. [0];
        if (!f) return;
        if (!f.type.match(/^image\//)) return;
        const url = URL.createObjectURL(f);
        if (imgPrev) {
            imgPrev.src = url;
            imgPrev.onload = () => URL.revokeObjectURL(url);
        }
    });

    // Botão cancelar
    btnCancel?.addEventListener('click', () => {
        if (history.length > 1) {
            history.back();
        } else {
            location.href = BASE + 'dashboard';
        }
    });

    // Validações
    function validateBeforeSubmit(fd) {
        const rawCPF = (fd.get('cpf') || '').toString();
        if (rawCPF && onlyDigits(rawCPF).length !== 11) {
            throw new Error('CPF inválido. Verifique e tente novamente.');
        }

        const ns = (fd.get('nova_senha') || '').toString();
        const cs = (fd.get('conf_senha') || '').toString();
        if (ns || cs) {
            if (ns.length < 6) throw new Error('A nova senha deve ter ao menos 6 caracteres.');
            if (ns !== cs) throw new Error('A confirmação de senha não confere.');
        }

        const dn = (fd.get('data_nascimento') || '').toString();
        if (dn && new Date(dn) > new Date()) {
            throw new Error('A data de nascimento não pode ser futura.');
        }
    }

    // Submit do formulário
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Adiciona estado de loading
        form.classList.add('form-loading');
        const submitBtn = form.querySelector('.btn-primary');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Salvando...';

        const fd = new FormData(form);

        try {
            validateBeforeSubmit(fd);

            const r = await fetch(`${BASE}api/profile`, {
                method: 'POST',
                credentials: 'include',
                body: fd
            });

            const j = await r.json().catch(() => null);
            if (!r.ok || j?.status === 'error') {
                throw new Error(j?.message || 'Falha ao salvar.');
            }

            // Feedback de sucesso
            window.Swal?.fire?.({
                icon: 'success',
                title: 'Perfil atualizado com sucesso!',
                text: 'Suas informações foram salvas.',
                confirmButtonColor: '#e67e22'
            });

        } catch (err) {
            console.error(err);
            window.Swal?.fire?.({
                icon: 'error',
                title: 'Erro ao salvar',
                text: err.message || 'Erro ao salvar perfil',
                confirmButtonColor: '#e74c3c'
            });
        } finally {
            // Remove estado de loading
            form.classList.remove('form-loading');
            submitBtn.textContent = originalText;
        }
    });
})();
</script>