export function attachLancamentosModalEdit(ModalManager, dependencies) {
    const {
        DOM,
        STATE,
        Utils,
        MoneyMask,
        Notifications,
        Modules,
        _sugerirCategoriaIA,
        getErrorMessage,
        syncModalSelects,
        OptionsManager,
        planningStore,
        resolvePlanningPeriod,
        isSamePlanningPeriod,
        buildPlanningAlertCard,
        summarizeMetaTitles,
        canLinkMetaInLancamento,
        parseSelectedMetaId,
        resolveMetaOperationForLancamento,
    } = dependencies;

    let editPlanningRenderSeq = 0;

    Object.assign(ModalManager, {
        ensureLancModal: () => {
            if (STATE.modalEditLanc) return STATE.modalEditLanc;
            if (!DOM.modalEditLancEl) return null;

            if (window.bootstrap?.Modal) {
                window.LK?.modalSystem?.prepareBootstrapModal(DOM.modalEditLancEl, { scope: 'page' });
                STATE.modalEditLanc = window.bootstrap.Modal.getOrCreateInstance(DOM.modalEditLancEl);
                return STATE.modalEditLanc;
            }
            return null;
        },

        sugerirCategoriaIA: async () => {
            await _sugerirCategoriaIA({
                descricaoInputId: 'editLancDescricao',
                categoriaSelectId: 'editLancCategoria',
                subcategoriaSelectId: 'editLancSubcategoria',
                subcategoriaGroupId: 'editSubcategoriaGroup',
                btnId: 'btnEditAiSuggestCategoria',
                notify: (msg, type) => {
                    const iconMap = { success: 'success', warning: 'warning', error: 'error' };
                    Notifications.toast(msg, iconMap[type] || 'info');
                },
            });
            void ModalManager.renderPlanningAlerts();
        },

        clearLancAlert: () => {
            if (!DOM.editLancAlert) return;
            DOM.editLancAlert.classList.add('d-none');
            DOM.editLancAlert.textContent = '';
        },

        showLancAlert: (msg) => {
            if (!DOM.editLancAlert) return;
            DOM.editLancAlert.textContent = msg;
            DOM.editLancAlert.classList.remove('d-none');
        },

        getEditSelectLabel: (select, fallback = '') => {
            if (!select) return fallback;

            const currentValue = String(select.value || '').trim();
            if (!currentValue) return fallback;

            const option = select.options?.[select.selectedIndex];
            const label = String(option?.textContent || '').trim();
            return label || fallback;
        },

        canLinkMetaInLancamentoEdit: (tipo, formaPagamento = '') => {
            return canLinkMetaInLancamento({ tipo, formaPagamento });
        },

        getSelectedMetaId: (select) => parseSelectedMetaId(select),

        getEditMetaValue: (fallbackValue = 0) => {
            const raw = DOM.inputLancMetaValor?.value || '';
            const parsed = Math.abs(Number(MoneyMask.unformat(raw)));
            if (Number.isFinite(parsed) && parsed > 0) {
                return parsed;
            }
            return Math.max(0, Number(fallbackValue) || 0);
        },

        resolveMetaOperationForEdit: (tipo, hasMetaLink) => {
            return resolveMetaOperationForLancamento({
                tipo,
                hasMetaLink,
                isRealizacao: Boolean(DOM.checkLancMetaRealizacao?.checked)
            });
        },

        resolveMetaTitle: (metaId, fallback = '') => {
            const title = String(planningStore.getMetaById(metaId)?.titulo || '').trim();
            return title || String(fallback || '').trim() || 'Meta';
        },

        syncEditMetaField: () => {
            const snapshot = STATE.editingLancamentoData || {};
            const tipo = DOM.selectLancTipo?.value || snapshot?.tipo || '';
            const formaPagamento = DOM.selectLancFormaPagamento?.value || snapshot?.forma_pagamento || '';
            const shouldShow = ModalManager.canLinkMetaInLancamentoEdit(tipo, formaPagamento);
            const selectedMetaId = shouldShow ? ModalManager.getSelectedMetaId(DOM.selectLancMeta) : null;
            const hasMeta = Number(selectedMetaId) > 0;
            const valorLancamentoAtual = DOM.inputLancValor?.value
                ? Math.abs(Number(MoneyMask.unformat(DOM.inputLancValor.value)))
                : Math.abs(Number(snapshot?.valor ?? 0));
            const isDespesa = String(tipo || '').toLowerCase() === 'despesa';
            const selectedMeta = hasMeta ? planningStore.getMetaById(selectedMetaId) : null;

            if (DOM.editLancMetaGroup) {
                DOM.editLancMetaGroup.hidden = !shouldShow;
            }

            if (!shouldShow && DOM.selectLancMeta) {
                DOM.selectLancMeta.value = '';
            }

            if (DOM.editLancMetaValorGroup) {
                DOM.editLancMetaValorGroup.hidden = !shouldShow || !hasMeta;
            }
            if (DOM.editLancMetaRealizacaoGroup) {
                DOM.editLancMetaRealizacaoGroup.hidden = !shouldShow || !hasMeta || !isDespesa;
            }

            if (!shouldShow || !hasMeta) {
                if (DOM.inputLancMetaValor) DOM.inputLancMetaValor.value = '';
                if (DOM.checkLancMetaRealizacao) {
                    DOM.checkLancMetaRealizacao.checked = false;
                    delete DOM.checkLancMetaRealizacao.dataset.autoDefaultPending;
                }
                return;
            }

            const currentMetaValue = ModalManager.getEditMetaValue(0);
            if (!(currentMetaValue > 0) && DOM.inputLancMetaValor) {
                DOM.inputLancMetaValor.value = MoneyMask.format(valorLancamentoAtual || 0);
            }

            if (isDespesa && DOM.checkLancMetaRealizacao) {
                const status = String(selectedMeta?.status || '').toLowerCase();
                const pendingDefault = DOM.checkLancMetaRealizacao.dataset.autoDefaultPending === '1';
                if (status === 'concluida' && pendingDefault && !DOM.checkLancMetaRealizacao.dataset.userTouched) {
                    DOM.checkLancMetaRealizacao.checked = true;
                }
                delete DOM.checkLancMetaRealizacao.dataset.autoDefaultPending;
            } else if (DOM.checkLancMetaRealizacao) {
                delete DOM.checkLancMetaRealizacao.dataset.autoDefaultPending;
            }
        },

        getEditStatusMeta: () => {
            const data = STATE.editingLancamentoData || {};
            if (data?.cancelado_em) {
                return { label: 'Cancelado', className: 'is-neutral' };
            }

            const rawPago = data?.pago;
            const isPago = !(rawPago === false || rawPago === 0 || rawPago === '0' || rawPago === 'false');
            return isPago
                ? { label: 'Pago', className: 'is-success' }
                : { label: 'Pendente', className: 'is-warning' };
        },

        formatEditSummaryDateTime: (dataValue, horaValue) => {
            const dateLabel = dataValue ? Utils.fmtDate(dataValue) : 'Sem data';
            return horaValue ? `${dateLabel} às ${horaValue}` : dateLabel;
        },

        syncEditSummary: () => {
            const snapshot = STATE.editingLancamentoData || {};
            const tipo = String(DOM.selectLancTipo?.value || snapshot?.tipo || 'despesa').toLowerCase();
            const descricao = String(DOM.inputLancDescricao?.value || snapshot?.descricao || '').trim();
            const contaLabel = ModalManager.getEditSelectLabel(DOM.selectLancConta, 'Conta não definida');
            const categoriaLabel = ModalManager.getEditSelectLabel(DOM.selectLancCategoria, 'Sem categoria');
            const formaPagamento = DOM.selectLancFormaPagamento?.value || snapshot?.forma_pagamento || '';
            const canLinkMeta = ModalManager.canLinkMetaInLancamentoEdit(tipo, formaPagamento);
            const metaId = canLinkMeta ? ModalManager.getSelectedMetaId(DOM.selectLancMeta) : null;
            const metaLabel = metaId
                ? ModalManager.getEditSelectLabel(DOM.selectLancMeta, Utils.getLancamentoMetaTitle(snapshot))
                : '';
            const dataValue = DOM.inputLancData?.value || snapshot?.data || '';
            const horaValue = DOM.inputLancHora?.value || snapshot?.hora_lancamento || '';
            const valorAtual = DOM.inputLancValor?.value
                ? MoneyMask.unformat(DOM.inputLancValor.value)
                : Math.abs(Number(snapshot?.valor ?? 0));
            const metaValue = metaId ? ModalManager.getEditMetaValue(valorAtual) : 0;
            const metaOperation = ModalManager.resolveMetaOperationForEdit(tipo, Boolean(metaId));
            const statusMeta = ModalManager.getEditStatusMeta();

            if (DOM.editLancSummaryTitle) {
                DOM.editLancSummaryTitle.textContent = descricao || 'Sem descrição informada';
            }

            if (DOM.editLancSummaryMeta) {
                let metaResumo = '';
                if (metaLabel) {
                    const opLabel = metaOperation === 'realizacao'
                        ? 'Realizacao'
                        : (metaOperation === 'resgate' ? 'Uso de meta' : 'Aporte');
                    metaResumo = `Meta: ${metaLabel} (${opLabel} ${Utils.fmtMoney(metaValue)})`;
                }

                const meta = [
                    contaLabel,
                    categoriaLabel,
                    ModalManager.formatEditSummaryDateTime(dataValue, horaValue),
                    metaResumo
                ].filter(Boolean).join(' • ');

                DOM.editLancSummaryMeta.textContent = meta || 'Conta, categoria e data aparecem aqui.';
            }

            if (DOM.editLancSummaryTipo) {
                DOM.editLancSummaryTipo.textContent = tipo === 'receita' ? 'Receita' : 'Despesa';
                DOM.editLancSummaryTipo.classList.remove('is-receita', 'is-despesa');
                DOM.editLancSummaryTipo.classList.add(tipo === 'receita' ? 'is-receita' : 'is-despesa');
            }

            if (DOM.editLancSummaryStatus) {
                DOM.editLancSummaryStatus.textContent = statusMeta.label;
                DOM.editLancSummaryStatus.classList.remove('is-success', 'is-warning', 'is-neutral');
                DOM.editLancSummaryStatus.classList.add(statusMeta.className);
            }

            if (DOM.editLancSummaryValor) {
                const valor = Number.isFinite(Number(valorAtual)) ? Math.abs(Number(valorAtual)) : 0;
                DOM.editLancSummaryValor.textContent = MoneyMask.format(valor);
            }
        },

        resetEditSummary: () => {
            if (DOM.editLancSummaryTitle) {
                DOM.editLancSummaryTitle.textContent = 'Sem descrição informada';
            }

            if (DOM.editLancSummaryMeta) {
                DOM.editLancSummaryMeta.textContent = 'Conta, categoria e data aparecem aqui.';
            }

            if (DOM.editLancSummaryTipo) {
                DOM.editLancSummaryTipo.textContent = 'Despesa';
                DOM.editLancSummaryTipo.classList.remove('is-receita', 'is-despesa');
                DOM.editLancSummaryTipo.classList.add('is-despesa');
            }

            if (DOM.editLancSummaryStatus) {
                DOM.editLancSummaryStatus.textContent = 'Pendente';
                DOM.editLancSummaryStatus.classList.remove('is-success', 'is-warning', 'is-neutral');
                DOM.editLancSummaryStatus.classList.add('is-warning');
            }

            if (DOM.editLancSummaryValor) {
                DOM.editLancSummaryValor.textContent = 'R$ 0,00';
            }
        },

        clearPlanningAlerts: () => {
            if (!DOM.editLancPlanningAlerts) return;
            DOM.editLancPlanningAlerts.innerHTML = '';
            DOM.editLancPlanningAlerts.hidden = true;
        },

        summarizeMetaTitles: (metas = []) => summarizeMetaTitles(metas),

        buildPlanningAlertCard: ({ tone = 'info', icon = 'target', eyebrow, title, message }) => buildPlanningAlertCard({
            tone,
            icon,
            eyebrow,
            title,
            message
        }),

        buildEditMetaAlert: () => {
            const snapshot = STATE.editingLancamentoData || {};
            const tipo = String(DOM.selectLancTipo?.value || snapshot?.tipo || 'despesa').toLowerCase();
            const formaPagamento = String(DOM.selectLancFormaPagamento?.value || snapshot?.forma_pagamento || '').toLowerCase();
            const valorAtual = DOM.inputLancValor?.value
                ? Math.abs(Number(MoneyMask.unformat(DOM.inputLancValor.value)))
                : Math.abs(Number(snapshot?.valor ?? 0));
            const canLinkMeta = ModalManager.canLinkMetaInLancamentoEdit(tipo, formaPagamento);
            const selectedMetaId = canLinkMeta ? ModalManager.getSelectedMetaId(DOM.selectLancMeta) : null;
            const originalMetaId = Number(snapshot?.meta_id ?? 0) > 0 ? Number(snapshot.meta_id) : null;
            const originalMetaTitle = Utils.getLancamentoMetaTitle(snapshot);

            if (!selectedMetaId && !originalMetaId) {
                return '';
            }

            if (selectedMetaId) {
                const selectedMeta = planningStore.getMetaById(selectedMetaId);
                const selectedTitle = ModalManager.resolveMetaTitle(
                    selectedMetaId,
                    ModalManager.getEditSelectLabel(DOM.selectLancMeta, originalMetaTitle)
                );
                const linkedValue = ModalManager.getEditMetaValue(valorAtual);
                const operation = ModalManager.resolveMetaOperationForEdit(tipo, true);
                const opLabel = operation === 'realizacao'
                    ? 'realização da meta'
                    : (operation === 'resgate' ? 'uso da meta' : 'aporte');
                const status = String(selectedMeta?.status || '').toLowerCase();
                const tone = operation === 'realizacao' ? 'success' : 'info';
                const title = operation === 'realizacao'
                    ? `Realização de ${selectedTitle}`
                    : (operation === 'resgate' ? `Uso de ${selectedTitle}` : `Aporte em ${selectedTitle}`);
                let message = `Ao salvar, este lançamento registra ${Utils.fmtMoney(linkedValue)} como ${opLabel}.`;
                if (operation === 'resgate' || operation === 'realizacao') {
                    message += ' Somente a parte fora da meta entra como gasto do mês.';
                }
                if (operation === 'realizacao' && status === 'concluida') {
                    message += ' A meta concluída será marcada como realizada.';
                }

                if (originalMetaId && originalMetaId !== selectedMetaId) {
                    message += ` O vínculo anterior com ${ModalManager.resolveMetaTitle(originalMetaId, originalMetaTitle)} será removido.`;
                }

                return ModalManager.buildPlanningAlertCard({
                    tone,
                    icon: 'target',
                    eyebrow: 'Meta vinculada',
                    title,
                    message
                });
            }

            if (!originalMetaId) {
                return '';
            }

            const resolvedOriginalTitle = ModalManager.resolveMetaTitle(originalMetaId, originalMetaTitle);
            const originalLinkedValue = Math.abs(Number(snapshot?.meta_valor ?? snapshot?.valor ?? 0));
            const message = `Ao salvar, este lançamento deixa de registrar ${Utils.fmtMoney(originalLinkedValue)} em ${resolvedOriginalTitle}.`;

            return ModalManager.buildPlanningAlertCard({
                tone: 'warning',
                icon: 'target',
                eyebrow: 'Meta removida',
                title: `Sem vínculo com ${resolvedOriginalTitle}`,
                message
            });
        },

        buildEditBudgetAlert: async () => {
            const snapshot = STATE.editingLancamentoData || {};
            const tipo = String(DOM.selectLancTipo?.value || snapshot?.tipo || '').toLowerCase();
            if (tipo !== 'despesa') return '';

            const categoriaId = Utils.parsePositiveId(DOM.selectLancCategoria?.value || snapshot?.categoria_id || '');
            if (categoriaId === null) return '';

            const dataValue = DOM.inputLancData?.value || snapshot?.data || '';
            const period = resolvePlanningPeriod(dataValue);
            const orcamento = await planningStore.getBudgetByCategoria(categoriaId, dataValue);
            if (!orcamento) return '';

            const valorAtual = DOM.inputLancValor?.value
                ? Math.abs(Number(MoneyMask.unformat(DOM.inputLancValor.value)))
                : Math.abs(Number(snapshot?.valor ?? 0));
            const gastoAtual = Number(orcamento.gasto_real ?? 0);
            const limiteEfetivo = Number(orcamento.limite_efetivo ?? orcamento.valor_limite ?? 0);
            const mesmaCategoriaOriginal = Utils.parsePositiveId(snapshot?.categoria_id) === categoriaId;
            const mesmaCompetenciaOriginal = isSamePlanningPeriod(snapshot?.data, period);
            const originalContribution = mesmaCategoriaOriginal
                && mesmaCompetenciaOriginal
                && String(snapshot?.tipo || '').toLowerCase() === 'despesa'
                ? Math.abs(Number(snapshot?.valor ?? 0))
                : 0;
            const gastoBase = Math.max(0, gastoAtual - originalContribution);
            const gastoProjetado = gastoBase + valorAtual;
            const restante = Math.max(0, limiteEfetivo - gastoProjetado);
            const excesso = Math.max(0, gastoProjetado - limiteEfetivo);
            const percentual = limiteEfetivo > 0 ? (gastoProjetado / limiteEfetivo) * 100 : 0;
            const rollover = Number(orcamento.rollover_valor ?? 0);
            const categoriaNome = String(orcamento.categoria_nome || orcamento.categoria?.nome || 'categoria').trim();

            let tone = 'info';
            let title = `${categoriaNome} tem orçamento ativo`;
            let message = `Limite efetivo de ${Utils.fmtMoney(limiteEfetivo)}. Com este ajuste, restam ${Utils.fmtMoney(restante)} no período (${percentual.toFixed(1)}% usado).`;

            if (excesso > 0) {
                tone = 'danger';
                title = `${categoriaNome} estoura o orçamento`;
                message = `Limite efetivo de ${Utils.fmtMoney(limiteEfetivo)}. O gasto projetado vai para ${Utils.fmtMoney(gastoProjetado)} e passa ${Utils.fmtMoney(excesso)} do limite.`;
            } else if (percentual >= 80) {
                tone = 'warning';
                title = `${categoriaNome} entra em alerta`;
                message = `Limite efetivo de ${Utils.fmtMoney(limiteEfetivo)}. Depois deste ajuste, sobram ${Utils.fmtMoney(restante)} no período (${percentual.toFixed(1)}% usado).`;
            }

            if (rollover > 0) {
                message += ` O limite inclui ${Utils.fmtMoney(rollover)} de rollover.`;
            }

            return ModalManager.buildPlanningAlertCard({
                tone,
                icon: excesso > 0 ? 'triangle-alert' : 'wallet',
                eyebrow: 'Orcamento do periodo',
                title,
                message
            });
        },

        renderPlanningAlerts: async () => {
            if (!DOM.editLancPlanningAlerts) return;

            const renderId = ++editPlanningRenderSeq;
            await planningStore.ensureMetas();
            if (renderId !== editPlanningRenderSeq) return;

            const notices = [];
            const metaNotice = ModalManager.buildEditMetaAlert();
            if (metaNotice) notices.push(metaNotice);

            const budgetNotice = await ModalManager.buildEditBudgetAlert();
            if (renderId !== editPlanningRenderSeq) return;
            if (budgetNotice) notices.push(budgetNotice);

            if (!notices.length) {
                ModalManager.clearPlanningAlerts();
                return;
            }

            DOM.editLancPlanningAlerts.innerHTML = notices.join('');
            DOM.editLancPlanningAlerts.hidden = false;
            window.LK?.refreshIcons?.();
            window.lucide?.createIcons?.();
        },

        openEditLancamento: async (data) => {
            const modal = ModalManager.ensureLancModal();
            if (!modal || !Utils.canEditLancamento(data)) return;

            STATE.editingLancamentoId = data?.id ?? null;
            STATE.editingLancamentoData = data ? { ...data } : null;
            if (!STATE.editingLancamentoId) return;

            ModalManager.clearLancAlert();

            if (DOM.inputLancData) {
                DOM.inputLancData.value = (data?.data || '').slice(0, 10);
            }

            if (DOM.inputLancHora) {
                DOM.inputLancHora.value = data?.hora_lancamento || '';
            }

            if (DOM.selectLancTipo) {
                const tipo = String(data?.tipo || '').toLowerCase();
                DOM.selectLancTipo.value = ['receita', 'despesa'].includes(tipo) ? tipo : 'despesa';
            }

            OptionsManager.populateContaSelect(DOM.selectLancConta, data?.conta_id ?? null);
            const categoriaId = Utils.parsePositiveId(data?.categoria_id ?? null);
            const subcategoriaId = Utils.parsePositiveId(data?.subcategoria_id ?? null);
            OptionsManager.populateCategoriaSelect(
                DOM.selectLancCategoria,
                DOM.selectLancTipo?.value || '',
                categoriaId
            );

            if (categoriaId !== null) {
                OptionsManager.populateSubcategoriaSelect(categoriaId, subcategoriaId);
            } else {
                if (DOM.selectLancSubcategoria) DOM.selectLancSubcategoria.innerHTML = '<option value="">Sem subcategoria</option>';
                if (DOM.subcategoriaGroup) DOM.subcategoriaGroup.classList.add('hidden');
            }

            if (DOM.inputLancValor) {
                const valor = Math.abs(Number(data?.valor ?? 0));
                DOM.inputLancValor.value = Number.isFinite(valor) ? MoneyMask.format(valor) : '';
            }

            if (DOM.inputLancDescricao) {
                DOM.inputLancDescricao.value = data?.descricao || '';
            }

            if (DOM.selectLancFormaPagamento) {
                DOM.selectLancFormaPagamento.value = data?.forma_pagamento || '';
            }

            await OptionsManager.populateMetaSelect(
                DOM.selectLancMeta,
                data?.meta_id ?? null,
                { fallbackLabel: Utils.getLancamentoMetaTitle(data) || 'Meta vinculada' }
            );

            if (DOM.inputLancMetaValor) {
                const hasMeta = Number(data?.meta_id ?? 0) > 0;
                const linkedValue = hasMeta ? Math.abs(Number(data?.meta_valor ?? data?.valor ?? 0)) : 0;
                DOM.inputLancMetaValor.value = linkedValue > 0 ? MoneyMask.format(linkedValue) : '';
            }
            if (DOM.checkLancMetaRealizacao) {
                DOM.checkLancMetaRealizacao.checked = String(data?.meta_operacao || '').toLowerCase() === 'realizacao';
                delete DOM.checkLancMetaRealizacao.dataset.userTouched;
                delete DOM.checkLancMetaRealizacao.dataset.autoDefaultPending;
            }

            ModalManager.syncEditMetaField();
            syncModalSelects(DOM.modalEditLancEl);
            ModalManager.syncEditSummary();
            void ModalManager.renderPlanningAlerts();
            window.LK?.refreshIcons?.();
            modal.show();
        },

        ensureViewModal: () => {
            if (!STATE.modalViewLanc && DOM.modalViewLancEl) {
                window.LK?.modalSystem?.prepareBootstrapModal(DOM.modalViewLancEl, { scope: 'page' });
                STATE.modalViewLanc = bootstrap.Modal.getOrCreateInstance(DOM.modalViewLancEl);
            }
            return STATE.modalViewLanc;
        },

        openViewLancamento: (data) => {
            const modal = ModalManager.ensureViewModal();
            if (!modal || !data) return;

            STATE.viewingLancamento = data;

            if (DOM.viewLancamentoId) {
                DOM.viewLancamentoId.textContent = data.id || '';
            }

            if (DOM.viewLancData) {
                DOM.viewLancData.textContent = Utils.fmtDate(data.data);
            }

            if (DOM.viewLancTipo) {
                const tipo = String(data.tipo || (data.eh_transferencia ? 'transferencia' : '')).toLowerCase();
                if (tipo === 'receita') {
                    DOM.viewLancTipo.textContent = 'Receita';
                    DOM.viewLancTipo.className = 'badge bg-success';
                } else if (tipo === 'transferencia' || data.eh_transferencia) {
                    DOM.viewLancTipo.textContent = 'Transferencia';
                    DOM.viewLancTipo.className = 'badge bg-info text-dark';
                } else {
                    DOM.viewLancTipo.textContent = 'Despesa';
                    DOM.viewLancTipo.className = 'badge bg-danger';
                }
            }

            if (DOM.viewLancValor) {
                DOM.viewLancValor.textContent = Utils.fmtMoney(Math.abs(data.valor || 0));
            }

            if (DOM.viewLancStatus) {
                let statusLabel = 'Efetivado';
                let statusClass = 'bg-success';
                if (data.cancelado_em) {
                    statusLabel = 'Cancelado';
                    statusClass = 'bg-secondary';
                } else if (!data.pago) {
                    statusLabel = 'Pendente';
                    statusClass = 'bg-warning text-dark';
                }
                DOM.viewLancStatus.textContent = statusLabel;
                DOM.viewLancStatus.className = 'badge ' + statusClass;
            }

            if (DOM.viewLancCategoria) {
                DOM.viewLancCategoria.textContent = data.categoria_nome || data.categoria || '-';
            }

            if (DOM.viewLancConta) {
                DOM.viewLancConta.textContent = data.conta_nome || data.conta || '-';
            }

            if (DOM.viewLancCartaoItem && DOM.viewLancCartao) {
                if (data.cartao_credito_id && data.cartao_nome) {
                    DOM.viewLancCartaoItem.classList.remove('d-none');
                    DOM.viewLancCartao.textContent = data.cartao_nome;
                } else {
                    DOM.viewLancCartaoItem.classList.add('d-none');
                }
            }

            if (DOM.viewLancFormaPgtoItem && DOM.viewLancFormaPgto) {
                if (data.forma_pagamento) {
                    DOM.viewLancFormaPgtoItem.classList.remove('d-none');
                    DOM.viewLancFormaPgto.textContent = Utils.formatFormaPagamento ? Utils.formatFormaPagamento(data.forma_pagamento) : data.forma_pagamento;
                } else {
                    DOM.viewLancFormaPgtoItem.classList.add('d-none');
                }
            }

            if (DOM.viewLancMetaItem && DOM.viewLancMeta) {
                const metaTitle = Utils.getLancamentoMetaTitle(data);
                if (metaTitle) {
                    DOM.viewLancMetaItem.classList.remove('d-none');
                    DOM.viewLancMetaItem.style.display = '';
                    DOM.viewLancMeta.textContent = metaTitle;
                } else {
                    DOM.viewLancMetaItem.classList.add('d-none');
                    DOM.viewLancMetaItem.style.display = 'none';
                }
            }

            if (DOM.viewLancDescricaoCard && DOM.viewLancDescricao) {
                if (data.descricao && data.descricao.trim()) {
                    DOM.viewLancDescricaoCard.classList.remove('d-none');
                    DOM.viewLancDescricao.textContent = data.descricao;
                } else {
                    DOM.viewLancDescricaoCard.classList.add('d-none');
                }
            }

            if (DOM.viewLancParcelamentoCard && DOM.viewLancParcela) {
                if (data.parcela_atual && data.total_parcelas) {
                    DOM.viewLancParcelamentoCard.classList.remove('d-none');
                    DOM.viewLancParcelamentoCard.style.display = '';
                    DOM.viewLancParcela.textContent = `${data.parcela_atual}/${data.total_parcelas}`;
                } else {
                    DOM.viewLancParcelamentoCard.classList.add('d-none');
                    DOM.viewLancParcelamentoCard.style.display = 'none';
                }
            }

            if (DOM.viewLancLembreteCard) {
                const segundos = data.lembrar_antes_segundos;
                if (segundos && segundos > 0) {
                    DOM.viewLancLembreteCard.classList.remove('d-none');
                    DOM.viewLancLembreteCard.style.display = '';
                    let tempoLabel = '';
                    if (segundos >= 604800) tempoLabel = '1 semana antes';
                    else if (segundos >= 259200) tempoLabel = '3 dias antes';
                    else if (segundos >= 172800) tempoLabel = '2 dias antes';
                    else if (segundos >= 86400) tempoLabel = '1 dia antes';
                    else tempoLabel = `${Math.round(segundos / 3600)}h antes`;
                    if (DOM.viewLancLembreteTempo) DOM.viewLancLembreteTempo.textContent = tempoLabel;
                    const canais = [];
                    if (data.canal_inapp) canais.push('App');
                    if (data.canal_email) canais.push('E-mail');
                    if (DOM.viewLancLembreteCanais) DOM.viewLancLembreteCanais.textContent = canais.join(', ') || 'Nenhum canal';
                } else {
                    DOM.viewLancLembreteCard.classList.add('d-none');
                    DOM.viewLancLembreteCard.style.display = 'none';
                }
            }

            modal.show();
        },

        submitEditForm: async (ev) => {
            ev.preventDefault();
            if (!STATE.editingLancamentoId) return;

            ModalManager.clearLancAlert();

            const dataValue = DOM.inputLancData?.value || '';
            const horaValue = DOM.inputLancHora?.value || '';
            const tipoValue = DOM.selectLancTipo?.value || '';
            const contaValue = DOM.selectLancConta?.value || '';
            const categoriaValue = DOM.selectLancCategoria?.value || '';
            const contaId = Utils.parsePositiveId(contaValue);
            const categoriaId = Utils.parsePositiveId(categoriaValue);
            const subcategoriaId = Utils.parsePositiveId(DOM.selectLancSubcategoria?.value || '');
            const valorValue = DOM.inputLancValor?.value || '';
            const descricaoValue = (DOM.inputLancDescricao?.value || '').trim();
            const formaPagamentoValue = DOM.selectLancFormaPagamento?.value || '';
            const metaId = ModalManager.canLinkMetaInLancamentoEdit(tipoValue, formaPagamentoValue)
                ? ModalManager.getSelectedMetaId(DOM.selectLancMeta)
                : null;

            if (!dataValue) return ModalManager.showLancAlert('Informe a data do lançamento.');
            if (!tipoValue) return ModalManager.showLancAlert('Selecione o tipo do lançamento.');
            if (contaId === null) return ModalManager.showLancAlert('Selecione a conta.');

            const valorFloat = Math.abs(Number(MoneyMask.unformat(valorValue)));
            if (!Number.isFinite(valorFloat) || valorFloat <= 0) {
                return ModalManager.showLancAlert('Informe um valor válido maior que zero.');
            }

            let metaValor = null;
            let metaOperacao = null;
            if (metaId) {
                metaValor = ModalManager.getEditMetaValue(valorFloat);
                if (!Number.isFinite(metaValor) || metaValor <= 0) {
                    return ModalManager.showLancAlert('Informe quanto deste lancamento foi para a meta.');
                }
                if (metaValor > valorFloat) {
                    return ModalManager.showLancAlert('O valor da meta nao pode ser maior que o valor do lancamento.');
                }
                metaOperacao = ModalManager.resolveMetaOperationForEdit(tipoValue, true);
            }

            const payload = {
                data: dataValue,
                hora_lancamento: horaValue || null,
                tipo: tipoValue,
                valor: Number(valorFloat.toFixed(2)),
                descricao: descricaoValue,
                conta_id: contaId,
                categoria_id: categoriaId,
                subcategoria_id: subcategoriaId,
                forma_pagamento: formaPagamentoValue || null,
                meta_id: metaId,
                meta_operacao: metaOperacao,
                meta_valor: metaValor
            };

            const submitBtn = DOM.formLanc.querySelector('button[type="submit"]');
            submitBtn?.setAttribute('disabled', 'disabled');

            try {
                const json = await Modules.API.updateLancamento(STATE.editingLancamentoId, payload);

                if (json && json.success === false) {
                    const msg = getErrorMessage({ data: json }, 'Falha ao atualizar lancamento.');
                    throw new Error(msg);
                }

                ModalManager.ensureLancModal()?.hide();
                Notifications.toast('Lançamento atualizado com sucesso!');
                await Modules.DataManager.load();

                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'transactions',
                        action: 'update',
                        id: Number(STATE.editingLancamentoId)
                    }
                }));
            } catch (err) {
                const errorMsg = getErrorMessage(err, 'Falha ao atualizar lancamento.');
                ModalManager.showLancAlert(errorMsg);
                Notifications.toast(errorMsg, 'error');
            } finally {
                submitBtn?.removeAttribute('disabled');
            }
        },
    });
}
