/**
 * Cartões Manager – UI module
 * Extracted from cartoes-manager.js (monolith → modules)
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { refreshIcons } from '../shared/ui.js';

export const CartoesUI = {
    /**
     * Setup Event Listeners
     */
    setupEventListeners() {
        // Botão novo cartão
        document.getElementById('btnNovoCartao')?.addEventListener('click', () => {
            CartoesUI.openModal('create');
        });

        document.getElementById('btnNovoCartaoEmpty')?.addEventListener('click', () => {
            CartoesUI.openModal('create');
        });

        // Modal close buttons
        const modalOverlay = document.getElementById('modalCartaoOverlay');
        const closeButtons = document.querySelectorAll('.modal-close, .modal-close-btn');

        // Backdrop bloqueado - modal fecha apenas pelo botão X

        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => CartoesUI.closeModal());
        });

        // Event delegation para máscara de limite total
        document.addEventListener('input', (e) => {
            if (e.target && e.target.id === 'limiteTotal') {
                let value = e.target.value;

                // Remove tudo que não é número
                value = value.replace(/[^\d]/g, '');

                // Converte para número (centavos)
                let number = parseInt(value) || 0;

                // Converte centavos para reais e formata
                const reais = number / 100;
                const formatted = reais.toFixed(2)
                    .replace('.', ',')
                    .replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                e.target.value = formatted;
            }

            // Validação para últimos 4 dígitos - apenas números
            if (e.target && e.target.id === 'ultimosDigitos') {
                let value = e.target.value;

                // Remove tudo que não é número
                value = value.replace(/\D/g, '');

                // Limita a 4 dígitos
                if (value.length > 4) {
                    value = value.substring(0, 4);
                }

                e.target.value = value;
            }
        });

        // Fechar modal com tecla ESC
        document.addEventListener('keydown', (e) => {
            const overlay = document.getElementById('modalCartaoOverlay');
            if (e.key === 'Escape' && overlay && overlay.classList.contains('active')) {
                CartoesUI.closeModal();
            }
        });

        // Form submit
        const form = document.getElementById('formCartao');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                Modules.API.saveCartao();
            });
        }

        // Toggle canais de lembrete de fatura
        const lembreteSelect = document.getElementById('cartaoLembreteAviso');
        if (lembreteSelect) {
            lembreteSelect.addEventListener('change', () => {
                const canaisDiv = document.getElementById('cartaoCanaisLembrete');
                if (canaisDiv) {
                    canaisDiv.style.display = lembreteSelect.value ? 'block' : 'none';
                }
            });
        }

        // Máscara para dias (fechamento e vencimento) - apenas 2 dígitos
        const diaFechamentoInput = document.getElementById('diaFechamento');
        const diaVencimentoInput = document.getElementById('diaVencimento');

        [diaFechamentoInput, diaVencimentoInput].forEach(input => {
            if (input) {
                input.addEventListener('input', (e) => {
                    // Remove tudo que não é número
                    let value = e.target.value.replace(/\D/g, '');
                    // Limita a 2 dígitos
                    if (value.length > 2) {
                        value = value.substring(0, 2);
                    }
                    // Limita de 1 a 31
                    if (value !== '' && parseInt(value) > 31) {
                        value = '31';
                    }
                    e.target.value = value;
                });
            }
        });

        // Reload
        document.getElementById('btnReload')?.addEventListener('click', () => {
            Modules.API.loadCartoes();
        });

        // Search
        const searchInput = document.getElementById('searchCartoes');
        const btnLimpar = document.getElementById('btnLimparFiltrosCartoes');

        const toggleClearBtn = () => {
            if (btnLimpar) {
                btnLimpar.style.display = (STATE.searchTerm || STATE.currentFilter !== 'all') ? '' : 'none';
            }
        };

        if (searchInput) {
            searchInput.addEventListener('input', Utils.debounce((e) => {
                STATE.searchTerm = e.target.value.toLowerCase();
                CartoesUI.filterCartoes();
                toggleClearBtn();
            }, 300));
        }

        // Filters
        document.querySelectorAll('.filter-btn:not(.btn-clear-filters)').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.filter-btn:not(.btn-clear-filters)').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                STATE.currentFilter = e.target.dataset.filter;
                CartoesUI.filterCartoes();
                toggleClearBtn();
            });
        });

        // Limpar todos os filtros
        if (btnLimpar) {
            btnLimpar.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                STATE.searchTerm = '';
                STATE.currentFilter = 'all';
                document.querySelectorAll('.filter-btn:not(.btn-clear-filters)').forEach(b => b.classList.remove('active'));
                document.querySelector('.filter-btn[data-filter="all"]')?.classList.add('active');
                CartoesUI.filterCartoes();
                toggleClearBtn();
            });
        }

        // View toggle
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                STATE.currentView = e.target.dataset.view;
                CartoesUI.updateView();
            });
        });

        // Exportar
        document.getElementById('btnExportar')?.addEventListener('click', () => {
            CartoesUI.exportarRelatorio();
        });
    },

    /**
     * Filtrar cartões
     */
    filterCartoes() {
        STATE.filteredCartoes = STATE.cartoes.filter(cartao => {
            // Filtro de busca
            const matchSearch = !STATE.searchTerm ||
                cartao.nome_cartao.toLowerCase().includes(STATE.searchTerm) ||
                cartao.ultimos_digitos?.includes(STATE.searchTerm);

            // Filtro de bandeira
            const matchFilter = STATE.currentFilter === 'all' ||
                cartao.bandeira?.toLowerCase() === STATE.currentFilter;

            return matchSearch && matchFilter;
        });

        CartoesUI.renderCartoes();
    },

    /**
     * Renderizar cartões
     */
    renderCartoes() {
        const grid = document.getElementById('cartoesGrid');
        const emptyState = document.getElementById('emptyState');

        if (STATE.filteredCartoes.length === 0) {
            grid.innerHTML = '';
            emptyState.style.display = 'block';
            emptyState.querySelector('h3').textContent =
                STATE.searchTerm || STATE.currentFilter !== 'all'
                    ? 'Nenhum cartão encontrado'
                    : 'Nenhum cartão cadastrado';
            return;
        }

        emptyState.style.display = 'none';

        grid.innerHTML = STATE.filteredCartoes.map(cartao => CartoesUI.createCardHTML(cartao)).join('');
        refreshIcons();

        // Add event listeners para ações
        CartoesUI.setupCardActions();
    },

    /**
     * Criar HTML do cartão
     */
    createCardHTML(cartao) {
        // Usar limite calculado (limite_disponivel_real) se disponível, senão usar limite_disponivel
        const limiteDisponivel = cartao.limite_disponivel_real ?? cartao.limite_disponivel ?? 0;
        const limiteUtilizado = cartao.limite_utilizado ?? (cartao.limite_total - limiteDisponivel);

        const percentualUso = cartao.percentual_uso ?? (cartao.limite_total > 0
            ? ((cartao.limite_total - limiteDisponivel) / cartao.limite_total * 100).toFixed(1)
            : 0);

        const brandIcon = Utils.getBrandIcon(cartao.bandeira);

        // Obter cor da instituição
        const corBg = cartao.conta?.instituicao_financeira?.cor_primaria ||
            cartao.instituicao_cor ||
            Utils.getDefaultColor(cartao.bandeira);

        return `
            <div class="credit-card" data-id="${cartao.id}" data-brand="${cartao.bandeira?.toLowerCase() || 'outros'}" style="background: ${corBg};">
                ${cartao.temFaturaPendente ? `
                    <div class="card-badge-fatura" title="Fatura pendente">
                        <i data-lucide="circle-alert"></i>
                        Fatura Pendente
                    </div>
                ` : ''}
               <div class="card-header">
    <div class="card-brand">
        <img
            src="${brandIcon}"
            alt="${cartao.bandeira}"
            class="brand-logo"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';"
        >
        <i class="brand-icon-fallback" data-lucide="credit-card" style="display: none;" aria-hidden="true"></i>
        <span class="card-name">
            ${Utils.escapeHtml(cartao.nome_cartao || cartao.nome)}
        </span>
    </div>

    <div class="card-actions">

        <!-- Tooltip de regra de exclusão -->
        <button
            type="button"
            class="lk-info"
            data-lk-tooltip-title="Exclusão de cartões"
            data-lk-tooltip="Para evitar perda de histórico e faturas, cartões só podem ser excluídos após serem arquivados. Arquive o cartão primeiro e depois realize a exclusão."
            aria-label="Ajuda: Exclusão de cartões"
        >
            <i data-lucide="info" aria-hidden="true"></i>
        </button>

        <button
            class="card-action-btn"
            onclick="cartoesManager.verFatura(${cartao.id})"
            title="Ver Fatura"
        >
            <i data-lucide="file-text" aria-hidden="true"></i>
        </button>

        <button
            class="card-action-btn"
            onclick="cartoesManager.editCartao(${cartao.id})"
            title="Editar"
        >
            <i data-lucide="pencil" aria-hidden="true"></i>
        </button>

        <button
            class="card-action-btn"
            onclick="cartoesManager.arquivarCartao(${cartao.id})"
            title="Arquivar"
        >
            <i data-lucide="archive" aria-hidden="true"></i>
        </button>

    </div>
</div>


                <div class="card-number">
                    •••• •••• •••• ${cartao.ultimos_digitos || '0000'}
                </div>

                <div class="card-footer">
                    <div class="card-holder">
                        <div class="card-label">Vencimento</div>
                        <div class="card-value">Dia ${cartao.dia_vencimento}</div>
                    </div>
                    <div class="card-limit">
                        <div class="card-label">Disponível</div>
                        <div class="card-value">${Utils.formatMoney(limiteDisponivel)}</div>
                        <div class="limit-bar">
                            <div class="limit-fill" style="width: ${100 - percentualUso}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Atualizar estatísticas
     */
    updateStats() {
        const stats = STATE.cartoes.reduce((acc, cartao) => {
            const limiteTotal = parseFloat(cartao.limite_total) || 0;
            // Usar limite calculado (limite_disponivel_real) se disponível
            const limiteDisponivel = parseFloat(cartao.limite_disponivel_real ?? cartao.limite_disponivel) || 0;
            const limiteUtilizado = parseFloat(cartao.limite_utilizado) || Math.max(0, limiteTotal - limiteDisponivel);

            acc.total++;
            acc.limiteTotal += limiteTotal;
            acc.limiteDisponivel += limiteDisponivel;
            acc.limiteUtilizado += limiteUtilizado;
            return acc;
        }, { total: 0, limiteTotal: 0, limiteDisponivel: 0, limiteUtilizado: 0 });

        document.getElementById('totalCartoes').textContent = stats.total;
        document.getElementById('statLimiteTotal').textContent = Utils.formatMoney(stats.limiteTotal);
        document.getElementById('limiteDisponivel').textContent = Utils.formatMoney(stats.limiteDisponivel);
        document.getElementById('limiteUtilizado').textContent = Utils.formatMoney(stats.limiteUtilizado);

        // Animar números
        CartoesUI.animateStats();
    },

    /**
     * Animar estatísticas
     */
    animateStats() {
        document.querySelectorAll('.stat-card').forEach((card, index) => {
            card.style.animation = 'none';
            setTimeout(() => {
                card.style.animation = 'fadeIn 0.5s ease forwards';
            }, index * 100);
        });
    },

    /**
     * Atualizar visualização (grid/list)
     */
    updateView() {
        const grid = document.getElementById('cartoesGrid');

        if (STATE.currentView === 'list') {
            grid.classList.add('list-view');
        } else {
            grid.classList.remove('list-view');
        }
    },

    /**
     * Abrir modal
     */
    async openModal(mode = 'create', cartaoData = null) {
        const overlay = document.getElementById('modalCartaoOverlay');
        const modal = document.getElementById('modalCartao');
        const form = document.getElementById('formCartao');
        const titulo = document.getElementById('modalCartaoTitulo');

        if (!overlay || !modal || !form) return;

        // Resetar formulário
        form.reset();
        document.getElementById('cartaoId').value = '';

        // Carregar contas no select PRIMEIRO
        await Modules.API.loadContasSelect();

        if (mode === 'edit' && cartaoData) {
            // Modo edição
            titulo.textContent = 'Editar Cartão de Crédito';
            document.getElementById('cartaoId').value = cartaoData.id;
            document.getElementById('nomeCartao').value = cartaoData.nome_cartao;
            document.getElementById('contaVinculada').value = cartaoData.conta_id;
            document.getElementById('bandeira').value = cartaoData.bandeira;
            document.getElementById('ultimosDigitos').value = cartaoData.ultimos_digitos;

            // Formata o limite total (converte para float primeiro)
            const limiteValue = parseFloat(cartaoData.limite_total || 0);
            const limiteFormatado = limiteValue.toFixed(2)
                .replace('.', ',')
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            document.getElementById('limiteTotal').value = limiteFormatado;

            document.getElementById('diaFechamento').value = cartaoData.dia_fechamento;
            document.getElementById('diaVencimento').value = cartaoData.dia_vencimento;

            // Campos de lembrete de fatura
            const lembreteSelect = document.getElementById('cartaoLembreteAviso');
            if (lembreteSelect) {
                lembreteSelect.value = cartaoData.lembrar_fatura_antes_segundos || '';
                const canaisDiv = document.getElementById('cartaoCanaisLembrete');
                if (canaisDiv) canaisDiv.style.display = lembreteSelect.value ? 'block' : 'none';
            }
            const canalInapp = document.getElementById('cartaoCanalInapp');
            if (canalInapp) canalInapp.checked = cartaoData.fatura_canal_inapp !== false && cartaoData.fatura_canal_inapp !== 0;
            const canalEmail = document.getElementById('cartaoCanalEmail');
            if (canalEmail) canalEmail.checked = !!cartaoData.fatura_canal_email;
        } else {
            // Modo criação
            titulo.textContent = 'Novo Cartão de Crédito';
            document.getElementById('limiteTotal').value = '0,00';
            const lembreteSelect = document.getElementById('cartaoLembreteAviso');
            if (lembreteSelect) lembreteSelect.value = '';
            const canaisDiv = document.getElementById('cartaoCanaisLembrete');
            if (canaisDiv) canaisDiv.style.display = 'none';
        }

        // Mostrar modal
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    /**
     * Fechar modal
     */
    closeModal() {
        const overlay = document.getElementById('modalCartaoOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    /**
     * Setup card click actions
     */
    setupCardActions() {
        document.querySelectorAll('.credit-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.card-action-btn')) {
                    const id = parseInt(card.dataset.id);
                    CartoesUI.showCardDetails(id);
                }
            });
        });
    },

    /**
     * Mostrar detalhes do cartão
     */
    async showCardDetails(id) {
        const cartao = STATE.cartoes.find(c => c.id === id);
        if (!cartao) return;

        // Implementar modal de detalhes (futuro)
    },

    /**
     * Exportar relatório em PDF
     */
    async exportarRelatorio() {
        if (!STATE.filteredCartoes?.length) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Nenhum cartão para exportar',
                    text: 'Adicione cartões ou altere os filtros.',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
            return;
        }

        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            const dataAtual = new Date();
            const mesAno = dataAtual.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });

            // Calcular resumo financeiro
            const limiteTotal = STATE.filteredCartoes.reduce((sum, c) => sum + parseFloat(c.limite_total || 0), 0);
            const limiteDisponivel = STATE.filteredCartoes.reduce((sum, c) => sum + parseFloat((c.limite_disponivel_real ?? c.limite_disponivel) || 0), 0);
            const limiteUtilizado = limiteTotal - limiteDisponivel;
            const percentualGeral = limiteTotal > 0 ? (limiteUtilizado / limiteTotal * 100).toFixed(1) : 0;

            // Configurar cores
            const primaryColor = [230, 126, 34]; // Laranja
            const darkColor = [26, 31, 46];
            const lightGray = [248, 249, 250];

            // Cabeçalho do documento
            doc.setFillColor(...primaryColor);
            doc.rect(0, 0, 210, 35, 'F');

            doc.setTextColor(255, 255, 255);
            doc.setFontSize(22);
            doc.setFont(undefined, 'bold');
            doc.text('RELATÓRIO DE CARTÕES DE CRÉDITO', 105, 15, { align: 'center' });

            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.text(`Período: ${mesAno}`, 105, 22, { align: 'center' });
            doc.text(`Gerado em: ${dataAtual.toLocaleDateString('pt-BR')} às ${dataAtual.toLocaleTimeString('pt-BR')}`, 105, 28, { align: 'center' });

            // Resumo Financeiro
            let yPos = 45;
            doc.setTextColor(...darkColor);
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text('RESUMO FINANCEIRO', 14, yPos);

            yPos += 8;
            doc.autoTable({
                startY: yPos,
                head: [['Indicador', 'Valor']],
                body: [
                    ['Total de Cartões', STATE.filteredCartoes.length.toString()],
                    ['Limite Total Combinado', Utils.formatMoney(limiteTotal)],
                    ['Limite Utilizado', Utils.formatMoney(limiteUtilizado)],
                    ['Limite Disponível', Utils.formatMoney(limiteDisponivel)],
                    ['Percentual de Utilização', `${percentualGeral}%`]
                ],
                theme: 'grid',
                headStyles: {
                    fillColor: primaryColor,
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'left'
                },
                columnStyles: {
                    0: { cellWidth: 100, fontStyle: 'bold' },
                    1: { cellWidth: 86, halign: 'right' }
                },
                styles: {
                    fontSize: 10,
                    cellPadding: 5
                },
                alternateRowStyles: {
                    fillColor: lightGray
                }
            });

            // Detalhamento por Cartão
            yPos = doc.lastAutoTable.finalY + 15;
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text('DETALHAMENTO POR CARTÃO', 14, yPos);

            yPos += 5;
            const tableData = STATE.filteredCartoes.map(cartao => {
                const limiteDisp = cartao.limite_disponivel_real ?? cartao.limite_disponivel ?? 0;
                const percentualUso = cartao.limite_total > 0
                    ? ((cartao.limite_total - limiteDisp) / cartao.limite_total * 100).toFixed(1)
                    : 0;

                return [
                    cartao.nome_cartao,
                    Utils.formatBandeira(cartao.bandeira),
                    `**** ${cartao.ultimos_digitos}`,
                    Utils.formatMoney(cartao.limite_total),
                    Utils.formatMoney(limiteDisp),
                    `${percentualUso}%`,
                    cartao.ativo ? 'Ativo' : 'Inativo'
                ];
            });

            doc.autoTable({
                startY: yPos,
                head: [['Cartão', 'Bandeira', 'Final', 'Limite Total', 'Disponível', 'Uso', 'Status']],
                body: tableData,
                theme: 'grid',
                headStyles: {
                    fillColor: primaryColor,
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'center'
                },
                columnStyles: {
                    0: { cellWidth: 40 },
                    1: { cellWidth: 25, halign: 'center' },
                    2: { cellWidth: 25, halign: 'center' },
                    3: { cellWidth: 28, halign: 'right' },
                    4: { cellWidth: 28, halign: 'right' },
                    5: { cellWidth: 18, halign: 'center' },
                    6: { cellWidth: 22, halign: 'center' }
                },
                styles: {
                    fontSize: 9,
                    cellPadding: 4
                },
                alternateRowStyles: {
                    fillColor: lightGray
                }
            });

            // Rodapé
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(128, 128, 128);
                doc.text(
                    `Página ${i} de ${pageCount} | Lukrato - Sistema de Gestão Financeira`,
                    105,
                    287,
                    { align: 'center' }
                );
            }

            // Salvar PDF
            doc.save(`relatorio_cartoes_${dataAtual.toISOString().split('T')[0]}.pdf`);

            Utils.showToast('success', 'Relatório exportado com sucesso');

        } catch (error) {
            console.error('Erro ao exportar:', error);
            Utils.showToast('error', 'Erro ao exportar relatório');
        }
    },
};

Modules.UI = CartoesUI;
