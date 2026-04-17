import { resolveBootstrapSource } from './bootstrap-context.js';

function normalizeBreadcrumbs(breadcrumbs) {
    if (!Array.isArray(breadcrumbs)) {
        return [];
    }

    return breadcrumbs.reduce((items, item, index) => {
        if (!item || typeof item !== 'object' || Array.isArray(item)) {
            return items;
        }

        const label = String(item.label || '').trim();
        if (label === '') {
            return items;
        }

        items.push({
            key: `breadcrumb-${index}-${label.toLowerCase()}`,
            label,
            url: String(item.url || '').trim(),
            icon: String(item.icon || '').trim(),
        });

        return items;
    }, []);
}

function normalizeNavigationModules(modules, currentMenu, scope) {
    if (!Array.isArray(modules)) {
        return [];
    }

    return modules.reduce((items, module, index) => {
        if (!module || typeof module !== 'object' || Array.isArray(module)) {
            return items;
        }

        const label = String(module.label || module.title || module.key || '').trim();
        if (label === '') {
            return items;
        }

        const route = String(module.route || '').trim();
        const menu = String(module.menu || '').trim();
        const icon = String(module.icon || '').trim();
        const key = String(module.key || `${scope}-${index}`).trim();

        items.push({
            key,
            label,
            route,
            menu,
            icon,
            isActive: currentMenu !== '' && menu === currentMenu,
        });

        return items;
    }, []);
}

function clearNode(node) {
    if (!node) {
        return;
    }

    while (node.firstChild) {
        node.removeChild(node.firstChild);
    }
}

function createTextNode(tagName, className, value) {
    const node = document.createElement(tagName);
    if (className !== '') {
        node.className = className;
    }
    node.textContent = value;
    return node;
}

function createNavigationLink(item, className, classPrefix) {
    const link = item.route !== ''
        ? document.createElement('a')
        : document.createElement('span');
    link.className = className;
    link.dataset.active = item.isActive ? 'true' : 'false';

    if (link instanceof HTMLAnchorElement) {
        link.href = item.route;
        if (item.isActive) {
            link.setAttribute('aria-current', 'page');
        }
    }

    const copy = document.createElement('span');
    copy.className = `${classPrefix}-link-copy`;
    copy.appendChild(createTextNode('strong', `${classPrefix}-link-label`, item.label));

    const metaParts = [];
    if (item.icon !== '') {
        metaParts.push(item.icon);
    }
    if (item.route !== '') {
        metaParts.push(`/${item.route}`);
    }

    if (metaParts.length > 0) {
        copy.appendChild(createTextNode('small', `${classPrefix}-link-meta`, metaParts.join(' · ')));
    }

    link.appendChild(copy);

    return link;
}

export function resolveNavigationShell(bootstrapPayload) {
    const bootstrap = resolveBootstrapSource(bootstrapPayload);
    const pageContext = bootstrap.pageContext && typeof bootstrap.pageContext === 'object' && !Array.isArray(bootstrap.pageContext)
        ? bootstrap.pageContext
        : {};
    const currentMenu = String(bootstrap.currentMenu || pageContext.currentMenu || '').trim();
    const currentViewId = String(bootstrap.currentViewId || pageContext.currentViewId || '').trim();
    const currentViewPath = String(bootstrap.currentViewPath || pageContext.currentViewPath || '').trim();
    const sidebar = pageContext.sidebar && typeof pageContext.sidebar === 'object' && !Array.isArray(pageContext.sidebar)
        ? pageContext.sidebar
        : {};
    const sidebarGroups = Object.entries(sidebar).reduce((groups, [groupLabel, modules], index) => {
        const label = String(groupLabel || '').trim();
        const normalizedModules = normalizeNavigationModules(modules, currentMenu, `sidebar-${index}`);
        if (normalizedModules.length === 0) {
            return groups;
        }

        groups.push({
            key: `group-${index}-${label.toLowerCase() || 'sem-grupo'}`,
            label: label !== '' ? label : 'Sem grupo',
            modules: normalizedModules,
        });

        return groups;
    }, []);

    return {
        currentMenu,
        currentViewId,
        currentViewPath,
        breadcrumbs: normalizeBreadcrumbs(pageContext.breadcrumbs),
        sidebarGroups,
        footerModules: normalizeNavigationModules(pageContext.footerModules, currentMenu, 'footer'),
    };
}

