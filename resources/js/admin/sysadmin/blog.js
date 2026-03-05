/**
 * ============================================================================
 * LUKRATO — SysAdmin Blog Page (Vite Module)
 * ============================================================================
 * CRUD de artigos do blog, upload de imagem, TinyMCE, filtros e paginação.
 * ============================================================================
 */

const BASE_URL = (() => {
    const meta = document.querySelector('meta[name="base-url"]')?.content || '';
    return meta.replace(/\/?$/, '/');
})();

function getCsrfToken() {
    if (window.CSRFManager) {
        if (typeof window.CSRFManager.getToken === 'function') {
            const token = window.CSRFManager.getToken();
            if (token) return token;
        }
        if (window.CSRFManager.token) return window.CSRFManager.token;
    }
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (metaToken) return metaToken;
    console.warn('CSRF Token não encontrado!');
    return '';
}

// ─── State ──────────────────────────────────────────────────
let posts = [];
let categorias = [];
let currentPage = 1;
let totalPages = 1;
let perPage = 15;
let editingPostId = null;
let filterDebounce = null;
let tinyMCEInitialized = false;

function showConteudoFallback(message) {
    const textarea = document.getElementById('conteudo');
    if (!textarea) return;

    // Remove editor wrappers in inconsistent states and force native textarea fallback.
    const wrappers = document.querySelectorAll('.tox-tinymce');
    wrappers.forEach((el) => el.remove());

    textarea.style.display = 'block';
    textarea.style.visibility = 'visible';
    textarea.style.opacity = '1';
    textarea.style.minHeight = '260px';
    if (!textarea.getAttribute('rows')) {
        textarea.setAttribute('rows', '12');
    }

    if (message && window.LKFeedback?.error) {
        LKFeedback.error(message);
    }
}

// ─── Helpers ────────────────────────────────────────────────
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function slugify(text) {
    return text
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

// ─── Init ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadCategorias();
    loadPosts();
    setupEventListeners();
    setupResumoCounter();
});

// ─── Event Delegation ───────────────────────────────────────
function setupEventListeners() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;

        switch (action) {
            case 'abrirModalCriarPost':
                openModal('create');
                break;
            case 'fecharModalPost':
                closeModal();
                break;
            case 'editarPost':
                editPost(parseInt(btn.dataset.id));
                break;
            case 'excluirPost':
                deletePost(parseInt(btn.dataset.id));
                break;
            case 'verPost':
                window.open(btn.dataset.url, '_blank');
                break;
            case 'removerImagem':
                removeImage();
                break;
            case 'goToPage':
                currentPage = parseInt(btn.dataset.page);
                loadPosts();
                break;
            case 'filterPosts':
                handleFilter();
                break;
            case 'generateSlug':
                // Handled via input event below
                break;
        }
    });

    // Change events para filtros
    document.getElementById('filterCategoria')?.addEventListener('change', handleFilter);
    document.getElementById('filterStatus')?.addEventListener('change', handleFilter);

    // Input event para busca com debounce
    document.getElementById('filterSearch')?.addEventListener('input', () => {
        clearTimeout(filterDebounce);
        filterDebounce = setTimeout(handleFilter, 400);
    });

    // Auto-gerar slug do título
    document.getElementById('titulo')?.addEventListener('input', (e) => {
        const slugField = document.getElementById('slug');
        if (slugField && !editingPostId) {
            slugField.value = slugify(e.target.value);
        }
    });

    // Upload de imagem
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('imagemCapa');

    if (uploadArea && fileInput) {
        uploadArea.addEventListener('click', () => fileInput.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                uploadImage(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                uploadImage(fileInput.files[0]);
            }
        });
    }

    // Form submit
    document.getElementById('formPost')?.addEventListener('submit', (e) => {
        e.preventDefault();
        savePost();
    });
}

function setupResumoCounter() {
    const resumo = document.getElementById('resumo');
    const counter = document.getElementById('resumoCount');
    if (resumo && counter) {
        resumo.addEventListener('input', () => {
            counter.textContent = resumo.value.length;
        });
    }
}

