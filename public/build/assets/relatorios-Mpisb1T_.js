import{m as Ae,b as Te,e as he,k as R}from"./api-EIRNFJb7.js";import{a as Ie}from"./utils-Bj4jxwhy.js";import{c as $e,p as _e,f as Le}from"./ui-preferences-B8SkNUZA.js";import{o as Me,g as Re,e as ke}from"./runtime-config-BDcybaNg.js";import{r as Oe}from"./finance-CgaDv1sH.js";import{r as Pe,a as Ne,b as Be,c as De,d as Ve,e as ie}from"./reports-CXrVZnrN.js";const Y="Relatórios são exclusivos do plano Pro.",u={BASE_URL:Ae(),CHART_COLORS:["#E67E22","#2C3E50","#2ECC71","#F39C12","#9B59B6","#1ABC9C","#E74C3C","#3498DB"],FETCH_TIMEOUT:3e4,VIEWS:{CATEGORY:"category",BALANCE:"balance",COMPARISON:"comparison",ACCOUNTS:"accounts",CARDS:"cards",EVOLUTION:"evolution",ANNUAL_SUMMARY:"annual_summary",ANNUAL_CATEGORY:"annual_category"}},Fe=new Set([u.VIEWS.ANNUAL_SUMMARY,u.VIEWS.ANNUAL_CATEGORY]),q={[u.VIEWS.CATEGORY]:[{value:"despesas_por_categoria",label:"Despesas por categoria"},{value:"receitas_por_categoria",label:"Receitas por categoria"}],[u.VIEWS.ANNUAL_CATEGORY]:[{value:"despesas_anuais_por_categoria",label:"Despesas anuais por categoria"},{value:"receitas_anuais_por_categoria",label:"Receitas anuais por categoria"}]},M={ACTIVE_SECTION:"rel_active_section",ACTIVE_VIEW:"rel_active_view",CATEGORY_TYPE:"rel_category_type",ANNUAL_CATEGORY_TYPE:"rel_annual_category_type"},ce={overview:{kicker:"Painel consolidado",title:"Leia seu mes com contexto",description:"Veja seu pulso financeiro, identifique sinais importantes e acompanhe a evolucao do periodo em um resumo rapido."},relatorios:{kicker:"Relatorio ativo",title:"Transforme lancamentos em decisao",description:"Explore seus numeros por categoria, conta, cartao e evolucao para descobrir onde agir."},insights:{kicker:"Leitura automatica",title:"Insights que ajudam a agir",description:"Receba sinais claros sobre gastos, saldo, concentracoes e oportunidades sem precisar interpretar tudo manualmente."},comparativos:{kicker:"Comparacao temporal",title:"Compare e ajuste sua rota",description:"Entenda o que melhorou, piorou ou estagnou em relacao ao mes e ao ano anteriores."}},le={[u.VIEWS.CATEGORY]:{title:"Categorias do periodo",description:"Encontre rapidamente onde seu dinheiro esta concentrado por categoria."},[u.VIEWS.BALANCE]:{title:"Saldo diario",description:"Acompanhe como seu caixa evolui ao longo do periodo."},[u.VIEWS.COMPARISON]:{title:"Receitas x despesas",description:"Compare entradas e saidas para entender pressao ou folga no caixa."},[u.VIEWS.ACCOUNTS]:{title:"Desempenho por conta",description:"Descubra quais contas concentram mais entradas e saidas."},[u.VIEWS.CARDS]:{title:"Saude dos cartoes",description:"Monitore faturas, uso de limite e sinais de atencao nos cartoes."},[u.VIEWS.EVOLUTION]:{title:"Evolucao em 12 meses",description:"Observe tendencia, sazonalidade e ritmo financeiro ao longo do ultimo ano."},[u.VIEWS.ANNUAL_SUMMARY]:{title:"Resumo anual",description:"Compare mes a mes como receitas, despesas e saldo se comportaram no ano."},[u.VIEWS.ANNUAL_CATEGORY]:{title:"Categorias do ano",description:"Veja quais categorias dominaram seu ano e onde houve maior concentracao."}};function ge(){const e=new Date;return`${e.getFullYear()}-${String(e.getMonth()+1).padStart(2,"0")}`}const f=e=>String(e??"").replace(/[&<>"']/g,function(t){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[t]??t});function Ue(e,t="#cccccc"){return/^#[0-9A-Fa-f]{6}$/.test(e)?e:t}const i={activeSection:"overview",currentView:u.VIEWS.CATEGORY,categoryType:"despesas_por_categoria",annualCategoryType:"despesas_anuais_por_categoria",currentMonth:ge(),currentAccount:null,chart:null,accounts:[],accessRestricted:!1,lastReportError:null,activeDrilldown:null,reportDetails:null},x={getCurrentMonth:ge,formatCurrency(e){return Ie(e)},formatMonthLabel(e){const[t,a]=e.split("-");return new Date(t,a-1).toLocaleDateString("pt-BR",{month:"long",year:"numeric"})},addMonths(e,t){const[a,s]=e.split("-").map(Number),r=new Date(a,s-1+t);return`${r.getFullYear()}-${String(r.getMonth()+1).padStart(2,"0")}`},hexToRgba(e,t=.25){const a=parseInt(e.slice(1,3),16),s=parseInt(e.slice(3,5),16),r=parseInt(e.slice(5,7),16);return`rgba(${a}, ${s}, ${r}, ${t})`},generateShades(e,t){const a=parseInt(e.slice(1,3),16),s=parseInt(e.slice(3,5),16),r=parseInt(e.slice(5,7),16),o=[];for(let n=0;n<t;n++){const l=.35-n/Math.max(t-1,1)*.7,c=h=>Math.min(255,Math.max(0,Math.round(h+(l>0?(255-h)*l:h*l)))),d=c(a),m=c(s),p=c(r);o.push(`#${d.toString(16).padStart(2,"0")}${m.toString(16).padStart(2,"0")}${p.toString(16).padStart(2,"0")}`)}return o},isYearlyView(e=i.currentView){return Fe.has(e)},extractFilename(e){if(!e)return null;const t=/filename\*=UTF-8''([^;]+)/i.exec(e);if(t)try{return decodeURIComponent(t[1])}catch{return t[1]}const a=/filename="?([^";]+)"?/i.exec(e);return a?a[1]:null},getCssVar(e,t=""){try{return(getComputedStyle(document.documentElement).getPropertyValue(e)||"").trim()||t}catch{return t}},isLightTheme(){try{return(document.documentElement?.getAttribute("data-theme")||"dark")==="light"}catch{return!1}},getReportType(){return{[u.VIEWS.CATEGORY]:i.categoryType,[u.VIEWS.ANNUAL_CATEGORY]:i.annualCategoryType,[u.VIEWS.BALANCE]:"saldo_mensal",[u.VIEWS.COMPARISON]:"receitas_despesas_diario",[u.VIEWS.ACCOUNTS]:"receitas_despesas_por_conta",[u.VIEWS.CARDS]:"cartoes_credito",[u.VIEWS.EVOLUTION]:"evolucao_12m",[u.VIEWS.ANNUAL_SUMMARY]:"resumo_anual"}[i.currentView]??i.categoryType},getActiveCategoryType(){return i.currentView===u.VIEWS.ANNUAL_CATEGORY?i.annualCategoryType:i.categoryType}},L={},_=e=>x.formatCurrency(e),U=e=>String(e??"").replace(/[&<>"']/g,t=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"})[t]||t);function H(){const e=(document.documentElement.getAttribute("data-theme")||"").toLowerCase()==="light"||x.isLightTheme?.();return{isLight:e,mode:e?"light":"dark",textColor:e?"#2c3e50":"#ffffff",textMuted:e?"#6c757d":"rgba(255, 255, 255, 0.7)",gridColor:e?"rgba(0, 0, 0, 0.08)":"rgba(255, 255, 255, 0.05)",surfaceColor:getComputedStyle(document.documentElement).getPropertyValue("--color-surface").trim()}}function Q(e=[]){return e.map(t=>{const a=Number(t);return Number.isFinite(a)?a:0})}function de(e,t=380){const a=e?.closest(".chart-container")||e?.parentElement,s=a?getComputedStyle(a):null,r=s?Number.parseFloat(s.height):Number.NaN,o=s?Number.parseFloat(s.minHeight):Number.NaN,n=a?.getBoundingClientRect?.().height??Number.NaN,l=s?(Number.parseFloat(s.paddingTop)||0)+(Number.parseFloat(s.paddingBottom)||0):0,c=window.innerWidth<768?320:t,d=[r,n,o,c].find(m=>Number.isFinite(m)&&m>0)??c;return Math.max(260,Math.round(d-l))}const v={_currentEntries:null,destroy(){i.chart&&(Array.isArray(i.chart)?i.chart.forEach(e=>e?.destroy()):i.chart.destroy(),i.chart=null),v._drilldownChart&&(v._drilldownChart.destroy(),v._drilldownChart=null),i.activeDrilldown=null,i.reportDetails=null},setupDefaults(){const e=getComputedStyle(document.documentElement).getPropertyValue("--color-text").trim();window.Apex=window.Apex||{},window.Apex.chart={foreColor:e,fontFamily:"Inter, Arial, sans-serif"},window.Apex.grid={borderColor:"rgba(255, 255, 255, 0.1)"}},renderPie(e){const{labels:t=[],values:a=[],details:s=null,cat_ids:r=null}=e;if(!t.length||!a.some(y=>y>0))return L.UI.showEmptyState();let o=t.map((y,C)=>({label:y,value:Number(a[C])||0,color:u.CHART_COLORS[C%u.CHART_COLORS.length],catId:r?r[C]??null:null})).filter(y=>y.value>0).sort((y,C)=>C.value-y.value);!r&&s&&(o=o.map(y=>{const C=s.find(k=>k.label===y.label);return{...y,catId:C?.cat_id??null}}));const n=window.innerWidth<768;let l=o;if(n&&o.length>5){const y=o.slice(0,5),k=o.slice(5).reduce((V,j)=>V+j.value,0);l=[...y,{label:"Outros",value:k,color:"#95a5a6",isOthers:!0}]}const c=!n&&l.length>2,d=c?Math.ceil(l.length/2):l.length,m=c?[l.slice(0,d),l.slice(d)].filter(y=>y.length):[l],p=`
            <div class="chart-container chart-container-pie">
                <div class="chart-dual">
                    ${m.map((y,C)=>`
                        <div class="chart-wrapper chart-wrapper-pie">
                            <div id="chart${C}"></div>
                        </div>
                    `).join("")}
                </div>
            </div>
            <div id="subcategoryDrilldown" class="drilldown-panel" aria-hidden="true"></div>
            ${n?'<div id="categoryListMobile" class="category-list-mobile"></div>':""}
        `;L.UI.setContent(p),v.destroy(),i.reportDetails=s,i.activeDrilldown=null,v._currentEntries=l;const h=x.getActiveCategoryType(),A={receitas_por_categoria:"Receitas por Categoria",despesas_por_categoria:"Despesas por Categoria",receitas_anuais_por_categoria:"Receitas anuais por Categoria",despesas_anuais_por_categoria:"Despesas anuais por Categoria"}[h]||"Distribuição por Categoria",g=H();let T=0;i.chart=m.map((y,C)=>{const k=document.getElementById(`chart${C}`);if(!k)return null;const V=y.reduce((S,F)=>S+F.value,0),j=T;T+=y.length;const re=new ApexCharts(k,{chart:{type:"donut",height:"100%",background:"transparent",fontFamily:"Inter, Arial, sans-serif",events:{dataPointSelection:(S,F,oe)=>{const ne=j+oe.dataPointIndex,K=l[ne];!K||K.isOthers||v.handlePieClick(K,ne,oe.dataPointIndex,C)},dataPointMouseEnter:S=>{S.target&&(S.target.style.cursor="pointer")},dataPointMouseLeave:S=>{S.target&&(S.target.style.cursor="default")}}},series:y.map(S=>S.value),labels:y.map(S=>S.label),colors:y.map(S=>S.color),stroke:{width:2,colors:[g.surfaceColor]},plotOptions:{pie:{donut:{size:"60%"},expandOnClick:!0}},legend:{show:!n,position:"bottom",labels:{colors:g.textColor},markers:{shape:"circle"}},title:{text:m.length>1?`${A} - Parte ${C+1}`:A,align:"center",style:{fontSize:"14px",fontWeight:"bold",color:g.textColor}},tooltip:{theme:g.mode,y:{formatter:S=>{const F=V>0?(S/V*100).toFixed(1):"0";return`${_(S)} (${F}%)`}}},dataLabels:{enabled:!1},theme:{mode:g.mode}});return re.render(),re}),n&&v.renderMobileCategoryList(l)},renderMobileCategoryList(e){const t=document.getElementById("categoryListMobile");if(!t)return;const a=e.reduce((o,n)=>o+n.value,0),s=!!i.reportDetails&&window.IS_PRO,r=e.map((o,n)=>{const l=(o.value/a*100).toFixed(1),c=s&&o.catId!=null?i.reportDetails.find(h=>h.cat_id===o.catId):null,d=c&&c.subcategories&&c.subcategories.filter(h=>h.id!==0).length>0,m=d?'<i data-lucide="chevron-down" class="category-chevron"></i>':"";let p="";if(d){const h=x.generateShades(o.color,c.subcategories.length);p=`
                    <div class="category-subcats-panel" id="mobileSubcatPanel-${n}" aria-hidden="true">
                        ${c.subcategories.map((w,A)=>{const g=c.total>0?(w.total/c.total*100).toFixed(1):"0.0";return`
                                <div class="drilldown-item drilldown-item-mobile">
                                    <div class="drilldown-indicator" style="background-color: ${h[A]}"></div>
                                    <div class="drilldown-info">
                                        <span class="drilldown-name">${U(w.label)}</span>
                                    </div>
                                    <div class="drilldown-values">
                                        <span class="drilldown-value">${_(w.total)}</span>
                                        <span class="drilldown-pct">${g}%</span>
                                    </div>
                                </div>
                            `}).join("")}
                    </div>
                `}return`
                <div class="category-item ${d?"has-subcats":""}"
                     ${d?`data-subcat-toggle="${n}"`:""}>
                    <div class="category-indicator" style="background-color: ${o.color}"></div>
                    <div class="category-info">
                        <span class="category-name">${U(o.label)}</span>
                        <span class="category-value">${_(o.value)}</span>
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
                ${r}
            </div>
            ${s?"":`<p class="category-info-text">
                <i data-lucide="info"></i>
                Para visualizar todas as categorias detalhadamente, exporte este relatório em PDF.
            </p>`}
        `,window.lucide&&lucide.createIcons(),v.setupExpandToggle(),s&&v.setupMobileSubcatToggles()},setupMobileSubcatToggles(){document.querySelectorAll("[data-subcat-toggle]").forEach(e=>{e.addEventListener("click",function(){const t=this.dataset.subcatToggle,a=document.getElementById(`mobileSubcatPanel-${t}`),s=this.querySelector(".category-chevron");if(!a)return;a.getAttribute("aria-hidden")==="false"?(a.style.maxHeight="0px",a.setAttribute("aria-hidden","true"),s&&(s.style.transform="rotate(0deg)")):(a.style.maxHeight=a.scrollHeight+"px",a.setAttribute("aria-hidden","false"),s&&(s.style.transform="rotate(180deg)"))})})},setupExpandToggle(){const e=document.getElementById("expandCategoriesBtn"),t=document.getElementById("expandableCard");!e||!t||e.addEventListener("click",function(){e.getAttribute("aria-expanded")==="true"?(t.style.maxHeight="0px",t.setAttribute("aria-hidden","true"),e.setAttribute("aria-expanded","false"),e.querySelector("span").textContent="Ver todas as categorias",e.querySelector("i").style.transform="rotate(0deg)"):(t.style.maxHeight=t.scrollHeight+"px",t.setAttribute("aria-hidden","false"),e.setAttribute("aria-expanded","true"),e.querySelector("span").textContent="Ocultar categorias",e.querySelector("i").style.transform="rotate(180deg)")})},handlePieClick(e,t,a,s){if(!window.IS_PRO){window.PlanLimits?.promptUpgrade?window.PlanLimits.promptUpgrade({context:"relatorios",message:"O detalhamento por subcategorias é exclusivo do plano Pro."}).catch(()=>{}):window.LKFeedback?.upgradePrompt?window.LKFeedback.upgradePrompt({context:"relatorios",message:"O detalhamento por subcategorias é exclusivo do plano Pro."}).catch(()=>{}):window.Swal?.fire&&Swal.fire({icon:"info",title:"Recurso Premium",html:"O detalhamento por <b>subcategorias</b> é exclusivo do <b>plano Pro</b>.<br>Faça upgrade para desbloquear!",confirmButtonText:"Fazer Upgrade",showCancelButton:!0,cancelButtonText:"Agora não",confirmButtonColor:"#f59e0b",cancelButtonColor:"#64748b"}).then(l=>{l.isConfirmed&&(window.location.href=(u.BASE_URL||"/")+"billing")});return}if(!i.reportDetails)return;const r=e.catId,o=i.reportDetails.find(l=>l.cat_id===r);if(!o||!o.subcategories||o.subcategories.length===0)return;if(o.subcategories.filter(l=>l.id!==0).length===0){window.Swal?.fire&&Swal.fire({icon:"info",title:"Sem subcategorias",text:"Atribua subcategorias aos seus lançamentos para ver o detalhamento desta categoria.",confirmButtonText:"Entendi",confirmButtonColor:"#f59e0b",timer:5e3,timerProgressBar:!0});return}if(i.activeDrilldown===r){v.closeDrilldown();return}i.activeDrilldown=r,v.renderSubcategoryDrilldown(o,e.color)},closeDrilldown(){i.activeDrilldown=null;const e=document.getElementById("subcategoryDrilldown");e&&(e.style.maxHeight="0px",e.setAttribute("aria-hidden","true"),setTimeout(()=>{e.innerHTML=""},400))},renderSubcategoryDrilldown(e,t){const a=document.getElementById("subcategoryDrilldown");if(!a)return;const{label:s,total:r,subcategories:o}=e,n=x.generateShades(t,o.length),l=o.map((m,p)=>{const h=r>0?(m.total/r*100).toFixed(1):"0.0",w=r>0?(m.total/r*100).toFixed(0):"0";return`
                <div class="drilldown-item" style="animation-delay: ${p*.05}s">
                    <div class="drilldown-indicator" style="background-color: ${n[p]}"></div>
                    <div class="drilldown-info">
                        <span class="drilldown-name">${U(m.label)}</span>
                        <div class="drilldown-bar-bg">
                            <div class="drilldown-bar" style="width: ${w}%; background-color: ${n[p]}"></div>
                        </div>
                    </div>
                    <div class="drilldown-values">
                        <span class="drilldown-value">${_(m.total)}</span>
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
                    <span class="drilldown-total">${_(r)}</span>
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
        `,a.setAttribute("aria-hidden","false"),requestAnimationFrame(()=>{a.style.maxHeight=a.scrollHeight+"px"}),document.getElementById("drilldownCloseBtn")?.addEventListener("click",()=>{v.closeDrilldown()}),c||v._renderDrilldownMiniChart(o,n),window.lucide&&lucide.createIcons()},_renderDrilldownMiniChart(e,t){const a=document.getElementById("drilldownMiniChart");if(!a)return;v._drilldownChart&&(v._drilldownChart.destroy(),v._drilldownChart=null);const s=H(),r=e.reduce((o,n)=>o+n.total,0);v._drilldownChart=new ApexCharts(a,{chart:{type:"donut",height:"100%",background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:e.map(o=>o.total),labels:e.map(o=>o.label),colors:t,stroke:{width:2,colors:[s.surfaceColor]},plotOptions:{pie:{donut:{size:"55%"}}},legend:{show:!1},tooltip:{theme:s.mode,y:{formatter:o=>{const n=r>0?(o/r*100).toFixed(1):"0";return`${_(o)} (${n}%)`}}},dataLabels:{enabled:!1},theme:{mode:s.mode}}),v._drilldownChart.render()},_drilldownChart:null,renderLine(e){const{labels:t=[],values:a=[]}={...e,values:Q(e?.values)};if(!t.length)return L.UI.showEmptyState();L.UI.setContent(`
            <div class="chart-container chart-container-line">
                <div class="chart-wrapper chart-wrapper-line">
                    <div id="chart0"></div>
                </div>
            </div>
        `),v.destroy();const s=getComputedStyle(document.documentElement).getPropertyValue("--color-primary").trim(),r=H(),o=document.getElementById("chart0"),n=de(o,420),l=new ApexCharts(o,{chart:{type:"area",height:n,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,redrawOnParentResize:!0,redrawOnWindowResize:!0},series:[{name:"Saldo Diário",data:a.map(Number)}],xaxis:{categories:t,labels:{style:{fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:r.isLight?"#000":"#fff",fontSize:"11px"},formatter:c=>_(c)}},colors:[s],stroke:{curve:"smooth",width:2.5},fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.4,opacityTo:.05,stops:[0,100]}},markers:{size:4,hover:{size:6}},grid:{borderColor:r.gridColor,strokeDashArray:4},tooltip:{theme:r.mode,y:{formatter:c=>_(c)}},legend:{position:"bottom",labels:{colors:r.textColor}},title:{text:"Evolução do Saldo Mensal",align:"center",style:{fontSize:"16px",fontWeight:"bold",color:r.textColor}},dataLabels:{enabled:!1},theme:{mode:r.mode}});l.render(),i.chart=l},renderBar(e){const{labels:t=[],receitas:a=[],despesas:s=[]}={...e,receitas:Q(e?.receitas),despesas:Q(e?.despesas)};if(!t.length)return L.UI.showEmptyState();L.UI.setContent(`
            <div class="chart-container chart-container-bar">
                <div class="chart-wrapper chart-wrapper-bar">
                    <div id="chart0"></div>
                </div>
            </div>
        `),v.destroy();const r=x.getCssVar("--color-success","#2ecc71"),o=x.getCssVar("--color-danger","#e74c3c"),n=H(),l=document.getElementById("chart0"),c=de(l,420),d=i.currentView===u.VIEWS.ACCOUNTS?"Receitas x Despesas por Conta":i.currentView===u.VIEWS.ANNUAL_SUMMARY?"Resumo Anual por Mês":"Receitas x Despesas",m=new ApexCharts(l,{chart:{type:"bar",height:c,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,redrawOnParentResize:!0,redrawOnWindowResize:!0},series:[{name:"Receitas",data:a.map(Number)},{name:"Despesas",data:s.map(Number)}],xaxis:{categories:t,labels:{style:{colors:n.textMuted,fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:n.isLight?"#000":"#fff",fontSize:"11px"},formatter:p=>_(p)}},colors:[r,o],plotOptions:{bar:{borderRadius:6,columnWidth:"55%"}},grid:{borderColor:n.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:n.mode,shared:!0,intersect:!1,y:{formatter:p=>_(p)}},legend:{position:"bottom",labels:{colors:n.textColor},markers:{shape:"circle"}},title:{text:d,align:"center",style:{fontSize:"16px",fontWeight:"bold",color:n.textColor}},dataLabels:{enabled:!1},theme:{mode:n.mode}});m.render(),i.chart=m}};L.ChartManager=v;const He={toggleRelQuickStats:"relQuickStats",toggleRelOverviewCharts:"relOverviewChartsRow",toggleRelControls:"relControlsRow"},ve={toggleRelQuickStats:!0,toggleRelOverviewCharts:!0,toggleRelControls:!0},We={...ve,toggleRelQuickStats:!1,toggleRelControls:!1};async function Ye(){return Le("relatorios")}async function qe(e){await _e("relatorios",e)}const ze=$e({storageKey:"lk_relatorios_prefs",sectionMap:He,completeDefaults:ve,essentialDefaults:We,loadPreferences:Ye,savePreferences:qe,modal:{overlayId:"relatoriosCustomizeModalOverlay",openButtonId:"btnCustomizeRelatorios",closeButtonId:"btnCloseCustomizeRelatorios",saveButtonId:"btnSaveCustomizeRelatorios",presetEssentialButtonId:"btnPresetEssencialRelatorios",presetCompleteButtonId:"btnPresetCompletoRelatorios"}});function Ge(){ze.init()}const b=e=>x.formatCurrency(e);function W(e,t,a,s=!1){const r=document.getElementById(e);if(!r)return;if(!a||a===0){r.innerHTML="",r.className="stat-trend";return}const o=(t-a)/Math.abs(a)*100,n=Math.abs(o).toFixed(1);if(Math.abs(o)<.5)r.className="stat-trend trend-neutral",r.textContent="— Sem alteração";else{const l=o>0,c=s?!l:l;r.className=`stat-trend ${c?"trend-positive":"trend-negative"}`;const d=l?"↑":"↓";r.textContent=`${d} ${n}% vs mês anterior`}}function je(e){const t=document.querySelector(".chart-insight-line");if(t&&t.remove(),!e)return;let a="";switch(i.currentView){case u.VIEWS.CATEGORY:case u.VIEWS.ANNUAL_CATEGORY:{if(!e.labels||!e.values||e.values.length===0)break;const n=e.values.reduce((l,c)=>l+Number(c),0);if(n>0){const l=e.values.reduce((d,m,p,h)=>Number(m)>Number(h[d])?p:d,0),c=(Number(e.values[l])/n*100).toFixed(0);a=`${e.labels[l]} lidera com ${c}% dos gastos (${b(e.values[l])})`}break}case u.VIEWS.BALANCE:{if(!e.labels||!e.values||e.values.length===0)break;const n=e.values.map(Number),l=Math.min(...n),c=n.indexOf(l);a=`Menor saldo: ${b(l)} em ${e.labels[c]}`;break}case u.VIEWS.COMPARISON:{if(!e.receitas||!e.despesas)break;const n=e.receitas.map(Number),l=e.despesas.map(Number);a=`Em ${n.filter((d,m)=>d>(l[m]||0)).length} de ${n.length} dias, receitas superaram despesas`;break}case u.VIEWS.ACCOUNTS:{if(!e.labels||!e.despesas||e.despesas.length===0)break;const n=e.despesas.map(Number),l=n.reduce((c,d,m,p)=>d>p[c]?m:c,0);a=`Maior gasto: ${e.labels[l]} com ${b(n[l])} em despesas`;break}case u.VIEWS.EVOLUTION:{if(!e.values||e.values.length<2)break;const n=e.values.map(Number),l=n[0],c=n[n.length-1];a=`Evolução nos últimos 12 meses: ${c>l?"tendência de alta":c<l?"tendência de queda":"estável"}`;break}case u.VIEWS.ANNUAL_SUMMARY:{if(!e.labels||!e.receitas||e.receitas.length===0)break;const n=e.receitas.map(Number),l=e.despesas.map(Number),c=n.map((p,h)=>p-(l[h]||0)),d=c.reduce((p,h,w,A)=>h>A[p]?w:p,0),m=c.reduce((p,h,w,A)=>h<A[p]?w:p,0);a=`Melhor mês: ${e.labels[d]}. Pior mês: ${e.labels[m]}`;break}}if(!a)return;const r=document.getElementById("reportArea");if(!r)return;const o=document.createElement("div");o.className="chart-insight-line",o.innerHTML=`<i data-lucide="sparkles"></i> <span>${f(a)}</span>`,r.appendChild(o),window.lucide&&lucide.createIcons()}function Ke(e){return!e||e.length===0?"":`
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
                ${e.map((a,s)=>{const r=a.variacao>0?"trend-negative":a.variacao<0?"trend-positive":"trend-neutral",o=a.variacao>0?"arrow-up":a.variacao<0?"arrow-down":"equal",n=Math.abs(a.variacao)<.1?"Sem alteração":`${a.variacao>0?"+":""}${a.variacao.toFixed(1)}%`,l=e.reduce((m,p)=>m+p.atual,0),c=l>0?(a.atual/l*100).toFixed(0):0;let d="";return a.subcategorias&&a.subcategorias.length>0&&(d=`<div class="cat-comp-subcats">${a.subcategorias.map(p=>{const h=p.variacao>0?"trend-negative":p.variacao<0?"trend-positive":"",w=Math.abs(p.variacao)<.1?"":`<span class="subcat-trend ${h}">${p.variacao>0?"↑":"↓"}${Math.abs(p.variacao).toFixed(0)}%</span>`;return`
                    <span class="cat-comp-subcat-pill">
                        ${f(p.nome)}
                        <span class="subcat-value">${b(p.atual)}</span>
                        ${w}
                    </span>
                `}).join("")}</div>`),`
            <div class="cat-comp-row" style="animation-delay: ${s*.06}s">
                <div class="cat-comp-rank">${s+1}</div>
                <div class="cat-comp-info">
                    <span class="cat-comp-name">${f(a.nome)}</span>
                    <div class="cat-comp-bar-bg">
                        <div class="cat-comp-bar" style="width: ${c}%"></div>
                    </div>
                    ${d}
                </div>
                <div class="cat-comp-values">
                    <span class="cat-comp-current">${b(a.atual)}</span>
                    <span class="cat-comp-prev">${b(a.anterior)}</span>
                </div>
                <div class="cat-comp-trend ${r}">
                    <i data-lucide="${o}"></i>
                    <span>${n}</span>
                </div>
            </div>
        `}).join("")}
            </div>
        </div>
    `}function Qe(e){return!e||e.length===0?"":`
        <div class="comparative-card comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3><i data-lucide="line-chart"></i> Evolução dos Últimos 6 Meses</h3>
                <span class="comp-subtitle">Receitas, despesas e saldo ao longo do tempo</span>
            </div>
            <div class="evolucao-chart-wrapper">
                <div id="evolucaoMiniChart" style="min-height:220px;"></div>
            </div>
        </div>
    `}let B=null;function Ze(e){if(!e||e.length===0)return;const t=document.getElementById("evolucaoMiniChart");if(!t)return;const a=e.map(l=>l.label),r=getComputedStyle(document.documentElement).getPropertyValue("--color-text-muted").trim()||"#999",n=document.documentElement.getAttribute("data-theme")==="dark"?"dark":"light";B&&(B.destroy(),B=null),B=new ApexCharts(t,{chart:{type:"line",height:260,stacked:!1,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:[{name:"Receitas",type:"column",data:e.map(l=>l.receitas)},{name:"Despesas",type:"column",data:e.map(l=>l.despesas)},{name:"Saldo",type:"area",data:e.map(l=>l.saldo)}],xaxis:{categories:a,labels:{style:{colors:r}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:r},formatter:l=>b(l)}},colors:["rgba(46, 204, 113, 0.85)","rgba(231, 76, 60, 0.85)","#3498db"],stroke:{width:[0,0,2.5],curve:"smooth"},fill:{opacity:[.85,.85,.1]},plotOptions:{bar:{borderRadius:6,columnWidth:"55%"}},grid:{borderColor:"rgba(128,128,128,0.1)",strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:n,shared:!0,intersect:!1,y:{formatter:l=>b(l)}},legend:{position:"bottom",labels:{colors:r},markers:{shape:"circle"}},dataLabels:{enabled:!1},theme:{mode:n}}),B.render()}function Je(e){if(!e)return"";const t=e.variacao>0?"trend-negative":e.variacao<0?"trend-positive":"trend-neutral",a=e.variacao>0?"arrow-up":e.variacao<0?"arrow-down":"equal";return`
        <div class="comparative-card comp-mini-card surface-card surface-card--interactive">
            <div class="comp-mini-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i data-lucide="calendar-clock"></i>
            </div>
            <div class="comp-mini-body">
                <span class="comp-mini-label">Média Diária de Gastos</span>
                <div class="comp-mini-values">
                    <span class="comp-mini-current">${b(e.atual)}/dia</span>
                    <span class="comp-mini-prev">anterior: ${b(e.anterior)}/dia</span>
                </div>
                <div class="comp-mini-trend ${t}">
                    <i data-lucide="${a}"></i>
                    <span>${Math.abs(e.variacao).toFixed(1)}%</span>
                </div>
            </div>
        </div>
    `}function Xe(e){if(!e)return"";const t=e.atual>=0,a=e.diferenca>0?"trend-positive":e.diferenca<0?"trend-negative":"trend-neutral",s=e.diferenca>0?"arrow-up":e.diferenca<0?"arrow-down":"equal";return`
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
    `}function et(e){if(!e||e.length===0)return"";const t={Pix:"zap","Cartão de Crédito":"credit-card","Cartão de Débito":"credit-card",Dinheiro:"banknote",Boleto:"file-text",Depósito:"landmark",Transferência:"arrow-right-left",Estorno:"undo-2"},a=e.reduce((r,o)=>r+o.atual,0);return`
        <div class="comparative-card comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3><i data-lucide="wallet"></i> Formas de Pagamento</h3>
                <span class="comp-subtitle">Distribuição mês atual vs anterior</span>
            </div>
            <div class="forma-comp-list">
                ${e.map((r,o)=>{const n=a>0?(r.atual/a*100).toFixed(0):0,l=t[r.nome]||"wallet";return`
            <div class="forma-comp-row" style="animation-delay: ${o*.06}s">
                <div class="forma-comp-icon"><i data-lucide="${l}"></i></div>
                <div class="forma-comp-info">
                    <span class="forma-comp-name">${f(r.nome)}</span>
                    <div class="forma-comp-bar-bg">
                        <div class="forma-comp-bar" style="width: ${n}%"></div>
                    </div>
                </div>
                <div class="forma-comp-values">
                    <span class="forma-comp-current">${b(r.atual)} <small>(${r.atual_qtd}x)</small></span>
                    <span class="forma-comp-prev">${b(r.anterior)} <small>(${r.anterior_qtd}x)</small></span>
                </div>
            </div>
        `}).join("")}
            </div>
        </div>
    `}function ue(e,t,a){const s=c=>c>0?'<i data-lucide="arrow-up"></i>':c<0?'<i data-lucide="arrow-down"></i>':'<i data-lucide="equal"></i>',r=(c,d=!1)=>{if(d){if(c>0)return"trend-negative";if(c<0)return"trend-positive"}else{if(c>0)return"trend-positive";if(c<0)return"trend-negative"}return"trend-neutral"},o=c=>Math.abs(c)<.1?"Sem alteração":c>0?`Aumentou ${Math.abs(c).toFixed(1)}%`:c<0?`Reduziu ${Math.abs(c).toFixed(1)}%`:"Sem alteração",n=()=>{if(a.includes("mês")){const[c,d]=i.currentMonth.split("-");return new Date(c,d-1).toLocaleDateString("pt-BR",{month:"short",year:"numeric"})}else return i.currentMonth.split("-")[0]},l=()=>{if(a.includes("mês")){const[c,d]=i.currentMonth.split("-");return new Date(c,d-2).toLocaleDateString("pt-BR",{month:"short",year:"numeric"})}else return(parseInt(i.currentMonth.split("-")[0])-1).toString()};return`
        <div class="comparative-card surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3>${f(e)}</h3>
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
                            <span class="value-amount">${b(t.current.receitas)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${b(t.previous.receitas)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${r(t.variation.receitas,!1)}">
                        ${s(t.variation.receitas)}
                        <span>${o(t.variation.receitas)}</span>
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
                            <span class="value-amount">${b(t.current.despesas)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${b(t.previous.despesas)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${r(t.variation.despesas,!0)}">
                        ${s(t.variation.despesas)}
                        <span>${o(t.variation.despesas)}</span>
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
                            <span class="value-amount">${b(t.current.saldo)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${b(t.previous.saldo)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${r(t.variation.saldo,!1)}">
                        ${s(t.variation.saldo)}
                        <span>${o(t.variation.saldo)}</span>
                    </div>
                </div>
            </div>
        </div>
    `}function tt(e){const t=document.getElementById("reportArea");if(!t)return;const a=e.resumo_consolidado&&e.cards&&e.cards.length>0?`
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
                        <span class="stat-value">${b(e.resumo_consolidado.total_faturas)}</span>
                    </div>
                </div>
                
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i data-lucide="wallet" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Limite Total</span>
                        <span class="stat-value">${b(e.resumo_consolidado.total_limites)}</span>
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
                        <span class="stat-value">${b(e.resumo_consolidado.total_disponivel)}</span>
                    </div>
                </div>
            </div>
            
            ${e.resumo_consolidado.melhor_cartao||e.resumo_consolidado.requer_atencao?`
                <div class="summary-insights">
                    ${e.resumo_consolidado.melhor_cartao?`
                        <div class="insight-item success">
                            <i data-lucide="star"></i>
                            <span><strong>Melhor cartão:</strong> ${f(e.resumo_consolidado.melhor_cartao.nome)} (${e.resumo_consolidado.melhor_cartao.percentual.toFixed(1)}% de uso)</span>
                        </div>
                    `:""}
                    ${e.resumo_consolidado.requer_atencao?`
                        <div class="insight-item warning">
                            <i data-lucide="triangle-alert"></i>
                            <span><strong>Requer atenção:</strong> ${f(e.resumo_consolidado.requer_atencao.nome)} (${e.resumo_consolidado.requer_atencao.percentual.toFixed(1)}% de uso)</span>
                        </div>
                    `:""}
                    ${e.resumo_consolidado.total_parcelamentos>0?`
                        <div class="insight-item info">
                            <i data-lucide="calendar-check"></i>
                            <span><strong>${e.resumo_consolidado.total_parcelamentos} parcelamento${e.resumo_consolidado.total_parcelamentos>1?"s":""}</strong> comprometendo ${b(e.resumo_consolidado.valor_parcelamentos)}</span>
                        </div>
                    `:""}
                </div>
            `:""}
        </div>
    `:"";t.innerHTML=`
        <div class="cards-report-container">
            ${a}
            
            <div class="cards-grid">
                ${e.cards&&e.cards.length>0?e.cards.map(s=>{const r=Ue(s.cor,"#E67E22");return`
                    <div class="card-item surface-card surface-card--interactive surface-card--clip ${s.status_saude.status}"
                         style="--card-color: ${r}; cursor: pointer;"
                         data-card-id="${s.id||""}"
                         data-card-month="${i.currentMonth}"
                         data-action="open-card-detail"
                         role="button"
                         tabindex="0">
                        <div class="card-header-gradient">
                            <div class="card-brand">
                                <div class="card-icon-wrapper" style="background: linear-gradient(135deg, ${r}, ${r}99);">
                                    <i data-lucide="credit-card" style="color: white"></i>
                                </div>
                                <div class="card-info">
                                    <h3 class="card-name">${f(s.nome)}</h3>
                                    <div class="card-meta">
                                        ${s.conta?`<span class="card-account"><i data-lucide="landmark"></i> ${f(s.conta)}</span>`:""}
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
                                ${s.alertas.map(o=>`
                                    <span class="alert-badge alert-${o.type}">
                                        <i data-lucide="${o.type==="danger"?"triangle-alert":o.type==="warning"?"circle-alert":"info"}"></i>
                                        ${f(o.message)}
                                    </span>
                                `).join("")}
                            </div>
                        `:""}


                        <div class="card-balance">
                            <div class="balance-main">
                                <span class="balance-label">FATURA DO MÊS</span>
                                <span class="balance-value">${b(s.fatura_atual||0)}</span>
                                ${s.media_historica>0&&Math.abs(s.fatura_atual-s.media_historica)>1?`
                                    <span class="balance-comparison">
                                        ${s.fatura_atual>s.media_historica?"↑":"↓"} ${(Math.abs(s.fatura_atual-s.media_historica)/s.media_historica*100).toFixed(0)}% vs média
                                    </span>
                                `:""}
                            </div>
                            <div class="balance-grid">
                                <div class="balance-item">
                                    <span class="balance-small-label">Limite</span>
                                    <span class="balance-small-value">${b(s.limite||0)}</span>
                                </div>
                                <div class="balance-item">
                                    <span class="balance-small-label">Disponível</span>
                                    <span class="balance-small-value">${b(s.disponivel||0)}</span>
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

                        ${s.parcelamentos&&s.parcelamentos.ativos>0||s.proximos_meses&&s.proximos_meses.length>0&&s.proximos_meses.some(o=>o.valor>0)?`
                            <div class="card-quick-info">
                                ${s.parcelamentos&&s.parcelamentos.ativos>0?`
                                    <div class="quick-info-item">
                                        <i data-lucide="calendar-check"></i>
                                        <span>${s.parcelamentos.ativos} parcelamento${s.parcelamentos.ativos>1?"s":""}</span>
                                    </div>
                                `:""}
                                ${s.proximos_meses&&s.proximos_meses.length>0&&s.proximos_meses.some(o=>o.valor>0)?`
                                    <div class="quick-info-item">
                                        <i data-lucide="line-chart"></i>
                                        <span>Próximo: ${b(s.proximos_meses.find(o=>o.valor>0)?.valor||0)}</span>
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
    `,window.lucide&&lucide.createIcons()}const I=e=>x.formatCurrency(e),fe={"arrow-trend-up":"trending-up","arrow-trend-down":"trending-down","arrow-up":"arrow-up","arrow-down":"arrow-down","chart-line":"line-chart","chart-pie":"pie-chart","exclamation-triangle":"triangle-alert","exclamation-circle":"circle-alert","check-circle":"circle-check","info-circle":"info",lightbulb:"lightbulb",star:"star",bolt:"zap",wallet:"wallet","credit-card":"credit-card","calendar-check":"calendar-check",calendar:"calendar",crown:"crown",trophy:"trophy",leaf:"leaf","shield-alt":"shield","money-bill-wave":"banknote","trending-up":"trending-up","trending-down":"trending-down","shield-alert":"shield-alert",gauge:"gauge",target:"target",clock:"clock",receipt:"receipt",calculator:"calculator",layers:"layers","calendar-clock":"calendar-clock","pie-chart":"pie-chart","calendar-range":"calendar-range","list-plus":"list-plus","list-minus":"list-minus","file-text":"file-text","piggy-bank":"piggy-bank",banknote:"banknote"};let z=[];function at(){z.forEach(e=>{try{e.destroy()}catch{}}),z=[]}function st(e,t){if(!e)return;const a=t.saldo||0,s=a>=0?"var(--color-success)":"var(--color-danger)",r=a>=0?"positivo":"negativo";let o=`
        <p class="pulse-text">
            Neste mês você recebeu <strong>${I(t.totalReceitas)}</strong>
            e gastou <strong>${I(t.totalDespesas)}</strong>.
            Seu saldo é <strong style="color:${s}">${r} em ${I(Math.abs(a))}</strong>.
    `;t.totalCartoes>0&&(o+=` Faturas de cartões somam <strong>${I(t.totalCartoes)}</strong>.`),o+="</p>",e.innerHTML=o}function rt(e,t){if(e){if(t?.insights?.length>0){e.innerHTML=t.insights.map(a=>{const s=fe[a.icon]||a.icon;return`
                <div class="insight-card insight-${a.type} surface-card surface-card--interactive">
                    <div class="insight-icon"><i data-lucide="${s}"></i></div>
                    <div class="insight-content">
                        <h4>${f(a.title)}</h4>
                        <p>${f(a.message)}</p>
                    </div>
                </div>`}).join("");return}e.innerHTML='<p class="empty-message">Nenhum insight disponível no momento</p>'}}function ot(e,t){if(!e)return;if(!t?.labels?.length){e.innerHTML='<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de categorias</p>';return}e.innerHTML="";const a=5,s=t.labels.slice(0,a),r=t.values.slice(0,a).map(Number);if(t.labels.length>a){const n=t.values.slice(a).reduce((l,c)=>l+Number(c),0);s.push("Outros"),r.push(n)}const o=new ApexCharts(e,{chart:{type:"donut",height:220,background:"transparent"},series:r,labels:s,colors:["#E67E22","#2C3E50","#2ECC71","#F39C12","#9B59B6","#1ABC9C"],legend:{position:"bottom",fontSize:"11px",labels:{colors:"var(--color-text-muted)"}},dataLabels:{enabled:!1},plotOptions:{pie:{donut:{size:"60%"}}},stroke:{show:!1},tooltip:{y:{formatter:n=>I(n)}}});o.render(),z.push(o)}function nt(e,t){if(!e)return;if(!t?.labels?.length){e.innerHTML='<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de movimentação</p>';return}e.innerHTML="";const a=(t.receitas||[]).map(Number),s=(t.despesas||[]).map(Number),r=[],o=[],n=[],l=7;for(let d=0;d<t.labels.length;d+=l){const m=Math.floor(d/l)+1;r.push(`Sem ${m}`),o.push(a.slice(d,d+l).reduce((p,h)=>p+h,0)),n.push(s.slice(d,d+l).reduce((p,h)=>p+h,0))}const c=new ApexCharts(e,{chart:{type:"bar",height:220,background:"transparent",toolbar:{show:!1}},series:[{name:"Receitas",data:o},{name:"Despesas",data:n}],colors:["#2ECC71","#E74C3C"],xaxis:{categories:r,labels:{style:{colors:"var(--color-text-muted)",fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{fontSize:"10px"},formatter:d=>I(d)}},plotOptions:{bar:{columnWidth:"60%",borderRadius:4}},dataLabels:{enabled:!1},legend:{position:"bottom",fontSize:"11px",labels:{colors:"var(--color-text-muted)"}},grid:{borderColor:"rgba(255,255,255,0.05)"},tooltip:{shared:!0,intersect:!1,y:{formatter:d=>I(d)}}});c.render(),z.push(c)}function it({API:e}){async function t(){const o=document.getElementById("overviewPulse"),n=document.getElementById("overviewInsights"),l=document.getElementById("overviewCategoryChart"),c=document.getElementById("overviewComparisonChart");at();const[d,m,p,h]=await Promise.all([e.fetchSummaryStats(),e.fetchInsightsTeaser(),e.fetchReportDataForType("despesas_por_categoria",{accountId:null}),e.fetchReportDataForType("receitas_despesas_diario",{accountId:null})]);st(o,d),rt(n,m),ot(l,p),nt(c,h),window.lucide&&lucide.createIcons()}async function a(){const o=document.getElementById("insightsContainer");if(!o)return;const n=window.IS_PRO?await e.fetchInsights():await e.fetchInsightsTeaser();if(!n||!n.insights||n.insights.length===0){o.innerHTML='<p class="empty-message">Nenhum insight disponível no momento</p>';return}const l=n.insights.map(c=>{const d=fe[c.icon]||c.icon;return`
                <div class="insight-card insight-${c.type} surface-card surface-card--interactive">
                    <div class="insight-icon">
                        <i data-lucide="${d}"></i>
                    </div>
                    <div class="insight-content">
                        <h4>${f(c.title)}</h4>
                        <p>${f(c.message)}</p>
                    </div>
                </div>
            `}).join("");if(o.innerHTML=l,!window.IS_PRO&&n.isTeaser){const c=Math.max(0,(n.totalCount||0)-n.insights.length),d=c>0?`Desbloqueie mais ${c} insights com PRO`:"Desbloqueie todos os insights com PRO";o.insertAdjacentHTML("beforeend",`
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
            `)}window.lucide&&lucide.createIcons()}async function s(){const o=document.getElementById("comparativesContainer");if(!o)return;const n=await e.fetchComparatives();if(!n){o.innerHTML='<p class="empty-message">Dados de comparação não disponíveis</p>';return}const l=ue("Comparativo Mensal",n.monthly,"mês anterior"),c=ue("Comparativo Anual",n.yearly,"ano anterior"),d=Ke(n.categories||[]),m=Qe(n.evolucao||[]),p=Je(n.mediaDiaria),h=Xe(n.taxaEconomia),w=et(n.formasPagamento||[]);o.innerHTML=`<div class="comp-top-row">${l}${c}</div><div class="comp-duo-grid">${p}${h}</div>`+d+m+w,window.lucide&&lucide.createIcons(),Ze(n.evolucao||[])}async function r(){const o=await e.fetchSummaryStats(),n=document.getElementById("totalReceitas"),l=document.getElementById("totalDespesas"),c=document.getElementById("saldoMes"),d=document.getElementById("totalCartoes");if(n&&(n.textContent=I(o.totalReceitas||0)),l&&(l.textContent=I(o.totalDespesas||0)),c){const w=o.saldo||0;c.textContent=I(w),c.style.color=w>=0?"var(--color-success)":"var(--color-danger)"}d&&(d.textContent=I(o.totalCartoes||0)),W("trendReceitas",o.totalReceitas,o.prevReceitas,!1),W("trendDespesas",o.totalDespesas,o.prevDespesas,!0),W("trendSaldo",o.saldo,o.prevSaldo,!1),W("trendCartoes",o.totalCartoes,o.prevCartoes,!0);const m=document.getElementById("section-overview");m&&m.classList.contains("active")&&await t();const p=document.getElementById("section-insights");p&&p.classList.contains("active")&&await a();const h=document.getElementById("section-comparativos");h&&h.classList.contains("active")&&await s()}return{updateSummaryCards:r,updateInsightsSection:a,updateOverviewSection:t,updateComparativesSection:s}}function ct(e){return Array.from(e.querySelectorAll('button:not([disabled]), select:not([disabled]), input:not([disabled]), [href], [tabindex]:not([tabindex="-1"])')).filter(t=>t.offsetParent!==null)}function pe(e,t,a=""){const s=a?`${t}: ${a}`:t;if(typeof window.showToast=="function"){window.showToast(s,e,e==="error"?4500:3e3);return}let r=document.getElementById("relExportToastContainer");r||(r=document.createElement("div"),r.id="relExportToastContainer",r.className="rel-export-toast-container",document.body.appendChild(r));const o=document.createElement("div");o.className=`rel-export-toast rel-export-toast--${e}`,o.textContent=s,r.appendChild(o),requestAnimationFrame(()=>o.classList.add("is-visible")),setTimeout(()=>{o.classList.remove("is-visible"),setTimeout(()=>o.remove(),220)},e==="error"?4500:3e3)}function lt(e){const t=document.getElementById("relExportModalOverlay"),a=t?.querySelector(".rel-export-modal"),s=document.getElementById("relExportForm"),r=document.getElementById("relExportType");if(!t||!a||!s||!r)return Promise.resolve(null);const o=Array.from(r.options).some(c=>c.value===e);r.value=o?e:"despesas_por_categoria";const n=s.querySelector('input[name="format"][value="pdf"]');n&&(n.checked=!0);const l=document.activeElement;return new Promise(c=>{let d=!1;const m=()=>{s.removeEventListener("submit",h),t.removeEventListener("click",w),document.removeEventListener("keydown",A)},p=(g=null)=>{d||(d=!0,m(),t.classList.remove("is-open"),document.body.classList.remove("rel-export-modal-open"),setTimeout(()=>{t.hidden=!0,l&&typeof l.focus=="function"&&l.focus()},140),c(g))};function h(g){g.preventDefault(),p({type:r.value,format:s.elements.format?.value||"pdf"})}function w(g){(g.target===t||g.target.closest("[data-rel-export-close]"))&&(g.preventDefault(),p(null))}function A(g){if(g.key==="Escape"){g.preventDefault(),p(null);return}if(g.key!=="Tab")return;const T=ct(a);if(T.length===0)return;const y=T[0],C=T[T.length-1];g.shiftKey&&document.activeElement===y?(g.preventDefault(),C.focus()):!g.shiftKey&&document.activeElement===C&&(g.preventDefault(),y.focus())}s.addEventListener("submit",h),t.addEventListener("click",w),document.addEventListener("keydown",A),t.hidden=!1,document.body.classList.add("rel-export-modal-open"),requestAnimationFrame(()=>{t.classList.add("is-open"),window.lucide?.createIcons?.(),r.focus()})})}function dt({getReportType:e,showRestrictionAlert:t,handleRestrictedAccess:a}){return async function(){if(!window.IS_PRO)return t("Exportacao de relatorios e exclusiva do plano PRO.");const r=e()||"despesas_por_categoria",o=await lt(r);if(!o)return;const n=document.getElementById("exportBtn"),l=n?n.innerHTML:"";n&&(n.disabled=!0,n.innerHTML=`
                <div class="spinner" style="width: 1rem; height: 1rem; border-width: 2px;"></div>
                <span>Exportando...</span>
            `);try{const c=o.type,d=o.format,m=new URLSearchParams({type:c,format:d,year:i.currentMonth.split("-")[0],month:i.currentMonth.split("-")[1]});i.currentAccount&&m.set("account_id",i.currentAccount);const p=await Te(`${Pe()}?${m.toString()}`,{method:"GET"},{responseType:"response"}),h=await p.blob(),w=p.headers.get("Content-Disposition"),A=x.extractFilename(w)||(d==="excel"?"relatorio.xlsx":"relatorio.pdf"),g=URL.createObjectURL(h),T=document.createElement("a");T.href=g,T.download=A,document.body.appendChild(T),T.click(),T.remove(),URL.revokeObjectURL(g),pe("success","Relatorio exportado",A)}catch(c){if(await a(c))return;console.error("Export error:",c);const d=he(c,"Erro ao exportar relatorio. Tente novamente.");pe("error","Erro ao exportar",d)}finally{n&&(n.disabled=!1,n.innerHTML=l)}}}const be=e=>x.formatMonthLabel(e),D=e=>x.isYearlyView(e),ut=()=>x.getReportType(),ye=()=>x.getActiveCategoryType();function Z(e=i.currentAccount){return e?i.accounts.find(t=>String(t.id)===String(e))?.name||`Conta #${e}`:null}function J(){return D()?`Ano ${i.currentMonth.split("-")[0]}`:be(i.currentMonth)}function me(){const e=ye();return(q[i.currentView]||[]).find(a=>a.value===e)?.label||null}function pt(e=i.activeSection){return e==="relatorios"||e==="comparativos"}function mt(e=i.activeSection){return ce[e]||ce.overview}function X(e=i.currentView){return le[e]||le[u.VIEWS.CATEGORY]}function we(){try{localStorage.setItem(M.ACTIVE_VIEW,i.currentView),localStorage.setItem(M.CATEGORY_TYPE,i.categoryType),localStorage.setItem(M.ANNUAL_CATEGORY_TYPE,i.annualCategoryType)}catch{}}function te(){location.href=`${u.BASE_URL}billing`}async function Ee(e){const t=e||Y;window.PlanLimits?.promptUpgrade?await window.PlanLimits.promptUpgrade({context:"relatorios",message:t}):window.LKFeedback?.upgradePrompt?await window.LKFeedback.upgradePrompt({context:"relatorios",message:t}):window.Swal?.fire?(await Swal.fire({title:"Recurso exclusivo",text:t,icon:"info",showCancelButton:!0,confirmButtonText:"Assinar plano Pro",cancelButtonText:"Agora não",reverseButtons:!0,focusConfirm:!0})).isConfirmed&&te():confirm(`${t}

Deseja ir para a página de planos agora?`)&&te()}async function O(e){if(!e)return!1;const t=Number(e.status||e?.data?.status||0);if(t===401){const a=encodeURIComponent(location.pathname+location.search);return location.href=`${u.BASE_URL}login?return=${a}`,!0}if(t===403){let a=Y;if(e?.data?.message)a=e.data.message;else if(typeof e?.clone=="function")try{const s=await e.clone().json();s?.message&&(a=s.message)}catch{}return i.accessRestricted||(i.accessRestricted=!0,await Ee(a)),E.showPaywall(a),!0}return!1}function ht(e){typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"error",title:e,showConfirmButton:!1,timer:4e3,timerProgressBar:!0})}const G={async fetchReportData(){i.lastReportError=null;const e=new AbortController,t=setTimeout(()=>e.abort(),u.FETCH_TIMEOUT);try{const a=await R(ie(),{type:x.getReportType(),year:i.currentMonth.split("-")[0],month:i.currentMonth.split("-")[1],account_id:i.currentAccount||void 0});return clearTimeout(t),i.accessRestricted=!1,i.lastReportError=null,a.data||a}catch(a){return clearTimeout(t),await O(a)||(i.lastReportError=a.name==="AbortError"?"A requisição demorou demais. Tente novamente em instantes.":"Não foi possível carregar o relatório agora. Verifique a conexão e tente novamente.",console.error("Error fetching report data:",a),ht(he(a,"Erro ao carregar relatório. Verifique sua conexão."))),null}},async fetchReportDataForType(e,t={}){const a=new URLSearchParams({type:e,year:i.currentMonth.split("-")[0],month:i.currentMonth.split("-")[1]}),s=Object.prototype.hasOwnProperty.call(t,"accountId")?t.accountId:i.currentAccount;s&&a.set("account_id",s);try{const r=await R(ie(),Object.fromEntries(a.entries()));return r.data||r}catch{return null}},async fetchAccounts(){try{const e=await R(Oe());i.accessRestricted=!1;const t=e.data||e.items||e||[];return(Array.isArray(t)?t:[]).map(a=>({id:Number(a.id),name:a.nome||a.apelido||a.instituicao||`Conta #${a.id}`}))}catch(e){return await O(e)?[]:(console.error("Error fetching accounts:",e),[])}},async fetchSummaryStats(){const[e,t]=i.currentMonth.split("-"),a=new AbortController,s=setTimeout(()=>a.abort(),u.FETCH_TIMEOUT);try{const r=await R(Ve(),{year:e,month:t});return clearTimeout(s),r.data||r}catch(r){return clearTimeout(s),await O(r)?{totalReceitas:0,totalDespesas:0,saldo:0,totalCartoes:0}:(console.error("Error fetching summary stats:",r),{totalReceitas:0,totalDespesas:0,saldo:0,totalCartoes:0})}},async fetchInsights(){const[e,t]=i.currentMonth.split("-"),a=new AbortController,s=setTimeout(()=>a.abort(),u.FETCH_TIMEOUT);try{const r=await R(De(),{year:e,month:t});return clearTimeout(s),r.data||r}catch(r){return clearTimeout(s),await O(r)?{insights:[]}:(console.error("Error fetching insights:",r),{insights:[]})}},async fetchInsightsTeaser(){const[e,t]=i.currentMonth.split("-"),a=new AbortController,s=setTimeout(()=>a.abort(),u.FETCH_TIMEOUT);try{const r=await R(Be(),{year:e,month:t});return clearTimeout(s),r.data||r}catch(r){return clearTimeout(s),console.error("Error fetching insights teaser:",r),{insights:[],totalCount:0,isTeaser:!0}}},async fetchComparatives(){const[e,t]=i.currentMonth.split("-"),a=new URLSearchParams({year:e,month:t});i.currentAccount&&a.set("account_id",i.currentAccount);const s=new AbortController,r=setTimeout(()=>s.abort(),u.FETCH_TIMEOUT);try{const o=await R(Ne(),Object.fromEntries(a.entries()));return clearTimeout(r),o.data||o}catch(o){return clearTimeout(r),await O(o)||console.error("Error fetching comparatives:",o),null}}};L.API=G;const E={setContent(e){const t=document.getElementById("reportArea");t&&(t.innerHTML=e,t.setAttribute("aria-busy","false"),window.lucide&&lucide.createIcons())},showLoading(){const e=document.getElementById("reportArea");e&&(e.setAttribute("aria-busy","true"),e.innerHTML=`
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Carregando relatório...</p>
                </div>
            `,window.lucide&&lucide.createIcons())},showEmptyState(){const e=Z(),t=X(),a=J(),s=e?`Nenhum dado foi encontrado para ${e} em ${a}.`:`Não há lançamentos suficientes para montar este recorte em ${a}.`;E.setContent(`
            <div class="empty-state report-empty-state">
                <i data-lucide="pie-chart"></i>
                <h3>${f(t.title)}</h3>
                <p>${f(s)}</p>
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
        `)},showErrorState(e){const t=f(e||"Não foi possível carregar este relatório.");E.setContent(`
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
        `)},showPaywall(e=Y){const t=document.getElementById("reportArea");if(!t)return;const a=f(e||Y);t.setAttribute("aria-busy","false"),t.innerHTML=`
            <div class="paywall-message" role="alert">
                <i data-lucide="crown" aria-hidden="true"></i>
                <h3>Recurso Premium</h3>
                <p>${a}</p>
                <button type="button" class="btn-upgrade surface-button surface-button--upgrade surface-button--lg" data-action="go-pro">
                    Fazer Upgrade para PRO
                </button>
            </div>
        `,window.lucide&&lucide.createIcons();const s=t.querySelector('[data-action="go-pro"]');s&&s.addEventListener("click",te)},updateMonthLabel(){const e=document.getElementById("monthLabel");e&&(e.textContent=D()?i.currentMonth.split("-")[0]:be(i.currentMonth))},updatePageContext(){const e=document.getElementById("reportsContextKicker"),t=document.getElementById("reportsContextTitle"),a=document.getElementById("reportsContextDescription"),s=document.getElementById("reportsContextChips"),r=document.getElementById("reportsContextActions");if(!e||!t||!a||!s||!r)return;const o=mt(),n=X(),l=J(),c=Z(),d=pt(),m=me(),p=!window.IS_PRO&&i.activeSection==="insights";e.textContent=o.kicker,t.textContent=i.activeSection==="relatorios"?n.title:o.title,a.textContent=i.activeSection==="relatorios"?n.description:o.description;const h=[`<span class="context-chip surface-chip"><i data-lucide="calendar-range"></i><span>${f(l)}</span></span>`];i.activeSection==="relatorios"&&m&&h.push(`<span class="context-chip surface-chip surface-chip--highlight context-chip-highlight"><i data-lucide="filter"></i><span>${f(m)}</span></span>`),c&&d?h.push(`<span class="context-chip surface-chip surface-chip--highlight context-chip-highlight"><i data-lucide="landmark"></i><span>${f(c)}</span></span>`):c&&!d?h.push(`<span class="context-chip surface-chip"><i data-lucide="bookmark"></i><span>Filtro salvo: ${f(c)}</span></span>`):h.push('<span class="context-chip surface-chip"><i data-lucide="layers"></i><span>Consolidado</span></span>'),p&&h.push('<span class="context-chip surface-chip surface-chip--pro context-chip-pro"><i data-lucide="crown"></i><span>Preview PRO</span></span>'),s.innerHTML=h.join(""),r.innerHTML=c?`
            <button type="button" class="context-action-btn surface-button surface-button--subtle" data-action="clear-report-account">
                <i data-lucide="eraser"></i>
                <span>Limpar filtro de conta</span>
            </button>
        `:"",window.lucide&&lucide.createIcons()},updateReportFilterSummary(){const e=document.getElementById("reportFilterSummary"),t=document.getElementById("reportScopeNote");if(!e||!t)return;const a=[`<span class="report-filter-chip surface-chip"><i data-lucide="calendar-range"></i><span>${f(J())}</span></span>`,`<span class="report-filter-chip surface-chip"><i data-lucide="bar-chart-3"></i><span>${f(X().title)}</span></span>`],s=me();s&&a.push(`<span class="report-filter-chip surface-chip"><i data-lucide="filter"></i><span>${f(s)}</span></span>`),i.currentAccount?a.push(`<span class="report-filter-chip surface-chip surface-chip--highlight report-filter-chip-highlight"><i data-lucide="landmark"></i><span>${f(Z())}</span></span>`):a.push('<span class="report-filter-chip surface-chip"><i data-lucide="layers"></i><span>Todas as contas</span></span>'),e.innerHTML=a.join(""),t.classList.remove("hidden"),t.innerHTML=i.currentAccount?'<i data-lucide="info"></i><span>O resumo do topo continua consolidado. O filtro por conta afeta este gráfico e a aba Comparativos.</span>':'<i data-lucide="info"></i><span>Use o filtro de conta para analisar um recorte específico sem perder o consolidado do topo.</span>',window.lucide&&lucide.createIcons()},updateControls(){const e=document.getElementById("typeSelectWrapper"),t=[u.VIEWS.CATEGORY,u.VIEWS.ANNUAL_CATEGORY].includes(i.currentView);e&&(e.classList.toggle("hidden",!t),t&&E.syncTypeSelect());const a=document.getElementById("accountSelectWrapper");a&&a.classList.remove("hidden")},syncTypeSelect(){const e=document.getElementById("reportType");if(!e)return;const t=q[i.currentView];if(!t)return;(e.options.length!==t.length||t.some((s,r)=>e.options[r]?.value!==s.value))&&(e.innerHTML=t.map(s=>`<option value="${s.value}">${s.label}</option>`).join("")),e.value=ye()},setActiveTab(e){document.querySelectorAll(".tab-btn").forEach(t=>{const a=t.dataset.view===e;t.classList.toggle("active",a),t.setAttribute("aria-selected",a)})}};L.UI=E;const gt=()=>G.fetchReportData(),vt=()=>E.showLoading(),ee=()=>E.showEmptyState(),ft=e=>E.showErrorState(e),bt=()=>E.updateMonthLabel(),P=()=>E.updatePageContext(),N=()=>E.updateReportFilterSummary(),yt=()=>E.updateControls(),wt=e=>E.setActiveTab(e),Et=e=>v.renderPie(e),Ct=e=>v.renderLine(e),St=e=>v.renderBar(e);async function $(){P(),N(),vt(),xt();const e=await gt();if(!i.accessRestricted){if(i.lastReportError)return ft(i.lastReportError);if(i.currentView===u.VIEWS.CARDS){if(!e||!Array.isArray(e.cards))return ee();tt(e);return}if(!e||!e.labels||e.labels.length===0)return ee();switch(i.currentView){case u.VIEWS.CATEGORY:case u.VIEWS.ANNUAL_CATEGORY:Et(e);break;case u.VIEWS.BALANCE:case u.VIEWS.EVOLUTION:Ct(e);break;case u.VIEWS.COMPARISON:case u.VIEWS.ACCOUNTS:case u.VIEWS.ANNUAL_SUMMARY:St(e);break;default:ee()}je(e)}}const{updateSummaryCards:xt,updateInsightsSection:At,updateOverviewSection:Tt,updateComparativesSection:It}=it({API:G}),$t=dt({getReportType:ut,showRestrictionAlert:Ee,handleRestrictedAccess:O});async function _t(e){e==="overview"?await Tt():e==="relatorios"?await $():e==="insights"?await At():e==="comparativos"&&await It()}function Ce(){const e=D();if(window.LukratoHeader?.setPickerMode?.(e?"year":"month"),e){const t=window.LukratoHeader?.getYear?.();if(t){const[,a="01"]=i.currentMonth.split("-"),s=String(a).padStart(2,"0");i.currentMonth=`${t}-${s}`}}}function Se(e){i.currentView=e,wt(e),yt(),P(),N(),Ce(),we(),$()}function xe(e){i.currentView===u.VIEWS.ANNUAL_CATEGORY?i.annualCategoryType=e:i.categoryType=e,P(),N(),we(),$()}function ae(e){i.currentAccount=e||null,P(),N(),$()}function Lt(e){!e?.detail?.month||D()||i.currentMonth!==e.detail.month&&(i.currentMonth=e.detail.month,bt(),P(),N(),$())}function Mt(e){if(!D()||!e?.detail?.year)return;const[,t="01"]=i.currentMonth.split("-"),a=String(t).padStart(2,"0"),s=`${e.detail.year}-${a}`;i.currentMonth!==s&&(i.currentMonth=s,P(),N(),$())}function se(){return window.IS_PRO=Re().isPro===!0,window.IS_PRO}Me(()=>{se()});function Rt(){try{const e=localStorage.getItem(M.ACTIVE_VIEW);e&&Object.values(u.VIEWS).includes(e)&&(i.currentView=e);const t=localStorage.getItem(M.CATEGORY_TYPE);t&&q[u.VIEWS.CATEGORY]?.some(s=>s.value===t)&&(i.categoryType=t);const a=localStorage.getItem(M.ANNUAL_CATEGORY_TYPE);a&&q[u.VIEWS.ANNUAL_CATEGORY]?.some(s=>s.value===a)&&(i.annualCategoryType=a)}catch{}}function kt(){const e=s=>{i.activeSection=s,document.querySelectorAll(".rel-section-tab").forEach(n=>{n.classList.remove("active"),n.setAttribute("aria-selected","false")}),document.querySelectorAll(".rel-section-panel").forEach(n=>{n.classList.remove("active")});const r=document.querySelector(`.rel-section-tab[data-section="${s}"]`);r&&(r.classList.add("active"),r.setAttribute("aria-selected","true"));const o=document.getElementById(`section-${s}`);o&&o.classList.add("active"),localStorage.setItem(M.ACTIVE_SECTION,s),E.updatePageContext(),_t(s),window.lucide?.createIcons?.()},t=["comparativos"];document.querySelectorAll(".rel-section-tab").forEach(s=>{s.addEventListener("click",()=>{const r=s.dataset.section;if(!window.IS_PRO&&t.includes(r)){window.PlanLimits?.promptUpgrade?window.PlanLimits.promptUpgrade({context:"relatorios",message:"Esta funcionalidade e exclusiva do plano Pro."}).catch(()=>{}):window.LKFeedback?.upgradePrompt?window.LKFeedback.upgradePrompt({context:"relatorios",message:"Esta funcionalidade e exclusiva do plano Pro."}).catch(()=>{}):Swal.fire({icon:"info",title:"Recurso Premium",html:"Esta funcionalidade e exclusiva do <b>plano Pro</b>.<br>Faca upgrade para desbloquear!",confirmButtonText:'<i class="lucide-crown" style="margin-right:6px"></i> Fazer Upgrade',showCancelButton:!0,cancelButtonText:"Agora nao",confirmButtonColor:"#f59e0b",cancelButtonColor:"#64748b"}).then(o=>{o.isConfirmed&&(window.location.href=`${u.BASE_URL}billing`)});return}e(r)})});const a=localStorage.getItem(M.ACTIVE_SECTION);if(a&&document.getElementById(`section-${a}`)){!window.IS_PRO&&t.includes(a)?e("overview"):e(a);return}e("overview")}function Ot(e,t){const a=document.getElementById("clearFiltersWrapper"),s=document.getElementById("btnLimparFiltrosRel"),r=()=>{if(!a)return;const o=e&&e.selectedIndex>0,n=t&&t.value!=="";a.style.display=o||n?"flex":"none"};return e?.addEventListener("change",r),t?.addEventListener("change",r),s?.addEventListener("click",()=>{e&&(e.selectedIndex=0,xe(e.value)),t&&(t.value="",ae("")),r()}),r(),r}function Pt(e,t){document.addEventListener("click",a=>{if(a.target.closest('[data-action="retry-report"]')){a.preventDefault(),$();return}if(a.target.closest('[data-action="clear-report-account"]')){a.preventDefault(),e&&(e.value=""),ae(""),typeof t=="function"&&t();return}const o=a.target.closest('[data-action="open-card-detail"]');if(!o)return;a.stopPropagation();const n=Number.parseInt(String(o.dataset.cardId||""),10),l=o.dataset.cardMonth||i.currentMonth,c=/^\d{4}-\d{2}$/.test(l)?l:i.currentMonth;if(!Number.isInteger(n)||n<=0)return;const d=new URLSearchParams({mes:c,origem:"relatorios"});window.location.href=`${u.BASE_URL}cartoes/${n}?${d.toString()}`})}function Nt(){window.ReportsAPI={setMonth:e=>{/^\d{4}-\d{2}$/.test(e)&&(i.currentMonth=e,E.updateMonthLabel(),$())},setView:e=>{Object.values(u.VIEWS).includes(e)&&Se(e)},refresh:()=>$(),getState:()=>({...i})}}async function Bt(){se(),Ge(),v.setupDefaults(),i.accounts=await G.fetchAccounts();const e=document.getElementById("accountFilter");e&&i.accounts.forEach(r=>{const o=document.createElement("option");o.value=r.id,o.textContent=r.name,e.appendChild(o)}),Rt(),document.querySelectorAll(".tab-btn").forEach(r=>{r.addEventListener("click",()=>Se(r.dataset.view))}),E.setActiveTab(i.currentView),E.updateControls(),E.updatePageContext(),kt();const t=document.getElementById("reportType");t?.addEventListener("change",r=>xe(r.target.value)),e?.addEventListener("change",r=>ae(r.target.value));const a=Ot(t,e);document.addEventListener("lukrato:theme-changed",()=>{v.setupDefaults(),$()});const s=window.LukratoHeader?.getMonth?.();s&&(i.currentMonth=s),document.addEventListener("lukrato:month-changed",Lt),document.addEventListener("lukrato:year-changed",Mt),document.getElementById("exportBtn")?.addEventListener("click",$t),Pt(e,a),Ce(),E.updateMonthLabel(),E.updateControls(),await $()}function Dt(){if(window.__LK_REPORTS_LOADED__)return;window.__LK_REPORTS_LOADED__=!0;const e=async()=>{await ke({},{silent:!0}),se(),await Bt()};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",()=>{e()}):e(),Nt()}Dt();
