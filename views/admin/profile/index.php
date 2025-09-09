<?php
$pageTitle = $pageTitle ?? 'Meu Perfil';
$menu      = $menu ?? 'perfil';
$user      = $user ?? null;

$avatarUrl = $user && !empty($user->avatar)
    ? rtrim(BASE_URL, '/') . '/' . $user->avatar
    : rtrim(BASE_URL, '/') . '/assets/img/avatar-placeholder.png';

function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// tenta normalizar a data para Y-m-d, caso venha em outro formato

?>
<style>
    .card{
        background-color: #111f2b;
        color: #fff;
    }
</style>
<div class="container">
    <section class="card" style="padding:16px;">
        <div class="card-header" style="display:flex;align-items:center;gap:16px;">
            <!-- (opcional) avatar se você quiser exibir -->
            <img id="avatarPreview" src="<?= esc($avatarUrl) ?>" alt="Avatar" style="width:56px;height:56px;border-radius:50%;object-fit:cover;display:none;">
            <div>
                <h1 class="card-title" style="margin:0;">Perfil</h1>
                <p style="opacity:.75;margin:4px 0 0 0;">Atualize seus dados da conta</p>
            </div>
        </div>

        <form id="profileForm" class="card-body" enctype="multipart/form-data" style="margin-top:16px;">
            <!-- DADOS BÁSICOS -->
            <div class="grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
                <div class="form-group">
                    <label>Nome completo</label>
                    <input class="form-input" name="nome" type="text" value="<?= esc($user->nome ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input class="form-input" name="email" type="email" value="<?= esc($user->email ?? '') ?>" required>
                </div>

                <!-- NOVOS CAMPOS -->
                <div class="form-group">
                    <label>CPF</label>
                    <input class="form-input" id="cpf" name="cpf" type="text" inputmode="numeric" maxlength="14"
                           placeholder="000.000.000-00" value="<?= esc($user->cpf ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Data de nascimento</label>
                    <input class="form-input" id="data_nascimento" name="data_nascimento" type="date"
                           value="<?= esc($dtNasc) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input class="form-input" id="telefone" name="telefone" type="tel" inputmode="tel" maxlength="15"
                           placeholder="(00) 00000-0000" value="<?= esc($user->telefone ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Sexo</label>
                    <select class="form-input" name="sexo" id="sexo">
                        <?php $sx = $user->sexo ?? ''; ?>
                        <option value="" <?= $sx === '' ? 'selected' : '' ?>>Selecione</option>
                        <option value="M" <?= $sx === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= $sx === 'F' ? 'selected' : '' ?>>Feminino</option>
                        <option value="O" <?= $sx === 'O' ? 'selected' : '' ?>>Outro</option>
                        <option value="N" <?= $sx === 'N' ? 'selected' : '' ?>>Prefiro não informar</option>
                    </select>
                </div>
            </div>

            <hr style="border-color:var(--glass-border);margin:16px 0;">

            <!-- TROCA DE SENHA -->
            <h3 style="margin-bottom:8px;">Trocar senha (opcional)</h3>
            <div class="grid" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;">
                <div class="form-group">
                    <label>Senha atual</label>
                    <input class="form-input" name="senha_atual" type="password" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label>Nova senha</label>
                    <input class="form-input" name="nova_senha" type="password" autocomplete="new-password" minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirmar senha</label>
                    <input class="form-input" name="conf_senha" type="password" autocomplete="new-password" minlength="6">
                </div>
            </div>

            <!-- (opcional) upload de avatar para casar com o JS -->
            <div class="form-group" style="margin-top:12px;display:none;">
                <label>Avatar</label>
                <input class="form-input" type="file" id="avatarInput" name="avatar" accept="image/*">
            </div>

            <div class="lk-modal-footer" style="justify-content:flex-end;margin-top:16px;">
                <button type="button" class="btn btn-ghost" id="btnCancel">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar alterações</button>
            </div>
        </form>
    </section>
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

    const form      = document.getElementById('profileForm');
    const inputAva  = document.getElementById('avatarInput');
    const imgPrev   = document.getElementById('avatarPreview');
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
            // (99) 9999-9999
            return v
                .replace(/^(\d{0,2})/, '($1')
                .replace(/^\((\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4})(\d)/, '$1-$2');
        } else {
            // (99) 99999-9999
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
        // aplica máscara em valor inicial, se houver
        if ($cpf.value) $cpf.value = maskCPF($cpf.value);
    }
    if ($tel) {
        $tel.addEventListener('input', () => $tel.value = maskPhone($tel.value));
        if ($tel.value) $tel.value = maskPhone($tel.value);
    }

    // preview do avatar (se você decidir exibir o upload)
    inputAva?.addEventListener('change', () => {
        const f = inputAva.files?.[0];
        if (!f) return;
        if (!f.type.match(/^image\//)) return;
        const url = URL.createObjectURL(f);
        if (imgPrev) {
            imgPrev.style.display = 'inline-block';
            imgPrev.src = url;
            imgPrev.onload = () => URL.revokeObjectURL(url);
        }
    });

    // cancelar = volta (ou recarrega)
    btnCancel?.addEventListener('click', () => history.length > 1 ? history.back() : location.href = BASE + 'dashboard');

    // validações mínimas antes de enviar
    function validateBeforeSubmit(fd) {
        // se CPF vier preenchido, checa tamanho (apenas formato)
        const rawCPF = (fd.get('cpf') || '').toString();
        if (rawCPF && onlyDigits(rawCPF).length !== 11) {
            throw new Error('CPF inválido. Verifique e tente novamente.');
        }
        // se preencher nova_senha/conf_senha, precisa bater
        const ns = (fd.get('nova_senha') || '').toString();
        const cs = (fd.get('conf_senha') || '').toString();
        if (ns || cs) {
            if (ns.length < 6) throw new Error('A nova senha deve ter ao menos 6 caracteres.');
            if (ns !== cs) throw new Error('A confirmação de senha não confere.');
        }
        // data de nascimento no futuro?
        const dn = (fd.get('data_nascimento') || '').toString();
        if (dn && new Date(dn) > new Date()) {
            throw new Error('A data de nascimento não pode ser futura.');
        }
    }

    // submit via fetch multipart
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);

        try {
            validateBeforeSubmit(fd);

            const r = await fetch(`${BASE}api/profile`, {
                method: 'POST',
                credentials: 'include',
                body: fd
            });
            const j = await r.json().catch(() => null);
            if (!r.ok || j?.status === 'error') throw new Error(j?.message || 'Falha ao salvar.');

            // feedback
            window.Swal?.fire?.({
                icon: 'success',
                title: 'Perfil atualizado!'
            });

            // Se quiser refletir novo avatar/username no layout (menu/header), faça aqui:
            // document.querySelector('.sidebar .username')?.textContent = j.user?.username || '';
            // document.querySelector('.sidebar .avatar img')?.setAttribute('src', BASE + j.user?.avatar);

        } catch (err) {
            console.error(err);
            window.Swal?.fire?.({
                icon: 'error',
                title: err.message || 'Erro ao salvar perfil'
            });
        }
    });
})();
</script>
