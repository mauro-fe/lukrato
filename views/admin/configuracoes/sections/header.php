    <div class="profile-header surface-card surface-card--interactive surface-card--clip" id="profileHeaderSection">
        <div class="profile-avatar-wrapper">
            <div class="profile-avatar" id="profileAvatar">
                <span class="avatar-initials" id="avatarInitials">U</span>
                <img class="avatar-img" id="avatarImg" alt="Foto de perfil" style="display:none">
            </div>
            <button type="button" class="avatar-edit-btn" id="avatarEditBtn" title="Alterar foto de perfil">
                <i data-lucide="camera"></i>
            </button>
            <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp" hidden>
        </div>

        <div class="profile-header-top">
            <h1 class="profile-title">Configurações</h1>

            <button type="button" class="lk-info" data-lk-tooltip-title="Configurações da conta"
                data-lk-tooltip="Gerencie segurança, integrações e preferências da sua conta."
                aria-label="Ajuda: Configurações da conta">
                <i data-lucide="info" aria-hidden="true"></i>
            </button>
        </div>

        <p class="profile-subtitle">Gerencie segurança, integrações e preferências da conta</p>
    </div>