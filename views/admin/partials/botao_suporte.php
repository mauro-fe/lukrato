<style>
.lk-support-button {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 58px;
    height: 58px;
    background: var(--color-primary);
    color: #fff;
    border-radius: var(--radius-full, 50%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: var(--shadow-md);
    transition: var(--transition);
    z-index: 9999;
}

.lk-support-button:hover {
    border-color: var(--color-primary);
    background-color: color-mix(in srgb,
            var(--glass-bg) 5%,
            var(--color-primary) 70%);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
</style>

<!-- Botão Flutuante de Suporte -->
<a href="#" class="lk-support-button" onclick="openSupportModal()" class="lk-support-button" title="Fale com o Suporte">
    <i class="fas fa-headset"></i>
</a>

<script>
function openSupportModal() {
    Swal.fire({
        title: "Suporte Lukrato",
        html: `
            <p>Envie um e-mail para: <b>lukratosistema@gmail.com</b></p>
        `,
        icon: "info",
        confirmButtonText: "Ok"
    });
}
</script>