// ─── API Requests ───────────────────────────────────────────
async function apiRequest(url, options = {}) {
    const defaultHeaders = {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken(),
    };

    const response = await fetch(url, {
        ...options,
        headers: { ...defaultHeaders, ...options.headers },
        credentials: 'include',
    });

    // Handle CSRF refresh
    const newToken = response.headers.get('X-CSRF-Token');
    if (newToken && window.CSRFManager && typeof window.CSRFManager.update === 'function') {
        window.CSRFManager.update(newToken);
    }

    return response.json();
}

// ─── Load Categorias ────────────────────────────────────────
async function loadCategorias() {
    try {
        const data = await apiRequest(`${BASE_URL}api/sysadmin/blog/categorias`);
        if (data.status === 'success') {
            categorias = data.data.categorias;
            populateCategoriaSelects();
        }
    } catch (error) {
        console.error('Erro ao carregar categorias:', error);
    }
}

function populateCategoriaSelects() {
    const filterSelect = document.getElementById('filterCategoria');
    const formSelect = document.getElementById('blog_categoria_id');

    const options = categorias
        .map((c) => `<option value="${c.id}">${escapeHtml(c.nome)}</option>`)
        .join('');

    if (filterSelect) {
        filterSelect.innerHTML = `<option value="">Todas as categorias</option>${options}`;
    }
    if (formSelect) {
        formSelect.innerHTML = `<option value="">Sem categoria</option>${options}`;
    }
}

// ─── Load Posts ─────────────────────────────────────────────
async function loadPosts() {
    const loading = document.getElementById('loading');
    const table = document.getElementById('blogTable');
    const empty = document.getElementById('emptyState');

    loading.style.display = 'flex';
    table.style.display = 'none';
    empty.style.display = 'none';

    try {
        const search = document.getElementById('filterSearch')?.value || '';
        const status = document.getElementById('filterStatus')?.value || '';
        const catId = document.getElementById('filterCategoria')?.value || '';

        const params = new URLSearchParams({
            page: currentPage,
            per_page: perPage,
        });
        if (search) params.set('search', search);
        if (status) params.set('status', status);
        if (catId) params.set('blog_categoria_id', catId);

        const data = await apiRequest(`${BASE_URL}api/sysadmin/blog/posts?${params}`);

        if (data.status === 'success') {
            posts = data.data.items;
            totalPages = Math.ceil(data.data.total / data.data.perPage);
            currentPage = data.data.page;

            updateStats(data.data.stats);
            renderTable();
            renderPagination(data.data.total, data.data.page, data.data.perPage);
        } else {
            throw new Error(data.message || 'Erro ao carregar posts');
        }
    } catch (error) {
        console.error('Erro ao carregar posts:', error);
        LKFeedback.error(error.message);
    } finally {
        loading.style.display = 'none';
    }
}

function handleFilter() {
    currentPage = 1;
    loadPosts();
}

// ─── Update Stats ───────────────────────────────────────────
function updateStats(stats) {
    if (!stats) return;
    const container = document.getElementById('blogStats');
    container.style.display = 'grid';
    document.getElementById('statTotal').textContent = stats.total || 0;
    document.getElementById('statPublished').textContent = stats.published || 0;
    document.getElementById('statDraft').textContent = stats.draft || 0;
}

