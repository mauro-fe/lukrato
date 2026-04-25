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
                    <h1 class="profile-title">Meu Perfil</h1>

                    <button type="button" class="lk-info" data-lk-tooltip-title="Perfil completo"
                        data-lk-tooltip="Manter seus dados completos ajuda na segurança da conta, recuperação de acesso e suporte."
                        aria-label="Ajuda: Perfil completo">
                        <i data-lucide="info" aria-hidden="true"></i>
                    </button>
                </div>

                <p class="profile-subtitle">Nome, contato e endereço organizados com a mesma hierarquia visual das outras áreas do produto.</p>
            </div>
        </div>

        <div class="profile-header__highlights" aria-label="Áreas principais do perfil">
            <span class="profile-highlight">
                <i data-lucide="sparkles" aria-hidden="true"></i>
                <span>Nome e foto</span>
            </span>
            <span class="profile-highlight">
                <i data-lucide="fingerprint" aria-hidden="true"></i>
                <span>Dados pessoais</span>
            </span>
            <span class="profile-highlight">
                <i data-lucide="map-pin" aria-hidden="true"></i>
                <span>Endereço</span>
            </span>
        </div>
    </div>
