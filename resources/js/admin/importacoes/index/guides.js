import { buildUrl } from '../../shared/api.js';
import { resolveImportacoesCsvTemplateEndpoint } from '../../api/endpoints/importacoes.js';
import { normalizeSourceType } from '../app.js';
import {
    formatDelimiterLabel,
    formatImportTargetLabel,
    formatMappingModeLabel,
    formatSourceTypeLabel,
    normalizeImportTarget,
} from './helpers.js';

export function buildColumnMapSummary(profile, importTarget) {
    const csvColumnMap = profile?.csvColumnMap && typeof profile.csvColumnMap === 'object'
        ? profile.csvColumnMap
        : {};
    const columnLabels = importTarget === 'cartao'
        ? {
            data: 'Data',
            descricao: 'Descrição',
            valor: 'Valor',
            observacao: 'Observação',
            id_externo: 'ID externo',
        }
        : {
            tipo: 'Tipo',
            data: 'Data',
            descricao: 'Descrição',
            valor: 'Valor',
            categoria: 'Categoria',
            subcategoria: 'Subcategoria',
        };

    const parts = Object.entries(columnLabels)
        .map(([key, label]) => {
            const columnReference = String(csvColumnMap[key] || '').trim().toUpperCase();
            return columnReference ? `${label}: ${columnReference}` : '';
        })
        .filter(Boolean);

    return parts.length > 0 ? parts.join(' | ') : 'Padrão Lukrato';
}

function buildPathTitle(importTarget, sourceType) {
    if (sourceType === 'csv') {
        return importTarget === 'cartao' ? 'CSV de fatura guiado' : 'CSV de conta no padrão Lukrato';
    }

    return importTarget === 'cartao' ? 'OFX de fatura do cartão' : 'OFX de extrato bancário';
}

function buildUploadPrompt(importTarget, sourceType) {
    if (sourceType === 'csv') {
        return importTarget === 'cartao'
            ? {
                title: 'Envie o CSV da fatura',
                copy: 'Use data, descrição e valor.',
            }
            : {
                title: 'Envie o CSV da conta',
                copy: 'Use tipo, data, descrição e valor.',
            };
    }

    return importTarget === 'cartao'
        ? {
            title: 'Envie o OFX da fatura',
            copy: 'O sistema lê a fatura automaticamente.',
        }
        : {
            title: 'Envie o OFX do extrato',
            copy: 'O sistema lê o extrato automaticamente.',
        };
}

function buildPreviewReadyCopy(importTarget, sourceType, autoAdjusted = false) {
    if (sourceType === 'csv') {
        if (importTarget === 'cartao') {
            return autoAdjusted
                ? 'CSV detectado. Revise data, descrição e valor.'
                : 'CSV pronto. Clique em "Preparar preview".';
        }

        return autoAdjusted
            ? 'CSV detectado. Revise datas e valores.'
            : 'CSV pronto. Clique em "Preparar preview".';
    }

    if (importTarget === 'cartao') {
        return autoAdjusted
            ? 'OFX detectado. Clique em "Preparar preview".'
            : 'OFX pronto. Clique em "Preparar preview".';
    }

    return autoAdjusted
        ? 'OFX detectado. Clique em "Preparar preview".'
        : 'OFX pronto. Clique em "Preparar preview".';
}

function buildDetectedFileNoteText(importTarget, sourceType, autoAdjusted = false) {
    if (sourceType === 'csv') {
        if (importTarget === 'cartao') {
            return autoAdjusted
                ? 'CSV detectado e alinhado.'
                : 'CSV detectado.';
        }

        return autoAdjusted
            ? 'CSV detectado e alinhado.'
            : 'CSV detectado.';
    }

    if (importTarget === 'cartao') {
        return autoAdjusted
            ? 'OFX detectado e alinhado.'
            : 'OFX detectado.';
    }

    return autoAdjusted
        ? 'OFX detectado e alinhado.'
        : 'OFX detectado.';
}

function buildDetectedImportTargetNoteText(detectedImportTarget, autoAdjusted = false) {
    const normalizedTarget = normalizeImportTarget(detectedImportTarget, '');
    if (!normalizedTarget) {
        return '';
    }

    if (normalizedTarget === 'cartao') {
        return autoAdjusted
            ? 'OFX reconhecido como cartão. O alvo foi ajustado.'
            : 'OFX reconhecido como cartão.';
    }

    return autoAdjusted
        ? 'OFX reconhecido como conta. O alvo foi ajustado.'
        : 'OFX reconhecido como conta.';
}