// ─── Render Table ───────────────────────────────────────────
function renderTable() {
    const tbody = document.getElementById('blogTableBody');
    const table = document.getElementById('blogTable');
    const empty = document.getElementById('emptyState');

    if (posts.length === 0) {
        table.style.display = 'none';
        empty.style.display = 'block';
        return;
    }

    table.style.display = 'table';
    empty.style.display = 'none';

    tbody.innerHTML = posts
        .map((post) => {
            const thumb = post.imagem_capa_url
                ? `<img src="${escapeHtml(post.imagem_capa_url)}" alt="" class="post-thumb">`
                : `<div class="post-thumb-empty"><i data-lucide="image"></i></div>`;

            const statusBadge =
                post.status === 'published'
                    ? `<span class="badge badge-published"><i data-lucide="globe" style="width:12px;height:12px;"></i> Publicado</span>`
                    : `<span class="badge badge-draft"><i data-lucide="file-edit" style="width:12px;height:12px;"></i> Rascunho</span>`;

            const date = post.status === 'published' && post.published_at
                ? post.published_at
                : post.created_at || '—';

            const viewBtn = post.status === 'published'
                ? `<button class="btn-action" data-action="verPost" data-url="${escapeHtml(post.url)}" title="Ver no site"><i data-lucide="external-link"></i></button>`
                : '';

            return `<tr>
                <td class="col-img">${thumb}</td>
                <td class="col-titulo">
                    <div class="post-title-cell">
                        <span class="post-name">${escapeHtml(post.titulo)}</span>
                        <span class="post-slug">/${escapeHtml(post.slug)}</span>
                    </div>
                </td>
                <td class="col-cat">${escapeHtml(post.categoria_nome || '—')}</td>
                <td class="col-status">${statusBadge}</td>
                <td class="col-data">${escapeHtml(date)}</td>
                <td class="col-acoes">
                    <div class="action-btns">
                        <button class="btn-action" data-action="editarPost" data-id="${post.id}" title="Editar"><i data-lucide="pencil"></i></button>
                        ${viewBtn}
                        <button class="btn-action danger" data-action="excluirPost" data-id="${post.id}" title="Excluir"><i data-lucide="trash-2"></i></button>
                    </div>
                </td>
            </tr>`;
        })
        .join('');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// ─── Pagination ─────────────────────────────────────────────
function renderPagination(total, page, perPage) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;

    const totalPages = Math.ceil(total / perPage);
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    const startItem = (page - 1) * perPage + 1;
    const endItem = Math.min(page * perPage, total);

    let btns = '';
    // Prev
    btns += `<button ${page <= 1 ? 'disabled' : ''} data-action="goToPage" data-page="${page - 1}"><i data-lucide="chevron-left" style="width:14px;height:14px;"></i></button>`;

    // Pages
    const startPage = Math.max(1, page - 2);
    const endPage = Math.min(totalPages, page + 2);

    if (startPage > 1) {
        btns += `<button data-action="goToPage" data-page="1">1</button>`;
        if (startPage > 2) btns += `<button disabled>...</button>`;
    }

    for (let i = startPage; i <= endPage; i++) {
        btns += `<button ${i === page ? 'class="active"' : ''} data-action="goToPage" data-page="${i}">${i}</button>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) btns += `<button disabled>...</button>`;
        btns += `<button data-action="goToPage" data-page="${totalPages}">${totalPages}</button>`;
    }

    // Next
    btns += `<button ${page >= totalPages ? 'disabled' : ''} data-action="goToPage" data-page="${page + 1}"><i data-lucide="chevron-right" style="width:14px;height:14px;"></i></button>`;

    container.innerHTML = `
        <div class="pagination-info">Mostrando ${startItem} – ${endItem} de ${total}</div>
        <div class="pagination-controls">${btns}</div>
    `;

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// ─── Modal ──────────────────────────────────────────────────
function openModal(mode, post = null) {
    const modal = document.getElementById('modalPost');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('formPost');

    form.reset();
    editingPostId = null;

    // Reset image
    document.getElementById('uploadPreview').style.display = 'none';
    document.getElementById('uploadPlaceholder').style.display = 'flex';
    document.getElementById('imagemCapaPath').value = '';
    document.getElementById('resumoCount').textContent = '0';

    if (mode === 'edit' && post) {
        title.textContent = 'Editar Artigo';
        editingPostId = post.id;
        document.getElementById('postId').value = post.id;
        document.getElementById('titulo').value = post.titulo || '';
        document.getElementById('slug').value = post.slug || '';
        document.getElementById('blog_categoria_id').value = post.blog_categoria_id || '';
        document.getElementById('status').value = post.status || 'draft';
        document.getElementById('resumo').value = post.resumo || '';
        document.getElementById('meta_title').value = post.meta_title || '';
        document.getElementById('meta_description').value = post.meta_description || '';
        document.getElementById('resumoCount').textContent = (post.resumo || '').length;

        // Image
        if (post.imagem_capa) {
            document.getElementById('imagemCapaPath').value = post.imagem_capa;
            const imgUrl = post.imagem_capa_url || (BASE_URL + post.imagem_capa);
            document.getElementById('previewImg').src = imgUrl;
            document.getElementById('uploadPreview').style.display = 'block';
            document.getElementById('uploadPlaceholder').style.display = 'none';
        }

        initTinyMCE(post.conteudo || '');
    } else {
        title.textContent = 'Novo Artigo';
        initTinyMCE('');
    }

    modal.classList.add('active');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeModal() {
    const modal = document.getElementById('modalPost');
    modal.classList.remove('active');
    editingPostId = null;
    destroyTinyMCE();
}

// ─── TinyMCE ────────────────────────────────────────────────
function initTinyMCE(initialContent = '') {
    const textarea = document.getElementById('conteudo');
    if (!textarea) return;

    textarea.value = initialContent || '';

    if (!window.tinymce) {
        console.error('TinyMCE não carregado (window.tinymce ausente).');
        showConteudoFallback('Editor de texto indisponível no momento. Recarregue a página.');
        return;
    }

    const existing = tinymce.get('conteudo');
    if (tinyMCEInitialized && existing) {
        existing.setContent(initialContent || '');
        return;
    }

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

    try {
        tinymce.init({
            selector: '#conteudo',
            height: 400,
            skin: isDark ? 'oxide-dark' : 'oxide',
            content_css: isDark ? 'dark' : 'default',
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
            toolbar: 'undo redo | styles | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code fullscreen | removeformat help',
            menubar: 'file edit view insert format tools table help',
            branding: false,
            promotion: false,
            statusbar: true,
            resize: true,
            content_style: `
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    font-size: 16px;
                    line-height: 1.7;
                    color: ${isDark ? '#e0e0e0' : '#333'};
                    max-width: 100%;
                    padding: 16px;
                }
                h1, h2, h3, h4 { margin-top: 1.5em; margin-bottom: 0.5em; }
                p { margin-bottom: 1em; }
                img { max-width: 100%; height: auto; border-radius: 8px; }
                blockquote { border-left: 4px solid #e67e22; padding-left: 16px; margin: 1em 0; color: #666; }
                code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
                pre { background: #f4f4f4; padding: 16px; border-radius: 8px; overflow-x: auto; }
            `,
            setup: (editor) => {
                editor.on('init', () => {
                    tinyMCEInitialized = true;
                    editor.setContent(initialContent || '');
                });
            },
        });

        setTimeout(() => {
            const editor = tinymce.get('conteudo');
            if (!editor) {
                tinyMCEInitialized = false;
                showConteudoFallback('Editor avançado indisponível. Use o campo de texto simples.');
            }
        }, 1200);
    } catch (error) {
        console.error('Falha ao inicializar TinyMCE:', error);
        tinyMCEInitialized = false;
        showConteudoFallback('Falha ao carregar editor avançado. Use o campo de texto simples.');
    }
}

function destroyTinyMCE() {
    if (!window.tinymce) {
        tinyMCEInitialized = false;
        return;
    }

    const editor = tinymce.get('conteudo');
    if (editor) {
        editor.remove();
    }
    tinyMCEInitialized = false;
}

// ─── Image Upload ───────────────────────────────────────────
async function uploadImage(file) {
    // Validate
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        LKFeedback.error('Tipo de arquivo não permitido. Use JPEG, PNG ou WebP.');
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        LKFeedback.error('A imagem não pode ter mais de 2MB.');
        return;
    }

    const formData = new FormData();
    formData.append('imagem', file);

    try {
        const response = await fetch(`${BASE_URL}api/sysadmin/blog/upload`, {
            method: 'POST',
            headers: { 'X-CSRF-Token': getCsrfToken() },
            credentials: 'include',
            body: formData,
        });

        const data = await response.json();

        if (data.status === 'success') {
            document.getElementById('imagemCapaPath').value = data.data.path;
            document.getElementById('previewImg').src = data.data.url;
            document.getElementById('uploadPreview').style.display = 'block';
            document.getElementById('uploadPlaceholder').style.display = 'none';
            LKFeedback.success('Imagem enviada!');
        } else {
            throw new Error(data.message || 'Erro no upload');
        }
    } catch (error) {
        console.error('Erro no upload:', error);
        LKFeedback.error(error.message);
    }
}

function removeImage() {
    document.getElementById('imagemCapaPath').value = '';
    document.getElementById('previewImg').src = '';
    document.getElementById('uploadPreview').style.display = 'none';
    document.getElementById('uploadPlaceholder').style.display = 'flex';
    document.getElementById('imagemCapa').value = '';
}

// ─── Save Post ──────────────────────────────────────────────
async function savePost() {
    const editor = window.tinymce ? tinymce.get('conteudo') : null;
    const conteudo = editor
        ? editor.getContent()
        : (document.getElementById('conteudo')?.value || '');

    const payload = {
        titulo: document.getElementById('titulo').value.trim(),
        slug: document.getElementById('slug').value.trim(),
        blog_categoria_id: document.getElementById('blog_categoria_id').value || null,
        status: document.getElementById('status').value,
        resumo: document.getElementById('resumo').value.trim(),
        conteudo: conteudo,
        imagem_capa: document.getElementById('imagemCapaPath').value || null,
        meta_title: document.getElementById('meta_title').value.trim(),
        meta_description: document.getElementById('meta_description').value.trim(),
    };

    // Basic validation
    if (!payload.titulo) {
        LKFeedback.error('O título é obrigatório.');
        return;
    }
    if (!conteudo || conteudo.replace(/<[^>]*>/g, '').trim() === '') {
        LKFeedback.error('O conteúdo é obrigatório.');
        return;
    }

    const isEdit = !!editingPostId;
    const url = isEdit
        ? `${BASE_URL}api/sysadmin/blog/posts/${editingPostId}`
        : `${BASE_URL}api/sysadmin/blog/posts`;
    const method = isEdit ? 'PUT' : 'POST';

    try {
        const data = await apiRequest(url, {
            method,
            body: JSON.stringify(payload),
        });

        if (data.status === 'success') {
            LKFeedback.success(data.data?.message || data.message || 'Salvo com sucesso!');
            closeModal();
            loadPosts();
        } else {
            // Validation errors
            if (data.errors) {
                const msgs = Object.values(data.errors).join('\n');
                LKFeedback.error(msgs);
            } else {
                throw new Error(data.message || 'Erro ao salvar');
            }
        }
    } catch (error) {
        console.error('Erro ao salvar post:', error);
        LKFeedback.error(error.message);
    }
}

// ─── Edit Post ──────────────────────────────────────────────
async function editPost(id) {
    try {
        const data = await apiRequest(`${BASE_URL}api/sysadmin/blog/posts/${id}`);

        if (data.status === 'success') {
            openModal('edit', data.data.post);
        } else {
            throw new Error(data.message || 'Erro ao carregar post');
        }
    } catch (error) {
        console.error('Erro ao editar post:', error);
        LKFeedback.error(error.message);
    }
}

// ─── Delete Post ────────────────────────────────────────────
async function deletePost(id) {
    const post = posts.find((p) => p.id === id);
    const titulo = post ? post.titulo : `#${id}`;

    const confirmed = await LKFeedback.confirm(
        `Tem certeza que deseja excluir o artigo "${escapeHtml(titulo)}"?`,
        'Excluir Artigo',
        'Sim, excluir',
        'Cancelar'
    );

    if (!confirmed) return;

    try {
        const data = await apiRequest(`${BASE_URL}api/sysadmin/blog/posts/${id}`, {
            method: 'DELETE',
        });

        if (data.status === 'success') {
            LKFeedback.success(data.data?.message || 'Artigo excluído!');
            loadPosts();
        } else {
            throw new Error(data.message || 'Erro ao excluir');
        }
    } catch (error) {
        console.error('Erro ao excluir post:', error);
        LKFeedback.error(error.message);
    }
}
