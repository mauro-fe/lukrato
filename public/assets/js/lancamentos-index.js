document.addEventListener("DOMContentLoaded", () => {
    const tbody = document.getElementById("tbodyLancamentos");
    const filtroMes = document.getElementById("filtroMes");
    const filtroTipo = document.getElementById("filtroTipo");
    const filtroCategoria = document.getElementById("filtroCategoria");

    async function carregarCategorias() {
        try {
            const r = await fetch(`${window.BASE_URL}api/options`, { credentials: 'include' });
            if (!r.ok) throw new Error('Falha ao buscar categorias');
            const json = await r.json();

            filtroCategoria.innerHTML = '<option value="">Todas</option>';
            const mix = [...(json?.categorias?.receitas || []), ...(json?.categorias?.despesas || [])];
            mix.forEach(c => filtroCategoria.insertAdjacentHTML('beforeend', `<option value="${c.id}">${c.nome}</option>`));
        } catch (err) {
            console.error(err);
        }
    }

    async function carregarLancamentos() {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center">Carregando...</td></tr>`;
        try {
            const month = filtroMes.value;
            const r = await fetch(`${window.BASE_URL}api/dashboard/transactions?month=${encodeURIComponent(month)}&limit=500`, { credentials: 'include' });
            if (!r.ok) throw new Error('Falha ao buscar lançamentos');
            const data = await r.json();

            const filtrados = data.filter(t => {
                if (filtroTipo.value && t.tipo !== filtroTipo.value) return false;
                if (filtroCategoria.value && String(t.categoria?.id) !== filtroCategoria.value) return false;
                return true;
            });

            if (!filtrados.length) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center">Nenhum lançamento encontrado</td></tr>`;
                return;
            }

            tbody.innerHTML = "";
            filtrados.forEach(t => {
                const cor = t.tipo === 'receita' ? 'var(--verde)' : 'var(--vermelho)';
                const tr = document.createElement('tr');
                tr.innerHTML = `
          <td>${t.data}</td>
          <td>${t.tipo}</td>
          <td>${t.categoria ? t.categoria.nome : '—'}</td>
          <td>${t.descricao || '—'}</td>
          <td>${t.observacao || '—'}</td>
          <td class="text-right" style="font-weight:700;color:${cor}">R$ ${Number(t.valor).toFixed(2).replace('.', ',')}</td>
          <td class="text-center">
            <button class="btn-icon" data-edit="${t.id}" title="Editar"><i class="fas fa-edit"></i></button>
            <button class="btn-icon text-red" data-delete="${t.id}" title="Excluir"><i class="fas fa-trash"></i></button>
          </td>
        `;
                tbody.appendChild(tr);
            });
        } catch (err) {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-red">Erro ao carregar lançamentos</td></tr>`;
        }
    }

    // Ações do topo
    document.getElementById("btnNovaReceita")?.addEventListener("click", () => {
        if (typeof openModal === 'function') openModal("modalReceita");
    });
    document.getElementById("btnNovaDespesa")?.addEventListener("click", () => {
        if (typeof openModal === 'function') openModal("modalDespesa");
    });

    // Filtros
    document.getElementById("formFiltros")?.addEventListener("submit", (e) => {
        e.preventDefault();
        carregarLancamentos();
    });

    // Inicialização
    carregarCategorias().then(carregarLancamentos);
});
