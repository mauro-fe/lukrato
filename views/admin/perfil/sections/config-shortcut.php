    <?php if ($isProfileView): ?>
        <div class="profile-section surface-card surface-card--interactive" id="profileConfigShortcutSection">
            <div class="section-header">
                <div class="section-icon"><i data-lucide="settings" style="color:white"></i></div>
                <div class="section-header-text">
                    <h3>Configurações da Conta</h3>
                    <p>Acesse segurança, integrações e preferências avançadas</p>
                </div>
            </div>
            <div class="form-actions" style="margin-top: 0.5rem;">
                <a href="<?= BASE_URL ?>configuracoes" class="btn-save surface-button surface-button--primary">
                    <span><i data-lucide="arrow-right"></i> Ir para Configurações</span>
                </a>
            </div>
        </div>
    <?php endif; ?>
