    <div class="profile-customize-trigger">
        <button class="profile-customize-open" id="btnCustomizePerfil" type="button">
            <i data-lucide="sliders-horizontal"></i>
            <span>Personalizar tela</span>
        </button>
    </div>

    <div class="profile-customize-overlay" id="perfilCustomizeModalOverlay" style="display:none;">
        <div class="profile-customize-modal surface-card" role="dialog" aria-modal="true"
            aria-labelledby="perfilCustomizeModalTitle">
            <div class="profile-customize-header">
                <h3 class="profile-customize-title" id="perfilCustomizeModalTitle">Personalizar configurações</h3>
                <button class="profile-customize-close" id="btnCloseCustomizePerfil" type="button"
                    aria-label="Fechar personalização">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="profile-customize-body">
                <p class="profile-customize-desc">Comece no modo essencial e habilite os blocos quando quiser.</p>

                <div class="profile-customize-presets" role="group" aria-label="Preset de visualizacao">
                    <button class="profile-customize-preset" id="btnPresetEssencialPerfil" type="button">Modo
                        essencial</button>
                    <button class="profile-customize-preset" id="btnPresetCompletoPerfil" type="button">Modo
                        completo</button>
                </div>

                <div class="profile-customize-group">
                    <p class="profile-customize-group-title">Blocos da tela</p>
                    <label class="profile-customize-toggle">
                        <span>Cabeçalho da página</span>
                        <input type="checkbox" id="togglePerfilHeader" checked>
                    </label>
                    <label class="profile-customize-toggle">
                        <span>Navegação por abas</span>
                        <input type="checkbox" id="togglePerfilTabs" checked>
                    </label>
                </div>
            </div>

            <div class="profile-customize-footer">
                <button class="profile-customize-save" id="btnSaveCustomizePerfil" type="button">Salvar</button>
            </div>
        </div>
    </div>