/**
 * Contact — Tabs (WhatsApp / E-mail) + form submit with SweetAlert2
 */

/** Lazy-load SweetAlert2 on first use */
let _swalPromise;
function loadSwal() {
    if (window.Swal) return Promise.resolve(window.Swal);
    if (!_swalPromise) {
        _swalPromise = new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
            s.onload = () => resolve(window.Swal);
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }
    return _swalPromise;
}

export function init() {
    /* ── Tab toggle (custom markup) ── */
    const root = document.querySelector('#contato.lk-contact');
    if (root) {
        const buttons = root.querySelectorAll('.lk-toggle-btn');
        const panels  = root.querySelectorAll('.lk-contact-panel');

        function show(target) {
            buttons.forEach(btn => {
                const active = btn.dataset.target === target;
                btn.classList.toggle('is-active', active);
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            panels.forEach(p => p.classList.toggle('is-active', p.dataset.panel === target));
        }

        buttons.forEach(btn => btn.addEventListener('click', () => show(btn.dataset.target)));
        show('whatsapp');
    }

    /* ── Contact form ── */
    const form = document.getElementById('contactForm');
    const whatsappInput = document.getElementById('whatsapp');

    if (!form) return;

    let sending = false;

    const apiUrl = `${window.APP_BASE_URL}/api/contato/enviar`;

    /* Phone mask */
    if (whatsappInput) {
        const formatPhone = (digits) => {
            if (digits.length <= 2)  return `(${digits}`;
            if (digits.length <= 6)  return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
            if (digits.length <= 10) return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
            return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7, 11)}`;
        };
        whatsappInput.addEventListener('input', (e) => {
            const digits = e.target.value.replace(/\D/g, '').slice(0, 11);
            e.target.value = formatPhone(digits);
        });
    }

    /* Submit */
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (sending) return;
        sending = true;

        const submitBtn = form.querySelector('[type="submit"]');
        const oldBtnText = submitBtn ? submitBtn.textContent : null;
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Enviando...'; }

        try {
            const res = await fetch(apiUrl, { method: 'POST', body: new FormData(form) });
            const raw = await res.text();
            let payload = null;
            try { payload = JSON.parse(raw); } catch (_) { /* ignore */ }

            const okByStatus  = payload?.status === 'success';
            const okBySuccess = payload?.success === true;
            const message     = payload?.message ?? payload?.data?.message ?? 'Mensagem enviada com sucesso.';

            const Swal = await loadSwal();

            if (res.ok && (okByStatus || okBySuccess)) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Mensagem enviada! ',
                    text: message,
                    confirmButtonText: 'Ok',
                    confirmButtonColor: '#e67e22'
                });
                form.reset();
                return;
            }

            const errorMsg = payload?.message ?? payload?.data?.message ?? `Erro ao enviar (status ${res.status}).`;
            await Swal.fire({
                icon: res.status === 422 ? 'warning' : 'error',
                title: res.status === 422 ? 'Verifique os campos' : 'Não foi possível enviar',
                text: errorMsg,
                confirmButtonColor: '#e67e22'
            });
        } catch (err) {
            console.error(err);
            try {
                const Swal = await loadSwal();
                await Swal.fire({
                    icon: 'error',
                    title: 'Erro de conexão',
                    text: 'Não foi possível enviar sua mensagem agora. Tente novamente.',
                    confirmButtonColor: '#e67e22'
                });
            } catch (_) { /* SweetAlert2 failed to load — fail silently */ }
        } finally {
            sending = false;
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = oldBtnText ?? 'Enviar'; }
        }
    });
}
