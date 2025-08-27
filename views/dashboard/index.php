<!-- Base URL e CSRF para o JS -->
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token('default') ?? '', ENT_QUOTES, 'UTF-8'); ?>">
<script>
    // BASE_URL sempre com / no final
    window.BASE_URL = "<?= rtrim(BASE_URL ?? '/', '/') . '/'; ?>";
</script>

<!---------------------TTTTTTTTTTTTTEEEEEEEEEEEEEEESSSSSSSSSSSSSSSTTTTTTTTTTTTTSSSSSSSSS---->

<!-- Chart.js UMD (necessário antes do dashboard-index.js) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<section>
    <!-- Conteúdo -->
    <div class="container">
        <!-- KPIs -->
        <section class="kpi-grid" role="region" aria-label="Indicadores principais">
            <div class="card kpi-card" id="saldoCard">
                <div class="card-header">
                    <div class="kpi-icon saldo"><i class="fas fa-wallet"></i></div>
                    <span class="kpi-title">Saldo Atual</span>
                </div>
                <div class="kpi-value" id="saldoValue">R$ 0,00</div>
            </div>

            <div class="card kpi-card" id="receitasCard">
                <div class="card-header">
                    <div class="kpi-icon receitas"><i class="fas fa-arrow-up"></i></div>
                    <span class="kpi-title">Receitas do Mês</span>
                </div>
                <div class="kpi-value receitas" id="receitasValue">R$ 0,00</div>
            </div>

            <div class="card kpi-card" id="despesasCard">
                <div class="card-header">
                    <div class="kpi-icon despesas"><i class="fas fa-arrow-down"></i></div>
                    <span class="kpi-title">Despesas do Mês</span>
                </div>
                <div class="kpi-value despesas" id="despesasValue">R$ 0,00</div>
            </div>
        </section>

        <!-- Gráfico + Resumo -->
        <section class="charts-grid">
            <div class="card chart-card">
                <div class="card-header">
                    <h2 class="card-title">Evolução Financeira</h2>
                </div>
                <div class="chart-container"><canvas id="evolutionChart" role="img"
                        aria-label="Gráfico de evolução do saldo"></canvas></div>
            </div>

            <div class="card summary-card">
                <div class="card-header">
                    <h2 class="card-title">Resumo Mensal</h2>
                </div>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Total Receitas</span><span
                            class="summary-value receitas" id="totalReceitas">R$ 0,00</span></div>
                    <div class="summary-item"><span class="summary-label">Total Despesas</span><span
                            class="summary-value despesas" id="totalDespesas">R$ 0,00</span></div>
                    <div class="summary-item"><span class="summary-label">Resultado</span><span class="summary-value"
                            id="resultadoMes">R$ 0,00</span></div>
                    <div class="summary-item"><span class="summary-label">Saldo Acumulado</span><span
                            class="summary-value" id="saldoAcumulado">R$ 0,00</span></div>
                </div>
            </div>
        </section>

        <!-- Tabela -->
        <section class="card table-card">
            <div class="card-header">
                <h2 class="card-title">Últimos Lançamentos</h2>
            </div>
            <div class="table-container">
                <div class="empty-state" id="emptyState" style="display:none;">
                    <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                    <h3>Nenhum lançamento encontrado</h3>
                    <p>Adicione sua primeira transação clicando no botão + no canto inferior direito</p>
                </div>
                <table class="table" id="transactionsTable">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Conta</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsTableBody"></tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- FAB -->
    <div class="fab-container">
        <button class="fab" id="fabButton" aria-label="Adicionar transação" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-plus"></i>
        </button>
        <div class="fab-menu" id="fabMenu" role="menu">
            <button class="fab-menu-item" data-modal="receita" role="menuitem"><i
                    class="fas fa-arrow-up"></i><span>Receita</span></button>
            <button class="fab-menu-item" data-modal="despesa" role="menuitem"><i
                    class="fas fa-arrow-down"></i><span>Despesa</span></button>
            <button class="fab-menu-item" data-modal="despesa-cartao" role="menuitem"><i
                    class="fas fa-credit-card"></i><span>Despesa Cartão</span></button>
            <button class="fab-menu-item" data-modal="transferencia" role="menuitem"><i
                    class="fas fa-exchange-alt"></i><span>Transferência</span></button>
        </div>
    </div>
