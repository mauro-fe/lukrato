        <!-- Tab: Segurança -->
        <div class="profile-tab-panel active" id="panel-seguranca" role="tabpanel" aria-labelledby="tab-seguranca">
            <div class="profile-section surface-card surface-card--interactive">
                <div class="section-header">
                    <div class="section-icon"><i data-lucide="lock" style="color:white"></i></div>
                    <div class="section-header-text">
                        <h3>Segurança</h3>
                        <p>Altere sua senha de acesso</p>
                    </div>
                </div>

                <input type="text" name="_fake_user" style="display:none" tabindex="-1" aria-hidden="true">
                <input type="password" name="_fake_pass" style="display:none" tabindex="-1" aria-hidden="true">

                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label"><i data-lucide="key-round" class="icon-label"
                                style="color:#f59e0b"></i> Senha Atual</label>
                        <input class="form-input" id="senha_atual" name="senha_atual" type="password"
                            placeholder="Digite sua senha atual" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i data-lucide="lock" class="icon-label" style="color:#f59e0b"></i>
                            Nova Senha</label>
                        <input class="form-input" id="nova_senha" name="nova_senha" type="password"
                            placeholder="Mínimo 8 caracteres" autocomplete="off" minlength="8">
                        <div class="pwd-strength" id="pwdStrengthProfile">
                            <div class="pwd-bar-label">
                                <span>Força da senha</span>
                                <span class="pwd-level" id="pwdLevelProfile"></span>
                            </div>
                            <div class="pwd-bar-wrap">
                                <div class="pwd-bar-fill" id="pwdBarFillProfile"></div>
                            </div>
                            <div class="pwd-divider"></div>
                            <div class="pwd-reqs">
                                <div class="pwd-req" id="prof-req-length"><span class="req-icon"></span> 8+ caracteres
                                </div>
                                <div class="pwd-req" id="prof-req-lower"><span class="req-icon"></span> Letra minúscula
                                </div>
                                <div class="pwd-req" id="prof-req-upper"><span class="req-icon"></span> Letra maiúscula
                                </div>
                                <div class="pwd-req" id="prof-req-number"><span class="req-icon"></span> Número</div>
                                <div class="pwd-req" id="prof-req-special"><span class="req-icon"></span> Caractere
                                    especial</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i data-lucide="circle-check" class="icon-label"
                                style="color:#22c55e"></i> Confirmar
                            Senha</label>
                        <input class="form-input" id="conf_senha" name="conf_senha" type="password"
                            placeholder="Digite novamente" autocomplete="off" minlength="8">
                        <div class="pwd-match" id="pwdMatchProfile">
                            <span class="match-icon"><i data-lucide="check"></i></span>
                            <span class="match-text"></span>
                        </div>
                    </div>
                </div>

                <div class="form-actions form-actions--inside">
                    <button type="submit" class="btn-save surface-button surface-button--primary" id="btn-save-seguranca">
                        <span><i data-lucide="lock-keyhole"></i> Alterar Senha</span>
                    </button>
                </div>
            </div>
        </div><!-- /panel-seguranca -->
