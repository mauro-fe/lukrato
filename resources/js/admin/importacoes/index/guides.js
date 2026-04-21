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
                copy: 'Mantenha data, descrição e valor legíveis. Sem coluna de tipo, valor positivo vira despesa e negativo vira estorno.',
            }
            : {
                title: 'Envie o CSV no padrão de conta',
                copy: 'Use tipo;data;descricao;valor com ;, datas dd/mm/yyyy e vírgula decimal.',
            };
    }

    return importTarget === 'cartao'
        ? {
            title: 'Envie o OFX da fatura',
            copy: 'Compras e parcelas do MEMO entram automáticas.',
        }
        : {
            title: 'Envie o OFX do extrato',
            copy: 'Pix, depósito e histórico entram automáticos.',
        };
}

function buildPreviewReadyCopy(importTarget, sourceType, autoAdjusted = false) {
    if (sourceType === 'csv') {
        if (importTarget === 'cartao') {
            return autoAdjusted
                ? 'Formato sincronizado para CSV. Agora valide data, descrição e valor da fatura no preview.'
                : 'CSV pronto. Clique em "Preparar preview" para validar a fatura antes da confirmação.';
        }

        return autoAdjusted
            ? 'Formato sincronizado para CSV. Agora valide cabeçalho, datas e valores no padrão tipo;data;descricao;valor.'
            : 'CSV pronto. Clique em "Preparar preview" para validar cabeçalho, datas e valores.';
    }

    if (importTarget === 'cartao') {
        return autoAdjusted
            ? 'Formato sincronizado para OFX. Agora clique em "Preparar preview" para ler compras e parcelas da fatura.'
            : 'OFX pronto. Clique em "Preparar preview" para ler compras e parcelas da fatura automaticamente.';
    }

    return autoAdjusted
        ? 'Formato sincronizado para OFX. Agora clique em "Preparar preview" para ler o extrato automaticamente.'
        : 'OFX pronto. Clique em "Preparar preview" para ler data, valor e histórico do extrato.';
}

function buildDetectedFileNoteText(importTarget, sourceType, autoAdjusted = false) {
    if (sourceType === 'csv') {
        if (importTarget === 'cartao') {
            return autoAdjusted
                ? 'Detectamos um CSV e alinhamos o formato. Se vier sem coluna de tipo, valor positivo entra como despesa e negativo como estorno.'
                : 'CSV detectado. Se vier sem coluna de tipo, valor positivo entra como despesa e negativo como estorno.';
        }

        return autoAdjusted
            ? 'Detectamos um CSV e alinhamos o formato. O melhor encaixe aqui é tipo;data;descricao;valor com ;, dd/mm/yyyy, vírgula decimal e sem linhas incompletas no final.'
            : 'CSV detectado. O melhor encaixe aqui é tipo;data;descricao;valor com ;, dd/mm/yyyy, vírgula decimal e sem linhas incompletas no final.';
    }

    if (importTarget === 'cartao') {
        return autoAdjusted
            ? 'Detectamos um OFX e alinhamos o formato. Compras e parcelas da fatura podem vir no MEMO sem exigir mapeamento manual.'
            : 'OFX detectado. Compras e parcelas da fatura podem vir no MEMO sem exigir mapeamento manual.';
    }

    return autoAdjusted
        ? 'Detectamos um OFX e alinhamos o formato. O Lukrato usa data, valor e histórico mesmo quando o banco usa TRNTYPE genérico.'
        : 'OFX detectado. O Lukrato usa data, valor e histórico mesmo quando o banco usa TRNTYPE genérico.';
}

function buildDetectedImportTargetNoteText(detectedImportTarget, autoAdjusted = false) {
    const normalizedTarget = normalizeImportTarget(detectedImportTarget, '');
    if (!normalizedTarget) {
        return '';
    }

    if (normalizedTarget === 'cartao') {
        return autoAdjusted
            ? 'O conteúdo do OFX foi reconhecido como cartão/fatura e o alvo foi ajustado automaticamente. Revise apenas o cartão antes do preview.'
            : 'O conteúdo do OFX foi reconhecido como cartão/fatura. Revise apenas o cartão antes do preview.';
    }

    return autoAdjusted
        ? 'O conteúdo do OFX foi reconhecido como conta bancária e o alvo foi ajustado automaticamente para Conta.'
        : 'O conteúdo do OFX foi reconhecido como conta bancária.';
}

export function buildAdvancedDescription(importTarget, sourceType) {
    if (sourceType === 'csv') {
        return importTarget === 'cartao'
            ? 'Use data, descrição e valor. Abra o avançado só se cabeçalho, delimitador ou data fugirem do padrão.'
            : 'Use tipo;data;descricao;valor com ;, datas em dd/mm/yyyy e vírgula decimal. Abra o avançado só se o arquivo fugir disso.';
    }

    return importTarget === 'cartao'
        ? 'OFX de fatura entra automático. Parcelas podem vir no MEMO sem ajuste manual.'
        : 'OFX bancário entra automático. O Lukrato usa data, valor e histórico do extrato.';
}

