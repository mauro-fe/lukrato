<section class="c-page">
    <h3 class="c-title">Categorias</h3>
    <div class="c-card mt-4">
        <h6>Crie sua categoria</h6>

        <form id="formNova" class="c-form">
            <?= csrf_input('default') ?>
            <input class="c-input" name="nome" placeholder="Nome da categoria" required />
            <select class="c-select" name="tipo" required>
                <option value="receita">Receita</option>
                <option value="despesa">Despesa</option>
            </select>
            <button class="btn lk-select btn-primary" type="submit">Adicionar</button>
        </form>
    </div>
    <div class="c-filter">
        <label class="c-muted">Filtrar por tipo:</label>
        <select id="filtroTipo" class="c-select">
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
    // ========= BASE E API =========
    function getCsrfToken() {
        return document.querySelector('input[name="_token"]')?.value ||
            document.querySelector('meta[name="csrf-token"]')?.content ||
            '';
    }

    // BASE da API (usa sua meta)
    const BASE = (document.querySelector('meta[name="base-url"]')?.content || '/').replace(/\/?$/, '/');
    const API = `${BASE}api/`;

    // ========= CSRF =========
    const getCsrf = () =>
        document.querySelector('input[name="_token"]')?.value ||
        document.querySelector('meta[name="csrf-token"]')?.content || '';

    // ========= FETCH HELPER (injeta CSRF e cookies) =========
    async function tryFetch(path, opts = {}) {
        const url = `${API}${path}`.replace(/([^:])\/{2,}/g, '$1/');
        opts.credentials = 'include';

        const method = (opts.method || 'GET').toUpperCase();
        const isBodyless = method === 'GET' || method === 'HEAD';
        const hasFormData = (opts.body && typeof FormData !== 'undefined' && opts.body instanceof FormData);

        if (!isBodyless && !hasFormData) {
            const token = getCsrf();
            const payload = opts.body && typeof opts.body === 'object' ? opts.body : {};
            opts.headers = Object.assign({
                'Content-Type': 'application/json',
                'X-CSRF-Token': token
            }, opts.headers || {});
            opts.body = JSON.stringify({
                ...payload,
                _token: token
            });
        } else if (!isBodyless && hasFormData) {
            const token = getCsrf();
            if (token && !opts.body.has('_token')) opts.body.append('_token', token);
            opts.headers = Object.assign({
                'X-CSRF-Token': token
            }, opts.headers || {});
        }

        const res = await fetch(url, opts);
        const ct = res.headers.get('content-type') || '';
        const data = ct.includes('application/json') ? await res.json() : {
            status: 'error',
            message: await res.text()
        };

        if (!res.ok) {
            if (res.status === 403) throw new Error(
                'Acesso negado (403). Possível falha de CSRF ou sessão expirada.');
            if (res.status === 404) throw new Error('Endpoint não encontrado (404): ' + url);
            throw new Error(data?.message || `Erro ${res.status}`);
        }
        return data;
    }

    // ========= UI HELPERS =========
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
        }).then(r => r.isConfirmed);

    // ========= DOM =========
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
        if (!['receita', 'despesa', 'transferencia'].includes(tipo)) return alertError(
            'Selecione um tipo válido.');

        try {
            const j = await tryFetch('categorias', {
                method: 'POST',
                body: fd
            });
            if (j.status !== 'success') {
                const msg = j.message || (j.errors ? Object.values(j.errors).join('\n') :
                    'Falha ao criar categoria');
                return alertError(msg);
            }
            form.reset();
            toast('success', 'Categoria adicionada!');
            await load();
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
            // pega token da página
            const token =
                document.querySelector('input[name="_token"]')?.value ||
                document.querySelector('input[name="csrf_token"]')?.value ||
                document.querySelector('meta[name="csrf-token"]')?.content || '';

            const fd = new FormData();
            // envie com AMBOS os nomes — garante compatibilidade
            fd.append('_token', token);
            fd.append('csrf_token', token);

            const res = await fetch(`${API}categorias/${id}/delete`, {
                method: 'POST',
                body: fd,
                credentials: 'include',
                // use exatamente este header em CAIXA ALTA
                headers: {
                    'X-CSRF-TOKEN': token
                }
            });

            const ct = res.headers.get('content-type') || '';
            const j = ct.includes('application/json') ? await res.json() : {
                status: 'error',
                message: await res.text()
            };

            if (!res.ok || j.status !== 'success') throw new Error(j.message || `Erro ${res.status}`);

            toast('success', 'Categoria removida!');
            await load();
        } catch (err) {
            alertError(err.message);
        }
    });

    load();
})();
</script>