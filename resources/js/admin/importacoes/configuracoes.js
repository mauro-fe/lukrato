import '../../../css/admin/importacoes/configuracoes.css';
import {
    appendCsrfToken,
    bootImportacoesPage,
    fetchApiJson,
    normalizeSourceType,
} from './app.js';

const context = bootImportacoesPage('configuracoes');

if (context) {
    const accountSelect = context.root.querySelector('[data-imp-account-select]');
    const selectedAccountLabel = context.root.querySelector('[data-imp-config-selected-account]');
    const saveForm = context.root.querySelector('[data-imp-config-save-form]');
    const saveFeedback = context.root.querySelector('[data-imp-config-save-feedback]');
    const contaIdInput = context.root.querySelector('[data-imp-conta-id-input]');
    const sourceTypeInput = context.root.querySelector('[data-imp-source-type]');
    const agenciaInput = context.root.querySelector('[data-imp-agencia]');
    const numeroContaInput = context.root.querySelector('[data-imp-numero-conta]');

    const csvMappingModeInputs = Array.from(context.root.querySelectorAll('[data-imp-csv-mapping-mode]'));
    const csvStartRowInput = context.root.querySelector('[data-imp-csv-start-row]');
    const csvDelimiterInput = context.root.querySelector('[data-imp-csv-delimiter]');
    const csvDateFormatInput = context.root.querySelector('[data-imp-csv-date-format]');
    const csvDecimalSeparatorInput = context.root.querySelector('[data-imp-csv-decimal-separator]');
    const csvHasHeaderInput = context.root.querySelector('[data-imp-csv-has-header]');
    const csvManualFields = context.root.querySelector('[data-imp-csv-manual-fields]');

    const csvColumnInputs = {
        tipo: context.root.querySelector('[data-imp-csv-column-tipo]'),
        data: context.root.querySelector('[data-imp-csv-column-data]'),
        descricao: context.root.querySelector('[data-imp-csv-column-descricao]'),
        valor: context.root.querySelector('[data-imp-csv-column-valor]'),
        categoria: context.root.querySelector('[data-imp-csv-column-categoria]'),
        subcategoria: context.root.querySelector('[data-imp-csv-column-subcategoria]'),
        observacao: context.root.querySelector('[data-imp-csv-column-observacao]'),
        id_externo: context.root.querySelector('[data-imp-csv-column-id-externo]'),
    };

    const saveButton = context.root.querySelector('[data-imp-save-button]');

    const summaryContaId = context.root.querySelector('[data-imp-summary-conta-id]');
    const summarySourceType = context.root.querySelector('[data-imp-summary-source-type]');
    const summaryCsvMappingMode = context.root.querySelector('[data-imp-summary-csv-mapping-mode]');
    const summaryAgencia = context.root.querySelector('[data-imp-summary-agencia]');
    const summaryNumeroConta = context.root.querySelector('[data-imp-summary-numero-conta]');
    const summaryCsvDelimiter = context.root.querySelector('[data-imp-summary-csv-delimiter]');
    const summaryCsvStartRow = context.root.querySelector('[data-imp-summary-csv-start-row]');

    const saveEndpoint = String(context.root.dataset.impConfigSaveEndpoint || '').trim();

    const setFeedback = (message, status = 'idle') => {
        if (!saveFeedback) {
            return;
        }

        saveFeedback.textContent = String(message || '');
        saveFeedback.dataset.status = status;
    };

    const parsePositiveInt = (value, fallback = 1) => {
        const parsed = Number.parseInt(String(value || ''), 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
    };

    const normalizeColumnReference = (value) => {
        const normalized = String(value || '').trim().toUpperCase();
        if (!normalized) {
            return '';
        }

        if (/^\d+$/.test(normalized) || /^[A-Z]+$/.test(normalized)) {
            return normalized.slice(0, 8);
        }

        return '';
    };

    const currentMappingMode = () => {
        const checked = csvMappingModeInputs.find((input) => input.checked);
        return checked && checked.value === 'manual' ? 'manual' : 'auto';
    };

    const toggleManualFields = () => {
        if (!csvManualFields) {
            return;
        }

        const isManual = currentMappingMode() === 'manual';
        csvManualFields.hidden = !isManual;
    };

    const syncSummary = () => {
        if (summaryContaId && contaIdInput) {
            summaryContaId.textContent = String(contaIdInput.value || '0');
        }

        if (summarySourceType && sourceTypeInput) {
            summarySourceType.textContent = String(sourceTypeInput.value || 'ofx').toLowerCase();
        }

        if (summaryCsvMappingMode) {
            summaryCsvMappingMode.textContent = currentMappingMode();
        }

        if (summaryAgencia && agenciaInput) {
            summaryAgencia.textContent = agenciaInput.value.trim() || 'Opcional';
        }

        if (summaryNumeroConta && numeroContaInput) {
            summaryNumeroConta.textContent = numeroContaInput.value.trim() || 'Opcional';
        }

        if (summaryCsvDelimiter && csvDelimiterInput) {
            const delimiter = csvDelimiterInput.value.trim();
            summaryCsvDelimiter.textContent = delimiter || ';';
        }

        if (summaryCsvStartRow && csvStartRowInput) {
            summaryCsvStartRow.textContent = String(parsePositiveInt(csvStartRowInput.value, 1));
        }
    };

    const applyOptionsToForm = (options = {}) => {
        if (csvDelimiterInput && options.csv_delimiter !== undefined) {
            csvDelimiterInput.value = String(options.csv_delimiter || ';');
        }

        if (csvHasHeaderInput && options.csv_has_header !== undefined) {
            csvHasHeaderInput.checked = Boolean(options.csv_has_header);
        }

        if (csvStartRowInput && options.csv_start_row !== undefined) {
            csvStartRowInput.value = String(parsePositiveInt(options.csv_start_row, csvHasHeaderInput?.checked ? 2 : 1));
        }

        if (csvDateFormatInput && options.csv_date_format !== undefined) {
            csvDateFormatInput.value = String(options.csv_date_format || 'd/m/Y');
        }

        if (csvDecimalSeparatorInput && options.csv_decimal_separator !== undefined) {
            csvDecimalSeparatorInput.value = String(options.csv_decimal_separator || ',');
        }

        const mode = String(options.csv_mapping_mode || 'auto').toLowerCase() === 'manual' ? 'manual' : 'auto';
        csvMappingModeInputs.forEach((input) => {
            input.checked = input.value === mode;
        });

        const columnMap = typeof options.csv_column_map === 'object' && options.csv_column_map !== null
            ? options.csv_column_map
            : {};

        Object.entries(csvColumnInputs).forEach(([field, input]) => {
            if (!input) {
                return;
            }

            input.value = normalizeColumnReference(columnMap[field] || input.value || '');
        });

        toggleManualFields();
    };

    const assignPayloadToForm = (profile = {}, fallbackContaId = null) => {
        if (sourceTypeInput) {
            sourceTypeInput.value = normalizeSourceType(profile.source_type || sourceTypeInput.value || 'ofx');
        }

        if (agenciaInput) {
            agenciaInput.value = String(profile.agencia || '');
        }

        if (numeroContaInput) {
            numeroContaInput.value = String(profile.numero_conta || '');
        }

        if (contaIdInput) {
            contaIdInput.value = String(profile.conta_id || fallbackContaId || contaIdInput.value || '');
        }

        const options = typeof profile.options === 'object' && profile.options !== null ? profile.options : {};
        applyOptionsToForm(options);
        syncSummary();
    };

    if (accountSelect) {
        context.setState({ selectedAccountId: Number(accountSelect.value || 0) || null });

        accountSelect.addEventListener('change', () => {
            context.setState({
                selectedAccountId: Number(accountSelect.value || 0) || null,
                previewStatus: 'idle',
            });

            if (selectedAccountLabel) {
                selectedAccountLabel.textContent = accountSelect.value || 'Não definida';
            }
        });
    }

    [
        agenciaInput,
        numeroContaInput,
        sourceTypeInput,
        csvDelimiterInput,
        csvStartRowInput,
        csvDateFormatInput,
        csvDecimalSeparatorInput,
        csvHasHeaderInput,
        ...Object.values(csvColumnInputs),
    ].forEach((field) => {
        if (!field) {
            return;
        }

        field.addEventListener('input', syncSummary);
        field.addEventListener('change', syncSummary);
    });

    csvMappingModeInputs.forEach((input) => {
        input.addEventListener('change', () => {
            toggleManualFields();
            syncSummary();
        });
    });

    if (saveForm) {
        saveForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!saveEndpoint) {
                setFeedback('Endpoint de salvamento não configurado.', 'error');
                return;
            }

            const contaId = Number.parseInt(String(contaIdInput?.value || context.state.selectedAccountId || ''), 10);
            if (!Number.isFinite(contaId) || contaId <= 0) {
                setFeedback('Selecione uma conta válida para salvar.', 'error');
                return;
            }

            const mappingMode = currentMappingMode();
            const formData = new FormData(saveForm);
            formData.set('conta_id', String(contaId));
            formData.set('source_type', normalizeSourceType(formData.get('source_type') || 'ofx'));
            formData.set('csv_mapping_mode', mappingMode);
            formData.set('csv_has_header', csvHasHeaderInput?.checked ? '1' : '0');
            formData.set('csv_start_row', String(parsePositiveInt(csvStartRowInput?.value || '', csvHasHeaderInput?.checked ? 2 : 1)));
            formData.set('csv_delimiter', String(csvDelimiterInput?.value || ';').trim());
            formData.set('csv_date_format', String(csvDateFormatInput?.value || 'd/m/Y').trim());
            formData.set('csv_decimal_separator', String(csvDecimalSeparatorInput?.value || ',').trim());

            Object.entries(csvColumnInputs).forEach(([field, input]) => {
                formData.set(`csv_column_${field}`, normalizeColumnReference(input?.value || ''));
            });

            appendCsrfToken(formData, saveForm);

            if (saveButton) {
                saveButton.disabled = true;
            }

            setFeedback('Salvando configuração...', 'loading');

            try {
                const response = await fetchApiJson(saveEndpoint, {
                    method: 'POST',
                    body: formData,
                });

                const profile = typeof response?.data === 'object' && response?.data !== null
                    ? response.data
                    : {};

                assignPayloadToForm(profile, contaId);
                setFeedback('Configuração salva com sucesso.', 'success');
            } catch (error) {
                const messages = Array.isArray(error?.messages) && error.messages.length > 0
                    ? error.messages
                    : [String(error?.message || 'Falha ao salvar configuração.')];
                setFeedback(messages.join(' '), 'error');
            } finally {
                if (saveButton) {
                    saveButton.disabled = false;
                }
            }
        });
    }

    toggleManualFields();
    syncSummary();
}
