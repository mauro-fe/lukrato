function encodeEndpointSegment(value) {
    return encodeURIComponent(String(value));
}

export function resolveInstitutionsEndpoint() {
    return 'api/v1/instituicoes';
}

export function resolveAccountsInstitutionsEndpoint() {
    return 'api/v1/contas/instituicoes';
}

export function resolveAccountsEndpoint() {
    return 'api/v1/contas';
}

export function resolveAccountEndpoint(accountId) {
    return `${resolveAccountsEndpoint()}/${encodeEndpointSegment(accountId)}`;
}

export function resolveAccountArchiveEndpoint(accountId) {
    return `${resolveAccountEndpoint(accountId)}/archive`;
}

export function resolveAccountRestoreEndpoint(accountId) {
    return `${resolveAccountEndpoint(accountId)}/restore`;
}

export function resolveAccountDeleteEndpoint(accountId) {
    return `${resolveAccountEndpoint(accountId)}/delete`;
}

export function resolveCategoriesEndpoint() {
    return 'api/v1/categorias';
}

export function resolveCategoryEndpoint(categoryId) {
    return `${resolveCategoriesEndpoint()}/${encodeEndpointSegment(categoryId)}`;
}

export function resolveCategoryReorderEndpoint() {
    return `${resolveCategoriesEndpoint()}/reorder`;
}

export function resolveCategorySubcategoriesEndpoint(categoryId) {
    return `api/v1/categorias/${encodeURIComponent(String(categoryId))}/subcategorias`;
}

export function resolveSubcategoriesGroupedEndpoint() {
    return 'api/v1/subcategorias/grouped';
}

export function resolveSubcategoryEndpoint(subcategoryId) {
    return `api/v1/subcategorias/${encodeEndpointSegment(subcategoryId)}`;
}

export function resolveCardsEndpoint() {
    return 'api/v1/cartoes';
}

export function resolveCardsSummaryEndpoint() {
    return `${resolveCardsEndpoint()}/resumo`;
}

export function resolveCardsAlertsEndpoint() {
    return `${resolveCardsEndpoint()}/alertas`;
}

export function resolveCardEndpoint(cardId) {
    return `${resolveCardsEndpoint()}/${encodeEndpointSegment(cardId)}`;
}

export function resolveCardArchiveEndpoint(cardId) {
    return `${resolveCardEndpoint(cardId)}/archive`;
}

export function resolveCardRestoreEndpoint(cardId) {
    return `${resolveCardEndpoint(cardId)}/restore`;
}

export function resolveCardDeleteEndpoint(cardId) {
    return `${resolveCardEndpoint(cardId)}/delete`;
}

export function resolveFinanceSummaryEndpoint() {
    return 'api/v1/financas/resumo';
}

export function resolveFinanceGoalsEndpoint() {
    return 'api/v1/financas/metas';
}

export function resolveFinanceGoalEndpoint(goalId) {
    return `${resolveFinanceGoalsEndpoint()}/${encodeEndpointSegment(goalId)}`;
}

export function resolveFinanceGoalContributionEndpoint(goalId) {
    return `${resolveFinanceGoalEndpoint(goalId)}/aporte`;
}

export function resolveFinanceGoalTemplatesEndpoint() {
    return 'api/v1/financas/metas/templates';
}

export function resolveFinanceBudgetsEndpoint() {
    return 'api/v1/financas/orcamentos';
}

export function resolveFinanceBudgetEndpoint(budgetId) {
    return `${resolveFinanceBudgetsEndpoint()}/${encodeEndpointSegment(budgetId)}`;
}

export function resolveFinanceBudgetSuggestionsEndpoint() {
    return 'api/v1/financas/orcamentos/sugestoes';
}

export function resolveFinanceBudgetApplySuggestionsEndpoint() {
    return `${resolveFinanceBudgetsEndpoint()}/aplicar-sugestoes`;
}

export function resolveFinanceBudgetCopyMonthEndpoint() {
    return `${resolveFinanceBudgetsEndpoint()}/copiar-mes`;
}

export function resolveFinanceInsightsEndpoint() {
    return 'api/v1/financas/insights';
}