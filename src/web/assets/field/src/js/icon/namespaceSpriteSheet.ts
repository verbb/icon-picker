/** Keep symbols and their local references inside their owning collection. */
export const namespaceSpriteSheet = (root: Element, namespace: string): void => {
    const ids = new Map<string, string>();
    root.querySelectorAll('[id]').forEach((element) => {
        const id = element.id;
        ids.set(id, `${namespace}-${id}`);
        element.id = `${namespace}-${id}`;
    });

    const replaceUrls = (value: string): string => value.replace(
        /url\(\s*(['"]?)#([^'"\s)]+)\1\s*\)/g,
        (reference, _quote: string, id: string) => ids.has(id) ? `url(#${ids.get(id)})` : reference,
    );

    root.querySelectorAll('*').forEach((element) => {
        for (const attribute of Array.from(element.attributes)) {
            if (attribute.name === 'id') continue;
            let value = replaceUrls(attribute.value);
            if (attribute.localName === 'href' && value.startsWith('#') && ids.has(value.slice(1))) {
                value = `#${ids.get(value.slice(1))}`;
            } else if (['aria-labelledby', 'aria-describedby'].includes(attribute.name)) {
                value = value.replace(/\S+/g, (id) => ids.get(id) ?? id);
            }
            attribute.value = value;
        }
    });

    // Only rewrite CSS selectors before an opening brace, preserving colour values.
    const selectorIds = new Map(Array.from(ids, ([id, value]) => [CSS.escape(id), CSS.escape(value)]));
    const escaped = Array.from(selectorIds.keys()).sort((a, b) => b.length - a.length)
        .map((id) => id.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
    if (escaped.length) {
        const selector = new RegExp(`#(${escaped.join('|')})(?![\\w-])`, 'g');
        root.querySelectorAll('style').forEach((style) => {
            style.textContent = replaceUrls(style.textContent ?? '').replace(/([^{}]+)\{/g, (_match, selectors: string) =>
                `${selectors.replace(selector, (_reference, id: string) => `#${selectorIds.get(id)}`)}{`,
            );
        });
    }
};