</section>




<!-- Modal Receita -->
<div class="modal" id="modalReceita" role="dialog" aria-labelledby="modalReceitaTitle" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalReceitaTitle">Nova Receita</h2>
            <button class="modal-close" aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form class="modal-body" id="formReceita">
            <div class="form-group">
                <label for="receitaData">Data</label>
                <input type="date" id="receitaData" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="receitaCategoria">Categoria</label>
                <select id="receitaCategoria" class="form-select" required>
                    <option value="">Selecione uma categoria</option>
                </select>
            </div>
            <div class="form-group">
                <label for="receitaConta">Conta</label>
                <select id="receitaConta" class="form-select" required>
                    <option value="">Selecione uma conta</option>
                </select>
            </div>
            <div class="form-group">
                <label for="receitaDescricao">Descrição</label>
                <input type="text" id="receitaDescricao" class="form-input" placeholder="Descrição da receita" required>
            </div>

            <div class="form-group">
                <label for="receitaObservacao">Observação (opcional)</label>
                <input type="text" id="receitaObservacao" class="form-input" placeholder="Detalhe, nota interna...">
            </div>

            <div class="form-group">
                <label for="receitaValor">Valor</label>
                <input type="text" id="receitaValor" class="form-input money-mask" placeholder="R$ 0,00" required>
            </div>

            <!-- Conta → opcional -->
            <div class="form-group">
                <label for="receitaConta">Conta</label>
                <select id="receitaConta" class="form-select">
                    <option value="">—</option>
                </select>
            </div>

            <!-- Checkbox é só visual (não vai pro banco) -->
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="receitaPago">
                    <span class="checkbox-custom"></span>
                    Foi recebido?
                </label>
            </div>

        </form>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-dismiss="modal">Cancelar</button>
            <button type="submit" form="formReceita" class="btn btn-primary">Salvar Receita</button>
        </div>
    </div>
</div>

<!-- Modal Despesa -->
<div class="modal" id="modalDespesa" role="dialog" aria-labelledby="modalDespesaTitle" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalDespesaTitle">Nova Despesa</h2>
            <button class="modal-close" aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form class="modal-body" id="formDespesa">
            <div class="form-group">
                <label for="despesaData">Data</label>
                <input type="date" id="despesaData" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="despesaCategoria">Categoria</label>
                <select id="despesaCategoria" class="form-select" required>
                    <option value="">Selecione uma categoria</option>
                </select>
            </div>
            <div class="form-group">
                <label for="despesaConta">Conta</label>
                <select id="despesaConta" class="form-select" required>
                    <option value="">Selecione uma conta</option>
                </select>
            </div>
            <div class="form-group">
                <label for="despesaDescricao">Descrição</label>
                <input type="text" id="despesaDescricao" class="form-input" placeholder="Descrição da despesa">
            </div>

            <div class="form-group">
                <label for="despesaObservacao">Observação (opcional)</label>
                <input type="text" id="despesaObservacao" class="form-input" placeholder="Detalhe, nota interna...">
            </div>

            <div class="form-group">
                <label for="despesaValor">Valor</label>
                <input type="text" id="despesaValor" class="form-input money-mask" placeholder="R$ 0,00" required>
            </div>

            <!-- Conta → opcional -->
            <div class="form-group">
                <label for="despesaConta">Conta</label>
                <select id="despesaConta" class="form-select">
                    <option value="">—</option>
                </select>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="despesaPago">
                    <span class="checkbox-custom"></span>
                    Foi pago?
                </label>
            </div>

        </form>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-dismiss="modal">Cancelar</button>
            <button type="submit" form="formDespesa" class="btn btn-primary">Salvar Despesa</button>
        </div>
    </div>
</div>

