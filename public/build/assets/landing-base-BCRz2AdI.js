const T="lukrato-theme",$="lukrato:theme-changed";function z(){const e=document.documentElement,t=localStorage.getItem(T);if(t==="light"||t==="dark")return t;const n=e.getAttribute("data-theme");return n==="light"||n==="dark"?n:"light"}function C(e){document.querySelectorAll(".lk-theme-toggle").forEach(t=>{t.classList.toggle("dark",e==="dark")})}function P(e){document.documentElement.setAttribute("data-theme",e),localStorage.setItem(T,e),C(e);const n=document.querySelector('meta[name="theme-color"]');n&&n.setAttribute("content",e==="dark"?"#092741":"#e67e22"),document.dispatchEvent(new CustomEvent($,{detail:{theme:e}})),window.lucide&&requestAnimationFrame(()=>lucide.createIcons())}function M(){const e=z();P(e==="dark"?"light":"dark")}function _(){const e=z();P(e),document.querySelectorAll(".lk-theme-toggle").forEach(t=>{t.addEventListener("click",M)}),document.addEventListener($,t=>{C(t.detail.theme)})}function F(){return"api/v1/contato/enviar"}function y(e){return String(e||"").replace(/\/+$/,"")}function O(e){if(!e)return"";try{return new URL(e,globalThis.location?.origin||"http://localhost").pathname}catch{return String(e||"")}}function S(e=globalThis.window?.APP_BASE_URL||""){const t=globalThis.document?.querySelector?.('meta[name="base-url"]')?.getAttribute?.("content");return y(t||e||"")}function U(e=globalThis.window?.API_BASE_URL||""){const t=globalThis.document?.querySelector?.('meta[name="api-base-url"]')?.getAttribute?.("content"),n=y(t||e||"");return n||S()}function N(e=S(),t=globalThis.location?.pathname||"/"){const n=String(t||"/"),c=y(e||""),i=c!==""?O(c).replace(/\/?$/,"/"):"";if(i&&i!=="./")return i;const s=n.indexOf("/public/");return s!==-1?n.substring(0,s+8):"/"}function R(e,t){const n=String(e||"").replace(/^\/+/,""),c=y(t||"");return c?`${c}/${n}`:`/${n}`}function Y(e,t=S()){return R(e,t)}function G(e,t=U()){return R(e,t)}function W(){const e=N(),t=["funcionalidades","beneficios","gamificacao","planos","indicacao","contato"];function n(){const r=document.querySelector("header, .lk-site-header");return r?r.offsetHeight+10:0}function c(r){const o=document.getElementById(r);if(!o)return;const l=o.getBoundingClientRect().top+window.pageYOffset-n();window.scrollTo({top:l,behavior:"smooth"})}function i(r){if(!r.startsWith(e))return null;const l=r.slice(e.length).replace(/^\/+/,"").split("/")[0];return t.includes(l)?l:null}const s=i(location.pathname);s&&(history.replaceState(null,"",e+s),requestAnimationFrame(()=>c(s))),document.addEventListener("click",r=>{const o=r.target.closest("a[href]");if(!o)return;let l=o.getAttribute("href");if(!l)return;try{l=new URL(l,location.origin).pathname}catch{return}const a=i(l);a&&(r.preventDefault(),history.pushState(null,"",e+a),c(a))}),window.addEventListener("popstate",()=>{const r=i(location.pathname);r&&c(r)})}function H(){if(window.lkLandingBootstrapped)return;window.lkLandingBootstrapped=!0;const e=document.querySelector(".lk-site-burger"),t=document.querySelector(".lk-site-header"),n=document.body;if(!e||!t)return;const c=document.createElement("div");c.className="lk-site-menu-overlay",t.appendChild(c);function i(){t.classList.remove("is-open"),n.classList.remove("lk-nav-open"),e.setAttribute("aria-expanded","false")}function s(){const r=!t.classList.contains("is-open");t.classList.toggle("is-open",r),n.classList.toggle("lk-nav-open",r),e.setAttribute("aria-expanded",r?"true":"false")}e.setAttribute("aria-expanded","false"),e.addEventListener("click",s),c.addEventListener("click",i),window.addEventListener("resize",()=>{window.innerWidth>768&&t.classList.contains("is-open")&&i()}),t.addEventListener("click",r=>{r.target.closest(".lk-site-nav-link")&&t.classList.contains("is-open")&&i()}),document.addEventListener("keydown",r=>{r.key==="Escape"&&t.classList.contains("is-open")&&i()})}function X(){const e="func-gallery",t=document.getElementById(e);if(!t)return;const n=document.querySelectorAll(`[data-open="${e}"]`),c=t.querySelectorAll(`[data-close="${e}"]`),i=t.querySelector(".lk-gallery-track"),s=i?Array.from(i.querySelectorAll("img")):[],r=t.querySelector(".lk-gallery-prev"),o=t.querySelector(".lk-gallery-next"),l=t.querySelector("#lkGalleryTitle"),a=t.querySelector("#lkGalleryDesc"),u=t.querySelector("#lkGalleryCount"),f=t.querySelector(".lk-gallery");let m=0,h=0,p=!1;function w(){t.setAttribute("aria-hidden","false"),t.classList.add("is-open"),document.body.style.overflow="hidden",requestAnimationFrame(()=>k(!0))}function x(){t.setAttribute("aria-hidden","true"),t.classList.remove("is-open"),document.body.style.overflow=""}function L(){if(!s.length)return;const d=s[m],g=d.dataset.title||d.alt||"Tela",D=d.dataset.desc||"";l&&(l.textContent=g),a&&(a.textContent=D),u&&(u.textContent=`${m+1}/${s.length}`)}function k(d=!1){if(!i||s.length===0)return;const g=s[0].clientWidth;d&&(i.style.transition="none"),i.style.transform=`translateX(-${m*g}px)`,L(),d&&requestAnimationFrame(()=>{i.style.transition="transform 0.28s ease"})}function v(){s.length&&(m=(m+1)%s.length,k())}function b(){s.length&&(m=(m-1+s.length)%s.length,k())}n.forEach(d=>d.addEventListener("click",w)),c.forEach(d=>d.addEventListener("click",x)),r&&r.addEventListener("click",b),o&&o.addEventListener("click",v),s.forEach(d=>d.addEventListener("click",v)),document.addEventListener("keydown",d=>{t.classList.contains("is-open")&&(d.key==="Escape"&&x(),d.key==="ArrowRight"&&v(),d.key==="ArrowLeft"&&b())}),f&&(f.addEventListener("touchstart",d=>{t.classList.contains("is-open")&&(h=d.touches[0].clientX,p=!0)},{passive:!0}),f.addEventListener("touchmove",()=>{},{passive:!0}),f.addEventListener("touchend",d=>{if(!p)return;const g=d.changedTouches[0].clientX-h;Math.abs(g)>45&&(g<0?v():b()),p=!1})),window.addEventListener("resize",()=>k(!0)),L()}let E;function B(){return window.Swal?Promise.resolve(window.Swal):(E||(E=new Promise((e,t)=>{const n=document.createElement("script");n.src="https://cdn.jsdelivr.net/npm/sweetalert2@11",n.onload=()=>e(window.Swal),n.onerror=t,document.head.appendChild(n)})),E)}function K(){const e=document.querySelector("#contato.lk-contact");if(e){let l=function(a){r.forEach(u=>{const f=u.dataset.target===a;u.classList.toggle("is-active",f),u.setAttribute("aria-selected",f?"true":"false")}),o.forEach(u=>u.classList.toggle("is-active",u.dataset.panel===a))};var s=l;const r=e.querySelectorAll(".lk-toggle-btn"),o=e.querySelectorAll(".lk-contact-panel");r.forEach(a=>a.addEventListener("click",()=>l(a.dataset.target))),l("whatsapp")}const t=document.getElementById("contactForm"),n=document.getElementById("whatsapp");if(!t)return;let c=!1;const i=G(F());if(n){const r=o=>o.length<=2?`(${o}`:o.length<=6?`(${o.slice(0,2)}) ${o.slice(2)}`:o.length<=10?`(${o.slice(0,2)}) ${o.slice(2,6)}-${o.slice(6)}`:`(${o.slice(0,2)}) ${o.slice(2,7)}-${o.slice(7,11)}`;n.addEventListener("input",o=>{const l=o.target.value.replace(/\D/g,"").slice(0,11);o.target.value=r(l)})}t.addEventListener("submit",async r=>{if(r.preventDefault(),c)return;c=!0;const o=t.querySelector('[type="submit"]'),l=o?o.textContent:null;o&&(o.disabled=!0,o.textContent="Enviando...");try{const a=await fetch(i,{method:"POST",body:new FormData(t)}),u=await a.text();let f=null;try{f=JSON.parse(u)}catch{}const m=f?.success===!0,h=f?.message??"Mensagem enviada com sucesso.",p=await B();if(a.ok&&m){await p.fire({icon:"success",title:"Mensagem enviada! ",text:h,confirmButtonText:"Ok",confirmButtonColor:"#e67e22"}),t.reset();return}const w=f?.message??f?.data?.message??`Erro ao enviar (status ${a.status}).`;await p.fire({icon:a.status===422?"warning":"error",title:a.status===422?"Verifique os campos":"Não foi possível enviar",text:w,confirmButtonColor:"#e67e22"})}catch(a){console.error(a);try{await(await B()).fire({icon:"error",title:"Erro de conexão",text:"Não foi possível enviar sua mensagem agora. Tente novamente.",confirmButtonColor:"#e67e22"})}catch{}}finally{c=!1,o&&(o.disabled=!1,o.textContent=l??"Enviar")}})}function j(){const e=document.getElementById("lkBackToTop");if(!e)return;const t=420;let n=!1;function c(){(window.scrollY||document.documentElement.scrollTop||0)>t?e.classList.add("is-visible"):e.classList.remove("is-visible"),n=!1}window.addEventListener("scroll",()=>{n||(window.requestAnimationFrame(c),n=!0)},{passive:!0}),e.addEventListener("click",()=>window.scrollTo({top:0,behavior:"smooth"})),c()}function V(){const e="lk_cookie_consent";if(localStorage.getItem(e))return;const t=Y("privacidade");setTimeout(function(){const n=document.createElement("div");n.id="cookieConsent",n.innerHTML=`
            <style>
                @keyframes cookieSlideIn {
                    from { opacity: 0; transform: translateY(20px); }
                    to   { opacity: 1; transform: translateY(0); }
                }
                #cookieBannerInner {
                    position: fixed;
                    bottom: 16px;
                    left: 16px;
                    right: 16px;
                    max-width: 380px;
                    background: var(--color-bg);
                    border-radius: 16px;
                    box-shadow: 0 10px 40px rgba(0,0,0,.15), 0 0 0 1px rgba(0,0,0,.05);
                    padding: 16px;
                    z-index: 9999;
                    font-family: var(--font-primary);
                    animation: cookieSlideIn .4s ease-out;
                }
                @media (min-width: 480px) {
                    #cookieBannerInner { right: auto; padding: 20px; }
                }
                #cookieBannerInner .cookie-content { display: flex; align-items: flex-start; gap: 12px; }
                #cookieBannerInner .cookie-icon {
                    width: 36px; height: 36px;
                    background: var(--color-primary);
                    border-radius: 10px;
                    display: flex; align-items: center; justify-content: center;
                    flex-shrink: 0; font-size: 18px;
                }
                @media (min-width: 480px) {
                    #cookieBannerInner .cookie-icon { width: 40px; height: 40px; font-size: 20px; }
                }
                #cookieBannerInner .cookie-text { flex: 1; min-width: 0; }
                #cookieBannerInner h4 { margin: 0 0 4px; font-size: 14px; font-weight: 600; color: var(--color-text); }
                @media (min-width: 480px) { #cookieBannerInner h4 { font-size: 15px; margin-bottom: 6px; } }
                #cookieBannerInner p { margin: 0 0 12px; font-size: 12px; color: var(--color-text); line-height: 1.5; }
                @media (min-width: 480px) { #cookieBannerInner p { font-size: 13px; margin-bottom: 14px; } }
                #cookieBannerInner a { color: var(--color-primary); text-decoration: underline; }
                #cookieBannerInner .cookie-buttons { display: flex; gap: 8px; }
                #cookieBannerInner .btn-accept {
                    flex: 1; padding: 10px 14px;
                    background: var(--color-primary);
                    color: white; border: none; border-radius: 8px;
                    font-size: 13px; font-weight: 600; cursor: pointer;
                    transition: all .2s ease;
                }
                #cookieBannerInner .btn-accept:hover { transform: scale(1.02); }
                #cookieBannerInner .btn-decline {
                    padding: 10px 14px; background: var(--glass-bg); color:var(--color-text);
                    border: 1px solid var(--color-primary); border-radius: 8px; font-size: 13px; font-weight: 500;
                    cursor: pointer; transition: all .2s ease;
                }
                #cookieBannerInner .btn-decline:hover { background: var(--color-surface); }
            </style>
            <div id="cookieBannerInner">
                <div class="cookie-content">
                    <div class="cookie-icon">
                        <i data-lucide="cookie" class="w-6 h-6"></i>
                    </div>
                    <div class="cookie-text">
                        <h4>Usamos cookies</h4>
                        <p>
                            Para melhorar sua experiência e analisar o uso do site.
                            <a href="${t}">Saiba mais</a>
                        </p>
                        <div class="cookie-buttons">
                            <button id="cookieAccept" class="btn-accept">Aceitar</button>
                            <button id="cookieDecline" class="btn-decline">Recusar</button>
                        </div>
                    </div>
                </div>
            </div>
        `,document.body.appendChild(n),window.lucide&&lucide.createIcons(),document.getElementById("cookieAccept").addEventListener("click",function(){localStorage.setItem(e,"accepted"),c()}),document.getElementById("cookieDecline").addEventListener("click",function(){localStorage.setItem(e,"declined"),c()});function c(){const i=document.getElementById("cookieBannerInner");i&&(i.style.opacity="0",i.style.transform="translateY(20px)",i.style.transition="all .3s ease",setTimeout(()=>{const s=document.getElementById("cookieConsent");s&&s.remove()},300))}},1500)}function I(e){return e.toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2})}function A(e){return String(e||"").replace(/\D/g,"")}function J(e){e.classList.remove("lk-vazamento-resultado"),e.offsetWidth,e.classList.add("lk-vazamento-resultado")}function Q(){document.querySelectorAll("[data-leak-calculator]").forEach(t=>{if(t.dataset.lkLeakCalculatorReady==="true")return;const n=t.querySelector("[data-lk-vazamento-input]"),c=t.querySelector("[data-lk-vazamento-submit]"),i=t.querySelector("[data-lk-vazamento-result]"),s=t.querySelector("[data-lk-vazamento-amount]");if(!n||!c||!i||!s)return;t.dataset.lkLeakCalculatorReady="true";function r(){s.textContent="",i.hidden=!0}function o(){const a=A(n.value);if(!a){n.value="",r();return}const u=Number.parseInt(a,10)/100;n.value=`R$ ${I(u)}`,r()}function l(){const a=A(n.value);if(!a){r();return}const u=Number.parseInt(a,10)/100;if(!Number.isFinite(u)||u<=0){r();return}const f=Math.round(u*.15*100)/100;s.textContent=I(f),i.hidden=!1,J(i)}n.addEventListener("input",o),n.addEventListener("keydown",a=>{a.key==="Enter"&&(a.preventDefault(),l())}),c.addEventListener("click",l)})}_();function q(){W(),H(),X(),K(),j(),V(),Q()}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",q):q();
