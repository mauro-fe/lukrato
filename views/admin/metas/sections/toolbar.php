<!-- ==================== FILTROS ==================== -->
<section class="met-toolbar surface-card" id="metToolbarSection" data-aos="fade-up" data-aos-delay="120">
    <label class="met-toolbar__search">
        <i data-lucide="search"></i>
        <input type="search" id="metSearchInput" placeholder="Buscar meta">
    </label>
    <div class="met-toolbar__chips" id="metFilterChips">
        <button type="button" class="met-chip is-active" data-filter="all">Todas</button>
        <button type="button" class="met-chip" data-filter="ativa">Ativas</button>
        <button type="button" class="met-chip" data-filter="atrasada">Atrasadas</button>
        <button type="button" class="met-chip" data-filter="concluida">Concluidas</button>
    </div>
    <label class="met-toolbar__sort">
        <span>Ordenar</span>
        <select id="metSortSelect" class="fin-select">
            <option value="deadline">Prazo mais proximo</option>
            <option value="progress">Maior progresso</option>
            <option value="remaining">Maior valor restante</option>
            <option value="priority">Prioridade</option>
            <option value="title">Nome</option>
        </select>
    </label>
</section>