export function buildAdvancedDescription(importTarget, sourceType) {
    if (sourceType === 'csv') {
        return importTarget === 'cartao'
            ? 'Use data, descrição e valor. Abra o avançado só se precisar ajustar.'
            : 'Use tipo, data, descrição e valor. Abra o avançado só se precisar ajustar.';
    }

    return importTarget === 'cartao'
        ? 'OFX de fatura entra automático.'
        : 'OFX bancário entra automático.';
}

export function buildTemplateMeta(importTarget) {
    if (importTarget === 'cartao') {
        return {
            chip: 'Modelo de fatura',
            title: 'Modelo recomendado para cartão',
            copy: 'O modelo rápido cobre data, descrição e valor. Use o completo só se vierem colunas extras.',
            autoLabel: 'Modelo rápido',
            manualLabel: 'Modelo completo',
            autoHref: buildUrl(resolveImportacoesCsvTemplateEndpoint({ mode: 'auto', target: 'cartao' })),
            manualHref: buildUrl(resolveImportacoesCsvTemplateEndpoint({ mode: 'manual', target: 'cartao' })),
            contextNote: 'Usa a conta vinculada ao cartão.',
            summaryContext: 'Conta vinculada ao cartão selecionado',
            badge: 'Conta vinculada',
        };
    }

    return {
        chip: 'Modelo de conta',
        title: 'Modelo recomendado para conta',
        copy: 'O modelo rápido cobre tipo, data, descrição e valor. Use o completo se precisar de mais colunas.',
        autoLabel: 'Modelo rápido',
        manualLabel: 'Modelo completo',
        autoHref: buildUrl(resolveImportacoesCsvTemplateEndpoint({ mode: 'auto', target: 'conta' })),
        manualHref: buildUrl(resolveImportacoesCsvTemplateEndpoint({ mode: 'manual', target: 'conta' })),
        contextNote: 'Usa a conta selecionada.',
        summaryContext: 'Conta selecionada',
        badge: 'Conta ativa',
    };
}

export function renderProfileDisplay({
    activeProfileConfig,
    profileLoadState,
    profileLoadError,
    importTarget,
    resolveActiveConfigAccountId,
    resolveActiveConfigAccountLabel,
}) {
    const accountId = resolveActiveConfigAccountId(importTarget);
    const accountLabel = resolveActiveConfigAccountLabel();
    const matchingProfile = activeProfileConfig && activeProfileConfig.contaId === accountId
        ? activeProfileConfig
        : null;

    if (!accountId) {
        return {
            accountLabel,
            sourceTypeLabel: '-',
            mappingModeLabel: '-',
            hasHeaderLabel: '-',
            startRowLabel: '-',
            delimiterLabel: '-',
            dateFormatLabel: '-',
            decimalLabel: '-',
            columnMapSummary: 'Selecione uma conta para ver a configuração CSV aplicada.',
        };
    }

    if (!matchingProfile) {
        const loadingLabel = profileLoadState === 'loading' ? 'Carregando...' : 'Indisponível';

        return {
            accountLabel,
            sourceTypeLabel: loadingLabel,
            mappingModeLabel: loadingLabel,
            hasHeaderLabel: loadingLabel,
            startRowLabel: loadingLabel,
            delimiterLabel: loadingLabel,
            dateFormatLabel: loadingLabel,
            decimalLabel: loadingLabel,
            columnMapSummary: profileLoadError || 'Não foi possível carregar a configuração CSV desta conta.',
        };
    }

    return {
        accountLabel,
        sourceTypeLabel: String(matchingProfile.sourceType || 'ofx').toUpperCase(),
        mappingModeLabel: formatMappingModeLabel(matchingProfile.csvMappingMode),
        hasHeaderLabel: matchingProfile.csvHasHeader ? 'Sim' : 'Não',
        startRowLabel: String(matchingProfile.csvStartRow || (matchingProfile.csvHasHeader ? 2 : 1)),
        delimiterLabel: formatDelimiterLabel(matchingProfile.csvDelimiter),
        dateFormatLabel: matchingProfile.csvDateFormat || 'd/m/Y',
        decimalLabel: matchingProfile.csvDecimalSeparator || ',',
        columnMapSummary: buildColumnMapSummary(matchingProfile, importTarget),
    };
}

