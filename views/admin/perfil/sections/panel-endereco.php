        <!-- Tab: Endereço -->
        <div class="profile-tab-panel" id="panel-endereco" role="tabpanel" aria-labelledby="tab-endereco">
            <div class="profile-section surface-card surface-card--interactive">
                <div class="section-header">
                    <div class="section-icon"><i data-lucide="map-pin" style="color:white"></i></div>
                    <div class="section-header-text">
                        <h3>Endereço</h3>
                        <p>Informações de localização</p>
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="mail-open" class="icon-label"
                                style="color:#f97316"></i> CEP</label>
                        <input class="form-input" id="end_cep" name="endereco[cep]" type="text" inputmode="numeric"
                            placeholder="00000-000" maxlength="9">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="map" class="icon-label" style="color:#22c55e"></i>
                            Estado</label>
                        <input class="form-input" id="end_estado" name="endereco[estado]" type="text" placeholder="SP"
                            maxlength="2" style="text-transform: uppercase;">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="building-2" class="icon-label"
                                style="color:#64748b"></i> Cidade</label>
                        <input class="form-input" id="end_cidade" name="endereco[cidade]" type="text"
                            placeholder="São Paulo">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="home" class="icon-label" style="color:#f97316"></i>
                            Bairro</label>
                        <input class="form-input" id="end_bairro" name="endereco[bairro]" type="text"
                            placeholder="Centro">
                    </div>
                </div>

                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="route" class="icon-label" style="color:#3b82f6"></i>
                            Rua/Avenida</label>
                        <input class="form-input" id="end_rua" name="endereco[rua]" type="text"
                            placeholder="Rua das Flores">
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="hash" class="icon-label" style="color:#6366f1"></i>
                            Número</label>
                        <input class="form-input" id="end_numero" name="endereco[numero]" type="text" placeholder="123">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="building" class="icon-label"
                                style="color:#64748b"></i> Complemento</label>
                        <input class="form-input" id="end_complemento" name="endereco[complemento]" type="text"
                            placeholder="Apto, Bloco (opcional)">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save surface-button surface-button--primary" id="btn-save-endereco">
                    <span><i data-lucide="save"></i> Salvar Endereço</span>
                </button>
            </div>
        </div><!-- /panel-endereco -->
