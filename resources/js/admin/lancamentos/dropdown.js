const VIEWPORT_PADDING = 8;
const MENU_OFFSET = 4;

function getMenuState(menu) {
    if (!menu._lkDropdownState) {
        menu._lkDropdownState = {
            dropdown: null,
            card: null,
            placeholder: null,
            cleanupFns: []
        };
    }

    return menu._lkDropdownState;
}

function clearMenuListeners(menu) {
    const state = getMenuState(menu);

    state.cleanupFns.forEach((cleanup) => {
        try {
            cleanup();
        } catch (_) {
            // Ignore stale listener cleanup.
        }
    });

    state.cleanupFns = [];
}

function positionDropdownMenu(trigger, menu) {
    const triggerRect = trigger.getBoundingClientRect();
    const menuWidth = menu.offsetWidth || menu.scrollWidth || 200;
    const menuHeight = menu.offsetHeight || menu.scrollHeight || 200;

    const spaceBelow = window.innerHeight - triggerRect.bottom;
    let top = triggerRect.bottom + MENU_OFFSET;
    if (spaceBelow < menuHeight + VIEWPORT_PADDING) {
        top = triggerRect.top - menuHeight - MENU_OFFSET;
    }

    let left = triggerRect.right - menuWidth;

    top = Math.max(VIEWPORT_PADDING, Math.min(top, window.innerHeight - menuHeight - VIEWPORT_PADDING));
    left = Math.max(VIEWPORT_PADDING, Math.min(left, window.innerWidth - menuWidth - VIEWPORT_PADDING));

    menu.style.top = `${Math.round(top)}px`;
    menu.style.left = `${Math.round(left)}px`;
}

export function resolveDropdownMenu(dropdown) {
    if (!dropdown) return null;

    const menu = dropdown._lkDropdownMenu || dropdown.querySelector('.lk-dropdown-menu');
    if (menu) {
        dropdown._lkDropdownMenu = menu;
    }

    return menu || null;
}

export function closeDropdownMenu(menu) {
    if (!menu) return;

    const state = getMenuState(menu);
    clearMenuListeners(menu);

    menu.classList.remove('open');
    menu.style.cssText = '';
    state.card?.classList.remove('lk-dropdown-open');

    if (state.placeholder?.parentNode) {
        state.placeholder.parentNode.replaceChild(menu, state.placeholder);
    } else if (state.dropdown?.isConnected && menu.parentElement !== state.dropdown) {
        state.dropdown.appendChild(menu);
    }

    state.placeholder = null;
}

export function closeAllDropdownMenus(exceptMenu = null) {
    document.querySelectorAll('.lk-dropdown-menu.open').forEach((menu) => {
        if (menu !== exceptMenu) {
            closeDropdownMenu(menu);
        }
    });
}

function bindCloseHandlers(menu, dropdown) {
    const state = getMenuState(menu);
    clearMenuListeners(menu);

    const handleDocumentClick = (event) => {
        if (dropdown.contains(event.target) || menu.contains(event.target)) {
            return;
        }

        closeDropdownMenu(menu);
    };

    const handleWindowChange = () => closeDropdownMenu(menu);
    const handleEscape = (event) => {
        if (event.key === 'Escape') {
            closeDropdownMenu(menu);
        }
    };

    document.addEventListener('click', handleDocumentClick, true);
    window.addEventListener('scroll', handleWindowChange, true);
    window.addEventListener('resize', handleWindowChange);
    document.addEventListener('keydown', handleEscape, true);

    state.cleanupFns = [
        () => document.removeEventListener('click', handleDocumentClick, true),
        () => window.removeEventListener('scroll', handleWindowChange, true),
        () => window.removeEventListener('resize', handleWindowChange),
        () => document.removeEventListener('keydown', handleEscape, true)
    ];
}

export function toggleDropdownMenu({ trigger, dropdown, menu, card }) {
    if (!trigger || !dropdown || !menu) return false;

    if (menu.classList.contains('open')) {
        closeDropdownMenu(menu);
        return false;
    }

    closeAllDropdownMenus(menu);

    const state = getMenuState(menu);
    state.dropdown = dropdown;
    state.card = card || trigger.closest('.lk-txn-card') || null;
    dropdown._lkDropdownMenu = menu;

    if (menu.parentElement !== document.body) {
        const placeholder = document.createComment('lk-dropdown-menu-placeholder');
        menu.parentNode?.insertBefore(placeholder, menu.nextSibling);
        state.placeholder = placeholder;
        document.body.appendChild(menu);
    }

    state.card?.classList.add('lk-dropdown-open');
    menu.classList.add('open');
    positionDropdownMenu(trigger, menu);
    bindCloseHandlers(menu, dropdown);

    return true;
}
