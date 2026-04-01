/**
 * ============================================================================
 * LUKRATO - Categorias / Icon Picker
 * ============================================================================
 * Handles create/edit icon picker UI and icon filtering behavior.
 * ============================================================================
 */

export function createIconPickerModule({
    STATE,
    Utils,
    AVAILABLE_ICONS,
    ICON_GROUPS,
}) {
    function getRecentIcons() {
        try {
            return JSON.parse(localStorage.getItem('lk_recent_icons') || '[]').slice(0, 8);
        } catch {
            return [];
        }
    }

    function pushRecentIcon(iconName) {
        const recent = getRecentIcons().filter((name) => name !== iconName);
        recent.unshift(iconName);
        localStorage.setItem('lk_recent_icons', JSON.stringify(recent.slice(0, 8)));
    }

    function renderIconGrid(containerId, onSelect) {
        const container = document.getElementById(containerId);
        if (!container) return;

        let html = '';

        const recent = getRecentIcons();
        if (recent.length > 0) {
            html += '<div class="icon-group-label">Recentes</div><div class="icon-group-grid">';
            html += recent
                .map((name) => `<button type="button" class="icon-pick-item" data-icon="${name}" title="${name}"><i data-lucide="${name}"></i></button>`)
                .join('');
            html += '</div>';
        }

        for (const group of ICON_GROUPS) {
            html += `<div class="icon-group-label">${group.label}</div><div class="icon-group-grid">`;
            html += group.icons
                .map((name) => `<button type="button" class="icon-pick-item" data-icon="${name}" title="${name}"><i data-lucide="${name}"></i></button>`)
                .join('');
            html += '</div>';
        }

        container.innerHTML = html;

        if (!container._lkDelegated) {
            container.addEventListener('click', (event) => {
                const item = event.target.closest('.icon-pick-item');
                if (!item) return;
                onSelect(item.dataset.icon);
            });
            container._lkDelegated = true;
        }

        Utils.processNewIcons();
    }

    function closeIconPicker() {
        const drawer = document.getElementById('iconPickerDrawer');
        if (drawer) drawer.classList.remove('open');
        toggleIconPickerBackdrop(false);
    }

    function toggleIconPickerBackdrop(show) {
        let backdrop = document.getElementById('iconPickerBackdrop');
        if (show && !backdrop) {
            backdrop = document.createElement('div');
            backdrop.id = 'iconPickerBackdrop';
            backdrop.className = 'icon-picker-backdrop';
            backdrop.addEventListener('click', closeIconPicker);
            document.body.appendChild(backdrop);
        }
        if (backdrop) backdrop.classList.toggle('show', show);
    }

    function highlightSelectedIcon(containerId, iconName) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.querySelectorAll('.icon-pick-item').forEach((item) => {
            item.classList.toggle('selected', item.dataset.icon === iconName);
        });
    }

    function updateIconPreview(iconName) {
        const inner = document.querySelector('#iconPreviewRing .create-icon-inner');
        if (!inner) return;

        inner.innerHTML = `<i data-lucide="${iconName}" class="create-main-icon" id="iconPreview"></i>`;
        Utils.processNewIcons();

        const ring = document.getElementById('iconPreviewRing');
        if (ring) {
            ring.style.transform = 'scale(1.1)';
            setTimeout(() => {
                ring.style.transform = '';
            }, 300);
        }
    }

    function selectIcon(iconName) {
        STATE.selectedIcon = iconName;

        const iconInput = document.getElementById('catIcone');
        if (iconInput) iconInput.value = iconName;

        updateIconPreview(iconName);
        highlightSelectedIcon('iconPickerGrid', iconName);
        pushRecentIcon(iconName);
        closeIconPicker();
    }

    function filterGrid(containerId, query) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const normalizedQuery = query.toLowerCase().trim();

        container.querySelectorAll('.icon-group-grid').forEach((grid) => {
            let visibleCount = 0;

            grid.querySelectorAll('.icon-pick-item').forEach((item) => {
                if (!normalizedQuery) {
                    item.style.display = '';
                    visibleCount++;
                    return;
                }

                const iconName = item.dataset.icon;
                const iconData = AVAILABLE_ICONS.find((icon) => icon.name === iconName);
                const searchText = `${iconName} ${iconData?.label || ''}`.toLowerCase();
                const isVisible = searchText.includes(normalizedQuery);
                item.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });

            const label = grid.previousElementSibling;
            if (label?.classList.contains('icon-group-label')) {
                label.style.display = visibleCount > 0 || !normalizedQuery ? '' : 'none';
            }
            grid.style.display = visibleCount > 0 || !normalizedQuery ? '' : 'none';
        });
    }

    function filterIcons(query) {
        filterGrid('iconPickerGrid', query);
    }

    function filterEditIcons(query) {
        filterGrid('editIconPickerGrid', query);
    }

    function toggleIconPicker() {
        const drawer = document.getElementById('iconPickerDrawer');
        if (!drawer) return;

        if (!STATE._iconGridCreateReady) {
            renderIconGrid('iconPickerGrid', (icon) => selectIcon(icon));
            STATE._iconGridCreateReady = true;
        }

        drawer.classList.toggle('open');
        toggleIconPickerBackdrop(drawer.classList.contains('open'));

        if (drawer.classList.contains('open')) {
            const input = document.getElementById('iconSearchInput');
            if (input) {
                input.value = '';
                input.focus();
            }
            filterIcons('');
            highlightSelectedIcon('iconPickerGrid', STATE.selectedIcon);
        }
    }

    function selectEditIcon(iconName) {
        STATE.editSelectedIcon = iconName;

        const editInput = document.getElementById('editCategoriaIcone');
        if (editInput) editInput.value = iconName;

        const preview = document.getElementById('editIconPreview');
        if (preview) {
            preview.innerHTML = `<i data-lucide="${iconName}"></i>`;
            Utils.processNewIcons();
        }

        highlightSelectedIcon('editIconPickerGrid', iconName);
        pushRecentIcon(iconName);

        const panel = document.getElementById('editIconPickerPanel');
        if (panel) panel.classList.add('d-none');
    }

    function toggleEditIconPicker() {
        const panel = document.getElementById('editIconPickerPanel');
        if (!panel) return;

        if (!STATE._iconGridEditReady) {
            renderIconGrid('editIconPickerGrid', (icon) => selectEditIcon(icon));
            STATE._iconGridEditReady = true;
        }

        panel.classList.toggle('d-none');
        if (!panel.classList.contains('d-none')) {
            const input = document.getElementById('editIconSearchInput');
            if (input) {
                input.value = '';
                input.focus();
            }
            filterEditIcons('');
            highlightSelectedIcon('editIconPickerGrid', STATE.editSelectedIcon);
        }
    }

    return {
        toggleIconPicker,
        closeIconPicker,
        filterIcons,
        selectIcon,
        toggleEditIconPicker,
        filterEditIcons,
        highlightSelectedIcon,
    };
}
