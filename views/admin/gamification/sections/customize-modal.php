<div class="gam-customize-overlay" id="gamificationCustomizeModalOverlay" style="display:none;">
    <div class="gam-customize-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="gamificationCustomizeModalTitle">
        <div class="gam-customize-header">
            <h3 class="gam-customize-title" id="gamificationCustomizeModalTitle">Personalizar gamificacao</h3>
            <button class="gam-customize-close" id="btnCloseCustomizeGamification" type="button" aria-label="Fechar">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="gam-customize-body">
            <p class="gam-customize-desc">Comece no modo essencial e habilite os blocos quando quiser.</p>

            <div class="gam-customize-presets" role="group" aria-label="Preset de visualização">
                <button class="gam-customize-preset" id="btnPresetEssencialGamification" type="button">Modo
                    essencial</button>
                <button class="gam-customize-preset" id="btnPresetCompletoGamification" type="button">Modo
                    completo</button>
            </div>

            <div class="gam-customize-group">
                <p class="gam-customize-group-title">Blocos da tela</p>
                <label class="gam-customize-toggle">
                    <span>Cabeçalho</span>
                    <input type="checkbox" id="toggleGamHeader" checked>
                </label>
                <label class="gam-customize-toggle">
                    <span>Progresso geral</span>
                    <input type="checkbox" id="toggleGamProgress" checked>
                </label>
                <label class="gam-customize-toggle">
                    <span>Conquistas</span>
                    <input type="checkbox" id="toggleGamAchievements" checked>
                </label>
                <label class="gam-customize-toggle">
                    <span>Historico recente</span>
                    <input type="checkbox" id="toggleGamHistory" checked>
                </label>
                <label class="gam-customize-toggle">
                    <span>Ranking</span>
                    <input type="checkbox" id="toggleGamLeaderboard" checked>
                </label>
            </div>
        </div>

        <div class="gam-customize-footer">
            <button class="gam-customize-save" id="btnSaveCustomizeGamification" type="button">Salvar</button>
        </div>
    </div>
</div>