export function buildTemplateMeta(importTarget) {
    if (importTarget === 'cartao') {
        return {
            chip: 'Modelo de fatura',
            title: 'Modelo recomendado para CSV de cartão/fatura',
            copy: 'O modelo automático cobre data, descrição e valor. Use o manual se a operadora exportar observação, ID externo ou colunas extras fora do padrão esperado.',
            autoLabel: 'Baixar modelo rápido de fatura',
            manualLabel: 'Baixar modelo completo de fatura',
            autoHref: buildUrl(resolveImportacoesCsvTemplateEndpoint({ mode: 'auto', target: 'cartao' })),
            manualHref: buildUrl(resolveImportacoesCsvTemplateEndpoint({ mode: 'manual', target: 'cartao' })),
            contextNote: 'A configuração CSV usa a conta vinculada ao cartão selecionado.',
            summaryContext: 'Conta vinculada ao cartão selecionado',
            badge: 'Conta vinculada',
        };
    }

    return {
        chip: 'Modelo de conta',
        title: 'Modelo recomendado para CSV de conta',
        copy: 'O modelo rápido segue o padrão tipo;data;descricao;valor com ;, dd/mm/yyyy e valores como 149,90. O manual adiciona categoria, subcategoria, observação e ID externo.',
        autoLabel: 'Baixar modelo rápido de conta',
        manualLabel: 'Baixar modelo completo de conta',
        autoHref: buildUrl(resolveImportacoesCsvTemplateEndpoint({ mode: 'auto', target: 'conta' })),
        manualHref: buildUrl(resolveImportacoesCsvTemplateEndpoint({ mode: 'manual', target: 'conta' })),
        contextNote: 'A configuração CSV usa a conta selecionada neste fluxo.',
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
            copy: `Arquivo ${formatSourceTypeLabel(detectedSourceType)} detectado, mas o formato ativo está em ${formatSourceTypeLabel(sourceType)}. Alinhe antes do preview para evitar erro de leitura.`,
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
            copy: `O conteúdo do OFX parece ser de ${formatImportTargetLabel(detectedImportTarget)}, mas o alvo ativo está em ${formatImportTargetLabel(importTarget)}.`,
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
            copy: `Arquivo ${formatSourceTypeLabel(detectedSourceType)} detectado e o formato foi sincronizado automaticamente. ${buildAdvancedDescription(importTarget, sourceType)}`,
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
                copy: 'Escolha o cartão/fatura antes de preparar o preview.',
            };
        }

        if (!activeConfigAccountId) {
            return {
                state: 'warning',
                title: currentCardLabel(),
                copy: 'Sem conta vinculada para herdar o perfil CSV. OFX segue normal; CSV pode exigir ajuste manual.',
            };
        }

        return {
            state: 'ready',
            title: currentCardLabel(),
            copy: state.targetAutoAdjustedToDetectedFile
                ? `${buildDetectedImportTargetNoteText('cartao', true)} Perfil CSV herdado de ${profileDisplay.accountLabel}.`
                : `Perfil CSV herdado de ${profileDisplay.accountLabel}.`,
        };
    }

    if (!state.selectedAccountId) {
        return {
            state: 'warning',
            title: 'Selecione uma conta',
            copy: 'Escolha a conta antes de preparar o preview.',
        };
    }

    return {
        state: 'ready',
        title: currentAccountLabel(),
        copy: state.targetAutoAdjustedToDetectedFile
            ? `${buildDetectedImportTargetNoteText('conta', true)} Esta conta define o preview e o perfil CSV aplicado.`
            : 'Esta conta define o preview e o perfil CSV aplicado.',
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
            copy: quota.message || 'Faça upgrade para continuar usando este fluxo de importação.',
        };
    }

    if (state.previewStatus === 'loading_preview') {
        return {
            state: 'info',
            title: 'Montando preview',
            copy: 'Validando o arquivo e preparando a revisão final.',
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
            copy: state.previewErrors[0] || 'Revise o contexto, o formato e o arquivo antes de tentar novamente.',
        };
    }

    if (state.previewStatus === 'preview_ready') {
        if (!state.previewCanConfirm) {
            return {
                state: 'warning',
                title: 'Preview com bloqueios',
                copy: state.previewErrors[0] || 'O preview retornou bloqueios que precisam ser corrigidos antes da confirmação.',
            };
        }

        if (isContaOfxPreviewActive(state.selectedImportTarget, state.selectedSourceType)
            && (state.previewSummary.uncategorizedRows || 0) > 0) {
            return {
                state: 'ready',
                title: 'Preview pronto para revisar',
                copy: `${state.previewSummary.uncategorizedRows} linha(s) sem categoria. Você pode revisar agora ou confirmar assim mesmo.`,
            };
        }

        return {
            state: 'ready',
            title: 'Preview pronto para confirmar',
            copy: 'Revise as linhas e confirme a importação quando estiver tudo certo.',
        };
    }

    if (!hasContext) {
        return {
            state: 'warning',
            title: state.selectedImportTarget === 'cartao' ? 'Falta escolher o cartão' : 'Falta escolher a conta',
            copy: 'Defina o contexto antes de enviar o arquivo.',
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
            copy: 'Não foi possível identificar automaticamente se o arquivo é OFX ou CSV. Confira a extensão antes do preview.',
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
            copy: `O conteúdo do OFX parece ser de ${formatImportTargetLabel(detectedImportTarget)}, mas o alvo ativo está em ${formatImportTargetLabel(state.selectedImportTarget)}.`,
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
        copy: buildPreviewReadyCopy(
            state.selectedImportTarget,
            detectedSourceType,
            state.sourceAutoAdjustedToDetectedFile,
        ),
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
            text: 'Não foi possível detectar automaticamente se o arquivo é OFX ou CSV. Revise a extensão antes do preview.',
        };
    }

    if (
        detectedSourceType === 'ofx'
        && detectedImportTarget
        && detectedImportTarget !== state.selectedImportTarget
    ) {
        return {
            state: 'warning',
            text: `O conteúdo do OFX parece ser de ${formatImportTargetLabel(detectedImportTarget)}, mas o alvo selecionado está em ${formatImportTargetLabel(state.selectedImportTarget)}.`,
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