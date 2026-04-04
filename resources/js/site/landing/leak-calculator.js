function formatCurrency(value) {
    return value.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function parseDigits(value) {
    return String(value || '').replace(/\D/g, '');
}

function animateResult(resultElement) {
    resultElement.classList.remove('lk-vazamento-resultado');
    void resultElement.offsetWidth;
    resultElement.classList.add('lk-vazamento-resultado');
}

export function init() {
    const roots = document.querySelectorAll('[data-leak-calculator]');

    roots.forEach((root) => {
        if (root.dataset.lkLeakCalculatorReady === 'true') {
            return;
        }

        const input = root.querySelector('[data-lk-vazamento-input]');
        const submitButton = root.querySelector('[data-lk-vazamento-submit]');
        const result = root.querySelector('[data-lk-vazamento-result]');
        const amount = root.querySelector('[data-lk-vazamento-amount]');

        if (!input || !submitButton || !result || !amount) {
            return;
        }

        root.dataset.lkLeakCalculatorReady = 'true';

        function resetResult() {
            amount.textContent = '';
            result.hidden = true;
        }

        function formatInput() {
            const digits = parseDigits(input.value);

            if (!digits) {
                input.value = '';
                resetResult();
                return;
            }

            const monthlyIncome = Number.parseInt(digits, 10) / 100;
            input.value = `R$ ${formatCurrency(monthlyIncome)}`;
            resetResult();
        }

        function calculate() {
            const digits = parseDigits(input.value);

            if (!digits) {
                resetResult();
                return;
            }

            const monthlyIncome = Number.parseInt(digits, 10) / 100;
            if (!Number.isFinite(monthlyIncome) || monthlyIncome <= 0) {
                resetResult();
                return;
            }

            const estimatedLeak = Math.round(monthlyIncome * 0.15 * 100) / 100;
            amount.textContent = formatCurrency(estimatedLeak);
            result.hidden = false;
            animateResult(result);
        }

        input.addEventListener('input', formatInput);
        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            calculate();
        });

        submitButton.addEventListener('click', calculate);
    });
}