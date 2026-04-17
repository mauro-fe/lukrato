export function resolveDisplayNameValue(primaryValue, fallbackValue = '') {
    const primary = String(primaryValue ?? '').trim();
    if (primary !== '') {
        return primary;
    }

    return String(fallbackValue ?? '').trim();
}

export function buildDisplayNameSnapshot(displayName) {
    const resolvedDisplayName = resolveDisplayNameValue(displayName);
    const firstName = resolvedDisplayName.split(/\s+/).filter(Boolean)[0] || 'usuario';
    const initial = firstName.charAt(0).toUpperCase() || 'U';

    return {
        displayName: resolvedDisplayName,
        firstName,
        initial,
    };
}