import{a as _,g as T,j as b,e as h}from"./api-Dkfcp6ON.js";import{r as C}from"./ui-H2yoVZe7.js";function M(e){return{house:"#f97316",utensils:"#ef4444",car:"#3b82f6",lightbulb:"#eab308","heart-pulse":"#ef4444","graduation-cap":"#6366f1",shirt:"#ec4899",clapperboard:"#a855f7","credit-card":"#0ea5e9",smartphone:"#6366f1","shopping-cart":"#f97316",coins:"#eab308",briefcase:"#3b82f6",laptop:"#06b6d4","trending-up":"#22c55e",gift:"#ec4899",banknote:"#22c55e",trophy:"#f59e0b",wallet:"#14b8a6",tag:"#94a3b8","pie-chart":"#8b5cf6","piggy-bank":"#ec4899",plane:"#0ea5e9","gamepad-2":"#a855f7",baby:"#f472b6",dog:"#92400e",wrench:"#64748b",church:"#6366f1",dumbbell:"#ef4444",music:"#a855f7","book-open":"#3b82f6",scissors:"#ec4899","building-2":"#64748b",landmark:"#3b82f6",receipt:"#14b8a6"}[e]||"#f97316"}const v={BASE_URL:(window.BASE_URL||"/").replace(/\/?$/,"/"),ENDPOINTS:{parcelamentos:"api/faturas",categorias:"api/categorias",contas:"api/contas",cartoes:"api/cartoes"},TIMEOUTS:{alert:5e3,successMessage:2e3}},n={};function L(){n.loadingEl=document.getElementById("loadingParcelamentos"),n.containerEl=document.getElementById("parcelamentosContainer"),n.emptyStateEl=document.getElementById("emptyState"),n.filtroStatus=document.getElementById("filtroStatus"),n.filtroCartao=document.getElementById("filtroCartao"),n.filtroAno=document.getElementById("filtroAno"),n.filtroMes=document.getElementById("filtroMes"),n.btnFiltrar=document.getElementById("btnFiltrar"),n.btnLimparFiltros=document.getElementById("btnLimparFiltros"),n.filtersContainer=document.querySelector(".filters-modern"),n.filtersBody=document.getElementById("filtersBody"),n.toggleFilters=document.getElementById("toggleFilters"),n.activeFilters=document.getElementById("activeFilters"),n.modalDetalhes=document.getElementById("modalDetalhesParcelamento"),n.detalhesContent=document.getElementById("detalhesParcelamentoContent")}const i={parcelamentos:[],cartoes:[],faturaAtual:null,sortColumn:"data_compra",sortDirection:"asc",filtros:{status:"",cartao_id:"",ano:new Date().getFullYear(),mes:""},modalDetalhesInstance:null,anosCarregados:!1},u={},c={formatMoney(e){return new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(e||0)},formatDate(e){return e?new Date(e+"T00:00:00").toLocaleDateString("pt-BR"):""},parseMoney(e){return e&&parseFloat(e.replace(/[^\d,]/g,"").replace(",","."))||0},showAlert(e,a,t="danger"){e&&(e.className=`alert alert-${t}`,e.textContent=a,e.style.display="block",setTimeout(()=>{e.style.display="none"},v.TIMEOUTS.alert))},getCSRFToken(){return T()},escapeHtml(e){if(!e)return"";const a=document.createElement("div");return a.textContent=e,a.innerHTML},buildUrl(e,a={}){const t=e.startsWith("http")?e:v.BASE_URL+e.replace(/^\//,""),o=Object.entries(a).filter(([r,s])=>s!=null&&s!=="").map(([r,s])=>`${r}=${encodeURIComponent(s)}`);return o.length>0?`${t}?${o.join("&")}`:t},async apiRequest(e,a={}){const t=e.startsWith("http")?e:v.BASE_URL+e.replace(/^\//,"");try{return await _(t,{...a,headers:{"X-CSRF-Token":this.getCSRFToken(),...a.headers}})}catch(o){throw console.error("Erro na requisição:",o),o}},debounce(e,a){let t;return function(...r){const s=()=>{clearTimeout(t),e(...r)};clearTimeout(t),t=setTimeout(s,a)}},calcularDiferencaDias(e,a){const t=new Date(e+"T00:00:00"),o=new Date(a+"T00:00:00");return Math.floor((t-o)/(1e3*60*60*24))}},k={async listarParcelamentos(e={}){const a={status:e.status,cartao_id:e.cartao_id,ano:e.ano,mes:e.mes},t=c.buildUrl(v.ENDPOINTS.parcelamentos,a);return await c.apiRequest(t)},async listarCartoes(){return await c.apiRequest(v.ENDPOINTS.cartoes)},async buscarParcelamento(e){const a=parseInt(e,10);if(isNaN(a))throw new Error("ID inválido");return await c.apiRequest(`${v.ENDPOINTS.parcelamentos}/${a}`)},async criarParcelamento(e){return await c.apiRequest(v.ENDPOINTS.parcelamentos,{method:"POST",body:JSON.stringify(e)})},async cancelarParcelamento(e){return await c.apiRequest(`${v.ENDPOINTS.parcelamentos}/${e}`,{method:"DELETE"})},async toggleItemFatura(e,a,t){return await c.apiRequest(`${v.ENDPOINTS.parcelamentos}/${e}/itens/${a}/toggle`,{method:"POST",body:JSON.stringify({pago:t})})},async pagarFaturaCompleta(e,a,t,o=null){const r={mes:a,ano:t};return o&&(r.conta_id=o),await c.apiRequest(`${v.ENDPOINTS.cartoes}/${e}/fatura/pagar`,{method:"POST",body:JSON.stringify(r)})},async listarContas(){return await c.apiRequest(`${v.ENDPOINTS.contas}?with_balances=1`)}};u.API=k;const y={showLoading(){n.loadingEl.style.display="flex",n.containerEl.style.display="none",n.emptyStateEl.style.display="none"},hideLoading(){n.loadingEl.style.display="none"},showEmpty(){n.containerEl.style.display="none",n.emptyStateEl.style.display="block"},renderParcelamentos(e){if(!Array.isArray(e)||e.length===0){this.showEmpty();return}n.emptyStateEl.style.display="none",n.containerEl.style.display="grid";const a=document.createDocumentFragment();e.forEach(t=>{const o=this.createParcelamentoCard(t);a.appendChild(o)}),n.containerEl.innerHTML="",n.containerEl.appendChild(a),C()},createParcelamentoCard(e){const a=e.progresso||0,t=e.parcelas_pendentes||0,o=e.parcelas_pagas||0,r=o+t,s=document.createElement("div");s.className=`parcelamento-card status-${e.status}`,s.dataset.id=e.id;const l=this.getStatusBadge(e.status,a),d=e.mes_referencia||"",f=e.ano_referencia||"";return s.innerHTML=this.createCardHTML({parc:e,statusBadge:l,mes:d,ano:f,itensPendentes:t,itensPagos:o,totalItens:r,progresso:a}),this.attachCardEventListeners(s,e.id),s},createCardHTML({parc:e,statusBadge:a,mes:t,ano:o,itensPendentes:r,itensPagos:s,totalItens:l,progresso:d}){this.getCartaoInfo(e);const f=this.getResumoPrincipal(e),m=this.getProgressoSection(l,s,d),g=e.cartao&&(e.cartao.nome||e.cartao.bandeira)||"Cartão",w=e.cartao?.ultimos_digitos?`•••• ${e.cartao.ultimos_digitos}`:"",E=this.getCardColor(e.cartao),x=e.cartao?.bandeira?.toLowerCase()||"outros",$=this.getBandeiraIcon(x);return`
            <div class="parc-card-header" style="background: ${E};">
                <div class="header-left">
                    <div class="header-brand">
                        ${$}
                    </div>
                    <div class="header-info">
                        <span class="header-cartao-nome">${c.escapeHtml(g)}</span>
                        <span class="header-cartao-numero">${w||""}</span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-periodo">
                        <i data-lucide="calendar-days" style= "color:white"></i>
                        <span>${t}/${o}</span>
                    </div>
                    ${a}
                </div>
            </div>
            <div class="fatura-list-info">
                <span class="list-cartao-nome">${c.escapeHtml(g)}</span>
                <span class="list-periodo">${t}/${o}</span>
                ${w?`<span class="list-cartao-numero">${w}</span>`:""}
            </div>
            <div class="fatura-resumo-principal">${f}</div>
            ${m}
            <div class="fatura-status-col">${a}</div>
            <div class="parc-card-actions">
                <button class="parc-btn parc-btn-view" data-action="view" data-id="${e.id}">
                    <i data-lucide="eye"></i>
                    <span>Ver Detalhes</span>
                </button>
            </div>
        `},extrairMesAno(e){const a=e.match(/(\d{1,2})\/(\d{4})/);if(a)return[parseInt(a[1],10),a[2]];const t=new Date;return[t.getMonth()+1,t.getFullYear()]},getCardColor(e){return e?.cor_cartao?e.cor_cartao:e?.conta?.instituicao_financeira?.cor_primaria?e.conta.instituicao_financeira.cor_primaria:{visa:"linear-gradient(135deg, #1A1F71 0%, #2D3A8C 100%)",mastercard:"linear-gradient(135deg, #EB001B 0%, #F79E1B 100%)",elo:"linear-gradient(135deg, #FFCB05 0%, #FFE600 100%)",amex:"linear-gradient(135deg, #006FCF 0%, #0099CC 100%)",diners:"linear-gradient(135deg, #0079BE 0%, #00558C 100%)",discover:"linear-gradient(135deg, #FF6000 0%, #FF8500 100%)",hipercard:"linear-gradient(135deg, #B11116 0%, #D32F2F 100%)"}[e?.bandeira?.toLowerCase()]||"linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%)"},getAccentColorSolid(e){return e?.cor_cartao?e.cor_cartao:{visa:"#1A1F71",mastercard:"#EB001B",elo:"#FFCB05",amex:"#006FCF",diners:"#0079BE",discover:"#FF6000",hipercard:"#B11116"}[e?.bandeira?.toLowerCase()]||"#8b5cf6"},getBandeiraIcon(e){return{visa:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71"/><text x="24" y="20" text-anchor="middle" font-size="12" font-weight="bold" fill="#fff" font-family="sans-serif">VISA</text></svg>',mastercard:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71" opacity="0"/><circle cx="19" cy="16" r="10" fill="#EB001B" opacity=".85"/><circle cx="29" cy="16" r="10" fill="#F79E1B" opacity=".85"/></svg>',elo:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#000"/><text x="24" y="20" text-anchor="middle" font-size="13" font-weight="bold" fill="#FFCB05" font-family="sans-serif">elo</text></svg>',amex:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#006FCF"/><text x="24" y="20" text-anchor="middle" font-size="9" font-weight="bold" fill="#fff" font-family="sans-serif">AMEX</text></svg>',hipercard:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#B11116"/><text x="24" y="20" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff" font-family="sans-serif">HIPER</text></svg>',diners:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#0079BE"/><text x="24" y="20" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff" font-family="sans-serif">DINERS</text></svg>'}[e]||'<i data-lucide="credit-card"></i>'},getCartaoInfo(e){return e.cartao?`
            <span class="cartao-nome">${c.escapeHtml(e.cartao.nome||e.cartao.bandeira)}</span>
            <span class="cartao-numero">•••• ${e.cartao.ultimos_digitos||""}</span>
        `:`<span class="cartao-nome">${c.escapeHtml(e.descricao)}</span>`},getResumoPrincipal(e){const a=e.total_estornos&&e.total_estornos>0;let t="",o=e.data_vencimento;if(!o&&e.cartao?.dia_vencimento&&e.descricao){const r=e.descricao.match(/(\d{1,2})\/(\d{4})/);if(r){const s=r[1].padStart(2,"0"),l=r[2],d=String(e.cartao.dia_vencimento).padStart(2,"0");o=`${l}-${s}-${d}`}}if(o){const r=c.formatDate(o),s=new Date;s.setHours(0,0,0,0);const l=new Date(o+"T00:00:00"),d=e.status!=="paga"&&e.status!=="concluido"&&e.status!=="cancelado",f=d&&l<s,m=d&&!f&&l-s<=4320*60*1e3;let g="resumo-vencimento";f?g+=" vencimento-atrasado":m&&(g+=" vencimento-proximo"),t=`
                <div class="${g}">
                    <i data-lucide="calendar-clock"></i>
                    <span class="vencimento-label">Vencimento</span>
                    <span class="vencimento-data">${r}</span>
                    ${f?'<span class="vencimento-tag tag-atrasado">Vencida</span>':""}
                    ${m?'<span class="vencimento-tag tag-proximo">Em breve</span>':""}
                </div>
            `}return`
            <div class="resumo-item">
                <span class="resumo-label">Total a Pagar</span>
                <strong class="resumo-valor">${c.formatMoney(e.valor_total)}</strong>
            </div>
            ${a?`
                <div class="resumo-item resumo-estornos">
                    <span class="resumo-label" style="color: #10b981;">Estornos</span>
                    <span class="resumo-valor" style="color: #10b981;">- ${c.formatMoney(e.total_estornos)}</span>
                </div>
            `:""}
            ${t}
        `},getItensInfo(e,a){return""},getProgressoSection(e,a,t){return e===0?"":`
            <div class="parc-progress-section">
                <div class="parc-progress-header">
                    <span class="parc-progress-text">${a} de ${e} pagos</span>
                    <span class="parc-progress-percent">${Math.round(t)}%</span>
                </div>
                <div class="parc-progress-bar">
                    <div class="parc-progress-fill" style="width: ${t}%"></div>
                </div>
            </div>
        `},attachCardEventListeners(e,a){const t=e.querySelector('[data-action="view"]');t&&t.addEventListener("click",()=>this.showDetalhes(a))},getStatusBadge(e,a=null){return a!==null?a===0?'<span class="parc-card-badge badge-pendente"><i data-lucide="clock" style="width:12px;height:12px"></i> Pendente</span>':a>=100?'<span class="parc-card-badge badge-paga"><i data-lucide="circle-check" style="width:12px;height:12px"></i> Paga</span>':'<span class="parc-card-badge badge-parcial"><i data-lucide="loader-2" style="width:12px;height:12px"></i> Parcial</span>':{ativo:'<span class="parc-card-badge badge-ativo"><i data-lucide="clock" style="width:12px;height:12px"></i> Pendente</span>',paga:'<span class="parc-card-badge badge-paga"><i data-lucide="circle-check" style="width:12px;height:12px"></i> Paga</span>',concluido:'<span class="parc-card-badge badge-paga"><i data-lucide="circle-check" style="width:12px;height:12px"></i> Paga</span>',cancelado:'<span class="parc-card-badge badge-cancelado"><i data-lucide="x-circle" style="width:12px;height:12px"></i> Cancelada</span>'}[e]||'<span class="parc-card-badge badge-ativo"><i data-lucide="clock" style="width:12px;height:12px"></i> Pendente</span>'},async showDetalhes(e){try{const a=await u.API.buscarParcelamento(e),t=b(a,null);if(!t){i.modalDetalhesInstance&&i.modalDetalhesInstance.hide();return}i.faturaAtual=t;const o=n.modalDetalhes;if(o&&t.cartao){const r=this.getAccentColorSolid(t.cartao),s=o.querySelector(".modal-content");s&&s.style.setProperty("--card-accent",r)}n.detalhesContent.innerHTML=this.renderDetalhes(t),C(),this.attachDetalhesEventListeners(t.id),document.activeElement?.blur(),i.modalDetalhesInstance.show()}catch(a){if(console.error("Erro ao abrir detalhes:",a),a.message&&a.message.includes("404")){i.modalDetalhesInstance&&i.modalDetalhesInstance.hide();return}Swal.fire({icon:"error",title:"Erro",text:h(a,"Não foi possível carregar os detalhes da fatura")})}},attachDetalhesEventListeners(e){n.detalhesContent.querySelectorAll(".th-sortable").forEach(s=>{s.addEventListener("click",()=>{const l=s.dataset.sort;i.sortColumn===l?i.sortDirection=i.sortDirection==="asc"?"desc":"asc":(i.sortColumn=l,i.sortDirection="asc"),i.faturaAtual&&(n.detalhesContent.innerHTML=this.renderDetalhes(i.faturaAtual),C(),this.attachDetalhesEventListeners(e))})}),n.detalhesContent.querySelectorAll(".btn-pagar, .btn-desfazer").forEach(s=>{s.addEventListener("click",async l=>{const d=parseInt(l.currentTarget.dataset.lancamentoId,10),f=l.currentTarget.dataset.pago==="true";await this.toggleParcelaPaga(e,d,!f)})}),n.detalhesContent.querySelectorAll(".btn-editar").forEach(s=>{s.addEventListener("click",async l=>{const d=parseInt(l.currentTarget.dataset.lancamentoId,10),f=l.currentTarget.dataset.descricao||"",m=parseFloat(l.currentTarget.dataset.valor)||0;await this.editarItemFatura(e,d,f,m)})}),n.detalhesContent.querySelectorAll(".btn-excluir").forEach(s=>{s.addEventListener("click",async l=>{const d=parseInt(l.currentTarget.dataset.lancamentoId,10),f=l.currentTarget.dataset.ehParcelado==="true",m=parseInt(l.currentTarget.dataset.totalParcelas)||1;await this.excluirItemFatura(e,d,f,m)})})},renderDetalhes(e){const a=e.progresso||0,{valorPago:t,valorRestante:o}=this.calcularValores(e),r=e.parcelas_pendentes>0&&o>0;return`
            ${this.renderDetalhesHeader(e,r,o)}
            ${this.renderDetalhesGrid(e,a)}
            ${this.renderDetalhesProgresso(e,a,t,o)}
            ${this.renderParcelasTabela(e)}
        `},calcularValores(e){let a=0,t=e.valor_total;return e.parcelas&&e.parcelas.length>0&&(a=e.parcelas.filter(o=>o.pago).reduce((o,r)=>o+parseFloat(r.valor_parcela||r.valor||0),0),t=e.parcelas.filter(o=>!o.pago).reduce((o,r)=>o+parseFloat(r.valor_parcela||r.valor||0),0)),{valorPago:a,valorRestante:t}},renderDetalhesHeader(e,a,t){let o="/";e.data_vencimento?o=c.formatDate(e.data_vencimento):e.mes_referencia&&e.ano_referencia&&(o=`${this.getNomeMes(e.mes_referencia)}/${e.ano_referencia}`),e.parcelas_pagas>0;const r=e.parcelas_pendentes===0&&e.parcelas_pagas>0;return`
            <div class="detalhes-header">
                <div class="detalhes-header-content" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <span style="color: #9ca3af; font-size: 0.875rem; font-weight: 500;">Vencimento</span>
                        <h3 class="detalhes-title" style="margin: 0;">${o}</h3>
                    </div>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                        ${a?`
                            <button class="btn-pagar-fatura" 
                                    onclick="window.abrirModalPagarFatura(${e.id}, ${t})"
                                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                                <i data-lucide="credit-card"></i>
                                <span class="btn-text-desktop">Pagar Fatura</span>
                                <span class="btn-text-mobile">Pagar</span>
                            </button>
                        `:""}
                        ${r?`
                            <button class="btn-reverter-fatura" 
                                    onclick="window.reverterPagamentoFaturaGlobal(${e.id})"
                                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                                <i data-lucide="undo-2"></i>
                                <span class="btn-text-desktop">Reverter Pagamento</span>
                                <span class="btn-text-mobile">Reverter</span>
                            </button>
                        `:""}
                    </div>
                </div>
            </div>
        `},renderDetalhesGrid(e,a){const t=e.parcelas_pagas+e.parcelas_pendentes,o=e.total_estornos&&e.total_estornos>0;return`
            <div class="detalhes-grid">
                <div class="detalhes-item">
                    <span class="detalhes-label">💵 Valor Total a Pagar</span>
                    <span class="detalhes-value detalhes-value-highlight">${c.formatMoney(e.valor_total)}</span>
                </div>
                ${o?`
                <div class="detalhes-item">
                    <span class="detalhes-label">↩️ Estornos/Créditos</span>
                    <span class="detalhes-value" style="color: #10b981;">- ${c.formatMoney(e.total_estornos)}</span>
                </div>
                `:""}
                <div class="detalhes-item">
                    <span class="detalhes-label">📦 Itens</span>
                    <span class="detalhes-value">${t} itens</span>
                </div>
                <div class="detalhes-item">
                    <span class="detalhes-label">📊 Tipo</span>
                    <span class="detalhes-value">💸 Despesas${o?" + ↩️ Estornos":""}</span>
                </div>
                <div class="detalhes-item">
                    <span class="detalhes-label">🎯 Status</span>
                    <span class="detalhes-value">${this.getStatusBadge(e.status,a)}</span>
                </div>
                ${e.cartao?`
                    <div class="detalhes-item">
                        <span class="detalhes-label">💳 Cartão</span>
                        <span class="detalhes-value">${e.cartao.bandeira} ${e.cartao.nome?"- "+c.escapeHtml(e.cartao.nome):""}</span>
                    </div>
                `:""}
            </div>
        `},renderDetalhesProgresso(e,a,t,o){const r=e.parcelas_pagas+e.parcelas_pendentes;return`
            <div class="detalhes-progresso">
                <div class="progresso-info">
                    <span><strong>${e.parcelas_pagas}</strong> de <strong>${r}</strong> itens pagos</span>
                    <span class="progresso-percent"><strong>${Math.round(a)}%</strong></span>
                </div>
                <div class="progresso-barra">
                    <div class="progresso-fill" style="width: ${a}%"></div>
                </div>
                <div class="progresso-valores">
                    <span class="valor-pago">✅ Pago: ${c.formatMoney(t)}</span>
                    <span class="valor-restante">⏳ Restante: ${c.formatMoney(o)}</span>
                </div>
            </div>
        `},renderParcelasTabela(e){const a=r=>i.sortColumn===r?i.sortDirection==="asc"?'<i data-lucide="arrow-up" class="sort-icon active"></i>':'<i data-lucide="arrow-down" class="sort-icon active"></i>':'<i data-lucide="arrow-up-down" class="sort-icon"></i>',t=this.sortParcelas(e.parcelas||[]);let o=`
            <h4 class="parcelas-titulo">📋 Lista de Itens</h4>
            
            <!-- Tabela Desktop -->
            <div class="parcelas-container parcelas-desktop">
                <table class="parcelas-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="th-sortable" data-sort="descricao">Descrição ${a("descricao")}</th>
                            <th class="th-sortable" data-sort="data_compra">Data Compra ${a("data_compra")}</th>
                            <th class="th-sortable" data-sort="valor">Valor ${a("valor")}</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
        `;return t.length>0?t.forEach((r,s)=>{o+=this.renderParcelaRow(r,s,e.descricao)}):o+=`
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem;">
                        <p style="color: #6b7280;">Nenhuma parcela encontrada</p>
                    </td>
                </tr>
            `,o+=`
                    </tbody>
                </table>
            </div>
            
            <!-- Cards Mobile -->
            <div class="parcelas-container parcelas-mobile">
        `,e.parcelas&&e.parcelas.length>0?this.sortParcelas(e.parcelas).forEach((s,l)=>{o+=this.renderParcelaCard(s,l,e.descricao)}):o+=`
                <div class="parcela-card-empty">
                    <p>Nenhuma parcela encontrada</p>
                </div>
            `,o+="</div>",o},sortParcelas(e){if(!e||e.length===0)return[];const a=[...e],t=i.sortDirection==="asc"?1:-1,o=i.sortColumn;return a.sort((r,s)=>{if(o==="descricao"){const l=(r.descricao||"").toLowerCase(),d=(s.descricao||"").toLowerCase();return l.localeCompare(d)*t}if(o==="data_compra"){const l=r.data_compra||"0000-00-00",d=s.data_compra||"0000-00-00";return l.localeCompare(d)*t}if(o==="valor"){const l=parseFloat(r.valor_parcela||r.valor||0),d=parseFloat(s.valor_parcela||s.valor||0);return(l-d)*t}return 0}),a},renderParcelaCard(e,a,t){const o=e.pago,r=e.tipo==="estorno",s=o?"parcela-paga":"parcela-pendente",l=o?"✅ Paga":"⏳ Pendente",d=o?"parcela-card-paga":"",f=`${this.getNomeMes(e.mes_referencia)}/${e.ano_referencia}`,m=`parcela-card-${e.id||a}`;let g=e.descricao||t;g=g.replace(/\s*\(\d+\/\d+\)\s*$/,"");let w="";if(e.categoria){const E=e.categoria.icone||"tag",x=e.categoria.nome||e.categoria;w=`<i data-lucide="${E}" style="width:14px;height:14px;display:inline-block;vertical-align:middle;color:${M(E)}"></i> ${c.escapeHtml(x)}`}return r?`
                <div class="parcela-card" id="${m}" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%); border-color: rgba(16, 185, 129, 0.4);">
                    <div class="parcela-card-header">
                        <span class="parcela-numero" style="color: #10b981;">↩️ Estorno</span>
                        <span class="parcela-paga" style="background: #10b981;">✅ Creditado</span>
                    </div>
                    <div class="parcela-card-body">
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Descrição</span>
                            <span class="parcela-card-value" style="color: #10b981;">${c.escapeHtml(g)}</span>
                        </div>
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Crédito na Fatura</span>
                            <span class="parcela-card-value parcela-valor" style="color: #10b981; font-weight: 600;">
                                - ${c.formatMoney(Math.abs(e.valor_parcela))}
                            </span>
                        </div>
                    </div>
                </div>
            `:`
            <div class="parcela-card ${d}" id="${m}">
                <div class="parcela-card-header">
                    <span class="parcela-numero">${e.recorrente?'<i data-lucide="refresh-cw" style="width:12px;height:12px;display:inline-block;vertical-align:middle;color:var(--primary, #e67e22);margin-right:3px;"></i> Recorrente':`${e.numero_parcela||a+1}/${e.total_parcelas||1}`}</span>
                    <span class="${s}">${l}</span>
                </div>
                <div class="parcela-card-body">
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Descrição</span>
                        <span class="parcela-card-value">${c.escapeHtml(g)}${e.recorrente?' <span class="badge-recorrente" title="Assinatura recorrente" style="display:inline-flex;align-items:center;background:rgba(230,126,34,0.15);border-radius:6px;padding:1px 6px;margin-left:6px;"><i data-lucide="refresh-cw" style="width:12px;height:12px;color:var(--primary, #e67e22);"></i></span>':""}</span>
                    </div>
                    ${e.data_compra?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Data Compra</span>
                        <span class="parcela-card-value"><i data-lucide="shopping-cart" style="margin-right: 4px; font-size: 0.75rem;"></i>${c.formatDate(e.data_compra)}</span>
                    </div>
                    `:""}
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Valor</span>
                        <span class="parcela-card-value parcela-valor">${c.formatMoney(e.valor_parcela)}</span>
                    </div>
                </div>
                
                <!-- Detalhes expandíveis -->
                <div class="parcela-card-detalhes" id="detalhes-${m}" style="display: none;">
                    ${w?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Categoria</span>
                        <span class="parcela-card-value">${w}</span>
                    </div>
                    `:""}
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Mês/Ano</span>
                        <span class="parcela-card-value">${f}</span>
                    </div>
                    ${o&&e.data_pagamento?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Data Pagamento</span>
                        <span class="parcela-card-value">${e.data_pagamento}</span>
                    </div>
                    `:""}
                    ${e.id?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">ID do Item</span>
                        <span class="parcela-card-value">#${e.id}</span>
                    </div>
                    `:""}
                </div>
                
                <div class="parcela-card-footer">
                  
                    ${this.renderParcelaButton(e,o)}
                </div>
            </div>
        `},renderParcelaRow(e,a,t){const o=e.pago,r=e.tipo==="estorno",s=o?"tr-paga":"";`${this.getNomeMes(e.mes_referencia)}${e.ano_referencia}`,this.getDataPagamentoInfo(e);let l=e.descricao||t;l=l.replace(/\s*\(\d+\/\d+\)\s*$/,""),e.categoria&&(l=e.categoria.nome||e.categoria);const d=e.data_compra?c.formatDate(e.data_compra):"-";return r?`
                <tr class="tr-estorno" style="background: rgba(16, 185, 129, 0.1);">
                    <td data-label="#">
                        <span class="parcela-numero" style="color: #10b981;">↩️</span>
                    </td>
                    <td data-label="Descrição" class="td-descricao">
                        <div class="parcela-desc" style="color: #10b981;">${c.escapeHtml(l)}</div>
                    </td>
                    <td data-label="Data Compra">
                        <span style="color: #10b981; font-size: 0.85rem;">${d}</span>
                    </td>
                    <td data-label="Valor">
                        <span class="parcela-valor" style="color: #10b981; font-weight: 600;">
                            - ${c.formatMoney(Math.abs(e.valor_parcela))}
                        </span>
                    </td>
                    <td data-label="Ação" class="td-acoes">
                        <span style="color: #10b981; font-size: 0.85rem;">Estorno aplicado</span>
                    </td>
                </tr>
            `:`
            <tr class="${s}">
                <td data-label="#">
                    <span class="parcela-numero">${e.recorrente?'<i data-lucide="refresh-cw" style="width:12px;height:12px;display:inline-block;vertical-align:middle;color:var(--primary, #e67e22);"></i>':`${e.numero_parcela}/${e.total_parcelas}`}</span>
                </td>
                <td data-label="Descrição" class="td-descricao">
                    <div class="parcela-desc">${c.escapeHtml(l)}${e.recorrente?' <span class="badge-recorrente" style="display:inline-flex;align-items:center;background:rgba(230,126,34,0.15);border-radius:6px;padding:1px 6px;margin-left:6px;"><i data-lucide="refresh-cw" style="width:12px;height:12px;color:var(--primary, #e67e22);"></i></span>':""}</div>
                </td>
                <td data-label="Data Compra">
                    <span style="font-size: 0.85rem; color: #9ca3af;">${d}</span>
                </td>
                <td data-label="Valor">
                    <span class="parcela-valor">${c.formatMoney(e.valor_parcela)}</span>
                </td>
                <td data-label="Ação" class="td-acoes">
                    ${this.renderParcelaButton(e,o)}
                </td>
            </tr>
        `},getDataPagamentoInfo(e){return!e.pago||!e.data_pagamento?"":`<small style="color: #10b981; display: block; margin-top: 3px;">✅ Pago em ${e.data_pagamento}</small>`},renderParcelaButton(e,a){if(a)return`
                <div class="btn-group-parcela">
                    <span class="badge-pago" style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;">
                        <i data-lucide="check"></i> Pago
                    </span>
                </div>
            `;{const t=e.total_parcelas>1;return`
                <div class="btn-group-parcela">
                    <button class="btn-toggle-parcela btn-editar" 
                        data-lancamento-id="${e.id}"
                        data-descricao="${c.escapeHtml(e.descricao||"")}"
                        data-valor="${e.valor_parcela||0}"
                        title="Editar item">
                        <i data-lucide="pencil"></i>
                    </button>
                    <button class="btn-toggle-parcela btn-excluir" 
                        data-lancamento-id="${e.id}"
                        data-eh-parcelado="${t}"
                        data-total-parcelas="${e.total_parcelas||1}"
                        title="Excluir item">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            `}},getNomeMes(e){return["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"][e-1]||e},mostrarDetalhesParcela(e,a){const t=e.pago,o=t?"✅":"⏳",r=t?"Paga":"Pendente",s=t?"#10b981":"#f59e0b",l=`${this.getNomeMesCompleto(e.mes_referencia)}/${e.ano_referencia}`;let d="";t&&e.data_pagamento&&(d=`
                <div class="detalhes-item">
                    <span class="detalhes-label">Data de Pagamento</span>
                    <span class="detalhes-value">${c.formatDate(e.data_pagamento)}</span>
                </div>
            `),Swal.fire({title:`${o} Detalhes da Parcela`,html:`
                <div style="text-align: left;">
                    <div style="background: ${s}15; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid ${s};">
                        <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">Status</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: ${s};">${r}</div>
                    </div>
                    
                    <div class="detalhes-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="detalhes-item">
                            <span class="detalhes-label" style="display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Parcela</span>
                            <span class="detalhes-value" style="display: block; font-weight: 600; color: #1f2937;">${e.numero_parcela}/${e.total_parcelas}</span>
                        </div>
                        
                        <div class="detalhes-item">
                            <span class="detalhes-label" style="display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Valor</span>
                            <span class="detalhes-value" style="display: block; font-weight: 600; color: ${s};">${c.formatMoney(e.valor)}</span>
                        </div>
                    </div>

                    <div class="detalhes-item" style="margin-bottom: 1rem;">
                        <span class="detalhes-label" style="display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Descrição</span>
                        <span class="detalhes-value" style="display: block; font-weight: 500; color: #1f2937;">${c.escapeHtml(a)}</span>
                    </div>

                    <div class="detalhes-item" style="margin-bottom: 1rem;">
                        <span class="detalhes-label" style="display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Mês de Referência</span>
                        <span class="detalhes-value" style="display: block; font-weight: 600; color: #1f2937;">${l}</span>
                    </div>

                    ${d}
                </div>
            `,icon:!1,confirmButtonText:"Fechar",confirmButtonColor:"#6366f1",width:"500px"})},getNomeMesCompleto(e){return["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"][e-1]||e},async toggleParcelaPaga(e,a,t){try{const o=t?"pagar":"desfazer pagamento";if(!(await Swal.fire({title:t?"Marcar como pago?":"Desfazer pagamento?",text:`Deseja realmente ${o} este item?`,icon:"question",showCancelButton:!0,confirmButtonColor:t?"#10b981":"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:t?"Sim, marcar como pago":"Sim, desfazer",cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")}})).isConfirmed)return;Swal.fire({title:"Processando...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")},customClass:{container:"swal-above-modal"}}),await u.API.toggleItemFatura(e,a,t),await Swal.fire({icon:"success",title:"Sucesso!",text:t?"Item marcado como pago":"Pagamento desfeito",timer:v.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")}}),await u.App.carregarParcelamentos(),setTimeout(()=>{y.showDetalhes(e)},100)}catch(o){console.error("Erro ao alternar status:",o),Swal.fire({icon:"error",title:"Erro",text:h(o,"Erro ao processar operação"),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const r=document.querySelector(".swal2-container");r&&(r.style.zIndex="99999")}})}},async editarItemFatura(e,a,t,o){const r=document.getElementById("modalEditarItemFatura");if(!r){console.error("Modal de edição não encontrado");return}document.getElementById("editItemFaturaId").value=e,document.getElementById("editItemId").value=a,document.getElementById("editItemDescricao").value=t,document.getElementById("editItemValor").value=o.toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2}),new bootstrap.Modal(r).show()},async salvarItemFatura(){const e=document.getElementById("editItemFaturaId").value,a=document.getElementById("editItemId").value,t=document.getElementById("editItemDescricao").value.trim(),o=document.getElementById("editItemValor").value;if(!t){Swal.fire({icon:"warning",title:"Atenção",text:"Informe a descrição do item.",timer:2e3,showConfirmButton:!1});return}const r=parseFloat(o.replace(/\./g,"").replace(",","."))||0;if(r<=0){Swal.fire({icon:"warning",title:"Atenção",text:"Informe um valor válido.",timer:2e3,showConfirmButton:!1});return}try{const s=document.getElementById("modalEditarItemFatura"),l=bootstrap.Modal.getInstance(s);l&&l.hide(),Swal.fire({title:"Atualizando item...",html:"Aguarde enquanto salvamos as alterações.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading()}}),await c.apiRequest(`api/faturas/${e}/itens/${a}`,{method:"PUT",body:JSON.stringify({descricao:t,valor:r})}),await Swal.fire({icon:"success",title:"Item Atualizado!",text:"O item foi atualizado com sucesso.",timer:v.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1}),await u.App.carregarParcelamentos(),setTimeout(()=>{y.showDetalhes(e)},100)}catch(s){console.error("Erro ao editar item:",s),Swal.fire({icon:"error",title:"Erro",text:h(s,"Não foi possível atualizar o item."),heightAuto:!1})}},async excluirItemFatura(e,a,t,o){try{let r="Excluir Item?",s="Deseja realmente excluir este item da fatura?",l="Sim, excluir item";if(t&&o>1){const{value:m}=await Swal.fire({title:"O que deseja excluir?",html:`
                        <p>Este item faz parte de um parcelamento de <strong>${o}x</strong>.</p>
                        <p style="margin-top: 1rem;">Escolha uma opção:</p>
                    `,icon:"question",input:"radio",inputOptions:{item:"Apenas esta parcela",parcelamento:`Todo o parcelamento (${o} parcelas)`},inputValue:"item",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:"Continuar",cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const g=document.querySelector(".swal2-container");g&&(g.style.zIndex="99999")}});if(!m)return;if(m==="parcelamento")return await this.excluirParcelamentoCompleto(e,a,o)}if(!(await Swal.fire({title:r,text:s,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:l,cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const m=document.querySelector(".swal2-container");m&&(m.style.zIndex="99999")}})).isConfirmed)return;Swal.fire({title:"Excluindo...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const m=document.querySelector(".swal2-container");m&&(m.style.zIndex="99999")},customClass:{container:"swal-above-modal"}}),await c.apiRequest(`api/faturas/${e}/itens/${a}`,{method:"DELETE"}),await Swal.fire({icon:"success",title:"Excluído!",text:"Item removido da fatura.",timer:v.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const m=document.querySelector(".swal2-container");m&&(m.style.zIndex="99999")}}),await u.App.carregarParcelamentos(),i.parcelamentos.some(m=>m.id===e)?setTimeout(()=>{y.showDetalhes(e)},100):i.modalDetalhesInstance&&i.modalDetalhesInstance.hide()}catch(r){console.error("Erro ao excluir item:",r),Swal.fire({icon:"error",title:"Erro",text:h(r,"Não foi possível excluir o item."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")}})}},async excluirParcelamentoCompleto(e,a,t){if((await Swal.fire({title:"Excluir Parcelamento Completo?",html:`
                <p>Deseja realmente excluir <strong>todas as ${t} parcelas</strong> deste parcelamento?</p>
                <p style="color: #ef4444; margin-top: 1rem;"><i data-lucide="triangle-alert"></i> Esta ação não pode ser desfeita!</p>
            `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:`Sim, excluir ${t} parcelas`,cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const r=document.querySelector(".swal2-container");r&&(r.style.zIndex="99999"),C()}})).isConfirmed){Swal.fire({title:"Excluindo parcelamento...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const r=document.querySelector(".swal2-container");r&&(r.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});try{const r=await c.apiRequest(`api/faturas/${e}/itens/${a}/parcelamento`,{method:"DELETE"});await Swal.fire({icon:"success",title:"Parcelamento Excluído!",text:r.message||`${t} parcelas removidas.`,timer:v.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const l=document.querySelector(".swal2-container");l&&(l.style.zIndex="99999")}}),await u.App.carregarParcelamentos(),i.parcelamentos.some(l=>l.id===e)?setTimeout(()=>{y.showDetalhes(e)},100):i.modalDetalhesInstance&&i.modalDetalhesInstance.hide()}catch(r){console.error("Erro ao excluir parcelamento:",r),Swal.fire({icon:"error",title:"Erro",text:h(r,"Não foi possível excluir o parcelamento."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")}})}}},async pagarFaturaCompleta(e,a){try{Swal.fire({title:"Carregando...",html:"Buscando informações da fatura e contas disponíveis.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const p=document.querySelector(".swal2-container");p&&(p.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});const[t,o]=await Promise.all([u.API.buscarParcelamento(e),u.API.listarContas()]),r=b(t,null),s=b(o,[]);if(!r?.cartao)throw new Error("Dados da fatura incompletos");const l=r.cartao.id,d=r.cartao.conta_id||null,m=(r.descricao||"").match(/(\d+)\/(\d+)/),g=m?m[1]:null,w=m?m[2]:null;if(!g||!w)throw new Error("Não foi possível identificar o mês/ano da fatura");let E="";if(Array.isArray(s)&&s.length>0)s.forEach(p=>{const I=p.saldoAtual??p.saldo_atual??p.saldo??0,A=c.formatMoney(I),F=p.id===d,B=I>=a,R=B?"color: #059669;":"color: #dc2626;";E+=`<option value="${p.id}" ${F?"selected":""} ${B?"":'style="color: #dc2626;"'}>
                        ${c.escapeHtml(p.nome)} - ${A}${F?" (vinculada ao cartão)":""}
                    </option>`});else throw new Error("Nenhuma conta disponível para débito");const x=await Swal.fire({title:"Pagar Fatura Completa?",html:`
                    <p>Deseja realmente pagar todos os itens pendentes desta fatura?</p>
                    <div style="margin: 1.5rem 0; padding: 1rem; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                        <div style="font-size: 0.875rem; color: #047857; margin-bottom: 0.5rem;">Valor Total:</div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: #059669;">${c.formatMoney(a)}</div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; text-align: left; margin-bottom: 0.5rem; color: #374151; font-weight: 500;">
                            <i data-lucide="landmark"></i> Conta para débito:
                        </label>
                        <select id="swalContaSelect" class="swal2-select" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                            ${E}
                        </select>
                    </div>
                    <p style="color: #6b7280; font-size: 0.875rem;">O valor será debitado da conta selecionada.</p>
                `,icon:"question",showCancelButton:!0,confirmButtonColor:"#10b981",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="check"></i> Sim, pagar tudo',cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const p=document.querySelector(".swal2-container");p&&(p.style.zIndex="99999"),C()},preConfirm:()=>{const p=document.getElementById("swalContaSelect"),I=p?parseInt(p.value):null;return I?{contaId:I}:(Swal.showValidationMessage("Selecione uma conta para débito"),!1)}});if(!x.isConfirmed)return;const $=x.value.contaId;Swal.fire({title:"Processando pagamento...",html:"Aguarde enquanto processamos o pagamento de todos os itens.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const p=document.querySelector(".swal2-container");p&&(p.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});const S=await u.API.pagarFaturaCompleta(l,parseInt(g),parseInt(w),$);if(!S.success)throw new Error(S.message||"Erro ao processar pagamento");await Swal.fire({icon:"success",title:"Fatura Paga!",html:`
                    <p>${S.message||"Fatura paga com sucesso!"}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor debitado:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${c.formatMoney(b(S,{})?.valor_pago||a)}
                        </div>
                    </div>
                    <div style="color: #059669;">
                        <i data-lucide="circle-check" style="font-size: 2rem;"></i>
                    </div>
                `,timer:3e3,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const p=document.querySelector(".swal2-container");p&&(p.style.zIndex="99999"),C()}}),await u.App.carregarParcelamentos(),i.modalDetalhesInstance.hide()}catch(t){console.error("Erro ao pagar fatura completa:",t),Swal.fire({icon:"error",title:"Erro ao pagar fatura",text:h(t,"Não foi possível processar o pagamento. Tente novamente."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const o=document.querySelector(".swal2-container");o&&(o.style.zIndex="99999")}})}}};u.UI=y;const D={async init(){try{this.initModal(),this.initViewToggle(),this.aplicarFiltrosURL(),await this.carregarCartoes(),await this.carregarParcelamentos(),this.attachEventListeners()}catch(e){console.error("❌ Erro ao inicializar:",e),Swal.fire({icon:"error",title:"Erro de Inicialização",text:"Não foi possível carregar a página. Tente recarregar."})}},initViewToggle(){const e=document.querySelector(".view-toggle"),a=n.containerEl;if(!e||!a)return;const t=e.querySelectorAll(".view-btn"),o=localStorage.getItem("faturas_view_mode")||"grid";o==="list"&&a.classList.add("list-view"),this.updateViewToggleState(t,o);const r=document.getElementById("faturasListHeader");o==="list"&&r&&r.classList.add("visible"),t.forEach(s=>{s.addEventListener("click",()=>{const l=s.dataset.view;l==="list"?(a.classList.add("list-view"),r&&r.classList.add("visible")):(a.classList.remove("list-view"),r&&r.classList.remove("visible")),localStorage.setItem("faturas_view_mode",l),this.updateViewToggleState(t,l)})})},updateViewToggleState(e,a){e.forEach(t=>{t.dataset.view===a?t.classList.add("active"):t.classList.remove("active")})},initModal(){i.modalDetalhesInstance=new bootstrap.Modal(n.modalDetalhes,{backdrop:!0,keyboard:!0,focus:!0}),n.modalDetalhes.addEventListener("show.bs.modal",()=>{document.activeElement?.blur()}),n.modalDetalhes.addEventListener("hidden.bs.modal",()=>{document.activeElement?.blur()}),n.modalDetalhes.addEventListener("click",e=>{const a=e.target.closest(".btn-ver-detalhes-parcela");if(a){e.preventDefault();const t=JSON.parse(a.dataset.parcela),o=a.dataset.descricao;this.mostrarDetalhesParcela(t,o)}})},aplicarFiltrosURL(){const e=new URLSearchParams(window.location.search);if(e.has("cartao_id")&&(i.filtros.cartao_id=e.get("cartao_id"),n.filtroCartao&&(n.filtroCartao.value=i.filtros.cartao_id)),e.has("mes")&&e.has("ano")&&(i.filtros.mes=parseInt(e.get("mes"),10),i.filtros.ano=parseInt(e.get("ano"),10),window.monthPicker)){const a=new Date(i.filtros.ano,i.filtros.mes-1);window.monthPicker.setDate(a)}e.has("status")&&(i.filtros.status=e.get("status"),n.filtroStatus&&(n.filtroStatus.value=i.filtros.status))},async carregarCartoes(){try{const e=await u.API.listarCartoes(),a=b(e,[]);i.cartoes=Array.isArray(a)?a:[],this.preencherSelectCartoes(),this.sincronizarFiltrosComSelects()}catch(e){console.error("❌ Erro ao carregar cartões:",e)}},sincronizarFiltrosComSelects(){n.filtroStatus&&i.filtros.status&&(n.filtroStatus.value=i.filtros.status),n.filtroCartao&&i.filtros.cartao_id&&(n.filtroCartao.value=i.filtros.cartao_id),n.filtroAno&&i.filtros.ano&&(n.filtroAno.value=i.filtros.ano),n.filtroMes&&i.filtros.mes&&(n.filtroMes.value=i.filtros.mes)},preencherSelectCartoes(){n.filtroCartao&&(n.filtroCartao.innerHTML='<option value="">Todos os cartões</option>',i.cartoes.forEach(e=>{const a=document.createElement("option");a.value=e.id;const t=e.nome_cartao||e.nome||e.bandeira||"Cartão",o=e.ultimos_digitos?` •••• ${e.ultimos_digitos}`:"";a.textContent=t+o,n.filtroCartao.appendChild(a)}))},preencherSelectAnos(e=[]){if(!n.filtroAno)return;const a=n.filtroAno.value,t=new Date().getFullYear();if(n.filtroAno.innerHTML='<option value="">Todos os anos</option>',e.length>0){const o=[...e].sort((r,s)=>r-s);o.includes(t)||(o.push(t),o.sort((r,s)=>r-s)),o.forEach(r=>{const s=document.createElement("option");s.value=r,s.textContent=r,n.filtroAno.appendChild(s)})}else{const o=document.createElement("option");o.value=t,o.textContent=t,n.filtroAno.appendChild(o)}a?n.filtroAno.value=a:(n.filtroAno.value=t,i.filtros.ano=t),this.sincronizarFiltrosComSelects()},extrairAnosDisponiveis(e){const a=new Set;return e.forEach(t=>{const r=(t.descricao||"").match(/(\d{1,2})\/(\d{4})/);if(r&&a.add(parseInt(r[2],10)),t.data_vencimento){const s=new Date(t.data_vencimento).getFullYear();a.add(s)}}),Array.from(a)},async carregarParcelamentos(){u.UI.showLoading();try{const e=await u.API.listarParcelamentos({status:i.filtros.status||"",cartao_id:i.filtros.cartao_id||"",mes:i.filtros.mes||"",ano:i.filtros.ano||""}),a=b(e,{});let t=a?.faturas||[];if(i.parcelamentos=t,!i.anosCarregados){const o=a?.anos_disponiveis||this.extrairAnosDisponiveis(t);this.preencherSelectAnos(o),i.anosCarregados=!0}u.UI.renderParcelamentos(t)}catch(e){console.error("❌ Erro ao carregar parcelamentos:",e),u.UI.showEmpty(),Swal.fire({icon:"error",title:"Erro ao Carregar",text:h(e,"Não foi possível carregar os parcelamentos")})}finally{u.UI.hideLoading()}},async cancelarParcelamento(e){try{await u.API.cancelarParcelamento(e),await Swal.fire({icon:"success",title:"Cancelado!",text:"Parcelamento cancelado com sucesso",timer:v.TIMEOUTS.successMessage,showConfirmButton:!1}),await this.carregarParcelamentos()}catch(a){console.error("Erro ao cancelar:",a),Swal.fire({icon:"error",title:"Erro ao Cancelar",text:h(a,"Não foi possível cancelar o parcelamento")})}},attachEventListeners(){n.toggleFilters&&n.toggleFilters.addEventListener("click",o=>{o.stopPropagation(),this.toggleFilters()});const e=document.querySelector(".filters-header");e&&e.addEventListener("click",()=>{this.toggleFilters()}),n.btnFiltrar&&n.btnFiltrar.addEventListener("click",()=>{this.aplicarFiltros()}),n.btnLimparFiltros&&n.btnLimparFiltros.addEventListener("click",()=>{this.limparFiltros()}),[n.filtroStatus,n.filtroCartao,n.filtroAno,n.filtroMes].forEach(o=>{o&&o.addEventListener("keypress",r=>{r.key==="Enter"&&this.aplicarFiltros()})});const a=document.getElementById("btnSalvarItemFatura");a&&a.addEventListener("click",()=>{u.UI.salvarItemFatura()});const t=document.getElementById("formEditarItemFatura");t&&t.addEventListener("submit",o=>{o.preventDefault(),u.UI.salvarItemFatura()})},toggleFilters(){n.filtersContainer&&n.filtersContainer.classList.toggle("collapsed")},aplicarFiltros(){i.filtros.status=n.filtroStatus?.value||"",i.filtros.cartao_id=n.filtroCartao?.value||"",i.filtros.ano=n.filtroAno?.value||"",i.filtros.mes=n.filtroMes?.value||"",this.atualizarBadgesFiltros(),this.carregarParcelamentos()},limparFiltros(){n.filtroStatus&&(n.filtroStatus.value=""),n.filtroCartao&&(n.filtroCartao.value=""),n.filtroAno&&(n.filtroAno.value=""),n.filtroMes&&(n.filtroMes.value=""),i.filtros={status:"",cartao_id:"",ano:"",mes:""},this.atualizarBadgesFiltros(),this.carregarParcelamentos()},atualizarBadgesFiltros(){if(!n.activeFilters)return;const e=[],a=["","Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"];if(i.filtros.status){const t={pendente:"⏳ Pendente",parcial:"🔄 Parcial",paga:"✅ Paga",cancelado:"❌ Cancelado"};e.push({key:"status",label:t[i.filtros.status]||i.filtros.status})}if(i.filtros.cartao_id){const t=i.cartoes.find(r=>r.id==i.filtros.cartao_id),o=t?t.nome_cartao||t.nome:"Cartão";e.push({key:"cartao_id",label:`💳 ${o}`})}i.filtros.ano&&e.push({key:"ano",label:`📅 ${i.filtros.ano}`}),i.filtros.mes&&e.push({key:"mes",label:`📆 ${a[i.filtros.mes]}`}),e.length>0?(n.activeFilters.style.display="flex",n.activeFilters.innerHTML=e.map(t=>`
                <span class="filter-badge">
                    ${t.label}
                    <button class="filter-badge-remove" data-filter="${t.key}" title="Remover filtro">
                        <i data-lucide="x"></i>
                    </button>
                </span>
            `).join(""),window.lucide&&lucide.createIcons(),n.activeFilters.querySelectorAll(".filter-badge-remove").forEach(t=>{t.addEventListener("click",o=>{const r=o.currentTarget.dataset.filter;this.removerFiltro(r)})})):(n.activeFilters.style.display="none",n.activeFilters.innerHTML="")},removerFiltro(e){i.filtros[e]="";const a={status:n.filtroStatus,cartao_id:n.filtroCartao,ano:n.filtroAno,mes:n.filtroMes};a[e]&&(a[e].value=""),this.atualizarBadgesFiltros(),this.carregarParcelamentos()}};u.App=D;const P={instance:null,faturaId:null,valorTotal:null,cartaoId:null,mes:null,ano:null,contas:[],contaPadraoId:null,init(){const e=document.getElementById("modalPagarFatura");e&&(this.instance=new bootstrap.Modal(e),this.attachEvents())},attachEvents(){document.getElementById("btnPagarTotal")?.addEventListener("click",()=>{this.instance.hide(),u.UI.pagarFaturaCompleta(this.faturaId,this.valorTotal)}),document.getElementById("btnPagarParcial")?.addEventListener("click",()=>{this.mostrarFormularioParcial()}),document.getElementById("btnVoltarEscolha")?.addEventListener("click",()=>{this.mostrarEscolha()}),document.getElementById("btnConfirmarPagamento")?.addEventListener("click",()=>{this.confirmarPagamentoParcial()});const e=document.getElementById("valorPagamentoParcial");e&&(e.addEventListener("input",a=>{let t=a.target.value.replace(/\D/g,"");if(t===""){a.target.value="";return}t=(parseInt(t)/100).toFixed(2),a.target.value=parseFloat(t).toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2})}),e.addEventListener("focus",a=>{a.target.select()})),document.querySelectorAll(".btn-opcao-pagamento").forEach(a=>{a.addEventListener("mouseenter",()=>{a.style.transform="translateY(-2px)",a.style.boxShadow="0 8px 25px rgba(0,0,0,0.2)"}),a.addEventListener("mouseleave",()=>{a.style.transform="translateY(0)",a.style.boxShadow="none"})})},async abrir(e,a){this.faturaId=e,this.valorTotal=a,document.getElementById("pagarFaturaId").value=e,document.getElementById("pagarFaturaValorTotal").value=a,document.getElementById("valorTotalDisplay").textContent=c.formatMoney(a),document.getElementById("valorTotalInfo").textContent=`Valor total da fatura: ${c.formatMoney(a)}`,document.getElementById("valorPagamentoParcial").value=c.formatMoney(a).replace("R$ ",""),this.mostrarEscolha(),await this.carregarDados(),this.instance.show()},async carregarDados(){try{const[e,a]=await Promise.all([u.API.buscarParcelamento(this.faturaId),u.API.listarContas()]),t=b(e,null);if(this.contas=b(a,[]),!t?.cartao)throw new Error("Dados da fatura incompletos");this.cartaoId=t.cartao.id,this.contaPadraoId=t.cartao.conta_id||null;const r=(t.descricao||"").match(/(\d+)\/(\d+)/);this.mes=r?parseInt(r[1]):null,this.ano=r?parseInt(r[2]):null,this.popularSelectContas()}catch(e){console.error("Erro ao carregar dados:",e),Swal.fire({icon:"error",title:"Erro",text:h(e,"Erro ao carregar dados da fatura.")})}},popularSelectContas(){const e=document.getElementById("contaPagamentoFatura");if(e){if(e.innerHTML="",!Array.isArray(this.contas)||this.contas.length===0){e.innerHTML='<option value="">Nenhuma conta disponível</option>';return}this.contas.forEach(a=>{const t=a.saldoAtual??a.saldo_atual??a.saldo??0,o=c.formatMoney(t),r=a.id===this.contaPadraoId,s=document.createElement("option");s.value=a.id,s.textContent=`${a.nome} - ${o}${r?" (vinculada ao cartão)":""}`,r&&(s.selected=!0),e.appendChild(s)})}},mostrarEscolha(){document.getElementById("pagarFaturaEscolha").style.display="block",document.getElementById("pagarFaturaFormParcial").style.display="none",document.getElementById("pagarFaturaFooter").style.display="none"},mostrarFormularioParcial(){document.getElementById("pagarFaturaEscolha").style.display="none",document.getElementById("pagarFaturaFormParcial").style.display="block",document.getElementById("pagarFaturaFooter").style.display="flex",setTimeout(()=>{const e=document.getElementById("valorPagamentoParcial");e&&(e.focus(),e.select())},100)},async confirmarPagamentoParcial(){const e=document.getElementById("valorPagamentoParcial").value,a=document.getElementById("contaPagamentoFatura").value,t=parseFloat(e.replace(/\./g,"").replace(",","."))||0;if(t<=0){Swal.fire({icon:"warning",title:"Valor inválido",text:"Digite um valor válido para o pagamento.",timer:2e3,showConfirmButton:!1});return}if(t>this.valorTotal){Swal.fire({icon:"warning",title:"Valor inválido",text:`O valor não pode ser maior que ${c.formatMoney(this.valorTotal)}`,timer:2e3,showConfirmButton:!1});return}if(!a){Swal.fire({icon:"warning",title:"Conta não selecionada",text:"Selecione uma conta para débito.",timer:2e3,showConfirmButton:!1});return}if(!this.cartaoId||!this.mes||!this.ano){Swal.fire({icon:"error",title:"Erro",text:"Dados da fatura incompletos. Tente novamente."});return}this.instance.hide(),Swal.fire({title:"Processando pagamento...",html:"Aguarde enquanto processamos o pagamento.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>Swal.showLoading()});try{const o=await c.apiRequest(`api/cartoes/${this.cartaoId}/fatura/pagar`,{method:"POST",body:JSON.stringify({mes:this.mes,ano:this.ano,conta_id:parseInt(a),valor_parcial:t})});if(!o.success)throw new Error(h(o,"Erro ao processar pagamento"));await Swal.fire({icon:"success",title:"Pagamento Realizado!",html:`
                    <p>${o.message||"Pagamento efetuado com sucesso!"}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor pago:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${c.formatMoney(t)}
                        </div>
                    </div>
                `,timer:3e3,showConfirmButton:!1}),await u.App.carregarParcelamentos(),i.modalDetalhesInstance&&i.modalDetalhesInstance.hide()}catch(o){console.error("Erro ao pagar fatura:",o),Swal.fire({icon:"error",title:"Erro ao pagar fatura",text:h(o,"Não foi possível processar o pagamento. Tente novamente.")})}}};async function O(e){const a=i.faturaAtual;if(!a||!a.cartao||!a.mes_referencia||!a.ano_referencia){Swal.fire({icon:"error",title:"Erro",text:"Dados da fatura incompletos para reverter o pagamento."});return}if((await Swal.fire({title:"Desfazer Pagamento?",html:`
            <p>Você está prestes a <strong>reverter o pagamento</strong> de todos os itens desta fatura.</p>
            <div style="margin: 1rem 0; padding: 0.75rem; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <p style="margin: 0; color: #92400e; font-size: 0.875rem;">
                    <i data-lucide="triangle-alert"></i> 
                    O lançamento de pagamento será excluído e o valor voltará para a conta.
                </p>
            </div>
        `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#f59e0b",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="undo-2"></i> Sim, reverter',cancelButtonText:"Cancelar",didOpen:()=>{window.lucide&&lucide.createIcons()}})).isConfirmed)try{Swal.fire({title:"Revertendo pagamento...",html:"Aguarde enquanto processamos a reversão.",allowOutsideClick:!1,didOpen:()=>Swal.showLoading()});const o=a.cartao.id,r=a.mes_referencia,s=a.ano_referencia,l=await c.apiRequest(`api/cartoes/${o}/fatura/desfazer-pagamento`,{method:"POST",body:JSON.stringify({mes:r,ano:s})});if(l.success)await Swal.fire({icon:"success",title:"Pagamento Revertido!",html:`
                    <p>${l.message||"O pagamento foi revertido com sucesso."}</p>
                    <p style="color: #059669; margin-top: 0.5rem;">
                        <i data-lucide="circle-check"></i> 
                        ${b(l,{})?.itens_revertidos||0} item(s) voltou(aram) para pendente.
                    </p>
                `,timer:3e3,showConfirmButton:!1,didOpen:()=>{window.lucide&&lucide.createIcons()}}),i.modalDetalhesInstance&&i.modalDetalhesInstance.hide(),await u.App.carregarParcelamentos();else throw new Error(h(l,"Erro ao reverter pagamento"))}catch(o){console.error("Erro ao reverter pagamento:",o),Swal.fire({icon:"error",title:"Erro",text:h(o,"Não foi possível reverter o pagamento.")})}}async function z(e){if((await Swal.fire({title:"Excluir Fatura?",html:`
            <p>Você está prestes a excluir esta fatura e <strong>todos os seus itens pendentes</strong>.</p>
            <p style="color: #ef4444; font-weight: 500;">Esta ação não pode ser desfeita!</p>
        `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="trash-2"></i> Sim, excluir',cancelButtonText:"Cancelar",didOpen:()=>{window.lucide&&lucide.createIcons()}})).isConfirmed)try{const t=await c.apiRequest(`api/faturas/${e}`,{method:"DELETE"});if(t.success)Swal.fire({icon:"success",title:"Fatura Excluída!",text:"A fatura foi excluída com sucesso.",timer:2e3,showConfirmButton:!1}),i.modalDetalhesInstance&&i.modalDetalhesInstance.hide(),u.App.carregarParcelamentos();else throw new Error(h(t,"Erro ao excluir fatura"))}catch(t){console.error("Erro ao excluir fatura:",t),Swal.fire({icon:"error",title:"Erro",text:h(t,"Não foi possível excluir a fatura.")})}}async function N(e,a){if((await Swal.fire({title:"Excluir Item?",html:`
            <p>Você está prestes a excluir este item da fatura.</p>
            <p style="color: #ef4444; font-weight: 500;">Esta ação não pode ser desfeita!</p>
        `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="trash-2"></i> Sim, excluir',cancelButtonText:"Cancelar",customClass:{container:"swal-above-modal"},didOpen:()=>{window.lucide&&lucide.createIcons()}})).isConfirmed)try{const o=await c.apiRequest(`api/faturas/${e}/itens/${a}`,{method:"DELETE"});if(o.success)Swal.fire({icon:"success",title:"Item Excluído!",text:"O item foi excluído com sucesso.",timer:2e3,showConfirmButton:!1,customClass:{container:"swal-above-modal"}}),u.App.carregarParcelamentos(),i.faturaAtual&&setTimeout(()=>{u.UI.abrirDetalhes(e)},500);else throw new Error(h(o,"Erro ao excluir item"))}catch(o){console.error("Erro ao excluir item:",o),Swal.fire({icon:"error",title:"Erro",text:h(o,"Não foi possível excluir o item."),customClass:{container:"swal-above-modal"}})}}u.ModalPagarFatura=P;window.abrirModalPagarFatura=(e,a)=>P.abrir(e,a);window.reverterPagamentoFaturaGlobal=O;window.excluirFaturaGlobal=z;window.excluirItemFaturaGlobal=N;window.pagarFaturaCompletaGlobal=(e,a)=>y.pagarFaturaCompleta(e,a);window.FaturasModule={toggleCardDetalhes:e=>y.showDetalhes(e),excluirItemFatura:(...e)=>y.excluirItemFatura(...e),editarItemFatura:(...e)=>y.editarItemFatura(...e),toggleParcelaPaga:(...e)=>y.toggleParcelaPaga(...e)};window.__LK_PARCELAMENTOS_LOADER__||(window.__LK_PARCELAMENTOS_LOADER__=!0,document.addEventListener("DOMContentLoaded",()=>{L(),D.init(),P.init()}));
