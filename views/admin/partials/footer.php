</main>
<?php loadPageCss('admin-partials-footer'); ?>

<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-8 mx-auto text-center mt-1">
                <span>© 2025 Lukrato -</span>
                <span>Desenvolvido por <a href="https://github.com/mauro-fe" target="_blank" rel="noopener">Mauro Felix
                    </a></span>
                <span> & <a href="https://github.com/Joseph-0505" target="_blank" rel="noopener"> José Victor</a></span>
            </div>
        </div>
    </div>
</footer>
<script src="<?= BASE_URL ?>assets/js/core/popper.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/core/bootstrap.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/plugins/perfect-scrollbar.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/plugins/smooth-scrollbar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script async defer src="https://buttons.github.io/buttons.js"></script>

<script src="<?= BASE_URL ?>assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>

<?php loadPageJs(); ?>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>


<script>
    // Inicialização do AOS com todas as configurações possíveis
    AOS.init({
        // offset: distância (em px) do topo da tela antes do elemento começar a animar
        offset: 120, // padrão: 120

        // delay: atraso antes da animação começar (em milissegundos)
        delay: 0, // padrão: 0

        // duration: duração da animação (em milissegundos)
        duration: 1000, // padrão: 400

        // easing: tipo de curva de aceleração (como no CSS)
        // opções comuns: 'ease', 'ease-in', 'ease-out', 'ease-in-out', 'linear'
        easing: 'ease',

        // once: se true, anima apenas a primeira vez que o elemento entra na tela
        // se false, anima toda vez que o elemento reaparece
        once: false, // padrão: false

        // mirror: se true, o elemento anima também ao rolar para cima (saindo da tela)
        mirror: true, // padrão: false

        // anchorPlacement: define qual parte do elemento ativa a animação
        // opções: 'top-bottom', 'center-bottom', 'bottom-bottom', 'top-center', etc.
        anchorPlacement: 'top-bottom',

        // startEvent: evento que inicializa o AOS (pode ser 'DOMContentLoaded', 'load', etc.)
        startEvent: 'DOMContentLoaded',

        // disable: permite desativar o AOS em determinadas condições
        // pode ser 'mobile', 'phone', 'tablet', 'true' (desativa sempre), ou função personalizada
        disable: false,

        // debounceDelay: tempo em ms para reagir a redimensionamento de janela (melhora performance)
        debounceDelay: 50,

        // throttleDelay: tempo em ms para reagir ao scroll (melhora performance)
        throttleDelay: 99,

        // useClassNames: se true, adiciona classes AOS-* no elemento para personalização manual via CSS
        useClassNames: false,

        // disableMutationObserver: se true, desativa o observador que detecta elementos novos no DOM
        // útil se você tiver muitos elementos dinâmicos e quiser performance máxima
        disableMutationObserver: false,

        // animatedClassName: nome da classe adicionada quando o elemento está animando
        animatedClassName: 'aos-animate',

        // initClassName: classe adicionada ao body quando o AOS está pronto
        initClassName: 'aos-init'
    });
</script>
</body>

</html>