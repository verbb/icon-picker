// @vitest-environment happy-dom
/// <reference types="vite/client" />
import source from '../../../../redactor/dist/icon-picker.js?raw';
import { afterEach, expect, it } from 'vitest';
import { renderIconInto } from './renderIcon.js';

class RedactorDom {
    constructor(public nodes: HTMLElement[]) {}
    get length() { return this.nodes.length; }
    get() { return this.nodes[0]; }
    find(selector: string) { return new RedactorDom(this.nodes.flatMap((node) => Array.from(node.querySelectorAll<HTMLElement>(selector)))); }
    html(value?: string) {
        if (value === undefined) return this.get()?.innerHTML;
        this.nodes.forEach((node) => { node.innerHTML = value; });
        return this;
    }
}
const wrap = (html: string) => {
    const template = document.createElement('template');
    template.innerHTML = html;
    return new RedactorDom(Array.from(template.content.children) as HTMLElement[]);
};
afterEach(() => document.body.replaceChildren());

it('keeps original public sprite IDs through Redactor output and restores current CP namespaces on reload', async () => {
    let plugin: any;
    let convert: (wrapper: RedactorDom) => void;
    let unconvert: (wrapper: RedactorDom) => void;
    let saved = '';
    const editor = document.createElement('div');
    const context = {
        Redactor: { add: (_kind: string, _name: string, definition: unknown) => { plugin = definition; } },
        $: wrap, document, console,
        Craft: { t: (_category: string, value: string) => value, IconPicker: { loadRedactorSpriteSheets: async () => ({ 'outline-sprites': 'current-namespace' }) } },
    };
    new Function('Redactor', '$', 'Craft', 'document', source)(context.Redactor, context.$, context.Craft, document);
    const app = {
        cleaner: { addConvertRules: (_name: string, handler: typeof convert) => { convert = handler; }, addUnconvertRules: (_name: string, handler: typeof unconvert) => { unconvert = handler; } },
        toolbar: { addButton: () => null }, editor: { getElement: () => new RedactorDom([editor]) },
        api() {}, selection: { restore() {} },
        insertion: { insertNode: (node: RedactorDom) => { editor.appendChild(node.get()); } },
        broadcast: (event: string) => {
            if (event === 'hardsync') {
                const output = wrap(`<div>${editor.innerHTML}</div>`);
                unconvert(output);
                saved = output.html() as string;
            }
        },
    };
    plugin.init(app);
    plugin.start();
    const modal = document.createElement('div');
    modal.innerHTML = '<div class="ipui-icon-input-item"><div class="ipui-icon-input-svg"></div></div>';
    renderIconInto(modal.querySelector('.ipui-icon-input-svg')!, { type:'sprite',value:'heart',displayValue:'heart',spriteId:'old-namespace-heart',spriteSheet:'outline-sprites' });
    plugin.onmodal.iconPickerModal.insert.call(plugin, { $modalBody:new RedactorDom([modal]) }, null);
    expect(saved).toContain('href="#heart"');
    expect(saved).not.toContain('old-namespace');
    expect(saved).not.toContain('xlink:href');
    expect(saved).toContain('ip-sprite-sheet-outline-sprites');
    await Promise.resolve();
    expect(editor.querySelector('use')!.getAttribute('href')).toBe('#current-namespace-heart');
    editor.innerHTML = saved;
    convert!(new RedactorDom([editor]));
    expect(editor.querySelector('use')!.getAttribute('href')).toBe('#current-namespace-heart');
    app.broadcast('hardsync');
    expect(saved).toContain('href="#heart"');
    plugin.stop();
});
