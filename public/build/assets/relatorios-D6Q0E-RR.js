import{m as Ie,b as xe,e as ge,k}from"./api-DpYnTMaG.js";import{a as _e}from"./utils-Bj4jxwhy.js";import{c as Le,p as Me,f as Re}from"./ui-preferences-CUuIZRMg.js";import{o as ke,g as Oe,e as Pe}from"./runtime-config-CXTcOn9X.js";import{r as Ne}from"./finance-CgaDv1sH.js";import{r as Ve,a as Be,b as De,c as Ue,d as Fe,e as le}from"./reports-CXrVZnrN.js";const q="Relatórios são exclusivos do plano Pro.",p={BASE_URL:Ie(),CHART_COLORS:["#E67E22","#2C3E50","#2ECC71","#F39C12","#9B59B6","#1ABC9C","#E74C3C","#3498DB"],FETCH_TIMEOUT:3e4,VIEWS:{CATEGORY:"category",BALANCE:"balance",COMPARISON:"comparison",ACCOUNTS:"accounts",CARDS:"cards",EVOLUTION:"evolution",ANNUAL_SUMMARY:"annual_summary",ANNUAL_CATEGORY:"annual_category"}},We=new Set([p.VIEWS.ANNUAL_SUMMARY,p.VIEWS.ANNUAL_CATEGORY]),z={[p.VIEWS.CATEGORY]:[{value:"despesas_por_categoria",label:"Despesas por categoria"},{value:"receitas_por_categoria",label:"Receitas por categoria"}],[p.VIEWS.ANNUAL_CATEGORY]:[{value:"despesas_anuais_por_categoria",label:"Despesas anuais por categoria"},{value:"receitas_anuais_por_categoria",label:"Receitas anuais por categoria"}]},R={ACTIVE_SECTION:"rel_active_section",ACTIVE_VIEW:"rel_active_view",CATEGORY_TYPE:"rel_category_type",ANNUAL_CATEGORY_TYPE:"rel_annual_category_type"},de={overview:{kicker:"Painel consolidado",title:"Leia seu mês com contexto",description:"Veja seu pulso financeiro, identifique sinais importantes e acompanhe a evolução do período em um resumo rápido."},relatorios:{kicker:"Relatório ativo",title:"Transforme lançamentos em decisão",description:"Explore seus números por categoria, conta, cartão e evolução para descobrir onde agir."},insights:{kicker:"Leitura automática",title:"Insights que ajudam a agir",description:"Receba sinais claros sobre gastos, saldo, concentrações e oportunidades sem precisar interpretar tudo manualmente."},comparativos:{kicker:"Comparação temporal",title:"Compare e ajuste sua rota",description:"Entenda o que melhorou, piorou ou estagnou em relação ao mês e ao ano anteriores."}},D={[p.VIEWS.CATEGORY]:{title:"Categorias do período",description:"Encontre rapidamente onde seu dinheiro está concentrado por categoria."},[p.VIEWS.BALANCE]:{title:"Saldo diário",description:"Acompanhe como seu caixa evolui ao longo do período."},[p.VIEWS.COMPARISON]:{title:"Receitas x despesas",description:"Compare entradas e saídas para entender pressão ou folga no caixa."},[p.VIEWS.ACCOUNTS]:{title:"Desempenho por conta",description:"Descubra quais contas concentram mais entradas e saídas."},[p.VIEWS.CARDS]:{title:"Saúde dos cartões",description:"Monitore faturas, uso de limite e sinais de atenção nos cartões."},[p.VIEWS.EVOLUTION]:{title:"Evolução em 12 meses",description:"Observe tendência, sazonalidade e ritmo financeiro ao longo do último ano."},[p.VIEWS.ANNUAL_SUMMARY]:{title:"Resumo anual",description:"Compare mês a mês como receitas, despesas e saldo se comportaram no ano."},[p.VIEWS.ANNUAL_CATEGORY]:{title:"Categorias do ano",description:"Veja quais categorias dominaram seu ano e onde houve maior concentração."}};function fe(){const e=new Date;return`${e.getFullYear()}-${String(e.getMonth()+1).padStart(2,"0")}`}const h=e=>String(e??"").replace(/[&<>"']/g,function(t){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[t]??t});function He(e,t="#cccccc"){return/^#[0-9A-Fa-f]{6}$/.test(e)?e:t}const i={activeSection:"overview",currentView:p.VIEWS.CATEGORY,categoryType:"despesas_por_categoria",annualCategoryType:"despesas_anuais_por_categoria",currentMonth:fe(),currentAccount:null,chart:null,accounts:[],accessRestricted:!1,lastReportError:null,activeDrilldown:null,reportDetails:null},C={getCurrentMonth:fe,formatCurrency(e){return _e(e)},formatMonthLabel(e){const[t,a]=e.split("-");return new Date(t,a-1).toLocaleDateString("pt-BR",{month:"long",year:"numeric"})},addMonths(e,t){const[a,o]=e.split("-").map(Number),r=new Date(a,o-1+t);return`${r.getFullYear()}-${String(r.getMonth()+1).padStart(2,"0")}`},hexToRgba(e,t=.25){const a=parseInt(e.slice(1,3),16),o=parseInt(e.slice(3,5),16),r=parseInt(e.slice(5,7),16);return`rgba(${a}, ${o}, ${r}, ${t})`},generateShades(e,t){const a=parseInt(e.slice(1,3),16),o=parseInt(e.slice(3,5),16),r=parseInt(e.slice(5,7),16),s=[];for(let n=0;n<t;n++){const c=.35-n/Math.max(t-1,1)*.7,l=m=>Math.min(255,Math.max(0,Math.round(m+(c>0?(255-m)*c:m*c)))),u=l(a),v=l(o),d=l(r);s.push(`#${u.toString(16).padStart(2,"0")}${v.toString(16).padStart(2,"0")}${d.toString(16).padStart(2,"0")}`)}return s},isYearlyView(e=i.currentView){return We.has(e)},extractFilename(e){if(!e)return null;const t=/filename\*=UTF-8''([^;]+)/i.exec(e);if(t)try{return decodeURIComponent(t[1])}catch{return t[1]}const a=/filename="?([^";]+)"?/i.exec(e);return a?a[1]:null},getCssVar(e,t=""){try{return(getComputedStyle(document.documentElement).getPropertyValue(e)||"").trim()||t}catch{return t}},isLightTheme(){try{return(document.documentElement?.getAttribute("data-theme")||"dark")==="light"}catch{return!1}},getReportType(){return{[p.VIEWS.CATEGORY]:i.categoryType,[p.VIEWS.ANNUAL_CATEGORY]:i.annualCategoryType,[p.VIEWS.BALANCE]:"saldo_mensal",[p.VIEWS.COMPARISON]:"receitas_despesas_diario",[p.VIEWS.ACCOUNTS]:"receitas_despesas_por_conta",[p.VIEWS.CARDS]:"cartoes_credito",[p.VIEWS.EVOLUTION]:"evolucao_12m",[p.VIEWS.ANNUAL_SUMMARY]:"resumo_anual"}[i.currentView]??i.categoryType},getActiveCategoryType(){return i.currentView===p.VIEWS.ANNUAL_CATEGORY?i.annualCategoryType:i.categoryType}},L={},_=e=>C.formatCurrency(e),M=e=>String(e??"").replace(/[&<>"']/g,t=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"})[t]||t);function H(){const e=(document.documentElement.getAttribute("data-theme")||"").toLowerCase()==="light"||C.isLightTheme?.();return{isLight:e,mode:e?"light":"dark",textColor:e?"#2c3e50":"#ffffff",textMuted:e?"#6c757d":"rgba(255, 255, 255, 0.7)",gridColor:e?"rgba(0, 0, 0, 0.08)":"rgba(255, 255, 255, 0.05)",surfaceColor:getComputedStyle(document.documentElement).getPropertyValue("--color-surface").trim()}}function Z(e=[]){return e.map(t=>{const a=Number(t);return Number.isFinite(a)?a:0})}function ue(e,t=380){const a=e?.closest(".chart-container")||e?.parentElement,o=a?getComputedStyle(a):null,r=o?Number.parseFloat(o.height):Number.NaN,s=o?Number.parseFloat(o.minHeight):Number.NaN,n=a?.getBoundingClientRect?.().height??Number.NaN,c=o?(Number.parseFloat(o.paddingTop)||0)+(Number.parseFloat(o.paddingBottom)||0):0,l=window.innerWidth<768?320:t,u=[r,n,s,l].find(v=>Number.isFinite(v)&&v>0)??l;return Math.max(260,Math.round(u-c))}const pe={[p.VIEWS.CATEGORY]:{tone:"category",kicker:"Leitura por categoria",icon:"pie-chart"},[p.VIEWS.BALANCE]:{tone:"balance",kicker:"Pulso do caixa",icon:"line-chart"},[p.VIEWS.COMPARISON]:{tone:"comparison",kicker:"Balanço operacional",icon:"bar-chart-3"},[p.VIEWS.ACCOUNTS]:{tone:"accounts",kicker:"Distribuição por conta",icon:"wallet"},[p.VIEWS.EVOLUTION]:{tone:"evolution",kicker:"Linha do tempo financeira",icon:"git-branch"},[p.VIEWS.ANNUAL_SUMMARY]:{tone:"annual",kicker:"Visão anual consolidada",icon:"calendar-days"},[p.VIEWS.ANNUAL_CATEGORY]:{tone:"annual",kicker:"Mapa anual por categoria",icon:"pie-chart"}};function Ye(){return C.isYearlyView()?`Ano ${String(i.currentMonth||"").split("-")[0]}`:C.formatMonthLabel(i.currentMonth)}function qe(){const e=C.getActiveCategoryType();return{despesas_por_categoria:"Despesas",receitas_por_categoria:"Receitas",despesas_anuais_por_categoria:"Despesas anuais",receitas_anuais_por_categoria:"Receitas anuais"}[e]||null}function ze(){return i.currentAccount?i.accounts.find(t=>String(t.id)===String(i.currentAccount))?.name||`Conta #${i.currentAccount}`:null}function J(e,t={}){const a=D[i.currentView]||D[p.VIEWS.CATEGORY],o=pe[i.currentView]||pe[p.VIEWS.CATEGORY],r=[{icon:"calendar-days",label:Ye()}],s=qe();s&&r.push({icon:"filter",label:s,accent:!0});const n=ze();n&&r.push({icon:"landmark",label:n});const c=t.title||a.title,l=t.description||a.description;return`
        <div class="report-visual-shell chart-shell chart-shell--${o.tone}">
            <div class="report-visual-header">
                <div class="report-visual-copy">
                    <span class="report-visual-kicker">
                        <i data-lucide="${o.icon}"></i>
                        <span>${M(t.kicker||o.kicker)}</span>
                    </span>
                    <h3 class="report-visual-title">${M(c)}</h3>
                    <p class="report-visual-description">${M(l)}</p>
                </div>

                <div class="report-visual-badges">
                    ${r.map(u=>`
                        <span class="report-visual-badge${u.accent?" report-visual-badge--accent":""}">
                            <i data-lucide="${u.icon}"></i>
                            <span>${M(u.label)}</span>
                        </span>
                    `).join("")}
                </div>
            </div>

            <div class="chart-shell-body">
                ${e}
            </div>
        </div>
    `}const b={_currentEntries:null,destroy(){i.chart&&(Array.isArray(i.chart)?i.chart.forEach(e=>e?.destroy()):i.chart.destroy(),i.chart=null),b._drilldownChart&&(b._drilldownChart.destroy(),b._drilldownChart=null),i.activeDrilldown=null,i.reportDetails=null},setupDefaults(){const e=getComputedStyle(document.documentElement).getPropertyValue("--color-text").trim();window.Apex=window.Apex||{},window.Apex.chart={foreColor:e,fontFamily:"Inter, Arial, sans-serif"},window.Apex.grid={borderColor:"rgba(255, 255, 255, 0.1)"}},renderPie(e){const{labels:t=[],values:a=[],details:o=null,cat_ids:r=null}=e;if(!t.length||!a.some(w=>w>0))return L.UI.showEmptyState();let s=t.map((w,S)=>({label:w,value:Number(a[S])||0,color:p.CHART_COLORS[S%p.CHART_COLORS.length],catId:r?r[S]??null:null})).filter(w=>w.value>0).sort((w,S)=>S.value-w.value);!r&&o&&(s=s.map(w=>{const S=o.find(O=>O.label===w.label);return{...w,catId:S?.cat_id??null}}));const n=window.innerWidth<768;let c=s;if(n&&s.length>5){const w=s.slice(0,5),O=s.slice(5).reduce((F,K)=>F+K.value,0);c=[...w,{label:"Outros",value:O,color:"#95a5a6",isOthers:!0}]}const l=!n&&c.length>2,u=l?Math.ceil(c.length/2):c.length,v=l?[c.slice(0,u),c.slice(u)].filter(w=>w.length):[c],d=J(`
            <div class="chart-container chart-container-pie">
                <div class="chart-dual">
                    ${v.map((w,S)=>`
                        <div class="chart-wrapper chart-wrapper-pie">
                            <div id="chart${S}"></div>
                        </div>
                    `).join("")}
                </div>
            </div>
            <div id="subcategoryDrilldown" class="drilldown-panel" aria-hidden="true"></div>
            ${n?'<div id="categoryListMobile" class="category-list-mobile"></div>':""}
        `);L.UI.setContent(d),b.destroy(),i.reportDetails=o,i.activeDrilldown=null,b._currentEntries=c;const m=C.getActiveCategoryType(),$={receitas_por_categoria:"Receitas por Categoria",despesas_por_categoria:"Despesas por Categoria",receitas_anuais_por_categoria:"Receitas anuais por Categoria",despesas_anuais_por_categoria:"Despesas anuais por Categoria"}[m]||"Distribuição por Categoria",f=H();let T=0;i.chart=v.map((w,S)=>{const O=document.getElementById(`chart${S}`);if(!O)return null;const F=w.reduce((A,W)=>A+W.value,0),K=T;T+=w.length;const ne=new ApexCharts(O,{chart:{type:"donut",height:"100%",background:"transparent",fontFamily:"Inter, Arial, sans-serif",events:{dataPointSelection:(A,W,ie)=>{const ce=K+ie.dataPointIndex,Q=c[ce];!Q||Q.isOthers||b.handlePieClick(Q,ce,ie.dataPointIndex,S)},dataPointMouseEnter:A=>{A.target&&(A.target.style.cursor="pointer")},dataPointMouseLeave:A=>{A.target&&(A.target.style.cursor="default")}}},series:w.map(A=>A.value),labels:w.map(A=>A.label),colors:w.map(A=>A.color),stroke:{width:2,colors:[f.surfaceColor]},plotOptions:{pie:{donut:{size:"60%"},expandOnClick:!0}},legend:{show:!n,position:"bottom",labels:{colors:f.textColor},markers:{shape:"circle"}},title:{text:v.length>1?`${$} - Parte ${S+1}`:$,align:"center",style:{fontSize:"14px",fontWeight:"bold",color:f.textColor}},tooltip:{theme:f.mode,y:{formatter:A=>{const W=F>0?(A/F*100).toFixed(1):"0";return`${_(A)} (${W}%)`}}},dataLabels:{enabled:!1},theme:{mode:f.mode}});return ne.render(),ne}),n&&b.renderMobileCategoryList(c)},renderMobileCategoryList(e){const t=document.getElementById("categoryListMobile");if(!t)return;const a=e.reduce((s,n)=>s+n.value,0),o=!!i.reportDetails&&window.IS_PRO,r=e.map((s,n)=>{const c=(s.value/a*100).toFixed(1),l=o&&s.catId!=null?i.reportDetails.find(m=>m.cat_id===s.catId):null,u=l&&l.subcategories&&l.subcategories.filter(m=>m.id!==0).length>0,v=u?'<i data-lucide="chevron-down" class="category-chevron"></i>':"";let d="";if(u){const m=C.generateShades(s.color,l.subcategories.length);d=`
                    <div class="category-subcats-panel" id="mobileSubcatPanel-${n}" aria-hidden="true">
                        ${l.subcategories.map((y,$)=>{const f=l.total>0?(y.total/l.total*100).toFixed(1):"0.0";return`
                                <div class="drilldown-item drilldown-item-mobile">
                                    <div class="drilldown-indicator" style="background-color: ${m[$]}"></div>
                                    <div class="drilldown-info">
                                        <span class="drilldown-name">${M(y.label)}</span>
                                    </div>
                                    <div class="drilldown-values">
                                        <span class="drilldown-value">${_(y.total)}</span>
                                        <span class="drilldown-pct">${f}%</span>
                                    </div>
                                </div>
                            `}).join("")}
                    </div>
                `}return`
                <div class="category-item ${u?"has-subcats":""}"
                     ${u?`data-subcat-toggle="${n}"`:""}>
                    <div class="category-indicator" style="background-color: ${s.color}"></div>
                    <div class="category-info">
                        <span class="category-name">${M(s.label)}</span>
                        <span class="category-value">${_(s.value)}</span>
                    </div>
                    <span class="category-percentage">${c}%</span>
                    ${v}
                </div>
                ${d}
            `}).join("");t.innerHTML=`
            <button class="category-expand-btn" id="expandCategoriesBtn" aria-expanded="false">
                <span>Ver todas as categorias</span>
                <i data-lucide="chevron-down"></i>
            </button>
            <div class="category-expandable-card" id="expandableCard" aria-hidden="true">
                ${r}
            </div>
            ${o?"":`<p class="category-info-text">
                <i data-lucide="info"></i>
                Para visualizar todas as categorias detalhadamente, exporte este relatório em PDF.
            </p>`}
        `,window.lucide&&lucide.createIcons(),b.setupExpandToggle(),o&&b.setupMobileSubcatToggles()},setupMobileSubcatToggles(){document.querySelectorAll("[data-subcat-toggle]").forEach(e=>{e.addEventListener("click",function(){const t=this.dataset.subcatToggle,a=document.getElementById(`mobileSubcatPanel-${t}`),o=this.querySelector(".category-chevron");if(!a)return;a.getAttribute("aria-hidden")==="false"?(a.style.maxHeight="0px",a.setAttribute("aria-hidden","true"),o&&(o.style.transform="rotate(0deg)")):(a.style.maxHeight=a.scrollHeight+"px",a.setAttribute("aria-hidden","false"),o&&(o.style.transform="rotate(180deg)"))})})},setupExpandToggle(){const e=document.getElementById("expandCategoriesBtn"),t=document.getElementById("expandableCard");!e||!t||e.addEventListener("click",function(){e.getAttribute("aria-expanded")==="true"?(t.style.maxHeight="0px",t.setAttribute("aria-hidden","true"),e.setAttribute("aria-expanded","false"),e.querySelector("span").textContent="Ver todas as categorias",e.querySelector("i").style.transform="rotate(0deg)"):(t.style.maxHeight=t.scrollHeight+"px",t.setAttribute("aria-hidden","false"),e.setAttribute("aria-expanded","true"),e.querySelector("span").textContent="Ocultar categorias",e.querySelector("i").style.transform="rotate(180deg)")})},handlePieClick(e,t,a,o){if(!window.IS_PRO){window.PlanLimits?.promptUpgrade?window.PlanLimits.promptUpgrade({context:"relatorios",message:"O detalhamento por subcategorias é exclusivo do plano Pro."}).catch(()=>{}):window.LKFeedback?.upgradePrompt?window.LKFeedback.upgradePrompt({context:"relatorios",message:"O detalhamento por subcategorias é exclusivo do plano Pro."}).catch(()=>{}):window.Swal?.fire&&Swal.fire({icon:"info",title:"Recurso Premium",html:"O detalhamento por <b>subcategorias</b> é exclusivo do <b>plano Pro</b>.<br>Faça upgrade para desbloquear!",confirmButtonText:"Fazer Upgrade",showCancelButton:!0,cancelButtonText:"Agora não",confirmButtonColor:"#f59e0b",cancelButtonColor:"#64748b"}).then(c=>{c.isConfirmed&&(window.location.href=(p.BASE_URL||"/")+"billing")});return}if(!i.reportDetails)return;const r=e.catId,s=i.reportDetails.find(c=>c.cat_id===r);if(!s||!s.subcategories||s.subcategories.length===0)return;if(s.subcategories.filter(c=>c.id!==0).length===0){window.Swal?.fire&&Swal.fire({icon:"info",title:"Sem subcategorias",text:"Atribua subcategorias aos seus lançamentos para ver o detalhamento desta categoria.",confirmButtonText:"Entendi",confirmButtonColor:"#f59e0b",timer:5e3,timerProgressBar:!0});return}if(i.activeDrilldown===r){b.closeDrilldown();return}i.activeDrilldown=r,b.renderSubcategoryDrilldown(s,e.color)},closeDrilldown(){i.activeDrilldown=null;const e=document.getElementById("subcategoryDrilldown");e&&(e.style.maxHeight="0px",e.setAttribute("aria-hidden","true"),setTimeout(()=>{e.innerHTML=""},400))},renderSubcategoryDrilldown(e,t){const a=document.getElementById("subcategoryDrilldown");if(!a)return;const{label:o,total:r,subcategories:s}=e,n=C.generateShades(t,s.length),c=s.map((v,d)=>{const m=r>0?(v.total/r*100).toFixed(1):"0.0",y=r>0?(v.total/r*100).toFixed(0):"0";return`
                <div class="drilldown-item" style="animation-delay: ${d*.05}s">
                    <div class="drilldown-indicator" style="background-color: ${n[d]}"></div>
                    <div class="drilldown-info">
                        <span class="drilldown-name">${M(v.label)}</span>
                        <div class="drilldown-bar-bg">
                            <div class="drilldown-bar" style="width: ${y}%; background-color: ${n[d]}"></div>
                        </div>
                    </div>
                    <div class="drilldown-values">
                        <span class="drilldown-value">${_(v.total)}</span>
                        <span class="drilldown-pct">${m}%</span>
                    </div>
                </div>
            `}).join(""),l=window.innerWidth<768,u=l?"":`
            <div class="drilldown-mini-chart">
                <div id="drilldownMiniChart"></div>
            </div>
        `;a.innerHTML=`
            <div class="drilldown-header" style="border-left-color: ${t}">
                <div class="drilldown-title">
                    <span class="drilldown-cat-indicator" style="background-color: ${t}"></span>
                    <h4>${M(o)}</h4>
                    <span class="drilldown-total">${_(r)}</span>
                </div>
                <button class="drilldown-close" id="drilldownCloseBtn" aria-label="Fechar detalhamento">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="drilldown-body">
                ${u}
                <div class="drilldown-list">
                    ${c}
                </div>
            </div>
        `,a.setAttribute("aria-hidden","false"),requestAnimationFrame(()=>{a.style.maxHeight=a.scrollHeight+"px"}),document.getElementById("drilldownCloseBtn")?.addEventListener("click",()=>{b.closeDrilldown()}),l||b._renderDrilldownMiniChart(s,n),window.lucide&&lucide.createIcons()},_renderDrilldownMiniChart(e,t){const a=document.getElementById("drilldownMiniChart");if(!a)return;b._drilldownChart&&(b._drilldownChart.destroy(),b._drilldownChart=null);const o=H(),r=e.reduce((s,n)=>s+n.total,0);b._drilldownChart=new ApexCharts(a,{chart:{type:"donut",height:"100%",background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:e.map(s=>s.total),labels:e.map(s=>s.label),colors:t,stroke:{width:2,colors:[o.surfaceColor]},plotOptions:{pie:{donut:{size:"55%"}}},legend:{show:!1},tooltip:{theme:o.mode,y:{formatter:s=>{const n=r>0?(s/r*100).toFixed(1):"0";return`${_(s)} (${n}%)`}}},dataLabels:{enabled:!1},theme:{mode:o.mode}}),b._drilldownChart.render()},_drilldownChart:null,renderLine(e){const{labels:t=[],values:a=[]}={...e,values:Z(e?.values)};if(!t.length)return L.UI.showEmptyState();L.UI.setContent(J(`
            <div class="chart-container chart-container-line">
                <div class="chart-wrapper chart-wrapper-line">
                    <div id="chart0"></div>
                </div>
            </div>
        `)),b.destroy();const o=getComputedStyle(document.documentElement).getPropertyValue("--color-primary").trim(),r=H(),s=document.getElementById("chart0"),n=ue(s,420),c=new ApexCharts(s,{chart:{type:"area",height:n,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,redrawOnParentResize:!0,redrawOnWindowResize:!0},series:[{name:"Saldo Diário",data:a.map(Number)}],xaxis:{categories:t,labels:{style:{fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:r.isLight?"#000":"#fff",fontSize:"11px"},formatter:l=>_(l)}},colors:[o],stroke:{curve:"smooth",width:2.5},fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.4,opacityTo:.05,stops:[0,100]}},markers:{size:4,hover:{size:6}},grid:{borderColor:r.gridColor,strokeDashArray:4},tooltip:{theme:r.mode,y:{formatter:l=>_(l)}},legend:{position:"bottom",labels:{colors:r.textColor}},dataLabels:{enabled:!1},theme:{mode:r.mode}});c.render(),i.chart=c},renderBar(e){const{labels:t=[],receitas:a=[],despesas:o=[]}={...e,receitas:Z(e?.receitas),despesas:Z(e?.despesas)};if(!t.length)return L.UI.showEmptyState();const r=i.currentView===p.VIEWS.ACCOUNTS?"Receitas x Despesas por Conta":i.currentView===p.VIEWS.ANNUAL_SUMMARY?"Resumo Anual por Mês":"Receitas x Despesas";L.UI.setContent(J(`
            <div class="chart-container chart-container-bar">
                <div class="chart-wrapper chart-wrapper-bar">
                    <div id="chart0"></div>
                </div>
            </div>
        `,{title:r})),b.destroy();const s=C.getCssVar("--color-success","#2ecc71"),n=C.getCssVar("--color-danger","#e74c3c"),c=H(),l=document.getElementById("chart0"),u=ue(l,420),v=new ApexCharts(l,{chart:{type:"bar",height:u,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,redrawOnParentResize:!0,redrawOnWindowResize:!0},series:[{name:"Receitas",data:a.map(Number)},{name:"Despesas",data:o.map(Number)}],xaxis:{categories:t,labels:{style:{colors:c.textMuted,fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:c.isLight?"#000":"#fff",fontSize:"11px"},formatter:d=>_(d)}},colors:[s,n],plotOptions:{bar:{borderRadius:6,columnWidth:"55%"}},grid:{borderColor:c.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:c.mode,shared:!0,intersect:!1,y:{formatter:d=>_(d)}},legend:{position:"bottom",labels:{colors:c.textColor},markers:{shape:"circle"}},dataLabels:{enabled:!1},theme:{mode:c.mode}});v.render(),i.chart=v}};L.ChartManager=b;const Ge={toggleRelQuickStats:"relQuickStats",toggleRelOverviewCharts:"relOverviewChartsRow",toggleRelControls:"relControlsRow"},be={toggleRelQuickStats:!0,toggleRelOverviewCharts:!0,toggleRelControls:!0},je={...be,toggleRelQuickStats:!1,toggleRelControls:!1};async function Ke(){return Re("relatorios")}async function Qe(e){await Me("relatorios",e)}const Ze=Le({storageKey:"lk_relatorios_prefs",sectionMap:Ge,completeDefaults:be,essentialDefaults:je,loadPreferences:Ke,savePreferences:Qe,modal:{overlayId:"relatoriosCustomizeModalOverlay",openButtonId:"btnCustomizeRelatorios",closeButtonId:"btnCloseCustomizeRelatorios",saveButtonId:"btnSaveCustomizeRelatorios",presetEssentialButtonId:"btnPresetEssencialRelatorios",presetCompleteButtonId:"btnPresetCompletoRelatorios"}});function Je(){Ze.init()}const g=e=>C.formatCurrency(e);function Xe(){return C.isYearlyView()?`Ano ${String(i.currentMonth||"").split("-")[0]}`:C.formatMonthLabel(i.currentMonth)}function Y(e,t,a,o=!1){const r=document.getElementById(e);if(!r)return;if(!a||a===0){r.innerHTML="",r.className="stat-trend";return}const s=(t-a)/Math.abs(a)*100,n=Math.abs(s).toFixed(1);if(Math.abs(s)<.5)r.className="stat-trend trend-neutral",r.textContent="— Sem alteração";else{const c=s>0,l=o?!c:c;r.className=`stat-trend ${l?"trend-positive":"trend-negative"}`;const u=c?"↑":"↓";r.textContent=`${u} ${n}% vs mês anterior`}}function et(e){const t=document.querySelector(".chart-insight-line");if(t&&t.remove(),!e)return;let a="";switch(i.currentView){case p.VIEWS.CATEGORY:case p.VIEWS.ANNUAL_CATEGORY:{if(!e.labels||!e.values||e.values.length===0)break;const n=e.values.reduce((c,l)=>c+Number(l),0);if(n>0){const c=e.values.reduce((u,v,d,m)=>Number(v)>Number(m[u])?d:u,0),l=(Number(e.values[c])/n*100).toFixed(0);a=`${e.labels[c]} lidera com ${l}% dos gastos (${g(e.values[c])})`}break}case p.VIEWS.BALANCE:{if(!e.labels||!e.values||e.values.length===0)break;const n=e.values.map(Number),c=Math.min(...n),l=n.indexOf(c);a=`Menor saldo: ${g(c)} em ${e.labels[l]}`;break}case p.VIEWS.COMPARISON:{if(!e.receitas||!e.despesas)break;const n=e.receitas.map(Number),c=e.despesas.map(Number);a=`Em ${n.filter((u,v)=>u>(c[v]||0)).length} de ${n.length} dias, receitas superaram despesas`;break}case p.VIEWS.ACCOUNTS:{if(!e.labels||!e.despesas||e.despesas.length===0)break;const n=e.despesas.map(Number),c=n.reduce((l,u,v,d)=>u>d[l]?v:l,0);a=`Maior gasto: ${e.labels[c]} com ${g(n[c])} em despesas`;break}case p.VIEWS.EVOLUTION:{if(!e.values||e.values.length<2)break;const n=e.values.map(Number),c=n[0],l=n[n.length-1];a=`Evolução nos últimos 12 meses: ${l>c?"tendência de alta":l<c?"tendência de queda":"estável"}`;break}case p.VIEWS.ANNUAL_SUMMARY:{if(!e.labels||!e.receitas||e.receitas.length===0)break;const n=e.receitas.map(Number),c=e.despesas.map(Number),l=n.map((d,m)=>d-(c[m]||0)),u=l.reduce((d,m,y,$)=>m>$[d]?y:d,0),v=l.reduce((d,m,y,$)=>m<$[d]?y:d,0);a=`Melhor mês: ${e.labels[u]}. Pior mês: ${e.labels[v]}`;break}}if(!a)return;const r=document.getElementById("reportArea");if(!r)return;const s=document.createElement("div");s.className="chart-insight-line",s.innerHTML=`<i data-lucide="sparkles"></i> <span>${h(a)}</span>`,r.appendChild(s),window.lucide&&lucide.createIcons()}function tt(e){return!e||e.length===0?"":`
        <div class="comparative-card comparative-card--support comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <div class="comparative-heading">
                    <span class="comparative-kicker">Peso nas despesas</span>
                    <h3><i data-lucide="bar-chart-3"></i> Top Categorias de Despesa</h3>
                    <span class="comp-subtitle">Mês atual vs anterior</span>
                </div>
            </div>
            <div class="cat-comp-list">
                <div class="cat-comp-header-row">
                    <span></span><span></span>
                    <span class="cat-comp-col-label">Atual / Anterior</span>
                    <span class="cat-comp-col-label">Variação</span>
                </div>
                ${e.map((a,o)=>{const r=a.variacao>0?"trend-negative":a.variacao<0?"trend-positive":"trend-neutral",s=a.variacao>0?"arrow-up":a.variacao<0?"arrow-down":"equal",n=Math.abs(a.variacao)<.1?"Sem alteração":`${a.variacao>0?"+":""}${a.variacao.toFixed(1)}%`,c=e.reduce((v,d)=>v+d.atual,0),l=c>0?(a.atual/c*100).toFixed(0):0;let u="";return a.subcategorias&&a.subcategorias.length>0&&(u=`<div class="cat-comp-subcats">${a.subcategorias.map(d=>{const m=d.variacao>0?"trend-negative":d.variacao<0?"trend-positive":"",y=Math.abs(d.variacao)<.1?"":`<span class="subcat-trend ${m}">${d.variacao>0?"↑":"↓"}${Math.abs(d.variacao).toFixed(0)}%</span>`;return`
                    <span class="cat-comp-subcat-pill">
                        ${h(d.nome)}
                        <span class="subcat-value">${g(d.atual)}</span>
                        ${y}
                    </span>
                `}).join("")}</div>`),`
            <div class="cat-comp-row" style="animation-delay: ${o*.06}s">
                <div class="cat-comp-rank">${o+1}</div>
                <div class="cat-comp-info">
                    <span class="cat-comp-name">${h(a.nome)}</span>
                    <div class="cat-comp-bar-bg">
                        <div class="cat-comp-bar" style="width: ${l}%"></div>
                    </div>
                    ${u}
                </div>
                <div class="cat-comp-values">
                    <span class="cat-comp-current">${g(a.atual)}</span>
                    <span class="cat-comp-prev">${g(a.anterior)}</span>
                </div>
                <div class="cat-comp-trend ${r}">
                    <i data-lucide="${s}"></i>
                    <span>${n}</span>
                </div>
            </div>
        `}).join("")}
            </div>
        </div>
    `}function at(e){return!e||e.length===0?"":`
        <div class="comparative-card comparative-card--support comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <div class="comparative-heading">
                    <span class="comparative-kicker">Ritmo recente</span>
                    <h3><i data-lucide="line-chart"></i> Evolução dos Últimos 6 Meses</h3>
                    <span class="comp-subtitle">Receitas, despesas e saldo ao longo do tempo</span>
                </div>
            </div>
            <div class="evolucao-chart-wrapper">
                <div id="evolucaoMiniChart" style="min-height:220px;"></div>
            </div>
        </div>
    `}let B=null;function st(e){if(!e||e.length===0)return;const t=document.getElementById("evolucaoMiniChart");if(!t)return;const a=e.map(c=>c.label),r=getComputedStyle(document.documentElement).getPropertyValue("--color-text-muted").trim()||"#999",n=document.documentElement.getAttribute("data-theme")==="dark"?"dark":"light";B&&(B.destroy(),B=null),B=new ApexCharts(t,{chart:{type:"line",height:260,stacked:!1,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:[{name:"Receitas",type:"column",data:e.map(c=>c.receitas)},{name:"Despesas",type:"column",data:e.map(c=>c.despesas)},{name:"Saldo",type:"area",data:e.map(c=>c.saldo)}],xaxis:{categories:a,labels:{style:{colors:r}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:r},formatter:c=>g(c)}},colors:["rgba(46, 204, 113, 0.85)","rgba(231, 76, 60, 0.85)","#3498db"],stroke:{width:[0,0,2.5],curve:"smooth"},fill:{opacity:[.85,.85,.1]},plotOptions:{bar:{borderRadius:6,columnWidth:"55%"}},grid:{borderColor:"rgba(128,128,128,0.1)",strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:n,shared:!0,intersect:!1,y:{formatter:c=>g(c)}},legend:{position:"bottom",labels:{colors:r},markers:{shape:"circle"}},dataLabels:{enabled:!1},theme:{mode:n}}),B.render()}function rt(e){if(!e)return"";const t=e.variacao>0?"trend-negative":e.variacao<0?"trend-positive":"trend-neutral",a=e.variacao>0?"arrow-up":e.variacao<0?"arrow-down":"equal";return`
        <div class="comparative-card comparative-card--compact comp-mini-card surface-card surface-card--interactive">
            <div class="comp-mini-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i data-lucide="calendar-clock"></i>
            </div>
            <div class="comp-mini-body">
                <span class="comp-mini-label">Média Diária de Gastos</span>
                <div class="comp-mini-values">
                    <span class="comp-mini-current">${g(e.atual)}/dia</span>
                    <span class="comp-mini-prev">anterior: ${g(e.anterior)}/dia</span>
                </div>
                <div class="comp-mini-trend ${t}">
                    <i data-lucide="${a}"></i>
                    <span>${Math.abs(e.variacao).toFixed(1)}%</span>
                </div>
            </div>
        </div>
    `}function ot(e){if(!e)return"";const t=e.atual>=0,a=e.diferenca>0?"trend-positive":e.diferenca<0?"trend-negative":"trend-neutral",o=e.diferenca>0?"arrow-up":e.diferenca<0?"arrow-down":"equal";return`
        <div class="comparative-card comparative-card--compact comp-mini-card surface-card surface-card--interactive">
            <div class="comp-mini-icon" style="background: linear-gradient(135deg, ${t?"#2ecc71, #27ae60":"#e74c3c, #c0392b"});">
                <i data-lucide="piggy-bank" style= "color: white"></i>
            </div>
            <div class="comp-mini-body">
                <span class="comp-mini-label">Taxa de Economia</span>
                <div class="comp-mini-values">
                    <span class="comp-mini-current">${e.atual.toFixed(1)}%</span>
                    <span class="comp-mini-prev">anterior: ${e.anterior.toFixed(1)}%</span>
                </div>
                <div class="comp-mini-trend ${a}">
                    <i data-lucide="${o}"></i>
                    <span>${e.diferenca>0?"+":""}${e.diferenca.toFixed(1)}pp</span>
                </div>
            </div>
        </div>
    `}function nt(e){if(!e||e.length===0)return"";const t={Pix:"zap","Cartão de Crédito":"credit-card","Cartão de Débito":"credit-card",Dinheiro:"banknote",Boleto:"file-text",Depósito:"landmark",Transferência:"arrow-right-left",Estorno:"undo-2"},a=e.reduce((r,s)=>r+s.atual,0);return`
        <div class="comparative-card comparative-card--support comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <div class="comparative-heading">
                    <span class="comparative-kicker">Mix operacional</span>
                    <h3><i data-lucide="wallet"></i> Formas de Pagamento</h3>
                    <span class="comp-subtitle">Distribuição mês atual vs anterior</span>
                </div>
            </div>
            <div class="forma-comp-list">
                ${e.map((r,s)=>{const n=a>0?(r.atual/a*100).toFixed(0):0,c=t[r.nome]||"wallet";return`
            <div class="forma-comp-row" style="animation-delay: ${s*.06}s">
                <div class="forma-comp-icon"><i data-lucide="${c}"></i></div>
                <div class="forma-comp-info">
                    <span class="forma-comp-name">${h(r.nome)}</span>
                    <div class="forma-comp-bar-bg">
                        <div class="forma-comp-bar" style="width: ${n}%"></div>
                    </div>
                </div>
                <div class="forma-comp-values">
                    <span class="forma-comp-current">${g(r.atual)} <small>(${r.atual_qtd}x)</small></span>
                    <span class="forma-comp-prev">${g(r.anterior)} <small>(${r.anterior_qtd}x)</small></span>
                </div>
            </div>
        `}).join("")}
            </div>
        </div>
    `}function me(e,t,a,o="monthly"){const r=d=>d>0?'<i data-lucide="arrow-up"></i>':d<0?'<i data-lucide="arrow-down"></i>':'<i data-lucide="equal"></i>',s=(d,m=!1)=>{if(m){if(d>0)return"trend-negative";if(d<0)return"trend-positive"}else{if(d>0)return"trend-positive";if(d<0)return"trend-negative"}return"trend-neutral"},n=d=>Math.abs(d)<.1?"Sem alteração":d>0?`Aumentou ${Math.abs(d).toFixed(1)}%`:d<0?`Reduziu ${Math.abs(d).toFixed(1)}%`:"Sem alteração",c=()=>{if(a.includes("mês")){const[d,m]=i.currentMonth.split("-");return new Date(d,m-1).toLocaleDateString("pt-BR",{month:"short",year:"numeric"})}else return i.currentMonth.split("-")[0]},l=()=>{if(a.includes("mês")){const[d,m]=i.currentMonth.split("-");return new Date(d,m-2).toLocaleDateString("pt-BR",{month:"short",year:"numeric"})}else return(parseInt(i.currentMonth.split("-")[0])-1).toString()},u=[{label:"Receitas",icon:"trending-up",value:g(t.current.receitas),trendText:n(t.variation.receitas),trendClass:s(t.variation.receitas,!1)},{label:"Despesas",icon:"trending-down",value:g(t.current.despesas),trendText:n(t.variation.despesas),trendClass:s(t.variation.despesas,!0)},{label:"Saldo",icon:"wallet",value:g(t.current.saldo),trendText:n(t.variation.saldo),trendClass:s(t.variation.saldo,!1)}];return`
        <div class="comparative-card comparative-card--hero comparative-card--${o} surface-card surface-card--interactive">
            <div class="comparative-header">
                <div class="comparative-heading">
                    <span class="comparative-kicker">${h(o==="annual"?"Leitura anual":"Leitura mensal")}</span>
                    <h3>${h(e)}</h3>
                </div>
                <div class="period-labels">
                    <span class="period-current"><i data-lucide="calendar" style="color: white;"></i> ${c()}</span>
                    <span class="period-separator">vs</span>
                    <span class="period-previous">${l()}</span>
                </div>
            </div>

            <div class="comparative-glance">
                ${u.map(d=>`
                    <div class="comparative-glance-item ${d.trendClass}">
                        <span class="comparative-glance-label">
                            <i data-lucide="${d.icon}"></i>
                            <span>${d.label}</span>
                        </span>
                        <strong class="comparative-glance-value">${d.value}</strong>
                        <span class="comparative-glance-trend">${d.trendText}</span>
                    </div>
                `).join("")}
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
                            <span class="value-amount">${g(t.current.receitas)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${g(t.previous.receitas)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${s(t.variation.receitas,!1)}">
                        ${r(t.variation.receitas)}
                        <span>${n(t.variation.receitas)}</span>
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
                            <span class="value-amount">${g(t.current.despesas)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${g(t.previous.despesas)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${s(t.variation.despesas,!0)}">
                        ${r(t.variation.despesas)}
                        <span>${n(t.variation.despesas)}</span>
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
                            <span class="value-amount">${g(t.current.saldo)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${g(t.previous.saldo)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${s(t.variation.saldo,!1)}">
                        ${r(t.variation.saldo)}
                        <span>${n(t.variation.saldo)}</span>
                    </div>
                </div>
            </div>
        </div>
    `}function it(e){const t=document.getElementById("reportArea");if(!t)return;const a=D[p.VIEWS.CARDS]||{title:"Saude dos cartoes"},o=e.cards?.length?`${e.cards.length} cart${e.cards.length>1?"ões":"ão"} acompanhad${e.cards.length>1?"os":"o"}`:"Sem cartoes ativos",r=e.resumo_consolidado&&e.cards&&e.cards.length>0?`
        <div class="consolidated-summary consolidated-summary--fused">
            <div class="summary-topline">
                <div class="summary-title-block">
                    <span class="summary-eyebrow">
                        <i data-lucide="credit-card"></i>
                        <span>Visão Geral dos Cartões</span>
                    </span>
                    <p class="summary-lead">Resumo consolidado de todos os seus cartões de crédito</p>
                </div>
                <span class="summary-context-badge">
                    <i data-lucide="wallet"></i>
                    <span>${h(o)}</span>
                </span>
            </div>
            
            <div class="summary-grid">
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        <i data-lucide="file-text" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Total em Faturas</span>
                        <span class="stat-value">${g(e.resumo_consolidado.total_faturas)}</span>
                    </div>
                </div>
                
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i data-lucide="wallet" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Limite Total</span>
                        <span class="stat-value">${g(e.resumo_consolidado.total_limites)}</span>
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
                        <span class="stat-value">${g(e.resumo_consolidado.total_disponivel)}</span>
                    </div>
                </div>
            </div>
            
            ${e.resumo_consolidado.melhor_cartao||e.resumo_consolidado.requer_atencao?`
                <div class="summary-insights">
                    ${e.resumo_consolidado.melhor_cartao?`
                        <div class="insight-item success">
                            <i data-lucide="star"></i>
                            <span><strong>Melhor cartão:</strong> ${h(e.resumo_consolidado.melhor_cartao.nome)} (${e.resumo_consolidado.melhor_cartao.percentual.toFixed(1)}% de uso)</span>
                        </div>
                    `:""}
                    ${e.resumo_consolidado.requer_atencao?`
                        <div class="insight-item warning">
                            <i data-lucide="triangle-alert"></i>
                            <span><strong>Requer atenção:</strong> ${h(e.resumo_consolidado.requer_atencao.nome)} (${e.resumo_consolidado.requer_atencao.percentual.toFixed(1)}% de uso)</span>
                        </div>
                    `:""}
                    ${e.resumo_consolidado.total_parcelamentos>0?`
                        <div class="insight-item info">
                            <i data-lucide="calendar-check"></i>
                            <span><strong>${e.resumo_consolidado.total_parcelamentos} parcelamento${e.resumo_consolidado.total_parcelamentos>1?"s":""}</strong> comprometendo ${g(e.resumo_consolidado.valor_parcelamentos)}</span>
                        </div>
                    `:""}
                </div>
            `:""}
        </div>
    `:"";t.innerHTML=`
        <div class="cards-report-container cards-report-container--compact-top">
            <div class="report-visual-header report-visual-header--cards">
                <div class="report-visual-copy">
                    <span class="report-visual-kicker">
                        <i data-lucide="credit-card"></i>
                        <span>Radar dos cartoes</span>
                    </span>
                    <h3 class="report-visual-title">${h(a.title)}</h3>
                </div>

                <div class="report-visual-badges">
                    <span class="report-visual-badge">
                        <i data-lucide="calendar-days"></i>
                        <span>${h(Xe())}</span>
                    </span>
                </div>
            </div>

            ${r}
            
            <div class="cards-grid">
                ${e.cards&&e.cards.length>0?e.cards.map(s=>{const n=He(s.cor,"#E67E22");return`
                    <div class="card-item surface-card surface-card--interactive surface-card--clip ${s.status_saude.status}"
                         style="--card-color: ${n}; cursor: pointer;"
                         data-card-id="${s.id||""}"
                         data-card-month="${i.currentMonth}"
                         data-action="open-card-detail"
                         role="button"
                         tabindex="0">
                        <div class="card-header-gradient">
                            <div class="card-brand">
                                <div class="card-icon-wrapper" style="background: linear-gradient(135deg, ${n}, ${n}99);">
                                    <i data-lucide="credit-card" style="color: white"></i>
                                </div>
                                <div class="card-info">
                                    <h3 class="card-name">${h(s.nome)}</h3>
                                    <div class="card-meta">
                                        ${s.conta?`<span class="card-account"><i data-lucide="landmark"></i> ${h(s.conta)}</span>`:""}
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
                                ${s.alertas.map(c=>`
                                    <span class="alert-badge alert-${c.type}">
                                        <i data-lucide="${c.type==="danger"?"triangle-alert":c.type==="warning"?"circle-alert":"info"}"></i>
                                        ${h(c.message)}
                                    </span>
                                `).join("")}
                            </div>
                        `:""}


                        <div class="card-balance">
                            <div class="balance-main">
                                <span class="balance-label">FATURA DO MÊS</span>
                                <span class="balance-value">${g(s.fatura_atual||0)}</span>
                                ${s.media_historica>0&&Math.abs(s.fatura_atual-s.media_historica)>1?`
                                    <span class="balance-comparison">
                                        ${s.fatura_atual>s.media_historica?"↑":"↓"} ${(Math.abs(s.fatura_atual-s.media_historica)/s.media_historica*100).toFixed(0)}% vs média
                                    </span>
                                `:""}
                            </div>
                            <div class="balance-grid">
                                <div class="balance-item">
                                    <span class="balance-small-label">Limite</span>
                                    <span class="balance-small-value">${g(s.limite||0)}</span>
                                </div>
                                <div class="balance-item">
                                    <span class="balance-small-label">Disponível</span>
                                    <span class="balance-small-value">${g(s.disponivel||0)}</span>
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

                        ${s.parcelamentos&&s.parcelamentos.ativos>0||s.proximos_meses&&s.proximos_meses.length>0&&s.proximos_meses.some(c=>c.valor>0)?`
                            <div class="card-quick-info">
                                ${s.parcelamentos&&s.parcelamentos.ativos>0?`
                                    <div class="quick-info-item">
                                        <i data-lucide="calendar-check"></i>
                                        <span>${s.parcelamentos.ativos} parcelamento${s.parcelamentos.ativos>1?"s":""}</span>
                                    </div>
                                `:""}
                                ${s.proximos_meses&&s.proximos_meses.length>0&&s.proximos_meses.some(c=>c.valor>0)?`
                                    <div class="quick-info-item">
                                        <i data-lucide="line-chart"></i>
                                        <span>Próximo: ${g(s.proximos_meses.find(c=>c.valor>0)?.valor||0)}</span>
                                    </div>
                                `:""}
                            </div>
                        `:""}
                        
                        <div class="card-footer">
                            <button class="card-action-btn primary full-width" data-action="open-card-detail" data-card-id="${s.id||""}" data-card-month="${i.currentMonth}" title="Ver relatório detalhado">
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
    `,window.lucide&&lucide.createIcons()}const I=e=>C.formatCurrency(e),ye={"arrow-trend-up":"trending-up","arrow-trend-down":"trending-down","arrow-up":"arrow-up","arrow-down":"arrow-down","chart-line":"line-chart","chart-pie":"pie-chart","exclamation-triangle":"triangle-alert","exclamation-circle":"circle-alert","check-circle":"circle-check","info-circle":"info",lightbulb:"lightbulb",star:"star",bolt:"zap",wallet:"wallet","credit-card":"credit-card","calendar-check":"calendar-check",calendar:"calendar",crown:"crown",trophy:"trophy",leaf:"leaf","shield-alt":"shield","money-bill-wave":"banknote","trending-up":"trending-up","trending-down":"trending-down","shield-alert":"shield-alert",gauge:"gauge",target:"target",clock:"clock",receipt:"receipt",calculator:"calculator",layers:"layers","calendar-clock":"calendar-clock","pie-chart":"pie-chart","calendar-range":"calendar-range","list-plus":"list-plus","list-minus":"list-minus","file-text":"file-text","piggy-bank":"piggy-bank",banknote:"banknote"};let G=[];function ct(){G.forEach(e=>{try{e.destroy()}catch{}}),G=[]}function lt(e,t){if(!e)return;const a=t.saldo||0,o=a>=0?"var(--color-success)":"var(--color-danger)",r=a>=0?"positivo":"negativo";let s=`
        <p class="pulse-text">
            Neste mês você recebeu <strong>${I(t.totalReceitas)}</strong>
            e gastou <strong>${I(t.totalDespesas)}</strong>.
            Seu saldo é <strong style="color:${o}">${r} em ${I(Math.abs(a))}</strong>.
    `;t.totalCartoes>0&&(s+=` Faturas de cartões somam <strong>${I(t.totalCartoes)}</strong>.`),s+="</p>",e.innerHTML=s}function dt(e,t){if(e){if(t?.insights?.length>0){e.innerHTML=t.insights.map(a=>{const o=ye[a.icon]||a.icon;return`
                <div class="insight-card insight-${a.type} surface-card surface-card--interactive">
                    <div class="insight-icon"><i data-lucide="${o}"></i></div>
                    <div class="insight-content">
                        <h4>${h(a.title)}</h4>
                        <p>${h(a.message)}</p>
                    </div>
                </div>`}).join("");return}e.innerHTML='<p class="empty-message">Nenhum insight disponível no momento</p>'}}function ut(e,t){if(!e)return;if(!t?.labels?.length){e.innerHTML='<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de categorias</p>';return}e.innerHTML="";const a=5,o=t.labels.slice(0,a),r=t.values.slice(0,a).map(Number);if(t.labels.length>a){const n=t.values.slice(a).reduce((c,l)=>c+Number(l),0);o.push("Outros"),r.push(n)}const s=new ApexCharts(e,{chart:{type:"donut",height:220,background:"transparent"},series:r,labels:o,colors:["#E67E22","#2C3E50","#2ECC71","#F39C12","#9B59B6","#1ABC9C"],legend:{position:"bottom",fontSize:"11px",labels:{colors:"var(--color-text-muted)"}},dataLabels:{enabled:!1},plotOptions:{pie:{donut:{size:"60%"}}},stroke:{show:!1},tooltip:{y:{formatter:n=>I(n)}}});s.render(),G.push(s)}function pt(e,t){if(!e)return;if(!t?.labels?.length){e.innerHTML='<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de movimentação</p>';return}e.innerHTML="";const a=(t.receitas||[]).map(Number),o=(t.despesas||[]).map(Number),r=[],s=[],n=[],c=7;for(let u=0;u<t.labels.length;u+=c){const v=Math.floor(u/c)+1;r.push(`Sem ${v}`),s.push(a.slice(u,u+c).reduce((d,m)=>d+m,0)),n.push(o.slice(u,u+c).reduce((d,m)=>d+m,0))}const l=new ApexCharts(e,{chart:{type:"bar",height:220,background:"transparent",toolbar:{show:!1}},series:[{name:"Receitas",data:s},{name:"Despesas",data:n}],colors:["#2ECC71","#E74C3C"],xaxis:{categories:r,labels:{style:{colors:"var(--color-text-muted)",fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{fontSize:"10px"},formatter:u=>I(u)}},plotOptions:{bar:{columnWidth:"60%",borderRadius:4}},dataLabels:{enabled:!1},legend:{position:"bottom",fontSize:"11px",labels:{colors:"var(--color-text-muted)"}},grid:{borderColor:"rgba(255,255,255,0.05)"},tooltip:{shared:!0,intersect:!1,y:{formatter:u=>I(u)}}});l.render(),G.push(l)}function mt({API:e}){async function t(){const s=document.getElementById("overviewPulse"),n=document.getElementById("overviewInsights"),c=document.getElementById("overviewCategoryChart"),l=document.getElementById("overviewComparisonChart");ct();const[u,v,d,m]=await Promise.all([e.fetchSummaryStats(),e.fetchInsightsTeaser(),e.fetchReportDataForType("despesas_por_categoria",{accountId:null}),e.fetchReportDataForType("receitas_despesas_diario",{accountId:null})]);lt(s,u),dt(n,v),ut(c,d),pt(l,m),window.lucide&&lucide.createIcons()}async function a(){const s=document.getElementById("insightsContainer");if(!s)return;const n=window.IS_PRO?await e.fetchInsights():await e.fetchInsightsTeaser();if(!n||!n.insights||n.insights.length===0){s.innerHTML='<p class="empty-message">Nenhum insight disponível no momento</p>';return}s.classList.add("insights-grid--has-featured");const c=n.insights.map((l,u)=>{const v=ye[l.icon]||l.icon;return`
                <div class="insight-card insight-${l.type} surface-card surface-card--interactive${u===0?" insight-card--featured":""}">
                    <div class="insight-icon">
                        <i data-lucide="${v}"></i>
                    </div>
                    <div class="insight-content">
                        ${u===0?'<span class="insight-priority-pill">Destaque do período</span>':""}
                        <h4>${h(l.title)}</h4>
                        <p>${h(l.message)}</p>
                    </div>
                </div>
            `}).join("");if(s.innerHTML=c,!window.IS_PRO&&n.isTeaser){const l=Math.max(0,(n.totalCount||0)-n.insights.length),u=l>0?`Desbloqueie mais ${l} insights com PRO`:"Desbloqueie todos os insights com PRO";s.insertAdjacentHTML("beforeend",`
                <div class="insights-teaser-overlay">
                    <div class="teaser-blur-mask"></div>
                    <div class="teaser-cta">
                        <i data-lucide="crown"></i>
                        <h4>${u}</h4>
                        <p>Tenha uma visão completa da sua saúde financeira com análises detalhadas.</p>
                        <a href="${p.BASE_URL}billing" class="btn-upgrade-cta surface-button surface-button--upgrade">
                            <i data-lucide="crown"></i> Fazer Upgrade
                        </a>
                    </div>
                </div>
            `)}window.lucide&&lucide.createIcons()}async function o(){const s=document.getElementById("comparativesContainer");if(!s)return;const n=await e.fetchComparatives();if(!n){s.innerHTML='<p class="empty-message">Dados de comparação não disponíveis</p>';return}const c=me("Comparativo Mensal",n.monthly,"mês anterior","monthly"),l=me("Comparativo Anual",n.yearly,"ano anterior","annual"),u=tt(n.categories||[]),v=at(n.evolucao||[]),d=rt(n.mediaDiaria),m=ot(n.taxaEconomia),y=nt(n.formasPagamento||[]);s.innerHTML=`<div class="comp-top-row">${c}${l}</div><section class="comparatives-subsection comparatives-subsection--metrics">
                <div class="comparatives-subsection-header">
                    <span class="comparatives-subsection-kicker">Leituras rápidas</span>
                    <h4>Indicadores de eficiência</h4>
                </div>
                <div class="comp-duo-grid">${d}${m}</div>
            </section><section class="comparatives-subsection comparatives-subsection--analysis">
                <div class="comparatives-subsection-header">
                    <span class="comparatives-subsection-kicker">Leituras complementares</span>
                    <h4>Entenda onde a diferença apareceu</h4>
                </div>
                <div class="comparatives-support-stack">${u}${v}${y}</div>
            </section>`,window.lucide&&lucide.createIcons(),st(n.evolucao||[])}async function r(){const s=await e.fetchSummaryStats(),n=document.getElementById("totalReceitas"),c=document.getElementById("totalDespesas"),l=document.getElementById("saldoMes"),u=document.getElementById("totalCartoes");if(n&&(n.textContent=I(s.totalReceitas||0)),c&&(c.textContent=I(s.totalDespesas||0)),l){const y=s.saldo||0;l.textContent=I(y),l.style.color=y>=0?"var(--color-success)":"var(--color-danger)"}u&&(u.textContent=I(s.totalCartoes||0)),Y("trendReceitas",s.totalReceitas,s.prevReceitas,!1),Y("trendDespesas",s.totalDespesas,s.prevDespesas,!0),Y("trendSaldo",s.saldo,s.prevSaldo,!1),Y("trendCartoes",s.totalCartoes,s.prevCartoes,!0);const v=document.getElementById("section-overview");v&&v.classList.contains("active")&&await t();const d=document.getElementById("section-insights");d&&d.classList.contains("active")&&await a();const m=document.getElementById("section-comparativos");m&&m.classList.contains("active")&&await o()}return{updateSummaryCards:r,updateInsightsSection:a,updateOverviewSection:t,updateComparativesSection:o}}function vt(e){return Array.from(e.querySelectorAll('button:not([disabled]), select:not([disabled]), input:not([disabled]), [href], [tabindex]:not([tabindex="-1"])')).filter(t=>t.offsetParent!==null)}function ve(e,t,a=""){const o=a?`${t}: ${a}`:t;if(typeof window.showToast=="function"){window.showToast(o,e,e==="error"?4500:3e3);return}let r=document.getElementById("relExportToastContainer");r||(r=document.createElement("div"),r.id="relExportToastContainer",r.className="rel-export-toast-container",document.body.appendChild(r));const s=document.createElement("div");s.className=`rel-export-toast rel-export-toast--${e}`,s.textContent=o,r.appendChild(s),requestAnimationFrame(()=>s.classList.add("is-visible")),setTimeout(()=>{s.classList.remove("is-visible"),setTimeout(()=>s.remove(),220)},e==="error"?4500:3e3)}function ht(e){const t=document.getElementById("relExportModalOverlay"),a=t?.querySelector(".rel-export-modal"),o=document.getElementById("relExportForm"),r=document.getElementById("relExportType");if(!t||!a||!o||!r)return Promise.resolve(null);const s=Array.from(r.options).some(l=>l.value===e);r.value=s?e:"despesas_por_categoria";const n=o.querySelector('input[name="format"][value="pdf"]');n&&(n.checked=!0);const c=document.activeElement;return new Promise(l=>{let u=!1;const v=()=>{o.removeEventListener("submit",m),t.removeEventListener("click",y),document.removeEventListener("keydown",$)},d=(f=null)=>{u||(u=!0,v(),t.classList.remove("is-open"),document.body.classList.remove("rel-export-modal-open"),setTimeout(()=>{t.hidden=!0,c&&typeof c.focus=="function"&&c.focus()},140),l(f))};function m(f){f.preventDefault(),d({type:r.value,format:o.elements.format?.value||"pdf"})}function y(f){(f.target===t||f.target.closest("[data-rel-export-close]"))&&(f.preventDefault(),d(null))}function $(f){if(f.key==="Escape"){f.preventDefault(),d(null);return}if(f.key!=="Tab")return;const T=vt(a);if(T.length===0)return;const w=T[0],S=T[T.length-1];f.shiftKey&&document.activeElement===w?(f.preventDefault(),S.focus()):!f.shiftKey&&document.activeElement===S&&(f.preventDefault(),w.focus())}o.addEventListener("submit",m),t.addEventListener("click",y),document.addEventListener("keydown",$),t.hidden=!1,document.body.classList.add("rel-export-modal-open"),requestAnimationFrame(()=>{t.classList.add("is-open"),window.lucide?.createIcons?.(),r.focus()})})}function gt({getReportType:e,showRestrictionAlert:t,handleRestrictedAccess:a}){return async function(){if(!window.IS_PRO)return t("Exportacao de relatorios e exclusiva do plano PRO.");const r=e()||"despesas_por_categoria",s=await ht(r);if(!s)return;const n=document.getElementById("exportBtn"),c=n?n.innerHTML:"";n&&(n.disabled=!0,n.innerHTML=`
                <div class="spinner" style="width: 1rem; height: 1rem; border-width: 2px;"></div>
                <span>Exportando...</span>
            `);try{const l=s.type,u=s.format,v=new URLSearchParams({type:l,format:u,year:i.currentMonth.split("-")[0],month:i.currentMonth.split("-")[1]});i.currentAccount&&v.set("account_id",i.currentAccount);const d=await xe(`${Ve()}?${v.toString()}`,{method:"GET"},{responseType:"response"}),m=await d.blob(),y=d.headers.get("Content-Disposition"),$=C.extractFilename(y)||(u==="excel"?"relatorio.xlsx":"relatorio.pdf"),f=URL.createObjectURL(m),T=document.createElement("a");T.href=f,T.download=$,document.body.appendChild(T),T.click(),T.remove(),URL.revokeObjectURL(f),ve("success","Relatorio exportado",$)}catch(l){if(await a(l))return;console.error("Export error:",l);const u=ge(l,"Erro ao exportar relatorio. Tente novamente.");ve("error","Erro ao exportar",u)}finally{n&&(n.disabled=!1,n.innerHTML=c)}}}const we=e=>C.formatMonthLabel(e),U=e=>C.isYearlyView(e),ft=()=>C.getReportType(),Ee=()=>C.getActiveCategoryType();function X(e=i.currentAccount){return e?i.accounts.find(t=>String(t.id)===String(e))?.name||`Conta #${e}`:null}function ee(){return U()?`Ano ${i.currentMonth.split("-")[0]}`:we(i.currentMonth)}function he(){const e=Ee();return(z[i.currentView]||[]).find(a=>a.value===e)?.label||null}function bt(e=i.activeSection){return e==="relatorios"||e==="comparativos"}function yt(e=i.activeSection){return de[e]||de.overview}function te(e=i.currentView){return D[e]||D[p.VIEWS.CATEGORY]}function Ce(){try{localStorage.setItem(R.ACTIVE_VIEW,i.currentView),localStorage.setItem(R.CATEGORY_TYPE,i.categoryType),localStorage.setItem(R.ANNUAL_CATEGORY_TYPE,i.annualCategoryType)}catch{}}function se(){location.href=`${p.BASE_URL}billing`}async function Se(e){const t=e||q;window.PlanLimits?.promptUpgrade?await window.PlanLimits.promptUpgrade({context:"relatorios",message:t}):window.LKFeedback?.upgradePrompt?await window.LKFeedback.upgradePrompt({context:"relatorios",message:t}):window.Swal?.fire?(await Swal.fire({title:"Recurso exclusivo",text:t,icon:"info",showCancelButton:!0,confirmButtonText:"Assinar plano Pro",cancelButtonText:"Agora não",reverseButtons:!0,focusConfirm:!0})).isConfirmed&&se():confirm(`${t}

Deseja ir para a página de planos agora?`)&&se()}async function P(e){if(!e)return!1;const t=Number(e.status||e?.data?.status||0);if(t===401){const a=encodeURIComponent(location.pathname+location.search);return location.href=`${p.BASE_URL}login?return=${a}`,!0}if(t===403){let a=q;if(e?.data?.message)a=e.data.message;else if(typeof e?.clone=="function")try{const o=await e.clone().json();o?.message&&(a=o.message)}catch{}return i.accessRestricted||(i.accessRestricted=!0,await Se(a)),E.showPaywall(a),!0}return!1}function wt(e){typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"error",title:e,showConfirmButton:!1,timer:4e3,timerProgressBar:!0})}const j={async fetchReportData(){i.lastReportError=null;const e=new AbortController,t=setTimeout(()=>e.abort(),p.FETCH_TIMEOUT);try{const a=await k(le(),{type:C.getReportType(),year:i.currentMonth.split("-")[0],month:i.currentMonth.split("-")[1],account_id:i.currentAccount||void 0});return clearTimeout(t),i.accessRestricted=!1,i.lastReportError=null,a.data||a}catch(a){return clearTimeout(t),await P(a)||(i.lastReportError=a.name==="AbortError"?"A requisição demorou demais. Tente novamente em instantes.":"Não foi possível carregar o relatório agora. Verifique a conexão e tente novamente.",console.error("Error fetching report data:",a),wt(ge(a,"Erro ao carregar relatório. Verifique sua conexão."))),null}},async fetchReportDataForType(e,t={}){const a=new URLSearchParams({type:e,year:i.currentMonth.split("-")[0],month:i.currentMonth.split("-")[1]}),o=Object.prototype.hasOwnProperty.call(t,"accountId")?t.accountId:i.currentAccount;o&&a.set("account_id",o);try{const r=await k(le(),Object.fromEntries(a.entries()));return r.data||r}catch{return null}},async fetchAccounts(){try{const e=await k(Ne());i.accessRestricted=!1;const t=e.data||e.items||e||[];return(Array.isArray(t)?t:[]).map(a=>({id:Number(a.id),name:a.nome||a.apelido||a.instituicao||`Conta #${a.id}`}))}catch(e){return await P(e)?[]:(console.error("Error fetching accounts:",e),[])}},async fetchSummaryStats(){const[e,t]=i.currentMonth.split("-"),a=new AbortController,o=setTimeout(()=>a.abort(),p.FETCH_TIMEOUT);try{const r=await k(Fe(),{year:e,month:t});return clearTimeout(o),r.data||r}catch(r){return clearTimeout(o),await P(r)?{totalReceitas:0,totalDespesas:0,saldo:0,totalCartoes:0}:(console.error("Error fetching summary stats:",r),{totalReceitas:0,totalDespesas:0,saldo:0,totalCartoes:0})}},async fetchInsights(){const[e,t]=i.currentMonth.split("-"),a=new AbortController,o=setTimeout(()=>a.abort(),p.FETCH_TIMEOUT);try{const r=await k(Ue(),{year:e,month:t});return clearTimeout(o),r.data||r}catch(r){return clearTimeout(o),await P(r)?{insights:[]}:(console.error("Error fetching insights:",r),{insights:[]})}},async fetchInsightsTeaser(){const[e,t]=i.currentMonth.split("-"),a=new AbortController,o=setTimeout(()=>a.abort(),p.FETCH_TIMEOUT);try{const r=await k(De(),{year:e,month:t});return clearTimeout(o),r.data||r}catch(r){return clearTimeout(o),console.error("Error fetching insights teaser:",r),{insights:[],totalCount:0,isTeaser:!0}}},async fetchComparatives(){const[e,t]=i.currentMonth.split("-"),a=new URLSearchParams({year:e,month:t});i.currentAccount&&a.set("account_id",i.currentAccount);const o=new AbortController,r=setTimeout(()=>o.abort(),p.FETCH_TIMEOUT);try{const s=await k(Be(),Object.fromEntries(a.entries()));return clearTimeout(r),s.data||s}catch(s){return clearTimeout(r),await P(s)||console.error("Error fetching comparatives:",s),null}}};L.API=j;const E={setContent(e){const t=document.getElementById("reportArea");t&&(t.innerHTML=e,t.setAttribute("aria-busy","false"),window.lucide&&lucide.createIcons())},showLoading(){const e=document.getElementById("reportArea");e&&(e.setAttribute("aria-busy","true"),e.innerHTML=`
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Carregando relatório...</p>
                </div>
            `,window.lucide&&lucide.createIcons())},showEmptyState(){const e=X(),t=te(),a=ee(),o=e?`Nenhum dado foi encontrado para ${e} em ${a}.`:`Não há lançamentos suficientes para montar este recorte em ${a}.`;E.setContent(`
            <div class="empty-state report-empty-state">
                <i data-lucide="pie-chart"></i>
                <h3>${h(t.title)}</h3>
                <p>${h(o)}</p>
                <div class="report-state-actions">
                    <a href="${p.BASE_URL}lancamentos" class="empty-cta">
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
        `)},showErrorState(e){const t=h(e||"Não foi possível carregar este relatório.");E.setContent(`
            <div class="error-state report-error-state">
                <i data-lucide="triangle-alert"></i>
                <p class="error-message">${t}</p>
                <div class="report-state-actions">
                    <button type="button" class="btn btn-primary btn-retry" data-action="retry-report">
                        <i data-lucide="refresh-cw"></i>
                        <span>Tentar novamente</span>
                    </button>
                    ${i.currentAccount?`
                        <button type="button" class="btn btn-secondary" data-action="clear-report-account">
                            <i data-lucide="layers"></i>
                            <span>Voltar para todas as contas</span>
                        </button>
                    `:""}
                </div>
            </div>
        `)},showPaywall(e=q){const t=document.getElementById("reportArea");if(!t)return;const a=h(e||q);t.setAttribute("aria-busy","false"),t.innerHTML=`
            <div class="paywall-message" role="alert">
                <i data-lucide="crown" aria-hidden="true"></i>
                <h3>Recurso Premium</h3>
                <p>${a}</p>
                <button type="button" class="btn-upgrade surface-button surface-button--upgrade surface-button--lg" data-action="go-pro">
                    Fazer Upgrade para PRO
                </button>
            </div>
        `,window.lucide&&lucide.createIcons();const o=t.querySelector('[data-action="go-pro"]');o&&o.addEventListener("click",se)},updateMonthLabel(){const e=document.getElementById("monthLabel");e&&(e.textContent=U()?i.currentMonth.split("-")[0]:we(i.currentMonth))},updatePageContext(){const e=document.getElementById("reportsContextKicker"),t=document.getElementById("reportsContextTitle"),a=document.getElementById("reportsContextDescription"),o=document.getElementById("reportsContextChips"),r=document.getElementById("reportsContextActions");if(!e||!t||!a||!o||!r)return;const s=yt(),n=te(),c=ee(),l=X(),u=bt(),v=he(),d=!window.IS_PRO&&i.activeSection==="insights";e.textContent=s.kicker,t.textContent=i.activeSection==="relatorios"?n.title:s.title,a.textContent=i.activeSection==="relatorios"?n.description:s.description;const m=[`<span class="context-chip surface-chip"><i data-lucide="calendar-range"></i><span>${h(c)}</span></span>`];i.activeSection==="relatorios"&&v&&m.push(`<span class="context-chip surface-chip surface-chip--highlight context-chip-highlight"><i data-lucide="filter"></i><span>${h(v)}</span></span>`),l&&u?m.push(`<span class="context-chip surface-chip surface-chip--highlight context-chip-highlight"><i data-lucide="landmark"></i><span>${h(l)}</span></span>`):l&&!u?m.push(`<span class="context-chip surface-chip"><i data-lucide="bookmark"></i><span>Filtro salvo: ${h(l)}</span></span>`):m.push('<span class="context-chip surface-chip"><i data-lucide="layers"></i><span>Consolidado</span></span>'),d&&m.push('<span class="context-chip surface-chip surface-chip--pro context-chip-pro"><i data-lucide="crown"></i><span>Preview PRO</span></span>'),o.innerHTML=m.join(""),r.innerHTML=l?`
            <button type="button" class="context-action-btn surface-button surface-button--subtle" data-action="clear-report-account">
                <i data-lucide="eraser"></i>
                <span>Limpar filtro de conta</span>
            </button>
        `:"",window.lucide&&lucide.createIcons()},updateReportFilterSummary(){const e=document.getElementById("reportFilterSummary"),t=document.getElementById("reportScopeNote");if(!e||!t)return;const a=[`<span class="report-filter-chip surface-chip"><i data-lucide="calendar-range"></i><span>${h(ee())}</span></span>`,`<span class="report-filter-chip surface-chip"><i data-lucide="bar-chart-3"></i><span>${h(te().title)}</span></span>`],o=he();o&&a.push(`<span class="report-filter-chip surface-chip"><i data-lucide="filter"></i><span>${h(o)}</span></span>`),i.currentAccount?a.push(`<span class="report-filter-chip surface-chip surface-chip--highlight report-filter-chip-highlight"><i data-lucide="landmark"></i><span>${h(X())}</span></span>`):a.push('<span class="report-filter-chip surface-chip"><i data-lucide="layers"></i><span>Todas as contas</span></span>'),e.innerHTML=a.join(""),t.classList.remove("hidden"),t.innerHTML=i.currentAccount?'<i data-lucide="info"></i><span>O resumo do topo continua consolidado. O filtro por conta afeta este gráfico e a aba Comparativos.</span>':'<i data-lucide="info"></i><span>Use o filtro de conta para analisar um recorte específico sem perder o consolidado do topo.</span>',E.syncControlsLayoutState(),window.lucide&&lucide.createIcons()},syncControlsLayoutState(){const e=document.getElementById("relControlsRow");if(!e)return;const t=document.getElementById("typeSelectWrapper"),a=document.getElementById("accountSelectWrapper"),o=document.getElementById("clearFiltersWrapper"),r=document.getElementById("reportFilterSummary"),s=u=>!u||u.classList.contains("hidden")||u.style.display==="none"?!1:window.getComputedStyle(u).display!=="none",n=[t,a].filter(s).length,c=s(o),l=n<=1?"sparse":"full";e.dataset.layoutDensity=l,e.dataset.controlCount=String(n),e.dataset.view=i.currentView,e.dataset.hasClear=c?"true":"false",r&&(r.dataset.layoutDensity=l,r.dataset.view=i.currentView)},updateControls(){const e=document.getElementById("typeSelectWrapper"),t=[p.VIEWS.CATEGORY,p.VIEWS.ANNUAL_CATEGORY].includes(i.currentView);e&&(e.classList.toggle("hidden",!t),t&&E.syncTypeSelect());const a=document.getElementById("accountSelectWrapper");a&&a.classList.remove("hidden"),E.syncControlsLayoutState()},syncTypeSelect(){const e=document.getElementById("reportType");if(!e)return;const t=z[i.currentView];if(!t)return;(e.options.length!==t.length||t.some((o,r)=>e.options[r]?.value!==o.value))&&(e.innerHTML=t.map(o=>`<option value="${o.value}">${o.label}</option>`).join("")),e.value=Ee()},setActiveTab(e){document.querySelectorAll(".tab-btn").forEach(t=>{const a=t.dataset.view===e;t.classList.toggle("active",a),t.setAttribute("aria-selected",a)})}};L.UI=E;const Et=()=>j.fetchReportData(),Ct=()=>E.showLoading(),ae=()=>E.showEmptyState(),St=e=>E.showErrorState(e),At=()=>E.updateMonthLabel(),N=()=>E.updatePageContext(),V=()=>E.updateReportFilterSummary(),$t=()=>E.updateControls(),Tt=e=>E.setActiveTab(e),It=e=>b.renderPie(e),xt=e=>b.renderLine(e),_t=e=>b.renderBar(e);async function x(){N(),V(),Ct(),Lt();const e=await Et();if(!i.accessRestricted){if(i.lastReportError)return St(i.lastReportError);if(i.currentView===p.VIEWS.CARDS){if(!e||!Array.isArray(e.cards))return ae();it(e);return}if(!e||!e.labels||e.labels.length===0)return ae();switch(i.currentView){case p.VIEWS.CATEGORY:case p.VIEWS.ANNUAL_CATEGORY:It(e);break;case p.VIEWS.BALANCE:case p.VIEWS.EVOLUTION:xt(e);break;case p.VIEWS.COMPARISON:case p.VIEWS.ACCOUNTS:case p.VIEWS.ANNUAL_SUMMARY:_t(e);break;default:ae()}et(e)}}const{updateSummaryCards:Lt,updateInsightsSection:Mt,updateOverviewSection:Rt,updateComparativesSection:kt}=mt({API:j}),Ot=gt({getReportType:ft,showRestrictionAlert:Se,handleRestrictedAccess:P});async function Pt(e){e==="overview"?await Rt():e==="relatorios"?await x():e==="insights"?await Mt():e==="comparativos"&&await kt()}function Ae(){const e=U();if(window.LukratoHeader?.setPickerMode?.(e?"year":"month"),e){const t=window.LukratoHeader?.getYear?.();if(t){const[,a="01"]=i.currentMonth.split("-"),o=String(a).padStart(2,"0");i.currentMonth=`${t}-${o}`}}}function $e(e){i.currentView=e,Tt(e),$t(),N(),V(),Ae(),Ce(),x()}function Te(e){i.currentView===p.VIEWS.ANNUAL_CATEGORY?i.annualCategoryType=e:i.categoryType=e,N(),V(),Ce(),x()}function re(e){i.currentAccount=e||null,N(),V(),x()}function Nt(e){!e?.detail?.month||U()||i.currentMonth!==e.detail.month&&(i.currentMonth=e.detail.month,At(),N(),V(),x())}function Vt(e){if(!U()||!e?.detail?.year)return;const[,t="01"]=i.currentMonth.split("-"),a=String(t).padStart(2,"0"),o=`${e.detail.year}-${a}`;i.currentMonth!==o&&(i.currentMonth=o,N(),V(),x())}function oe(){return window.IS_PRO=Oe().isPro===!0,window.IS_PRO}ke(()=>{oe()});function Bt(){try{const e=localStorage.getItem(R.ACTIVE_VIEW);e&&Object.values(p.VIEWS).includes(e)&&(i.currentView=e);const t=localStorage.getItem(R.CATEGORY_TYPE);t&&z[p.VIEWS.CATEGORY]?.some(o=>o.value===t)&&(i.categoryType=t);const a=localStorage.getItem(R.ANNUAL_CATEGORY_TYPE);a&&z[p.VIEWS.ANNUAL_CATEGORY]?.some(o=>o.value===a)&&(i.annualCategoryType=a)}catch{}}function Dt(){const e=o=>{i.activeSection=o,document.querySelectorAll(".rel-section-tab").forEach(n=>{n.classList.remove("active"),n.setAttribute("aria-selected","false")}),document.querySelectorAll(".rel-section-panel").forEach(n=>{n.classList.remove("active")});const r=document.querySelector(`.rel-section-tab[data-section="${o}"]`);r&&(r.classList.add("active"),r.setAttribute("aria-selected","true"));const s=document.getElementById(`section-${o}`);s&&s.classList.add("active"),localStorage.setItem(R.ACTIVE_SECTION,o),E.updatePageContext(),Pt(o),window.lucide?.createIcons?.()},t=["comparativos"];document.querySelectorAll(".rel-section-tab").forEach(o=>{o.addEventListener("click",()=>{const r=o.dataset.section;if(!window.IS_PRO&&t.includes(r)){window.PlanLimits?.promptUpgrade?window.PlanLimits.promptUpgrade({context:"relatorios",message:"Esta funcionalidade e exclusiva do plano Pro."}).catch(()=>{}):window.LKFeedback?.upgradePrompt?window.LKFeedback.upgradePrompt({context:"relatorios",message:"Esta funcionalidade e exclusiva do plano Pro."}).catch(()=>{}):Swal.fire({icon:"info",title:"Recurso Premium",html:"Esta funcionalidade e exclusiva do <b>plano Pro</b>.<br>Faca upgrade para desbloquear!",confirmButtonText:'<i class="lucide-crown" style="margin-right:6px"></i> Fazer Upgrade',showCancelButton:!0,cancelButtonText:"Agora nao",confirmButtonColor:"#f59e0b",cancelButtonColor:"#64748b"}).then(s=>{s.isConfirmed&&(window.location.href=`${p.BASE_URL}billing`)});return}e(r)})});const a=localStorage.getItem(R.ACTIVE_SECTION);if(a&&document.getElementById(`section-${a}`)){!window.IS_PRO&&t.includes(a)?e("overview"):e(a);return}e("overview")}function Ut(e,t){const a=document.getElementById("clearFiltersWrapper"),o=document.getElementById("btnLimparFiltrosRel"),r=()=>{if(!a)return;const s=e&&e.selectedIndex>0,n=t&&t.value!=="";a.style.display=s||n?"flex":"none",E.syncControlsLayoutState()};return e?.addEventListener("change",r),t?.addEventListener("change",r),o?.addEventListener("click",()=>{e&&(e.selectedIndex=0,Te(e.value)),t&&(t.value="",re("")),r()}),r(),r}function Ft(e,t){document.addEventListener("click",a=>{if(a.target.closest('[data-action="retry-report"]')){a.preventDefault(),x();return}if(a.target.closest('[data-action="clear-report-account"]')){a.preventDefault(),e&&(e.value=""),re(""),typeof t=="function"&&t();return}const s=a.target.closest('[data-action="open-card-detail"]');if(!s)return;a.stopPropagation();const n=Number.parseInt(String(s.dataset.cardId||""),10),c=s.dataset.cardMonth||i.currentMonth,l=/^\d{4}-\d{2}$/.test(c)?c:i.currentMonth;if(!Number.isInteger(n)||n<=0)return;const u=new URLSearchParams({mes:l,origem:"relatorios"});window.location.href=`${p.BASE_URL}cartoes/${n}?${u.toString()}`})}function Wt(){window.ReportsAPI={setMonth:e=>{/^\d{4}-\d{2}$/.test(e)&&(i.currentMonth=e,E.updateMonthLabel(),x())},setView:e=>{Object.values(p.VIEWS).includes(e)&&$e(e)},refresh:()=>x(),getState:()=>({...i})}}async function Ht(){oe(),Je(),b.setupDefaults(),i.accounts=await j.fetchAccounts();const e=document.getElementById("accountFilter");e&&i.accounts.forEach(r=>{const s=document.createElement("option");s.value=r.id,s.textContent=r.name,e.appendChild(s)}),Bt(),document.querySelectorAll(".tab-btn").forEach(r=>{r.addEventListener("click",()=>$e(r.dataset.view))}),E.setActiveTab(i.currentView),E.updateControls(),E.updatePageContext(),Dt();const t=document.getElementById("reportType");t?.addEventListener("change",r=>Te(r.target.value)),e?.addEventListener("change",r=>re(r.target.value));const a=Ut(t,e);document.addEventListener("lukrato:theme-changed",()=>{b.setupDefaults(),x()});const o=window.LukratoHeader?.getMonth?.();o&&(i.currentMonth=o),document.addEventListener("lukrato:month-changed",Nt),document.addEventListener("lukrato:year-changed",Vt),document.getElementById("exportBtn")?.addEventListener("click",Ot),Ft(e,a),Ae(),E.updateMonthLabel(),E.updateControls(),await x()}function Yt(){if(window.__LK_REPORTS_LOADED__)return;window.__LK_REPORTS_LOADED__=!0;const e=async()=>{await Pe({},{silent:!0}),oe(),await Ht()};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",()=>{e()}):e(),Wt()}Yt();
