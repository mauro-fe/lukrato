const B="lukrato-theme",I="lukrato:theme-changed";function A(){const n=document.documentElement,e=localStorage.getItem(B);if(e==="light"||e==="dark")return e;const o=n.getAttribute("data-theme");return o==="light"||o==="dark"?o:"light"}function q(n){document.querySelectorAll(".lk-theme-toggle").forEach(e=>{e.classList.toggle("dark",n==="dark")})}function T(n){document.documentElement.setAttribute("data-theme",n),localStorage.setItem(B,n),q(n);const o=document.querySelector('meta[name="theme-color"]');o&&o.setAttribute("content",n==="dark"?"#092741":"#e67e22"),document.dispatchEvent(new CustomEvent(I,{detail:{theme:n}})),window.lucide&&requestAnimationFrame(()=>lucide.createIcons())}function C(){const n=A();T(n==="dark"?"light":"dark")}function P(){const n=A();T(n),document.querySelectorAll(".lk-theme-toggle").forEach(e=>{e.addEventListener("click",C)}),document.addEventListener(I,e=>{q(e.detail.theme)})}function _(){function n(){const t=document.querySelector('meta[name="base-url"]');if(t?.content)try{return new URL(t.content).pathname.replace(/\/?$/,"/")}catch{return t.content.replace(/\/?$/,"/")}const s=location.pathname,i=s.indexOf("/public/");return i!==-1?s.substring(0,i+8):"/"}const e=n(),o=["funcionalidades","beneficios","gamificacao","planos","indicacao","contato"];function d(){const t=document.querySelector("header, .lk-site-header");return t?t.offsetHeight+10:0}function r(t){const s=document.getElementById(t);if(!s)return;const i=s.getBoundingClientRect().top+window.pageYOffset-d();window.scrollTo({top:i,behavior:"smooth"})}function a(t){if(!t.startsWith(e))return null;const i=t.slice(e.length).replace(/^\/+/,"").split("/")[0];return o.includes(i)?i:null}const c=a(location.pathname);c&&(history.replaceState(null,"",e+c),requestAnimationFrame(()=>r(c))),document.addEventListener("click",t=>{const s=t.target.closest("a[href]");if(!s)return;let i=s.getAttribute("href");if(!i)return;try{i=new URL(i,location.origin).pathname}catch{return}const u=a(i);u&&(t.preventDefault(),history.pushState(null,"",e+u),r(u))}),window.addEventListener("popstate",()=>{const t=a(location.pathname);t&&r(t)})}function M(){if(window.lkLandingBootstrapped)return;window.lkLandingBootstrapped=!0;const n=document.querySelector(".lk-site-burger"),e=document.querySelector(".lk-site-header"),o=document.body;if(!n||!e)return;const d=document.createElement("div");d.className="lk-site-menu-overlay",e.appendChild(d);function r(){e.classList.remove("is-open"),o.classList.remove("lk-nav-open"),n.setAttribute("aria-expanded","false")}function a(){const c=!e.classList.contains("is-open");e.classList.toggle("is-open",c),o.classList.toggle("lk-nav-open",c),n.setAttribute("aria-expanded",c?"true":"false")}n.setAttribute("aria-expanded","false"),n.addEventListener("click",a),d.addEventListener("click",r),window.addEventListener("resize",()=>{window.innerWidth>768&&e.classList.contains("is-open")&&r()}),e.addEventListener("click",c=>{c.target.closest(".lk-site-nav-link")&&e.classList.contains("is-open")&&r()}),document.addEventListener("keydown",c=>{c.key==="Escape"&&e.classList.contains("is-open")&&r()})}function O(){const n="func-gallery",e=document.getElementById(n);if(!e)return;const o=document.querySelectorAll(`[data-open="${n}"]`),d=e.querySelectorAll(`[data-close="${n}"]`),r=e.querySelector(".lk-gallery-track"),a=r?Array.from(r.querySelectorAll("img")):[],c=e.querySelector(".lk-gallery-prev"),t=e.querySelector(".lk-gallery-next"),s=e.querySelector("#lkGalleryTitle"),i=e.querySelector("#lkGalleryDesc"),u=e.querySelector("#lkGalleryCount"),f=e.querySelector(".lk-gallery");let m=0,h=0,p=!1;function y(){e.setAttribute("aria-hidden","false"),e.classList.add("is-open"),document.body.style.overflow="hidden",requestAnimationFrame(()=>k(!0))}function w(){e.setAttribute("aria-hidden","true"),e.classList.remove("is-open"),document.body.style.overflow=""}function b(){if(!a.length)return;const l=a[m],g=l.dataset.title||l.alt||"Tela",$=l.dataset.desc||"";s&&(s.textContent=g),i&&(i.textContent=$),u&&(u.textContent=`${m+1}/${a.length}`)}function k(l=!1){if(!r||a.length===0)return;const g=a[0].clientWidth;l&&(r.style.transition="none"),r.style.transform=`translateX(-${m*g}px)`,b(),l&&requestAnimationFrame(()=>{r.style.transition="transform 0.28s ease"})}function v(){a.length&&(m=(m+1)%a.length,k())}function x(){a.length&&(m=(m-1+a.length)%a.length,k())}o.forEach(l=>l.addEventListener("click",y)),d.forEach(l=>l.addEventListener("click",w)),c&&c.addEventListener("click",x),t&&t.addEventListener("click",v),a.forEach(l=>l.addEventListener("click",v)),document.addEventListener("keydown",l=>{e.classList.contains("is-open")&&(l.key==="Escape"&&w(),l.key==="ArrowRight"&&v(),l.key==="ArrowLeft"&&x())}),f&&(f.addEventListener("touchstart",l=>{e.classList.contains("is-open")&&(h=l.touches[0].clientX,p=!0)},{passive:!0}),f.addEventListener("touchmove",()=>{},{passive:!0}),f.addEventListener("touchend",l=>{if(!p)return;const g=l.changedTouches[0].clientX-h;Math.abs(g)>45&&(g<0?v():x()),p=!1})),window.addEventListener("resize",()=>k(!0)),b()}let E;function L(){return window.Swal?Promise.resolve(window.Swal):(E||(E=new Promise((n,e)=>{const o=document.createElement("script");o.src="https://cdn.jsdelivr.net/npm/sweetalert2@11",o.onload=()=>n(window.Swal),o.onerror=e,document.head.appendChild(o)})),E)}function z(){const n=document.querySelector("#contato.lk-contact");if(n){let s=function(i){c.forEach(u=>{const f=u.dataset.target===i;u.classList.toggle("is-active",f),u.setAttribute("aria-selected",f?"true":"false")}),t.forEach(u=>u.classList.toggle("is-active",u.dataset.panel===i))};var a=s;const c=n.querySelectorAll(".lk-toggle-btn"),t=n.querySelectorAll(".lk-contact-panel");c.forEach(i=>i.addEventListener("click",()=>s(i.dataset.target))),s("whatsapp")}const e=document.getElementById("contactForm"),o=document.getElementById("whatsapp");if(!e)return;let d=!1;const r=`${window.APP_BASE_URL}/api/contato/enviar`;if(o){const c=t=>t.length<=2?`(${t}`:t.length<=6?`(${t.slice(0,2)}) ${t.slice(2)}`:t.length<=10?`(${t.slice(0,2)}) ${t.slice(2,6)}-${t.slice(6)}`:`(${t.slice(0,2)}) ${t.slice(2,7)}-${t.slice(7,11)}`;o.addEventListener("input",t=>{const s=t.target.value.replace(/\D/g,"").slice(0,11);t.target.value=c(s)})}e.addEventListener("submit",async c=>{if(c.preventDefault(),d)return;d=!0;const t=e.querySelector('[type="submit"]'),s=t?t.textContent:null;t&&(t.disabled=!0,t.textContent="Enviando...");try{const i=await fetch(r,{method:"POST",body:new FormData(e)}),u=await i.text();let f=null;try{f=JSON.parse(u)}catch{}const m=f?.success===!0,h=f?.message??"Mensagem enviada com sucesso.",p=await L();if(i.ok&&m){await p.fire({icon:"success",title:"Mensagem enviada! ",text:h,confirmButtonText:"Ok",confirmButtonColor:"#e67e22"}),e.reset();return}const y=f?.message??f?.data?.message??`Erro ao enviar (status ${i.status}).`;await p.fire({icon:i.status===422?"warning":"error",title:i.status===422?"Verifique os campos":"Não foi possível enviar",text:y,confirmButtonColor:"#e67e22"})}catch(i){console.error(i);try{await(await L()).fire({icon:"error",title:"Erro de conexão",text:"Não foi possível enviar sua mensagem agora. Tente novamente.",confirmButtonColor:"#e67e22"})}catch{}}finally{d=!1,t&&(t.disabled=!1,t.textContent=s??"Enviar")}})}function D(){const n=document.getElementById("lkBackToTop");if(!n)return;const e=420;let o=!1;function d(){(window.scrollY||document.documentElement.scrollTop||0)>e?n.classList.add("is-visible"):n.classList.remove("is-visible"),o=!1}window.addEventListener("scroll",()=>{o||(window.requestAnimationFrame(d),o=!0)},{passive:!0}),n.addEventListener("click",()=>window.scrollTo({top:0,behavior:"smooth"})),d()}function F(){const n="lk_cookie_consent";if(localStorage.getItem(n))return;const e=(window.APP_BASE_URL||"")+"/";setTimeout(function(){const o=document.createElement("div");o.id="cookieConsent",o.innerHTML=`
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
                            <a href="${e}/privacidade">Saiba mais</a>
                        </p>
                        <div class="cookie-buttons">
                            <button id="cookieAccept" class="btn-accept">Aceitar</button>
                            <button id="cookieDecline" class="btn-decline">Recusar</button>
                        </div>
                    </div>
                </div>
            </div>
        `,document.body.appendChild(o),window.lucide&&lucide.createIcons(),document.getElementById("cookieAccept").addEventListener("click",function(){localStorage.setItem(n,"accepted"),d()}),document.getElementById("cookieDecline").addEventListener("click",function(){localStorage.setItem(n,"declined"),d()});function d(){const r=document.getElementById("cookieBannerInner");r&&(r.style.opacity="0",r.style.transform="translateY(20px)",r.style.transition="all .3s ease",setTimeout(()=>{const a=document.getElementById("cookieConsent");a&&a.remove()},300))}},1500)}P();function S(){_(),M(),O(),z(),D(),F()}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",S):S();
