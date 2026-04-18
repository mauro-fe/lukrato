import{m as xe,b as _e,e as me,k as R}from"./api-Bz3e_1Ao.js";import{a as Ae}from"./utils-Bj4jxwhy.js";import{c as $e,p as Te,f as Ie}from"./ui-preferences-CPWe3C-7.js";import{o as Me,g as Le,e as Re}from"./runtime-config-Dd8wBDd8.js";import{r as Oe}from"./finance-CgaDv1sH.js";import{r as ke,a as Pe,b as Be,c as Ne,d as De,e as ie}from"./reports-CXrVZnrN.js";const Y="Relatórios são exclusivos do plano Pro.",u={BASE_URL:xe(),CHART_COLORS:["#E67E22","#2C3E50","#2ECC71","#F39C12","#9B59B6","#1ABC9C","#E74C3C","#3498DB"],FETCH_TIMEOUT:3e4,VIEWS:{CATEGORY:"category",BALANCE:"balance",COMPARISON:"comparison",ACCOUNTS:"accounts",CARDS:"cards",EVOLUTION:"evolution",ANNUAL_SUMMARY:"annual_summary",ANNUAL_CATEGORY:"annual_category"}},Ve=new Set([u.VIEWS.ANNUAL_SUMMARY,u.VIEWS.ANNUAL_CATEGORY]),z={[u.VIEWS.CATEGORY]:[{value:"despesas_por_categoria",label:"Despesas por categoria"},{value:"receitas_por_categoria",label:"Receitas por categoria"}],[u.VIEWS.ANNUAL_CATEGORY]:[{value:"despesas_anuais_por_categoria",label:"Despesas anuais por categoria"},{value:"receitas_anuais_por_categoria",label:"Receitas anuais por categoria"}]},L={ACTIVE_SECTION:"rel_active_section",ACTIVE_VIEW:"rel_active_view",CATEGORY_TYPE:"rel_category_type",ANNUAL_CATEGORY_TYPE:"rel_annual_category_type"},ce={overview:{kicker:"Painel consolidado",title:"Leia seu mes com contexto",description:"Veja seu pulso financeiro, identifique sinais importantes e acompanhe a evolucao do periodo em um resumo rapido."},relatorios:{kicker:"Relatorio ativo",title:"Transforme lancamentos em decisao",description:"Explore seus numeros por categoria, conta, cartao e evolucao para descobrir onde agir."},insights:{kicker:"Leitura automatica",title:"Insights que ajudam a agir",description:"Receba sinais claros sobre gastos, saldo, concentracoes e oportunidades sem precisar interpretar tudo manualmente."},comparativos:{kicker:"Comparacao temporal",title:"Compare e ajuste sua rota",description:"Entenda o que melhorou, piorou ou estagnou em relacao ao mes e ao ano anteriores."}},le={[u.VIEWS.CATEGORY]:{title:"Categorias do periodo",description:"Encontre rapidamente onde seu dinheiro esta concentrado por categoria."},[u.VIEWS.BALANCE]:{title:"Saldo diario",description:"Acompanhe como seu caixa evolui ao longo do periodo."},[u.VIEWS.COMPARISON]:{title:"Receitas x despesas",description:"Compare entradas e saidas para entender pressao ou folga no caixa."},[u.VIEWS.ACCOUNTS]:{title:"Desempenho por conta",description:"Descubra quais contas concentram mais entradas e saidas."},[u.VIEWS.CARDS]:{title:"Saude dos cartoes",description:"Monitore faturas, uso de limite e sinais de atencao nos cartoes."},[u.VIEWS.EVOLUTION]:{title:"Evolucao em 12 meses",description:"Observe tendencia, sazonalidade e ritmo financeiro ao longo do ultimo ano."},[u.VIEWS.ANNUAL_SUMMARY]:{title:"Resumo anual",description:"Compare mes a mes como receitas, despesas e saldo se comportaram no ano."},[u.VIEWS.ANNUAL_CATEGORY]:{title:"Categorias do ano",description:"Veja quais categorias dominaram seu ano e onde houve maior concentracao."}};function he(){const e=new Date;return`${e.getFullYear()}-${String(e.getMonth()+1).padStart(2,"0")}`}const g=e=>String(e??"").replace(/[&<>"']/g,function(t){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[t]??t});function Fe(e,t="#cccccc"){return/^#[0-9A-Fa-f]{6}$/.test(e)?e:t}const i={activeSection:"overview",currentView:u.VIEWS.CATEGORY,categoryType:"despesas_por_categoria",annualCategoryType:"despesas_anuais_por_categoria",currentMonth:he(),currentAccount:null,chart:null,accounts:[],accessRestricted:!1,lastReportError:null,activeDrilldown:null,reportDetails:null},E={getCurrentMonth:he,formatCurrency(e){return Ae(e)},formatMonthLabel(e){const[t,a]=e.split("-");return new Date(t,a-1).toLocaleDateString("pt-BR",{month:"long",year:"numeric"})},addMonths(e,t){const[a,s]=e.split("-").map(Number),o=new Date(a,s-1+t);return`${o.getFullYear()}-${String(o.getMonth()+1).padStart(2,"0")}`},hexToRgba(e,t=.25){const a=parseInt(e.slice(1,3),16),s=parseInt(e.slice(3,5),16),o=parseInt(e.slice(5,7),16);return`rgba(${a}, ${s}, ${o}, ${t})`},generateShades(e,t){const a=parseInt(e.slice(1,3),16),s=parseInt(e.slice(3,5),16),o=parseInt(e.slice(5,7),16),r=[];for(let n=0;n<t;n++){const l=.35-n/Math.max(t-1,1)*.7,c=h=>Math.min(255,Math.max(0,Math.round(h+(l>0?(255-h)*l:h*l)))),d=c(a),m=c(s),p=c(o);r.push(`#${d.toString(16).padStart(2,"0")}${m.toString(16).padStart(2,"0")}${p.toString(16).padStart(2,"0")}`)}return r},isYearlyView(e=i.currentView){return Ve.has(e)},extractFilename(e){if(!e)return null;const t=/filename\*=UTF-8''([^;]+)/i.exec(e);if(t)try{return decodeURIComponent(t[1])}catch{return t[1]}const a=/filename="?([^";]+)"?/i.exec(e);return a?a[1]:null},getCssVar(e,t=""){try{return(getComputedStyle(document.documentElement).getPropertyValue(e)||"").trim()||t}catch{return t}},isLightTheme(){try{return(document.documentElement?.getAttribute("data-theme")||"dark")==="light"}catch{return!1}},getReportType(){return{[u.VIEWS.CATEGORY]:i.categoryType,[u.VIEWS.ANNUAL_CATEGORY]:i.annualCategoryType,[u.VIEWS.BALANCE]:"saldo_mensal",[u.VIEWS.COMPARISON]:"receitas_despesas_diario",[u.VIEWS.ACCOUNTS]:"receitas_despesas_por_conta",[u.VIEWS.CARDS]:"cartoes_credito",[u.VIEWS.EVOLUTION]:"evolucao_12m",[u.VIEWS.ANNUAL_SUMMARY]:"resumo_anual"}[i.currentView]??i.categoryType},getActiveCategoryType(){return i.currentView===u.VIEWS.ANNUAL_CATEGORY?i.annualCategoryType:i.categoryType}},I={},T=e=>E.formatCurrency(e),U=e=>String(e??"").replace(/[&<>"']/g,t=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"})[t]||t);function H(){const e=(document.documentElement.getAttribute("data-theme")||"").toLowerCase()==="light"||E.isLightTheme?.();return{isLight:e,mode:e?"light":"dark",textColor:e?"#2c3e50":"#ffffff",textMuted:e?"#6c757d":"rgba(255, 255, 255, 0.7)",gridColor:e?"rgba(0, 0, 0, 0.08)":"rgba(255, 255, 255, 0.05)",surfaceColor:getComputedStyle(document.documentElement).getPropertyValue("--color-surface").trim()}}function Q(e=[]){return e.map(t=>{const a=Number(t);return Number.isFinite(a)?a:0})}function de(e,t=380){const a=e?.closest(".chart-container")||e?.parentElement,s=a?getComputedStyle(a):null,o=s?Number.parseFloat(s.height):Number.NaN,r=s?Number.parseFloat(s.minHeight):Number.NaN,n=a?.getBoundingClientRect?.().height??Number.NaN,l=s?(Number.parseFloat(s.paddingTop)||0)+(Number.parseFloat(s.paddingBottom)||0):0,c=window.innerWidth<768?320:t,d=[o,n,r,c].find(m=>Number.isFinite(m)&&m>0)??c;return Math.max(260,Math.round(d-l))}const v={_currentEntries:null,destroy(){i.chart&&(Array.isArray(i.chart)?i.chart.forEach(e=>e?.destroy()):i.chart.destroy(),i.chart=null),v._drilldownChart&&(v._drilldownChart.destroy(),v._drilldownChart=null),i.activeDrilldown=null,i.reportDetails=null},setupDefaults(){const e=getComputedStyle(document.documentElement).getPropertyValue("--color-text").trim();window.Apex=window.Apex||{},window.Apex.chart={foreColor:e,fontFamily:"Inter, Arial, sans-serif"},window.Apex.grid={borderColor:"rgba(255, 255, 255, 0.1)"}},renderPie(e){const{labels:t=[],values:a=[],details:s=null,cat_ids:o=null}=e;if(!t.length||!a.some(b=>b>0))return I.UI.showEmptyState();let r=t.map((b,S)=>({label:b,value:Number(a[S])||0,color:u.CHART_COLORS[S%u.CHART_COLORS.length],catId:o?o[S]??null:null})).filter(b=>b.value>0).sort((b,S)=>S.value-b.value);!o&&s&&(r=r.map(b=>{const S=s.find(O=>O.label===b.label);return{...b,catId:S?.cat_id??null}}));const n=window.innerWidth<768;let l=r;if(n&&r.length>5){const b=r.slice(0,5),O=r.slice(5).reduce((V,j)=>V+j.value,0);l=[...b,{label:"Outros",value:O,color:"#95a5a6",isOthers:!0}]}const c=!n&&l.length>2,d=c?Math.ceil(l.length/2):l.length,m=c?[l.slice(0,d),l.slice(d)].filter(b=>b.length):[l],p=`
            <div class="chart-container chart-container-pie">
                <div class="chart-dual">
                    ${m.map((b,S)=>`
                        <div class="chart-wrapper chart-wrapper-pie">
                            <div id="chart${S}"></div>
                        </div>
                    `).join("")}
                </div>
            </div>
            <div id="subcategoryDrilldown" class="drilldown-panel" aria-hidden="true"></div>
            ${n?'<div id="categoryListMobile" class="category-list-mobile"></div>':""}
        `;I.UI.setContent(p),v.destroy(),i.reportDetails=s,i.activeDrilldown=null,v._currentEntries=l;const h=E.getActiveCategoryType(),x={receitas_por_categoria:"Receitas por Categoria",despesas_por_categoria:"Despesas por Categoria",receitas_anuais_por_categoria:"Receitas anuais por Categoria",despesas_anuais_por_categoria:"Despesas anuais por Categoria"}[h]||"Distribuição por Categoria",$=H();let M=0;i.chart=m.map((b,S)=>{const O=document.getElementById(`chart${S}`);if(!O)return null;const V=b.reduce((C,F)=>C+F.value,0),j=M;M+=b.length;const oe=new ApexCharts(O,{chart:{type:"donut",height:"100%",background:"transparent",fontFamily:"Inter, Arial, sans-serif",events:{dataPointSelection:(C,F,re)=>{const ne=j+re.dataPointIndex,K=l[ne];!K||K.isOthers||v.handlePieClick(K,ne,re.dataPointIndex,S)},dataPointMouseEnter:C=>{C.target&&(C.target.style.cursor="pointer")},dataPointMouseLeave:C=>{C.target&&(C.target.style.cursor="default")}}},series:b.map(C=>C.value),labels:b.map(C=>C.label),colors:b.map(C=>C.color),stroke:{width:2,colors:[$.surfaceColor]},plotOptions:{pie:{donut:{size:"60%"},expandOnClick:!0}},legend:{show:!n,position:"bottom",labels:{colors:$.textColor},markers:{shape:"circle"}},title:{text:m.length>1?`${x} - Parte ${S+1}`:x,align:"center",style:{fontSize:"14px",fontWeight:"bold",color:$.textColor}},tooltip:{theme:$.mode,y:{formatter:C=>{const F=V>0?(C/V*100).toFixed(1):"0";return`${T(C)} (${F}%)`}}},dataLabels:{enabled:!1},theme:{mode:$.mode}});return oe.render(),oe}),n&&v.renderMobileCategoryList(l)},renderMobileCategoryList(e){const t=document.getElementById("categoryListMobile");if(!t)return;const a=e.reduce((r,n)=>r+n.value,0),s=!!i.reportDetails&&window.IS_PRO,o=e.map((r,n)=>{const l=(r.value/a*100).toFixed(1),c=s&&r.catId!=null?i.reportDetails.find(h=>h.cat_id===r.catId):null,d=c&&c.subcategories&&c.subcategories.filter(h=>h.id!==0).length>0,m=d?'<i data-lucide="chevron-down" class="category-chevron"></i>':"";let p="";if(d){const h=E.generateShades(r.color,c.subcategories.length);p=`
                    <div class="category-subcats-panel" id="mobileSubcatPanel-${n}" aria-hidden="true">
                        ${c.subcategories.map((y,x)=>{const $=c.total>0?(y.total/c.total*100).toFixed(1):"0.0";return`
                                <div class="drilldown-item drilldown-item-mobile">
                                    <div class="drilldown-indicator" style="background-color: ${h[x]}"></div>
                                    <div class="drilldown-info">
                                        <span class="drilldown-name">${U(y.label)}</span>
                                    </div>
                                    <div class="drilldown-values">
                                        <span class="drilldown-value">${T(y.total)}</span>
                                        <span class="drilldown-pct">${$}%</span>
                                    </div>
                                </div>
                            `}).join("")}
                    </div>
                `}return`
                <div class="category-item ${d?"has-subcats":""}"
                     ${d?`data-subcat-toggle="${n}"`:""}>
                    <div class="category-indicator" style="background-color: ${r.color}"></div>
                    <div class="category-info">
                        <span class="category-name">${U(r.label)}</span>
                        <span class="category-value">${T(r.value)}</span>
                    </div>
                    <span class="category-percentage">${l}%</span>
                    ${m}
                </div>
                ${p}
            `}).join("");t.innerHTML=`
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
        `,window.lucide&&lucide.createIcons(),v.setupExpandToggle(),s&&v.setupMobileSubcatToggles()},setupMobileSubcatToggles(){document.querySelectorAll("[data-subcat-toggle]").forEach(e=>{e.addEventListener("click",function(){const t=this.dataset.subcatToggle,a=document.getElementById(`mobileSubcatPanel-${t}`),s=this.querySelector(".category-chevron");if(!a)return;a.getAttribute("aria-hidden")==="false"?(a.style.maxHeight="0px",a.setAttribute("aria-hidden","true"),s&&(s.style.transform="rotate(0deg)")):(a.style.maxHeight=a.scrollHeight+"px",a.setAttribute("aria-hidden","false"),s&&(s.style.transform="rotate(180deg)"))})})},setupExpandToggle(){const e=document.getElementById("expandCategoriesBtn"),t=document.getElementById("expandableCard");!e||!t||e.addEventListener("click",function(){e.getAttribute("aria-expanded")==="true"?(t.style.maxHeight="0px",t.setAttribute("aria-hidden","true"),e.setAttribute("aria-expanded","false"),e.querySelector("span").textContent="Ver todas as categorias",e.querySelector("i").style.transform="rotate(0deg)"):(t.style.maxHeight=t.scrollHeight+"px",t.setAttribute("aria-hidden","false"),e.setAttribute("aria-expanded","true"),e.querySelector("span").textContent="Ocultar categorias",e.querySelector("i").style.transform="rotate(180deg)")})},handlePieClick(e,t,a,s){if(!window.IS_PRO){window.PlanLimits?.promptUpgrade?window.PlanLimits.promptUpgrade({context:"relatorios",message:"O detalhamento por subcategorias é exclusivo do plano Pro."}).catch(()=>{}):window.LKFeedback?.upgradePrompt?window.LKFeedback.upgradePrompt({context:"relatorios",message:"O detalhamento por subcategorias é exclusivo do plano Pro."}).catch(()=>{}):window.Swal?.fire&&Swal.fire({icon:"info",title:"Recurso Premium",html:"O detalhamento por <b>subcategorias</b> é exclusivo do <b>plano Pro</b>.<br>Faça upgrade para desbloquear!",confirmButtonText:"Fazer Upgrade",showCancelButton:!0,cancelButtonText:"Agora não",confirmButtonColor:"#f59e0b",cancelButtonColor:"#64748b"}).then(l=>{l.isConfirmed&&(window.location.href=(u.BASE_URL||"/")+"billing")});return}if(!i.reportDetails)return;const o=e.catId,r=i.reportDetails.find(l=>l.cat_id===o);if(!r||!r.subcategories||r.subcategories.length===0)return;if(r.subcategories.filter(l=>l.id!==0).length===0){window.Swal?.fire&&Swal.fire({icon:"info",title:"Sem subcategorias",text:"Atribua subcategorias aos seus lançamentos para ver o detalhamento desta categoria.",confirmButtonText:"Entendi",confirmButtonColor:"#f59e0b",timer:5e3,timerProgressBar:!0});return}if(i.activeDrilldown===o){v.closeDrilldown();return}i.activeDrilldown=o,v.renderSubcategoryDrilldown(r,e.color)},closeDrilldown(){i.activeDrilldown=null;const e=document.getElementById("subcategoryDrilldown");e&&(e.style.maxHeight="0px",e.setAttribute("aria-hidden","true"),setTimeout(()=>{e.innerHTML=""},400))},renderSubcategoryDrilldown(e,t){const a=document.getElementById("subcategoryDrilldown");if(!a)return;const{label:s,total:o,subcategories:r}=e,n=E.generateShades(t,r.length),l=r.map((m,p)=>{const h=o>0?(m.total/o*100).toFixed(1):"0.0",y=o>0?(m.total/o*100).toFixed(0):"0";return`
                <div class="drilldown-item" style="animation-delay: ${p*.05}s">
                    <div class="drilldown-indicator" style="background-color: ${n[p]}"></div>
                    <div class="drilldown-info">
                        <span class="drilldown-name">${U(m.label)}</span>
                        <div class="drilldown-bar-bg">
                            <div class="drilldown-bar" style="width: ${y}%; background-color: ${n[p]}"></div>
                        </div>
                    </div>
                    <div class="drilldown-values">
                        <span class="drilldown-value">${T(m.total)}</span>
                        <span class="drilldown-pct">${h}%</span>
                    </div>
                </div>
            `}).join(""),c=window.innerWidth<768,d=c?"":`
            <div class="drilldown-mini-chart">
                <div id="drilldownMiniChart"></div>
            </div>
        `;a.innerHTML=`
            <div class="drilldown-header" style="border-left-color: ${t}">
                <div class="drilldown-title">
                    <span class="drilldown-cat-indicator" style="background-color: ${t}"></span>
                    <h4>${U(s)}</h4>
                    <span class="drilldown-total">${T(o)}</span>
                </div>
                <button class="drilldown-close" id="drilldownCloseBtn" aria-label="Fechar detalhamento">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="drilldown-body">
                ${d}
                <div class="drilldown-list">
                    ${l}
                </div>
            </div>
        `,a.setAttribute("aria-hidden","false"),requestAnimationFrame(()=>{a.style.maxHeight=a.scrollHeight+"px"}),document.getElementById("drilldownCloseBtn")?.addEventListener("click",()=>{v.closeDrilldown()}),c||v._renderDrilldownMiniChart(r,n),window.lucide&&lucide.createIcons()},_renderDrilldownMiniChart(e,t){const a=document.getElementById("drilldownMiniChart");if(!a)return;v._drilldownChart&&(v._drilldownChart.destroy(),v._drilldownChart=null);const s=H(),o=e.reduce((r,n)=>r+n.total,0);v._drilldownChart=new ApexCharts(a,{chart:{type:"donut",height:"100%",background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:e.map(r=>r.total),labels:e.map(r=>r.label),colors:t,stroke:{width:2,colors:[s.surfaceColor]},plotOptions:{pie:{donut:{size:"55%"}}},legend:{show:!1},tooltip:{theme:s.mode,y:{formatter:r=>{const n=o>0?(r/o*100).toFixed(1):"0";return`${T(r)} (${n}%)`}}},dataLabels:{enabled:!1},theme:{mode:s.mode}}),v._drilldownChart.render()},_drilldownChart:null,renderLine(e){const{labels:t=[],values:a=[]}={...e,values:Q(e?.values)};if(!t.length)return I.UI.showEmptyState();I.UI.setContent(`
            <div class="chart-container chart-container-line">
                <div class="chart-wrapper chart-wrapper-line">
                    <div id="chart0"></div>
                </div>
            </div>
        `),v.destroy();const s=getComputedStyle(document.documentElement).getPropertyValue("--color-primary").trim(),o=H(),r=document.getElementById("chart0"),n=de(r,420),l=new ApexCharts(r,{chart:{type:"area",height:n,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,redrawOnParentResize:!0,redrawOnWindowResize:!0},series:[{name:"Saldo Diário",data:a.map(Number)}],xaxis:{categories:t,labels:{style:{fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:o.isLight?"#000":"#fff",fontSize:"11px"},formatter:c=>T(c)}},colors:[s],stroke:{curve:"smooth",width:2.5},fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.4,opacityTo:.05,stops:[0,100]}},markers:{size:4,hover:{size:6}},grid:{borderColor:o.gridColor,strokeDashArray:4},tooltip:{theme:o.mode,y:{formatter:c=>T(c)}},legend:{position:"bottom",labels:{colors:o.textColor}},title:{text:"Evolução do Saldo Mensal",align:"center",style:{fontSize:"16px",fontWeight:"bold",color:o.textColor}},dataLabels:{enabled:!1},theme:{mode:o.mode}});l.render(),i.chart=l},renderBar(e){const{labels:t=[],receitas:a=[],despesas:s=[]}={...e,receitas:Q(e?.receitas),despesas:Q(e?.despesas)};if(!t.length)return I.UI.showEmptyState();I.UI.setContent(`
            <div class="chart-container chart-container-bar">
                <div class="chart-wrapper chart-wrapper-bar">
                    <div id="chart0"></div>
                </div>
            </div>
        `),v.destroy();const o=E.getCssVar("--color-success","#2ecc71"),r=E.getCssVar("--color-danger","#e74c3c"),n=H(),l=document.getElementById("chart0"),c=de(l,420),d=i.currentView===u.VIEWS.ACCOUNTS?"Receitas x Despesas por Conta":i.currentView===u.VIEWS.ANNUAL_SUMMARY?"Resumo Anual por Mês":"Receitas x Despesas",m=new ApexCharts(l,{chart:{type:"bar",height:c,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,redrawOnParentResize:!0,redrawOnWindowResize:!0},series:[{name:"Receitas",data:a.map(Number)},{name:"Despesas",data:s.map(Number)}],xaxis:{categories:t,labels:{style:{colors:n.textMuted,fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:n.isLight?"#000":"#fff",fontSize:"11px"},formatter:p=>T(p)}},colors:[o,r],plotOptions:{bar:{borderRadius:6,columnWidth:"55%"}},grid:{borderColor:n.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:n.mode,shared:!0,intersect:!1,y:{formatter:p=>T(p)}},legend:{position:"bottom",labels:{colors:n.textColor},markers:{shape:"circle"}},title:{text:d,align:"center",style:{fontSize:"16px",fontWeight:"bold",color:n.textColor}},dataLabels:{enabled:!1},theme:{mode:n.mode}});m.render(),i.chart=m}};I.ChartManager=v;const Ue={toggleRelQuickStats:"relQuickStats",toggleRelOverviewCharts:"relOverviewChartsRow",toggleRelControls:"relControlsRow"},ge={toggleRelQuickStats:!0,toggleRelOverviewCharts:!0,toggleRelControls:!0},He={...ge,toggleRelQuickStats:!1,toggleRelControls:!1};async function We(){return Ie("relatorios")}async function Ye(e){await Te("relatorios",e)}const ze=$e({storageKey:"lk_relatorios_prefs",sectionMap:Ue,completeDefaults:ge,essentialDefaults:He,loadPreferences:We,savePreferences:Ye,modal:{overlayId:"relatoriosCustomizeModalOverlay",openButtonId:"btnCustomizeRelatorios",closeButtonId:"btnCloseCustomizeRelatorios",saveButtonId:"btnSaveCustomizeRelatorios",presetEssentialButtonId:"btnPresetEssencialRelatorios",presetCompleteButtonId:"btnPresetCompletoRelatorios"}});function qe(){ze.init()}const f=e=>E.formatCurrency(e);function W(e,t,a,s=!1){const o=document.getElementById(e);if(!o)return;if(!a||a===0){o.innerHTML="",o.className="stat-trend";return}const r=(t-a)/Math.abs(a)*100,n=Math.abs(r).toFixed(1);if(Math.abs(r)<.5)o.className="stat-trend trend-neutral",o.textContent="— Sem alteração";else{const l=r>0,c=s?!l:l;o.className=`stat-trend ${c?"trend-positive":"trend-negative"}`;const d=l?"↑":"↓";o.textContent=`${d} ${n}% vs mês anterior`}}function Ge(e){const t=document.querySelector(".chart-insight-line");if(t&&t.remove(),!e)return;let a="";switch(i.currentView){case u.VIEWS.CATEGORY:case u.VIEWS.ANNUAL_CATEGORY:{if(!e.labels||!e.values||e.values.length===0)break;const n=e.values.reduce((l,c)=>l+Number(c),0);if(n>0){const l=e.values.reduce((d,m,p,h)=>Number(m)>Number(h[d])?p:d,0),c=(Number(e.values[l])/n*100).toFixed(0);a=`${e.labels[l]} lidera com ${c}% dos gastos (${f(e.values[l])})`}break}case u.VIEWS.BALANCE:{if(!e.labels||!e.values||e.values.length===0)break;const n=e.values.map(Number),l=Math.min(...n),c=n.indexOf(l);a=`Menor saldo: ${f(l)} em ${e.labels[c]}`;break}case u.VIEWS.COMPARISON:{if(!e.receitas||!e.despesas)break;const n=e.receitas.map(Number),l=e.despesas.map(Number);a=`Em ${n.filter((d,m)=>d>(l[m]||0)).length} de ${n.length} dias, receitas superaram despesas`;break}case u.VIEWS.ACCOUNTS:{if(!e.labels||!e.despesas||e.despesas.length===0)break;const n=e.despesas.map(Number),l=n.reduce((c,d,m,p)=>d>p[c]?m:c,0);a=`Maior gasto: ${e.labels[l]} com ${f(n[l])} em despesas`;break}case u.VIEWS.EVOLUTION:{if(!e.values||e.values.length<2)break;const n=e.values.map(Number),l=n[0],c=n[n.length-1];a=`Evolução nos últimos 12 meses: ${c>l?"tendência de alta":c<l?"tendência de queda":"estável"}`;break}case u.VIEWS.ANNUAL_SUMMARY:{if(!e.labels||!e.receitas||e.receitas.length===0)break;const n=e.receitas.map(Number),l=e.despesas.map(Number),c=n.map((p,h)=>p-(l[h]||0)),d=c.reduce((p,h,y,x)=>h>x[p]?y:p,0),m=c.reduce((p,h,y,x)=>h<x[p]?y:p,0);a=`Melhor mês: ${e.labels[d]}. Pior mês: ${e.labels[m]}`;break}}if(!a)return;const o=document.getElementById("reportArea");if(!o)return;const r=document.createElement("div");r.className="chart-insight-line",r.innerHTML=`<i data-lucide="sparkles"></i> <span>${g(a)}</span>`,o.appendChild(r),window.lucide&&lucide.createIcons()}function je(e){return!e||e.length===0?"":`
        <div class="comparative-card comp-full-width surface-card surface-card--interactive">
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
                ${e.map((a,s)=>{const o=a.variacao>0?"trend-negative":a.variacao<0?"trend-positive":"trend-neutral",r=a.variacao>0?"arrow-up":a.variacao<0?"arrow-down":"equal",n=Math.abs(a.variacao)<.1?"Sem alteração":`${a.variacao>0?"+":""}${a.variacao.toFixed(1)}%`,l=e.reduce((m,p)=>m+p.atual,0),c=l>0?(a.atual/l*100).toFixed(0):0;let d="";return a.subcategorias&&a.subcategorias.length>0&&(d=`<div class="cat-comp-subcats">${a.subcategorias.map(p=>{const h=p.variacao>0?"trend-negative":p.variacao<0?"trend-positive":"",y=Math.abs(p.variacao)<.1?"":`<span class="subcat-trend ${h}">${p.variacao>0?"↑":"↓"}${Math.abs(p.variacao).toFixed(0)}%</span>`;return`
                    <span class="cat-comp-subcat-pill">
                        ${g(p.nome)}
                        <span class="subcat-value">${f(p.atual)}</span>
                        ${y}
                    </span>
                `}).join("")}</div>`),`
            <div class="cat-comp-row" style="animation-delay: ${s*.06}s">
                <div class="cat-comp-rank">${s+1}</div>
                <div class="cat-comp-info">
                    <span class="cat-comp-name">${g(a.nome)}</span>
                    <div class="cat-comp-bar-bg">
                        <div class="cat-comp-bar" style="width: ${c}%"></div>
                    </div>
                    ${d}
                </div>
                <div class="cat-comp-values">
                    <span class="cat-comp-current">${f(a.atual)}</span>
                    <span class="cat-comp-prev">${f(a.anterior)}</span>
                </div>
                <div class="cat-comp-trend ${o}">
                    <i data-lucide="${r}"></i>
                    <span>${n}</span>
                </div>
            </div>
        `}).join("")}
            </div>
        </div>
    `}function Ke(e){return!e||e.length===0?"":`
        <div class="comparative-card comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3><i data-lucide="line-chart"></i> Evolução dos Últimos 6 Meses</h3>
                <span class="comp-subtitle">Receitas, despesas e saldo ao longo do tempo</span>
            </div>
            <div class="evolucao-chart-wrapper">
                <div id="evolucaoMiniChart" style="min-height:220px;"></div>
            </div>
        </div>
    `}let N=null;function Qe(e){if(!e||e.length===0)return;const t=document.getElementById("evolucaoMiniChart");if(!t)return;const a=e.map(l=>l.label),o=getComputedStyle(document.documentElement).getPropertyValue("--color-text-muted").trim()||"#999",n=document.documentElement.getAttribute("data-theme")==="dark"?"dark":"light";N&&(N.destroy(),N=null),N=new ApexCharts(t,{chart:{type:"line",height:260,stacked:!1,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:[{name:"Receitas",type:"column",data:e.map(l=>l.receitas)},{name:"Despesas",type:"column",data:e.map(l=>l.despesas)},{name:"Saldo",type:"area",data:e.map(l=>l.saldo)}],xaxis:{categories:a,labels:{style:{colors:o}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:o},formatter:l=>f(l)}},colors:["rgba(46, 204, 113, 0.85)","rgba(231, 76, 60, 0.85)","#3498db"],stroke:{width:[0,0,2.5],curve:"smooth"},fill:{opacity:[.85,.85,.1]},plotOptions:{bar:{borderRadius:6,columnWidth:"55%"}},grid:{borderColor:"rgba(128,128,128,0.1)",strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:n,shared:!0,intersect:!1,y:{formatter:l=>f(l)}},legend:{position:"bottom",labels:{colors:o},markers:{shape:"circle"}},dataLabels:{enabled:!1},theme:{mode:n}}),N.render()}function Ze(e){if(!e)return"";const t=e.variacao>0?"trend-negative":e.variacao<0?"trend-positive":"trend-neutral",a=e.variacao>0?"arrow-up":e.variacao<0?"arrow-down":"equal";return`
        <div class="comparative-card comp-mini-card surface-card surface-card--interactive">
            <div class="comp-mini-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i data-lucide="calendar-clock"></i>
            </div>
            <div class="comp-mini-body">
                <span class="comp-mini-label">Média Diária de Gastos</span>
                <div class="comp-mini-values">
                    <span class="comp-mini-current">${f(e.atual)}/dia</span>
                    <span class="comp-mini-prev">anterior: ${f(e.anterior)}/dia</span>
                </div>
                <div class="comp-mini-trend ${t}">
                    <i data-lucide="${a}"></i>
                    <span>${Math.abs(e.variacao).toFixed(1)}%</span>
                </div>
            </div>
        </div>
    `}function Je(e){if(!e)return"";const t=e.atual>=0,a=e.diferenca>0?"trend-positive":e.diferenca<0?"trend-negative":"trend-neutral",s=e.diferenca>0?"arrow-up":e.diferenca<0?"arrow-down":"equal";return`
        <div class="comparative-card comp-mini-card surface-card surface-card--interactive">
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
                    <i data-lucide="${s}"></i>
                    <span>${e.diferenca>0?"+":""}${e.diferenca.toFixed(1)}pp</span>
                </div>
            </div>
        </div>
    `}function Xe(e){if(!e||e.length===0)return"";const t={Pix:"zap","Cartão de Crédito":"credit-card","Cartão de Débito":"credit-card",Dinheiro:"banknote",Boleto:"file-text",Depósito:"landmark",Transferência:"arrow-right-left",Estorno:"undo-2"},a=e.reduce((o,r)=>o+r.atual,0);return`
        <div class="comparative-card comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3><i data-lucide="wallet"></i> Formas de Pagamento</h3>
                <span class="comp-subtitle">Distribuição mês atual vs anterior</span>
            </div>
            <div class="forma-comp-list">
                ${e.map((o,r)=>{const n=a>0?(o.atual/a*100).toFixed(0):0,l=t[o.nome]||"wallet";return`
            <div class="forma-comp-row" style="animation-delay: ${r*.06}s">
                <div class="forma-comp-icon"><i data-lucide="${l}"></i></div>
                <div class="forma-comp-info">
                    <span class="forma-comp-name">${g(o.nome)}</span>
                    <div class="forma-comp-bar-bg">
                        <div class="forma-comp-bar" style="width: ${n}%"></div>
                    </div>
                </div>
                <div class="forma-comp-values">
                    <span class="forma-comp-current">${f(o.atual)} <small>(${o.atual_qtd}x)</small></span>
                    <span class="forma-comp-prev">${f(o.anterior)} <small>(${o.anterior_qtd}x)</small></span>
                </div>
            </div>
        `}).join("")}
            </div>
        </div>
    `}function ue(e,t,a){const s=(c,d=!1)=>c>0?'<i data-lucide="arrow-up"></i>':c<0?'<i data-lucide="arrow-down"></i>':'<i data-lucide="equal"></i>',o=(c,d=!1)=>{if(d){if(c>0)return"trend-negative";if(c<0)return"trend-positive"}else{if(c>0)return"trend-positive";if(c<0)return"trend-negative"}return"trend-neutral"},r=(c,d=!1)=>Math.abs(c)<.1?"Sem alteração":c>0?`Aumentou ${Math.abs(c).toFixed(1)}%`:c<0?`Reduziu ${Math.abs(c).toFixed(1)}%`:"Sem alteração",n=()=>{if(a.includes("mês")){const[c,d]=i.currentMonth.split("-");return new Date(c,d-1).toLocaleDateString("pt-BR",{month:"short",year:"numeric"})}else return i.currentMonth.split("-")[0]},l=()=>{if(a.includes("mês")){const[c,d]=i.currentMonth.split("-");return new Date(c,d-2).toLocaleDateString("pt-BR",{month:"short",year:"numeric"})}else return(parseInt(i.currentMonth.split("-")[0])-1).toString()};return`
        <div class="comparative-card surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3>${g(e)}</h3>
                <div class="period-labels">
                    <span class="period-current"><i data-lucide="calendar" style="color: white;"></i> ${n()}</span>
                    <span class="period-separator">vs</span>
                    <span class="period-previous">${l()}</span>
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
                            <span class="value-amount">${f(t.current.receitas)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${f(t.previous.receitas)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${o(t.variation.receitas,!1)}">
                        ${s(t.variation.receitas,!1)}
                        <span>${r(t.variation.receitas,!1)}</span>
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
                            <span class="value-amount">${f(t.current.despesas)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${f(t.previous.despesas)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${o(t.variation.despesas,!0)}">
                        ${s(t.variation.despesas,!0)}
                        <span>${r(t.variation.despesas,!0)}</span>
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
                            <span class="value-amount">${f(t.current.saldo)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${f(t.previous.saldo)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${o(t.variation.saldo,!1)}">
                        ${s(t.variation.saldo,!1)}
                        <span>${r(t.variation.saldo,!1)}</span>
                    </div>
                </div>
            </div>
        </div>
    `}function et(e){const t=document.getElementById("reportArea");if(!t)return;const a=e.resumo_consolidado&&e.cards&&e.cards.length>0?`
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
                        <span class="stat-value">${f(e.resumo_consolidado.total_faturas)}</span>
                    </div>
                </div>
                
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i data-lucide="wallet" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Limite Total</span>
                        <span class="stat-value">${f(e.resumo_consolidado.total_limites)}</span>
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
                        <span class="stat-value">${f(e.resumo_consolidado.total_disponivel)}</span>
                    </div>
                </div>
            </div>
            
            ${e.resumo_consolidado.melhor_cartao||e.resumo_consolidado.requer_atencao?`
                <div class="summary-insights">
                    ${e.resumo_consolidado.melhor_cartao?`
                        <div class="insight-item success">
                            <i data-lucide="star"></i>
                            <span><strong>Melhor cartão:</strong> ${g(e.resumo_consolidado.melhor_cartao.nome)} (${e.resumo_consolidado.melhor_cartao.percentual.toFixed(1)}% de uso)</span>
                        </div>
                    `:""}
                    ${e.resumo_consolidado.requer_atencao?`
                        <div class="insight-item warning">
                            <i data-lucide="triangle-alert"></i>
                            <span><strong>Requer atenção:</strong> ${g(e.resumo_consolidado.requer_atencao.nome)} (${e.resumo_consolidado.requer_atencao.percentual.toFixed(1)}% de uso)</span>
                        </div>
                    `:""}
                    ${e.resumo_consolidado.total_parcelamentos>0?`
                        <div class="insight-item info">
                            <i data-lucide="calendar-check"></i>
                            <span><strong>${e.resumo_consolidado.total_parcelamentos} parcelamento${e.resumo_consolidado.total_parcelamentos>1?"s":""}</strong> comprometendo ${f(e.resumo_consolidado.valor_parcelamentos)}</span>
                        </div>
                    `:""}
                </div>
            `:""}
        </div>
    `:"";t.innerHTML=`
        <div class="cards-report-container">
            ${a}
            
            <div class="cards-grid">
                ${e.cards&&e.cards.length>0?e.cards.map(s=>{const o=Fe(s.cor,"#E67E22");return`
                    <div class="card-item surface-card surface-card--interactive surface-card--clip ${s.status_saude.status}"
                         style="--card-color: ${o}; cursor: pointer;"
                         data-card-id="${s.id||""}"
                         data-card-nome="${g(s.nome)}"
                         data-card-cor="${o}"
                         data-card-month="${i.currentMonth}"
                         data-action="open-card-detail"
                         role="button"
                         tabindex="0">
                        <div class="card-header-gradient">
                            <div class="card-brand">
                                <div class="card-icon-wrapper" style="background: linear-gradient(135deg, ${o}, ${o}99);">
                                    <i data-lucide="credit-card" style="color: white"></i>
                                </div>
                                <div class="card-info">
                                    <h3 class="card-name">${g(s.nome)}</h3>
                                    <div class="card-meta">
                                        ${s.conta?`<span class="card-account"><i data-lucide="landmark"></i> ${g(s.conta)}</span>`:""}
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
                                        ${g(r.message)}
                                    </span>
                                `).join("")}
                            </div>
                        `:""}


                        <div class="card-balance">
                            <div class="balance-main">
                                <span class="balance-label">FATURA DO MÊS</span>
                                <span class="balance-value">${f(s.fatura_atual||0)}</span>
                                ${s.media_historica>0&&Math.abs(s.fatura_atual-s.media_historica)>1?`
                                    <span class="balance-comparison">
                                        ${s.fatura_atual>s.media_historica?"↑":"↓"} ${(Math.abs(s.fatura_atual-s.media_historica)/s.media_historica*100).toFixed(0)}% vs média
                                    </span>
                                `:""}
                            </div>
                            <div class="balance-grid">
                                <div class="balance-item">
                                    <span class="balance-small-label">Limite</span>
                                    <span class="balance-small-value">${f(s.limite||0)}</span>
                                </div>
                                <div class="balance-item">
                                    <span class="balance-small-label">Disponível</span>
                                    <span class="balance-small-value">${f(s.disponivel||0)}</span>
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
                                        <span>Próximo: ${f(s.proximos_meses.find(r=>r.valor>0)?.valor||0)}</span>
                                    </div>
                                `:""}
                            </div>
                        `:""}
                        
                        <div class="card-footer">
                            <button class="card-action-btn primary full-width" data-action="open-card-detail" data-card-id="${s.id||""}" data-card-nome="${g(s.nome)}" data-card-cor="${o}" data-card-month="${i.currentMonth}" title="Ver relatório detalhado">
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
    `,window.lucide&&lucide.createIcons()}const _=e=>E.formatCurrency(e),ve={"arrow-trend-up":"trending-up","arrow-trend-down":"trending-down","arrow-up":"arrow-up","arrow-down":"arrow-down","chart-line":"line-chart","chart-pie":"pie-chart","exclamation-triangle":"triangle-alert","exclamation-circle":"circle-alert","check-circle":"circle-check","info-circle":"info",lightbulb:"lightbulb",star:"star",bolt:"zap",wallet:"wallet","credit-card":"credit-card","calendar-check":"calendar-check",calendar:"calendar",crown:"crown",trophy:"trophy",leaf:"leaf","shield-alt":"shield","money-bill-wave":"banknote","trending-up":"trending-up","trending-down":"trending-down","shield-alert":"shield-alert",gauge:"gauge",target:"target",clock:"clock",receipt:"receipt",calculator:"calculator",layers:"layers","calendar-clock":"calendar-clock","pie-chart":"pie-chart","calendar-range":"calendar-range","list-plus":"list-plus","list-minus":"list-minus","file-text":"file-text","piggy-bank":"piggy-bank",banknote:"banknote"};let q=[];function tt(){q.forEach(e=>{try{e.destroy()}catch{}}),q=[]}function at(e,t){if(!e)return;const a=t.saldo||0,s=a>=0?"var(--color-success)":"var(--color-danger)",o=a>=0?"positivo":"negativo";let r=`
        <p class="pulse-text">
            Neste mês você recebeu <strong>${_(t.totalReceitas)}</strong>
            e gastou <strong>${_(t.totalDespesas)}</strong>.
            Seu saldo é <strong style="color:${s}">${o} em ${_(Math.abs(a))}</strong>.
    `;t.totalCartoes>0&&(r+=` Faturas de cartões somam <strong>${_(t.totalCartoes)}</strong>.`),r+="</p>",e.innerHTML=r}function st(e,t){if(e){if(t?.insights?.length>0){e.innerHTML=t.insights.map(a=>{const s=ve[a.icon]||a.icon;return`
                <div class="insight-card insight-${a.type} surface-card surface-card--interactive">
                    <div class="insight-icon"><i data-lucide="${s}"></i></div>
                    <div class="insight-content">
                        <h4>${g(a.title)}</h4>
                        <p>${g(a.message)}</p>
                    </div>
                </div>`}).join("");return}e.innerHTML='<p class="empty-message">Nenhum insight disponível no momento</p>'}}function ot(e,t){if(!e)return;if(!t?.labels?.length){e.innerHTML='<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de categorias</p>';return}e.innerHTML="";const a=5,s=t.labels.slice(0,a),o=t.values.slice(0,a).map(Number);if(t.labels.length>a){const n=t.values.slice(a).reduce((l,c)=>l+Number(c),0);s.push("Outros"),o.push(n)}const r=new ApexCharts(e,{chart:{type:"donut",height:220,background:"transparent"},series:o,labels:s,colors:["#E67E22","#2C3E50","#2ECC71","#F39C12","#9B59B6","#1ABC9C"],legend:{position:"bottom",fontSize:"11px",labels:{colors:"var(--color-text-muted)"}},dataLabels:{enabled:!1},plotOptions:{pie:{donut:{size:"60%"}}},stroke:{show:!1},tooltip:{y:{formatter:n=>_(n)}}});r.render(),q.push(r)}function rt(e,t){if(!e)return;if(!t?.labels?.length){e.innerHTML='<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de movimentação</p>';return}e.innerHTML="";const a=(t.receitas||[]).map(Number),s=(t.despesas||[]).map(Number),o=[],r=[],n=[],l=7;for(let d=0;d<t.labels.length;d+=l){const m=Math.floor(d/l)+1;o.push(`Sem ${m}`),r.push(a.slice(d,d+l).reduce((p,h)=>p+h,0)),n.push(s.slice(d,d+l).reduce((p,h)=>p+h,0))}const c=new ApexCharts(e,{chart:{type:"bar",height:220,background:"transparent",toolbar:{show:!1}},series:[{name:"Receitas",data:r},{name:"Despesas",data:n}],colors:["#2ECC71","#E74C3C"],xaxis:{categories:o,labels:{style:{colors:"var(--color-text-muted)",fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{fontSize:"10px"},formatter:d=>_(d)}},plotOptions:{bar:{columnWidth:"60%",borderRadius:4}},dataLabels:{enabled:!1},legend:{position:"bottom",fontSize:"11px",labels:{colors:"var(--color-text-muted)"}},grid:{borderColor:"rgba(255,255,255,0.05)"},tooltip:{shared:!0,intersect:!1,y:{formatter:d=>_(d)}}});c.render(),q.push(c)}function nt({API:e}){async function t(){const r=document.getElementById("overviewPulse"),n=document.getElementById("overviewInsights"),l=document.getElementById("overviewCategoryChart"),c=document.getElementById("overviewComparisonChart");tt();const[d,m,p,h]=await Promise.all([e.fetchSummaryStats(),e.fetchInsightsTeaser(),e.fetchReportDataForType("despesas_por_categoria",{accountId:null}),e.fetchReportDataForType("receitas_despesas_diario",{accountId:null})]);at(r,d),st(n,m),ot(l,p),rt(c,h),window.lucide&&lucide.createIcons()}async function a(){const r=document.getElementById("insightsContainer");if(!r)return;const n=window.IS_PRO?await e.fetchInsights():await e.fetchInsightsTeaser();if(!n||!n.insights||n.insights.length===0){r.innerHTML='<p class="empty-message">Nenhum insight disponível no momento</p>';return}const l=n.insights.map(c=>{const d=ve[c.icon]||c.icon;return`
                <div class="insight-card insight-${c.type} surface-card surface-card--interactive">
                    <div class="insight-icon">
                        <i data-lucide="${d}"></i>
                    </div>
                    <div class="insight-content">
                        <h4>${g(c.title)}</h4>
                        <p>${g(c.message)}</p>
                    </div>
                </div>
            `}).join("");if(r.innerHTML=l,!window.IS_PRO&&n.isTeaser){const c=Math.max(0,(n.totalCount||0)-n.insights.length),d=c>0?`Desbloqueie mais ${c} insights com PRO`:"Desbloqueie todos os insights com PRO";r.insertAdjacentHTML("beforeend",`
                <div class="insights-teaser-overlay">
                    <div class="teaser-blur-mask"></div>
                    <div class="teaser-cta">
                        <i data-lucide="crown"></i>
                        <h4>${d}</h4>
                        <p>Tenha uma visão completa da sua saúde financeira com análises detalhadas.</p>
                        <a href="${u.BASE_URL}billing" class="btn-upgrade-cta surface-button surface-button--upgrade">
                            <i data-lucide="crown"></i> Fazer Upgrade
                        </a>
                    </div>
                </div>
            `)}window.lucide&&lucide.createIcons()}async function s(){const r=document.getElementById("comparativesContainer");if(!r)return;const n=await e.fetchComparatives();if(!n){r.innerHTML='<p class="empty-message">Dados de comparação não disponíveis</p>';return}const l=ue("Comparativo Mensal",n.monthly,"mês anterior"),c=ue("Comparativo Anual",n.yearly,"ano anterior"),d=je(n.categories||[]),m=Ke(n.evolucao||[]),p=Ze(n.mediaDiaria),h=Je(n.taxaEconomia),y=Xe(n.formasPagamento||[]);r.innerHTML=`<div class="comp-top-row">${l}${c}</div><div class="comp-duo-grid">${p}${h}</div>`+d+m+y,window.lucide&&lucide.createIcons(),Qe(n.evolucao||[])}async function o(){const r=await e.fetchSummaryStats(),n=document.getElementById("totalReceitas"),l=document.getElementById("totalDespesas"),c=document.getElementById("saldoMes"),d=document.getElementById("totalCartoes");if(n&&(n.textContent=_(r.totalReceitas||0)),l&&(l.textContent=_(r.totalDespesas||0)),c){const y=r.saldo||0;c.textContent=_(y),c.style.color=y>=0?"var(--color-success)":"var(--color-danger)"}d&&(d.textContent=_(r.totalCartoes||0)),W("trendReceitas",r.totalReceitas,r.prevReceitas,!1),W("trendDespesas",r.totalDespesas,r.prevDespesas,!0),W("trendSaldo",r.saldo,r.prevSaldo,!1),W("trendCartoes",r.totalCartoes,r.prevCartoes,!0);const m=document.getElementById("section-overview");m&&m.classList.contains("active")&&await t();const p=document.getElementById("section-insights");p&&p.classList.contains("active")&&await a();const h=document.getElementById("section-comparativos");h&&h.classList.contains("active")&&await s()}return{updateSummaryCards:o,updateInsightsSection:a,updateOverviewSection:t,updateComparativesSection:s}}function it({getReportType:e,showRestrictionAlert:t,handleRestrictedAccess:a}){return async function(){if(!window.IS_PRO)return t("Exportação de relatórios é exclusiva do plano PRO.");const o=e()||"despesas_por_categoria",{value:r}=await Swal.fire({title:"Exportar Relatório",html:`
                <div style="text-align:left;display:flex;flex-direction:column;gap:12px;padding-top:8px;">
                    <label style="font-weight:600;font-size:0.85rem;color:var(--color-text-muted);">Tipo de Relatório</label>
                    <select id="swalExportType" class="swal2-select" style="width:100%;font-size:0.9rem;">
                        <option value="despesas_por_categoria" ${o==="despesas_por_categoria"?"selected":""}>Despesas por Categoria</option>
                        <option value="receitas_por_categoria" ${o==="receitas_por_categoria"?"selected":""}>Receitas por Categoria</option>
                        <option value="saldo_mensal" ${o==="saldo_mensal"?"selected":""}>Saldo Diário</option>
                        <option value="receitas_despesas_diario" ${o==="receitas_despesas_diario"?"selected":""}>Receitas x Despesas Diário</option>
                        <option value="evolucao_12m" ${o==="evolucao_12m"?"selected":""}>Evolução 12 Meses</option>
                        <option value="receitas_despesas_por_conta" ${o==="receitas_despesas_por_conta"?"selected":""}>Receitas x Despesas por Conta</option>
                        <option value="cartoes_credito" ${o==="cartoes_credito"?"selected":""}>Relatório de Cartões</option>
                        <option value="resumo_anual" ${o==="resumo_anual"?"selected":""}>Resumo Anual</option>
                        <option value="despesas_anuais_por_categoria" ${o==="despesas_anuais_por_categoria"?"selected":""}>Despesas Anuais por Categoria</option>
                        <option value="receitas_anuais_por_categoria" ${o==="receitas_anuais_por_categoria"?"selected":""}>Receitas Anuais por Categoria</option>
                    </select>
                    <label style="font-weight:600;font-size:0.85rem;color:var(--color-text-muted);">Formato</label>
                    <select id="swalExportFormat" class="swal2-select" style="width:100%;font-size:0.9rem;">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel (.xlsx)</option>
                    </select>
                </div>
            `,showCancelButton:!0,confirmButtonText:"Exportar",cancelButtonText:"Cancelar",confirmButtonColor:"#e67e22",preConfirm:()=>({type:document.getElementById("swalExportType").value,format:document.getElementById("swalExportFormat").value})});if(!r)return;const n=document.getElementById("exportBtn"),l=n?n.innerHTML:"";n&&(n.disabled=!0,n.innerHTML=`
                <div class="spinner" style="width: 1rem; height: 1rem; border-width: 2px;"></div>
                <span>Exportando...</span>
            `);try{const c=r.type,d=r.format,m=new URLSearchParams({type:c,format:d,year:i.currentMonth.split("-")[0],month:i.currentMonth.split("-")[1]});i.currentAccount&&m.set("account_id",i.currentAccount);const p=await _e(`${ke()}?${m.toString()}`,{method:"GET"},{responseType:"response"}),h=await p.blob(),y=p.headers.get("Content-Disposition"),x=E.extractFilename(y)||(d==="excel"?"relatorio.xlsx":"relatorio.pdf"),$=URL.createObjectURL(h),M=document.createElement("a");M.href=$,M.download=x,document.body.appendChild(M),M.click(),M.remove(),URL.revokeObjectURL($),typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"success",title:"Relatório exportado!",text:x,showConfirmButton:!1,timer:3e3,timerProgressBar:!0})}catch(c){if(await a(c))return;console.error("Export error:",c);const d=me(c,"Erro ao exportar relatório. Tente novamente.");typeof Swal<"u"?Swal.fire({toast:!0,position:"top-end",icon:"error",title:"Erro ao exportar",text:d,showConfirmButton:!1,timer:3e3}):alert(d)}finally{n&&(n.disabled=!1,n.innerHTML=l)}}}const fe=e=>E.formatMonthLabel(e),D=e=>E.isYearlyView(e),ct=()=>E.getReportType(),be=()=>E.getActiveCategoryType();function Z(e=i.currentAccount){return e?i.accounts.find(t=>String(t.id)===String(e))?.name||`Conta #${e}`:null}function J(){return D()?`Ano ${i.currentMonth.split("-")[0]}`:fe(i.currentMonth)}function pe(){const e=be();return(z[i.currentView]||[]).find(a=>a.value===e)?.label||null}function lt(e=i.activeSection){return e==="relatorios"||e==="comparativos"}function dt(e=i.activeSection){return ce[e]||ce.overview}function X(e=i.currentView){return le[e]||le[u.VIEWS.CATEGORY]}function we(){try{localStorage.setItem(L.ACTIVE_VIEW,i.currentView),localStorage.setItem(L.CATEGORY_TYPE,i.categoryType),localStorage.setItem(L.ANNUAL_CATEGORY_TYPE,i.annualCategoryType)}catch{}}function te(){typeof window.openBillingModal=="function"?window.openBillingModal():location.href=`${u.BASE_URL}billing`}async function ye(e){const t=e||Y;window.PlanLimits?.promptUpgrade?await window.PlanLimits.promptUpgrade({context:"relatorios",message:t}):window.LKFeedback?.upgradePrompt?await window.LKFeedback.upgradePrompt({context:"relatorios",message:t}):window.Swal?.fire?(await Swal.fire({title:"Recurso exclusivo",text:t,icon:"info",showCancelButton:!0,confirmButtonText:"Assinar plano Pro",cancelButtonText:"Agora não",reverseButtons:!0,focusConfirm:!0})).isConfirmed&&te():confirm(`${t}

Deseja ir para a página de planos agora?`)&&te()}async function k(e){if(!e)return!1;const t=Number(e.status||e?.data?.status||0);if(t===401){const a=encodeURIComponent(location.pathname+location.search);return location.href=`${u.BASE_URL}login?return=${a}`,!0}if(t===403){let a=Y;if(e?.data?.message)a=e.data.message;else if(typeof e?.clone=="function")try{const s=await e.clone().json();s?.message&&(a=s.message)}catch{}return i.accessRestricted||(i.accessRestricted=!0,await ye(a)),w.showPaywall(a),!0}return!1}function ut(e){typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"error",title:e,showConfirmButton:!1,timer:4e3,timerProgressBar:!0})}const G={async fetchReportData(){i.lastReportError=null;const e=new AbortController,t=setTimeout(()=>e.abort(),u.FETCH_TIMEOUT);try{const a=await R(ie(),{type:E.getReportType(),year:i.currentMonth.split("-")[0],month:i.currentMonth.split("-")[1],account_id:i.currentAccount||void 0});return clearTimeout(t),i.accessRestricted=!1,i.lastReportError=null,a.data||a}catch(a){return clearTimeout(t),await k(a)||(i.lastReportError=a.name==="AbortError"?"A requisição demorou demais. Tente novamente em instantes.":"Não foi possível carregar o relatório agora. Verifique a conexão e tente novamente.",console.error("Error fetching report data:",a),ut(me(a,"Erro ao carregar relatório. Verifique sua conexão."))),null}},async fetchReportDataForType(e,t={}){const a=new URLSearchParams({type:e,year:i.currentMonth.split("-")[0],month:i.currentMonth.split("-")[1]}),s=Object.prototype.hasOwnProperty.call(t,"accountId")?t.accountId:i.currentAccount;s&&a.set("account_id",s);try{const o=await R(ie(),Object.fromEntries(a.entries()));return o.data||o}catch{return null}},async fetchAccounts(){try{const e=await R(Oe());i.accessRestricted=!1;const t=e.data||e.items||e||[];return(Array.isArray(t)?t:[]).map(a=>({id:Number(a.id),name:a.nome||a.apelido||a.instituicao||`Conta #${a.id}`}))}catch(e){return await k(e)?[]:(console.error("Error fetching accounts:",e),[])}},async fetchSummaryStats(){const[e,t]=i.currentMonth.split("-"),a=new AbortController,s=setTimeout(()=>a.abort(),u.FETCH_TIMEOUT);try{const o=await R(De(),{year:e,month:t});return clearTimeout(s),o.data||o}catch(o){return clearTimeout(s),await k(o)?{totalReceitas:0,totalDespesas:0,saldo:0,totalCartoes:0}:(console.error("Error fetching summary stats:",o),{totalReceitas:0,totalDespesas:0,saldo:0,totalCartoes:0})}},async fetchInsights(){const[e,t]=i.currentMonth.split("-"),a=new AbortController,s=setTimeout(()=>a.abort(),u.FETCH_TIMEOUT);try{const o=await R(Ne(),{year:e,month:t});return clearTimeout(s),o.data||o}catch(o){return clearTimeout(s),await k(o)?{insights:[]}:(console.error("Error fetching insights:",o),{insights:[]})}},async fetchInsightsTeaser(){const[e,t]=i.currentMonth.split("-"),a=new AbortController,s=setTimeout(()=>a.abort(),u.FETCH_TIMEOUT);try{const o=await R(Be(),{year:e,month:t});return clearTimeout(s),o.data||o}catch(o){return clearTimeout(s),console.error("Error fetching insights teaser:",o),{insights:[],totalCount:0,isTeaser:!0}}},async fetchComparatives(){const[e,t]=i.currentMonth.split("-"),a=new URLSearchParams({year:e,month:t});i.currentAccount&&a.set("account_id",i.currentAccount);const s=new AbortController,o=setTimeout(()=>s.abort(),u.FETCH_TIMEOUT);try{const r=await R(Pe(),Object.fromEntries(a.entries()));return clearTimeout(o),r.data||r}catch(r){return clearTimeout(o),await k(r)||console.error("Error fetching comparatives:",r),null}}};I.API=G;const w={setContent(e){const t=document.getElementById("reportArea");t&&(t.innerHTML=e,t.setAttribute("aria-busy","false"),window.lucide&&lucide.createIcons())},showLoading(){const e=document.getElementById("reportArea");e&&(e.setAttribute("aria-busy","true"),e.innerHTML=`
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Carregando relatório...</p>
                </div>
            `,window.lucide&&lucide.createIcons())},showEmptyState(){const e=Z(),t=X(),a=J(),s=e?`Nenhum dado foi encontrado para ${e} em ${a}.`:`Não há lançamentos suficientes para montar este recorte em ${a}.`;w.setContent(`
            <div class="empty-state report-empty-state">
                <i data-lucide="pie-chart"></i>
                <h3>${g(t.title)}</h3>
                <p>${g(s)}</p>
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
        `)},showErrorState(e){const t=g(e||"Não foi possível carregar este relatório.");w.setContent(`
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
        `)},showPaywall(e=Y){const t=document.getElementById("reportArea");if(!t)return;const a=g(e||Y);t.setAttribute("aria-busy","false"),t.innerHTML=`
            <div class="paywall-message" role="alert">
                <i data-lucide="crown" aria-hidden="true"></i>
                <h3>Recurso Premium</h3>
                <p>${a}</p>
                <button type="button" class="btn-upgrade surface-button surface-button--upgrade surface-button--lg" data-action="go-pro">
                    Fazer Upgrade para PRO
                </button>
            </div>
        `,window.lucide&&lucide.createIcons();const s=t.querySelector('[data-action="go-pro"]');s&&s.addEventListener("click",te)},updateMonthLabel(){const e=document.getElementById("monthLabel");e&&(e.textContent=D()?i.currentMonth.split("-")[0]:fe(i.currentMonth))},updatePageContext(){const e=document.getElementById("reportsContextKicker"),t=document.getElementById("reportsContextTitle"),a=document.getElementById("reportsContextDescription"),s=document.getElementById("reportsContextChips"),o=document.getElementById("reportsContextActions");if(!e||!t||!a||!s||!o)return;const r=dt(),n=X(),l=J(),c=Z(),d=lt(),m=pe(),p=!window.IS_PRO&&i.activeSection==="insights";e.textContent=r.kicker,t.textContent=i.activeSection==="relatorios"?n.title:r.title,a.textContent=i.activeSection==="relatorios"?n.description:r.description;const h=[`<span class="context-chip surface-chip"><i data-lucide="calendar-range"></i><span>${g(l)}</span></span>`];i.activeSection==="relatorios"&&m&&h.push(`<span class="context-chip surface-chip surface-chip--highlight context-chip-highlight"><i data-lucide="filter"></i><span>${g(m)}</span></span>`),c&&d?h.push(`<span class="context-chip surface-chip surface-chip--highlight context-chip-highlight"><i data-lucide="landmark"></i><span>${g(c)}</span></span>`):c&&!d?h.push(`<span class="context-chip surface-chip"><i data-lucide="bookmark"></i><span>Filtro salvo: ${g(c)}</span></span>`):h.push('<span class="context-chip surface-chip"><i data-lucide="layers"></i><span>Consolidado</span></span>'),p&&h.push('<span class="context-chip surface-chip surface-chip--pro context-chip-pro"><i data-lucide="crown"></i><span>Preview PRO</span></span>'),s.innerHTML=h.join(""),o.innerHTML=c?`
            <button type="button" class="context-action-btn surface-button surface-button--subtle" data-action="clear-report-account">
                <i data-lucide="eraser"></i>
                <span>Limpar filtro de conta</span>
            </button>
        `:"",window.lucide&&lucide.createIcons()},updateReportFilterSummary(){const e=document.getElementById("reportFilterSummary"),t=document.getElementById("reportScopeNote");if(!e||!t)return;const a=[`<span class="report-filter-chip surface-chip"><i data-lucide="calendar-range"></i><span>${g(J())}</span></span>`,`<span class="report-filter-chip surface-chip"><i data-lucide="bar-chart-3"></i><span>${g(X().title)}</span></span>`],s=pe();s&&a.push(`<span class="report-filter-chip surface-chip"><i data-lucide="filter"></i><span>${g(s)}</span></span>`),i.currentAccount?a.push(`<span class="report-filter-chip surface-chip surface-chip--highlight report-filter-chip-highlight"><i data-lucide="landmark"></i><span>${g(Z())}</span></span>`):a.push('<span class="report-filter-chip surface-chip"><i data-lucide="layers"></i><span>Todas as contas</span></span>'),e.innerHTML=a.join(""),t.classList.remove("hidden"),t.innerHTML=i.currentAccount?'<i data-lucide="info"></i><span>O resumo do topo continua consolidado. O filtro por conta afeta este gráfico e a aba Comparativos.</span>':'<i data-lucide="info"></i><span>Use o filtro de conta para analisar um recorte específico sem perder o consolidado do topo.</span>',window.lucide&&lucide.createIcons()},updateControls(){const e=document.getElementById("typeSelectWrapper"),t=[u.VIEWS.CATEGORY,u.VIEWS.ANNUAL_CATEGORY].includes(i.currentView);e&&(e.classList.toggle("hidden",!t),t&&w.syncTypeSelect());const a=document.getElementById("accountSelectWrapper");a&&a.classList.remove("hidden")},syncTypeSelect(){const e=document.getElementById("reportType");if(!e)return;const t=z[i.currentView];if(!t)return;(e.options.length!==t.length||t.some((s,o)=>e.options[o]?.value!==s.value))&&(e.innerHTML=t.map(s=>`<option value="${s.value}">${s.label}</option>`).join("")),e.value=be()},setActiveTab(e){document.querySelectorAll(".tab-btn").forEach(t=>{const a=t.dataset.view===e;t.classList.toggle("active",a),t.setAttribute("aria-selected",a)})}};I.UI=w;const pt=()=>G.fetchReportData(),mt=()=>w.showLoading(),ee=()=>w.showEmptyState(),ht=e=>w.showErrorState(e),gt=()=>w.updateMonthLabel(),P=()=>w.updatePageContext(),B=()=>w.updateReportFilterSummary(),vt=()=>w.updateControls(),ft=e=>w.setActiveTab(e),bt=e=>v.renderPie(e),wt=e=>v.renderLine(e),yt=e=>v.renderBar(e);async function A(){P(),B(),mt(),Ct();const e=await pt();if(!i.accessRestricted){if(i.lastReportError)return ht(i.lastReportError);if(i.currentView===u.VIEWS.CARDS){if(!e||!Array.isArray(e.cards))return ee();et(e);return}if(!e||!e.labels||e.labels.length===0)return ee();switch(i.currentView){case u.VIEWS.CATEGORY:case u.VIEWS.ANNUAL_CATEGORY:bt(e);break;case u.VIEWS.BALANCE:case u.VIEWS.EVOLUTION:wt(e);break;case u.VIEWS.COMPARISON:case u.VIEWS.ACCOUNTS:case u.VIEWS.ANNUAL_SUMMARY:yt(e);break;default:ee()}Ge(e)}}const{updateSummaryCards:Ct,updateInsightsSection:Et,updateOverviewSection:St,updateComparativesSection:xt}=nt({API:G}),_t=it({getReportType:ct,showRestrictionAlert:ye,handleRestrictedAccess:k});async function At(e){e==="overview"?await St():e==="relatorios"?await A():e==="insights"?await Et():e==="comparativos"&&await xt()}function Ce(){const e=D();if(window.LukratoHeader?.setPickerMode?.(e?"year":"month"),e){const t=window.LukratoHeader?.getYear?.();if(t){const[,a="01"]=i.currentMonth.split("-"),s=String(a).padStart(2,"0");i.currentMonth=`${t}-${s}`}}}function Ee(e){i.currentView=e,ft(e),vt(),P(),B(),Ce(),we(),A()}function Se(e){i.currentView===u.VIEWS.ANNUAL_CATEGORY?i.annualCategoryType=e:i.categoryType=e,P(),B(),we(),A()}function ae(e){i.currentAccount=e||null,P(),B(),A()}function $t(e){!e?.detail?.month||D()||i.currentMonth!==e.detail.month&&(i.currentMonth=e.detail.month,gt(),P(),B(),A())}function Tt(e){if(!D()||!e?.detail?.year)return;const[,t="01"]=i.currentMonth.split("-"),a=String(t).padStart(2,"0"),s=`${e.detail.year}-${a}`;i.currentMonth!==s&&(i.currentMonth=s,P(),B(),A())}function se(){return window.IS_PRO=Le().isPro===!0,window.IS_PRO}Me(()=>{se()});function It(){try{const e=localStorage.getItem(L.ACTIVE_VIEW);e&&Object.values(u.VIEWS).includes(e)&&(i.currentView=e);const t=localStorage.getItem(L.CATEGORY_TYPE);t&&z[u.VIEWS.CATEGORY]?.some(s=>s.value===t)&&(i.categoryType=t);const a=localStorage.getItem(L.ANNUAL_CATEGORY_TYPE);a&&z[u.VIEWS.ANNUAL_CATEGORY]?.some(s=>s.value===a)&&(i.annualCategoryType=a)}catch{}}function Mt(){const e=s=>{i.activeSection=s,document.querySelectorAll(".rel-section-tab").forEach(n=>{n.classList.remove("active"),n.setAttribute("aria-selected","false")}),document.querySelectorAll(".rel-section-panel").forEach(n=>{n.classList.remove("active")});const o=document.querySelector(`.rel-section-tab[data-section="${s}"]`);o&&(o.classList.add("active"),o.setAttribute("aria-selected","true"));const r=document.getElementById(`section-${s}`);r&&r.classList.add("active"),localStorage.setItem(L.ACTIVE_SECTION,s),w.updatePageContext(),At(s),window.lucide?.createIcons?.()},t=["comparativos"];document.querySelectorAll(".rel-section-tab").forEach(s=>{s.addEventListener("click",()=>{const o=s.dataset.section;if(!window.IS_PRO&&t.includes(o)){window.PlanLimits?.promptUpgrade?window.PlanLimits.promptUpgrade({context:"relatorios",message:"Esta funcionalidade e exclusiva do plano Pro."}).catch(()=>{}):window.LKFeedback?.upgradePrompt?window.LKFeedback.upgradePrompt({context:"relatorios",message:"Esta funcionalidade e exclusiva do plano Pro."}).catch(()=>{}):Swal.fire({icon:"info",title:"Recurso Premium",html:"Esta funcionalidade e exclusiva do <b>plano Pro</b>.<br>Faca upgrade para desbloquear!",confirmButtonText:'<i class="lucide-crown" style="margin-right:6px"></i> Fazer Upgrade',showCancelButton:!0,cancelButtonText:"Agora nao",confirmButtonColor:"#f59e0b",cancelButtonColor:"#64748b"}).then(r=>{r.isConfirmed&&(window.location.href=`${u.BASE_URL}billing`)});return}e(o)})});const a=localStorage.getItem(L.ACTIVE_SECTION);if(a&&document.getElementById(`section-${a}`)){!window.IS_PRO&&t.includes(a)?e("overview"):e(a);return}e("overview")}function Lt(e,t){const a=document.getElementById("clearFiltersWrapper"),s=document.getElementById("btnLimparFiltrosRel"),o=()=>{if(!a)return;const r=e&&e.selectedIndex>0,n=t&&t.value!=="";a.style.display=r||n?"flex":"none"};return e?.addEventListener("change",o),t?.addEventListener("change",o),s?.addEventListener("click",()=>{e&&(e.selectedIndex=0,Se(e.value)),t&&(t.value="",ae("")),o()}),o(),o}function Rt(e,t){document.addEventListener("click",a=>{if(a.target.closest('[data-action="retry-report"]')){a.preventDefault(),A();return}if(a.target.closest('[data-action="clear-report-account"]')){a.preventDefault(),e&&(e.value=""),ae(""),typeof t=="function"&&t();return}const r=a.target.closest('[data-action="open-card-detail"]');if(!r)return;a.stopPropagation();const n=parseInt(r.dataset.cardId,10),l=r.dataset.cardNome||"",c=r.dataset.cardCor||"#E67E22",d=r.dataset.cardMonth||i.currentMonth;if(n){if(window.LK_CardDetail?.open){window.LK_CardDetail.open(n,l,c,d);return}console.error("[Relatorios] LK_CardDetail module not loaded"),typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"error",title:"Modulo de detalhes nao carregado",text:"Recarregue a pagina.",showConfirmButton:!1,timer:3e3})}})}function Ot(){window.ReportsAPI={setMonth:e=>{/^\d{4}-\d{2}$/.test(e)&&(i.currentMonth=e,w.updateMonthLabel(),A())},setView:e=>{Object.values(u.VIEWS).includes(e)&&Ee(e)},refresh:()=>A(),getState:()=>({...i})}}async function kt(){se(),qe(),v.setupDefaults(),i.accounts=await G.fetchAccounts();const e=document.getElementById("accountFilter");e&&i.accounts.forEach(o=>{const r=document.createElement("option");r.value=o.id,r.textContent=o.name,e.appendChild(r)}),It(),document.querySelectorAll(".tab-btn").forEach(o=>{o.addEventListener("click",()=>Ee(o.dataset.view))}),w.setActiveTab(i.currentView),w.updateControls(),w.updatePageContext(),Mt();const t=document.getElementById("reportType");t?.addEventListener("change",o=>Se(o.target.value)),e?.addEventListener("change",o=>ae(o.target.value));const a=Lt(t,e);document.addEventListener("lukrato:theme-changed",()=>{v.setupDefaults(),A()});const s=window.LukratoHeader?.getMonth?.();s&&(i.currentMonth=s),document.addEventListener("lukrato:month-changed",$t),document.addEventListener("lukrato:year-changed",Tt),document.getElementById("exportBtn")?.addEventListener("click",_t),Rt(e,a),Ce(),w.updateMonthLabel(),w.updateControls(),await A()}function Pt(){if(window.__LK_REPORTS_LOADED__)return;window.__LK_REPORTS_LOADED__=!0;const e=async()=>{await Re({},{silent:!0}),se(),await kt()};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",()=>{e()}):e(),Ot()}Pt();
