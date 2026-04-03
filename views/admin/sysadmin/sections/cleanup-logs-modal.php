<div class="modal fade" id="cleanupLogsModal" tabindex="-1" aria-labelledby="cleanupLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cleanup-logs-modal-content" style="--surface-modal-accent: var(--color-danger);">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon" aria-hidden="true">
                        <i data-lucide="trash-2"></i>
                    </div>
                    <div>
                        <h2 class="modal-title" id="cleanupLogsModalLabel">Limpar logs antigos</h2>
                        <p class="modal-subtitle">Escolha o período de retenção e o escopo da limpeza.</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body cleanup-logs-modal-body">
                <p class="cleanup-logs-modal-copy">
                    O sistema pode remover apenas logs resolvidos ou, se você quiser, todos os logs antigos.
                </p>

                <div class="cleanup-logs-form-group">
                    <label class="cleanup-logs-label" for="cleanupLogsDays">
                        <i data-lucide="calendar-clock"></i>
                        Remover registros com mais de
                    </label>
                    <select id="cleanupLogsDays" class="form-select cleanup-logs-select">
                        <option value="7">7 dias</option>
                        <option value="15">15 dias</option>
                        <option value="30" selected>30 dias</option>
                        <option value="60">60 dias</option>
                        <option value="90">90 dias</option>
                        <option value="180">180 dias</option>
                    </select>
                </div>

                <label class="cleanup-logs-toggle" for="cleanupLogsIncludeUnresolved">
                    <input type="checkbox" id="cleanupLogsIncludeUnresolved">
                    <span class="cleanup-logs-toggle__control" aria-hidden="true"></span>
                    <span class="cleanup-logs-toggle__copy">
                        <strong>Incluir logs não resolvidos</strong>
                        <small>Use isso só quando quiser fazer uma limpeza ampla do histórico.</small>
                    </span>
                </label>

                <div class="cleanup-logs-modal-hint" id="cleanupLogsModalHint">
                    Serão removidos apenas logs <strong>resolvidos</strong> há mais de 30 dias. Logs ainda abertos serão preservados.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="cleanupLogsConfirmBtn">
                    <i data-lucide="trash-2"></i>
                    <span>Limpar resolvidos antigos</span>
                </button>
            </div>
        </div>
    </div>
</div>
