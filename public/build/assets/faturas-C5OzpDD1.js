import{a as J,g as G,j as E,e as h}from"./api-CiEmwEpk.js";import{r as P}from"./ui-H2yoVZe7.js";import{c as K,p as X,f as Y}from"./ui-preferences-CsiHVLYn.js";function W(e){return{house:"#f97316",utensils:"#ef4444",car:"#3b82f6",lightbulb:"#eab308","heart-pulse":"#ef4444","graduation-cap":"#6366f1",shirt:"#ec4899",clapperboard:"#a855f7","credit-card":"#0ea5e9",smartphone:"#6366f1","shopping-cart":"#f97316",coins:"#eab308",briefcase:"#3b82f6",laptop:"#06b6d4","trending-up":"#22c55e",gift:"#ec4899",banknote:"#22c55e",trophy:"#f59e0b",wallet:"#14b8a6",tag:"#94a3b8","pie-chart":"#8b5cf6","piggy-bank":"#ec4899",plane:"#0ea5e9","gamepad-2":"#a855f7",baby:"#f472b6",dog:"#92400e",wrench:"#64748b",church:"#6366f1",dumbbell:"#ef4444",music:"#a855f7","book-open":"#3b82f6",scissors:"#ec4899","building-2":"#64748b",landmark:"#3b82f6",receipt:"#14b8a6"}[e]||"#f97316"}const b={BASE_URL:(window.BASE_URL||"/").replace(/\/?$/,"/"),ENDPOINTS:{parcelamentos:"api/faturas",categorias:"api/categorias",contas:"api/contas",cartoes:"api/cartoes"},TIMEOUTS:{alert:5e3,successMessage:2e3}},n={};function M(e){const a=document.getElementById(e);return a?(window.LK?.modalSystem?.prepareBootstrapModal(a,{scope:"page"}),a):null}function Q(){n.loadingEl=document.getElementById("loadingParcelamentos"),n.containerEl=document.getElementById("parcelamentosContainer"),n.emptyStateEl=document.getElementById("emptyState"),n.filtroStatus=document.getElementById("filtroStatus"),n.filtroCartao=document.getElementById("filtroCartao"),n.filtroAno=document.getElementById("filtroAno"),n.filtroMes=document.getElementById("filtroMes"),n.btnFiltrar=document.getElementById("btnFiltrar"),n.btnLimparFiltros=document.getElementById("btnLimparFiltros"),n.filtersContainer=document.querySelector(".filters-modern"),n.filtersBody=document.getElementById("filtersBody"),n.toggleFilters=document.getElementById("toggleFilters"),n.activeFilters=document.getElementById("activeFilters"),n.modalDetalhes=M("modalDetalhesParcelamento"),n.modalPagarFatura=M("modalPagarFatura"),n.modalEditarItemFatura=M("modalEditarItemFatura"),n.detalhesContent=document.getElementById("detalhesParcelamentoContent")}const l={parcelamentos:[],cartoes:[],faturaAtual:null,sortColumn:"data_compra",sortDirection:"asc",filtros:{status:"",cartao_id:"",ano:new Date().getFullYear(),mes:""},modalDetalhesInstance:null,anosCarregados:!1},m={},c={formatMoney(e){return new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(e||0)},formatDate(e){return e?new Date(e+"T00:00:00").toLocaleDateString("pt-BR"):""},parseMoney(e){return e&&parseFloat(e.replace(/[^\d,]/g,"").replace(",","."))||0},showAlert(e,a,t="danger"){e&&(e.className=`alert alert-${t}`,e.textContent=a,e.style.display="block",setTimeout(()=>{e.style.display="none"},b.TIMEOUTS.alert))},getCSRFToken(){return G()},escapeHtml(e){if(!e)return"";const a=document.createElement("div");return a.textContent=e,a.innerHTML},buildUrl(e,a={}){const t=e.startsWith("http")?e:b.BASE_URL+e.replace(/^\//,""),o=Object.entries(a).filter(([r,s])=>s!=null&&s!=="").map(([r,s])=>`${r}=${encodeURIComponent(s)}`);return o.length>0?`${t}?${o.join("&")}`:t},async apiRequest(e,a={}){const t=e.startsWith("http")?e:b.BASE_URL+e.replace(/^\//,"");try{return await J(t,{...a,headers:{"X-CSRF-Token":this.getCSRFToken(),...a.headers}})}catch(o){throw console.error("Erro na requisição:",o),o}},debounce(e,a){let t;return function(...r){const s=()=>{clearTimeout(t),e(...r)};clearTimeout(t),t=setTimeout(s,a)}},calcularDiferencaDias(e,a){const t=new Date(e+"T00:00:00"),o=new Date(a+"T00:00:00");return Math.floor((t-o)/(1e3*60*60*24))}},Z={async listarParcelamentos(e={}){const a={status:e.status,cartao_id:e.cartao_id,ano:e.ano,mes:e.mes},t=c.buildUrl(b.ENDPOINTS.parcelamentos,a);return await c.apiRequest(t)},async listarCartoes(){return await c.apiRequest(b.ENDPOINTS.cartoes)},async buscarParcelamento(e){const a=parseInt(e,10);if(isNaN(a))throw new Error("ID inválido");return await c.apiRequest(`${b.ENDPOINTS.parcelamentos}/${a}`)},async criarParcelamento(e){return await c.apiRequest(b.ENDPOINTS.parcelamentos,{method:"POST",body:JSON.stringify(e)})},async cancelarParcelamento(e){return await c.apiRequest(`${b.ENDPOINTS.parcelamentos}/${e}`,{method:"DELETE"})},async toggleItemFatura(e,a,t){return await c.apiRequest(`${b.ENDPOINTS.parcelamentos}/${e}/itens/${a}/toggle`,{method:"POST",body:JSON.stringify({pago:t})})},async pagarFaturaCompleta(e,a,t,o=null){const r={mes:a,ano:t};return o&&(r.conta_id=o),await c.apiRequest(`${b.ENDPOINTS.cartoes}/${e}/fatura/pagar`,{method:"POST",body:JSON.stringify(r)})},async listarContas(){return await c.apiRequest(`${b.ENDPOINTS.contas}?with_balances=1`)}};m.API=Z;const R=(e,a=0,t=100)=>Math.min(t,Math.max(a,Number(e)||0)),g=(e,a="")=>c.escapeHtml(String(e??a)),ee=(e,a=0)=>`${(Number(e)||0).toLocaleString("pt-BR",{minimumFractionDigits:a,maximumFractionDigits:a})}%`,_=(e,a)=>`data-lk-tooltip-title="${g(e)}" data-lk-tooltip="${g(a)}"`,q=/(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|hsla?\([^)]+\))/,V=()=>window.LK?.getBase?.()||"/",ae={renderParcelamentos(e){if(!Array.isArray(e)||e.length===0){this.showEmpty();return}n.emptyStateEl.style.display="none",n.containerEl.style.display="grid";const a=document.createDocumentFragment();e.forEach(t=>{const o=this.createParcelamentoCard(t);a.appendChild(o)}),n.containerEl.innerHTML="",n.containerEl.appendChild(a),P()},createParcelamentoCard(e){const a=e.progresso||0,t=e.parcelas_pendentes||0,o=e.parcelas_pagas||0,r=o+t,s=this.getDueMeta(e),i=this.getStatusMeta(e.status,a,s),d=document.createElement("div");d.className=`parcelamento-card surface-card surface-card--interactive surface-card--clip status-${e.status}`,d.dataset.id=e.id,d.style.setProperty("--fatura-accent",this.getAccentColorSolid(e.cartao));const f=this.getStatusBadge(e.status,a,s),u=e.mes_referencia||"",v=e.ano_referencia||"";return d.innerHTML=this.createCardHTML({parc:e,statusBadge:f,mes:u,ano:v,itensPendentes:t,itensPagos:o,totalItens:r,progresso:a,dueMeta:s,statusMeta:i}),this.attachCardEventListeners(d,e.id),d},attachCardEventListeners(e,a){const t=e.querySelector('[data-action="view"]');t&&t.addEventListener("click",()=>this.showDetalhes(a))},getAccentColorSolid(e){const t={visa:"#1A1F71",mastercard:"#EB001B",elo:"#FFCB05",amex:"#006FCF",diners:"#0079BE",discover:"#FF6000",hipercard:"#B11116"}[e?.bandeira?.toLowerCase()]||"#3b82f6",o=String(e?.cor_cartao||e?.conta?.instituicao_financeira?.cor_primaria||t).trim();return o?/gradient/i.test(o)?o.match(q)?.[1]||t:/^var\(/i.test(o)||q.test(o)?o:t:t},getBandeiraIcon(e){return{visa:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71"/><text x="24" y="20" text-anchor="middle" font-size="12" font-weight="bold" fill="#fff" font-family="sans-serif">VISA</text></svg>',mastercard:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71" opacity="0"/><circle cx="19" cy="16" r="10" fill="#EB001B" opacity=".85"/><circle cx="29" cy="16" r="10" fill="#F79E1B" opacity=".85"/></svg>',elo:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#000"/><text x="24" y="20" text-anchor="middle" font-size="13" font-weight="bold" fill="#FFCB05" font-family="sans-serif">elo</text></svg>',amex:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#006FCF"/><text x="24" y="20" text-anchor="middle" font-size="9" font-weight="bold" fill="#fff" font-family="sans-serif">AMEX</text></svg>',hipercard:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#B11116"/><text x="24" y="20" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff" font-family="sans-serif">HIPER</text></svg>',diners:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#0079BE"/><text x="24" y="20" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff" font-family="sans-serif">DINERS</text></svg>'}[e]||'<i data-lucide="credit-card"></i>'},getDueMeta(e){let a=e.data_vencimento;if(!a&&e.cartao?.dia_vencimento&&e.descricao){const f=e.descricao.match(/(\d{1,2})\/(\d{4})/);if(f){const u=f[1].padStart(2,"0"),v=f[2],y=String(e.cartao.dia_vencimento).padStart(2,"0");a=`${v}-${u}-${y}`}}if(!a)return{hasDate:!1,label:"A definir",helper:"Sem data de vencimento informada",detailClass:"",isVencida:!1,isProxima:!1};const t=c.formatDate(a),o=new Date;o.setHours(0,0,0,0);const r=new Date(`${a}T00:00:00`),s=e.status!=="paga"&&e.status!=="concluido"&&e.status!=="cancelado",i=s&&r<o,d=s&&!i&&r-o<=4320*60*1e3;return{hasDate:!0,raw:a,label:t,helper:i?"Vencimento expirado":d?"Vence em breve":"Dentro do prazo",detailClass:i?"is-danger":d?"is-warning":"",isVencida:i,isProxima:d}},getStatusMeta(e,a=null,t=null){const o=R(a);return e==="cancelado"?{badgeClass:"badge-cancelado",progressClass:"is-muted",icon:"ban",label:"Cancelada",shortLabel:"Cancelada",hint:"Sem cobranca ativa",tooltip:"Esta fatura foi cancelada e nao entra mais no acompanhamento ativo."}:o>=100||e==="paga"||e==="concluido"?{badgeClass:"badge-paga",progressClass:"is-safe",icon:"circle-check",label:"Paga",shortLabel:"Liquidada",hint:"Pagamento concluido",tooltip:"O valor desta fatura ja foi quitado integralmente."}:t?.isVencida?{badgeClass:"badge-alerta",progressClass:"is-danger",icon:"triangle-alert",label:"Vencida",shortLabel:"Em atraso",hint:"Regularize esta fatura",tooltip:"A fatura passou do vencimento e merece prioridade para evitar juros."}:t?.isProxima?{badgeClass:"badge-alerta",progressClass:"is-warning",icon:"clock-3",label:"Vence em breve",shortLabel:"Vence logo",hint:"Priorize o pagamento",tooltip:"O vencimento esta proximo. Vale organizar o pagamento desta fatura."}:o>0?{badgeClass:"badge-parcial",progressClass:"is-warning",icon:"loader-2",label:"Pagamento parcial",shortLabel:"Parcial",hint:"Parte do valor ja foi paga",tooltip:"A fatura segue aberta, mas ja possui pagamentos registrados."}:{badgeClass:"badge-pendente",progressClass:"is-safe",icon:"clock-3",label:"Pendente",shortLabel:"No prazo",hint:"Aguardando pagamento",tooltip:"A fatura segue aberta e ainda esta dentro do prazo normal de pagamento."}},getResumoPrincipal(e,a,t,o,r,s){const i=e.total_estornos&&e.total_estornos>0,d=s>0?`${r} de ${s} itens pagos`:"Sem itens consolidados",f=a.hasDate&&a.helper!=="Dentro do prazo"?`<span class="fatura-card-due-tag ${a.detailClass}">${g(a.helper)}</span>`:"";return`
            <div class="fatura-card-main">
                <span class="resumo-label">Valor total da fatura</span>
                <strong class="resumo-valor">${c.formatMoney(e.valor_total)}</strong>
                <div class="fatura-card-due-line ${a.detailClass}">
                    <span class="fatura-card-due-copy">Vencimento ${g(a.label)}</span>
                    ${f}
                </div>
                ${i?`
                    <p class="fatura-card-note">
                        Inclui ${c.formatMoney(e.total_estornos)} em estornos no fechamento.
                    </p>
                `:""}
            </div>

            <div class="fatura-card-details">
                <div class="fatura-card-detail ${a.detailClass}" ${_("Vencimento",a.hasDate?`Data prevista para pagamento desta fatura: ${a.label}.`:"A fatura ainda nao possui data de vencimento consolidada.")}>
                    <span class="fatura-card-detail-label">Vencimento</span>
                    <strong class="fatura-card-detail-value">${g(a.label)}</strong>
                    <span class="fatura-card-detail-meta">${g(a.helper)}</span>
                </div>

                <div class="fatura-card-detail ${t.progressClass}" ${_("Progresso de pagamento",s>0?`${r} de ${s} itens ja foram pagos nesta fatura.`:"Ainda nao existem itens suficientes para calcular o progresso de pagamento.")}>
                    <span class="fatura-card-detail-label">Pagamento</span>
                    <strong class="fatura-card-detail-value">${s>0?`${r}/${s}`:"--"}</strong>
                    <span class="fatura-card-detail-meta">${g(d)}</span>
                </div>
            </div>
        `},getProgressoSection(e,a,t,o,r){const s=R(o),i=s>0?Math.max(s,8):0;return e===0?`
                <div class="parc-progress-section is-empty">
                    <div class="parc-progress-header">
                        <span class="parc-progress-text">Sem itens suficientes para medir o pagamento</span>
                        <span class="parc-progress-percent">--</span>
                    </div>
                    <div class="parc-progress-bar">
                        <div class="parc-progress-fill ${r.progressClass}" style="width: 0%"></div>
                    </div>
                </div>
            `:`
            <div class="parc-progress-section ${r.progressClass}">
                <div class="parc-progress-header">
                    <span class="parc-progress-text">Pagamento ${ee(s)}</span>
                    <span class="parc-progress-percent">${g(r.shortLabel)}</span>
                </div>
                <div class="parc-progress-bar">
                    <div class="parc-progress-fill ${r.progressClass}" style="width: ${i}%"></div>
                </div>
                <div class="parc-progress-foot">
                    <span>${t} de ${e} itens pagos</span>
                    <span>${a} em aberto</span>
                </div>
            </div>
        `},getStatusBadge(e,a=null,t=null){const o=this.getStatusMeta(e,a,t);return`
            <span
                class="parc-card-badge ${o.badgeClass}"
                ${_(o.label,o.tooltip)}>
                <i data-lucide="${o.icon}" style="width:12px;height:12px"></i>
                ${g(o.label)}
            </span>
        `},createCardHTML({parc:e,statusBadge:a,mes:t,ano:o,itensPendentes:r,itensPagos:s,totalItens:i,progresso:d,dueMeta:f,statusMeta:u}){const v=this.getResumoPrincipal(e,f,u,r,s,i),y=this.getProgressoSection(i,r,s,d,u),w=Number.parseInt(String(e.cartao?.id??e.cartao_id??0),10)||0,x=w>0?`${V()}importacoes?import_target=cartao&source_type=ofx&cartao_id=${w}`:`${V()}importacoes?import_target=cartao&source_type=ofx`,B=e.cartao&&(e.cartao.nome||e.cartao.bandeira)||"Cartao",C=e.cartao?.conta?.instituicao_financeira?.nome||"Sem instituicao",p=e.cartao?.ultimos_digitos?`Final ${e.cartao.ultimos_digitos}`:"",I=this.getAccentColorSolid(e.cartao),L=e.cartao?.bandeira?.toLowerCase()||"outros",A=this.getBandeiraIcon(L),F=t&&o?`${t}/${o}`:"Fatura atual",N=[g(C),p?g(p):""].filter(Boolean).join(" - ");return`
            <div class="fatura-card-shell" style="--fatura-accent:${I};">
                <div class="fatura-card-top">
                    <div class="fatura-card-media">
                        <div class="fatura-card-brand" aria-hidden="true">
                            ${A}
                        </div>
                    </div>

                    <div class="fatura-card-head">
                        <div class="fatura-card-title-wrap">
                            <span class="fatura-card-title">${g(B)}</span>
                            <span class="fatura-card-subtitle">${g(C)}</span>
                        </div>
                        <div class="fatura-card-meta">
                            <span class="fatura-card-period" ${_("Periodo da fatura","Competencia consolidada desta fatura para acompanhar fechamento e vencimento.")}>
                                <i data-lucide="calendar-days"></i>
                                <span>${g(F)}</span>
                            </span>
                            ${a}
                        </div>
                    </div>
                </div>

                <div class="fatura-list-info">
                    <span class="list-cartao-nome">${g(B)}</span>
                    <span class="list-periodo">${g(F)}</span>
                    <span class="list-cartao-numero">${N}</span>
                </div>

                <div class="fatura-resumo-principal">${v}</div>
                ${y}
                <div class="fatura-status-col">${a}</div>
                <div class="parc-card-actions">
                    <a
                        class="parc-btn parc-btn-import"
                        href="${g(x)}"
                        data-no-transition="true"
                        title="Importar OFX desta fatura/cartão"
                    >
                        <i data-lucide="upload"></i>
                        <span>Importar OFX</span>
                    </a>
                    <button class="parc-btn parc-btn-view" data-action="view" data-id="${e.id}">
                        <i data-lucide="eye"></i>
                        <span>Ver detalhes</span>
                    </button>
                </div>
            </div>
        `}},te={async showDetalhes(e){try{const a=await m.API.buscarParcelamento(e),t=E(a,null);if(!t){l.modalDetalhesInstance&&l.modalDetalhesInstance.hide();return}l.faturaAtual=t;const o=n.modalDetalhes;if(o&&t.cartao){const r=this.getAccentColorSolid(t.cartao),s=o.querySelector(".modal-content");s&&s.style.setProperty("--card-accent",r)}n.detalhesContent.innerHTML=this.renderDetalhes(t),P(),this.attachDetalhesEventListeners(t.id),document.activeElement?.blur(),l.modalDetalhesInstance.show()}catch(a){if(console.error("Erro ao abrir detalhes:",a),a.message&&a.message.includes("404")){l.modalDetalhesInstance&&l.modalDetalhesInstance.hide();return}Swal.fire({icon:"error",title:"Erro",text:h(a,"Não foi possível carregar os detalhes da fatura")})}},attachDetalhesEventListeners(e){n.detalhesContent.querySelectorAll(".th-sortable").forEach(s=>{s.addEventListener("click",()=>{const i=s.dataset.sort;l.sortColumn===i?l.sortDirection=l.sortDirection==="asc"?"desc":"asc":(l.sortColumn=i,l.sortDirection="asc"),l.faturaAtual&&(n.detalhesContent.innerHTML=this.renderDetalhes(l.faturaAtual),P(),this.attachDetalhesEventListeners(e))})}),n.detalhesContent.querySelectorAll(".btn-pagar, .btn-desfazer").forEach(s=>{s.addEventListener("click",async i=>{const d=parseInt(i.currentTarget.dataset.lancamentoId,10),f=i.currentTarget.dataset.pago==="true";await this.toggleParcelaPaga(e,d,!f)})}),n.detalhesContent.querySelectorAll(".btn-editar").forEach(s=>{s.addEventListener("click",async i=>{const d=parseInt(i.currentTarget.dataset.lancamentoId,10),f=i.currentTarget.dataset.descricao||"",u=parseFloat(i.currentTarget.dataset.valor)||0;await this.editarItemFatura(e,d,f,u)})}),n.detalhesContent.querySelectorAll(".btn-excluir").forEach(s=>{s.addEventListener("click",async i=>{const d=parseInt(i.currentTarget.dataset.lancamentoId,10),f=i.currentTarget.dataset.ehParcelado==="true",u=parseInt(i.currentTarget.dataset.totalParcelas)||1;await this.excluirItemFatura(e,d,f,u)})})},renderDetalhes(e){const a=e.progresso||0,{valorPago:t,valorRestante:o}=this.calcularValores(e),r=e.parcelas_pendentes>0&&o>0;return`
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
        `},renderParcelasTabela(e){const a=r=>l.sortColumn===r?l.sortDirection==="asc"?'<i data-lucide="arrow-up" class="sort-icon active"></i>':'<i data-lucide="arrow-down" class="sort-icon active"></i>':'<i data-lucide="arrow-up-down" class="sort-icon"></i>',t=this.sortParcelas(e.parcelas||[]);let o=`
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
        `,e.parcelas&&e.parcelas.length>0?this.sortParcelas(e.parcelas).forEach((s,i)=>{o+=this.renderParcelaCard(s,i,e.descricao)}):o+=`
                <div class="parcela-card-empty">
                    <p>Nenhuma parcela encontrada</p>
                </div>
            `,o+="</div>",o},sortParcelas(e){if(!e||e.length===0)return[];const a=[...e],t=l.sortDirection==="asc"?1:-1,o=l.sortColumn;return a.sort((r,s)=>{if(o==="descricao"){const i=(r.descricao||"").toLowerCase(),d=(s.descricao||"").toLowerCase();return i.localeCompare(d)*t}if(o==="data_compra"){const i=r.data_compra||"0000-00-00",d=s.data_compra||"0000-00-00";return i.localeCompare(d)*t}if(o==="valor"){const i=parseFloat(r.valor_parcela||r.valor||0),d=parseFloat(s.valor_parcela||s.valor||0);return(i-d)*t}return 0}),a},renderParcelaCard(e,a,t){const o=e.pago,r=e.tipo==="estorno",s=o?"parcela-paga":"parcela-pendente",i=o?"✅ Paga":"⏳ Pendente",d=o?"parcela-card-paga":"",f=`${this.getNomeMes(e.mes_referencia)}/${e.ano_referencia}`,u=`parcela-card-${e.id||a}`;let v=e.descricao||t;v=v.replace(/\s*\(\d+\/\d+\)\s*$/,"");let y="";if(e.categoria){const w=e.categoria.icone||"tag",x=e.categoria.nome||e.categoria;y=`<i data-lucide="${w}" style="width:14px;height:14px;display:inline-block;vertical-align:middle;color:${W(w)}"></i> ${c.escapeHtml(x)}`}return r?`
                <div class="parcela-card" id="${u}" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%); border-color: rgba(16, 185, 129, 0.4);">
                    <div class="parcela-card-header">
                        <span class="parcela-numero" style="color: #10b981;">↩️ Estorno</span>
                        <span class="parcela-paga" style="background: #10b981;">✅ Creditado</span>
                    </div>
                    <div class="parcela-card-body">
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Descrição</span>
                            <span class="parcela-card-value" style="color: #10b981;">${c.escapeHtml(v)}</span>
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
            <div class="parcela-card ${d}" id="${u}">
                <div class="parcela-card-header">
                    <span class="parcela-numero">${e.recorrente?'<i data-lucide="refresh-cw" style="width:12px;height:12px;display:inline-block;vertical-align:middle;color:var(--primary, #e67e22);margin-right:3px;"></i> Recorrente':`${e.numero_parcela||a+1}/${e.total_parcelas||1}`}</span>
                    <span class="${s}">${i}</span>
                </div>
                <div class="parcela-card-body">
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Descrição</span>
                        <span class="parcela-card-value">${c.escapeHtml(v)}${e.recorrente?' <span class="badge-recorrente" title="Assinatura recorrente" style="display:inline-flex;align-items:center;background:rgba(230,126,34,0.15);border-radius:6px;padding:1px 6px;margin-left:6px;"><i data-lucide="refresh-cw" style="width:12px;height:12px;color:var(--primary, #e67e22);"></i></span>':""}</span>
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
                <div class="parcela-card-detalhes" id="detalhes-${u}" style="display: none;">
                    ${y?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Categoria</span>
                        <span class="parcela-card-value">${y}</span>
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
        `},renderParcelaRow(e,a,t){const o=e.pago,r=e.tipo==="estorno",s=o?"tr-paga":"";`${this.getNomeMes(e.mes_referencia)}${e.ano_referencia}`,this.getDataPagamentoInfo(e);let i=e.descricao||t;i=i.replace(/\s*\(\d+\/\d+\)\s*$/,""),e.categoria&&(i=e.categoria.nome||e.categoria);const d=e.data_compra?c.formatDate(e.data_compra):"-";return r?`
                <tr class="tr-estorno" style="background: rgba(16, 185, 129, 0.1);">
                    <td data-label="#">
                        <span class="parcela-numero" style="color: #10b981;">↩️</span>
                    </td>
                    <td data-label="Descrição" class="td-descricao">
                        <div class="parcela-desc" style="color: #10b981;">${c.escapeHtml(i)}</div>
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
                    <div class="parcela-desc">${c.escapeHtml(i)}${e.recorrente?' <span class="badge-recorrente" style="display:inline-flex;align-items:center;background:rgba(230,126,34,0.15);border-radius:6px;padding:1px 6px;margin-left:6px;"><i data-lucide="refresh-cw" style="width:12px;height:12px;color:var(--primary, #e67e22);"></i></span>':""}</div>
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
            `}},getNomeMes(e){return["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"][e-1]||e},mostrarDetalhesParcela(e,a){const t=e.pago,o=t?"✅":"⏳",r=t?"Paga":"Pendente",s=t?"#10b981":"#f59e0b",i=`${this.getNomeMesCompleto(e.mes_referencia)}/${e.ano_referencia}`;let d="";t&&e.data_pagamento&&(d=`
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
                        <span class="detalhes-value" style="display: block; font-weight: 600; color: #1f2937;">${i}</span>
                    </div>

                    ${d}
                </div>
            `,icon:!1,confirmButtonText:"Fechar",confirmButtonColor:"#6366f1",width:"500px"})},getNomeMesCompleto(e){return["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"][e-1]||e}};let D=null,S=null,T=null;function O(){return{modalEl:document.getElementById("modalDeleteFaturaItemScope"),formEl:document.getElementById("deleteFaturaItemScopeForm"),titleEl:document.getElementById("modalDeleteFaturaItemScopeLabel"),subtitleEl:document.getElementById("deleteFaturaItemScopeModalSubtitle"),leadEl:document.getElementById("deleteFaturaItemScopeModalLead"),hintEl:document.getElementById("deleteFaturaItemScopeModalHint"),optionsEl:document.getElementById("deleteFaturaItemScopeOptions"),confirmButtonEl:document.getElementById("btnConfirmDeleteFaturaItemScope")}}function oe(){const{formEl:e,optionsEl:a}=O();e?.reset();const t=a?.querySelector('input[value="item"]');t&&(t.checked=!0),a&&(a.hidden=!1)}function re(e=1){const{titleEl:a,subtitleEl:t,leadEl:o,hintEl:r,optionsEl:s,confirmButtonEl:i}=O(),d=Number(e)>1;if(a&&(a.textContent="Excluir item da fatura"),t&&(t.textContent=d?`Este item faz parte de um parcelamento de ${e}x.`:"Revise a exclusão antes de confirmar."),o&&(o.textContent=d?"Escolha se deseja remover apenas esta parcela ou o parcelamento completo.":"Esta ação não pode ser desfeita."),r&&(r.textContent=d?"Excluir todo o parcelamento remove todas as parcelas vinculadas a esta compra.":"O item será removido permanentemente da fatura."),i&&(i.textContent=d?"Continuar":"Excluir item"),s){s.hidden=!d;const u=s.querySelector('[data-delete-fatura-scope-title="item"]'),v=s.querySelector('[data-delete-fatura-scope-text="item"]'),y=s.querySelector('[data-delete-fatura-scope-title="parcelamento"]'),w=s.querySelector('[data-delete-fatura-scope-text="parcelamento"]');u&&(u.textContent="Apenas esta parcela"),v&&(v.textContent="Remove somente o item atual da fatura."),y&&(y.textContent=`Todo o parcelamento (${e} parcelas)`),w&&(w.textContent="Remove todas as parcelas vinculadas a esta compra parcelada.")}const f=s?.querySelector('input[value="item"]');f&&(f.checked=!0)}function se(){const e=O();return D?{modal:D,...e}:!e.modalEl||!window.bootstrap?.Modal?null:(window.LK?.modalSystem?.prepareBootstrapModal(e.modalEl,{scope:"page"}),e.modalEl.dataset.bound||(e.modalEl.dataset.bound="1",e.formEl?.addEventListener("submit",a=>{a.preventDefault(),T={scope:e.optionsEl?.querySelector('input[name="deleteFaturaItemScopeOption"]:checked')?.value||"item"},D?.hide()}),e.modalEl.addEventListener("hidden.bs.modal",()=>{const a=S,t=T;S=null,T=null,oe(),typeof a=="function"&&a(t||null)})),D=window.bootstrap.Modal.getOrCreateInstance(e.modalEl,{backdrop:!0,keyboard:!0,focus:!0}),{modal:D,...e})}function ne(e=1){const a=se();return a?(typeof S=="function"&&S(null),S=null,T=null,re(e),new Promise(t=>{S=t,a.modal.show(),requestAnimationFrame(()=>{(Number(e)>1?a.optionsEl?.querySelector('input[name="deleteFaturaItemScopeOption"]:checked'):a.confirmButtonEl)?.focus?.()})})):Promise.resolve(null)}const le={async toggleParcelaPaga(e,a,t){try{const o=t?"pagar":"desfazer pagamento";if(!(await Swal.fire({title:t?"Marcar como pago?":"Desfazer pagamento?",text:`Deseja realmente ${o} este item?`,icon:"question",showCancelButton:!0,confirmButtonColor:t?"#10b981":"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:t?"Sim, marcar como pago":"Sim, desfazer",cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")}})).isConfirmed)return;Swal.fire({title:"Processando...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")},customClass:{container:"swal-above-modal"}}),await m.API.toggleItemFatura(e,a,t),await Swal.fire({icon:"success",title:"Sucesso!",text:t?"Item marcado como pago":"Pagamento desfeito",timer:b.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")}}),await m.App.carregarParcelamentos(),setTimeout(()=>{this.showDetalhes(e)},100)}catch(o){console.error("Erro ao alternar status:",o),Swal.fire({icon:"error",title:"Erro",text:h(o,"Erro ao processar operação"),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const r=document.querySelector(".swal2-container");r&&(r.style.zIndex="99999")}})}},async editarItemFatura(e,a,t,o){const r=n.modalEditarItemFatura||document.getElementById("modalEditarItemFatura");if(!r){console.error("Modal de edição não encontrado");return}window.LK?.modalSystem?.prepareBootstrapModal(r,{scope:"page"}),document.getElementById("editItemFaturaId").value=e,document.getElementById("editItemId").value=a,document.getElementById("editItemDescricao").value=t,document.getElementById("editItemValor").value=o.toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2}),bootstrap.Modal.getOrCreateInstance(r,{backdrop:!0,keyboard:!0,focus:!0}).show()},async salvarItemFatura(){const e=document.getElementById("editItemFaturaId").value,a=document.getElementById("editItemId").value,t=document.getElementById("editItemDescricao").value.trim(),o=document.getElementById("editItemValor").value;if(!t){Swal.fire({icon:"warning",title:"Atenção",text:"Informe a descrição do item.",timer:2e3,showConfirmButton:!1});return}const r=parseFloat(o.replace(/\./g,"").replace(",","."))||0;if(r<=0){Swal.fire({icon:"warning",title:"Atenção",text:"Informe um valor válido.",timer:2e3,showConfirmButton:!1});return}try{const s=n.modalEditarItemFatura||document.getElementById("modalEditarItemFatura"),i=bootstrap.Modal.getInstance(s);i&&i.hide(),Swal.fire({title:"Atualizando item...",html:"Aguarde enquanto salvamos as alterações.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading()}}),await c.apiRequest(`api/faturas/${e}/itens/${a}`,{method:"PUT",body:JSON.stringify({descricao:t,valor:r})}),await Swal.fire({icon:"success",title:"Item Atualizado!",text:"O item foi atualizado com sucesso.",timer:b.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1}),await m.App.carregarParcelamentos(),setTimeout(()=>{this.showDetalhes(e)},100)}catch(s){console.error("Erro ao editar item:",s),Swal.fire({icon:"error",title:"Erro",text:h(s,"Não foi possível atualizar o item."),heightAuto:!1})}},async excluirItemFatura(e,a,t,o){try{let r="Excluir Item?",s="Deseja realmente excluir este item da fatura?",i="Sim, excluir item";if(t&&o>1){const u=await ne(o);if(!u?.scope)return;if(u.scope==="parcelamento")return await this.excluirParcelamentoCompleto(e,a,o)}if(!(await Swal.fire({title:r,text:s,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:i,cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const u=document.querySelector(".swal2-container");u&&(u.style.zIndex="99999")}})).isConfirmed)return;Swal.fire({title:"Excluindo...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const u=document.querySelector(".swal2-container");u&&(u.style.zIndex="99999")},customClass:{container:"swal-above-modal"}}),await c.apiRequest(`api/faturas/${e}/itens/${a}`,{method:"DELETE"}),await Swal.fire({icon:"success",title:"Excluído!",text:"Item removido da fatura.",timer:b.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const u=document.querySelector(".swal2-container");u&&(u.style.zIndex="99999")}}),await m.App.carregarParcelamentos(),l.parcelamentos.some(u=>u.id===e)?setTimeout(()=>{this.showDetalhes(e)},100):l.modalDetalhesInstance&&l.modalDetalhesInstance.hide()}catch(r){console.error("Erro ao excluir item:",r),Swal.fire({icon:"error",title:"Erro",text:h(r,"Não foi possível excluir o item."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")}})}},async excluirParcelamentoCompleto(e,a,t){if((await Swal.fire({title:"Excluir Parcelamento Completo?",html:`
                <p>Deseja realmente excluir <strong>todas as ${t} parcelas</strong> deste parcelamento?</p>
                <p style="color: #ef4444; margin-top: 1rem;"><i data-lucide="triangle-alert"></i> Esta ação não pode ser desfeita!</p>
            `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:`Sim, excluir ${t} parcelas`,cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const r=document.querySelector(".swal2-container");r&&(r.style.zIndex="99999"),P()}})).isConfirmed){Swal.fire({title:"Excluindo parcelamento...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const r=document.querySelector(".swal2-container");r&&(r.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});try{const r=await c.apiRequest(`api/faturas/${e}/itens/${a}/parcelamento`,{method:"DELETE"});await Swal.fire({icon:"success",title:"Parcelamento Excluído!",text:r.message||`${t} parcelas removidas.`,timer:b.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const i=document.querySelector(".swal2-container");i&&(i.style.zIndex="99999")}}),await m.App.carregarParcelamentos(),l.parcelamentos.some(i=>i.id===e)?setTimeout(()=>{this.showDetalhes(e)},100):l.modalDetalhesInstance&&l.modalDetalhesInstance.hide()}catch(r){console.error("Erro ao excluir parcelamento:",r),Swal.fire({icon:"error",title:"Erro",text:h(r,"Não foi possível excluir o parcelamento."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")}})}}},async pagarFaturaCompleta(e,a){try{Swal.fire({title:"Carregando...",html:"Buscando informações da fatura e contas disponíveis.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const p=document.querySelector(".swal2-container");p&&(p.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});const[t,o]=await Promise.all([m.API.buscarParcelamento(e),m.API.listarContas()]),r=E(t,null),s=E(o,[]);if(!r?.cartao)throw new Error("Dados da fatura incompletos");const i=r.cartao.id,d=r.cartao.conta_id||null,u=(r.descricao||"").match(/(\d+)\/(\d+)/),v=u?u[1]:null,y=u?u[2]:null;if(!v||!y)throw new Error("Não foi possível identificar o mês/ano da fatura");let w="";if(Array.isArray(s)&&s.length>0)s.forEach(p=>{const I=p.saldoAtual??p.saldo_atual??p.saldo??0,L=c.formatMoney(I),A=p.id===d,F=I>=a,N=F?"color: #059669;":"color: #dc2626;";w+=`<option value="${p.id}" ${A?"selected":""} ${F?"":'style="color: #dc2626;"'}>
                        ${c.escapeHtml(p.nome)} - ${L}${A?" (vinculada ao cartão)":""}
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
                            ${w}
                        </select>
                    </div>
                    <p style="color: #6b7280; font-size: 0.875rem;">O valor será debitado da conta selecionada.</p>
                `,icon:"question",showCancelButton:!0,confirmButtonColor:"#10b981",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="check"></i> Sim, pagar tudo',cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const p=document.querySelector(".swal2-container");p&&(p.style.zIndex="99999"),P()},preConfirm:()=>{const p=document.getElementById("swalContaSelect"),I=p?parseInt(p.value):null;return I?{contaId:I}:(Swal.showValidationMessage("Selecione uma conta para débito"),!1)}});if(!x.isConfirmed)return;const B=x.value.contaId;Swal.fire({title:"Processando pagamento...",html:"Aguarde enquanto processamos o pagamento de todos os itens.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const p=document.querySelector(".swal2-container");p&&(p.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});const C=await m.API.pagarFaturaCompleta(i,parseInt(v),parseInt(y),B);if(!C.success)throw new Error(C.message||"Erro ao processar pagamento");await Swal.fire({icon:"success",title:"Fatura Paga!",html:`
                    <p>${C.message||"Fatura paga com sucesso!"}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor debitado:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${c.formatMoney(E(C,{})?.valor_pago||a)}
                        </div>
                    </div>
                    <div style="color: #059669;">
                        <i data-lucide="circle-check" style="font-size: 2rem;"></i>
                    </div>
                `,timer:3e3,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const p=document.querySelector(".swal2-container");p&&(p.style.zIndex="99999"),P()}}),await m.App.carregarParcelamentos(),l.modalDetalhesInstance.hide()}catch(t){console.error("Erro ao pagar fatura completa:",t),Swal.fire({icon:"error",title:"Erro ao pagar fatura",text:h(t,"Não foi possível processar o pagamento. Tente novamente."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const o=document.querySelector(".swal2-container");o&&(o.style.zIndex="99999")}})}}},$={showLoading(){n.loadingEl.style.display="flex",n.containerEl.style.display="none",n.emptyStateEl.style.display="none"},hideLoading(){n.loadingEl.style.display="none"},showEmpty(){n.containerEl.style.display="none",n.emptyStateEl.style.display="block"},...ae,...te,...le};m.UI=$;const H={cleanupModalArtifacts(){window.LK?.modalSystem||document.querySelectorAll(".modal.show").length>0||(document.querySelectorAll(".modal-backdrop").forEach(a=>{a.remove()}),document.body.classList.remove("modal-open"),document.body.style.removeProperty("overflow"),document.body.style.removeProperty("padding-right"))},async init(){try{this.initModal(),this.initViewToggle(),this.aplicarFiltrosURL(),await this.carregarCartoes(),await this.carregarParcelamentos(),this.attachEventListeners()}catch(e){console.error("❌ Erro ao inicializar:",e),Swal.fire({icon:"error",title:"Erro de Inicialização",text:"Não foi possível carregar a página. Tente recarregar."})}},initViewToggle(){const e=document.querySelector(".view-toggle"),a=n.containerEl;if(!e||!a)return;const t=e.querySelectorAll(".view-btn"),o=localStorage.getItem("faturas_view_mode")||"grid";o==="list"&&a.classList.add("list-view"),this.updateViewToggleState(t,o);const r=document.getElementById("faturasListHeader");o==="list"&&r&&r.classList.add("visible"),t.forEach(s=>{s.addEventListener("click",()=>{const i=s.dataset.view;i==="list"?(a.classList.add("list-view"),r&&r.classList.add("visible")):(a.classList.remove("list-view"),r&&r.classList.remove("visible")),localStorage.setItem("faturas_view_mode",i),this.updateViewToggleState(t,i)})})},updateViewToggleState(e,a){e.forEach(t=>{t.dataset.view===a?t.classList.add("active"):t.classList.remove("active")})},initModal(){n.modalDetalhes&&(window.LK?.modalSystem?.prepareBootstrapModal(n.modalDetalhes,{scope:"page"}),l.modalDetalhesInstance=bootstrap.Modal.getOrCreateInstance(n.modalDetalhes,{backdrop:!0,keyboard:!0,focus:!0}),n.modalDetalhes.addEventListener("show.bs.modal",()=>{document.activeElement?.blur()}),n.modalDetalhes.addEventListener("hidden.bs.modal",()=>{document.activeElement?.blur(),l.faturaAtual=null,this.cleanupModalArtifacts()}),n.modalDetalhes.addEventListener("click",e=>{const a=e.target.closest(".btn-ver-detalhes-parcela");if(a){e.preventDefault();const t=JSON.parse(a.dataset.parcela),o=a.dataset.descricao;this.mostrarDetalhesParcela(t,o)}}))},aplicarFiltrosURL(){const e=new URLSearchParams(window.location.search);if(e.has("cartao_id")&&(l.filtros.cartao_id=e.get("cartao_id"),n.filtroCartao&&(n.filtroCartao.value=l.filtros.cartao_id)),e.has("mes")&&e.has("ano")&&(l.filtros.mes=parseInt(e.get("mes"),10),l.filtros.ano=parseInt(e.get("ano"),10),window.monthPicker)){const a=new Date(l.filtros.ano,l.filtros.mes-1);window.monthPicker.setDate(a)}e.has("status")&&(l.filtros.status=e.get("status"),n.filtroStatus&&(n.filtroStatus.value=l.filtros.status))},async carregarCartoes(){try{const e=await m.API.listarCartoes(),a=E(e,[]);l.cartoes=Array.isArray(a)?a:[],this.preencherSelectCartoes(),this.sincronizarFiltrosComSelects()}catch(e){console.error("❌ Erro ao carregar cartões:",e)}},sincronizarFiltrosComSelects(){n.filtroStatus&&l.filtros.status&&(n.filtroStatus.value=l.filtros.status),n.filtroCartao&&l.filtros.cartao_id&&(n.filtroCartao.value=l.filtros.cartao_id),n.filtroAno&&l.filtros.ano&&(n.filtroAno.value=l.filtros.ano),n.filtroMes&&l.filtros.mes&&(n.filtroMes.value=l.filtros.mes)},preencherSelectCartoes(){n.filtroCartao&&(n.filtroCartao.innerHTML='<option value="">Todos os cartões</option>',l.cartoes.forEach(e=>{const a=document.createElement("option");a.value=e.id;const t=e.nome_cartao||e.nome||e.bandeira||"Cartão",o=e.ultimos_digitos?` •••• ${e.ultimos_digitos}`:"";a.textContent=t+o,n.filtroCartao.appendChild(a)}))},preencherSelectAnos(e=[]){if(!n.filtroAno)return;const a=n.filtroAno.value,t=new Date().getFullYear();if(n.filtroAno.innerHTML='<option value="">Todos os anos</option>',e.length>0){const o=[...e].sort((r,s)=>r-s);o.includes(t)||(o.push(t),o.sort((r,s)=>r-s)),o.forEach(r=>{const s=document.createElement("option");s.value=r,s.textContent=r,n.filtroAno.appendChild(s)})}else{const o=document.createElement("option");o.value=t,o.textContent=t,n.filtroAno.appendChild(o)}a?n.filtroAno.value=a:(n.filtroAno.value=t,l.filtros.ano=t),this.sincronizarFiltrosComSelects()},extrairAnosDisponiveis(e){const a=new Set;return e.forEach(t=>{const r=(t.descricao||"").match(/(\d{1,2})\/(\d{4})/);if(r&&a.add(parseInt(r[2],10)),t.data_vencimento){const s=new Date(t.data_vencimento).getFullYear();a.add(s)}}),Array.from(a)},async carregarParcelamentos(){m.UI.showLoading();try{const e=await m.API.listarParcelamentos({status:l.filtros.status||"",cartao_id:l.filtros.cartao_id||"",mes:l.filtros.mes||"",ano:l.filtros.ano||""}),a=E(e,{});let t=a?.faturas||[];if(l.parcelamentos=t,!l.anosCarregados){const o=a?.anos_disponiveis||this.extrairAnosDisponiveis(t);this.preencherSelectAnos(o),l.anosCarregados=!0}m.UI.renderParcelamentos(t)}catch(e){console.error("❌ Erro ao carregar parcelamentos:",e),m.UI.showEmpty(),Swal.fire({icon:"error",title:"Erro ao Carregar",text:h(e,"Não foi possível carregar os parcelamentos")})}finally{m.UI.hideLoading()}},async cancelarParcelamento(e){try{await m.API.cancelarParcelamento(e),await Swal.fire({icon:"success",title:"Cancelado!",text:"Parcelamento cancelado com sucesso",timer:b.TIMEOUTS.successMessage,showConfirmButton:!1}),await this.carregarParcelamentos()}catch(a){console.error("Erro ao cancelar:",a),Swal.fire({icon:"error",title:"Erro ao Cancelar",text:h(a,"Não foi possível cancelar o parcelamento")})}},attachEventListeners(){n.toggleFilters&&n.toggleFilters.addEventListener("click",o=>{o.stopPropagation(),this.toggleFilters()});const e=document.querySelector(".filters-header");e&&e.addEventListener("click",()=>{this.toggleFilters()}),n.btnFiltrar&&n.btnFiltrar.addEventListener("click",()=>{this.aplicarFiltros()}),n.btnLimparFiltros&&n.btnLimparFiltros.addEventListener("click",()=>{this.limparFiltros()}),[n.filtroStatus,n.filtroCartao,n.filtroAno,n.filtroMes].forEach(o=>{o&&o.addEventListener("keypress",r=>{r.key==="Enter"&&this.aplicarFiltros()})});const a=document.getElementById("btnSalvarItemFatura");a&&a.addEventListener("click",()=>{m.UI.salvarItemFatura()});const t=document.getElementById("formEditarItemFatura");t&&t.addEventListener("submit",o=>{o.preventDefault(),m.UI.salvarItemFatura()})},toggleFilters(){n.filtersContainer&&n.filtersContainer.classList.toggle("collapsed")},aplicarFiltros(){l.filtros.status=n.filtroStatus?.value||"",l.filtros.cartao_id=n.filtroCartao?.value||"",l.filtros.ano=n.filtroAno?.value||"",l.filtros.mes=n.filtroMes?.value||"",this.atualizarBadgesFiltros(),this.carregarParcelamentos()},limparFiltros(){n.filtroStatus&&(n.filtroStatus.value=""),n.filtroCartao&&(n.filtroCartao.value=""),n.filtroAno&&(n.filtroAno.value=""),n.filtroMes&&(n.filtroMes.value=""),l.filtros={status:"",cartao_id:"",ano:"",mes:""},this.atualizarBadgesFiltros(),this.carregarParcelamentos()},atualizarBadgesFiltros(){if(!n.activeFilters)return;const e=[],a=["","Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"];if(l.filtros.status){const t={pendente:"⏳ Pendente",parcial:"🔄 Parcial",paga:"✅ Paga",cancelado:"❌ Cancelado"};e.push({key:"status",label:t[l.filtros.status]||l.filtros.status})}if(l.filtros.cartao_id){const t=l.cartoes.find(r=>r.id==l.filtros.cartao_id),o=t?t.nome_cartao||t.nome:"Cartão";e.push({key:"cartao_id",label:`💳 ${o}`})}l.filtros.ano&&e.push({key:"ano",label:`📅 ${l.filtros.ano}`}),l.filtros.mes&&e.push({key:"mes",label:`📆 ${a[l.filtros.mes]}`}),e.length>0?(n.activeFilters.style.display="flex",n.activeFilters.innerHTML=e.map(t=>`
                <span class="filter-badge">
                    ${t.label}
                    <button class="filter-badge-remove" data-filter="${t.key}" title="Remover filtro">
                        <i data-lucide="x"></i>
                    </button>
                </span>
            `).join(""),window.lucide&&lucide.createIcons(),n.activeFilters.querySelectorAll(".filter-badge-remove").forEach(t=>{t.addEventListener("click",o=>{const r=o.currentTarget.dataset.filter;this.removerFiltro(r)})})):(n.activeFilters.style.display="none",n.activeFilters.innerHTML="")},removerFiltro(e){l.filtros[e]="";const a={status:n.filtroStatus,cartao_id:n.filtroCartao,ano:n.filtroAno,mes:n.filtroMes};a[e]&&(a[e].value=""),this.atualizarBadgesFiltros(),this.carregarParcelamentos()}};m.App=H;const z={instance:null,faturaId:null,valorTotal:null,cartaoId:null,mes:null,ano:null,contas:[],contaPadraoId:null,init(){const e=n.modalPagarFatura||document.getElementById("modalPagarFatura");e&&(window.LK?.modalSystem?.prepareBootstrapModal(e,{scope:"page"}),this.instance=bootstrap.Modal.getOrCreateInstance(e,{backdrop:!0,keyboard:!0,focus:!0}),this.attachEvents())},attachEvents(){document.getElementById("btnPagarTotal")?.addEventListener("click",()=>{this.instance.hide(),m.UI.pagarFaturaCompleta(this.faturaId,this.valorTotal)}),document.getElementById("btnPagarParcial")?.addEventListener("click",()=>{this.mostrarFormularioParcial()}),document.getElementById("btnVoltarEscolha")?.addEventListener("click",()=>{this.mostrarEscolha()}),document.getElementById("btnConfirmarPagamento")?.addEventListener("click",()=>{this.confirmarPagamentoParcial()});const e=document.getElementById("valorPagamentoParcial");e&&(e.addEventListener("input",a=>{let t=a.target.value.replace(/\D/g,"");if(t===""){a.target.value="";return}t=(parseInt(t)/100).toFixed(2),a.target.value=parseFloat(t).toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2})}),e.addEventListener("focus",a=>{a.target.select()})),document.querySelectorAll(".btn-opcao-pagamento").forEach(a=>{a.addEventListener("mouseenter",()=>{a.style.transform="translateY(-2px)",a.style.boxShadow="0 8px 25px rgba(0,0,0,0.2)"}),a.addEventListener("mouseleave",()=>{a.style.transform="translateY(0)",a.style.boxShadow="none"})})},async abrir(e,a){this.faturaId=e,this.valorTotal=a,document.getElementById("pagarFaturaId").value=e,document.getElementById("pagarFaturaValorTotal").value=a,document.getElementById("valorTotalDisplay").textContent=c.formatMoney(a),document.getElementById("valorTotalInfo").textContent=`Valor total da fatura: ${c.formatMoney(a)}`,document.getElementById("valorPagamentoParcial").value=c.formatMoney(a).replace("R$ ",""),this.mostrarEscolha(),await this.carregarDados(),this.instance.show()},async carregarDados(){try{const[e,a]=await Promise.all([m.API.buscarParcelamento(this.faturaId),m.API.listarContas()]),t=E(e,null);if(this.contas=E(a,[]),!t?.cartao)throw new Error("Dados da fatura incompletos");this.cartaoId=t.cartao.id,this.contaPadraoId=t.cartao.conta_id||null;const r=(t.descricao||"").match(/(\d+)\/(\d+)/);this.mes=r?parseInt(r[1]):null,this.ano=r?parseInt(r[2]):null,this.popularSelectContas()}catch(e){console.error("Erro ao carregar dados:",e),Swal.fire({icon:"error",title:"Erro",text:h(e,"Erro ao carregar dados da fatura.")})}},popularSelectContas(){const e=document.getElementById("contaPagamentoFatura");if(e){if(e.innerHTML="",!Array.isArray(this.contas)||this.contas.length===0){e.innerHTML='<option value="">Nenhuma conta disponível</option>';return}this.contas.forEach(a=>{const t=a.saldoAtual??a.saldo_atual??a.saldo??0,o=c.formatMoney(t),r=a.id===this.contaPadraoId,s=document.createElement("option");s.value=a.id,s.textContent=`${a.nome} - ${o}${r?" (vinculada ao cartão)":""}`,r&&(s.selected=!0),e.appendChild(s)})}},mostrarEscolha(){document.getElementById("pagarFaturaEscolha").style.display="block",document.getElementById("pagarFaturaFormParcial").style.display="none",document.getElementById("pagarFaturaFooter").style.display="none"},mostrarFormularioParcial(){document.getElementById("pagarFaturaEscolha").style.display="none",document.getElementById("pagarFaturaFormParcial").style.display="block",document.getElementById("pagarFaturaFooter").style.display="flex",setTimeout(()=>{const e=document.getElementById("valorPagamentoParcial");e&&(e.focus(),e.select())},100)},async confirmarPagamentoParcial(){const e=document.getElementById("valorPagamentoParcial").value,a=document.getElementById("contaPagamentoFatura").value,t=parseFloat(e.replace(/\./g,"").replace(",","."))||0;if(t<=0){Swal.fire({icon:"warning",title:"Valor inválido",text:"Digite um valor válido para o pagamento.",timer:2e3,showConfirmButton:!1});return}if(t>this.valorTotal){Swal.fire({icon:"warning",title:"Valor inválido",text:`O valor não pode ser maior que ${c.formatMoney(this.valorTotal)}`,timer:2e3,showConfirmButton:!1});return}if(!a){Swal.fire({icon:"warning",title:"Conta não selecionada",text:"Selecione uma conta para débito.",timer:2e3,showConfirmButton:!1});return}if(!this.cartaoId||!this.mes||!this.ano){Swal.fire({icon:"error",title:"Erro",text:"Dados da fatura incompletos. Tente novamente."});return}this.instance.hide(),Swal.fire({title:"Processando pagamento...",html:"Aguarde enquanto processamos o pagamento.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>Swal.showLoading()});try{const o=await c.apiRequest(`api/cartoes/${this.cartaoId}/fatura/pagar`,{method:"POST",body:JSON.stringify({mes:this.mes,ano:this.ano,conta_id:parseInt(a),valor_parcial:t})});if(!o.success)throw new Error(h(o,"Erro ao processar pagamento"));await Swal.fire({icon:"success",title:"Pagamento Realizado!",html:`
                    <p>${o.message||"Pagamento efetuado com sucesso!"}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor pago:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${c.formatMoney(t)}
                        </div>
                    </div>
                `,timer:3e3,showConfirmButton:!1}),await m.App.carregarParcelamentos(),l.modalDetalhesInstance&&l.modalDetalhesInstance.hide()}catch(o){console.error("Erro ao pagar fatura:",o),Swal.fire({icon:"error",title:"Erro ao pagar fatura",text:h(o,"Não foi possível processar o pagamento. Tente novamente.")})}}};async function ie(e){const a=l.faturaAtual;if(!a||!a.cartao||!a.mes_referencia||!a.ano_referencia){Swal.fire({icon:"error",title:"Erro",text:"Dados da fatura incompletos para reverter o pagamento."});return}if((await Swal.fire({title:"Desfazer Pagamento?",html:`
            <p>Você está prestes a <strong>reverter o pagamento</strong> de todos os itens desta fatura.</p>
            <div style="margin: 1rem 0; padding: 0.75rem; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <p style="margin: 0; color: #92400e; font-size: 0.875rem;">
                    <i data-lucide="triangle-alert"></i> 
                    O lançamento de pagamento será excluído e o valor voltará para a conta.
                </p>
            </div>
        `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#f59e0b",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="undo-2"></i> Sim, reverter',cancelButtonText:"Cancelar",didOpen:()=>{window.lucide&&lucide.createIcons()}})).isConfirmed)try{Swal.fire({title:"Revertendo pagamento...",html:"Aguarde enquanto processamos a reversão.",allowOutsideClick:!1,didOpen:()=>Swal.showLoading()});const o=a.cartao.id,r=a.mes_referencia,s=a.ano_referencia,i=await c.apiRequest(`api/cartoes/${o}/fatura/desfazer-pagamento`,{method:"POST",body:JSON.stringify({mes:r,ano:s})});if(i.success)await Swal.fire({icon:"success",title:"Pagamento Revertido!",html:`
                    <p>${i.message||"O pagamento foi revertido com sucesso."}</p>
                    <p style="color: #059669; margin-top: 0.5rem;">
                        <i data-lucide="circle-check"></i> 
                        ${E(i,{})?.itens_revertidos||0} item(s) voltou(aram) para pendente.
                    </p>
                `,timer:3e3,showConfirmButton:!1,didOpen:()=>{window.lucide&&lucide.createIcons()}}),l.modalDetalhesInstance&&l.modalDetalhesInstance.hide(),await m.App.carregarParcelamentos();else throw new Error(h(i,"Erro ao reverter pagamento"))}catch(o){console.error("Erro ao reverter pagamento:",o),Swal.fire({icon:"error",title:"Erro",text:h(o,"Não foi possível reverter o pagamento.")})}}async function ce(e){if((await Swal.fire({title:"Excluir Fatura?",html:`
            <p>Você está prestes a excluir esta fatura e <strong>todos os seus itens pendentes</strong>.</p>
            <p style="color: #ef4444; font-weight: 500;">Esta ação não pode ser desfeita!</p>
        `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="trash-2"></i> Sim, excluir',cancelButtonText:"Cancelar",didOpen:()=>{window.lucide&&lucide.createIcons()}})).isConfirmed)try{const t=await c.apiRequest(`api/faturas/${e}`,{method:"DELETE"});if(t.success)Swal.fire({icon:"success",title:"Fatura Excluída!",text:"A fatura foi excluída com sucesso.",timer:2e3,showConfirmButton:!1}),l.modalDetalhesInstance&&l.modalDetalhesInstance.hide(),m.App.carregarParcelamentos();else throw new Error(h(t,"Erro ao excluir fatura"))}catch(t){console.error("Erro ao excluir fatura:",t),Swal.fire({icon:"error",title:"Erro",text:h(t,"Não foi possível excluir a fatura.")})}}async function de(e,a){if((await Swal.fire({title:"Excluir Item?",html:`
            <p>Você está prestes a excluir este item da fatura.</p>
            <p style="color: #ef4444; font-weight: 500;">Esta ação não pode ser desfeita!</p>
        `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="trash-2"></i> Sim, excluir',cancelButtonText:"Cancelar",customClass:{container:"swal-above-modal"},didOpen:()=>{window.lucide&&lucide.createIcons()}})).isConfirmed)try{const o=await c.apiRequest(`api/faturas/${e}/itens/${a}`,{method:"DELETE"});if(o.success)Swal.fire({icon:"success",title:"Item Excluído!",text:"O item foi excluído com sucesso.",timer:2e3,showConfirmButton:!1,customClass:{container:"swal-above-modal"}}),m.App.carregarParcelamentos(),l.faturaAtual&&setTimeout(()=>{m.UI.abrirDetalhes(e)},500);else throw new Error(h(o,"Erro ao excluir item"))}catch(o){console.error("Erro ao excluir item:",o),Swal.fire({icon:"error",title:"Erro",text:h(o,"Não foi possível excluir o item."),customClass:{container:"swal-above-modal"}})}}m.ModalPagarFatura=z;const ue={toggleFaturasHero:"faturasHero",toggleFaturasFiltros:"faturasFilters",toggleFaturasViewToggle:"faturasViewToggle"},U={toggleFaturasHero:!0,toggleFaturasFiltros:!0,toggleFaturasViewToggle:!0},me={...U,toggleFaturasFiltros:!1,toggleFaturasViewToggle:!1};async function pe(){return Y("faturas")}async function fe(e){await X("faturas",e)}const k=K({storageKey:"lk_faturas_prefs",sectionMap:ue,completeDefaults:U,essentialDefaults:me,loadPreferences:pe,savePreferences:fe,modal:{overlayId:"faturasCustomizeModalOverlay",openButtonId:"btnCustomizeFaturas",closeButtonId:"btnCloseCustomizeFaturas",saveButtonId:"btnSaveCustomizeFaturas",presetEssentialButtonId:"btnPresetEssencialFaturas",presetCompleteButtonId:"btnPresetCompletoFaturas"}});function j(){k.init()}m.Customize={init:j,open:k.open,close:k.close};window.abrirModalPagarFatura=(e,a)=>z.abrir(e,a);window.reverterPagamentoFaturaGlobal=ie;window.excluirFaturaGlobal=ce;window.excluirItemFaturaGlobal=de;window.pagarFaturaCompletaGlobal=(e,a)=>$.pagarFaturaCompleta(e,a);window.FaturasModule={toggleCardDetalhes:e=>$.showDetalhes(e),excluirItemFatura:(...e)=>$.excluirItemFatura(...e),editarItemFatura:(...e)=>$.editarItemFatura(...e),toggleParcelaPaga:(...e)=>$.toggleParcelaPaga(...e)};window.__LK_PARCELAMENTOS_LOADER__||(window.__LK_PARCELAMENTOS_LOADER__=!0,document.addEventListener("DOMContentLoaded",()=>{Q(),j(),H.init(),z.init()}));
