<?php
// Header incluído automaticamente pelo framework render() — não duplicar
?>

<div class="main-content">
    <div class="cupons-container">
        <!-- Botão Voltar -->
        <a href="<?= BASE_URL ?>sysadmin" class="btn-voltar">
            <i data-lucide="arrow-left"></i>
            <span>Voltar ao Painel</span>
        </a>

        <!-- Header -->
        <div class="cupons-header">
            <div class="cupons-header-title">
                <div class="cupons-header-icon">
                    <i data-lucide="ticket"></i>
                </div>
                <div>
                    <h1>Gerenciar Cupons de Desconto</h1>
                    <p>Crie e gerencie cupons promocionais para seus clientes</p>
                </div>
            </div>
            <button class="btn-criar-cupom" data-action="abrirModalCriarCupom">
                <i data-lucide="circle-plus"></i>
                Criar Novo Cupom
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="cupons-stats" id="cuponsStats" style="display: none;">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i data-lucide="ticket"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statTotalCupons">0</h3>
                    <p>Total de Cupons</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i data-lucide="circle-check"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statCuponsAtivos">0</h3>
                    <p>Cupons Ativos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i data-lucide="line-chart"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statTotalUsos">0</h3>
                    <p>Total de Usos</p>
                </div>
            </div>
        </div>

        <!-- Tabela de Cupons -->
        <div class="cupons-table-container">
            <div class="table-header">
                <h2><i data-lucide="list"></i> Lista de Cupons</h2>
            </div>
            <div id="loading" class="lk-loading-state">
                <i data-lucide="loader-2"></i>
                <p>Carregando cupons...</p>
            </div>
            <table class="cupons-table" id="cuponsTable" style="display: none;">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Desconto</th>
                        <th>Tipo</th>
                        <th>Validade</th>
                        <th>Uso</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="cuponsTableBody">
                    <!-- Preenchido via JavaScript -->
                </tbody>
            </table>
            <div id="emptyState" class="empty-state" style="display: none;">
                <i data-lucide="ticket"></i>
                <h3>Nenhum cupom cadastrado</h3>
                <p>Crie seu primeiro cupom de desconto para começar</p>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 já carregado no header -->
<div id="modalCupom" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>
                <i data-lucide="circle-plus"></i>
                <span id="modalTitle">Criar Novo Cupom</span>
            </h2>
            <button class="btn-close" data-action="fecharModalCupom">×</button>
        </div>
        <form id="formCupom">
            <div class="modal-body">
                <div class="form-group">
                    <label for="codigo">Código do Cupom *</label>
                    <input type="text" id="codigo" name="codigo" required placeholder="Ex: PROMO10, BLACKFRIDAY"
                        style="text-transform: uppercase;">
                    <small>Use apenas letras e números, sem espaços</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_desconto">Tipo de Desconto *</label>
                        <select id="tipo_desconto" name="tipo_desconto" required data-action="atualizarPlaceholder">
                            <option value="percentual">Percentual (%)</option>
                            <option value="fixo">Valor Fixo (R$)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="valor_desconto">Valor do Desconto *</label>
                        <input type="number" id="valor_desconto" name="valor_desconto" required min="0" step="0.01"
                            placeholder="10">
                        <small id="descontoHelp">Desconto em percentual (0-100)</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="valido_ate">Válido Até</label>
                        <input type="date" id="valido_ate" name="valido_ate">
                        <small>Deixe em branco para sem limite</small>
                    </div>

                    <div class="form-group">
                        <label for="hora_valido_ate">Até que Horas</label>
                        <input type="time" id="hora_valido_ate" name="hora_valido_ate" value="23:59">
                        <small>Horário limite de validade</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="limite_uso">Limite de Usos</label>
                        <input type="number" id="limite_uso" name="limite_uso" min="0" value="0"
                            placeholder="0 = Ilimitado">
                        <small>0 = Usos ilimitados</small>
                    </div>
                </div>

                <!-- Seção de Elegibilidade -->
                <div class="elegibilidade-section">
                    <div class="elegibilidade-header">
                        <i data-lucide="users"></i>
                        <span>Elegibilidade do Cupom</span>
                    </div>

                    <div class="elegibilidade-content">
                        <div class="elegibilidade-option">
                            <label class="toggle-switch">
                                <input type="checkbox" id="apenas_primeira_assinatura" name="apenas_primeira_assinatura"
                                    checked data-action="toggleReativacao">
                                <span class="toggle-slider"></span>
                            </label>
                            <div class="option-text">
                                <span class="option-title">Apenas novos assinantes</span>
                                <span class="option-desc">Somente quem nunca assinou pode usar</span>
                            </div>
                        </div>

                        <div class="elegibilidade-suboption" id="reativacaoGroup" style="display: none;">
                            <div class="suboption-divider"></div>
                            <div class="elegibilidade-option">
                                <label class="toggle-switch small">
                                    <input type="checkbox" id="permite_reativacao" name="permite_reativacao"
                                        data-action="toggleMesesInatividade">
                                    <span class="toggle-slider"></span>
                                </label>
                                <div class="option-text">
                                    <span class="option-title"><i data-lucide="refresh-cw"></i> Win-back</span>
                                    <span class="option-desc">Permitir ex-assinantes inativos</span>
                                </div>
                            </div>

                            <div class="meses-inatividade-box" id="mesesInatividadeGroup" style="display: none;">
                                <label for="meses_inatividade_reativacao">
                                    <i data-lucide="calendar-days"></i>
                                    Mínimo de inatividade
                                </label>
                                <div class="meses-input-group">
                                    <input type="number" id="meses_inatividade_reativacao"
                                        name="meses_inatividade_reativacao" min="1" max="24" value="3">
                                    <span class="meses-suffix">meses</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição (opcional)</label>
                    <textarea id="descricao" name="descricao" placeholder="Ex: Promoção de lançamento"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-action="fecharModalCupom">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    Salvar Cupom
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JS carregado via Vite (loadPageJs) -->