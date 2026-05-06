import{h as ie,b as le,g as ce,c as D,d as E,e as y}from"./api-DpYnTMaG.js";import{b as de,r as ue,a as me,d as pe}from"./finance-CgaDv1sH.js";import{r as fe,a as ge,b as U,c as he,d as j,e as ve,f as J}from"./faturas-osFPSmt_.js";import{r as $}from"./ui-H2yoVZe7.js";import{c as be,p as ye,f as we}from"./ui-preferences-Bh_GTAc4.js";import{e as X,g as Ee}from"./runtime-config-CXTcOn9X.js";function Ie(e){return{house:"#f97316",utensils:"#ef4444",car:"#3b82f6",lightbulb:"#eab308","heart-pulse":"#ef4444","graduation-cap":"#6366f1",shirt:"#ec4899",clapperboard:"#a855f7","credit-card":"#0ea5e9",smartphone:"#6366f1","shopping-cart":"#f97316",coins:"#eab308",briefcase:"#3b82f6",laptop:"#06b6d4","trending-up":"#22c55e",gift:"#ec4899",banknote:"#22c55e",trophy:"#f59e0b",wallet:"#14b8a6",tag:"#94a3b8","pie-chart":"#8b5cf6","piggy-bank":"#ec4899",plane:"#0ea5e9","gamepad-2":"#a855f7",baby:"#f472b6",dog:"#92400e",wrench:"#64748b",church:"#6366f1",dumbbell:"#ef4444",music:"#a855f7","book-open":"#3b82f6",scissors:"#ec4899","building-2":"#64748b",landmark:"#3b82f6",receipt:"#14b8a6"}[e]||"#f97316"}const w={BASE_URL:ie(),ENDPOINTS:{parcelamentos:fe(),categorias:me(),contas:ue(),cartoes:de()},TIMEOUTS:{alert:5e3,successMessage:2e3}},s={};function K(e){const t=document.getElementById(e);return t?(window.LK?.modalSystem?.prepareBootstrapModal(t,{scope:"page"}),t):null}function Ce(){s.loadingEl=document.getElementById("loadingParcelamentos"),s.containerEl=document.getElementById("parcelamentosContainer"),s.emptyStateEl=document.getElementById("emptyState"),s.detailPageEl=document.getElementById("faturaDetalhePage"),s.detailPageShell=document.getElementById("faturaDetalheShell"),s.detailPageLoading=document.getElementById("faturaDetalheLoading"),s.detailPageContent=document.getElementById("faturaDetalheContent"),s.detailPageTitle=document.getElementById("faturaDetalheTitle"),s.detailPageSubtitle=document.getElementById("faturaDetalheSubtitle"),s.filtroStatus=document.getElementById("filtroStatus"),s.filtroCartao=document.getElementById("filtroCartao"),s.filtroAno=document.getElementById("filtroAno"),s.filtroMes=document.getElementById("filtroMes"),s.btnFiltrar=document.getElementById("btnFiltrar"),s.btnLimparFiltros=document.getElementById("btnLimparFiltros"),s.filtersContainer=document.querySelector(".filters-modern"),s.filtersBody=document.getElementById("filtersBody"),s.toggleFilters=document.getElementById("toggleFilters"),s.activeFilters=document.getElementById("activeFilters"),s.filtersSummary=document.getElementById("faturasFiltersSummary"),s.resultsSummary=document.getElementById("faturasResultsSummary"),s.contextSummary=document.getElementById("faturasContextSummary"),s.modalPagarFatura=K("modalPagarFatura"),s.modalEditarItemFatura=K("modalEditarItemFatura")}const i={parcelamentos:[],cartoes:[],categorias:[],subcategoriasCache:new Map,faturaAtual:null,currentDetailId:null,sortColumn:"data_compra",sortDirection:"asc",filtros:{status:"",cartao_id:"",ano:new Date().getFullYear(),mes:""},anosCarregados:!1},m={},d={formatMoney(e){return new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(e||0)},formatDate(e){return e?new Date(e+"T00:00:00").toLocaleDateString("pt-BR"):""},parseMoney(e){return e&&parseFloat(e.replace(/[^\d,]/g,"").replace(",","."))||0},showAlert(e,t,a="danger"){e&&(e.className=`alert alert-${a}`,e.textContent=t,e.style.display="block",setTimeout(()=>{e.style.display="none"},w.TIMEOUTS.alert))},getCSRFToken(){return ce()},escapeHtml(e){if(!e)return"";const t=document.createElement("div");return t.textContent=e,t.innerHTML},buildUrl(e,t={}){const a=e.startsWith("http")?e:w.BASE_URL+e.replace(/^\//,""),r=Object.entries(t).filter(([o,n])=>n!=null&&n!=="").map(([o,n])=>`${o}=${encodeURIComponent(n)}`);return r.length>0?`${a}?${r.join("&")}`:a},async apiRequest(e,t={}){const a=e.startsWith("http")?e:w.BASE_URL+e.replace(/^\//,"");try{return await le(a,{...t,headers:{"X-CSRF-Token":this.getCSRFToken(),...t.headers}})}catch(r){throw console.error("Erro na requisição:",r),r}},debounce(e,t){let a;return function(...o){const n=()=>{clearTimeout(a),e(...o)};clearTimeout(a),a=setTimeout(n,t)}},calcularDiferencaDias(e,t){const a=new Date(e+"T00:00:00"),r=new Date(t+"T00:00:00");return Math.floor((a-r)/(1e3*60*60*24))}},Se={async listarParcelamentos(e={}){const t={status:e.status,cartao_id:e.cartao_id,ano:e.ano,mes:e.mes},a=d.buildUrl(w.ENDPOINTS.parcelamentos,t);return await d.apiRequest(a)},async listarCartoes(){return await d.apiRequest(w.ENDPOINTS.cartoes)},async buscarParcelamento(e){const t=parseInt(e,10);if(isNaN(t))throw new Error("ID inválido");return await d.apiRequest(J(t))},async criarParcelamento(e){return await d.apiRequest(w.ENDPOINTS.parcelamentos,{method:"POST",body:JSON.stringify(e)})},async cancelarParcelamento(e){return await d.apiRequest(J(e),{method:"DELETE"})},async toggleItemFatura(e,t,a){return await d.apiRequest(ve(e,t),{method:"POST",body:JSON.stringify({pago:a})})},async atualizarItemFatura(e,t,a){return await d.apiRequest(j(e,t),{method:"PUT",body:JSON.stringify(a)})},async excluirItemFatura(e,t){return await d.apiRequest(j(e,t),{method:"DELETE"})},async excluirParcelamentoDoItem(e,t){return await d.apiRequest(he(e,t),{method:"DELETE"})},async pagarFaturaCompleta(e,t,a,r=null){const o={mes:t,ano:a};return r&&(o.conta_id=r),await d.apiRequest(U(e),{method:"POST",body:JSON.stringify(o)})},async pagarFaturaParcial(e,t,a,r,o){return await d.apiRequest(U(e),{method:"POST",body:JSON.stringify({mes:t,ano:a,conta_id:r,valor_parcial:o})})},async desfazerPagamentoFatura(e,t,a){return await d.apiRequest(ge(e),{method:"POST",body:JSON.stringify({mes:t,ano:a})})},async listarContas(){return await d.apiRequest(`${w.ENDPOINTS.contas}?with_balances=1`)},async listarCategorias(){return await d.apiRequest(w.ENDPOINTS.categorias)},async listarSubcategorias(e){return await d.apiRequest(pe(e))}};m.API=Se;const Y=(e,t=0,a=100)=>Math.min(a,Math.max(t,Number(e)||0)),v=(e,t="")=>d.escapeHtml(String(e??t)),Pe=(e,t=0)=>`${(Number(e)||0).toLocaleString("pt-BR",{minimumFractionDigits:t,maximumFractionDigits:t})}%`,M=(e,t)=>`data-lk-tooltip-title="${v(e)}" data-lk-tooltip="${v(t)}"`,G=/(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|hsla?\([^)]+\))/,x={vencida:0,proxima:1,parcial:2,pendente:3,paga:4,cancelado:5,indefinido:6},xe={renderParcelamentos(e){if(!Array.isArray(e)||e.length===0){this.showEmpty();return}s.emptyStateEl.style.display="none",s.containerEl.style.display="grid";const t=this.sortParcelamentosByRelevance(e),a=document.createDocumentFragment();t.forEach(r=>{const o=this.createParcelamentoCard(r);a.appendChild(o)}),s.containerEl.innerHTML="",s.containerEl.appendChild(a),$()},createParcelamentoCard(e){const t=e.progresso||0,a=e.parcelas_pendentes||0,r=e.parcelas_pagas||0,o=r+a,n=this.getReferenceMeta(e),l=this.getDueMeta(e),c=this.getStatusMeta(e.status,t,l),u=n.isPastReference&&c.badgeClass==="badge-paga",p=document.createElement("div");p.className=`parcelamento-card surface-card surface-card--interactive surface-card--clip status-${e.status}`,p.dataset.id=e.id,p.style.setProperty("--fatura-accent",this.getAccentColorSolid(e.cartao)),n.isCurrentMonth&&p.classList.add("is-current-focus"),n.isPastReference&&p.classList.add("is-reference-past"),u&&p.classList.add("is-historical-paid");const f=this.getStatusBadge(e.status,t,l),h=e.mes_referencia||"",b=e.ano_referencia||"";return p.innerHTML=this.createCardHTML({parc:e,statusBadge:f,mes:h,ano:b,itensPendentes:a,itensPagos:r,totalItens:o,progresso:t,referenceMeta:n,dueMeta:l,statusMeta:c}),p},getReferenceMeta(e){let t=Number.parseInt(String(e?.mes_referencia??""),10),a=Number.parseInt(String(e?.ano_referencia??""),10);if(!Number.isInteger(t)||t<1||t>12||!Number.isInteger(a)||a<1900){const p=String(e?.descricao??"").match(/(\d{1,2})\/(\d{4})/);p&&(t=Number.parseInt(p[1],10),a=Number.parseInt(p[2],10))}if((!Number.isInteger(t)||t<1||t>12||!Number.isInteger(a)||a<1900)&&e?.data_vencimento){const u=new Date(`${e.data_vencimento}T00:00:00`);Number.isNaN(u.getTime())||(t=u.getMonth()+1,a=u.getFullYear())}const r=Number.isInteger(t)&&t>=1&&t<=12&&Number.isInteger(a)&&a>=1900,o=new Date,n=o.getMonth()+1,l=o.getFullYear(),c=r?(a-l)*12+(t-n):Number.MAX_SAFE_INTEGER;return{month:t,year:a,hasReference:r,monthOffset:c,isCurrentMonth:r&&c===0,isFutureReference:r&&c>0,isPastReference:r&&c<0}},getStatusSortRank(e,t){return e.status==="cancelado"?x.cancelado:t?.isVencida?x.vencida:t?.isProxima?x.proxima:(Number(e.progresso)||0)>=100||e.status==="paga"||e.status==="concluido"?x.paga:(Number(e.progresso)||0)>0||e.status==="parcial"?x.parcial:e.status==="pendente"?x.pendente:x.indefinido},getRelevanceBucket(e){return e.isCurrentMonth?0:e.isFutureReference?1:e.isPastReference?2:3},compareDueDates(e,t){return!e&&!t?0:e?t?e.localeCompare(t):-1:1},sortParcelamentosByRelevance(e){return[...e].sort((t,a)=>{const r=this.getReferenceMeta(t),o=this.getReferenceMeta(a),n=this.getRelevanceBucket(r),l=this.getRelevanceBucket(o);if(n!==l)return n-l;if(r.monthOffset!==o.monthOffset)return n===2?o.monthOffset-r.monthOffset:r.monthOffset-o.monthOffset;const c=this.getDueMeta(t),u=this.getDueMeta(a),p=this.getStatusSortRank(t,c),f=this.getStatusSortRank(a,u);if(p!==f)return p-f;const h=this.compareDueDates(t.data_vencimento,a.data_vencimento);if(h!==0)return h;const b=(Number(a.valor_total)||0)-(Number(t.valor_total)||0);if(b!==0)return b;const S=String(t.cartao?.nome||t.cartao?.nome_cartao||t.cartao?.bandeira||""),B=String(a.cartao?.nome||a.cartao?.nome_cartao||a.cartao?.bandeira||"");return S.localeCompare(B,"pt-BR")})},attachCardEventListeners(){},getAccentColorSolid(e){const a={visa:"#1A1F71",mastercard:"#EB001B",elo:"#FFCB05",amex:"#006FCF",diners:"#0079BE",discover:"#FF6000",hipercard:"#B11116"}[e?.bandeira?.toLowerCase()]||"#3b82f6",r=String(e?.cor_cartao||e?.conta?.instituicao_financeira?.cor_primaria||a).trim();return r?/gradient/i.test(r)?r.match(G)?.[1]||a:/^var\(/i.test(r)||G.test(r)?r:a:a},getBandeiraIcon(e){return{visa:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71"/><text x="24" y="20" text-anchor="middle" font-size="12" font-weight="bold" fill="#fff" font-family="sans-serif">VISA</text></svg>',mastercard:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71" opacity="0"/><circle cx="19" cy="16" r="10" fill="#EB001B" opacity=".85"/><circle cx="29" cy="16" r="10" fill="#F79E1B" opacity=".85"/></svg>',elo:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#000"/><text x="24" y="20" text-anchor="middle" font-size="13" font-weight="bold" fill="#FFCB05" font-family="sans-serif">elo</text></svg>',amex:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#006FCF"/><text x="24" y="20" text-anchor="middle" font-size="9" font-weight="bold" fill="#fff" font-family="sans-serif">AMEX</text></svg>',hipercard:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#B11116"/><text x="24" y="20" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff" font-family="sans-serif">HIPER</text></svg>',diners:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#0079BE"/><text x="24" y="20" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff" font-family="sans-serif">DINERS</text></svg>'}[e]||'<i data-lucide="credit-card"></i>'},getDueMeta(e){let t=e.data_vencimento;if(!t&&e.cartao?.dia_vencimento&&e.descricao){const u=e.descricao.match(/(\d{1,2})\/(\d{4})/);if(u){const p=u[1].padStart(2,"0"),f=u[2],h=String(e.cartao.dia_vencimento).padStart(2,"0");t=`${f}-${p}-${h}`}}if(!t)return{hasDate:!1,label:"A definir",helper:"Sem data de vencimento informada",detailClass:"",isVencida:!1,isProxima:!1};const a=d.formatDate(t),r=new Date;r.setHours(0,0,0,0);const o=new Date(`${t}T00:00:00`),n=e.status!=="paga"&&e.status!=="concluido"&&e.status!=="cancelado",l=n&&o<r,c=n&&!l&&o-r<=4320*60*1e3;return{hasDate:!0,raw:t,label:a,helper:l?"Vencimento expirado":c?"Vence em breve":"Dentro do prazo",detailClass:l?"is-danger":c?"is-warning":"",isVencida:l,isProxima:c}},getStatusMeta(e,t=null,a=null){const r=Y(t);return e==="cancelado"?{badgeClass:"badge-cancelado",progressClass:"is-muted",icon:"ban",label:"Cancelada",shortLabel:"Cancelada",hint:"Sem cobranca ativa",tooltip:"Esta fatura foi cancelada e nao entra mais no acompanhamento ativo."}:r>=100||e==="paga"||e==="concluido"?{badgeClass:"badge-paga",progressClass:"is-safe",icon:"circle-check",label:"Paga",shortLabel:"Liquidada",hint:"Pagamento concluido",tooltip:"O valor desta fatura ja foi quitado integralmente."}:a?.isVencida?{badgeClass:"badge-alerta",progressClass:"is-danger",icon:"triangle-alert",label:"Vencida",shortLabel:"Em atraso",hint:"Regularize esta fatura",tooltip:"A fatura passou do vencimento e merece prioridade para evitar juros."}:a?.isProxima?{badgeClass:"badge-alerta",progressClass:"is-warning",icon:"clock-3",label:"Vence em breve",shortLabel:"Vence logo",hint:"Priorize o pagamento",tooltip:"O vencimento esta proximo. Vale organizar o pagamento desta fatura."}:r>0?{badgeClass:"badge-parcial",progressClass:"is-warning",icon:"loader-2",label:"Pagamento parcial",shortLabel:"Parcial",hint:"Parte do valor ja foi paga",tooltip:"A fatura segue aberta, mas ja possui pagamentos registrados."}:{badgeClass:"badge-pendente",progressClass:"is-safe",icon:"clock-3",label:"Pendente",shortLabel:"No prazo",hint:"Aguardando pagamento",tooltip:"A fatura segue aberta e ainda esta dentro do prazo normal de pagamento."}},getResumoPrincipal(e,t,a,r,o,n){const l=e.total_estornos&&e.total_estornos>0,c=n>0?`${o}/${n} itens pagos`:"Sem itens consolidados",u=t.hasDate&&t.helper!=="Dentro do prazo"?`<span class="fatura-card-due-tag ${t.detailClass}">${v(t.helper)}</span>`:"";return`
            <div class="fatura-card-main">
                <span class="resumo-label">Valor total</span>
                <strong class="resumo-valor">${d.formatMoney(e.valor_total)}</strong>
                <div class="fatura-card-due-line ${t.detailClass}">
                    <span class="fatura-card-due-copy">Vence ${v(t.label)}</span>
                    ${u}
                </div>
                ${l?`
                    <p class="fatura-card-note">
                        Inclui ${d.formatMoney(e.total_estornos)} em estornos.
                    </p>
                `:""}
            </div>

            <div class="fatura-card-details">
                <div class="fatura-card-detail ${t.detailClass}" ${M("Vencimento",t.hasDate?`Data prevista para pagamento desta fatura: ${t.label}.`:"A fatura ainda nao possui data de vencimento consolidada.")}>
                    <span class="fatura-card-detail-label">Vencimento</span>
                    <strong class="fatura-card-detail-value">${v(t.label)}</strong>
                    <span class="fatura-card-detail-meta">${v(t.helper)}</span>
                </div>

                <div class="fatura-card-detail ${a.progressClass}" ${M("Progresso de pagamento",n>0?`${o} de ${n} itens ja foram pagos nesta fatura.`:"Ainda nao existem itens suficientes para calcular o progresso de pagamento.")}>
                    <span class="fatura-card-detail-label">Pagamento</span>
                    <strong class="fatura-card-detail-value">${n>0?`${o}/${n}`:"--"}</strong>
                    <span class="fatura-card-detail-meta">${v(c)}</span>
                </div>
            </div>
        `},getProgressoSection(e,t,a,r,o){const n=Y(r),l=n>0?Math.max(n,8):0;return e===0?`
                <div class="parc-progress-section is-empty">
                    <div class="parc-progress-header">
                        <span class="parc-progress-text">Sem itens suficientes para medir o pagamento</span>
                        <span class="parc-progress-percent">--</span>
                    </div>
                    <div class="parc-progress-bar">
                        <div class="parc-progress-fill ${o.progressClass}" style="width: 0%"></div>
                    </div>
                </div>
            `:`
            <div class="parc-progress-section ${o.progressClass}">
                <div class="parc-progress-header">
                    <span class="parc-progress-text">Pago ${Pe(n)}</span>
                    <span class="parc-progress-percent">${v(o.shortLabel)}</span>
                </div>
                <div class="parc-progress-bar">
                    <div class="parc-progress-fill ${o.progressClass}" style="width: ${l}%"></div>
                </div>
                <div class="parc-progress-foot">
                    <span>${a} de ${e} itens pagos</span>
                    <span>${t} em aberto</span>
                </div>
            </div>
        `},getStatusBadge(e,t=null,a=null){const r=this.getStatusMeta(e,t,a);return`
            <span
                class="parc-card-badge ${r.badgeClass}"
                ${M(r.label,r.tooltip)}>
                <i data-lucide="${r.icon}" style="width:12px;height:12px"></i>
                ${v(r.label)}
            </span>
        `},createCardHTML({parc:e,statusBadge:t,mes:a,ano:r,itensPendentes:o,itensPagos:n,totalItens:l,progresso:c,referenceMeta:u,dueMeta:p,statusMeta:f}){const h=this.getResumoPrincipal(e,p,f,o,n,l),b=this.getProgressoSection(l,o,n,c,f),S=Number.parseInt(String(e.cartao?.id??e.cartao_id??0),10)||0,B=D("importacoes",{import_target:"cartao",...S>0?{cartao_id:S}:{}}),P=e.cartao&&(e.cartao.nome||e.cartao.bandeira)||"Cartao",g=e.cartao?.conta?.instituicao_financeira?.nome||"Sem instituicao",C=e.cartao?.ultimos_digitos?`Final ${e.cartao.ultimos_digitos}`:"",k=this.getAccentColorSolid(e.cartao),_=e.cartao?.bandeira?.toLowerCase()||"outros",R=this.getBandeiraIcon(_),O=u.hasReference?`${String(u.month).padStart(2,"0")}/${u.year}`:a&&r?`${a}/${r}`:"Fatura atual",ae=u.isCurrentMonth?`Mes atual · ${O}`:O,re=u.isCurrentMonth?"fatura-card-period is-current":"fatura-card-period",oe=u.isCurrentMonth?'<span class="fatura-list-kicker fatura-list-kicker--current">Fatura do mes</span>':u.isPastReference&&f.badgeClass==="badge-paga"?'<span class="fatura-list-kicker fatura-list-kicker--history">Historico pago</span>':"",se=[v(g),C?v(C):""].filter(Boolean).join(" - "),ne=D(`faturas/${e.id}`);return`
            <div class="fatura-card-shell" style="--fatura-accent:${k};">
                <div class="fatura-card-top">
                    <div class="fatura-card-media">
                        <div class="fatura-card-brand" aria-hidden="true">
                            ${R}
                        </div>
                    </div>

                    <div class="fatura-card-head">
                        <div class="fatura-card-title-wrap">
                            <span class="fatura-card-title">${v(P)}</span>
                            <span class="fatura-card-subtitle">${v(g)}</span>
                        </div>
                        <div class="fatura-card-meta">
                            <span class="${re}" ${M("Periodo da fatura","Competencia consolidada desta fatura para acompanhar fechamento e vencimento.")}>
                                <i data-lucide="calendar-days"></i>
                                <span>${v(ae)}</span>
                            </span>
                            ${t}
                        </div>
                    </div>
                </div>

                <div class="fatura-list-info">
                    ${oe}
                    <span class="list-cartao-nome">${v(P)}</span>
                    <span class="list-periodo">${v(O)}</span>
                    <span class="list-cartao-numero">${se}</span>
                </div>

                <div class="fatura-resumo-principal">${h}</div>
                ${b}
                <div class="fatura-status-col">${t}</div>
                <div class="parc-card-actions">
                    <a
                        class="parc-btn parc-btn-import"
                        href="${v(B)}"
                        data-no-transition="true"
                        title="Importar esta fatura/cartão"
                    >
                        <i data-lucide="upload"></i>
                        <span>Importar</span>
                    </a>
                    <a
                        class="parc-btn parc-btn-view"
                        href="${v(ne)}"
                        data-no-transition="true">
                        <i data-lucide="eye"></i>
                        <span>Detalhes</span>
                    </a>
                </div>
            </div>
        `}},$e={getDetalhesTarget(){return s.detailPageContent||null},isDetailPageMode(){return!!(s.detailPageEl&&s.detailPageContent)},setDetailPageLoading(e){this.isDetailPageMode()&&(s.detailPageLoading&&(s.detailPageLoading.hidden=!e,s.detailPageLoading.style.display=e?"flex":"none"),s.detailPageContent&&(s.detailPageContent.hidden=e))},updateDetailPageMeta(e){if(!this.isDetailPageMode())return;const t=e.cartao?.nome||e.cartao?.bandeira||"Cartao",a=e.mes_referencia&&e.ano_referencia?`${this.getNomeMesCompleto(e.mes_referencia)} de ${e.ano_referencia}`:e.descricao||`Fatura #${e.id}`,r=e.data_vencimento?d.formatDate(e.data_vencimento):"a definir",o=String(e.status||"pendente").replace(/_/g," ").replace(/\b\w/g,n=>n.toUpperCase());if(s.detailPageTitle&&(s.detailPageTitle.textContent=`${t} - ${a}`),s.detailPageSubtitle&&(s.detailPageSubtitle.textContent=`Vencimento ${r} - Status ${o}.`),s.detailPageShell){const n=this.getAccentColorSolid(e.cartao);s.detailPageShell.style.setProperty("--card-accent",n)}document.title=`${t} - ${a} | Lukrato`},renderDetailPageState({title:e="Fatura indisponivel",message:t="Nao foi possivel carregar os detalhes desta fatura."}={}){if(!this.isDetailPageMode())return;const a=this.getDetalhesTarget();a&&(this.setDetailPageLoading(!1),s.detailPageTitle&&(s.detailPageTitle.textContent=e),s.detailPageSubtitle&&(s.detailPageSubtitle.textContent=t),a.innerHTML=`
            <div class="fat-detail-empty">
                <div class="fat-detail-empty__icon">
                    <i data-lucide="receipt-text"></i>
                </div>
                <h3>${d.escapeHtml(e)}</h3>
                <p>${d.escapeHtml(t)}</p>
                <a class="btn btn-primary" href="${d.escapeHtml(D("faturas"))}" data-no-transition="true">
                    Voltar para faturas
                </a>
            </div>
        `,$())},async showDetalhes(e){const t=this.getDetalhesTarget();if(!t){window.location.href=D(`faturas/${e}`);return}this.setDetailPageLoading(!0);try{const a=await m.API.buscarParcelamento(e),r=E(a,null);if(!r){this.renderDetailPageState({title:"Fatura indisponivel",message:"Esta fatura nao esta mais disponivel para consulta."});return}i.faturaAtual=r,i.currentDetailId=r.id,t.innerHTML=this.renderDetalhes(r),this.updateDetailPageMeta(r),this.setDetailPageLoading(!1),$(),this.attachDetalhesEventListeners(r.id)}catch(a){if(console.error("Erro ao abrir detalhes:",a),a?.status===404){this.renderDetailPageState({title:"Fatura nao encontrada",message:"Ela pode ter sido removida ou voce nao tem mais acesso a este registro."});return}this.renderDetailPageState({title:"Erro ao carregar fatura",message:y(a,"Nao foi possivel carregar os detalhes desta fatura.")})}},attachDetalhesEventListeners(e){const t=this.getDetalhesTarget();if(!t)return;t.querySelectorAll(".th-sortable").forEach(l=>{l.addEventListener("click",()=>{const c=l.dataset.sort;i.sortColumn===c?i.sortDirection=i.sortDirection==="asc"?"desc":"asc":(i.sortColumn=c,i.sortDirection="asc"),i.faturaAtual&&(t.innerHTML=this.renderDetalhes(i.faturaAtual),$(),this.attachDetalhesEventListeners(e))})}),t.querySelectorAll(".btn-pagar, .btn-desfazer").forEach(l=>{l.addEventListener("click",async c=>{const u=parseInt(c.currentTarget.dataset.lancamentoId,10),p=c.currentTarget.dataset.pago==="true";await this.toggleParcelaPaga(e,u,!p)})}),t.querySelectorAll(".btn-editar").forEach(l=>{l.addEventListener("click",async c=>{const u=parseInt(c.currentTarget.dataset.lancamentoId,10),p=c.currentTarget.dataset.descricao||"",f=parseFloat(c.currentTarget.dataset.valor)||0,h=parseInt(c.currentTarget.dataset.categoriaId,10)||null,b=parseInt(c.currentTarget.dataset.subcategoriaId,10)||null;await this.editarItemFatura(e,u,p,f,h,b)})}),t.querySelectorAll(".btn-excluir").forEach(l=>{l.addEventListener("click",async c=>{const u=parseInt(c.currentTarget.dataset.lancamentoId,10),p=c.currentTarget.dataset.ehParcelado==="true",f=parseInt(c.currentTarget.dataset.totalParcelas)||1;await this.excluirItemFatura(e,u,p,f)})})},renderDetalhes(e){const t=e.progresso||0,{valorPago:a,valorRestante:r}=this.calcularValores(e),o=e.parcelas_pendentes>0&&r>0;return`
            ${this.renderDetalhesHeader(e,o,r)}
            ${this.renderDetalhesGrid(e,t)}
            ${this.renderDetalhesProgresso(e,t,a,r)}
            ${this.renderParcelasTabela(e)}
        `},calcularValores(e){let t=0,a=e.valor_total;return e.parcelas&&e.parcelas.length>0&&(t=e.parcelas.filter(r=>r.pago).reduce((r,o)=>r+parseFloat(o.valor_parcela||o.valor||0),0),a=e.parcelas.filter(r=>!r.pago).reduce((r,o)=>r+parseFloat(o.valor_parcela||o.valor||0),0)),{valorPago:t,valorRestante:a}},renderDetalhesHeader(e,t,a){let r="/";e.data_vencimento?r=d.formatDate(e.data_vencimento):e.mes_referencia&&e.ano_referencia&&(r=`${this.getNomeMes(e.mes_referencia)}/${e.ano_referencia}`);const o=e.parcelas_pendentes===0&&e.parcelas_pagas>0;return`
            <div class="detalhes-header">
                <div class="detalhes-header-content" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <span style="color: #9ca3af; font-size: 0.875rem; font-weight: 500;">Vencimento</span>
                        <h3 class="detalhes-title" style="margin: 0;">${r}</h3>
                    </div>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                        ${t?`
                            <button class="btn-pagar-fatura" 
                                    onclick="window.abrirModalPagarFatura(${e.id}, ${a})"
                                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                                <i data-lucide="credit-card"></i>
                                <span class="btn-text-desktop">Pagar Fatura</span>
                                <span class="btn-text-mobile">Pagar</span>
                            </button>
                        `:""}
                        ${o?`
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
        `},renderDetalhesGrid(e,t){const a=e.parcelas_pagas+e.parcelas_pendentes,r=e.total_estornos&&e.total_estornos>0;return`
            <div class="detalhes-grid">
                <div class="detalhes-item">
                    <span class="detalhes-label">💵 Valor Total a Pagar</span>
                    <span class="detalhes-value detalhes-value-highlight">${d.formatMoney(e.valor_total)}</span>
                </div>
                ${r?`
                <div class="detalhes-item">
                    <span class="detalhes-label">↩️ Estornos/Créditos</span>
                    <span class="detalhes-value" style="color: #10b981;">- ${d.formatMoney(e.total_estornos)}</span>
                </div>
                `:""}
                <div class="detalhes-item">
                    <span class="detalhes-label">📦 Itens</span>
                    <span class="detalhes-value">${a} itens</span>
                </div>
                <div class="detalhes-item">
                    <span class="detalhes-label">📊 Tipo</span>
                    <span class="detalhes-value">💸 Despesas${r?" + ↩️ Estornos":""}</span>
                </div>
                <div class="detalhes-item">
                    <span class="detalhes-label">🎯 Status</span>
                    <span class="detalhes-value">${this.getStatusBadge(e.status,t)}</span>
                </div>
                ${e.cartao?`
                    <div class="detalhes-item">
                        <span class="detalhes-label">💳 Cartão</span>
                        <span class="detalhes-value">${e.cartao.bandeira} ${e.cartao.nome?"- "+d.escapeHtml(e.cartao.nome):""}</span>
                    </div>
                `:""}
            </div>
        `},renderDetalhesProgresso(e,t,a,r){const o=e.parcelas_pagas+e.parcelas_pendentes;return`
            <div class="detalhes-progresso">
                <div class="progresso-info">
                    <span><strong>${e.parcelas_pagas}</strong> de <strong>${o}</strong> itens pagos</span>
                    <span class="progresso-percent"><strong>${Math.round(t)}%</strong></span>
                </div>
                <div class="progresso-barra">
                    <div class="progresso-fill" style="width: ${t}%"></div>
                </div>
                <div class="progresso-valores">
                    <span class="valor-pago">✅ Pago: ${d.formatMoney(a)}</span>
                    <span class="valor-restante">⏳ Restante: ${d.formatMoney(r)}</span>
                </div>
            </div>
        `},renderParcelasTabela(e){const t=o=>i.sortColumn===o?i.sortDirection==="asc"?'<i data-lucide="arrow-up" class="sort-icon active"></i>':'<i data-lucide="arrow-down" class="sort-icon active"></i>':'<i data-lucide="arrow-up-down" class="sort-icon"></i>',a=this.sortParcelas(e.parcelas||[]);let r=`
            <h4 class="parcelas-titulo">📋 Lista de Itens</h4>
            
            <!-- Tabela Desktop -->
            <div class="parcelas-container parcelas-desktop">
                <table class="parcelas-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="th-sortable" data-sort="descricao">Descrição ${t("descricao")}</th>
                            <th>Categoria</th>
                            <th class="th-sortable" data-sort="data_compra">Data Compra ${t("data_compra")}</th>
                            <th class="th-sortable" data-sort="valor">Valor ${t("valor")}</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
        `;return a.length>0?a.forEach((o,n)=>{r+=this.renderParcelaRow(o,n,e.descricao)}):r+=`
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem;">
                        <p style="color: #6b7280;">Nenhuma parcela encontrada</p>
                    </td>
                </tr>
            `,r+=`
                    </tbody>
                </table>
            </div>
            
            <!-- Cards Mobile -->
            <div class="parcelas-container parcelas-mobile">
        `,e.parcelas&&e.parcelas.length>0?this.sortParcelas(e.parcelas).forEach((n,l)=>{r+=this.renderParcelaCard(n,l,e.descricao)}):r+=`
                <div class="parcela-card-empty">
                    <p>Nenhuma parcela encontrada</p>
                </div>
            `,r+="</div>",r},sortParcelas(e){if(!e||e.length===0)return[];const t=[...e],a=i.sortDirection==="asc"?1:-1,r=i.sortColumn;return t.sort((o,n)=>{if(r==="descricao"){const l=(o.descricao||"").toLowerCase(),c=(n.descricao||"").toLowerCase();return l.localeCompare(c)*a}if(r==="data_compra"){const l=o.data_compra||"0000-00-00",c=n.data_compra||"0000-00-00";return l.localeCompare(c)*a}if(r==="valor"){const l=parseFloat(o.valor_parcela||o.valor||0),c=parseFloat(n.valor_parcela||n.valor||0);return(l-c)*a}return 0}),t},getCategoriaMeta(e){const t=e?.categoria||null,a=e?.subcategoria||null,r=typeof t=="object"?String(t?.nome||"").trim():String(t||"").trim(),o=typeof a=="object"?String(a?.nome||"").trim():String(a||"").trim(),n=typeof t=="object"&&t?.icone?String(t.icone):"tag";return{categoriaNome:r,subcategoriaNome:o,icon:n,hasCategoria:r!==""}},renderCategoriaBadge(e){const t=this.getCategoriaMeta(e);if(!t.hasCategoria)return'<span class="fatura-category-empty">Sem categoria</span>';const a=t.subcategoriaNome?`<span class="fatura-category-sub">${d.escapeHtml(t.subcategoriaNome)}</span>`:"";return`
            <span class="fatura-category-pill">
                <i data-lucide="${d.escapeHtml(t.icon)}" style="color:${Ie(t.icon)}"></i>
                <span>${d.escapeHtml(t.categoriaNome)}</span>
                ${a}
            </span>
        `},renderParcelaCard(e,t,a){const r=e.pago,o=e.tipo==="estorno",n=r?"parcela-paga":"parcela-pendente",l=r?"✅ Paga":"⏳ Pendente",c=r?"parcela-card-paga":"",u=`${this.getNomeMes(e.mes_referencia)}/${e.ano_referencia}`,p=`parcela-card-${e.id||t}`;let f=e.descricao||a;f=f.replace(/\s*\(\d+\/\d+\)\s*$/,"");const h=this.renderCategoriaBadge(e);return o?`
                <div class="parcela-card" id="${p}" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%); border-color: rgba(16, 185, 129, 0.4);">
                    <div class="parcela-card-header">
                        <span class="parcela-numero" style="color: #10b981;">↩️ Estorno</span>
                        <span class="parcela-paga" style="background: #10b981;">✅ Creditado</span>
                    </div>
                    <div class="parcela-card-body">
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Descrição</span>
                            <span class="parcela-card-value" style="color: #10b981;">${d.escapeHtml(f)}</span>
                        </div>
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Crédito na Fatura</span>
                            <span class="parcela-card-value parcela-valor" style="color: #10b981; font-weight: 600;">
                                - ${d.formatMoney(Math.abs(e.valor_parcela))}
                            </span>
                        </div>
                    </div>
                    ${e.id?`
                    <div class="parcela-card-footer">
                        <div class="btn-group-parcela">
                            <button class="btn-toggle-parcela btn-excluir"
                                data-lancamento-id="${e.id}"
                                data-eh-parcelado="false"
                                data-total-parcelas="1"
                                title="Excluir estorno">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                    </div>
                    `:""}
                </div>
            `:`
            <div class="parcela-card ${c}" id="${p}">
                <div class="parcela-card-header">
                    <span class="parcela-numero">${e.recorrente?'<i data-lucide="refresh-cw" style="width:12px;height:12px;display:inline-block;vertical-align:middle;color:var(--primary, #e67e22);margin-right:3px;"></i> Recorrente':`${e.numero_parcela||t+1}/${e.total_parcelas||1}`}</span>
                    <span class="${n}">${l}</span>
                </div>
                <div class="parcela-card-body">
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Descrição</span>
                        <span class="parcela-card-value">${d.escapeHtml(f)}${e.recorrente?' <span class="badge-recorrente" title="Assinatura recorrente" style="display:inline-flex;align-items:center;background:rgba(230,126,34,0.15);border-radius:6px;padding:1px 6px;margin-left:6px;"><i data-lucide="refresh-cw" style="width:12px;height:12px;color:var(--primary, #e67e22);"></i></span>':""}</span>
                    </div>
                    ${e.data_compra?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Data Compra</span>
                        <span class="parcela-card-value"><i data-lucide="shopping-cart" style="margin-right: 4px; font-size: 0.75rem;"></i>${d.formatDate(e.data_compra)}</span>
                    </div>
                    `:""}
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Valor</span>
                        <span class="parcela-card-value parcela-valor">${d.formatMoney(e.valor_parcela)}</span>
                    </div>
                </div>
                
                <!-- Detalhes expandíveis -->
                <div class="parcela-card-detalhes" id="detalhes-${p}" style="display: none;">
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Categoria</span>
                        <span class="parcela-card-value">${h}</span>
                    </div>
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Mês/Ano</span>
                        <span class="parcela-card-value">${u}</span>
                    </div>
                    ${r&&e.data_pagamento?`
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
                  
                    ${this.renderParcelaButton(e,r)}
                </div>
            </div>
        `},renderParcelaRow(e,t,a){const r=e.pago,o=e.tipo==="estorno",n=r?"tr-paga":"";let l=e.descricao||a;l=l.replace(/\s*\(\d+\/\d+\)\s*$/,"");const c=e.data_compra?d.formatDate(e.data_compra):"-",u=this.renderCategoriaBadge(e);return o?`
                <tr class="tr-estorno" style="background: rgba(16, 185, 129, 0.1);">
                    <td data-label="#">
                        <span class="parcela-numero" style="color: #10b981;">↩️</span>
                    </td>
                    <td data-label="Descrição" class="td-descricao">
                        <div class="parcela-desc" style="color: #10b981;">${d.escapeHtml(l)}</div>
                    </td>
                    <td data-label="Categoria" class="td-categoria">
                        ${u}
                    </td>
                    <td data-label="Data Compra">
                        <span style="color: #10b981; font-size: 0.85rem;">${c}</span>
                    </td>
                    <td data-label="Valor">
                        <span class="parcela-valor" style="color: #10b981; font-weight: 600;">
                            - ${d.formatMoney(Math.abs(e.valor_parcela))}
                        </span>
                    </td>
                    <td data-label="Ação" class="td-acoes">
                        <div class="btn-group-parcela" style="justify-content: flex-end; gap: 0.5rem;">
                            <span style="color: #10b981; font-size: 0.85rem;">Estorno aplicado</span>
                            ${e.id?`
                            <button class="btn-toggle-parcela btn-excluir"
                                data-lancamento-id="${e.id}"
                                data-eh-parcelado="false"
                                data-total-parcelas="1"
                                title="Excluir estorno">
                                <i data-lucide="trash-2"></i>
                            </button>
                            `:""}
                        </div>
                    </td>
                </tr>
            `:`
            <tr class="${n}">
                <td data-label="#">
                    <span class="parcela-numero">${e.recorrente?'<i data-lucide="refresh-cw" style="width:12px;height:12px;display:inline-block;vertical-align:middle;color:var(--primary, #e67e22);"></i>':`${e.numero_parcela}/${e.total_parcelas}`}</span>
                </td>
                <td data-label="Descrição" class="td-descricao">
                    <div class="parcela-desc">${d.escapeHtml(l)}${e.recorrente?' <span class="badge-recorrente" style="display:inline-flex;align-items:center;background:rgba(230,126,34,0.15);border-radius:6px;padding:1px 6px;margin-left:6px;"><i data-lucide="refresh-cw" style="width:12px;height:12px;color:var(--primary, #e67e22);"></i></span>':""}</div>
                </td>
                <td data-label="Categoria" class="td-categoria">
                    ${u}
                </td>
                <td data-label="Data Compra">
                    <span style="font-size: 0.85rem; color: #9ca3af;">${c}</span>
                </td>
                <td data-label="Valor">
                    <span class="parcela-valor">${d.formatMoney(e.valor_parcela)}</span>
                </td>
                <td data-label="Ação" class="td-acoes">
                    ${this.renderParcelaButton(e,r)}
                </td>
            </tr>
        `},renderParcelaButton(e,t){if(t)return`
                <div class="btn-group-parcela">
                    <span class="badge-pago" style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;">
                        <i data-lucide="check"></i> Pago
                    </span>
                </div>
            `;{const a=e.total_parcelas>1;return`
                <div class="btn-group-parcela">
                    <button class="btn-toggle-parcela btn-editar" 
                        data-lancamento-id="${e.id}"
                        data-descricao="${d.escapeHtml(e.descricao||"")}"
                        data-valor="${e.valor_parcela||0}"
                        data-categoria-id="${e.categoria_id||""}"
                        data-subcategoria-id="${e.subcategoria_id||""}"
                        title="Editar item">
                        <i data-lucide="pencil"></i>
                    </button>
                    <button class="btn-toggle-parcela btn-excluir" 
                        data-lancamento-id="${e.id}"
                        data-eh-parcelado="${a}"
                        data-total-parcelas="${e.total_parcelas||1}"
                        title="Excluir item">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            `}},getNomeMes(e){return["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"][e-1]||e},getNomeMesCompleto(e){return["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"][e-1]||e}};function I(e){const t=Number.parseInt(String(e??""),10);return Number.isFinite(t)&&t>0?t:null}function Fe(e){const t=E(e,[]);return(Array.isArray(t)?t:Array.isArray(t?.categorias)?t.categorias:[]).map(r=>({id:I(r?.id??null),nome:String(r?.nome||"").trim(),tipo:String(r?.tipo||"").trim().toLowerCase(),parentId:I(r?.parent_id??null)})).filter(r=>r.id&&r.nome&&!r.parentId).filter(r=>r.tipo==="despesa"||r.tipo==="ambas"||r.tipo==="").sort((r,o)=>r.nome.localeCompare(o.nome,"pt-BR",{sensitivity:"base"}))}function Be(e){const t=E(e,[]);return(Array.isArray(t?.subcategorias)?t.subcategorias:Array.isArray(t)?t:[]).map(r=>({id:I(r?.id??null),nome:String(r?.nome||"").trim()})).filter(r=>r.id&&r.nome).sort((r,o)=>r.nome.localeCompare(o.nome,"pt-BR",{sensitivity:"base"}))}function Q(){return{categoriaSelect:document.getElementById("editItemCategoria"),subcategoriaSelect:document.getElementById("editItemSubcategoria"),subcategoriaGroup:document.getElementById("editItemSubcategoriaGroup")}}async function Ae(){if(Array.isArray(i.categorias)&&i.categorias.length>0)return i.categorias;const e=await m.API.listarCategorias();return i.categorias=Fe(e),i.categorias}async function Z(e){const t=I(e);if(!t)return[];const a=String(t);if(i.subcategoriasCache.has(a))return i.subcategoriasCache.get(a)||[];const r=await m.API.listarSubcategorias(t),o=Be(r);return i.subcategoriasCache.set(a,o),o}function q(e,t,a,r){if(!e)return;e.innerHTML="";const o=document.createElement("option");o.value="",o.textContent=a,e.appendChild(o),t.forEach(l=>{const c=document.createElement("option");c.value=String(l.id),c.textContent=l.nome,e.appendChild(c)});const n=I(r);e.value=n?String(n):""}async function De(e=null,t=null){const{categoriaSelect:a,subcategoriaSelect:r,subcategoriaGroup:o}=Q();if(!a||!r)return;const n=await Ae();q(a,n,"Sem categoria",e);const l=I(a.value),c=l?await Z(l):[];q(r,c,"Sem subcategoria",t),o&&(o.style.display=c.length>0?"block":"none")}function _e(){const{categoriaSelect:e,subcategoriaSelect:t,subcategoriaGroup:a}=Q();!e||e.dataset.boundFaturaCategoria==="1"||(e.dataset.boundFaturaCategoria="1",e.addEventListener("change",async()=>{const r=I(e.value),o=r?await Z(r):[];q(t,o,"Sem subcategoria",null),a&&(a.style.display=o.length>0?"block":"none")}))}function Me(){return{categoriaId:I(document.getElementById("editItemCategoria")?.value||null),subcategoriaId:I(document.getElementById("editItemSubcategoria")?.value||null)}}let A=null,F=null,L=null;function V(){return{modalEl:document.getElementById("modalDeleteFaturaItemScope"),formEl:document.getElementById("deleteFaturaItemScopeForm"),titleEl:document.getElementById("modalDeleteFaturaItemScopeLabel"),subtitleEl:document.getElementById("deleteFaturaItemScopeModalSubtitle"),leadEl:document.getElementById("deleteFaturaItemScopeModalLead"),hintEl:document.getElementById("deleteFaturaItemScopeModalHint"),optionsEl:document.getElementById("deleteFaturaItemScopeOptions"),confirmButtonEl:document.getElementById("btnConfirmDeleteFaturaItemScope")}}function Te(){const{formEl:e,optionsEl:t}=V();e?.reset();const a=t?.querySelector('input[value="item"]');a&&(a.checked=!0),t&&(t.hidden=!1)}function Le(e=1){const{titleEl:t,subtitleEl:a,leadEl:r,hintEl:o,optionsEl:n,confirmButtonEl:l}=V(),c=Number(e)>1;if(t&&(t.textContent="Excluir item da fatura"),a&&(a.textContent=c?`Este item faz parte de um parcelamento de ${e}x.`:"Revise a exclusão antes de confirmar."),r&&(r.textContent=c?"Escolha se deseja remover apenas esta parcela ou o parcelamento completo.":"Esta ação não pode ser desfeita."),o&&(o.textContent=c?"Excluir todo o parcelamento remove todas as parcelas vinculadas a esta compra.":"O item será removido permanentemente da fatura."),l&&(l.textContent=c?"Continuar":"Excluir item"),n){n.hidden=!c;const p=n.querySelector('[data-delete-fatura-scope-title="item"]'),f=n.querySelector('[data-delete-fatura-scope-text="item"]'),h=n.querySelector('[data-delete-fatura-scope-title="parcelamento"]'),b=n.querySelector('[data-delete-fatura-scope-text="parcelamento"]');p&&(p.textContent="Apenas esta parcela"),f&&(f.textContent="Remove somente o item atual da fatura."),h&&(h.textContent=`Todo o parcelamento (${e} parcelas)`),b&&(b.textContent="Remove todas as parcelas vinculadas a esta compra parcelada.")}const u=n?.querySelector('input[value="item"]');u&&(u.checked=!0)}function Ne(){const e=V();return A?{modal:A,...e}:!e.modalEl||!window.bootstrap?.Modal?null:(window.LK?.modalSystem?.prepareBootstrapModal(e.modalEl,{scope:"page"}),e.modalEl.dataset.bound||(e.modalEl.dataset.bound="1",e.formEl?.addEventListener("submit",t=>{t.preventDefault(),L={scope:e.optionsEl?.querySelector('input[name="deleteFaturaItemScopeOption"]:checked')?.value||"item"},A?.hide()}),e.modalEl.addEventListener("hidden.bs.modal",()=>{const t=F,a=L;F=null,L=null,Te(),typeof t=="function"&&t(a||null)})),A=window.bootstrap.Modal.getOrCreateInstance(e.modalEl,{backdrop:!0,keyboard:!0,focus:!0}),{modal:A,...e})}function ke(e=1){const t=Ne();return t?(typeof F=="function"&&F(null),F=null,L=null,Le(e),new Promise(a=>{F=a,t.modal.show(),requestAnimationFrame(()=>{(Number(e)>1?t.optionsEl?.querySelector('input[name="deleteFaturaItemScopeOption"]:checked'):t.confirmButtonEl)?.focus?.()})})):Promise.resolve(null)}const Re={async toggleParcelaPaga(e,t,a){try{const r=a?"pagar":"desfazer pagamento";if(!(await Swal.fire({title:a?"Marcar como pago?":"Desfazer pagamento?",text:`Deseja realmente ${r} este item?`,icon:"question",showCancelButton:!0,confirmButtonColor:a?"#10b981":"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:a?"Sim, marcar como pago":"Sim, desfazer",cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const n=document.querySelector(".swal2-container");n&&(n.style.zIndex="99999")}})).isConfirmed)return;Swal.fire({title:"Processando...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const n=document.querySelector(".swal2-container");n&&(n.style.zIndex="99999")},customClass:{container:"swal-above-modal"}}),await m.API.toggleItemFatura(e,t,a),await Swal.fire({icon:"success",title:"Sucesso!",text:a?"Item marcado como pago":"Pagamento desfeito",timer:w.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const n=document.querySelector(".swal2-container");n&&(n.style.zIndex="99999")}}),await m.App.refreshAfterMutation(e)}catch(r){console.error("Erro ao alternar status:",r),Swal.fire({icon:"error",title:"Erro",text:y(r,"Erro ao processar operação"),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const o=document.querySelector(".swal2-container");o&&(o.style.zIndex="99999")}})}},async editarItemFatura(e,t,a,r,o=null,n=null){const l=s.modalEditarItemFatura||document.getElementById("modalEditarItemFatura");if(!l){console.error("Modal de edição não encontrado");return}window.LK?.modalSystem?.prepareBootstrapModal(l,{scope:"page"}),_e(),document.getElementById("editItemFaturaId").value=e,document.getElementById("editItemId").value=t,document.getElementById("editItemDescricao").value=a,document.getElementById("editItemValor").value=r.toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2}),await De(o,n),bootstrap.Modal.getOrCreateInstance(l,{backdrop:!0,keyboard:!0,focus:!0}).show()},async salvarItemFatura(){const e=document.getElementById("editItemFaturaId").value,t=document.getElementById("editItemId").value,a=document.getElementById("editItemDescricao").value.trim(),r=document.getElementById("editItemValor").value,{categoriaId:o,subcategoriaId:n}=Me();if(!a){Swal.fire({icon:"warning",title:"Atenção",text:"Informe a descrição do item.",timer:2e3,showConfirmButton:!1});return}const l=parseFloat(r.replace(/\./g,"").replace(",","."))||0;if(l<=0){Swal.fire({icon:"warning",title:"Atenção",text:"Informe um valor válido.",timer:2e3,showConfirmButton:!1});return}try{const c=s.modalEditarItemFatura||document.getElementById("modalEditarItemFatura"),u=bootstrap.Modal.getInstance(c);u&&u.hide(),Swal.fire({title:"Atualizando item...",html:"Aguarde enquanto salvamos as alterações.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading()}}),await m.API.atualizarItemFatura(e,t,{descricao:a,valor:l,categoria_id:o,subcategoria_id:n}),await Swal.fire({icon:"success",title:"Item Atualizado!",text:"O item foi atualizado com sucesso.",timer:w.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1}),await m.App.refreshAfterMutation(e)}catch(c){console.error("Erro ao editar item:",c),Swal.fire({icon:"error",title:"Erro",text:y(c,"Não foi possível atualizar o item."),heightAuto:!1})}},async excluirItemFatura(e,t,a,r){try{const o="Excluir Item?",n="Deseja realmente excluir este item da fatura?",l="Sim, excluir item";if(a&&r>1){const u=await ke(r);if(!u?.scope)return;if(u.scope==="parcelamento")return await this.excluirParcelamentoCompleto(e,t,r)}if(!(await Swal.fire({title:o,text:n,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:l,cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const u=document.querySelector(".swal2-container");u&&(u.style.zIndex="99999")}})).isConfirmed)return;Swal.fire({title:"Excluindo...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const u=document.querySelector(".swal2-container");u&&(u.style.zIndex="99999")},customClass:{container:"swal-above-modal"}}),await m.API.excluirItemFatura(e,t),await Swal.fire({icon:"success",title:"Excluído!",text:"Item removido da fatura.",timer:w.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const u=document.querySelector(".swal2-container");u&&(u.style.zIndex="99999")}}),await m.App.refreshAfterMutation(e)}catch(o){console.error("Erro ao excluir item:",o),Swal.fire({icon:"error",title:"Erro",text:y(o,"Não foi possível excluir o item."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const n=document.querySelector(".swal2-container");n&&(n.style.zIndex="99999")}})}},async excluirParcelamentoCompleto(e,t,a){if((await Swal.fire({title:"Excluir Parcelamento Completo?",html:`
                <p>Deseja realmente excluir <strong>todas as ${a} parcelas</strong> deste parcelamento?</p>
                <p style="color: #ef4444; margin-top: 1rem;"><i data-lucide="triangle-alert"></i> Esta ação não pode ser desfeita!</p>
            `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:`Sim, excluir ${a} parcelas`,cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const o=document.querySelector(".swal2-container");o&&(o.style.zIndex="99999"),$()}})).isConfirmed){Swal.fire({title:"Excluindo parcelamento...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const o=document.querySelector(".swal2-container");o&&(o.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});try{const o=await m.API.excluirParcelamentoDoItem(e,t);await Swal.fire({icon:"success",title:"Parcelamento Excluído!",text:o.message||`${a} parcelas removidas.`,timer:w.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const n=document.querySelector(".swal2-container");n&&(n.style.zIndex="99999")}}),await m.App.refreshAfterMutation(e)}catch(o){console.error("Erro ao excluir parcelamento:",o),Swal.fire({icon:"error",title:"Erro",text:y(o,"Não foi possível excluir o parcelamento."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const n=document.querySelector(".swal2-container");n&&(n.style.zIndex="99999")}})}}},async pagarFaturaCompleta(e,t){try{Swal.fire({title:"Carregando...",html:"Buscando informações da fatura e contas disponíveis.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const g=document.querySelector(".swal2-container");g&&(g.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});const[a,r]=await Promise.all([m.API.buscarParcelamento(e),m.API.listarContas()]),o=E(a,null),n=E(r,[]);if(!o?.cartao)throw new Error("Dados da fatura incompletos");const l=o.cartao.id,c=o.cartao.conta_id||null,p=(o.descricao||"").match(/(\d+)\/(\d+)/),f=p?p[1]:null,h=p?p[2]:null;if(!f||!h)throw new Error("Não foi possível identificar o mês/ano da fatura");let b="";if(Array.isArray(n)&&n.length>0)n.forEach(g=>{const C=g.saldoAtual??g.saldo_atual??g.saldo??0,k=d.formatMoney(C),_=g.id===c,R=C>=t;b+=`<option value="${g.id}" ${_?"selected":""} ${R?"":'style="color: #dc2626;"'}>
                        ${d.escapeHtml(g.nome)} - ${k}${_?" (vinculada ao cartão)":""}
                    </option>`});else throw new Error("Nenhuma conta disponível para débito");const S=await Swal.fire({title:"Pagar Fatura Completa?",html:`
                    <p>Deseja realmente pagar todos os itens pendentes desta fatura?</p>
                    <div style="margin: 1.5rem 0; padding: 1rem; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                        <div style="font-size: 0.875rem; color: #047857; margin-bottom: 0.5rem;">Valor Total:</div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: #059669;">${d.formatMoney(t)}</div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; text-align: left; margin-bottom: 0.5rem; color: #374151; font-weight: 500;">
                            <i data-lucide="landmark"></i> Conta para débito:
                        </label>
                        <select id="swalContaSelect" class="swal2-select" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                            ${b}
                        </select>
                    </div>
                    <p style="color: #6b7280; font-size: 0.875rem;">O valor será debitado da conta selecionada.</p>
                `,icon:"question",showCancelButton:!0,confirmButtonColor:"#10b981",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="check"></i> Sim, pagar tudo',cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const g=document.querySelector(".swal2-container");g&&(g.style.zIndex="99999"),$()},preConfirm:()=>{const g=document.getElementById("swalContaSelect"),C=g?parseInt(g.value):null;return C?{contaId:C}:(Swal.showValidationMessage("Selecione uma conta para débito"),!1)}});if(!S.isConfirmed)return;const B=S.value.contaId;Swal.fire({title:"Processando pagamento...",html:"Aguarde enquanto processamos o pagamento de todos os itens.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const g=document.querySelector(".swal2-container");g&&(g.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});const P=await m.API.pagarFaturaCompleta(l,parseInt(f),parseInt(h),B);if(!P.success)throw new Error(P.message||"Erro ao processar pagamento");await Swal.fire({icon:"success",title:"Fatura Paga!",html:`
                    <p>${P.message||"Fatura paga com sucesso!"}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor debitado:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${d.formatMoney(E(P,{})?.valor_pago||t)}
                        </div>
                    </div>
                    <div style="color: #059669;">
                        <i data-lucide="circle-check" style="font-size: 2rem;"></i>
                    </div>
                `,timer:3e3,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const g=document.querySelector(".swal2-container");g&&(g.style.zIndex="99999"),$()}}),await m.App.refreshAfterMutation(e)}catch(a){console.error("Erro ao pagar fatura completa:",a),Swal.fire({icon:"error",title:"Erro ao pagar fatura",text:y(a,"Não foi possível processar o pagamento. Tente novamente."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const r=document.querySelector(".swal2-container");r&&(r.style.zIndex="99999")}})}}},Oe={showLoading(){s.loadingEl.style.display="flex",s.containerEl.style.display="none",s.emptyStateEl.style.display="none"},hideLoading(){s.loadingEl.style.display="none"},showEmpty(){s.containerEl.style.display="none",s.emptyStateEl.style.display="block"},...xe,...$e,...Re};m.UI=Oe;const ee={async init(){try{if(this.attachEventListeners(),this.isDetailPage()){await this.carregarDetalhePagina();return}this.initViewToggle(),this.aplicarFiltrosURL(),await this.carregarCartoes(),await this.carregarParcelamentos()}catch(e){console.error("Erro ao inicializar:",e),Swal.fire({icon:"error",title:"Erro de Inicializacao",text:"Nao foi possivel carregar a pagina. Tente recarregar."})}},isDetailPage(){return!!(s.detailPageEl&&s.detailPageContent)},isListPage(){return!!(s.containerEl&&s.loadingEl&&s.emptyStateEl)},async carregarDetalhePagina(){const e=Number.parseInt(String(s.detailPageEl?.dataset.faturaId??""),10);if(!Number.isInteger(e)||e<=0){m.UI.renderDetailPageState({title:"Fatura invalida",message:"Nao foi possivel identificar a fatura solicitada."});return}i.currentDetailId=e,await m.UI.showDetalhes(e)},async refreshAfterMutation(e=null){const t=Number.parseInt(String(e??i.currentDetailId??i.faturaAtual?.id??0),10);if(this.isListPage()){await this.carregarParcelamentos();return}!Number.isInteger(t)||t<=0||this.isDetailPage()&&(i.currentDetailId=t,await m.UI.showDetalhes(t))},goToIndex(){window.location.href=D("faturas")},initViewToggle(){const e=document.querySelector(".view-toggle"),t=s.containerEl;if(!e||!t)return;const a=e.querySelectorAll(".view-btn"),r=localStorage.getItem("faturas_view_mode")||"grid",o=document.getElementById("faturasListHeader"),n=l=>{const c=l==="list";t.classList.toggle("list-view",c),t.dataset.viewMode=l,e.dataset.currentView=l,o&&o.classList.toggle("visible",c),localStorage.setItem("faturas_view_mode",l),this.updateViewToggleState(a,l)};n(r),a.forEach(l=>{l.addEventListener("click",()=>{n(l.dataset.view)})})},updateViewToggleState(e,t){e.forEach(a=>{const r=a.dataset.view===t;r?a.classList.add("active"):a.classList.remove("active"),a.setAttribute("aria-pressed",r?"true":"false")})},getMesLabel(e){return["","Janeiro","Fevereiro","Marco","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"][Number(e)]||String(e||"")},getStatusLabel(e){return{pendente:"Pendente",parcial:"Parcial",paga:"Paga",cancelado:"Cancelado"}[e]||e||""},getParcelamentoReferenceMeta(e){let t=Number.parseInt(String(e?.mes_referencia??""),10),a=Number.parseInt(String(e?.ano_referencia??""),10);if((!Number.isInteger(t)||t<1||t>12||!Number.isInteger(a)||a<1900)&&e?.descricao){const c=String(e.descricao).match(/(\d{1,2})\/(\d{4})/);c&&(t=Number.parseInt(c[1],10),a=Number.parseInt(c[2],10))}if((!Number.isInteger(t)||t<1||t>12||!Number.isInteger(a)||a<1900)&&e?.data_vencimento){const c=new Date(`${e.data_vencimento}T00:00:00`);Number.isNaN(c.getTime())||(t=c.getMonth()+1,a=c.getFullYear())}const r=new Date,o=r.getMonth()+1,n=r.getFullYear(),l=Number.isInteger(t)&&Number.isInteger(a)&&t===o&&a===n;return{month:t,year:a,isCurrentMonth:l}},getCurrentFocusSummary(e=i.parcelamentos){if(!e.find(r=>this.getParcelamentoReferenceMeta(r).isCurrentMonth))return"";const a=new Date;return`${this.getMesLabel(a.getMonth()+1)} em destaque`},buildFilterBadges(){const e=[];if(i.filtros.status&&e.push({key:"status",label:this.getStatusLabel(i.filtros.status)}),i.filtros.cartao_id){const t=i.cartoes.find(r=>r.id==i.filtros.cartao_id),a=t?t.nome_cartao||t.nome:"Cartao";e.push({key:"cartao_id",label:a})}return i.filtros.ano&&e.push({key:"ano",label:String(i.filtros.ano)}),i.filtros.mes&&e.push({key:"mes",label:this.getMesLabel(i.filtros.mes)}),e},formatResultsCount(e){return e===0?"Nenhuma fatura encontrada":e===1?"1 fatura visivel":`${e} faturas visiveis`},buildContextSummary(e=i.parcelamentos){const t=[],a=new Date().getFullYear();if(i.filtros.status&&t.push(this.getStatusLabel(i.filtros.status)),i.filtros.cartao_id){const r=i.cartoes.find(n=>n.id==i.filtros.cartao_id),o=r?r.nome_cartao||r.nome:"Cartao";t.push(o)}if(i.filtros.mes&&i.filtros.ano?t.push(`${this.getMesLabel(i.filtros.mes)} de ${i.filtros.ano}`):i.filtros.ano&&t.push(`Ano ${i.filtros.ano}`),t.length===0)return this.getCurrentFocusSummary(e)||"Visao completa";if(t.length===1&&t[0]===`Ano ${a}`&&!i.filtros.status&&!i.filtros.cartao_id&&!i.filtros.mes){const r=this.getCurrentFocusSummary(e);return r?`${r} · ${t[0]}`:t[0]}return t.join(" · ")},updatePageSummaries(e=i.parcelamentos.length,t=!1){const a=this.buildFilterBadges().length,r=a===1?"filtro ativo":"filtros ativos";s.filtersSummary&&(t?s.filtersSummary.textContent=a>0?`Aplicando ${a} ${r}...`:"Carregando resumo da busca...":a>0?s.filtersSummary.textContent=`${a} ${r} · ${e===1?"1 fatura no recorte":`${e} faturas no recorte`}`:s.filtersSummary.textContent=e===1?"1 fatura disponivel":`${e} faturas disponiveis`),s.resultsSummary&&(s.resultsSummary.textContent=t?"Carregando faturas...":this.formatResultsCount(e)),s.contextSummary&&(s.contextSummary.textContent=this.buildContextSummary(i.parcelamentos))},aplicarFiltrosURL(){const e=new URLSearchParams(window.location.search);if(e.has("cartao_id")&&(i.filtros.cartao_id=e.get("cartao_id"),s.filtroCartao&&(s.filtroCartao.value=i.filtros.cartao_id)),e.has("mes")&&e.has("ano")&&(i.filtros.mes=parseInt(e.get("mes"),10),i.filtros.ano=parseInt(e.get("ano"),10),window.monthPicker)){const t=new Date(i.filtros.ano,i.filtros.mes-1);window.monthPicker.setDate(t)}e.has("status")&&(i.filtros.status=e.get("status"),s.filtroStatus&&(s.filtroStatus.value=i.filtros.status))},async carregarCartoes(){try{const e=await m.API.listarCartoes(),t=E(e,[]);i.cartoes=Array.isArray(t)?t:[],this.preencherSelectCartoes(),this.sincronizarFiltrosComSelects()}catch(e){console.error("Erro ao carregar cartoes:",e)}},sincronizarFiltrosComSelects(){s.filtroStatus&&i.filtros.status&&(s.filtroStatus.value=i.filtros.status),s.filtroCartao&&i.filtros.cartao_id&&(s.filtroCartao.value=i.filtros.cartao_id),s.filtroAno&&i.filtros.ano&&(s.filtroAno.value=i.filtros.ano),s.filtroMes&&i.filtros.mes&&(s.filtroMes.value=i.filtros.mes)},preencherSelectCartoes(){s.filtroCartao&&(s.filtroCartao.innerHTML='<option value="">Todos os cartoes</option>',i.cartoes.forEach(e=>{const t=document.createElement("option");t.value=e.id;const a=e.nome_cartao||e.nome||e.bandeira||"Cartao",r=e.ultimos_digitos?` •••• ${e.ultimos_digitos}`:"";t.textContent=a+r,s.filtroCartao.appendChild(t)}))},preencherSelectAnos(e=[]){if(!s.filtroAno)return;const t=s.filtroAno.value,a=new Date().getFullYear();if(s.filtroAno.innerHTML='<option value="">Todos os anos</option>',e.length>0){const r=[...e].sort((o,n)=>o-n);r.includes(a)||(r.push(a),r.sort((o,n)=>o-n)),r.forEach(o=>{const n=document.createElement("option");n.value=o,n.textContent=o,s.filtroAno.appendChild(n)})}else{const r=document.createElement("option");r.value=a,r.textContent=a,s.filtroAno.appendChild(r)}t?s.filtroAno.value=t:(s.filtroAno.value=a,i.filtros.ano=a),this.sincronizarFiltrosComSelects()},extrairAnosDisponiveis(e){const t=new Set;return e.forEach(a=>{const o=(a.descricao||"").match(/(\d{1,2})\/(\d{4})/);if(o&&t.add(parseInt(o[2],10)),a.data_vencimento){const n=new Date(a.data_vencimento).getFullYear();t.add(n)}}),Array.from(t)},async carregarParcelamentos(){this.updatePageSummaries(i.parcelamentos.length,!0),m.UI.showLoading();try{const e=await m.API.listarParcelamentos({status:i.filtros.status||"",cartao_id:i.filtros.cartao_id||"",mes:i.filtros.mes||"",ano:i.filtros.ano||""}),t=E(e,{}),a=t?.faturas||[];if(i.parcelamentos=a,!i.anosCarregados){const r=t?.anos_disponiveis||this.extrairAnosDisponiveis(a);this.preencherSelectAnos(r),i.anosCarregados=!0}m.UI.renderParcelamentos(a),this.updatePageSummaries(a.length)}catch(e){console.error("Erro ao carregar parcelamentos:",e),m.UI.showEmpty(),this.updatePageSummaries(0),Swal.fire({icon:"error",title:"Erro ao carregar",text:y(e,"Nao foi possivel carregar os parcelamentos")})}finally{m.UI.hideLoading()}},async cancelarParcelamento(e){try{await m.API.cancelarParcelamento(e),await Swal.fire({icon:"success",title:"Cancelado",text:"Parcelamento cancelado com sucesso",timer:w.TIMEOUTS.successMessage,showConfirmButton:!1}),await this.carregarParcelamentos()}catch(t){console.error("Erro ao cancelar:",t),Swal.fire({icon:"error",title:"Erro ao cancelar",text:y(t,"Nao foi possivel cancelar o parcelamento")})}},attachEventListeners(){s.toggleFilters&&s.toggleFilters.addEventListener("click",r=>{r.stopPropagation(),this.toggleFilters()});const e=document.querySelector(".filters-header");e&&e.addEventListener("click",()=>{this.toggleFilters()}),s.btnFiltrar&&s.btnFiltrar.addEventListener("click",()=>{this.aplicarFiltros()}),s.btnLimparFiltros&&s.btnLimparFiltros.addEventListener("click",()=>{this.limparFiltros()}),[s.filtroStatus,s.filtroCartao,s.filtroAno,s.filtroMes].forEach(r=>{r&&r.addEventListener("keypress",o=>{o.key==="Enter"&&this.aplicarFiltros()})});const t=document.getElementById("btnSalvarItemFatura");t&&t.addEventListener("click",()=>{m.UI.salvarItemFatura()});const a=document.getElementById("formEditarItemFatura");a&&a.addEventListener("submit",r=>{r.preventDefault(),m.UI.salvarItemFatura()})},toggleFilters(){s.filtersContainer&&s.filtersContainer.classList.toggle("collapsed")},aplicarFiltros(){i.filtros.status=s.filtroStatus?.value||"",i.filtros.cartao_id=s.filtroCartao?.value||"",i.filtros.ano=s.filtroAno?.value||"",i.filtros.mes=s.filtroMes?.value||"",this.atualizarBadgesFiltros(),this.carregarParcelamentos()},limparFiltros(){s.filtroStatus&&(s.filtroStatus.value=""),s.filtroCartao&&(s.filtroCartao.value=""),s.filtroAno&&(s.filtroAno.value=""),s.filtroMes&&(s.filtroMes.value=""),i.filtros={status:"",cartao_id:"",ano:"",mes:""},this.atualizarBadgesFiltros(),this.carregarParcelamentos()},atualizarBadgesFiltros(){if(!s.activeFilters)return;const e=this.buildFilterBadges();e.length>0?(s.activeFilters.style.display="flex",s.activeFilters.innerHTML=e.map(t=>`
                <span class="filter-badge">
                    ${t.label}
                    <button class="filter-badge-remove" data-filter="${t.key}" title="Remover filtro">
                        <i data-lucide="x"></i>
                    </button>
                </span>
            `).join(""),window.lucide&&lucide.createIcons(),s.activeFilters.querySelectorAll(".filter-badge-remove").forEach(t=>{t.addEventListener("click",a=>{const r=a.currentTarget.dataset.filter;this.removerFiltro(r)})})):(s.activeFilters.style.display="none",s.activeFilters.innerHTML="")},removerFiltro(e){i.filtros[e]="";const t={status:s.filtroStatus,cartao_id:s.filtroCartao,ano:s.filtroAno,mes:s.filtroMes};t[e]&&(t[e].value=""),this.atualizarBadgesFiltros(),this.carregarParcelamentos()}};m.App=ee;const H={instance:null,faturaId:null,valorTotal:null,cartaoId:null,mes:null,ano:null,contas:[],contaPadraoId:null,init(){const e=s.modalPagarFatura||document.getElementById("modalPagarFatura");e&&(window.LK?.modalSystem?.prepareBootstrapModal(e,{scope:"page"}),this.instance=bootstrap.Modal.getOrCreateInstance(e,{backdrop:!0,keyboard:!0,focus:!0}),this.attachEvents())},attachEvents(){document.getElementById("btnPagarTotal")?.addEventListener("click",()=>{this.instance.hide(),m.UI.pagarFaturaCompleta(this.faturaId,this.valorTotal)}),document.getElementById("btnPagarParcial")?.addEventListener("click",()=>{this.mostrarFormularioParcial()}),document.getElementById("btnVoltarEscolha")?.addEventListener("click",()=>{this.mostrarEscolha()}),document.getElementById("btnConfirmarPagamento")?.addEventListener("click",()=>{this.confirmarPagamentoParcial()});const e=document.getElementById("valorPagamentoParcial");e&&(e.addEventListener("input",t=>{let a=t.target.value.replace(/\D/g,"");if(a===""){t.target.value="";return}a=(parseInt(a)/100).toFixed(2),t.target.value=parseFloat(a).toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2})}),e.addEventListener("focus",t=>{t.target.select()})),document.querySelectorAll(".btn-opcao-pagamento").forEach(t=>{t.addEventListener("mouseenter",()=>{t.style.transform="translateY(-2px)",t.style.boxShadow="0 8px 25px rgba(0,0,0,0.2)"}),t.addEventListener("mouseleave",()=>{t.style.transform="translateY(0)",t.style.boxShadow="none"})})},async abrir(e,t){this.faturaId=e,this.valorTotal=t,document.getElementById("pagarFaturaId").value=e,document.getElementById("pagarFaturaValorTotal").value=t,document.getElementById("valorTotalDisplay").textContent=d.formatMoney(t),document.getElementById("valorTotalInfo").textContent=`Valor total da fatura: ${d.formatMoney(t)}`,document.getElementById("valorPagamentoParcial").value=d.formatMoney(t).replace("R$ ",""),this.mostrarEscolha(),await this.carregarDados(),this.instance.show()},async carregarDados(){try{const[e,t]=await Promise.all([m.API.buscarParcelamento(this.faturaId),m.API.listarContas()]),a=E(e,null);if(this.contas=E(t,[]),!a?.cartao)throw new Error("Dados da fatura incompletos");this.cartaoId=a.cartao.id,this.contaPadraoId=a.cartao.conta_id||null;const o=(a.descricao||"").match(/(\d+)\/(\d+)/);this.mes=o?parseInt(o[1]):null,this.ano=o?parseInt(o[2]):null,this.popularSelectContas()}catch(e){console.error("Erro ao carregar dados:",e),Swal.fire({icon:"error",title:"Erro",text:y(e,"Erro ao carregar dados da fatura.")})}},popularSelectContas(){const e=document.getElementById("contaPagamentoFatura");if(e){if(e.innerHTML="",!Array.isArray(this.contas)||this.contas.length===0){e.innerHTML='<option value="">Nenhuma conta disponível</option>';return}this.contas.forEach(t=>{const a=t.saldoAtual??t.saldo_atual??t.saldo??0,r=d.formatMoney(a),o=t.id===this.contaPadraoId,n=document.createElement("option");n.value=t.id,n.textContent=`${t.nome} - ${r}${o?" (vinculada ao cartão)":""}`,o&&(n.selected=!0),e.appendChild(n)})}},mostrarEscolha(){document.getElementById("pagarFaturaEscolha").style.display="block",document.getElementById("pagarFaturaFormParcial").style.display="none",document.getElementById("pagarFaturaFooter").style.display="none"},mostrarFormularioParcial(){document.getElementById("pagarFaturaEscolha").style.display="none",document.getElementById("pagarFaturaFormParcial").style.display="block",document.getElementById("pagarFaturaFooter").style.display="flex",setTimeout(()=>{const e=document.getElementById("valorPagamentoParcial");e&&(e.focus(),e.select())},100)},async confirmarPagamentoParcial(){const e=document.getElementById("valorPagamentoParcial").value,t=document.getElementById("contaPagamentoFatura").value,a=parseFloat(e.replace(/\./g,"").replace(",","."))||0;if(a<=0){Swal.fire({icon:"warning",title:"Valor inválido",text:"Digite um valor válido para o pagamento.",timer:2e3,showConfirmButton:!1});return}if(a>this.valorTotal){Swal.fire({icon:"warning",title:"Valor inválido",text:`O valor não pode ser maior que ${d.formatMoney(this.valorTotal)}`,timer:2e3,showConfirmButton:!1});return}if(!t){Swal.fire({icon:"warning",title:"Conta não selecionada",text:"Selecione uma conta para débito.",timer:2e3,showConfirmButton:!1});return}if(!this.cartaoId||!this.mes||!this.ano){Swal.fire({icon:"error",title:"Erro",text:"Dados da fatura incompletos. Tente novamente."});return}this.instance.hide(),Swal.fire({title:"Processando pagamento...",html:"Aguarde enquanto processamos o pagamento.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>Swal.showLoading()});try{const r=await m.API.pagarFaturaParcial(this.cartaoId,this.mes,this.ano,parseInt(t,10),a);if(!r.success)throw new Error(y(r,"Erro ao processar pagamento"));await Swal.fire({icon:"success",title:"Pagamento Realizado!",html:`
                    <p>${r.message||"Pagamento efetuado com sucesso!"}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor pago:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${d.formatMoney(a)}
                        </div>
                    </div>
                `,timer:3e3,showConfirmButton:!1}),await m.App.refreshAfterMutation(this.faturaId)}catch(r){console.error("Erro ao pagar fatura:",r),Swal.fire({icon:"error",title:"Erro ao pagar fatura",text:y(r,"Não foi possível processar o pagamento. Tente novamente.")})}}};async function ze(e){const t=i.faturaAtual;if(!t||!t.cartao||!t.mes_referencia||!t.ano_referencia){Swal.fire({icon:"error",title:"Erro",text:"Dados da fatura incompletos para reverter o pagamento."});return}if((await Swal.fire({title:"Desfazer Pagamento?",html:`
            <p>Você está prestes a <strong>reverter o pagamento</strong> de todos os itens desta fatura.</p>
            <div style="margin: 1rem 0; padding: 0.75rem; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <p style="margin: 0; color: #92400e; font-size: 0.875rem;">
                    <i data-lucide="triangle-alert"></i> 
                    O lançamento de pagamento será excluído e o valor voltará para a conta.
                </p>
            </div>
        `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#f59e0b",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="undo-2"></i> Sim, reverter',cancelButtonText:"Cancelar",didOpen:()=>{window.lucide&&lucide.createIcons()}})).isConfirmed)try{Swal.fire({title:"Revertendo pagamento...",html:"Aguarde enquanto processamos a reversão.",allowOutsideClick:!1,didOpen:()=>Swal.showLoading()});const r=t.cartao.id,o=t.mes_referencia,n=t.ano_referencia,l=await m.API.desfazerPagamentoFatura(r,o,n);if(l.success)await Swal.fire({icon:"success",title:"Pagamento Revertido!",html:`
                    <p>${l.message||"O pagamento foi revertido com sucesso."}</p>
                    <p style="color: #059669; margin-top: 0.5rem;">
                        <i data-lucide="circle-check"></i> 
                        ${E(l,{})?.itens_revertidos||0} item(s) voltou(aram) para pendente.
                    </p>
                `,timer:3e3,showConfirmButton:!1,didOpen:()=>{window.lucide&&lucide.createIcons()}}),await m.App.refreshAfterMutation(e);else throw new Error(y(l,"Erro ao reverter pagamento"))}catch(r){console.error("Erro ao reverter pagamento:",r),Swal.fire({icon:"error",title:"Erro",text:y(r,"Não foi possível reverter o pagamento.")})}}m.ModalPagarFatura=H;let T=null,W=!1,z=null;async function qe(){return we("faturas")}async function Ve(e){await ye("faturas",e)}function He(){const e=Ee(),t=e?.pageCapabilities?.pageKey==="faturas"&&e?.pageCapabilities?.customizer&&typeof e.pageCapabilities.customizer=="object"?e.pageCapabilities.customizer:null,a=t?.descriptor&&typeof t.descriptor=="object"?t.descriptor:null,r=a?.sectionMap&&typeof a.sectionMap=="object"?a.sectionMap:{},o=t?.completePreferences&&typeof t.completePreferences=="object"?t.completePreferences:{},n=t?.essentialPreferences&&typeof t.essentialPreferences=="object"?t.essentialPreferences:{},l=a?.ids&&typeof a.ids=="object"?{overlayId:a.ids.overlay,openButtonId:a.trigger?.id||"btnCustomizeFaturas",closeButtonId:a.ids.close,saveButtonId:a.ids.save,presetEssentialButtonId:a.ids.presetEssential,presetCompleteButtonId:a.ids.presetComplete}:void 0;return{capabilities:t,sectionMap:r,completeDefaults:o,essentialDefaults:n,modalConfig:l}}function N(){const e=He();return T||Object.keys(e.sectionMap).length===0?{customizer:T,resolved:e}:(T=be({storageKey:"lk_faturas_prefs",sectionMap:e.sectionMap,completeDefaults:e.completeDefaults,essentialDefaults:e.essentialDefaults,capabilities:e.capabilities,loadPreferences:qe,savePreferences:Ve,modal:e.modalConfig}),{customizer:T,resolved:e})}function te(){const e=()=>{const{customizer:t}=N();return t?(W||(t.init(),W=!0),!0):!1};e()||z||(z=X({},{silent:!0}).finally(()=>{z=null,e()}))}m.Customize={init:te,open:()=>{const{customizer:e}=N();if(e?.open){e.open();return}X({},{silent:!0}).finally(()=>{const{customizer:t}=N();t?.open?.()})},close:()=>{const{customizer:e}=N();e?.close?.()}};window.abrirModalPagarFatura=(e,t)=>H.abrir(e,t);window.reverterPagamentoFaturaGlobal=ze;window.__LK_PARCELAMENTOS_LOADER__||(window.__LK_PARCELAMENTOS_LOADER__=!0,document.addEventListener("DOMContentLoaded",()=>{Ce(),document.getElementById("faturaDetalhePage")||te(),ee.init(),H.init()}));
