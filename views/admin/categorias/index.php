<style>
    /* usa seus tokens do design system */
    .c-card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border, #1f29371a);
        border-radius: var(--radius-sm, 8px);
        padding: var(--spacing-4)
    }

    .c-muted {
        color: var(--color-text-muted)
    }

    .c-input,
    .c-select {
        width: 100%;
        padding: var(--spacing-3);
        border-radius: var(--radius-sm, 8px);
        border: 1px solid var(--glass-border);
        background: var(--glass-bg);
        color: var(--color-text)
    }

    .c-input::placeholder {
        color: var(--color-text)
    }



    .table-container {
        width: 100%;
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        overflow-x: auto;
    }

    .lukrato-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 720px;
        color: var(--branco);
        overflow: hidden;
    }

    .lukrato-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        text-align: left;
        font-size: var(--font-size-sm);
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: var(--cinza);
        background: rgba(255, 255, 255, 0.04);
        border-bottom: 1px solid var(--glass-border);
        padding: var(--spacing-4) var(--spacing-6);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }

    .lukrato-table tbody td {
        padding: var(--spacing-4) var(--spacing-6);
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        vertical-align: middle;
    }

    .lukrato-table tbody tr:nth-child(even) td {
        background: rgba(255, 255, 255, 0.02);
    }

    .lukrato-table tbody tr:hover td {
        background: rgba(255, 255, 255, 0.05);
        transform: translateX(2px);
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .lukrato-table tbody td[colspan] {
        color: var(--cinza);
        font-weight: 600;
        padding: var(--spacing-6);
    }

    .tag {
        display: inline-block;
        padding: .1rem .5rem;
        border-radius: 999px;
        text-transform: capitalize
    }

    .tag-receita {
        background: rgba(46, 204, 113, .18)
    }

    .tag-despesa {
        background: rgba(230, 126, 34, .18)
    }

    .tag-transferencia {
        background: rgba(52, 152, 219, .18)
    }

    .color-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: .4rem;
        vertical-align: middle
    }
</style>

