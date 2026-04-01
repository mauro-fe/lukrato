/**
 * ============================================================================
 * LUKRATO - Dashboard / Runtime
 * ============================================================================
 * Transaction flow, dashboard refresh cycle and event bindings.
 * ============================================================================
 */

export function createDashboardRuntime({
    STATE,
    DOM,
    Utils,
    API,
    Notifications,
    Renderers,
    Provisao,
    OptionalWidgets,
    invalidateDashboardOverview,
    getErrorMessage,
    logClientError,
}) {
    const TransactionManager = {
        delete: async (id, rowElement) => {
            try {
                await Notifications.ensureSwal();

                const confirmed = await Notifications.confirm(
                    'Excluir lançamento?',
                    'Esta ação não pode ser desfeita.'
                );

                if (!confirmed) return;

                Notifications.loading('Excluindo...');

                await API.deleteTransaction(Number(id));

                Notifications.close();
                Notifications.toast('success', 'Lançamento excluído com sucesso!');

                if (rowElement) {
                    rowElement.style.opacity = '0';
                    rowElement.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        rowElement.remove();

                        if (DOM.tableBody.children.length === 0) {
                            if (DOM.emptyState) DOM.emptyState.style.display = 'block';
                            if (DOM.table) DOM.table.style.display = 'none';
                        }
                    }, 300);
                }

                // Refresh will be triggered by the lukrato:data-changed event listener
                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'transactions',
                        action: 'delete',
                        id: Number(id)
                    }
                }));
            } catch (err) {
                console.error('Erro ao excluir lançamento:', err);
                await Notifications.ensureSwal();
                Notifications.error('Erro', getErrorMessage(err, 'Falha ao excluir lançamento'));
            }
        }
    };

    const DashboardManager = {
        refresh: async ({ force = false } = {}) => {
            if (STATE.isLoading) return;

            STATE.isLoading = true;
            const month = Utils.getCurrentMonth();
            STATE.currentMonth = month;

            if (force) {
                invalidateDashboardOverview(month);
            }

            try {
                Renderers.updateMonthLabel(month);

                await Promise.allSettled([
                    Renderers.renderKPIs(month),
                    Renderers.renderTable(month),
                    Renderers.renderTransactionsList(month),
                    Renderers.renderChart(month),
                    Provisao.render(month),
                    OptionalWidgets.render(month)
                ]);
            } catch (err) {
                logClientError('Erro ao atualizar dashboard', err, 'Falha ao atualizar dashboard');
            } finally {
                STATE.isLoading = false;
            }
        },

        init: async () => {
            await DashboardManager.refresh({ force: false });
        }
    };

    const EventListeners = {
        init: () => {
            if (STATE.eventListenersInitialized) {
                return;
            }
            STATE.eventListenersInitialized = true;

            // Event listener para tabela desktop
            DOM.tableBody?.addEventListener('click', async (e) => {
                const btn = e.target.closest('.btn-del');
                if (!btn) return;

                const row = e.target.closest('tr');
                const id = btn.getAttribute('data-id');

                if (!id) return;

                btn.disabled = true;
                await TransactionManager.delete(id, row);
                btn.disabled = false;
            });

            // Event listener para cards mobile
            DOM.cardsContainer?.addEventListener('click', async (e) => {
                const btn = e.target.closest('.btn-del');
                if (!btn) return;

                const card = e.target.closest('.transaction-card');
                const id = btn.getAttribute('data-id');

                if (!id) return;

                btn.disabled = true;
                await TransactionManager.delete(id, card);
                btn.disabled = false;
            });

            // Event listener para lista de transações (novo layout)
            DOM.transactionsList?.addEventListener('click', async (e) => {
                const btn = e.target.closest('.btn-del');
                if (!btn) return;

                const item = e.target.closest('.dash-tx-item');
                const id = btn.getAttribute('data-id');

                if (!id) return;

                btn.disabled = true;
                await TransactionManager.delete(id, item);
                btn.disabled = false;
            });

            document.addEventListener('lukrato:data-changed', () => {
                invalidateDashboardOverview(STATE.currentMonth || Utils.getCurrentMonth());
                DashboardManager.refresh({ force: false });
            });

            document.addEventListener('lukrato:month-changed', () => {
                DashboardManager.refresh({ force: false });
            });

            document.addEventListener('lukrato:theme-changed', () => {
                Renderers.renderChart(STATE.currentMonth || Utils.getCurrentMonth());
            });

            // Chart mode toggle (donut vs compare)
            const chartToggle = document.getElementById('chartToggle');
            if (chartToggle) {
                chartToggle.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-mode]');
                    if (!btn) return;
                    const mode = btn.getAttribute('data-mode');
                    chartToggle.querySelectorAll('.dash-chart-toggle__btn').forEach(b => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    Renderers.renderChart(STATE.currentMonth || Utils.getCurrentMonth(), mode);
                });
            }

        }
    };

    return {
        TransactionManager,
        DashboardManager,
        EventListeners,
    };
}
