import { normalizeSourceType } from '../app.js';

export function parsePositiveInt(value) {
    const parsed = Number.parseInt(String(value || ''), 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}

export function normalizeBooleanSetting(value, fallback = true) {
    if (typeof value === 'boolean') {
        return value;
    }

    const normalized = String(value ?? '').trim().toLowerCase();
    if (!normalized) {
        return fallback;
    }

    return !['0', 'false', 'no', 'nao', 'não', 'off'].includes(normalized);
}

export function normalizeImportTarget(value, fallback = 'conta') {
    const normalized = String(value || '').trim().toLowerCase();
    return normalized === 'cartao' || normalized === 'conta' ? normalized : fallback;
}

export function isTruthyFlag(value) {
    return ['1', 'true', 'yes', 'on'].includes(String(value || '0').trim().toLowerCase());
}

export function normalizeMessages(messages) {
    if (!Array.isArray(messages)) {
        return [];
    }

    return messages
        .map((message) => String(message || '').trim())
        .filter((message) => message.length > 0);
}

export function normalizeProfileConfig(profile) {
    if (!profile || typeof profile !== 'object') {
        return null;
    }

    const options = profile.options && typeof profile.options === 'object' ? profile.options : {};
    const csvHasHeader = normalizeBooleanSetting(options.csv_has_header, true);
    const csvColumnMap = options.csv_column_map && typeof options.csv_column_map === 'object'
        ? options.csv_column_map
        : {};
    const csvStartRow = Number.parseInt(
        String(options.csv_start_row ?? (csvHasHeader ? 2 : 1)),
        10,
    );

    return {
        contaId: parsePositiveInt(profile.conta_id ?? null),
        sourceType: normalizeSourceType(profile.source_type || 'ofx'),
        csvMappingMode: String(options.csv_mapping_mode || 'auto').trim().toLowerCase() === 'manual'
            ? 'manual'
            : 'auto',
        csvHasHeader,
        csvStartRow: Number.isFinite(csvStartRow) && csvStartRow > 0 ? csvStartRow : (csvHasHeader ? 2 : 1),
        csvDelimiter: String(options.csv_delimiter || ';') || ';',
        csvDateFormat: String(options.csv_date_format || 'd/m/Y').trim() || 'd/m/Y',
        csvDecimalSeparator: String(options.csv_decimal_separator || ',').trim() === '.' ? '.' : ',',
        csvColumnMap: {
            tipo: String(csvColumnMap.tipo || '').trim().toUpperCase(),
            data: String(csvColumnMap.data || '').trim().toUpperCase(),
            descricao: String(csvColumnMap.descricao || '').trim().toUpperCase(),
            valor: String(csvColumnMap.valor || '').trim().toUpperCase(),
            categoria: String(csvColumnMap.categoria || '').trim().toUpperCase(),
            subcategoria: String(csvColumnMap.subcategoria || '').trim().toUpperCase(),
            observacao: String(csvColumnMap.observacao || '').trim().toUpperCase(),
            id_externo: String(csvColumnMap.id_externo || '').trim().toUpperCase(),
        },
    };
}

export function formatMappingModeLabel(mode) {
    return mode === 'manual' ? 'Manual' : 'Automático';
}

export function formatDelimiterLabel(delimiter) {
    const normalized = String(delimiter || '').trim();
    if (normalized === '\t') {
        return 'TAB';
    }

    return normalized || ';';
}

export function formatSourceTypeLabel(sourceType) {
    const normalized = normalizeSourceType(sourceType, '');
    return normalized ? normalized.toUpperCase() : 'Arquivo';
}

export function formatImportTargetLabel(importTarget) {
    return normalizeImportTarget(importTarget, '') === 'cartao' ? 'Cartão/fatura' : 'Conta';
}

export function detectSourceTypeFromFile(file) {
    if (!file) {
        return '';
    }

    const fileName = String(file.name || '').trim().toLowerCase();
    const mimeType = String(file.type || '').trim().toLowerCase();

    if (fileName.endsWith('.csv') || mimeType.includes('csv') || mimeType.includes('excel')) {
        return 'csv';
    }

    if (fileName.endsWith('.ofx') || fileName.endsWith('.qfx') || mimeType.includes('ofx') || mimeType.includes('qfx')) {
        return 'ofx';
    }

    return '';
}

export async function readTextFromFile(file) {
    if (!file) {
        return '';
    }

    if (typeof file.text === 'function') {
        return file.text();
    }

    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = () => reject(reader.error || new Error('Não foi possível ler o arquivo selecionado.'));
        reader.readAsText(file);
    });
}

export function detectImportTargetFromOfxContents(contents) {
    const normalizedContents = String(contents || '').trim();
    if (!normalizedContents) {
        return '';
    }

    const cardTags = ['CREDITCARDMSGSRSV1', 'CCSTMTTRNRS', 'CCSTMTRS', 'CCACCTFROM'];
    const accountTags = ['BANKMSGSRSV1', 'STMTTRNRS', 'STMTRS', 'BANKACCTFROM'];
    const hasTag = (tag) => new RegExp(`<\\s*${tag}\\b`, 'i').test(normalizedContents);

    const cardMatches = cardTags.filter(hasTag);
    const accountMatches = accountTags.filter(hasTag);

    if (cardMatches.length > 0 && accountMatches.length === 0) {
        return 'cartao';
    }

    if (accountMatches.length > 0 && cardMatches.length === 0) {
        return 'conta';
    }

    return '';
}

export function hasCategory(row) {
    return Boolean(row?.categoriaId);
}

export function formatAmount(value) {
    const number = Number(value);
    if (!Number.isFinite(number)) {
        return String(value ?? '-');
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(number);
}

export function countCategorizedRows(rows) {
    if (!Array.isArray(rows)) {
        return { categorizedRows: 0, uncategorizedRows: 0 };
    }

    const categorizedRows = rows.filter((row) => hasCategory(row)).length;
    return {
        categorizedRows,
        uncategorizedRows: Math.max(0, rows.length - categorizedRows),
    };
}

export function countSuggestedRows(rows) {
    if (!Array.isArray(rows)) {
        return { userRuleSuggestedRows: 0, globalRuleSuggestedRows: 0 };
    }

    return rows.reduce((summary, row) => {
        if (!parsePositiveInt(row?.categoriaSugeridaId ?? null)) {
            return summary;
        }

        const source = String(row?.categoriaSource || '').trim().toLowerCase();
        if (source === 'user_rule') {
            summary.userRuleSuggestedRows += 1;
        } else if (source === 'rule') {
            summary.globalRuleSuggestedRows += 1;
        }

        return summary;
    }, {
        userRuleSuggestedRows: 0,
        globalRuleSuggestedRows: 0,
    });
}

export function summarizeRows(rows) {
    return {
        ...countCategorizedRows(rows),
        ...countSuggestedRows(rows),
    };
}

export function createEmptyPreviewSummary(fileName = '') {
    return {
        fileName: String(fileName || ''),
        totalRows: 0,
        importedRows: 0,
        duplicateRows: 0,
        errorRows: 0,
        categorizedRows: 0,
        uncategorizedRows: 0,
        userRuleSuggestedRows: 0,
        globalRuleSuggestedRows: 0,
        categorizationApplied: false,
    };
}