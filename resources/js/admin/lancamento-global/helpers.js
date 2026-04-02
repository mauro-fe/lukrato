export function sortByLabel(items, resolver) {
    return [...items].sort((a, b) => {
        const labelA = String(resolver(a) || '').trim();
        const labelB = String(resolver(b) || '').trim();
        return labelA.localeCompare(labelB, 'pt-BR', { sensitivity: 'base' });
    });
}
