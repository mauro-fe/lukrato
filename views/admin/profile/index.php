<?php
$pageTitle = $pageTitle ?? 'Meu Perfil';
$menu      = $menu ?? 'perfil';
$user      = $user ?? null;

$avatarUrl = $user && !empty($user->avatar)
    ? rtrim(BASE_URL, '/') . '/' . $user->avatar
    : rtrim(BASE_URL, '/') . '/assets/img/avatar-placeholder.png';
?>
<div class="container">
    <section class="card" style="padding:16px;">
        <div class="card-header" style="display:flex;align-items:center;gap:16px;">
            <div class="user-avatar" style="width:72px;height:72px;border-radius:50%;overflow:hidden;border:2px solid var(--laranja);">
                <img id="avatarPreview" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div>
                <h1 class="card-title" style="margin:0;">Perfil</h1>
                <p style="opacity:.75;margin:4px 0 0 0;">Atualize seus dados da conta</p>
            </div>
        </div>

        <form id="profileForm" class="card-body" enctype="multipart/form-data" style="margin-top:16px;">
            <div class="grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
                <div class="form-group">
                    <label>Nome completo</label>
                    <input class="form-input" name="nome" type="text" value="<?= htmlspecialchars($user->nome ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input class="form-input" name="username" type="text" value="<?= htmlspecialchars($user->username ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input class="form-input" name="email" type="email" value="<?= htmlspecialchars($user->email ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Tema</label>
                    <select class="form-select" name="tema">
                        <?php $tema = $user->tema ?? 'system'; ?>
                        <option value="system" <?= $tema === 'system' ? 'selected' : '' ?>>Seguir o sistema</option>
                        <option value="dark" <?= $tema === 'dark'  ? 'selected' : '' ?>>Escuro</option>
                        <option value="light" <?= $tema === 'light' ? 'selected' : '' ?>>Claro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Moeda padrão</label>
                    <select class="form-select" name="moeda">
                        <?php $moeda = $user->moeda ?? 'BRL'; ?>
                        <option value="BRL" <?= $moeda === 'BRL' ? 'selected' : '' ?>>BRL (R$)</option>
                        <option value="USD" <?= $moeda === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                        <option value="EUR" <?= $moeda === 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Avatar</label>
                    <input class="form-input" name="avatar" id="avatarInput" type="file" accept="image/*">
                    <small style="opacity:.7;">JPG/PNG/WEBP até 2MB</small>
                </div>
            </div>

            <hr style="border-color:var(--glass-border);margin:16px 0;">

            <h3 style="margin-bottom:8px;">Trocar senha (opcional)</h3>
            <div class="grid" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;">
                <div class="form-group">
                    <label>Senha atual</label>
                    <input class="form-input" name="senha_atual" type="password" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label>Nova senha</label>
                    <input class="form-input" name="nova_senha" type="password" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label>Confirmar senha</label>
                    <input class="form-input" name="conf_senha" type="password" autocomplete="new-password">
                </div>
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

        const form = document.getElementById('profileForm');
        const input = document.getElementById('avatarInput');
        const img = document.getElementById('avatarPreview');
        const btnCancel = document.getElementById('btnCancel');

        // preview do avatar
        input?.addEventListener('change', () => {
            const f = input.files?.[0];
            if (!f) return;
            if (!f.type.match(/^image\//)) return;
            const url = URL.createObjectURL(f);
            img.src = url;
            img.onload = () => URL.revokeObjectURL(url);
        });

        // cancelar = volta (ou recarrega)
        btnCancel?.addEventListener('click', () => history.length > 1 ? history.back() : location.href = BASE + 'dashboard');

        // submit via fetch multipart
        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(form);

            try {
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

                // se quiser refletir novo avatar/username no layout (menu/header), faça aqui:
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