export function buildPathGuide(state) {
    const importTarget = state.selectedImportTarget;
    const sourceType = normalizeSourceType(state.selectedSourceType, 'ofx');
    const detectedSourceType = normalizeSourceType(state.selectedFileDetectedSourceType, '');
    const detectedImportTarget = normalizeImportTarget(state.selectedFileDetectedImportTarget, '');
    const title = buildPathTitle(importTarget, sourceType);

    if (state.selectedFile && detectedSourceType && detectedSourceType !== sourceType) {
        return {
            state: 'warning',
            title,
            copy: `O arquivo parece ${formatSourceTypeLabel(detectedSourceType)}, mas o formato ativo está em ${formatSourceTypeLabel(sourceType)}.`,
        };
    }

    if (
        state.selectedFile
        && detectedSourceType === 'ofx'
        && detectedImportTarget
        && detectedImportTarget !== importTarget
    ) {
        return {
            state: 'warning',
            title,
            copy: `O OFX parece ser de ${formatImportTargetLabel(detectedImportTarget)}, mas o alvo ativo está em ${formatImportTargetLabel(importTarget)}.`,
        };
    }

    if (
        state.selectedFile
        && detectedSourceType === 'ofx'
        && detectedImportTarget
        && state.targetAutoAdjustedToDetectedFile
    ) {
        return {
            state: 'ready',
            title,
            copy: `${buildDetectedImportTargetNoteText(detectedImportTarget, true)} ${buildAdvancedDescription(importTarget, sourceType)}`,
        };
    }

    if (state.selectedFile && detectedSourceType && state.sourceAutoAdjustedToDetectedFile) {
        return {
            state: 'ready',
            title,
            copy: `Arquivo ${formatSourceTypeLabel(detectedSourceType)} detectado. ${buildAdvancedDescription(importTarget, sourceType)}`,
        };
    }

    return {
        state: state.selectedFile && detectedSourceType ? 'ready' : 'info',
        title,
        copy: buildAdvancedDescription(importTarget, sourceType),
    };
}

export function buildContextGuide(state, { activeConfigAccountId, profileDisplay, currentAccountLabel, currentCardLabel }) {
    if (state.selectedImportTarget === 'cartao') {
        if (!state.selectedCardId) {
            return {
                state: 'warning',
                title: 'Selecione um cartão',
                copy: 'Escolha o cartão antes de continuar.',
            };
        }

        if (!activeConfigAccountId) {
            return {
                state: 'warning',
                title: currentCardLabel(),
                copy: 'Sem conta vinculada para herdar o perfil CSV.',
            };
        }

        return {
            state: 'ready',
            title: currentCardLabel(),
            copy: state.targetAutoAdjustedToDetectedFile
                ? `${buildDetectedImportTargetNoteText('cartao', true)} Perfil CSV de ${profileDisplay.accountLabel}.`
                : `Perfil CSV de ${profileDisplay.accountLabel}.`,
        };
    }

    if (!state.selectedAccountId) {
        return {
            state: 'warning',
            title: 'Selecione uma conta',
            copy: 'Escolha a conta antes de continuar.',
        };
    }

    return {
        state: 'ready',
        title: currentAccountLabel(),
        copy: state.targetAutoAdjustedToDetectedFile
            ? `${buildDetectedImportTargetNoteText('conta', true)} Esta conta define o preview.`
            : 'Esta conta define o preview.',
    };
}

