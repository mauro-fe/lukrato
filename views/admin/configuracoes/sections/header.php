    <div class="profile-header surface-card surface-card--interactive surface-card--clip" id="profileHeaderSection">
        <div class="profile-header__main">
            <div class="profile-avatar-wrapper profile-avatar-wrapper--compact">
                <div class="profile-avatar" id="profileAvatar">
                    <span class="avatar-initials" id="avatarInitials">U</span>
                    <img class="avatar-img" id="avatarImg" alt="Foto de perfil" style="display:none">
                </div>
                <button type="button" class="avatar-edit-btn" id="avatarEditBtn" title="Alterar foto de perfil">
                    <i data-lucide="camera"></i>
                </button>
                <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp" hidden>
            </div>

            <div class="profile-header__content">
                <span class="profile-header__eyebrow">Conta</span>

                <div class="profile-header-top">
                    <h1 class="profile-title">Configurações</h1>

                    <button type="button" class="lk-info" data-lk-tooltip-title="Configurações da conta"
                        data-lk-tooltip="Gerencie segurança, integrações e preferências da sua conta."
                        aria-label="Ajuda: Configurações da conta">
                        <i data-lucide="info" aria-hidden="true"></i>
                    </button>
                </div>

                <p class="profile-subtitle">Segurança, aparência e integrações da sua conta em uma composição mais limpa e consistente com o resto do painel.</p>
            </div>
        </div>

        <div class="profile-header__highlights" aria-label="Áreas principais das configurações">
            <span class="profile-highlight">
                <i data-lucide="shield-check" aria-hidden="true"></i>
                <span>Segurança</span>
            </span>
            <span class="profile-highlight">
                <i data-lucide="palette" aria-hidden="true"></i>
                <span>Aparência</span>
            </span>
            <span class="profile-highlight">
                <i data-lucide="plug" aria-hidden="true"></i>
                <span>Integrações</span>
            </span>
        </div>
    </div>
