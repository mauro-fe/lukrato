<!-- Modal Criar/Editar Post -->
<div id="modalPost" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2>
                <i data-lucide="file-edit"></i>
                <span id="modalTitle">Novo Artigo</span>
            </h2>
            <button class="btn-close" data-action="fecharModalPost">&times;</button>
        </div>
        <form id="formPost">
            <input type="hidden" id="postId" name="id">
            <div class="modal-body">
                <!-- Row: Título + Slug -->
                <div class="form-row">
                    <div class="form-group flex-2">
                        <label for="titulo">Título *</label>
                        <input type="text" id="titulo" name="titulo" required placeholder="Ex: Como organizar seu dinheiro"
                            data-action="generateSlug">
                    </div>
                    <div class="form-group flex-1">
                        <label for="slug">Slug (URL)</label>
                        <input type="text" id="slug" name="slug" placeholder="auto-gerado-do-titulo">
                        <small>Deixe vazio para gerar automaticamente</small>
                    </div>
                </div>

                <!-- Row: Categoria + Status -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="blog_categoria_id">Categoria</label>
                        <select id="blog_categoria_id" name="blog_categoria_id">
                            <option value="">Sem categoria</option>
                            <!-- Preenchido via JS -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status">
                            <option value="draft">Rascunho</option>
                            <option value="published">Publicado</option>
                        </select>
                    </div>
                </div>

                <!-- Resumo -->
                <div class="form-group">
                    <label for="resumo">Resumo</label>
                    <textarea id="resumo" name="resumo" rows="3"
                        placeholder="Breve descrição do artigo (exibida na listagem)" maxlength="500"></textarea>
                    <small><span id="resumoCount">0</span>/500 caracteres</small>
                </div>

                <!-- Imagem de Capa -->
                <div class="form-group">
                    <label>Imagem de Capa</label>
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-placeholder" id="uploadPlaceholder">
                            <i data-lucide="image-plus"></i>
                            <p>Clique ou arraste uma imagem</p>
                            <small>JPEG, PNG ou WebP — máx. 2MB</small>
                        </div>
                        <div class="upload-preview" id="uploadPreview" style="display: none;">
                            <img id="previewImg" src="" alt="Preview">
                            <button type="button" class="btn-remove-img" data-action="removerImagem">&times;</button>
                        </div>
                        <input type="file" id="imagemCapa" name="imagem_capa" accept="image/jpeg,image/png,image/webp"
                            style="display: none;">
                        <input type="hidden" id="imagemCapaPath" name="imagem_capa_path">
                    </div>
                </div>

                <!-- Conteúdo (TinyMCE) -->
                <div class="form-group">
                    <label for="conteudo">Conteúdo *</label>
                    <textarea id="conteudo" name="conteudo"></textarea>
                </div>

                <!-- SEO -->
                <details class="seo-section">
                    <summary>
                        <i data-lucide="search"></i>
                        Configurações de SEO
                    </summary>
                    <div class="seo-fields">
                        <div class="form-group">
                            <label for="meta_title">Meta Title</label>
                            <input type="text" id="meta_title" name="meta_title" maxlength="255"
                                placeholder="Título para mecanismos de busca">
                            <small>Deixe vazio para usar o título do artigo</small>
                        </div>
                        <div class="form-group">
                            <label for="meta_description">Meta Description</label>
                            <textarea id="meta_description" name="meta_description" rows="2" maxlength="500"
                                placeholder="Descrição para mecanismos de busca (máx. 160 caracteres recomendados)">
                            </textarea>
                            <small>Deixe vazio para usar o resumo do artigo</small>
                        </div>
                    </div>
                </details>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-action="fecharModalPost">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSalvar">
                    <i data-lucide="save"></i>
                    Salvar Artigo
                </button>
            </div>
        </form>
    </div>
</div>