export function createBootstrapShellRenderer(options = {}) {
    const {
        currentMenuNode = null,
        currentViewNode = null,
        breadcrumbsNode = null,
        sidebarNode = null,
        footerNode = null,
        classPrefix = 'lk-bootstrap-shell',
        emptyMessages = {},
    } = options;

    const messages = {
        breadcrumbs: emptyMessages.breadcrumbs || 'Sem breadcrumbs no contexto atual.',
        sidebar: emptyMessages.sidebar || 'Nenhum grupo lateral disponível no bootstrap.',
        footer: emptyMessages.footer || 'Nenhum atalho de rodapé disponível.',
    };

    const renderBreadcrumbs = (navigation) => {
        clearNode(breadcrumbsNode);
        if (!breadcrumbsNode) {
            return;
        }

        if (!Array.isArray(navigation.breadcrumbs) || navigation.breadcrumbs.length === 0) {
            breadcrumbsNode.appendChild(createTextNode('span', `${classPrefix}-empty`, messages.breadcrumbs));
            return;
        }

        navigation.breadcrumbs.forEach((breadcrumb, index) => {
            const item = breadcrumb.url !== ''
                ? document.createElement('a')
                : document.createElement('span');
            item.className = `${classPrefix}-crumb`;

            if (item instanceof HTMLAnchorElement) {
                item.href = breadcrumb.url;
            }

            item.textContent = breadcrumb.icon !== ''
                ? `${breadcrumb.label} (${breadcrumb.icon})`
                : breadcrumb.label;
            breadcrumbsNode.appendChild(item);

            if (index < navigation.breadcrumbs.length - 1) {
                breadcrumbsNode.appendChild(createTextNode('span', `${classPrefix}-crumb-separator`, '/'));
            }
        });
    };

    const renderSidebar = (navigation) => {
        clearNode(sidebarNode);
        if (!sidebarNode) {
            return;
        }

        if (!Array.isArray(navigation.sidebarGroups) || navigation.sidebarGroups.length === 0) {
            sidebarNode.appendChild(createTextNode('p', `${classPrefix}-empty`, messages.sidebar));
            return;
        }

        navigation.sidebarGroups.forEach((group) => {
            const section = document.createElement('section');
            section.className = `${classPrefix}-group`;
            section.appendChild(createTextNode('h3', `${classPrefix}-group-title`, group.label));

            const list = document.createElement('div');
            list.className = `${classPrefix}-group-list`;

            group.modules.forEach((module) => {
                list.appendChild(createNavigationLink(module, `${classPrefix}-link`, classPrefix));
            });

            section.appendChild(list);
            sidebarNode.appendChild(section);
        });
    };

    const renderFooterModules = (navigation) => {
        clearNode(footerNode);
        if (!footerNode) {
            return;
        }

        if (!Array.isArray(navigation.footerModules) || navigation.footerModules.length === 0) {
            footerNode.appendChild(createTextNode('span', `${classPrefix}-empty`, messages.footer));
            return;
        }

        navigation.footerModules.forEach((module) => {
            footerNode.appendChild(createNavigationLink(module, `${classPrefix}-chip`, classPrefix));
        });
    };

    return {
        render(navigation) {
            if (currentMenuNode) {
                currentMenuNode.textContent = navigation.currentMenu !== '' ? navigation.currentMenu : 'menu indefinido';
            }

            if (currentViewNode) {
                currentViewNode.textContent = navigation.currentViewPath !== '' ? navigation.currentViewPath : 'view indefinida';
            }

            renderBreadcrumbs(navigation);
            renderSidebar(navigation);
            renderFooterModules(navigation);
        },
    };
}