export function buildReadinessGuide(state, quota, {
    isContextSelected,
    isContaOfxPreviewActive,
    getCompletedMessage,
    getProcessingMessage,
}) {
    const detectedSourceType = normalizeSourceType(state.selectedFileDetectedSourceType, '');
    const detectedImportTarget = normalizeImportTarget(state.selectedFileDetectedImportTarget, '');
    const hasContext = isContextSelected(state.selectedImportTarget);

    if (!quota.allowed) {
            return {
                state: 'warning',
                title: 'Limite do plano atingido',
                copy: quota.message || 'Faça upgrade para continuar.',
            };
    }

    if (state.previewStatus === 'loading_preview') {
            return {
                state: 'info',
                title: 'Montando preview',
                copy: 'Lendo o arquivo.',
            };
    }

    if (state.previewStatus === 'confirming') {
        return {
            state: 'info',
            title: 'Importação em andamento',
            copy: String(state.jobProgressMessage || getProcessingMessage()),
        };
    }

    if (state.previewStatus === 'confirmed') {
            return {
                state: 'ready',
                title: 'Importação concluída',
                copy: getCompletedMessage(),
        };
    }

    if (state.previewStatus === 'preview_error') {
            return {
                state: 'warning',
                title: 'Ajuste antes de reenviar',
                copy: state.previewErrors[0] || 'Revise o contexto e o arquivo.',
            };
    }

    if (state.previewStatus === 'preview_ready') {
        if (!state.previewCanConfirm) {
            return {
                state: 'warning',
                title: 'Preview com bloqueios',
                copy: state.previewErrors[0] || 'Corrija antes de confirmar.',
            };
        }

        if (isContaOfxPreviewActive(state.selectedImportTarget, state.selectedSourceType)
            && (state.previewSummary.uncategorizedRows || 0) > 0) {
            return {
                state: 'ready',
                title: 'Preview pronto',
                copy: `${state.previewSummary.uncategorizedRows} linha(s) sem categoria.`,
            };
        }

        return {
            state: 'ready',
            title: 'Preview pronto',
            copy: 'Revise e confirme.',
        };
    }

    if (!hasContext) {
            return {
                state: 'warning',
                title: state.selectedImportTarget === 'cartao' ? 'Falta escolher o cartão' : 'Falta escolher a conta',
                copy: 'Escolha o contexto primeiro.',
            };
    }

    if (!state.selectedFile) {
        const uploadPrompt = buildUploadPrompt(state.selectedImportTarget, state.selectedSourceType);
        return {
            state: 'info',
            title: uploadPrompt.title,
            copy: uploadPrompt.copy,
        };
    }

    if (!detectedSourceType) {
        return {
            state: 'warning',
            title: 'Revise o tipo do arquivo',
            copy: 'Não foi possível identificar se o arquivo é OFX ou CSV.',
        };
    }

    if (
        detectedSourceType === 'ofx'
        && detectedImportTarget
        && detectedImportTarget !== state.selectedImportTarget
    ) {
        return {
            state: 'warning',
            title: 'OFX e alvo divergem',
            copy: `O OFX parece ser de ${formatImportTargetLabel(detectedImportTarget)}, mas o alvo ativo está em ${formatImportTargetLabel(state.selectedImportTarget)}.`,
        };
    }

    if (detectedSourceType !== state.selectedSourceType) {
        return {
            state: 'warning',
            title: 'Formato e arquivo divergem',
            copy: `O arquivo parece ser ${formatSourceTypeLabel(detectedSourceType)}, mas o formato ativo está em ${formatSourceTypeLabel(state.selectedSourceType)}.`,
        };
    }

    return {
        state: 'ready',
        title: 'Pronto para gerar preview',
        copy: buildPreviewReadyCopy(state.selectedImportTarget, detectedSourceType, state.sourceAutoAdjustedToDetectedFile),
    };
}

export function buildFileNote(state) {
    if (!state.selectedFile) {
        return null;
    }

    const detectedSourceType = normalizeSourceType(state.selectedFileDetectedSourceType, '');
    const detectedImportTarget = normalizeImportTarget(state.selectedFileDetectedImportTarget, '');
    if (!detectedSourceType) {
        return {
            state: 'warning',
            text: 'Não foi possível detectar se o arquivo é OFX ou CSV.',
        };
    }

    if (
        detectedSourceType === 'ofx'
        && detectedImportTarget
        && detectedImportTarget !== state.selectedImportTarget
    ) {
        return {
            state: 'warning',
            text: `O OFX parece ser de ${formatImportTargetLabel(detectedImportTarget)}, mas o alvo selecionado está em ${formatImportTargetLabel(state.selectedImportTarget)}.`,
        };
    }

    if (detectedSourceType === 'ofx' && detectedImportTarget && state.targetAutoAdjustedToDetectedFile) {
        return {
            state: 'ready',
            text: buildDetectedImportTargetNoteText(detectedImportTarget, true),
        };
    }

    if (detectedSourceType === 'ofx' && detectedImportTarget) {
        return {
            state: 'info',
            text: buildDetectedImportTargetNoteText(detectedImportTarget, false),
        };
    }

    if (detectedSourceType !== state.selectedSourceType) {
        return {
            state: 'warning',
            text: `Arquivo ${formatSourceTypeLabel(detectedSourceType)} detectado, mas o formato selecionado está em ${formatSourceTypeLabel(state.selectedSourceType)}.`,
        };
    }

    if (state.sourceAutoAdjustedToDetectedFile) {
        return {
            state: 'ready',
            text: buildDetectedFileNoteText(
                state.selectedImportTarget,
                detectedSourceType,
                true,
            ),
        };
    }

    return {
        state: 'info',
        text: buildDetectedFileNoteText(
            state.selectedImportTarget,
            detectedSourceType,
            false,
        ),
    };
}