<section class="container">
    <h3 class="c-title">Categorias</h3>
    <div class="c-card mt-4">
        <form id="formNova" class="row"
            style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:var(--spacing-3);margin:var(--spacing-4) 0;">
            <input class="c-input" name="nome" placeholder="Nome da categoria" required />
            <select class="c-select" name="tipo" required>
                <option value="receita">Receita</option>
                <option value="despesa">Despesa</option>
            </select>
            <button class="btn lk-select btn-primary" type="submit">Adicionar</button>
        </form>
    </div>
    <div class="py-4">
        <label class="c-muted">Filtrar por tipo:</label>
        <select id="filtroTipo" class="c-select" style="max-width:240px">
            <option value="">Todos</option>
            <option value="receita">Receita</option>
            <option value="despesa">Despesa</option>
        </select>
    </div>
    <section class="table-container">
        <table class="lukrato-table" id="tblCats">
            <!-- THEAD com a coluna "Cor" incluída -->
            <thead>
                <tr class="c-card">
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody></tbody>
        </table>
    </section>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    (() => {
        const metaBase = document.querySelector('meta[name="base-url"]')?.content || '';
        const deducedBase = (() => {
            const url = new URL(location.href);
            const parts = url.pathname.split('/').filter(Boolean);
            const iPublic = parts.lastIndexOf('public');
            if (iPublic >= 0) return `${url.origin}/${parts.slice(0, iPublic + 1).join('/')}/`;
            return `${url.origin}/`;
        })();
        const rawBase = (metaBase || deducedBase).replace(/\/?$/, '/');
        const BASES = [`${rawBase}api/`, `${rawBase}index.php/api/`, `/lukrato/public/api/`, `/api/`];
        const endpoint = (p, i = 0) => `${BASES[i]}${p}`;

        async function tryFetch(path, opts = {}) {
            let lastErr = 'API não encontrada';
            for (let i = 0; i < BASES.length; i++) {
                try {
                    const res = await fetch(endpoint(path, i), opts);
                    const ct = res.headers.get('content-type') || '';
                    if (res.ok && ct.includes('application/json')) {
                        return await res.json();
                    } else {
                        const text = await res.text();
                        lastErr = text?.slice(0, 300) || `${res.status} ${res.statusText}`;
                    }
                } catch (e) {
                    lastErr = e.message;
                }
            }
            throw new Error(lastErr);
        }

        const toast = (icon, title) => Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            icon,
            title
        });
        const alertError = (msg) => Swal.fire({
            icon: 'error',
            title: 'Ops...',
            text: msg || 'Algo deu errado.'
        });
        const confirmDel = (title = 'Remover?') =>
            Swal.fire({
                icon: 'warning',
                title,
                text: 'Esta ação não pode ser desfeita.',
                showCancelButton: true,
                confirmButtonText: 'Sim, remover',
                cancelButtonText: 'Cancelar'
            })
            .then(r => r.isConfirmed);

        const $ = (s, sc = document) => sc.querySelector(s);
        const tbody = $('#tblCats tbody');
        const filtro = $('#filtroTipo');
        const form = $('#formNova');

        function row(cat) {
            const tr = document.createElement('tr');
            tr.innerHTML = `
    <td style="padding:var(--spacing-3)"><strong>${cat.nome}</strong></td>
    <td style="padding:var(--spacing-3)"><span class="tag tag-${cat.tipo}">${cat.tipo}</span></td>
    <td style="padding:var(--spacing-3);text-align:right">
      <button class="lk-btn danger btn-del" data-del="${cat.id}" title="Excluir" aria-label="Excluir">
        <i class="fas fa-trash"></i>
      </button>
    </td>`;
            return tr;
        }


        async function load() {
            tbody.innerHTML = '';
            const q = filtro.value ? `?tipo=${encodeURIComponent(filtro.value)}` : '';
            try {
                const j = await tryFetch(`categorias${q}`);
                if (j.status !== 'success') return alertError(j.message || 'Falha ao carregar categorias');
                (j.data || []).forEach(c => tbody.appendChild(row(c)));
                if ((j.data || []).length === 0) {
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        `<td colspan="4" class="c-muted" style="padding:var(--spacing-4)">Nenhuma categoria encontrada.</td>`;
                    tbody.appendChild(tr);
                }
            } catch (e) {
                alertError(e.message);
            }
        }

        filtro.addEventListener('change', load);

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(form);

            const nome = (fd.get('nome') || '').toString().trim();
            const tipo = (fd.get('tipo') || '').toString().trim();
            if (nome.length < 2) return alertError('Informe um nome com pelo menos 2 caracteres.');
            if (!['receita', 'despesa', 'transferencia'].includes(tipo)) return alertError('Selecione um tipo válido.');

            try {
                const j = await tryFetch('categorias', {
                    method: 'POST',
                    body: fd
                });
                if (j.status !== 'success') {
                    const msg = j.message || (j.errors ? Object.values(j.errors).join('\n') : 'Falha ao criar categoria');
                    return alertError(msg);
                }

                form.reset();
                toast('success', 'Categoria adicionada!');
                // 🔁 Atualiza a listagem imediatamente
                await load();

                // 🔔 Notifica outras páginas/componentes (se estiverem ouvindo)
                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'categorias',
                        action: 'create',
                        id: j.data?.id ?? null
                    }
                }));
            } catch (e) {
                alertError(e.message);
            }
        });


        tbody.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-del[data-del]');
            if (!btn) return;
            const id = btn.getAttribute('data-del');

            if (!(await confirmDel('Deseja remover esta categoria?'))) return;

            try {
                let j;
                try {
                    j = await tryFetch(`categorias/${id}/delete`, {
                        method: 'POST'
                    });
                } catch (e1) {
                    const fd = new FormData();
                    fd.append('id', id);
                    j = await tryFetch('categorias/delete', {
                        method: 'POST',
                        body: fd
                    });
                }

                if (j.status !== 'success') return alertError(j.message || 'Falha ao excluir');
                toast('success', 'Categoria removida!');
                await load();
            } catch (e) {
                alertError(e.message);
            }
        });


        load();
    })();
</script>