<!-- Modal Despesa Cartão -->
<div class="modal" id="modalDespesaCartao" role="dialog" aria-labelledby="modalDespesaCartaoTitle" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalDespesaCartaoTitle">Nova Despesa no Cartão</h2>
            <button class="modal-close" aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form class="modal-body" id="formDespesaCartao">
            <div class="form-group">
                <label for="despesaCartaoData">Data da Compra</label>
                <input type="date" id="despesaCartaoData" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="despesaCartaoCartao">Cartão</label>
                <select id="despesaCartaoCartao" class="form-select" required>
                    <option value="">Selecione um cartão</option>
                </select>
            </div>
            <div class="form-group">
                <label for="despesaCartaoCategoria">Categoria</label>
                <select id="despesaCartaoCategoria" class="form-select" required>
                    <option value="">Selecione uma categoria</option>
                </select>
            </div>
            <div class="form-group">
                <label for="despesaCartaoDescricao">Descrição</label>
                <input type="text" id="despesaCartaoDescricao" class="form-input" placeholder="Descrição da compra">
            </div>
            <div class="form-group">
                <label for="despesaCartaoValor">Valor</label>
                <input type="text" id="despesaCartaoValor" class="form-input money-mask" placeholder="R$ 0,00" required>
            </div>
            <div class="form-group">
                <label for="despesaCartaoParcelas">Parcelas</label>
                <select id="despesaCartaoParcelas" class="form-select" required>
                    <option value="1">1x (à vista)</option>
                    <option value="2">2x sem juros</option>
                    <option value="3">3x sem juros</option>
                    <option value="4">4x sem juros</option>
                    <option value="5">5x sem juros</option>
                    <option value="6">6x sem juros</option>
                    <option value="7">7x sem juros</option>
                    <option value="8">8x sem juros</option>
                    <option value="9">9x sem juros</option>
                    <option value="10">10x sem juros</option>
                    <option value="11">11x sem juros</option>
                    <option value="12">12x sem juros</option>
                </select>
            </div>
        </form>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-dismiss="modal">Cancelar</button>
            <button type="submit" form="formDespesaCartao" class="btn btn-primary">Salvar Despesa</button>
        </div>
    </div>
</div>

<!-- Modal Transferência -->
<div class="modal" id="modalTransferencia" role="dialog" aria-labelledby="modalTransferenciaTitle" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTransferenciaTitle">Nova Transferência</h2>
            <button class="modal-close" aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form class="modal-body" id="formTransferencia">
            <div class="form-group">
                <label for="transferenciaData">Data</label>
                <input type="date" id="transferenciaData" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="transferenciaOrigem">Conta de Origem</label>
                <select id="transferenciaOrigem" class="form-select" required>
                    <option value="">Selecione a conta de origem</option>
                </select>
            </div>
            <div class="form-group">
                <label for="transferenciaDestino">Conta de Destino</label>
                <select id="transferenciaDestino" class="form-select" required>
                    <option value="">Selecione a conta de destino</option>
                </select>
            </div>
            <div class="form-group">
                <label for="transferenciaValor">Valor</label>
                <input type="text" id="transferenciaValor" class="form-input money-mask" placeholder="R$ 0,00" required>
            </div>
            <div class="form-group">
                <label for="transferenciaObservacao">Observação</label>
                <input type="text" id="transferenciaObservacao" class="form-input" placeholder="Observação (opcional)">
            </div>
        </form>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-dismiss="modal">Cancelar</button>
            <button type="submit" form="formTransferencia" class="btn btn-primary">Fazer Transferência</button>
        </div>
    </div>
</div>

<!-- Dashboard JavaScript -->
<script src="assets/js/dashboard.js"></script>

<!-- Modal: Seletor de Data/Mês -->
<div class="modal" id="monthPickerModal" aria-hidden="true" role="dialog" aria-labelledby="monthPickerTitle">
    <div class="modal-backdrop" data-close-month></div>

    <div class="modal-content" role="document">
        <div class="modal-header" style="gap: 12px;">
            <h2 id="monthPickerTitle" style="margin-right:auto;">Escolher data</h2>

            <div class="month-picker-nav" aria-live="polite">
                <button class="month-nav-btn" id="mpPrev" aria-label="Mês anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span id="mpLabel" class="mp-label" style="min-width:170px;text-align:center;font-weight:700;"></span>
                <button class="month-nav-btn" id="mpNext" aria-label="Próximo mês">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <button class="modal-close" data-close-month aria-label="Fechar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
            <div class="calendar">
                <div class="calendar-weekdays">
                    <span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
                </div>
                <div class="calendar-grid" id="calendarGrid"></div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-ghost" data-close-month>Cancelar</button>
            <button class="btn btn-primary" id="mpConfirm">Usar mês</button>
        </div>
    </div>
</div>