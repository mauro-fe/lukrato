document.addEventListener('DOMContentLoaded', () => {
    // --- helper universal para 401/403 nesta página ---
    async function handleFetch403(response, base) {
        // 401: não autenticado -> manda para login (preserva retorno)
        if (response.status === 401) {
            const here = encodeURIComponent(location.pathname + location.search);
            location.href = `${base}login?return=${here}`;
            return true;
        }
        // 403: proibido -> mostra motivo (ex.: plano gratuito sem acesso)
        if (response.status === 403) {
            let msg = 'Acesso não permitido.';
            try {
                const data = await response.clone().json();
                msg = data?.message || msg;
            } catch (_) { }
            if (typeof Swal !== 'undefined' && Swal.fire) {
                await Swal.fire('Acesso restrito', msg, 'warning');
            } else {
                alert(msg);
            }
            return true;
        }
        return false;
    }

    const base = (typeof LK !== 'undefined' && typeof LK.getBase === 'function')
        ? LK.getBase()
        : (document.querySelector('meta[name="base-url"]')?.content || '/');

    const tableElement = document.getElementById('agendamentosTable');
    const form = document.getElementById('formAgendamento');
    const cache = new Map();

    let tableInstance = null;

    const hideFormError = () => {
        const alertBox = document.getElementById('agAlert');
        if (!alertBox) return;
        alertBox.textContent = '';
        alertBox.classList.add('d-none');
    };

    const showFormError = (message) => {
        const alertBox = document.getElementById('agAlert');
        if (!alertBox) return;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    };

    const parseMoneyToCents = (value) => {
        if (value === null || value === undefined) return 0;
        const normalized = String(value)
            .replace(/[^\d,.-]/g, '')
            .replace(/\./g, '')
            .replace(',', '.');
        const number = Number(normalized);
        if (Number.isFinite(number)) {
            return Math.round(number * 100);
        }
        return 0;
    };

    const getCsrf = () => {
        if (typeof LK !== 'undefined' && typeof LK.getCSRF === 'function') {
            return LK.getCSRF();
        }
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (match) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[match] || match));

    const formatCurrency = (value) => {
        const number = Number(value ?? 0) / 100;
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 2
        }).format(number || 0);
    };

    const formatDateTime = (value) => {
        if (!value) return '-';
        try {
            const dt = new Date(value.replace(' ', 'T'));
            return new Intl.DateTimeFormat('pt-BR', {
                dateStyle: 'short',
                timeStyle: 'short'
            }).format(dt);
        } catch {
            return value;
        }
    };

    const statusBadge = (status) => {
        const map = {
            pendente: 'warning',
            enviado: 'info',
            concluido: 'success',
            cancelado: 'danger'
        };
        const color = map[String(status).toLowerCase()] || 'secondary';
        return `<span class="badge bg-${color} text-uppercase">${escapeHtml(status || '-')}</span>`;
    };

    const ensureTable = () => {
        if (tableInstance || !tableElement || typeof Tabulator === 'undefined') {
            return tableInstance;
        }

        tableInstance = new Tabulator(tableElement, {
            layout: 'fitColumns',
            reactiveData: false,
            placeholder: 'Nenhum agendamento encontrado.',
            index: 'id',
            height: tableElement.dataset.height || '',
            pagination: false,
            columnDefaults: {
                tooltip: true,
                headerFilter: true,
                headerFilterPlaceholder: 'filtrar...'
            },
            columns: [
                {
                    title: 'Titulo',
                    field: 'titulo',
                    minWidth: 180,
                    formatter: (cell) => escapeHtml(cell.getValue() || '-')
                },
                {
                    title: 'Tipo',
                    field: 'tipo',
                    width: 110,
                    headerFilter: 'select',
                    headerFilterParams: {
                        values: {
                            '': 'Todos',
                            despesa: 'Despesa',
                            receita: 'Receita'
                        }
                    },
                    formatter: (cell) => {
                        const value = String(cell.getValue() || '').toLowerCase();
                        if (!value) return '-';
                        return value.charAt(0).toUpperCase() + value.slice(1);
                    }
                },
                {
                    title: 'Categoria',
                    field: 'categoria.nome',
                    minWidth: 160,
                    formatter: (cell) => escapeHtml(cell.getValue() || '-'),
                    headerFilter: 'input',
                    headerFilterPlaceholder: 'Categoria'
                },
                {
                    title: 'Conta',
                    field: 'conta.nome',
                    minWidth: 160,
                    formatter: (cell) => escapeHtml(cell.getValue() || '-'),
                    headerFilter: 'input',
                    headerFilterPlaceholder: 'Conta'
                },
                {
                    title: 'Valor',
                    field: 'valor_centavos',
                    hozAlign: 'right',
                    width: 140,
                    formatter: (cell) => formatCurrency(cell.getValue())
                },
                {
                    title: 'Data',
                    field: 'data_pagamento',
                    width: 160,
                    formatter: (cell) => escapeHtml(formatDateTime(cell.getValue()))
                },
                {
                    title: 'Status',
                    field: 'status',
                    width: 140,
                    hozAlign: 'center',
                    headerFilter: 'select',
                    headerFilterParams: {
                        values: {
                            '': 'Todos',
                            pendente: 'Pendente',
                            enviado: 'Enviado',
                            concluido: 'Concluido',
                            cancelado: 'Cancelado'
                        }
                    },
                    formatter: (cell) => statusBadge(cell.getValue())
                },
                {
                    title: 'Acoes',
                    field: 'acoes',
                    headerSort: false,
                    hozAlign: 'center',
                    width: 150,
                    formatter: () => (
                        '<div class="d-flex justify-content-center gap-2">' +
                        '<button type="button" class="lk-btn ghost btn-pay" data-action="pagar" title="Confirmar pagamento"><i class="fas fa-check"></i></button>' +
                        '<button type="button" class="lk-btn ghost btn-cancel" data-action="cancelar" title="Cancelar agendamento"><i class="fas fa-times"></i></button>' +
                        '</div>'
                    ),
                    cellClick: (e, cell) => {
                        const button = e.target.closest('[data-action]');
                        if (!button) return;
                        const action = button.getAttribute('data-action');
                        const row = cell.getRow();
                        const data = row?.getData();
                        if (!data || !action) return;

                        document.dispatchEvent(new CustomEvent('lukrato:agendamento-action', {
                            detail: {
                                action,
                                id: data.id ?? null,
                                record: data
                            }
                        }));
                    }
                }
            ]
        });

        return tableInstance;
    };

    async function loadAgendamentos(preserveFilters = true) {
        const table = ensureTable();
        if (!table) return;

        const filters = preserveFilters ? [...table.getHeaderFilters()] : [];

        try {
            const res = await fetch(`${base}api/agendamentos`, { credentials: 'include' });
            if (await handleFetch403(res, base)) {
                table.clearData(); // limpa tabela
                return;
            }
            const json = await res.json();
            if (json?.status !== 'success') throw new Error(json?.message || 'Erro ao carregar agendamentos.');

            const itens = Array.isArray(json?.data?.itens) ? json.data.itens : [];
            cache.clear();
            itens.forEach((item) => {
                if (item?.id !== undefined && item?.id !== null) {
                    cache.set(String(item.id), item);
                }
            });

            await table.replaceData(itens);

            if (filters.length) {
                filters.forEach((filter) => {
                    if (filter?.field) {
                        table.setHeaderFilterValue(filter.field, filter.value ?? '');
                    }
                });
            }
        } catch (error) {
            table.clearData();
            console.error(error);
            if (typeof Swal !== 'undefined' && Swal?.fire) {
                Swal.fire('Erro', 'Nao foi possivel carregar os agendamentos.', 'error');
            }
        }
    }

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!form) return;
        hideFormError();

        const tituloInput = document.getElementById('agTitulo');
        const dataHoraInput = document.getElementById('agDataHora');
        const lembrarInput = document.getElementById('agLembrar');
        const tipoInput = document.getElementById('agTipo');
        const categoriaInput = document.getElementById('agCategoria');
        const contaInput = document.getElementById('agConta');
        const valorInput = document.getElementById('agValor');
        const descricaoInput = document.getElementById('agDescricao');
        const recorrenteInput = document.getElementById('agRecorrente');
        const canalInappInput = document.getElementById('agCanalInapp');
        const canalEmailInput = document.getElementById('agCanalEmail');

        const titulo = (tituloInput?.value || '').trim();
        const dataPagamento = (dataHoraInput?.value || '').trim();
        const lembrarAntes = lembrarInput?.value || '0';
        const tipo = tipoInput?.value || 'despesa';
        const categoriaId = (categoriaInput?.value || '').trim();
        const contaId = (contaInput?.value || '').trim();
        const valorBruto = valorInput?.value || '';
        const descricao = (descricaoInput?.value || '').trim();
        const recorrente = recorrenteInput?.value === '1';
        const canalInapp = !!(canalInappInput?.checked);
        const canalEmail = !!(canalEmailInput?.checked);

        const erros = [];
        if (!titulo) erros.push('Informe o titulo.');
        if (!dataPagamento) erros.push('Informe a data e hora do pagamento.');
        if (!categoriaId) erros.push('Selecione a categoria.');

        const valorCentavos = parseMoneyToCents(valorBruto);
        if (valorCentavos < 0) {
            erros.push('Informe um valor valido.');
        }

        if (erros.length) {
            showFormError(erros.join('\n'));
            return;
        }

        const payload = new FormData();
        const token = getCsrf();
        if (token) {
            payload.append('_token', token);
            payload.append('csrf_token', token);
        }

        payload.append('titulo', titulo);
        payload.append('data_pagamento', dataPagamento);
        payload.append('lembrar_antes_segundos', lembrarAntes || '0');
        payload.append('tipo', tipo);
        payload.append('categoria_id', categoriaId);
        if (contaId) payload.append('conta_id', contaId);
        payload.append('valor', valorBruto);
        payload.append('valor_centavos', String(valorCentavos));
        if (descricao) payload.append('descricao', descricao);
        payload.append('recorrente', recorrente ? '1' : '0');
        payload.append('canal_inapp', canalInapp ? '1' : '0');
        payload.append('canal_email', canalEmail ? '1' : '0');

        Swal.fire({
            title: 'Salvando...',
            text: 'Aguarde enquanto o agendamento e salvo.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const res = await fetch(`${base}api/agendamentos`, {
                method: 'POST',
                body: payload,
                credentials: 'include'
            });

            let json = null;
            try {
                json = await res.json();
            } catch (_) { }

            if (!res.ok) {
                if (res.status === 422 && json?.errors) {
                    const detalhes = Object.values(json.errors).flat().join('\n');
                    showFormError(detalhes || (json?.message || 'Erros de validacao.'));
                    throw new Error('Erros de validacao.');
                }
                const message = json?.message || Erro;
                throw new Error(message);
            }

            Swal.fire('Sucesso', 'Agendamento salvo com sucesso!', 'success');
            form.reset();
            hideFormError();
            if (recorrenteInput) recorrenteInput.value = '0';
            const toggle = document.getElementById('agRecorrenteToggle');
            if (toggle) {
                toggle.dataset.recorrente = '0';
                toggle.classList.remove('btn-primary');
                toggle.classList.add('btn-outline-secondary');
                toggle.textContent = 'Nao, agendamento unico';
            }
            document.querySelector('#modalAgendamento .btn-close')?.click();
            await loadAgendamentos(true);
        } catch (error) {
            console.error(error);
            Swal.close();
            if (error.message && error.message !== 'Erros de validacao.') {
                Swal.fire('Erro', error.message, 'error');
            }
        }
    });

    document.addEventListener('lukrato:agendamento-action', async (event) => {
        const detail = event?.detail || {};
        const id = detail.id ? Number(detail.id) : null;
        const action = detail.action || '';
        if (!id || !action) return;

        if (action === 'pagar') {
            const confirm = await Swal.fire({
                title: 'Confirmar pagamento?',
                text: 'O agendamento sera marcado como concluido.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim',
                cancelButtonText: 'Nao'
            });
            if (!confirm.isConfirmed) return;

            const fd = new FormData();
            const token = getCsrf();
            if (token) {
                fd.append('_token', token);
                fd.append('csrf_token', token);
            }
            fd.append('status', 'concluido');

            try {
                const res = await fetch(`${base}api/agendamentos/${id}/status`, {
                    method: 'POST',
                    body: fd,
                    credentials: 'include'
                });
                if (await handleFetch403(res, base)) return;
                if (await handleFetch403(res, base)) { Swal.close(); return; }
                const json = await res.json();
                if (!res.ok || json?.status !== 'success') {
                    throw new Error(json?.message || 'Erro ' + res.status);
                }
                Swal.fire('Sucesso', 'Agendamento concluido!', 'success');
                await loadAgendamentos(true);
                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: { resource: 'transactions', action: 'create' }
                }));
            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao concluir agendamento.', 'error');
            }
        } else if (action === 'cancelar') {
            const confirm = await Swal.fire({
                title: 'Cancelar agendamento?',
                text: 'Ele sera marcado como cancelado.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim',
                cancelButtonText: 'Nao'
            });
            if (!confirm.isConfirmed) return;

            const fd = new FormData();
            const token = getCsrf();
            if (token) {
                fd.append('_token', token);
                fd.append('csrf_token', token);
            }

            try {
                const res = await fetch(`${base}api/agendamentos/${id}/cancelar`, {
                    method: 'POST',
                    body: fd,
                    credentials: 'include'
                });
                if (await handleFetch403(res, base)) return;
                const json = await res.json();
                if (!res.ok || json?.status !== 'success') {
                    throw new Error(json?.message || 'Erro ' + res.status);
                }
                Swal.fire('Sucesso', 'Agendamento cancelado.', 'success');
                await loadAgendamentos(true);
            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao cancelar agendamento.', 'error');
            }
        }
    });

    const recurrenceButton = document.getElementById('agRecorrenteToggle');
    const recurrenceInput = document.getElementById('agRecorrente');
    const applyRecurrenceVisual = (isActive) => {
        if (!recurrenceButton) return;
        recurrenceButton.dataset.recorrente = isActive ? '1' : '0';
        recurrenceButton.classList.toggle('btn-primary', isActive);
        recurrenceButton.classList.toggle('btn-outline-secondary', !isActive);
        recurrenceButton.textContent = isActive ? 'Sim, recorrente' : 'Nao, agendamento unico';
    };

    if (recurrenceButton && recurrenceInput) {
        applyRecurrenceVisual(recurrenceInput.value === '1');
        recurrenceButton.addEventListener('click', () => {
            const next = recurrenceInput.value === '1' ? '0' : '1';
            recurrenceInput.value = next;
            applyRecurrenceVisual(next === '1');
        });
    }

    ensureTable();
    loadAgendamentos();
});