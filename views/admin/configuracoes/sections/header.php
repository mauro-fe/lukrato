    <div class="profile-header surface-card surface-card--interactive surface-card--clip" id="profileHeaderSection">
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
            <h1 class="profile-title">Configuracoes</h1>

            <button type="button" class="lk-info" data-lk-tooltip-title="Configuracoes da conta"
                data-lk-tooltip="Gerencie seguranca, integracoes e preferencias da sua conta."
                aria-label="Ajuda: Configuracoes da conta">
                <i data-lucide="info" aria-hidden="true"></i>
            </button>
        </div>

        <p class="profile-subtitle">Gerencie seguranca, integracoes e preferencias da conta</p>
    </div>
