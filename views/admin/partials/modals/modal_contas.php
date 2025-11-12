 <div class="lk-modal" id="modalConta" role="dialog" aria-modal="true" aria-labelledby="modalContaTitle">
     <div class="lk-modal-card">
         <div class="lk-modal-h">
             <div class="lk-modal-t" id="modalContaTitle">Nova conta</div>
             <button class="btn btn-ghost" id="modalClose" type="button">
                 <i class="fas fa-times"></i>
             </button>
         </div>
         <div class="lk-modal-b">
             <form id="formConta">
                 <input type="hidden" id="contaId" value="">
                 <div class="lk-form-grid">
                     <div class="lk-field full">
                         <label for="nome">Nome da conta *</label>
                         <input id="nome" name="nome" type="text" placeholder="Ex.: Nubank, Dinheiro, PicPay" required>
                     </div>
                     <div class="lk-field">
                         <label for="instituicao">Instituição *</label>
                         <input id="instituicao" name="instituicao" type="text" placeholder="Ex.: Nubank, Caixa"
                             required>
                     </div>
                     <div class="lk-field">
                         <label for="saldo_inicial">Saldo inicial</label>
                         <input id="saldo_inicial" name="saldo_inicial" type="text" inputmode="decimal"
                             placeholder="0,00">
                     </div>

                 </div>
                 <div class="lk-modal-f">
                     <button type="button" class="btn btn-light" id="btnCancel">Cancelar</button>
                     <button type="submit" class="btn btn-primary" id="btnSave">Salvar</button>
                 </div>
             </form>
         </div>
     </div>
 </div>
 <div class="lk-modal" id="modalLancConta" role="dialog" aria-modal="true" aria-labelledby="modalLancContaTitle">
     <div class="lk-modal-card">
         <div class="lk-modal-h">
             <div class="lk-modal-t" id="modalLancContaTitle">Novo lançamento</div>
             <button class="btn btn-ghost" id="lancClose" type="button"><i class="fas fa-times"></i></button>
         </div>
         <div class="lk-modal-b">
             <form id="formLancConta">
                 <input type="hidden" id="lanContaId" value="">
                 <div class="lk-form-grid">
                     <div class="lk-field">
                         <label for="lanTipo">Tipo</label>
                         <select id="lanTipo" required>
                             <option value="despesa">Despesa</option>
                             <option value="receita">Receita</option>
                         </select>
                     </div>
                     <div class="lk-field">
                         <label for="lanData">Data</label>
                         <input type="date" id="lanData" required>
                     </div>
                     <div class="lk-field full">
                         <label for="lanCategoria">Categoria</label>
                         <select id="lanCategoria" required>
                             <option value="">Selecione uma categoria</option>
                         </select>
                     </div>
                     <div class="lk-field full">
                         <label for="lanDescricao">Descrição</label>
                         <input type="text" id="lanDescricao" placeholder="Ex.: Mercado / Salário">
                     </div>
                     <div class="lk-field full">
                         <label for="lanValor">Valor</label>
                         <input type="text" id="lanValor" inputmode="decimal" placeholder="0,00" required>
                     </div>
                     <!-- <div class="lk-field">
                            <label class="checkbox-label">
                                <input type="checkbox" id="lanPago">
                                <span class="checkbox-custom"></span> <span id="lanPagoLabel">Foi pago?</span>
                            </label>
                        </div> -->
                 </div>
                 <div class="lk-modal-f">
                     <button type="button" class="btn btn-light" id="lancCancel">Cancelar</button>
                     <button type="submit" class="btn btn-primary">Salvar</button>
                 </div>
             </form>
         </div>
     </div>
 </div><!-- Modal: Transferência -->
 <div class="lk-modal" id="modalTransfer" role="dialog" aria-modal="true" aria-labelledby="modalTransferTitle">
     <div class="lk-modal-card">
         <div class="lk-modal-h">
             <div class="lk-modal-t" id="modalTransferTitle">Transferência</div>
             <button class="btn btn-ghost" id="trClose" type="button"><i class="fas fa-times"></i></button>
         </div>
         <div class="lk-modal-b">
             <form id="formTransfer">
                 <input type="hidden" id="trOrigemId">
                 <div class="lk-form-grid">
                     <div class="lk-field full">
                         <label>Origem</label>
                         <input id="trOrigemNome" type="text" readonly>
                     </div>
                     <div class="lk-field full">
                         <label for="trDestinoId">Destino</label>
                         <select id="trDestinoId" required>
                             <option value="">Selecione a conta de destino</option>
                         </select>
                     </div>
                     <div class="lk-field">
                         <label for="trData">Data</label>
                         <input type="date" id="trData" required>
                     </div>
                     <div class="lk-field">
                         <label for="trValor">Valor</label>
                         <input type="text" id="trValor" inputmode="decimal" placeholder="0,00" required>
                     </div>
                     <div class="lk-field full">
                         <label for="trDesc">Descrição (opcional)</label>
                         <input type="text" id="trDesc" placeholder="Ex.: Transferência entre contas">
                     </div>
                 </div>
                 <div class="lk-modal-f">
                     <button type="button" class="btn btn-light" id="trCancel">Cancelar</button>
                     <button type="submit" class="btn btn-primary">Transferir</button>
                 </div>
             </form>
         </div>
     </div>
 </div>