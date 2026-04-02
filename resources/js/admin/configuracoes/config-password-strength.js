export function initConfigPasswordStrength() {
    const pwd = document.getElementById('nova_senha');
    const confirm = document.getElementById('conf_senha');
    const panel = document.getElementById('pwdStrengthProfile');
    const barFill = document.getElementById('pwdBarFillProfile');
    const levelEl = document.getElementById('pwdLevelProfile');
    const matchEl = document.getElementById('pwdMatchProfile');

    if (!pwd || !confirm || !panel || !barFill || !levelEl || !matchEl) {
        return;
    }

    const rules = [
        { id: 'prof-req-length', test: (value) => value.length >= 8 },
        { id: 'prof-req-lower', test: (value) => /[a-z]/.test(value) },
        { id: 'prof-req-upper', test: (value) => /[A-Z]/.test(value) },
        { id: 'prof-req-number', test: (value) => /[0-9]/.test(value) },
        { id: 'prof-req-special', test: (value) => /[^a-zA-Z0-9]/.test(value) },
    ];

    const levels = [
        { label: '' },
        { label: 'Muito fraca' },
        { label: 'Fraca' },
        { label: 'Razoavel' },
        { label: 'Boa' },
        { label: 'Forte' },
    ];

    const saveBtn = document.getElementById('btn-save-seguranca');
    const senhaAtualInput = document.getElementById('senha_atual');

    function updateSaveBtn() {
        if (!saveBtn) {
            return;
        }

        const value = pwd.value;
        const allRulesPass = value && rules.every((rule) => rule.test(value));
        const matches = value && confirm.value && value === confirm.value;
        const hasCurrentPassword = senhaAtualInput && senhaAtualInput.value.length > 0;

        saveBtn.disabled = !(allRulesPass && matches && hasCurrentPassword);
    }

    function checkMatch() {
        const passwordValue = pwd.value;
        const confirmValue = confirm.value;

        if (!confirmValue) {
            matchEl.classList.remove('visible');
            updateSaveBtn();
            return;
        }

        matchEl.classList.add('visible');
        const isMatch = passwordValue === confirmValue;
        matchEl.classList.toggle('match', isMatch);
        matchEl.classList.toggle('no-match', !isMatch);

        const icon = matchEl.querySelector('.match-icon');
        const text = matchEl.querySelector('.match-text');
        if (icon) {
            icon.innerHTML = isMatch ? '<i data-lucide="check"></i>' : '<i data-lucide="x"></i>';
        }
        if (text) {
            text.textContent = isMatch ? 'Senhas coincidem' : 'Senhas nao coincidem';
        }

        updateSaveBtn();
    }

    if (saveBtn) {
        saveBtn.disabled = true;
    }

    pwd.addEventListener('focus', () => {
        panel.classList.add('visible');
    });

    pwd.addEventListener('input', () => {
        const value = pwd.value;
        let score = 0;

        if (!value) {
            panel.classList.remove('visible');
            barFill.className = 'pwd-bar-fill';
            levelEl.className = 'pwd-level';
            levelEl.textContent = '';

            rules.forEach((rule) => {
                const ruleEl = document.getElementById(rule.id);
                if (ruleEl) {
                    ruleEl.classList.remove('pass');
                }
            });

            updateSaveBtn();
            return;
        }

        panel.classList.add('visible');

        rules.forEach((rule) => {
            const ruleEl = document.getElementById(rule.id);
            const passed = rule.test(value);
            if (ruleEl) {
                ruleEl.classList.toggle('pass', passed);
            }
            if (passed) {
                score += 1;
            }
        });

        barFill.className = `pwd-bar-fill${score > 0 ? ` s${score}` : ''}`;
        levelEl.className = `pwd-level${score > 0 ? ` s${score}` : ''}`;
        levelEl.textContent = levels[score].label;

        if (confirm.value) {
            checkMatch();
        }

        updateSaveBtn();
    });

    confirm.addEventListener('input', checkMatch);
    pwd.addEventListener('input', () => {
        if (confirm.value) {
            checkMatch();
        }
    });

    if (senhaAtualInput) {
        senhaAtualInput.addEventListener('input', updateSaveBtn);
    }
}
