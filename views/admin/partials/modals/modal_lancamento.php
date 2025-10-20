  <div class="modal fade" id="modalLancamento" tabindex="-1" aria-labelledby="modalLancamentoTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width:600px">
          <div class="modal-content bg-dark text-light border-0 rounded-3">
              <div class="modal-header border-0">
                  <h5 class="modal-title" id="modalLancamentoTitle">Novo lancamento</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
              </div>

              <div class="modal-body pt-0">
                  <div id="novoLancAlert" class="alert alert-danger d-none" role="alert"></div>
                  <form id="formNovoLancamento" novalidate>
                      <div class="row g-3">

                          <div class="mb-3">
                              <label for="lanData" class="form-label text-light small mb-1">Data</label>
                              <input type="date" id="lanData"
                                  class="form-control form-control-sm bg-dark text-light border-secondary" required>
                          </div>

                          <div class="col-md-6 mb-3">
                              <label for="lanTipo" class="form-label text-light small mb-1">Tipo</label>
                              <select id="lanTipo"
                                  class="form-select form-select-sm bg-dark text-light border-secondary" required>
                                  <option value="despesa">Despesa</option>
                                  <option value="receita">Receita</option>
                              </select>
                          </div>

                          <div class="col-md-6 mb-3">
                              <label for="lanCategoria" class="form-label text-light small mb-1">Categoria</label>
                              <select id="lanCategoria"
                                  class="form-select form-select-sm bg-dark text-light border-secondary" required>
                                  <option value="">Selecione uma categoria</option>
                              </select>
                          </div>

                          <div class="mb-3">
                              <label for="headerConta" class="form-label text-light small mb-1">Conta</label>
                              <select id="headerConta"
                                  class="form-select form-select-sm bg-dark text-light border-secondary"
                                  autocomplete="off">
                                  <option value="">Todas as contas (opcional)</option>
                              </select>
                          </div>

                          <div class="col-md-3 mb-3">
                              <label for="lanValor" class="form-label text-light small mb-1">Valor</label>
                              <input type="text" id="lanValor"
                                  class="form-control form-control-sm bg-dark text-light border-secondary money-mask"
                                  placeholder="R$ 0,00" required>
                          </div>

                          <div class="col-md-9 mb-3">
                              <label for="lanDescricao" class="form-label text-light small mb-1">Descricao</label>
                              <input type="text" id="lanDescricao"
                                  class="form-control form-control-sm bg-dark text-light border-secondary"
                                  placeholder="Descricao do lancamento (opcional)">
                          </div>

                          <!-- 
                                    <div class="mb-3">
                                        <label for="lanObservacao"
                                            class="form-label text-light small mb-1">Observacao</label>
                                        <textarea id="lanObservacao"
                                            class="form-control form-control-sm bg-dark text-light border-secondary"
                                            rows="3" maxlength="500"
                                            placeholder="Detalhes adicionais (opcional)"></textarea>
                                    </div> -->

                      </div>
                  </form>
              </div>

              <div class="modal-footer border-0 pt-0">
                  <button type="button" class="btn btn-outline-secondary btn-sm"
                      data-bs-dismiss="modal">Cancelar</button>
                  <button type="submit" form="formNovoLancamento" class="btn btn-primary btn-sm">Salvar</button>
              </div>
          </div>
      </div>
  </div>