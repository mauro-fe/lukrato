<!-- ==================== CARD DE NOVA CATEGORIA ==================== -->
<div class="create-card-wrapper" id="categoriasCreateCard">
    <div class="modern-card create-card surface-card surface-card--interactive surface-card--clip">
        <div class="create-card-content">
            <!-- Lado esquerdo: ícone preview -->
            <div class="create-icon-area">
                <div class="create-icon-ring" id="iconPreviewRing">
                    <div class="create-icon-inner">
                        <i data-lucide="tag" class="create-main-icon" id="iconPreview"></i>
                    </div>
                </div>
                <button type="button" class="icon-picker-trigger" id="btnIconPicker" title="Escolher ícone">
                    <i data-lucide="palette"></i>
                    <span>Escolher ícone</span>
                </button>
            </div>

            <!-- Lado direito: formulário inline -->
            <div class="create-form-area">
                <div class="create-form-header">
                    <h3 class="create-form-title">Criar Categoria</h3>
                    <p class="create-form-subtitle">Organize suas finanças com categorias personalizadas</p>
                </div>

                <form id="formNova" class="create-form">
                    <?= csrf_input('default') ?>
                    <input type="hidden" name="icone" id="catIcone" value="">

                    <div class="create-form-fields">
                        <div class="create-field">
                            <div class="modern-input-wrapper">
                                <i data-lucide="tag" class="field-icon"></i>
                                <input id="catNome" class="modern-input create-input" name="nome"
                                    placeholder="Nome da categoria..." required minlength="2" maxlength="100"
                                    aria-label="Nome da categoria" autocomplete="off" />
                            </div>
                        </div>

                        <div class="create-field">
                            <div class="type-toggle-group" role="radiogroup" aria-label="Tipo de categoria">
                                <input type="radio" name="tipo" value="receita" id="tipoReceita" checked
                                    class="type-toggle-input">
                                <label for="tipoReceita" class="type-toggle-pill receita">
                                    <i data-lucide="trending-up"></i>
                                    <span>Receita</span>
                                </label>

                                <input type="radio" name="tipo" value="despesa" id="tipoDespesa"
                                    class="type-toggle-input">
                                <label for="tipoDespesa" class="type-toggle-pill despesa">
                                    <i data-lucide="trending-down"></i>
                                    <span>Despesa</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sugestões rápidas -->
                    <div class="suggestions-section" id="suggestionsSection">
                        <p class="suggestions-label">
                            <i data-lucide="sparkles"></i>
                            Sugestões rápidas
                        </p>
                        <div class="suggestions-chips" id="suggestionsChips">
                            <!-- Populado via JS baseado no tipo selecionado -->
                        </div>
                    </div>

                    <button class="create-submit-btn" type="submit">
                        <i data-lucide="plus" class="create-btn-icon"></i>
                        <span class="create-btn-text">Adicionar categoria</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Icon Picker Drawer -->
        <div class="icon-picker-drawer" id="iconPickerDrawer">
            <div class="icon-picker-header">
                <h4 class="icon-picker-title">
                    <i data-lucide="palette"></i>
                    Escolher Ícone
                </h4>
                <button type="button" class="icon-picker-close" id="btnCloseIconPicker">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="icon-picker-search">
                <i data-lucide="search" class="icon-search-icon"></i>
                <input type="text" class="icon-search-input" id="iconSearchInput" placeholder="Buscar ícone..."
                    autocomplete="off" />
            </div>
            <div class="icon-picker-grid" id="iconPickerGrid">
                <!-- Populado via JS -->
            </div>
        </div>
    </div>
</div>