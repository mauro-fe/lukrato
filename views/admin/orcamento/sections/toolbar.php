<!-- ==================== FILTROS ==================== -->
<section class="orc-toolbar surface-card" id="orcToolbarSection" data-aos="fade-up" data-aos-delay="120" <?= !$showOrcToolbar ? ' style="display:none;"' : '' ?>>
    <label class="orc-toolbar__search">
        <i data-lucide="search"></i>
        <input type="search" id="orcSearchInput" placeholder="Buscar categoria">
    </label>
    <div class="orc-toolbar__chips" id="orcFilterChips">
        <button type="button" class="orc-chip is-active" data-filter="all">Todos</button>
        <button type="button" class="orc-chip" data-filter="over">Estourados</button>
        <button type="button" class="orc-chip" data-filter="warn">Em alerta</button>
        <button type="button" class="orc-chip" data-filter="ok">Com folga</button>
        <button type="button" class="orc-chip" data-filter="rollover">Com rollover</button>
    </div>
    <label class="orc-toolbar__sort">
        <span>Ordenar</span>
        <select id="orcSortSelect" class="fin-select">
            <option value="usage">Maior uso</option>
            <option value="exceeded">Maior excedente</option>
            <option value="remaining">Maior folga</option>
            <option value="alpha">Nome</option>
        </select>
    </label>
</section>