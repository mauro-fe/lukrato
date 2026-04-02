export function isVisibleElement(element) {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    if (element.hidden
        || element.closest('[hidden]')
        || element.closest('[aria-hidden="true"]')
        || element.closest('[inert]')
        || element.closest('[data-lk-tour-ignore="true"]')
        || element.classList.contains('progressive-hidden')
        || element.closest('.progressive-hidden')) {
        return false;
    }

    const styles = window.getComputedStyle(element);
    const rect = element.getBoundingClientRect();

    return styles.display !== 'none'
        && styles.visibility !== 'hidden'
        && styles.opacity !== '0'
        && rect.width > 0
        && rect.height > 0;
}

export function resolveElement(target) {
    if (!target) {
        return null;
    }

    const candidate = resolveElementCandidate(target);
    if (!candidate) {
        return null;
    }

    if (candidate instanceof HTMLElement && isVisibleElement(candidate)) {
        return candidate;
    }

    return null;
}

export function resolveElementCandidate(target) {
    if (!target) {
        return null;
    }

    if (target instanceof HTMLElement) {
        return target;
    }

    if (Array.isArray(target)) {
        for (const candidate of target) {
            const resolved = resolveElement(candidate);
            if (resolved) {
                return resolved;
            }
        }
        return null;
    }

    if (typeof target === 'string') {
        return document.querySelector(target);
    }

    if (typeof target === 'function') {
        return resolveElement(target());
    }

    return null;
}

export function isScrollableElement(element) {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    const styles = window.getComputedStyle(element);
    const overflowY = styles.overflowY || styles.overflow;
    return /(auto|scroll|overlay)/.test(overflowY)
        && element.scrollHeight > element.clientHeight + 8;
}

export function isElementComfortablyInViewport(element, options = {}) {
    if (!(element instanceof HTMLElement)) {
        return true;
    }

    const { topOffset = 84, bottomOffset = 24 } = options;
    const rect = element.getBoundingClientRect();
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

    return rect.top >= topOffset
        && rect.bottom <= (viewportHeight - bottomOffset);
}

export function getScrollableAncestors(element) {
    const ancestors = [];

    let current = element?.parentElement || null;
    while (current && current !== document.body) {
        if (isScrollableElement(current)) {
            ancestors.push(current);
        }
        current = current.parentElement;
    }

    return ancestors;
}

export function ensureElementInViewport(element, options = {}) {
    if (!(element instanceof HTMLElement)) {
        return;
    }

    const { topOffset = 84, bottomOffset = 24 } = options;

    if (isElementComfortablyInViewport(element, { topOffset, bottomOffset })) {
        return;
    }

    const scrollableAncestors = getScrollableAncestors(element);
    if (scrollableAncestors.length > 0) {
        scrollableAncestors.forEach((container) => {
            const containerRect = container.getBoundingClientRect();
            const elementRect = element.getBoundingClientRect();

            if (elementRect.top < containerRect.top + topOffset
                || elementRect.bottom > containerRect.bottom - bottomOffset) {
                const desiredTop = element.offsetTop - container.clientHeight / 2 + element.clientHeight / 2;
                container.scrollTo({
                    top: Math.max(0, desiredTop),
                    behavior: 'smooth',
                });
            }
        });
    }

    element.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
        inline: 'nearest',
    });

    window.scrollBy({
        top: -(topOffset - 24),
        left: 0,
        behavior: 'smooth',
    });
}

export function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

export function getOppositeSide(side) {
    switch (side) {
        case 'top':
            return 'bottom';
        case 'bottom':
            return 'top';
        case 'left':
            return 'right';
        case 'right':
            return 'left';
        default:
            return 'center';
    }
}
