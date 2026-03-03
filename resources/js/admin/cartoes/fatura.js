/**
 * Cartões Manager – Fatura Modal module
 * Extracted from cartoes-manager.js (monolith → modules)
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { refreshIcons } from '../shared/ui.js';

export const FaturaModal = {
    /**
     * Ver fatura do cartão - redireciona para página de faturas
     */
    verFatura(cartaoId, mes = null, ano = null) {
        // Data atual para filtros (se não especificado)
        const hoje = new Date();
        mes = mes || hoje.getMonth() + 1; // 1-12
        ano = ano || hoje.getFullYear();

        // Redirecionar para página de faturas com filtro do cartão
        window.location.href = `${CONFIG.BASE_URL}faturas?cartao_id=${cartaoId}&mes=${mes}&ano=${ano}`;
    },

    /**
     * Mostrar modal da fatura
     */
    mostrarModalFatura(fatura, parcelamentos = null, statusPagamento = null, cartaoId = null) {
        // IMPORTANTE: Remover qualquer modal existente antes de criar um novo
        const modalExistente = document.querySelector('.modal-fatura-overlay');
        if (modalExistente) {
            modalExistente.remove();
        }

        const modal = FaturaModal.criarModalFatura(fatura, parcelamentos, statusPagamento, cartaoId);
        document.body.appendChild(modal);
        refreshIcons();

        // Animar entrada
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);

        // Fechar ao clicar fora
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                FaturaModal.fecharModalFatura(modal);
            }
        });

        // Botão fechar
        modal.querySelector('.btn-fechar-fatura')?.addEventListener('click', () => {
            FaturaModal.fecharModalFatura(modal);
        });

        // Gerenciar seleção de parcelas (aguardar renderização completa)
        requestAnimationFrame(() => {
            FaturaModal.setupParcelaSelection(modal, fatura);
        });

        // Botão pagar parcelas selecionadas
        modal.querySelector('.btn-pagar-fatura')?.addEventListener('click', () => {
            FaturaModal.pagarParcelasSelecionadas(fatura);
        });
    },

    /**
     * Configurar seleção de parcelas
     */
    setupParcelaSelection(modal, fatura) {
        const selectAll = modal.querySelector('#selectAllParcelas');
        const checkboxes = modal.querySelectorAll('.parcela-checkbox');
        const totalElement = modal.querySelector('#totalSelecionado');

        // Guard: se já foi configurado, não configurar novamente
        if (modal.dataset.parcelasConfigured === 'true') {
            return;
        }
        modal.dataset.parcelasConfigured = 'true';


        // Atualizar total quando mudar seleção
        const atualizarTotal = () => {
            let total = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    total += parseFloat(cb.dataset.valor);
                }
            });
            if (totalElement) {
                totalElement.textContent = Utils.formatMoney(total);
            }
        };

        // Selecionar/desselecionar todos
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                checkboxes.forEach(cb => {
                    cb.checked = e.target.checked;
                });
                atualizarTotal();
            });
        }

        // Atualizar ao mudar checkbox individual
        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                atualizarTotal();
                // Atualizar estado do "selecionar todos"
                if (selectAll) {
                    const todasMarcadas = Array.from(checkboxes).every(c => c.checked);
                    selectAll.checked = todasMarcadas;
                }
            });
        });

        // Inicializar total
        atualizarTotal();
    },

    /**
     * Pagar parcelas selecionadas
     */
    async pagarParcelasSelecionadas(fatura) {
        const checkboxes = document.querySelectorAll('.parcela-checkbox:checked');



        // Log detalhado de cada checkbox
        checkboxes.forEach((cb, index) => {
        });

        if (checkboxes.length === 0) {
            await Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Selecione pelo menos uma parcela para pagar.'
            });
            return;
        }

        let totalSelecionado = 0;
        checkboxes.forEach(cb => {
            const valor = parseFloat(cb.dataset.valor);
            totalSelecionado += valor;
        });


        const confirmado = await Utils.showConfirmDialog(
            'Confirmar Pagamento',
            `Deseja pagar ${checkboxes.length} parcela(s) no valor total de ${Utils.formatMoney(totalSelecionado)}?`
        );

        if (!confirmado) return;

        await Modules.API.pagarParcelasIndividuais(checkboxes, fatura);
    },

    /**
     * Criar HTML do modal da fatura
     */
    criarModalFatura(fatura, parcelamentos = null, statusPagamento = null, cartaoId = null) {
        // Resolver cor do cartão
        const corCartao = Utils.resolverCorCartao(fatura, cartaoId);

        const modal = document.createElement('div');
        modal.className = 'modal-fatura-overlay';
        modal.innerHTML = `<div class="modal-fatura-container" style="--card-accent: ${corCartao};">${FaturaModal.criarConteudoModal(fatura, parcelamentos, statusPagamento, cartaoId)}</div>`;
        return modal;
    },

    /**
     * Criar conteúdo interno do modal
     */
    criarConteudoModal(fatura, parcelamentos = null, statusPagamento = null, cartaoId = null) {
        // Garantir que temos o cartaoId correto
        const idCartao = cartaoId || fatura.cartao_id || fatura.cartao?.id;

        // Se a fatura está paga, mostrar modal diferente
        if (statusPagamento && statusPagamento.pago) {
            return FaturaModal.criarConteudoModalFaturaPaga(fatura, statusPagamento, parcelamentos, idCartao);
        }

        const totalPendentes = (fatura.itens || []).filter(p => !p.pago).length;
        const totalPagos = (fatura.itens || []).filter(p => p.pago).length;
        const brandLogo = fatura.cartao?.bandeira ? Utils.getBrandIcon(fatura.cartao.bandeira) : null;

        return `
                <div class="modal-fatura-header">
                    <div class="header-top-row">
                        <div class="header-card-identity">
                            ${brandLogo ? `<img src="${brandLogo}" alt="${fatura.cartao.bandeira}" class="header-brand-logo" onerror="this.style.display='none'">` : ''}
                            <div class="header-card-text">
                                <span class="cartao-nome">${fatura.cartao.nome}</span>
                                <span class="cartao-numero">•••• ${fatura.cartao.ultimos_digitos}</span>
                            </div>
                        </div>
                        <div class="header-actions">
                            <button class="btn-historico-toggle" onclick="cartoesManager.toggleHistoricoFatura(${idCartao})" title="Ver histórico">
                                <i data-lucide="history"></i>
                            </button>
                            <button class="btn-fechar-fatura" title="Fechar">
                                <i data-lucide="x"></i>
                            </button>
                        </div>
                    </div>
                    <div class="header-nav-row">
                        <button class="btn-nav-mes" onclick="cartoesManager.navegarMes(${idCartao}, ${fatura.mes}, ${fatura.ano}, -1)" title="Mês anterior">
                            <i data-lucide="chevron-left"></i>
                        </button>
                        <span class="fatura-periodo">${Utils.getNomeMes(fatura.mes)} ${fatura.ano}</span>
                        <button class="btn-nav-mes" onclick="cartoesManager.navegarMes(${idCartao}, ${fatura.mes}, ${fatura.ano}, 1)" title="Próximo mês">
                            <i data-lucide="chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-fatura-body">
                    ${totalPendentes === 0 && totalPagos === 0 ? `
                        <div class="fatura-empty">
                            <div class="empty-icon-wrap">
                                <i data-lucide="inbox"></i>
                            </div>
                            <h3>Nenhum lançamento</h3>
                            <p>Não há compras registradas neste mês.</p>
                        </div>
                    ` : totalPendentes === 0 && totalPagos > 0 ? `
                        <!-- Todas as parcelas já foram pagas -->
                        <div class="fatura-totalmente-paga">
                            <div class="status-paga-header">
                                <div class="status-paga-icon"><i data-lucide="circle-check"></i></div>
                                <h3>Fatura Paga</h3>
                                <p>Todos os lançamentos deste mês foram pagos</p>
                            </div>

                            <div class="fatura-parcelas-pagas-completa">
                                <div class="secao-titulo-bar">
                                    <span class="secao-titulo-text"><i data-lucide="receipt"></i> Itens Pagos</span>
                                    <span class="secao-titulo-count">${totalPagos}</span>
                                </div>
                                <div class="lancamentos-lista">
                                    ${(fatura.itens || []).filter(p => p.pago).map(parcela => FaturaModal.renderItemPago(parcela)).join('')}
                                </div>
                            </div>
                        </div>
                    ` : `
                        <div class="fatura-resumo-principal">
                            <div class="resumo-item resumo-valor-principal">
                                <span class="resumo-label">Total a pagar</span>
                                <strong class="resumo-valor">${Utils.formatMoney(fatura.total)}</strong>
                            </div>
                            <div class="resumo-item resumo-vencimento">
                                <span class="resumo-label">Vencimento</span>
                                <strong class="resumo-data">${Utils.formatDate(fatura.vencimento)}</strong>
                            </div>
                        </div>

                        <div class="fatura-parcelas">
                            <div class="secao-titulo-bar">
                                <label class="checkbox-custom secao-titulo-check">
                                    <input type="checkbox" id="selectAllParcelas">
                                    <span class="checkmark"></span>
                                    <span class="secao-titulo-text">Pendentes</span>
                                </label>
                                <span class="secao-titulo-count">${totalPendentes}</span>
                            </div>
                            <div class="lancamentos-lista">
                                ${(fatura.itens || []).filter(p => !p.pago).map(parcela => `
                                    <div class="lancamento-item">
                                        <label class="checkbox-custom">
                                            <input type="checkbox" class="parcela-checkbox" data-id="${parcela.id}" data-valor="${parcela.valor}">
                                            <span class="checkmark"></span>
                                        </label>
                                        <div class="lanc-info">
                                            <span class="lanc-desc">
                                                ${Utils.escapeHtml(parcela.descricao)}
                                                ${FaturaModal.renderBadgeRecorrente(parcela)}
                                            </span>
                                            ${parcela.data_compra ? `<span class="lanc-data-compra"><i data-lucide="shopping-cart"></i> ${Utils.formatDate(parcela.data_compra)}</span>` : ''}
                                        </div>
                                        <span class="lanc-valor">${Utils.formatMoney(parcela.valor)}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>

                        ${totalPagos > 0 ? `
                            <div class="fatura-parcelas-pagas">
                                <div class="secao-titulo-bar">
                                    <span class="secao-titulo-text"><i data-lucide="circle-check"></i> Pagos</span>
                                    <span class="secao-titulo-count">${totalPagos}</span>
                                </div>
                                <div class="lancamentos-lista">
                                    ${(fatura.itens || []).filter(p => p.pago).map(parcela => FaturaModal.renderItemPago(parcela)).join('')}
                                </div>
                            </div>
                        ` : ''}
                    `}
                </div>

                ${totalPendentes > 0 ? `
                    <div class="modal-fatura-footer">
                        <div class="footer-info">
                            <span class="footer-label">Total selecionado</span>
                            <strong class="footer-valor" id="totalSelecionado">${Utils.formatMoney(fatura.total)}</strong>
                        </div>
                        <button class="btn btn-primary btn-pagar-fatura" id="btnPagarSelecionadas">
                            <i data-lucide="check-circle"></i>
                            Pagar Selecionadas
                        </button>
                    </div>
                ` : ''}
        `;
    },

    /**
     * Renderiza um item pago de fatura (reutilizável)
     */
    renderItemPago(parcela) {
        return `
            <div class="lancamento-item lancamento-pago">
                <div class="lanc-info">
                    <span class="lanc-desc">
                        ${Utils.escapeHtml(parcela.descricao)}
                        ${FaturaModal.renderBadgeRecorrente(parcela)}
                    </span>
                    ${parcela.data_compra ? `<span class="lanc-data-compra"><i data-lucide="shopping-cart"></i> ${Utils.formatDate(parcela.data_compra)}</span>` : ''}
                    <span class="lanc-data-pagamento">
                        <i data-lucide="calendar-check"></i>
                        Pago em ${Utils.formatDate(parcela.data_pagamento || parcela.data)}
                    </span>
                </div>
                <div class="lanc-right">
                    <span class="lanc-valor">${Utils.formatMoney(parcela.valor)}</span>
                    <button class="btn-desfazer-parcela" 
                        onclick="cartoesManager.desfazerPagamentoParcela(${parcela.id})"
                        title="Desfazer pagamento desta parcela">
                        <i data-lucide="undo-2"></i>
                        Desfazer
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Renderiza badge de recorrência para itens de fatura
     */
    renderBadgeRecorrente(parcela) {
        if (!parcela.recorrente) return '';
        const freqLabel = Utils.getFreqLabel(parcela.recorrencia_freq);
        return `<span class="badge-recorrente" title="Assinatura ${freqLabel.toLowerCase()}"><i data-lucide="refresh-cw"></i> ${freqLabel}</span>`;
    },

    /**
     * Fechar modal da fatura
     */
    fecharModalFatura(modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.remove();
        }, 300);
    },

    /**
     * Pagar fatura
     */
    async pagarFatura(fatura) {
        const confirmado = await Utils.showConfirmDialog(
            'Confirmar Pagamento',
            `Deseja pagar a fatura de ${Utils.formatMoney(fatura.total)}?\n\nEsta ação criará um lançamento de despesa na conta vinculada e liberará o limite do cartão.`,
            'Sim, Pagar'
        );

        if (!confirmado) return;

        // Referência ao botão
        const btnPagar = document.querySelector('.btn-pagar-fatura');
        const originalText = btnPagar ? btnPagar.innerHTML : '';

        try {
            // Ativar loading state
            if (btnPagar) {
                btnPagar.disabled = true;
                btnPagar.innerHTML = '<i data-lucide="loader-2" class="icon-spin"></i> Processando...';
                refreshIcons();
                btnPagar.style.opacity = '0.6';
                btnPagar.style.cursor = 'not-allowed';
            }

            const csrfToken = await Utils.getCSRFToken();

            const response = await fetch(`${CONFIG.API_URL}/cartoes/${fatura.cartao.id}/fatura/pagar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    mes: fatura.mes,
                    ano: fatura.ano
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Erro ao pagar fatura');
            }

            const resultado = await response.json();

            // 🎮 GAMIFICAÇÃO: Exibir conquistas se houver
            if (resultado.gamification?.achievements && Array.isArray(resultado.gamification.achievements)) {
                if (typeof window.notifyMultipleAchievements === 'function') {
                    window.notifyMultipleAchievements(resultado.gamification.achievements);
                } else {
                    console.error('❌ notifyMultipleAchievements não está disponível');
                }
            } else {
            }

            Utils.showToast('success', `Fatura paga com sucesso! ${resultado.itens_pagos} parcela(s) quitada(s).`);

            // Fechar modal
            const modal = document.querySelector('.modal-fatura-overlay');
            if (modal) {
                FaturaModal.fecharModalFatura(modal);
            }

            // Recarregar cartões para atualizar limite
            Modules.API.loadCartoes();
        } catch (error) {
            console.error('❌ Erro ao pagar fatura:', error);

            // Restaurar botão em caso de erro
            if (btnPagar) {
                btnPagar.disabled = false;
                btnPagar.innerHTML = originalText;
                btnPagar.style.opacity = '1';
                btnPagar.style.cursor = 'pointer';
            }
            Utils.showToast('error', error.message || 'Erro ao pagar fatura');
        }
    },

    /**
     * Criar conteúdo do modal para fatura já paga
     */
    criarConteudoModalFaturaPaga(fatura, statusPagamento, parcelamentos, cartaoId) {
        const idCartao = cartaoId || fatura.cartao_id || fatura.cartao?.id;
        const totalPagos = (fatura.itens || []).filter(p => p.pago).length;
        const brandLogo = fatura.cartao?.bandeira ? Utils.getBrandIcon(fatura.cartao.bandeira) : null;

        const dataPagamento = statusPagamento?.data_pagamento ||
            (fatura.itens || []).find(p => p.pago && p.data_pagamento)?.data_pagamento ||
            null;

        return `
            <div class="modal-fatura-header modal-fatura-header--paga">
                <div class="header-top-row">
                    <div class="header-card-identity">
                        ${brandLogo ? `<img src="${brandLogo}" alt="${fatura.cartao.bandeira}" class="header-brand-logo" onerror="this.style.display='none'">` : ''}
                        <div class="header-card-text">
                            <span class="cartao-nome">${fatura.cartao.nome}</span>
                            <span class="cartao-numero">•••• ${fatura.cartao.ultimos_digitos}</span>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="btn-fechar-fatura" title="Fechar">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                </div>
                <div class="header-nav-row">
                    <button class="btn-nav-mes" onclick="cartoesManager.navegarMes(${idCartao}, ${fatura.mes}, ${fatura.ano}, -1)" title="Mês anterior">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <span class="fatura-periodo">${Utils.getNomeMes(fatura.mes)} ${fatura.ano}</span>
                    <button class="btn-nav-mes" onclick="cartoesManager.navegarMes(${idCartao}, ${fatura.mes}, ${fatura.ano}, 1)" title="Próximo mês">
                        <i data-lucide="chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="modal-fatura-body">
                <div class="fatura-totalmente-paga">
                    <div class="status-paga-header">
                        <div class="status-paga-icon"><i data-lucide="circle-check"></i></div>
                        <h3>Fatura Paga</h3>
                        <p>
                            ${dataPagamento ? `Pago em ${Utils.formatDate(dataPagamento)} &bull; ` : ''}
                            ${Utils.formatMoney(statusPagamento.valor)}
                        </p>
                    </div>

                    <div class="fatura-parcelas-pagas-completa">
                        <div class="secao-titulo-bar">
                            <span class="secao-titulo-text"><i data-lucide="receipt"></i> Itens Pagos</span>
                            <div class="secao-titulo-right">
                                <span class="secao-titulo-count">${totalPagos}</span>
                                <button class="btn-desfazer-todas" 
                                    onclick="cartoesManager.desfazerPagamento(${idCartao}, ${fatura.mes}, ${fatura.ano})"
                                    title="Desfazer pagamento de todas as parcelas">
                                    <i data-lucide="undo-2"></i>
                                    Desfazer Todas
                                </button>
                            </div>
                        </div>
                        <div class="lancamentos-lista">
                            ${(fatura.itens || []).filter(p => p.pago).map(parcela => FaturaModal.renderItemPago(parcela)).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Navegar entre meses na fatura
     */
    async navegarMes(cartaoId, mesAtual, anoAtual, direcao) {

        // Calcular novo mês/ano
        let novoMes = mesAtual + direcao;
        let novoAno = anoAtual;

        if (novoMes > 12) {
            novoMes = 1;
            novoAno++;
        } else if (novoMes < 1) {
            novoMes = 12;
            novoAno--;
        }

        try {
            // Buscar fatura, parcelamentos e status do novo mês
            const [faturaResponse, parcelamentosResponse, statusResponse] = await Promise.all([
                fetch(`${CONFIG.API_URL}/cartoes/${cartaoId}/fatura?mes=${novoMes}&ano=${novoAno}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).catch(() => ({ ok: false, status: 404 })),
                fetch(`${CONFIG.API_URL}/cartoes/${cartaoId}/parcelamentos-resumo?mes=${novoMes}&ano=${novoAno}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).catch(() => ({ ok: false, status: 404 })),
                fetch(`${CONFIG.API_URL}/cartoes/${cartaoId}/fatura/status?mes=${novoMes}&ano=${novoAno}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).catch(() => ({ ok: false, status: 404 }))
            ]);

            if (!faturaResponse.ok) {
                throw new Error('Erro ao carregar fatura');
            }

            const fatura = await faturaResponse.json();
            let parcelamentos = null;
            let statusPagamento = null;

            if (parcelamentosResponse.ok) {
                parcelamentos = await parcelamentosResponse.json();
            }

            if (statusResponse.ok) {
                statusPagamento = await statusResponse.json();
            }

            // Atualizar conteúdo do modal sem fechá-lo
            const modalContainer = document.querySelector('.modal-fatura-container');
            if (modalContainer) {
                // Atualizar cor do cartão
                const corCartao = Utils.resolverCorCartao(fatura, cartaoId);
                modalContainer.style.setProperty('--card-accent', corCartao);

                const novoConteudo = FaturaModal.criarConteudoModal(fatura, parcelamentos, statusPagamento, cartaoId);
                modalContainer.innerHTML = novoConteudo;
                refreshIcons();

                // Re-adicionar event listeners
                modalContainer.querySelector('.btn-fechar-fatura')?.addEventListener('click', () => {
                    const modal = document.querySelector('.modal-fatura-overlay');
                    FaturaModal.fecharModalFatura(modal);
                });

                // Botão pagar parcelas selecionadas (CORRETO)
                modalContainer.querySelector('.btn-pagar-fatura')?.addEventListener('click', () => {
                    FaturaModal.pagarParcelasSelecionadas(fatura);
                });

                // Re-aplicar seleção de parcelas
                const modal = document.querySelector('.modal-fatura-overlay');
                requestAnimationFrame(() => {
                    FaturaModal.setupParcelaSelection(modal, fatura);
                });
            }
        } catch (error) {
            console.error('❌ Erro ao navegar entre meses:', error);
            Utils.showToast('error', 'Erro ao carregar fatura');
        }
    },

    /**
     * Toggle entre fatura atual e histórico
     */
    async toggleHistoricoFatura(cartaoId) {
        try {
            const modalContainer = document.querySelector('.modal-fatura-container');
            if (!modalContainer) return;

            // Verifica se já está mostrando histórico
            const mostandoHistorico = modalContainer.querySelector('.historico-faturas');

            if (mostandoHistorico) {
                // Volta para a fatura atual
                const hoje = new Date();
                const mes = hoje.getMonth() + 1;
                const ano = hoje.getFullYear();

                const [fatura, parcelamentos, statusResponse] = await Promise.all([
                    Modules.API.carregarFatura(cartaoId, mes, ano),
                    Modules.API.carregarParcelamentosResumo(cartaoId, mes, ano).catch(() => null),
                    fetch(`${CONFIG.API_URL}/cartoes/${cartaoId}/fatura/status?mes=${mes}&ano=${ano}`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    }).then(r => r.ok ? r.json() : null).catch(() => null)
                ]);

                const conteudo = FaturaModal.criarConteudoModal(fatura, parcelamentos, statusResponse, cartaoId);
                modalContainer.innerHTML = conteudo;
                refreshIcons();

                FaturaModal.adicionarEventListenersModal(fatura);
            } else {
                // Mostra histórico
                const historico = await Modules.API.carregarHistoricoFaturas(cartaoId);
                const conteudo = FaturaModal.criarConteudoHistorico(historico, cartaoId);
                modalContainer.innerHTML = conteudo;
                refreshIcons();

                FaturaModal.adicionarEventListenersModal(null);
            }
        } catch (error) {
            console.error('❌ Erro ao alternar histórico:', error);
            Utils.showToast('error', 'Erro ao carregar histórico');
        }
    },

    /**
     * Criar conteúdo do histórico de faturas
     */
    criarConteudoHistorico(historico, cartaoId) {
        return `
            <div class="modal-fatura-header">
                <div class="header-info">
                    <div class="cartao-info">
                        <span class="cartao-nome">${historico.cartao.nome}</span>
                        <span class="cartao-subtitulo">Histórico de Faturas Pagas</span>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn-historico-toggle" onclick="cartoesManager.toggleHistoricoFatura(${cartaoId})" title="Voltar para fatura atual">
                        <i data-lucide="arrow-left"></i>
                    </button>
                    <button class="btn-fechar-fatura" title="Fechar">
                        <i data-lucide="x"></i>
                    </button>
                </div>
            </div>

            <div class="modal-fatura-body historico-faturas">
                ${historico.historico.length === 0 ? `
                    <div class="fatura-empty">
                        <i data-lucide="receipt"></i>
                        <h3>Nenhuma fatura paga</h3>
                        <p>Você ainda não pagou nenhuma fatura neste cartão.</p>
                    </div>
                ` : `
                    <div class="historico-lista">
                        ${historico.historico.map(item => `
                            <div class="historico-item">
                                <div class="historico-periodo">
                                    <i data-lucide="calendar-check"></i>
                                    <div class="periodo-info">
                                        <strong>${item.mes_nome} ${item.ano}</strong>
                                        <span class="historico-data-pag">Pago em ${Utils.formatDate(item.data_pagamento)}</span>
                                    </div>
                                </div>
                                <div class="historico-detalhes">
                                    <div class="historico-valor">
                                        ${Utils.formatMoney(item.total)}
                                    </div>
                                    <div class="historico-qtd">
                                        ${item.quantidade_lancamentos} lançamento${item.quantidade_lancamentos !== 1 ? 's' : ''}
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `}
            </div>
        `;
    },

    /**
     * Adicionar event listeners aos elementos do modal
     */
    adicionarEventListenersModal(fatura) {
        const modalContainer = document.querySelector('.modal-fatura-container');
        if (!modalContainer) return;

        modalContainer.querySelector('.btn-fechar-fatura')?.addEventListener('click', () => {
            const modal = document.querySelector('.modal-fatura-overlay');
            FaturaModal.fecharModalFatura(modal);
        });

        if (fatura) {
            modalContainer.querySelector('.btn-pagar-fatura')?.addEventListener('click', () => {
                FaturaModal.pagarFatura(fatura);
            });
        }
    },
};

// Register in module registry
Modules.Fatura = FaturaModal;
