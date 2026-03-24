import{a as Ce}from"./utils-Bj4jxwhy.js";import{d as k,e as pe}from"./api-Dkfcp6ON.js";const q="Relatórios são exclusivos do plano Pro.",u={BASE_URL:window.LK?.getBase?.()||"/",CHART_COLORS:["#E67E22","#2C3E50","#2ECC71","#F39C12","#9B59B6","#1ABC9C","#E74C3C","#3498DB"],FETCH_TIMEOUT:3e4,VIEWS:{CATEGORY:"category",BALANCE:"balance",COMPARISON:"comparison",ACCOUNTS:"accounts",CARDS:"cards",EVOLUTION:"evolution",ANNUAL_SUMMARY:"annual_summary",ANNUAL_CATEGORY:"annual_category"}},Se=new Set([u.VIEWS.ANNUAL_SUMMARY,u.VIEWS.ANNUAL_CATEGORY]),z={[u.VIEWS.CATEGORY]:[{value:"despesas_por_categoria",label:"Despesas por categoria"},{value:"receitas_por_categoria",label:"Receitas por categoria"}],[u.VIEWS.ANNUAL_CATEGORY]:[{value:"despesas_anuais_por_categoria",label:"Despesas anuais por categoria"},{value:"receitas_anuais_por_categoria",label:"Receitas anuais por categoria"}]},L={ACTIVE_SECTION:"rel_active_section",ACTIVE_VIEW:"rel_active_view",CATEGORY_TYPE:"rel_category_type",ANNUAL_CATEGORY_TYPE:"rel_annual_category_type"},re={overview:{kicker:"Painel consolidado",title:"Leia seu mes com contexto",description:"Veja seu pulso financeiro, identifique sinais importantes e acompanhe a evolucao do periodo em um resumo rapido."},relatorios:{kicker:"Relatorio ativo",title:"Transforme lancamentos em decisao",description:"Explore seus numeros por categoria, conta, cartao e evolucao para descobrir onde agir."},insights:{kicker:"Leitura automatica",title:"Insights que ajudam a agir",description:"Receba sinais claros sobre gastos, saldo, concentracoes e oportunidades sem precisar interpretar tudo manualmente."},comparativos:{kicker:"Comparacao temporal",title:"Compare e ajuste sua rota",description:"Entenda o que melhorou, piorou ou estagnou em relacao ao mes e ao ano anteriores."}},ne={[u.VIEWS.CATEGORY]:{title:"Categorias do periodo",description:"Encontre rapidamente onde seu dinheiro esta concentrado por categoria."},[u.VIEWS.BALANCE]:{title:"Saldo diario",description:"Acompanhe como seu caixa evolui ao longo do periodo."},[u.VIEWS.COMPARISON]:{title:"Receitas x despesas",description:"Compare entradas e saidas para entender pressao ou folga no caixa."},[u.VIEWS.ACCOUNTS]:{title:"Desempenho por conta",description:"Descubra quais contas concentram mais entradas e saidas."},[u.VIEWS.CARDS]:{title:"Saude dos cartoes",description:"Monitore faturas, uso de limite e sinais de atencao nos cartoes."},[u.VIEWS.EVOLUTION]:{title:"Evolucao em 12 meses",description:"Observe tendencia, sazonalidade e ritmo financeiro ao longo do ultimo ano."},[u.VIEWS.ANNUAL_SUMMARY]:{title:"Resumo anual",description:"Compare mes a mes como receitas, despesas e saldo se comportaram no ano."},[u.VIEWS.ANNUAL_CATEGORY]:{title:"Categorias do ano",description:"Veja quais categorias dominaram seu ano e onde houve maior concentracao."}};function me(){const e=new Date;return`${e.getFullYear()}-${String(e.getMonth()+1).padStart(2,"0")}`}const b=e=>String(e??"").replace(/[&<>"']/g,function(a){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[a]??a});function Ae(e,a="#cccccc"){return/^#[0-9A-Fa-f]{6}$/.test(e)?e:a}const n={activeSection:"overview",currentView:u.VIEWS.CATEGORY,categoryType:"despesas_por_categoria",annualCategoryType:"despesas_anuais_por_categoria",currentMonth:me(),currentAccount:null,chart:null,accounts:[],accessRestricted:!1,lastReportError:null,activeDrilldown:null,reportDetails:null},x={getCurrentMonth:me,formatCurrency(e){return Ce(e)},formatMonthLabel(e){const[a,t]=e.split("-");return new Date(a,t-1).toLocaleDateString("pt-BR",{month:"long",year:"numeric"})},addMonths(e,a){const[t,s]=e.split("-").map(Number),o=new Date(t,s-1+a);return`${o.getFullYear()}-${String(o.getMonth()+1).padStart(2,"0")}`},hexToRgba(e,a=.25){const t=parseInt(e.slice(1,3),16),s=parseInt(e.slice(3,5),16),o=parseInt(e.slice(5,7),16);return`rgba(${t}, ${s}, ${o}, ${a})`},generateShades(e,a){const t=parseInt(e.slice(1,3),16),s=parseInt(e.slice(3,5),16),o=parseInt(e.slice(5,7),16),r=[];for(let l=0;l<a;l++){const i=.35-l/Math.max(a-1,1)*.7,c=d=>Math.min(255,Math.max(0,Math.round(d+(i>0?(255-d)*i:d*i)))),p=c(t),h=c(s),m=c(o);r.push(`#${p.toString(16).padStart(2,"0")}${h.toString(16).padStart(2,"0")}${m.toString(16).padStart(2,"0")}`)}return r},isYearlyView(e=n.currentView){return Se.has(e)},extractFilename(e){if(!e)return null;const a=/filename\*=UTF-8''([^;]+)/i.exec(e);if(a)try{return decodeURIComponent(a[1])}catch{return a[1]}const t=/filename="?([^";]+)"?/i.exec(e);return t?t[1]:null},getCssVar(e,a=""){try{return(getComputedStyle(document.documentElement).getPropertyValue(e)||"").trim()||a}catch{return a}},isLightTheme(){try{return(document.documentElement?.getAttribute("data-theme")||"dark")==="light"}catch{return!1}},getReportType(){return{[u.VIEWS.CATEGORY]:n.categoryType,[u.VIEWS.ANNUAL_CATEGORY]:n.annualCategoryType,[u.VIEWS.BALANCE]:"saldo_mensal",[u.VIEWS.COMPARISON]:"receitas_despesas_diario",[u.VIEWS.ACCOUNTS]:"receitas_despesas_por_conta",[u.VIEWS.CARDS]:"cartoes_credito",[u.VIEWS.EVOLUTION]:"evolucao_12m",[u.VIEWS.ANNUAL_SUMMARY]:"resumo_anual"}[n.currentView]??n.categoryType},getActiveCategoryType(){return n.currentView===u.VIEWS.ANNUAL_CATEGORY?n.annualCategoryType:n.categoryType}},I={},T=e=>x.formatCurrency(e),F=e=>String(e??"").replace(/[&<>"']/g,a=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"})[a]||a);function H(){const e=(document.documentElement.getAttribute("data-theme")||"").toLowerCase()==="light"||x.isLightTheme?.();return{isLight:e,mode:e?"light":"dark",textColor:e?"#2c3e50":"#ffffff",textMuted:e?"#6c757d":"rgba(255, 255, 255, 0.7)",gridColor:e?"rgba(0, 0, 0, 0.08)":"rgba(255, 255, 255, 0.05)",surfaceColor:getComputedStyle(document.documentElement).getPropertyValue("--color-surface").trim()}}function K(e=[]){return e.map(a=>{const t=Number(a);return Number.isFinite(t)?t:0})}function ie(e,a=380){const t=e?.closest(".chart-container")||e?.parentElement,s=t?getComputedStyle(t):null,o=s?Number.parseFloat(s.height):Number.NaN,r=s?Number.parseFloat(s.minHeight):Number.NaN,l=t?.getBoundingClientRect?.().height??Number.NaN,i=s?(Number.parseFloat(s.paddingTop)||0)+(Number.parseFloat(s.paddingBottom)||0):0,c=window.innerWidth<768?320:a,p=[o,l,r,c].find(h=>Number.isFinite(h)&&h>0)??c;return Math.max(260,Math.round(p-i))}const w={_currentEntries:null,destroy(){n.chart&&(Array.isArray(n.chart)?n.chart.forEach(e=>e?.destroy()):n.chart.destroy(),n.chart=null),w._drilldownChart&&(w._drilldownChart.destroy(),w._drilldownChart=null),n.activeDrilldown=null,n.reportDetails=null},setupDefaults(){const e=getComputedStyle(document.documentElement).getPropertyValue("--color-text").trim();window.Apex=window.Apex||{},window.Apex.chart={foreColor:e,fontFamily:"Inter, Arial, sans-serif"},window.Apex.grid={borderColor:"rgba(255, 255, 255, 0.1)"}},renderPie(e){const{labels:a=[],values:t=[],details:s=null,cat_ids:o=null}=e;if(!a.length||!t.some(y=>y>0))return I.UI.showEmptyState();let r=a.map((y,C)=>({label:y,value:Number(t[C])||0,color:u.CHART_COLORS[C%u.CHART_COLORS.length],catId:o?o[C]??null:null})).filter(y=>y.value>0).sort((y,C)=>C.value-y.value);!o&&s&&(r=r.map(y=>{const C=s.find(M=>M.label===y.label);return{...y,catId:C?.cat_id??null}}));const l=window.innerWidth<768;let i=r;if(l&&r.length>5){const y=r.slice(0,5),M=r.slice(5).reduce((P,j)=>P+j.value,0);i=[...y,{label:"Outros",value:M,color:"#95a5a6",isOthers:!0}]}const c=!l&&i.length>2,p=c?Math.ceil(i.length/2):i.length,h=c?[i.slice(0,p),i.slice(p)].filter(y=>y.length):[i],m=`
            <div class="chart-container chart-container-pie">
                <div class="chart-dual">
                    ${h.map((y,C)=>`
                        <div class="chart-wrapper chart-wrapper-pie">
                            <div id="chart${C}"></div>
                        </div>
                    `).join("")}
                </div>
            </div>
            <div id="subcategoryDrilldown" class="drilldown-panel" aria-hidden="true"></div>
            ${l?'<div id="categoryListMobile" class="category-list-mobile"></div>':""}
        `;I.UI.setContent(m),w.destroy(),n.reportDetails=s,n.activeDrilldown=null,w._currentEntries=i;const d=x.getActiveCategoryType(),E={receitas_por_categoria:"Receitas por Categoria",despesas_por_categoria:"Despesas por Categoria",receitas_anuais_por_categoria:"Receitas anuais por Categoria",despesas_anuais_por_categoria:"Despesas anuais por Categoria"}[d]||"Distribuição por Categoria",f=H();let R=0;n.chart=h.map((y,C)=>{const M=document.getElementById(`chart${C}`);if(!M)return null;const P=y.reduce((A,U)=>A+U.value,0),j=R;R+=y.length;const ae=new ApexCharts(M,{chart:{type:"donut",height:"100%",background:"transparent",fontFamily:"Inter, Arial, sans-serif",events:{dataPointSelection:(A,U,se)=>{const oe=j+se.dataPointIndex,G=i[oe];!G||G.isOthers||w.handlePieClick(G,oe,se.dataPointIndex,C)},dataPointMouseEnter:A=>{A.target&&(A.target.style.cursor="pointer")},dataPointMouseLeave:A=>{A.target&&(A.target.style.cursor="default")}}},series:y.map(A=>A.value),labels:y.map(A=>A.label),colors:y.map(A=>A.color),stroke:{width:2,colors:[f.surfaceColor]},plotOptions:{pie:{donut:{size:"60%"},expandOnClick:!0}},legend:{show:!l,position:"bottom",labels:{colors:f.textColor},markers:{shape:"circle"}},title:{text:h.length>1?`${E} - Parte ${C+1}`:E,align:"center",style:{fontSize:"14px",fontWeight:"bold",color:f.textColor}},tooltip:{theme:f.mode,y:{formatter:A=>{const U=P>0?(A/P*100).toFixed(1):"0";return`${T(A)} (${U}%)`}}},dataLabels:{enabled:!1},theme:{mode:f.mode}});return ae.render(),ae}),l&&w.renderMobileCategoryList(i)},renderMobileCategoryList(e){const a=document.getElementById("categoryListMobile");if(!a)return;const t=e.reduce((r,l)=>r+l.value,0),s=!!n.reportDetails&&window.IS_PRO,o=e.map((r,l)=>{const i=(r.value/t*100).toFixed(1),c=s&&r.catId!=null?n.reportDetails.find(d=>d.cat_id===r.catId):null,p=c&&c.subcategories&&c.subcategories.filter(d=>d.id!==0).length>0,h=p?'<i data-lucide="chevron-down" class="category-chevron"></i>':"";let m="";if(p){const d=x.generateShades(r.color,c.subcategories.length);m=`
                    <div class="category-subcats-panel" id="mobileSubcatPanel-${l}" aria-hidden="true">
                        ${c.subcategories.map((g,E)=>{const f=c.total>0?(g.total/c.total*100).toFixed(1):"0.0";return`
                                <div class="drilldown-item drilldown-item-mobile">
                                    <div class="drilldown-indicator" style="background-color: ${d[E]}"></div>
                                    <div class="drilldown-info">
                                        <span class="drilldown-name">${F(g.label)}</span>
                                    </div>
                                    <div class="drilldown-values">
                                        <span class="drilldown-value">${T(g.total)}</span>
                                        <span class="drilldown-pct">${f}%</span>
                                    </div>
                                </div>
                            `}).join("")}
                    </div>
                `}return`
                <div class="category-item ${p?"has-subcats":""}"
                     ${p?`data-subcat-toggle="${l}"`:""}>
                    <div class="category-indicator" style="background-color: ${r.color}"></div>
                    <div class="category-info">
                        <span class="category-name">${F(r.label)}</span>
                        <span class="category-value">${T(r.value)}</span>
                    </div>
                    <span class="category-percentage">${i}%</span>
                    ${h}
                </div>
                ${m}
            `}).join("");a.innerHTML=`
            <button class="category-expand-btn" id="expandCategoriesBtn" aria-expanded="false">
                <span>Ver todas as categorias</span>
                <i data-lucide="chevron-down"></i>
            </button>
            <div class="category-expandable-card" id="expandableCard" aria-hidden="true">
                ${o}
            </div>
            ${s?"":`<p class="category-info-text">
                <i data-lucide="info"></i>
                Para visualizar todas as categorias detalhadamente, exporte este relatório em PDF.
            </p>`}
        `,window.lucide&&lucide.createIcons(),w.setupExpandToggle(),s&&w.setupMobileSubcatToggles()},setupMobileSubcatToggles(){document.querySelectorAll("[data-subcat-toggle]").forEach(e=>{e.addEventListener("click",function(){const a=this.dataset.subcatToggle,t=document.getElementById(`mobileSubcatPanel-${a}`),s=this.querySelector(".category-chevron");if(!t)return;t.getAttribute("aria-hidden")==="false"?(t.style.maxHeight="0px",t.setAttribute("aria-hidden","true"),s&&(s.style.transform="rotate(0deg)")):(t.style.maxHeight=t.scrollHeight+"px",t.setAttribute("aria-hidden","false"),s&&(s.style.transform="rotate(180deg)"))})})},setupExpandToggle(){const e=document.getElementById("expandCategoriesBtn"),a=document.getElementById("expandableCard");!e||!a||e.addEventListener("click",function(){e.getAttribute("aria-expanded")==="true"?(a.style.maxHeight="0px",a.setAttribute("aria-hidden","true"),e.setAttribute("aria-expanded","false"),e.querySelector("span").textContent="Ver todas as categorias",e.querySelector("i").style.transform="rotate(0deg)"):(a.style.maxHeight=a.scrollHeight+"px",a.setAttribute("aria-hidden","false"),e.setAttribute("aria-expanded","true"),e.querySelector("span").textContent="Ocultar categorias",e.querySelector("i").style.transform="rotate(180deg)")})},handlePieClick(e,a,t,s){if(!window.IS_PRO){window.Swal?.fire&&Swal.fire({icon:"info",title:"Recurso Premium",html:"O detalhamento por <b>subcategorias</b> é exclusivo do <b>plano Pro</b>.<br>Faça upgrade para desbloquear!",confirmButtonText:"Fazer Upgrade",showCancelButton:!0,cancelButtonText:"Agora não",confirmButtonColor:"#f59e0b",cancelButtonColor:"#64748b"}).then(i=>{i.isConfirmed&&(window.location.href=u.BASE_URL+"billing")});return}if(!n.reportDetails)return;const o=e.catId,r=n.reportDetails.find(i=>i.cat_id===o);if(!r||!r.subcategories||r.subcategories.length===0)return;if(r.subcategories.filter(i=>i.id!==0).length===0){window.Swal?.fire&&Swal.fire({icon:"info",title:"Sem subcategorias",text:"Atribua subcategorias aos seus lançamentos para ver o detalhamento desta categoria.",confirmButtonText:"Entendi",confirmButtonColor:"#f59e0b",timer:5e3,timerProgressBar:!0});return}if(n.activeDrilldown===o){w.closeDrilldown();return}n.activeDrilldown=o,w.renderSubcategoryDrilldown(r,e.color)},closeDrilldown(){n.activeDrilldown=null;const e=document.getElementById("subcategoryDrilldown");e&&(e.style.maxHeight="0px",e.setAttribute("aria-hidden","true"),setTimeout(()=>{e.innerHTML=""},400))},renderSubcategoryDrilldown(e,a){const t=document.getElementById("subcategoryDrilldown");if(!t)return;const{label:s,total:o,subcategories:r}=e,l=x.generateShades(a,r.length),i=r.map((h,m)=>{const d=o>0?(h.total/o*100).toFixed(1):"0.0",g=o>0?(h.total/o*100).toFixed(0):"0";return`
                <div class="drilldown-item" style="animation-delay: ${m*.05}s">
                    <div class="drilldown-indicator" style="background-color: ${l[m]}"></div>
                    <div class="drilldown-info">
                        <span class="drilldown-name">${F(h.label)}</span>
                        <div class="drilldown-bar-bg">
                            <div class="drilldown-bar" style="width: ${g}%; background-color: ${l[m]}"></div>
                        </div>
                    </div>
                    <div class="drilldown-values">
                        <span class="drilldown-value">${T(h.total)}</span>
                        <span class="drilldown-pct">${d}%</span>
                    </div>
                </div>
            `}).join(""),c=window.innerWidth<768,p=c?"":`
            <div class="drilldown-mini-chart">
                <div id="drilldownMiniChart"></div>
            </div>
        `;t.innerHTML=`
            <div class="drilldown-header" style="border-left-color: ${a}">
                <div class="drilldown-title">
                    <span class="drilldown-cat-indicator" style="background-color: ${a}"></span>
                    <h4>${F(s)}</h4>
                    <span class="drilldown-total">${T(o)}</span>
                </div>
                <button class="drilldown-close" id="drilldownCloseBtn" aria-label="Fechar detalhamento">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="drilldown-body">
                ${p}
                <div class="drilldown-list">
                    ${i}
                </div>
            </div>
        `,t.setAttribute("aria-hidden","false"),requestAnimationFrame(()=>{t.style.maxHeight=t.scrollHeight+"px"}),document.getElementById("drilldownCloseBtn")?.addEventListener("click",()=>{w.closeDrilldown()}),c||w._renderDrilldownMiniChart(r,l),window.lucide&&lucide.createIcons()},_renderDrilldownMiniChart(e,a){const t=document.getElementById("drilldownMiniChart");if(!t)return;w._drilldownChart&&(w._drilldownChart.destroy(),w._drilldownChart=null);const s=H(),o=e.reduce((r,l)=>r+l.total,0);w._drilldownChart=new ApexCharts(t,{chart:{type:"donut",height:"100%",background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:e.map(r=>r.total),labels:e.map(r=>r.label),colors:a,stroke:{width:2,colors:[s.surfaceColor]},plotOptions:{pie:{donut:{size:"55%"}}},legend:{show:!1},tooltip:{theme:s.mode,y:{formatter:r=>{const l=o>0?(r/o*100).toFixed(1):"0";return`${T(r)} (${l}%)`}}},dataLabels:{enabled:!1},theme:{mode:s.mode}}),w._drilldownChart.render()},_drilldownChart:null,renderLine(e){const{labels:a=[],values:t=[]}={...e,values:K(e?.values)};if(!a.length)return I.UI.showEmptyState();I.UI.setContent(`
            <div class="chart-container chart-container-line">
                <div class="chart-wrapper chart-wrapper-line">
                    <div id="chart0"></div>
                </div>
            </div>
        `),w.destroy();const s=getComputedStyle(document.documentElement).getPropertyValue("--color-primary").trim(),o=H(),r=document.getElementById("chart0"),l=ie(r,420),i=new ApexCharts(r,{chart:{type:"area",height:l,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,redrawOnParentResize:!0,redrawOnWindowResize:!0},series:[{name:"Saldo Diário",data:t.map(Number)}],xaxis:{categories:a,labels:{style:{fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:o.isLight?"#000":"#fff",fontSize:"11px"},formatter:c=>T(c)}},colors:[s],stroke:{curve:"smooth",width:2.5},fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.4,opacityTo:.05,stops:[0,100]}},markers:{size:4,hover:{size:6}},grid:{borderColor:o.gridColor,strokeDashArray:4},tooltip:{theme:o.mode,y:{formatter:c=>T(c)}},legend:{position:"bottom",labels:{colors:o.textColor}},title:{text:"Evolução do Saldo Mensal",align:"center",style:{fontSize:"16px",fontWeight:"bold",color:o.textColor}},dataLabels:{enabled:!1},theme:{mode:o.mode}});i.render(),n.chart=i},renderBar(e){const{labels:a=[],receitas:t=[],despesas:s=[]}={...e,receitas:K(e?.receitas),despesas:K(e?.despesas)};if(!a.length)return I.UI.showEmptyState();I.UI.setContent(`
            <div class="chart-container chart-container-bar">
                <div class="chart-wrapper chart-wrapper-bar">
                    <div id="chart0"></div>
                </div>
            </div>
        `),w.destroy();const o=x.getCssVar("--color-success","#2ecc71"),r=x.getCssVar("--color-danger","#e74c3c"),l=H(),i=document.getElementById("chart0"),c=ie(i,420),p=n.currentView===u.VIEWS.ACCOUNTS?"Receitas x Despesas por Conta":n.currentView===u.VIEWS.ANNUAL_SUMMARY?"Resumo Anual por Mês":"Receitas x Despesas",h=new ApexCharts(i,{chart:{type:"bar",height:c,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,redrawOnParentResize:!0,redrawOnWindowResize:!0},series:[{name:"Receitas",data:t.map(Number)},{name:"Despesas",data:s.map(Number)}],xaxis:{categories:a,labels:{style:{colors:l.textMuted,fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:l.isLight?"#000":"#fff",fontSize:"11px"},formatter:m=>T(m)}},colors:[o,r],plotOptions:{bar:{borderRadius:6,columnWidth:"55%"}},grid:{borderColor:l.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:l.mode,shared:!0,intersect:!1,y:{formatter:m=>T(m)}},legend:{position:"bottom",labels:{colors:l.textColor},markers:{shape:"circle"}},title:{text:p,align:"center",style:{fontSize:"16px",fontWeight:"bold",color:l.textColor}},dataLabels:{enabled:!1},theme:{mode:l.mode}});h.render(),n.chart=h}};I.ChartManager=w;const v=e=>x.formatCurrency(e),he=e=>x.formatMonthLabel(e),V=e=>x.isYearlyView(e),xe=()=>x.getReportType(),ge=()=>x.getActiveCategoryType();function Z(e=n.currentAccount){return e?n.accounts.find(a=>String(a.id)===String(e))?.name||`Conta #${e}`:null}function J(){return V()?`Ano ${n.currentMonth.split("-")[0]}`:he(n.currentMonth)}function ce(){const e=ge();return(z[n.currentView]||[]).find(t=>t.value===e)?.label||null}function _e(e=n.activeSection){return e==="relatorios"||e==="comparativos"}function $e(e=n.activeSection){return re[e]||re.overview}function Q(e=n.currentView){return ne[e]||ne[u.VIEWS.CATEGORY]}function ve(){try{localStorage.setItem(L.ACTIVE_VIEW,n.currentView),localStorage.setItem(L.CATEGORY_TYPE,n.categoryType),localStorage.setItem(L.ANNUAL_CATEGORY_TYPE,n.annualCategoryType)}catch{}}function te(){typeof window.openBillingModal=="function"?window.openBillingModal():location.href=`${u.BASE_URL}billing`}async function fe(e){const a=e||q;window.Swal?.fire?(await Swal.fire({title:"Recurso exclusivo",text:a,icon:"info",showCancelButton:!0,confirmButtonText:"Assinar plano Pro",cancelButtonText:"Agora não",reverseButtons:!0,focusConfirm:!0})).isConfirmed&&te():confirm(`${a}

Deseja ir para a página de planos agora?`)&&te()}async function B(e){if(!e)return!1;const a=Number(e.status||e?.data?.status||0);if(a===401){const t=encodeURIComponent(location.pathname+location.search);return location.href=`${u.BASE_URL}login?return=${t}`,!0}if(a===403){let t=q;if(e?.data?.message)t=e.data.message;else if(typeof e?.clone=="function")try{const s=await e.clone().json();s?.message&&(t=s.message)}catch{}return n.accessRestricted||(n.accessRestricted=!0,await fe(t)),S.showPaywall(t),!0}return!1}function Te(e){typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"error",title:e,showConfirmButton:!1,timer:4e3,timerProgressBar:!0})}const $={async fetchReportData(){n.lastReportError=null;const e=new AbortController,a=setTimeout(()=>e.abort(),u.FETCH_TIMEOUT);try{const t=await k(`${u.BASE_URL}api/reports`,{type:x.getReportType(),year:n.currentMonth.split("-")[0],month:n.currentMonth.split("-")[1],account_id:n.currentAccount||void 0});return clearTimeout(a),n.accessRestricted=!1,n.lastReportError=null,t.data||t}catch(t){return clearTimeout(a),await B(t)||(n.lastReportError=t.name==="AbortError"?"A requisição demorou demais. Tente novamente em instantes.":"Não foi possível carregar o relatório agora. Verifique a conexão e tente novamente.",console.error("Error fetching report data:",t),Te(pe(t,"Erro ao carregar relatório. Verifique sua conexão."))),null}},async fetchReportDataForType(e,a={}){const t=new URLSearchParams({type:e,year:n.currentMonth.split("-")[0],month:n.currentMonth.split("-")[1]}),s=Object.prototype.hasOwnProperty.call(a,"accountId")?a.accountId:n.currentAccount;s&&t.set("account_id",s);try{const o=await k(`${u.BASE_URL}api/reports`,Object.fromEntries(t.entries()));return o.data||o}catch{return null}},async fetchAccounts(){try{const e=await k(`${u.BASE_URL}api/contas`);n.accessRestricted=!1;const a=e.data||e.items||e||[];return(Array.isArray(a)?a:[]).map(t=>({id:Number(t.id),name:t.nome||t.apelido||t.instituicao||`Conta #${t.id}`}))}catch(e){return await B(e)?[]:(console.error("Error fetching accounts:",e),[])}},async fetchSummaryStats(){const[e,a]=n.currentMonth.split("-"),t=new AbortController,s=setTimeout(()=>t.abort(),u.FETCH_TIMEOUT);try{const o=await k(`${u.BASE_URL}api/reports/summary`,{year:e,month:a});return clearTimeout(s),o.data||o}catch(o){return clearTimeout(s),await B(o)?{totalReceitas:0,totalDespesas:0,saldo:0,totalCartoes:0}:(console.error("Error fetching summary stats:",o),{totalReceitas:0,totalDespesas:0,saldo:0,totalCartoes:0})}},async fetchInsights(){const[e,a]=n.currentMonth.split("-"),t=new AbortController,s=setTimeout(()=>t.abort(),u.FETCH_TIMEOUT);try{const o=await k(`${u.BASE_URL}api/reports/insights`,{year:e,month:a});return clearTimeout(s),o.data||o}catch(o){return clearTimeout(s),await B(o)?{insights:[]}:(console.error("Error fetching insights:",o),{insights:[]})}},async fetchInsightsTeaser(){const[e,a]=n.currentMonth.split("-"),t=new AbortController,s=setTimeout(()=>t.abort(),u.FETCH_TIMEOUT);try{const o=await k(`${u.BASE_URL}api/reports/insights-teaser`,{year:e,month:a});return clearTimeout(s),o.data||o}catch(o){return clearTimeout(s),console.error("Error fetching insights teaser:",o),{insights:[],totalCount:0,isTeaser:!0}}},async fetchComparatives(){const[e,a]=n.currentMonth.split("-"),t=new URLSearchParams({year:e,month:a});n.currentAccount&&t.set("account_id",n.currentAccount);const s=new AbortController,o=setTimeout(()=>s.abort(),u.FETCH_TIMEOUT);try{const r=await k(`${u.BASE_URL}api/reports/comparatives`,Object.fromEntries(t.entries()));return clearTimeout(o),r.data||r}catch(r){return clearTimeout(o),await B(r)||console.error("Error fetching comparatives:",r),null}}};I.API=$;const S={setContent(e){const a=document.getElementById("reportArea");a&&(a.innerHTML=e,a.setAttribute("aria-busy","false"),window.lucide&&lucide.createIcons())},showLoading(){const e=document.getElementById("reportArea");e&&(e.setAttribute("aria-busy","true"),e.innerHTML=`
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Carregando relatório...</p>
                </div>
            `,window.lucide&&lucide.createIcons())},showEmptyState(){const e=Z(),a=Q(),t=J(),s=e?`Nenhum dado foi encontrado para ${e} em ${t}.`:`Não há lançamentos suficientes para montar este recorte em ${t}.`;S.setContent(`
            <div class="empty-state report-empty-state">
                <i data-lucide="pie-chart"></i>
                <h3>${b(a.title)}</h3>
                <p>${b(s)}</p>
                <div class="report-state-actions">
                    <a href="${u.BASE_URL}lancamentos" class="empty-cta">
                        <i data-lucide="plus"></i>
                        <span>Adicionar lançamento</span>
                    </a>
                    ${e?`
                        <button type="button" class="btn btn-secondary" data-action="clear-report-account">
                            <i data-lucide="layers"></i>
                            <span>Mostrar todas as contas</span>
                        </button>
                    `:""}
                </div>
            </div>
        `)},showErrorState(e){const a=b(e||"Não foi possível carregar este relatório.");S.setContent(`
            <div class="error-state report-error-state">
                <i data-lucide="triangle-alert"></i>
                <p class="error-message">${a}</p>
                <div class="report-state-actions">
                    <button type="button" class="btn btn-primary btn-retry" data-action="retry-report">
                        <i data-lucide="refresh-cw"></i>
                        <span>Tentar novamente</span>
                    </button>
                    ${n.currentAccount?`
                        <button type="button" class="btn btn-secondary" data-action="clear-report-account">
                            <i data-lucide="layers"></i>
                            <span>Voltar para todas as contas</span>
                        </button>
                    `:""}
                </div>
            </div>
        `)},showPaywall(e=q){const a=document.getElementById("reportArea");if(!a)return;const t=b(e||q);a.setAttribute("aria-busy","false"),a.innerHTML=`
            <div class="paywall-message" role="alert">
                <i data-lucide="crown" aria-hidden="true"></i>
                <h3>Recurso Premium</h3>
                <p>${t}</p>
                <button type="button" class="btn-upgrade" data-action="go-pro">
                    Fazer Upgrade para PRO
                </button>
            </div>
        `,window.lucide&&lucide.createIcons();const s=a.querySelector('[data-action="go-pro"]');s&&s.addEventListener("click",te)},updateMonthLabel(){const e=document.getElementById("monthLabel");e&&(e.textContent=V()?n.currentMonth.split("-")[0]:he(n.currentMonth))},updatePageContext(){const e=document.getElementById("reportsContextKicker"),a=document.getElementById("reportsContextTitle"),t=document.getElementById("reportsContextDescription"),s=document.getElementById("reportsContextChips"),o=document.getElementById("reportsContextActions");if(!e||!a||!t||!s||!o)return;const r=$e(),l=Q(),i=J(),c=Z(),p=_e(),h=ce(),m=!window.IS_PRO&&n.activeSection==="insights";e.textContent=r.kicker,a.textContent=n.activeSection==="relatorios"?l.title:r.title,t.textContent=n.activeSection==="relatorios"?l.description:r.description;const d=[`<span class="context-chip"><i data-lucide="calendar-range"></i><span>${b(i)}</span></span>`];n.activeSection==="relatorios"&&h&&d.push(`<span class="context-chip context-chip-highlight"><i data-lucide="filter"></i><span>${b(h)}</span></span>`),c&&p?d.push(`<span class="context-chip context-chip-highlight"><i data-lucide="landmark"></i><span>${b(c)}</span></span>`):c&&!p?d.push(`<span class="context-chip"><i data-lucide="bookmark"></i><span>Filtro salvo: ${b(c)}</span></span>`):d.push('<span class="context-chip"><i data-lucide="layers"></i><span>Consolidado</span></span>'),m&&d.push('<span class="context-chip context-chip-pro"><i data-lucide="crown"></i><span>Preview PRO</span></span>'),s.innerHTML=d.join(""),o.innerHTML=c?`
            <button type="button" class="context-action-btn" data-action="clear-report-account">
                <i data-lucide="eraser"></i>
                <span>Limpar filtro de conta</span>
            </button>
        `:"",window.lucide&&lucide.createIcons()},updateReportFilterSummary(){const e=document.getElementById("reportFilterSummary"),a=document.getElementById("reportScopeNote");if(!e||!a)return;const t=[`<span class="report-filter-chip"><i data-lucide="calendar-range"></i><span>${b(J())}</span></span>`,`<span class="report-filter-chip"><i data-lucide="bar-chart-3"></i><span>${b(Q().title)}</span></span>`],s=ce();s&&t.push(`<span class="report-filter-chip"><i data-lucide="filter"></i><span>${b(s)}</span></span>`),n.currentAccount?t.push(`<span class="report-filter-chip report-filter-chip-highlight"><i data-lucide="landmark"></i><span>${b(Z())}</span></span>`):t.push('<span class="report-filter-chip"><i data-lucide="layers"></i><span>Todas as contas</span></span>'),e.innerHTML=t.join(""),a.classList.remove("hidden"),a.innerHTML=n.currentAccount?'<i data-lucide="info"></i><span>O resumo do topo continua consolidado. O filtro por conta afeta este gráfico e a aba Comparativos.</span>':'<i data-lucide="info"></i><span>Use o filtro de conta para analisar um recorte específico sem perder o consolidado do topo.</span>',window.lucide&&lucide.createIcons()},updateControls(){const e=document.getElementById("typeSelectWrapper"),a=[u.VIEWS.CATEGORY,u.VIEWS.ANNUAL_CATEGORY].includes(n.currentView);e&&(e.classList.toggle("hidden",!a),a&&S.syncTypeSelect());const t=document.getElementById("accountSelectWrapper");t&&t.classList.remove("hidden")},syncTypeSelect(){const e=document.getElementById("reportType");if(!e)return;const a=z[n.currentView];if(!a)return;(e.options.length!==a.length||a.some((s,o)=>e.options[o]?.value!==s.value))&&(e.innerHTML=a.map(s=>`<option value="${s.value}">${s.label}</option>`).join("")),e.value=ge()},setActiveTab(e){document.querySelectorAll(".tab-btn").forEach(a=>{const t=a.dataset.view===e;a.classList.toggle("active",t),a.setAttribute("aria-selected",t)})}};I.UI=S;const Ie=()=>$.fetchReportData(),Me=()=>S.showLoading(),X=()=>S.showEmptyState(),Le=e=>S.showErrorState(e),Re=()=>S.updateMonthLabel(),O=()=>S.updatePageContext(),N=()=>S.updateReportFilterSummary(),ke=()=>S.updateControls(),Be=e=>S.setActiveTab(e),Oe=e=>w.renderPie(e),Ne=e=>w.renderLine(e),De=e=>w.renderBar(e);async function _(){O(),N(),Me(),Pe();const e=await Ie();if(!n.accessRestricted){if(n.lastReportError)return Le(n.lastReportError);if(n.currentView===u.VIEWS.CARDS){if(!e||!Array.isArray(e.cards))return X();ze(e);return}if(!e||!e.labels||e.labels.length===0)return X();switch(n.currentView){case u.VIEWS.CATEGORY:case u.VIEWS.ANNUAL_CATEGORY:Oe(e);break;case u.VIEWS.BALANCE:case u.VIEWS.EVOLUTION:Ne(e);break;case u.VIEWS.COMPARISON:case u.VIEWS.ACCOUNTS:case u.VIEWS.ANNUAL_SUMMARY:De(e);break;default:X()}Ve(e)}}function W(e,a,t,s=!1){const o=document.getElementById(e);if(!o)return;if(!t||t===0){o.innerHTML="",o.className="stat-trend";return}const r=(a-t)/Math.abs(t)*100,l=Math.abs(r).toFixed(1);if(Math.abs(r)<.5)o.className="stat-trend trend-neutral",o.textContent="— Sem alteração";else{const i=r>0,c=s?!i:i;o.className=`stat-trend ${c?"trend-positive":"trend-negative"}`;const p=i?"↑":"↓";o.textContent=`${p} ${l}% vs mês anterior`}}function Ve(e){const a=document.querySelector(".chart-insight-line");if(a&&a.remove(),!e)return;let t="";switch(n.currentView){case u.VIEWS.CATEGORY:case u.VIEWS.ANNUAL_CATEGORY:{if(!e.labels||!e.values||e.values.length===0)break;const l=e.values.reduce((i,c)=>i+Number(c),0);if(l>0){const i=e.values.reduce((p,h,m,d)=>Number(h)>Number(d[p])?m:p,0),c=(Number(e.values[i])/l*100).toFixed(0);t=`${e.labels[i]} lidera com ${c}% dos gastos (${v(e.values[i])})`}break}case u.VIEWS.BALANCE:{if(!e.labels||!e.values||e.values.length===0)break;const l=e.values.map(Number),i=Math.min(...l),c=l.indexOf(i);t=`Menor saldo: ${v(i)} em ${e.labels[c]}`;break}case u.VIEWS.COMPARISON:{if(!e.receitas||!e.despesas)break;const l=e.receitas.map(Number),i=e.despesas.map(Number);t=`Em ${l.filter((p,h)=>p>(i[h]||0)).length} de ${l.length} dias, receitas superaram despesas`;break}case u.VIEWS.ACCOUNTS:{if(!e.labels||!e.despesas||e.despesas.length===0)break;const l=e.despesas.map(Number),i=l.reduce((c,p,h,m)=>p>m[c]?h:c,0);t=`Maior gasto: ${e.labels[i]} com ${v(l[i])} em despesas`;break}case u.VIEWS.EVOLUTION:{if(!e.values||e.values.length<2)break;const l=e.values.map(Number),i=l[0],c=l[l.length-1];t=`Evolução nos últimos 12 meses: ${c>i?"tendência de alta":c<i?"tendência de queda":"estável"}`;break}case u.VIEWS.ANNUAL_SUMMARY:{if(!e.labels||!e.receitas||e.receitas.length===0)break;const l=e.receitas.map(Number),i=e.despesas.map(Number),c=l.map((m,d)=>m-(i[d]||0)),p=c.reduce((m,d,g,E)=>d>E[m]?g:m,0),h=c.reduce((m,d,g,E)=>d<E[m]?g:m,0);t=`Melhor mês: ${e.labels[p]}. Pior mês: ${e.labels[h]}`;break}}if(!t)return;const o=document.getElementById("reportArea");if(!o)return;const r=document.createElement("div");r.className="chart-insight-line",r.innerHTML=`<i data-lucide="sparkles"></i> <span>${b(t)}</span>`,o.appendChild(r),window.lucide&&lucide.createIcons()}async function Pe(){const e=await $.fetchSummaryStats(),a=document.getElementById("totalReceitas"),t=document.getElementById("totalDespesas"),s=document.getElementById("saldoMes"),o=document.getElementById("totalCartoes");if(a&&(a.textContent=v(e.totalReceitas||0)),t&&(t.textContent=v(e.totalDespesas||0)),s){const c=e.saldo||0;s.textContent=v(c),s.style.color=c>=0?"var(--color-success)":"var(--color-danger)"}o&&(o.textContent=v(e.totalCartoes||0)),W("trendReceitas",e.totalReceitas,e.prevReceitas,!1),W("trendDespesas",e.totalDespesas,e.prevDespesas,!0),W("trendSaldo",e.saldo,e.prevSaldo,!1),W("trendCartoes",e.totalCartoes,e.prevCartoes,!0);const r=document.getElementById("section-overview");r&&r.classList.contains("active")&&await ye();const l=document.getElementById("section-insights");l&&l.classList.contains("active")&&await be();const i=document.getElementById("section-comparativos");i&&i.classList.contains("active")&&await we()}async function be(){const e=document.getElementById("insightsContainer");if(!e)return;let a;if(window.IS_PRO?a=await $.fetchInsights():a=await $.fetchInsightsTeaser(),!a||!a.insights||a.insights.length===0){e.innerHTML='<p class="empty-message">Nenhum insight disponível no momento</p>';return}const t={"arrow-trend-up":"trending-up","arrow-trend-down":"trending-down","arrow-up":"arrow-up","arrow-down":"arrow-down","chart-line":"line-chart","chart-pie":"pie-chart","exclamation-triangle":"triangle-alert","exclamation-circle":"circle-alert","check-circle":"circle-check","info-circle":"info",lightbulb:"lightbulb",star:"star",bolt:"zap",wallet:"wallet","credit-card":"credit-card","calendar-check":"calendar-check",calendar:"calendar",crown:"crown",trophy:"trophy",leaf:"leaf","shield-alt":"shield","money-bill-wave":"banknote","trending-up":"trending-up","trending-down":"trending-down","shield-alert":"shield-alert",gauge:"gauge",target:"target",clock:"clock",receipt:"receipt",calculator:"calculator",layers:"layers","calendar-clock":"calendar-clock","pie-chart":"pie-chart","calendar-range":"calendar-range","list-plus":"list-plus","list-minus":"list-minus","file-text":"file-text","piggy-bank":"piggy-bank",banknote:"banknote"},s=a.insights.map(o=>{const r=t[o.icon]||o.icon;return`
        <div class="insight-card insight-${o.type}">
            <div class="insight-icon">
                <i data-lucide="${r}"></i>
            </div>
            <div class="insight-content">
                <h4>${b(o.title)}</h4>
                <p>${b(o.message)}</p>
            </div>
        </div>
    `}).join("");if(e.innerHTML=s,!window.IS_PRO&&a.isTeaser){const o=Math.max(0,(a.totalCount||0)-a.insights.length),r=o>0?`Desbloqueie mais ${o} insights com PRO`:"Desbloqueie todos os insights com PRO";e.insertAdjacentHTML("beforeend",`
            <div class="insights-teaser-overlay">
                <div class="teaser-blur-mask"></div>
                <div class="teaser-cta">
                    <i data-lucide="crown"></i>
                    <h4>${r}</h4>
                    <p>Tenha uma visão completa da sua saúde financeira com análises detalhadas.</p>
                    <a href="${u.BASE_URL}billing" class="btn-upgrade-cta">
                        <i data-lucide="crown"></i> Fazer Upgrade
                    </a>
                </div>
            </div>
        `)}window.lucide&&lucide.createIcons()}let Y=[];async function ye(){const e=document.getElementById("overviewPulse"),a=document.getElementById("overviewInsights"),t=document.getElementById("overviewCategoryChart"),s=document.getElementById("overviewComparisonChart");Y.forEach(c=>{try{c.destroy()}catch{}}),Y=[];const[o,r,l,i]=await Promise.all([$.fetchSummaryStats(),$.fetchInsightsTeaser(),$.fetchReportDataForType("despesas_por_categoria",{accountId:null}),$.fetchReportDataForType("receitas_despesas_diario",{accountId:null})]);if(e){const c=o.saldo||0,p=c>=0?"var(--color-success)":"var(--color-danger)",h=c>=0?"positivo":"negativo";let m=`
            <p class="pulse-text">
                Neste mês você recebeu <strong>${v(o.totalReceitas)}</strong>
                e gastou <strong>${v(o.totalDespesas)}</strong>.
                Seu saldo é <strong style="color:${p}">${h} em ${v(Math.abs(c))}</strong>.
        `;o.totalCartoes>0&&(m+=` Faturas de cartões somam <strong>${v(o.totalCartoes)}</strong>.`),m+="</p>",e.innerHTML=m}if(a)if(r?.insights?.length>0){const c={"arrow-trend-up":"trending-up","arrow-trend-down":"trending-down","exclamation-triangle":"triangle-alert","check-circle":"circle-check","shield-alert":"shield-alert",gauge:"gauge",target:"target",receipt:"receipt",calculator:"calculator",layers:"layers","piggy-bank":"piggy-bank","pie-chart":"pie-chart","calendar-range":"calendar-range","calendar-clock":"calendar-clock","credit-card":"credit-card","trending-up":"trending-up","trending-down":"trending-down","list-plus":"list-plus","list-minus":"list-minus",banknote:"banknote",clock:"clock"};a.innerHTML=r.insights.map(p=>{const h=c[p.icon]||p.icon;return`
                <div class="insight-card insight-${p.type}">
                    <div class="insight-icon"><i data-lucide="${h}"></i></div>
                    <div class="insight-content">
                        <h4>${b(p.title)}</h4>
                        <p>${b(p.message)}</p>
                    </div>
                </div>`}).join("")}else a.innerHTML='<p class="empty-message">Nenhum insight disponível no momento</p>';if(t&&l?.labels?.length>0){t.innerHTML="";const c=5;let p=l.labels.slice(0,c),h=l.values.slice(0,c).map(Number);if(l.labels.length>c){const d=l.values.slice(c).reduce((g,E)=>g+Number(E),0);p.push("Outros"),h.push(d)}const m=new ApexCharts(t,{chart:{type:"donut",height:220,background:"transparent"},series:h,labels:p,colors:["#E67E22","#2C3E50","#2ECC71","#F39C12","#9B59B6","#1ABC9C"],legend:{position:"bottom",fontSize:"11px",labels:{colors:"var(--color-text-muted)"}},dataLabels:{enabled:!1},plotOptions:{pie:{donut:{size:"60%"}}},stroke:{show:!1},tooltip:{y:{formatter:d=>v(d)}}});m.render(),Y.push(m)}else t&&(t.innerHTML='<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de categorias</p>');if(s&&i?.labels?.length>0){s.innerHTML="";const c=(i.receitas||[]).map(Number),p=(i.despesas||[]).map(Number),h=[],m=[],d=[],g=7;for(let f=0;f<i.labels.length;f+=g){const R=Math.floor(f/g)+1;h.push(`Sem ${R}`),m.push(c.slice(f,f+g).reduce((y,C)=>y+C,0)),d.push(p.slice(f,f+g).reduce((y,C)=>y+C,0))}const E=new ApexCharts(s,{chart:{type:"bar",height:220,background:"transparent",toolbar:{show:!1}},series:[{name:"Receitas",data:m},{name:"Despesas",data:d}],colors:["#2ECC71","#E74C3C"],xaxis:{categories:h,labels:{style:{colors:"var(--color-text-muted)",fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{fontSize:"10px"},formatter:f=>v(f)}},plotOptions:{bar:{columnWidth:"60%",borderRadius:4}},dataLabels:{enabled:!1},legend:{position:"bottom",fontSize:"11px",labels:{colors:"var(--color-text-muted)"}},grid:{borderColor:"rgba(255,255,255,0.05)"},tooltip:{shared:!0,intersect:!1,y:{formatter:f=>v(f)}}});E.render(),Y.push(E)}else s&&(s.innerHTML='<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de movimentação</p>');window.lucide&&lucide.createIcons()}async function we(){const e=document.getElementById("comparativesContainer");if(!e)return;const a=await $.fetchComparatives();if(!a){e.innerHTML='<p class="empty-message">Dados de comparação não disponíveis</p>';return}const t=le("Comparativo Mensal",a.monthly,"mês anterior"),s=le("Comparativo Anual",a.yearly,"ano anterior"),o=Ue(a.categories||[]),r=Fe(a.evolucao||[]),l=We(a.mediaDiaria),i=Ye(a.taxaEconomia),c=qe(a.formasPagamento||[]);e.innerHTML=`<div class="comp-top-row">${t}${s}</div><div class="comp-duo-grid">${l}${i}</div>`+o+r+c,window.lucide&&lucide.createIcons(),He(a.evolucao||[])}function Ue(e){return!e||e.length===0?"":`
        <div class="comparative-card comp-full-width">
            <div class="comparative-header">
                <h3><i data-lucide="bar-chart-3"></i> Top Categorias de Despesa</h3>
                <span class="comp-subtitle">Mês atual vs anterior</span>
            </div>
            <div class="cat-comp-list">
                <div class="cat-comp-header-row">
                    <span></span><span></span>
                    <span class="cat-comp-col-label">Atual / Anterior</span>
                    <span class="cat-comp-col-label">Variação</span>
                </div>
                ${e.map((t,s)=>{const o=t.variacao>0?"trend-negative":t.variacao<0?"trend-positive":"trend-neutral",r=t.variacao>0?"arrow-up":t.variacao<0?"arrow-down":"equal",l=Math.abs(t.variacao)<.1?"Sem alteração":`${t.variacao>0?"+":""}${t.variacao.toFixed(1)}%`,i=e.reduce((h,m)=>h+m.atual,0),c=i>0?(t.atual/i*100).toFixed(0):0;let p="";return t.subcategorias&&t.subcategorias.length>0&&(p=`<div class="cat-comp-subcats">${t.subcategorias.map(m=>{const d=m.variacao>0?"trend-negative":m.variacao<0?"trend-positive":"",g=Math.abs(m.variacao)<.1?"":`<span class="subcat-trend ${d}">${m.variacao>0?"↑":"↓"}${Math.abs(m.variacao).toFixed(0)}%</span>`;return`
                    <span class="cat-comp-subcat-pill">
                        ${b(m.nome)}
                        <span class="subcat-value">${v(m.atual)}</span>
                        ${g}
                    </span>
                `}).join("")}</div>`),`
            <div class="cat-comp-row" style="animation-delay: ${s*.06}s">
                <div class="cat-comp-rank">${s+1}</div>
                <div class="cat-comp-info">
                    <span class="cat-comp-name">${b(t.nome)}</span>
                    <div class="cat-comp-bar-bg">
                        <div class="cat-comp-bar" style="width: ${c}%"></div>
                    </div>
                    ${p}
                </div>
                <div class="cat-comp-values">
                    <span class="cat-comp-current">${v(t.atual)}</span>
                    <span class="cat-comp-prev">${v(t.anterior)}</span>
                </div>
                <div class="cat-comp-trend ${o}">
                    <i data-lucide="${r}"></i>
                    <span>${l}</span>
                </div>
            </div>
        `}).join("")}
            </div>
        </div>
    `}function Fe(e){return!e||e.length===0?"":`
        <div class="comparative-card comp-full-width">
            <div class="comparative-header">
                <h3><i data-lucide="line-chart"></i> Evolução dos Últimos 6 Meses</h3>
                <span class="comp-subtitle">Receitas, despesas e saldo ao longo do tempo</span>
            </div>
            <div class="evolucao-chart-wrapper">
                <div id="evolucaoMiniChart" style="min-height:220px;"></div>
            </div>
        </div>
    `}let D=null;function He(e){if(!e||e.length===0)return;const a=document.getElementById("evolucaoMiniChart");if(!a)return;const t=e.map(i=>i.label),o=getComputedStyle(document.documentElement).getPropertyValue("--color-text-muted").trim()||"#999",l=document.documentElement.getAttribute("data-theme")==="dark"?"dark":"light";D&&(D.destroy(),D=null),D=new ApexCharts(a,{chart:{type:"line",height:260,stacked:!1,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:[{name:"Receitas",type:"column",data:e.map(i=>i.receitas)},{name:"Despesas",type:"column",data:e.map(i=>i.despesas)},{name:"Saldo",type:"area",data:e.map(i=>i.saldo)}],xaxis:{categories:t,labels:{style:{colors:o}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:o},formatter:i=>v(i)}},colors:["rgba(46, 204, 113, 0.85)","rgba(231, 76, 60, 0.85)","#3498db"],stroke:{width:[0,0,2.5],curve:"smooth"},fill:{opacity:[.85,.85,.1]},plotOptions:{bar:{borderRadius:6,columnWidth:"55%"}},grid:{borderColor:"rgba(128,128,128,0.1)",strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:l,shared:!0,intersect:!1,y:{formatter:i=>v(i)}},legend:{position:"bottom",labels:{colors:o},markers:{shape:"circle"}},dataLabels:{enabled:!1},theme:{mode:l}}),D.render()}function We(e){if(!e)return"";const a=e.variacao>0?"trend-negative":e.variacao<0?"trend-positive":"trend-neutral",t=e.variacao>0?"arrow-up":e.variacao<0?"arrow-down":"equal";return`
        <div class="comparative-card comp-mini-card">
            <div class="comp-mini-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i data-lucide="calendar-clock"></i>
            </div>
            <div class="comp-mini-body">
                <span class="comp-mini-label">Média Diária de Gastos</span>
                <div class="comp-mini-values">
                    <span class="comp-mini-current">${v(e.atual)}/dia</span>
                    <span class="comp-mini-prev">anterior: ${v(e.anterior)}/dia</span>
                </div>
                <div class="comp-mini-trend ${a}">
                    <i data-lucide="${t}"></i>
                    <span>${Math.abs(e.variacao).toFixed(1)}%</span>
                </div>
            </div>
        </div>
    `}function Ye(e){if(!e)return"";const a=e.atual>=0,t=e.diferenca>0?"trend-positive":e.diferenca<0?"trend-negative":"trend-neutral",s=e.diferenca>0?"arrow-up":e.diferenca<0?"arrow-down":"equal";return`
        <div class="comparative-card comp-mini-card">
            <div class="comp-mini-icon" style="background: linear-gradient(135deg, ${a?"#2ecc71, #27ae60":"#e74c3c, #c0392b"});">
                <i data-lucide="piggy-bank" style= "color: white"></i>
            </div>
            <div class="comp-mini-body">
                <span class="comp-mini-label">Taxa de Economia</span>
                <div class="comp-mini-values">
                    <span class="comp-mini-current">${e.atual.toFixed(1)}%</span>
                    <span class="comp-mini-prev">anterior: ${e.anterior.toFixed(1)}%</span>
                </div>
                <div class="comp-mini-trend ${t}">
                    <i data-lucide="${s}"></i>
                    <span>${e.diferenca>0?"+":""}${e.diferenca.toFixed(1)}pp</span>
                </div>
            </div>
        </div>
    `}function qe(e){if(!e||e.length===0)return"";const a={Pix:"zap","Cartão de Crédito":"credit-card","Cartão de Débito":"credit-card",Dinheiro:"banknote",Boleto:"file-text",Depósito:"landmark",Transferência:"arrow-right-left",Estorno:"undo-2"},t=e.reduce((o,r)=>o+r.atual,0);return`
        <div class="comparative-card comp-full-width">
            <div class="comparative-header">
                <h3><i data-lucide="wallet"></i> Formas de Pagamento</h3>
                <span class="comp-subtitle">Distribuição mês atual vs anterior</span>
            </div>
            <div class="forma-comp-list">
                ${e.map((o,r)=>{const l=t>0?(o.atual/t*100).toFixed(0):0,i=a[o.nome]||"wallet";return`
            <div class="forma-comp-row" style="animation-delay: ${r*.06}s">
                <div class="forma-comp-icon"><i data-lucide="${i}"></i></div>
                <div class="forma-comp-info">
                    <span class="forma-comp-name">${b(o.nome)}</span>
                    <div class="forma-comp-bar-bg">
                        <div class="forma-comp-bar" style="width: ${l}%"></div>
                    </div>
                </div>
                <div class="forma-comp-values">
                    <span class="forma-comp-current">${v(o.atual)} <small>(${o.atual_qtd}x)</small></span>
                    <span class="forma-comp-prev">${v(o.anterior)} <small>(${o.anterior_qtd}x)</small></span>
                </div>
            </div>
        `}).join("")}
            </div>
        </div>
    `}function le(e,a,t){const s=(c,p=!1)=>c>0?'<i data-lucide="arrow-up"></i>':c<0?'<i data-lucide="arrow-down"></i>':'<i data-lucide="equal"></i>',o=(c,p=!1)=>{if(p){if(c>0)return"trend-negative";if(c<0)return"trend-positive"}else{if(c>0)return"trend-positive";if(c<0)return"trend-negative"}return"trend-neutral"},r=(c,p=!1)=>Math.abs(c)<.1?"Sem alteração":c>0?`Aumentou ${Math.abs(c).toFixed(1)}%`:c<0?`Reduziu ${Math.abs(c).toFixed(1)}%`:"Sem alteração",l=()=>{if(t.includes("mês")){const[c,p]=n.currentMonth.split("-");return new Date(c,p-1).toLocaleDateString("pt-BR",{month:"short",year:"numeric"})}else return n.currentMonth.split("-")[0]},i=()=>{if(t.includes("mês")){const[c,p]=n.currentMonth.split("-");return new Date(c,p-2).toLocaleDateString("pt-BR",{month:"short",year:"numeric"})}else return(parseInt(n.currentMonth.split("-")[0])-1).toString()};return`
        <div class="comparative-card">
            <div class="comparative-header">
                <h3>${b(e)}</h3>
                <div class="period-labels">
                    <span class="period-current"><i data-lucide="calendar" style="color: white;"></i> ${l()}</span>
                    <span class="period-separator">vs</span>
                    <span class="period-previous">${i()}</span>
                </div>
            </div>
            
            <div class="comparative-grid-new">
                <div class="comparative-item-new">
                    <div class="item-header">
                        <i data-lucide="trending-up" class="item-icon revenue"></i>
                        <span class="item-label">RECEITAS</span>
                    </div>
                    <div class="item-values">
                        <div class="value-current">
                            <span class="value-label">Atual</span>
                            <span class="value-amount">${v(a.current.receitas)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${v(a.previous.receitas)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${o(a.variation.receitas,!1)}">
                        ${s(a.variation.receitas,!1)}
                        <span>${r(a.variation.receitas,!1)}</span>
                    </div>
                </div>
                
                <div class="comparative-item-new">
                    <div class="item-header">
                        <i data-lucide="trending-down" class="item-icon expense"></i>
                        <span class="item-label">DESPESAS</span>
                    </div>
                    <div class="item-values">
                        <div class="value-current">
                            <span class="value-label">Atual</span>
                            <span class="value-amount">${v(a.current.despesas)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${v(a.previous.despesas)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${o(a.variation.despesas,!0)}">
                        ${s(a.variation.despesas,!0)}
                        <span>${r(a.variation.despesas,!0)}</span>
                    </div>
                </div>
                
                <div class="comparative-item-new">
                    <div class="item-header">
                        <i data-lucide="wallet" class="item-icon balance"></i>
                        <span class="item-label">SALDO</span>
                    </div>
                    <div class="item-values">
                        <div class="value-current">
                            <span class="value-label">Atual</span>
                            <span class="value-amount">${v(a.current.saldo)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${v(a.previous.saldo)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${o(a.variation.saldo,!1)}">
                        ${s(a.variation.saldo,!1)}
                        <span>${r(a.variation.saldo,!1)}</span>
                    </div>
                </div>
            </div>
        </div>
    `}function ze(e){const a=document.getElementById("reportArea");if(!a)return;const t=e.resumo_consolidado&&e.cards&&e.cards.length>0?`
        <div class="consolidated-summary">
            <div class="summary-header">
                <div class="summary-icon">
                    <i data-lucide="credit-card" style="color: white"></i>
                </div>
                <div class="summary-title">
                    <h3>Visão Geral dos Cartões</h3>
                    <p>Resumo consolidado de todos os seus cartões de crédito</p>
                </div>
            </div>
            
            <div class="summary-grid">
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        <i data-lucide="file-text" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Total em Faturas</span>
                        <span class="stat-value">${v(e.resumo_consolidado.total_faturas)}</span>
                    </div>
                </div>
                
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i data-lucide="wallet" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Limite Total</span>
                        <span class="stat-value">${v(e.resumo_consolidado.total_limites)}</span>
                    </div>
                </div>
                
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, ${e.resumo_consolidado.utilizacao_geral>70?"#e74c3c, #c0392b":e.resumo_consolidado.utilizacao_geral>50?"#f39c12, #e67e22":"#2ecc71, #27ae60"});">
                        <i data-lucide="pie-chart" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Utilização Geral</span>
                        <span class="stat-value">${e.resumo_consolidado.utilizacao_geral.toFixed(1)}%</span>
                    </div>
                </div>
                
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                        <i data-lucide="banknote" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Disponível</span>
                        <span class="stat-value">${v(e.resumo_consolidado.total_disponivel)}</span>
                    </div>
                </div>
            </div>
            
            ${e.resumo_consolidado.melhor_cartao||e.resumo_consolidado.requer_atencao?`
                <div class="summary-insights">
                    ${e.resumo_consolidado.melhor_cartao?`
                        <div class="insight-item success">
                            <i data-lucide="star"></i>
                            <span><strong>Melhor cartão:</strong> ${b(e.resumo_consolidado.melhor_cartao.nome)} (${e.resumo_consolidado.melhor_cartao.percentual.toFixed(1)}% de uso)</span>
                        </div>
                    `:""}
                    ${e.resumo_consolidado.requer_atencao?`
                        <div class="insight-item warning">
                            <i data-lucide="triangle-alert"></i>
                            <span><strong>Requer atenção:</strong> ${b(e.resumo_consolidado.requer_atencao.nome)} (${e.resumo_consolidado.requer_atencao.percentual.toFixed(1)}% de uso)</span>
                        </div>
                    `:""}
                    ${e.resumo_consolidado.total_parcelamentos>0?`
                        <div class="insight-item info">
                            <i data-lucide="calendar-check"></i>
                            <span><strong>${e.resumo_consolidado.total_parcelamentos} parcelamento${e.resumo_consolidado.total_parcelamentos>1?"s":""}</strong> comprometendo ${v(e.resumo_consolidado.valor_parcelamentos)}</span>
                        </div>
                    `:""}
                </div>
            `:""}
        </div>
    `:"";a.innerHTML=`
        <div class="cards-report-container">
            ${t}
            
            <div class="cards-grid">
                ${e.cards&&e.cards.length>0?e.cards.map(s=>{const o=Ae(s.cor,"#E67E22");return`
                    <div class="card-item ${s.status_saude.status}" 
                         style="--card-color: ${o}; cursor: pointer;"
                         data-card-id="${s.id||""}"
                         data-card-nome="${b(s.nome)}"
                         data-card-cor="${o}"
                         data-card-month="${n.currentMonth}"
                         data-action="open-card-detail"
                         role="button"
                         tabindex="0">
                        <div class="card-header-gradient">
                            <div class="card-brand">
                                <div class="card-icon-wrapper" style="background: linear-gradient(135deg, ${o}, ${o}99);">
                                    <i data-lucide="credit-card" style="color: white"></i>
                                </div>
                                <div class="card-info">
                                    <h3 class="card-name">${b(s.nome)}</h3>
                                    <div class="card-meta">
                                        ${s.conta?`<span class="card-account"><i data-lucide="landmark"></i> ${b(s.conta)}</span>`:""}
                                        ${s.dia_vencimento?`<span class="card-due"><i data-lucide="calendar"></i> Vence dia ${s.dia_vencimento}</span>`:""}
                                    </div>
                                </div>
                            </div>
                            ${s.status_saude&&(s.status_saude.status==="critico"||s.status_saude.status==="alto_uso")?`
                                <div class="health-indicator ${s.status_saude.status}">
                                    <i data-lucide="triangle-alert"></i>
                                </div>
                            `:""}
                        </div>

                        ${s.historico_6_meses&&s.historico_6_meses.length>0?`
                            <div class="card-trend-compact">
                                <span class="trend-label">ÚLTIMOS 6 MESES</span>
                                <span class="trend-indicator ${s.tendencia}">
                                    ${s.tendencia==="subindo"?"↗":s.tendencia==="caindo"?"↘":"→"} ${s.tendencia==="subindo"?"Em alta":s.tendencia==="caindo"?"Em queda":"Estável"}
                                </span>
                            </div>
                        `:""}

                        ${s.alertas&&s.alertas.length>0?`
                            <div class="card-alerts">
                                ${s.alertas.map(r=>`
                                    <span class="alert-badge alert-${r.type}">
                                        <i data-lucide="${r.type==="danger"?"triangle-alert":r.type==="warning"?"circle-alert":"info"}"></i>
                                        ${b(r.message)}
                                    </span>
                                `).join("")}
                            </div>
                        `:""}


                        <div class="card-balance">
                            <div class="balance-main">
                                <span class="balance-label">FATURA DO MÊS</span>
                                <span class="balance-value">${v(s.fatura_atual||0)}</span>
                                ${s.media_historica>0&&Math.abs(s.fatura_atual-s.media_historica)>1?`
                                    <span class="balance-comparison">
                                        ${s.fatura_atual>s.media_historica?"↑":"↓"} ${(Math.abs(s.fatura_atual-s.media_historica)/s.media_historica*100).toFixed(0)}% vs média
                                    </span>
                                `:""}
                            </div>
                            <div class="balance-grid">
                                <div class="balance-item">
                                    <span class="balance-small-label">Limite</span>
                                    <span class="balance-small-value">${v(s.limite||0)}</span>
                                </div>
                                <div class="balance-item">
                                    <span class="balance-small-label">Disponível</span>
                                    <span class="balance-small-value">${v(s.disponivel||0)}</span>
                                </div>
                            </div>
                        </div>


                        <div class="card-usage-new">
                            <div class="usage-header">
                                <span class="usage-label">UTILIZAÇÃO DO LIMITE</span>
                                <span class="usage-percentage">${(s.percentual||0).toFixed(1)}%</span>
                            </div>
                            <div class="usage-bar-new">
                                <div class="usage-fill-new" 
                                     style="width: ${Math.min(s.percentual||0,100)}%"></div>
                            </div>
                        </div>

                        ${s.parcelamentos&&s.parcelamentos.ativos>0||s.proximos_meses&&s.proximos_meses.length>0&&s.proximos_meses.some(r=>r.valor>0)?`
                            <div class="card-quick-info">
                                ${s.parcelamentos&&s.parcelamentos.ativos>0?`
                                    <div class="quick-info-item">
                                        <i data-lucide="calendar-check"></i>
                                        <span>${s.parcelamentos.ativos} parcelamento${s.parcelamentos.ativos>1?"s":""}</span>
                                    </div>
                                `:""}
                                ${s.proximos_meses&&s.proximos_meses.length>0&&s.proximos_meses.some(r=>r.valor>0)?`
                                    <div class="quick-info-item">
                                        <i data-lucide="line-chart"></i>
                                        <span>Próximo: ${v(s.proximos_meses.find(r=>r.valor>0)?.valor||0)}</span>
                                    </div>
                                `:""}
                            </div>
                        `:""}
                        
                        <div class="card-footer">
                            <button class="card-action-btn primary full-width" data-action="open-card-detail" data-card-id="${s.id||""}" data-card-nome="${b(s.nome)}" data-card-cor="${o}" data-card-month="${n.currentMonth}" title="Ver relatório detalhado">
                                <i data-lucide="eye"></i>
                                <span>Ver Detalhes</span>
                            </button>
                        </div>
                    </div>
                `}).join(""):`
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i data-lucide="credit-card"></i>
                        </div>
                        <h3>Nenhum cartão de crédito cadastrado</h3>
                        <p>Cadastre seus cartões de crédito para visualizar relatórios detalhados de gastos e parcelamentos.</p>
                    </div>
                `}
            </div>
        </div>
    `,window.lucide&&lucide.createIcons()}async function je(){if(!window.IS_PRO)return fe("Exportação de relatórios é exclusiva do plano PRO.");const e=xe()||"despesas_por_categoria",{value:a}=await Swal.fire({title:"Exportar Relatório",html:`
            <div style="text-align:left;display:flex;flex-direction:column;gap:12px;padding-top:8px;">
                <label style="font-weight:600;font-size:0.85rem;color:var(--color-text-muted);">Tipo de Relatório</label>
                <select id="swalExportType" class="swal2-select" style="width:100%;font-size:0.9rem;">
                    <option value="despesas_por_categoria" ${e==="despesas_por_categoria"?"selected":""}>Despesas por Categoria</option>
                    <option value="receitas_por_categoria" ${e==="receitas_por_categoria"?"selected":""}>Receitas por Categoria</option>
                    <option value="saldo_mensal" ${e==="saldo_mensal"?"selected":""}>Saldo Diário</option>
                    <option value="receitas_despesas_diario" ${e==="receitas_despesas_diario"?"selected":""}>Receitas x Despesas Diário</option>
                    <option value="evolucao_12m" ${e==="evolucao_12m"?"selected":""}>Evolução 12 Meses</option>
                    <option value="receitas_despesas_por_conta" ${e==="receitas_despesas_por_conta"?"selected":""}>Receitas x Despesas por Conta</option>
                    <option value="cartoes_credito" ${e==="cartoes_credito"?"selected":""}>Relatório de Cartões</option>
                    <option value="resumo_anual" ${e==="resumo_anual"?"selected":""}>Resumo Anual</option>
                    <option value="despesas_anuais_por_categoria" ${e==="despesas_anuais_por_categoria"?"selected":""}>Despesas Anuais por Categoria</option>
                    <option value="receitas_anuais_por_categoria" ${e==="receitas_anuais_por_categoria"?"selected":""}>Receitas Anuais por Categoria</option>
                </select>
                <label style="font-weight:600;font-size:0.85rem;color:var(--color-text-muted);">Formato</label>
                <select id="swalExportFormat" class="swal2-select" style="width:100%;font-size:0.9rem;">
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel (.xlsx)</option>
                </select>
            </div>
        `,showCancelButton:!0,confirmButtonText:"Exportar",cancelButtonText:"Cancelar",confirmButtonColor:"#e67e22",preConfirm:()=>({type:document.getElementById("swalExportType").value,format:document.getElementById("swalExportFormat").value})});if(!a)return;const t=document.getElementById("exportBtn"),s=t?t.innerHTML:"";t&&(t.disabled=!0,t.innerHTML=`
            <div class="spinner" style="width: 1rem; height: 1rem; border-width: 2px;"></div>
            <span>Exportando...</span>
        `);try{const o=a.type,r=a.format,l=new URLSearchParams({type:o,format:r,year:n.currentMonth.split("-")[0],month:n.currentMonth.split("-")[1]});n.currentAccount&&l.set("account_id",n.currentAccount);const i=await fetch(`${u.BASE_URL}api/reports/export?${l}`,{credentials:"include"});if(await B(i))return;if(!i.ok){let g="Erro ao exportar relatório.";try{const E=await i.json();E?.message?g=E.message:E?.errors&&(g=Object.values(E.errors).flat().join(", "))}catch{}throw new Error(g)}const c=await i.blob(),p=i.headers.get("Content-Disposition"),h=x.extractFilename(p)||(r==="excel"?"relatorio.xlsx":"relatorio.pdf"),m=URL.createObjectURL(c),d=document.createElement("a");d.href=m,d.download=h,document.body.appendChild(d),d.click(),d.remove(),URL.revokeObjectURL(m),typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"success",title:"Relatório exportado!",text:h,showConfirmButton:!1,timer:3e3,timerProgressBar:!0})}catch(o){console.error("Export error:",o);const r=pe(o,"Erro ao exportar relatório. Tente novamente.");typeof Swal<"u"?Swal.fire({toast:!0,position:"top-end",icon:"error",title:"Erro ao exportar",text:r,showConfirmButton:!1,timer:3e3}):alert(r)}finally{t&&(t.disabled=!1,t.innerHTML=s)}}async function Ge(e){e==="overview"?await ye():e==="relatorios"?await _():e==="insights"?await be():e==="comparativos"&&await we()}function Ee(){const e=V();if(window.LukratoHeader?.setPickerMode?.(e?"year":"month"),e){const a=window.LukratoHeader?.getYear?.();if(a){const[,t="01"]=n.currentMonth.split("-"),s=String(t).padStart(2,"0");n.currentMonth=`${a}-${s}`}}}function de(e){n.currentView=e,Be(e),ke(),O(),N(),Ee(),ve(),_()}function ue(e){n.currentView===u.VIEWS.ANNUAL_CATEGORY?n.annualCategoryType=e:n.categoryType=e,O(),N(),ve(),_()}function ee(e){n.currentAccount=e||null,O(),N(),_()}function Ke(e){!e?.detail?.month||V()||n.currentMonth!==e.detail.month&&(n.currentMonth=e.detail.month,Re(),O(),N(),_())}function Ze(e){if(!V()||!e?.detail?.year)return;const[,a="01"]=n.currentMonth.split("-"),t=String(a).padStart(2,"0"),s=`${e.detail.year}-${t}`;n.currentMonth!==s&&(n.currentMonth=s,O(),N(),_())}if(!window.__LK_REPORTS_LOADED__){let e=function(){try{const t=localStorage.getItem(L.ACTIVE_VIEW);t&&Object.values(u.VIEWS).includes(t)&&(n.currentView=t);const s=localStorage.getItem(L.CATEGORY_TYPE);s&&z[u.VIEWS.CATEGORY]?.some(r=>r.value===s)&&(n.categoryType=s);const o=localStorage.getItem(L.ANNUAL_CATEGORY_TYPE);o&&z[u.VIEWS.ANNUAL_CATEGORY]?.some(r=>r.value===o)&&(n.annualCategoryType=o)}catch{}};window.__LK_REPORTS_LOADED__=!0;async function a(){w.setupDefaults(),n.accounts=await $.fetchAccounts();const t=document.getElementById("accountFilter");t&&n.accounts.forEach(d=>{const g=document.createElement("option");g.value=d.id,g.textContent=d.name,t.appendChild(g)}),e(),document.querySelectorAll(".tab-btn").forEach(d=>{d.addEventListener("click",()=>de(d.dataset.view))});const s=d=>{n.activeSection=d,document.querySelectorAll(".rel-section-tab").forEach(f=>{f.classList.remove("active"),f.setAttribute("aria-selected","false")}),document.querySelectorAll(".rel-section-panel").forEach(f=>f.classList.remove("active"));const g=document.querySelector(`.rel-section-tab[data-section="${d}"]`);g&&(g.classList.add("active"),g.setAttribute("aria-selected","true"));const E=document.getElementById("section-"+d);E&&E.classList.add("active"),localStorage.setItem(L.ACTIVE_SECTION,d),S.updatePageContext(),Ge(d),window.lucide&&window.lucide.createIcons()},o=["comparativos"];document.querySelectorAll(".rel-section-tab").forEach(d=>{d.addEventListener("click",()=>{const g=d.dataset.section;if(!window.IS_PRO&&o.includes(g)){Swal.fire({icon:"info",title:"Recurso Premium",html:"Esta funcionalidade é exclusiva do <b>plano Pro</b>.<br>Faça upgrade para desbloquear!",confirmButtonText:'<i class="lucide-crown" style="margin-right:6px"></i> Fazer Upgrade',showCancelButton:!0,cancelButtonText:"Agora não",confirmButtonColor:"#f59e0b",cancelButtonColor:"#64748b"}).then(E=>{E.isConfirmed&&(window.location.href=(window.BASE_URL||"/")+"billing")});return}s(g)})}),S.setActiveTab(n.currentView),S.updateControls(),S.updatePageContext();const r=localStorage.getItem(L.ACTIVE_SECTION);r&&document.getElementById("section-"+r)?!window.IS_PRO&&o.includes(r)?s("overview"):s(r):s("overview");const l=document.getElementById("reportType");l&&l.addEventListener("change",d=>ue(d.target.value)),t&&t.addEventListener("change",d=>ee(d.target.value));const i=document.getElementById("btnLimparFiltrosRel"),c=document.getElementById("clearFiltersWrapper"),p=()=>{if(!c)return;const d=l&&l.selectedIndex>0,g=t&&t.value!=="";c.style.display=d||g?"flex":"none"};l&&l.addEventListener("change",p),t&&t.addEventListener("change",p),i&&i.addEventListener("click",()=>{l&&(l.selectedIndex=0,ue(l.value)),t&&(t.value="",ee("")),p()}),p(),document.addEventListener("lukrato:theme-changed",()=>{w.setupDefaults(),_()});const h=window.LukratoHeader?.getMonth?.();h&&(n.currentMonth=h),document.addEventListener("lukrato:month-changed",Ke),document.addEventListener("lukrato:year-changed",Ze);const m=document.getElementById("exportBtn");m&&m.addEventListener("click",je),document.addEventListener("click",d=>{if(d.target.closest('[data-action="retry-report"]')){d.preventDefault(),_();return}if(d.target.closest('[data-action="clear-report-account"]')){d.preventDefault(),t&&(t.value=""),ee(""),p();return}const f=d.target.closest('[data-action="open-card-detail"]');if(!f)return;d.stopPropagation();const R=parseInt(f.dataset.cardId,10),y=f.dataset.cardNome||"",C=f.dataset.cardCor||"#E67E22",M=f.dataset.cardMonth||n.currentMonth;R&&(window.LK_CardDetail?.open?window.LK_CardDetail.open(R,y,C,M):(console.error("[Relatórios] LK_CardDetail module not loaded"),typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"error",title:"Módulo de detalhes não carregado",text:"Recarregue a página.",showConfirmButton:!1,timer:3e3})))}),Ee(),S.updateMonthLabel(),S.updateControls(),_()}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",a):a(),window.ReportsAPI={setMonth:t=>{/^\d{4}-\d{2}$/.test(t)&&(n.currentMonth=t,S.updateMonthLabel(),_())},setView:t=>{Object.values(u.VIEWS).includes(t)&&de(t)},refresh:()=>_(),getState:()=>({...n})}}
