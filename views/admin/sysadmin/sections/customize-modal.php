<div class="sys-customize-overlay" id="sysadminCustomizeModalOverlay" style="display:none;">
    <div class="sys-customize-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="sysadminCustomizeModalTitle">
        <div class="sys-customize-header">
            <h3 class="sys-customize-title" id="sysadminCustomizeModalTitle">Personalizar sysadmin</h3>
            <button class="sys-customize-close" id="btnCloseCustomizeSysadmin" type="button" aria-label="Fechar">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="sys-customize-body">
            <p class="sys-customize-desc">Comece no modo essencial e habilite os blocos quando quiser.</p>

            <div class="sys-customize-presets" role="group" aria-label="Preset de visualizacao">
                <button class="sys-customize-preset" id="btnPresetEssencialSysadmin" type="button">Modo
                    essencial</button>
                <button class="sys-customize-preset" id="btnPresetCompletoSysadmin" type="button">Modo
                    completo</button>
            </div>

            <div class="sys-customize-group">
                <p class="sys-customize-group-title">Blocos da tela</p>
                <label class="sys-customize-toggle">
                    <span>Cards de status</span>
                    <input type="checkbox" id="toggleSysStats" checked>
                </label>
                <label class="sys-customize-toggle">
                    <span>Menu de abas</span>
                    <input type="checkbox" id="toggleSysTabs" checked>
                </label>
                <label class="sys-customize-toggle">
                    <span>Painel visao geral</span>
                    <input type="checkbox" id="toggleSysDashboard" checked>
                </label>
                <label class="sys-customize-toggle">
                    <span>Painel feedback</span>
                    <input type="checkbox" id="toggleSysFeedback" checked>
                </label>
            </div>
        </div>

        <div class="sys-customize-footer">
            <button class="sys-customize-save" id="btnSaveCustomizeSysadmin" type="button">Salvar</button>
        </div>
    </div>
</div>