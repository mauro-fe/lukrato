<div class="profile-tab-panel active" id="panel-dados" role="tabpanel" aria-labelledby="tab-dados">
    <div class="profile-section surface-card surface-card--interactive">
        <div class="section-header">
            <div class="section-icon"><i data-lucide="user" style="color: white"></i></div>
            <div class="section-header-text">
                <h3>Dados Pessoais</h3>
                <p>Informações básicas</p>
            </div>
        </div>

        <div class="form-row cols-1">
            <div class="form-group profile-sync-hidden" aria-hidden="true">
                <label class="form-label" for="nome">Nome Completo *</label>
                <input class="form-input" id="nome" name="nome" type="text" placeholder="Digite seu nome completo"
                    required tabindex="-1" autocomplete="off">
            </div>

            <div class="profile-quick-name" data-profile-display-name-root>
                <div class="profile-quick-name__head">
                    <div>
                        <span class="profile-quick-name__eyebrow">Atualização rápida</span>
                        <h4 class="profile-quick-name__title">Nome de exibição</h4>
                        <p class="profile-quick-name__description">
                            Salva instantaneamente e sincroniza o nome mostrado no topo, avatar e perfil.
                        </p>
                    </div>
                </div>

                <label class="form-label" for="profileDisplayNameInput">
                    <i data-lucide="pencil" class="icon-label" style="color:#6366f1"></i>
                    Nome de exibição
                    *
                </label>

                <div class="profile-quick-name__row">
                    <input class="form-input" id="profileDisplayNameInput" type="text" maxlength="80"
                        autocomplete="nickname" data-role="display-name-input"
                        placeholder="Digite como prefere ser chamado">

                    <button type="button" class="surface-button surface-button--primary"
                        data-action="save-display-name">
                        Salvar nome
                    </button>
                </div>

                <small class="profile-quick-name__hint">
                    O restante dos dados pessoais continua no fluxo atual logo abaixo.
                </small>

                <p class="profile-quick-name__status" data-slot="display-name-status" hidden></p>
            </div>
        </div>

        <div class="form-row cols-1">
            <div class="form-group">
                <label class="form-label"><i data-lucide="mail" class="icon-label" style="color:#3b82f6"></i>
                    E-mail *</label>
                <input class="form-input" id="email" name="email" type="email" placeholder="seu@email.com" required>
            </div>
        </div>

        <div class="form-row cols-1">
            <div class="form-group">
                <label class="form-label"><i data-lucide="tag" class="icon-label" style="color:#f97316"></i>
                    Código de Suporte</label>
                <div class="profile-inline-copy-row">
                    <input class="form-input profile-code-input" id="support_code" type="text" readonly
                        value="Carregando...">
                    <button type="button"
                        class="btn-copy-support surface-button surface-button--subtle surface-button--compact"
                        id="btn-copy-support" onclick="copySupportCode()" title="Copiar código">
                        <i data-lucide="copy"></i>
                    </button>
                </div>
                <small style="color:var(--color-text-muted);font-size:12px;margin-top:4px;display:block;">
                    Use este código ao entrar em contato com o suporte
                </small>
            </div>
        </div>

        <div class="form-row cols-1">
            <div class="form-group">
                <label class="form-label"><i data-lucide="fingerprint" class="icon-label" style="color:#8b5cf6"></i>
                    CPF</label>
                <input class="form-input" id="cpf" name="cpf" type="text" inputmode="numeric" maxlength="14"
                    placeholder="000.000.000-00">
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label"><i data-lucide="calendar" class="icon-label" style="color:#0ea5e9"></i>
                    Nascimento</label>
                <input class="form-input" id="data_nascimento" name="data_nascimento" type="date"
                    max="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><i data-lucide="smartphone" class="icon-label" style="color:#6366f1"></i>
                    Telefone</label>
                <input class="form-input" id="telefone" name="telefone" type="tel" inputmode="tel" maxlength="15"
                    placeholder="(00) 00000-0000">
            </div>
        </div>

        <div class="form-row cols-1">
            <div class="form-group">
                <label class="form-label"><i data-lucide="users" class="icon-label" style="color:#14b8a6"></i>
                    Gênero</label>
                <select class="form-select" name="sexo" id="sexo">
                    <option value="">Selecione</option>
                    <option value="M">Masculino</option>
                    <option value="F">Feminino</option>
                    <option value="O">Outro</option>
                    <option value="NB">Não-binário</option>
                    <option value="N">Prefiro não informar</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-save surface-button surface-button--primary" id="btn-save-dados">
            <span><i data-lucide="save"></i> Salvar Dados Pessoais</span>
        </button>
    </div>
</div>