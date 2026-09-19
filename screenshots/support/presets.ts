import type { ScreenshotStep } from '@verbb/craft-screenshots/types';

export function createIconPickerFrameStep(): ScreenshotStep {
    return {
        type: 'evaluate',
        expression: `
            (() => {
                document.getElementById('icon-picker-screenshot-frame')?.remove();
                const input = document.querySelector('.ipui-input-component');
                const field = input?.closest('.field');

                if (!(field instanceof HTMLElement)) {
                    throw new Error('Icon Picker field was not found.');
                }

                const frame = document.createElement('div');
                frame.id = 'icon-picker-screenshot-frame';
                frame.style.cssText = [
                    'position:fixed',
                    'left:0',
                    'top:0',
                    'width:683px',
                    'height:360px',
                    'box-sizing:border-box',
                    'padding:18px',
                    'overflow:hidden',
                    'background:#ffffff',
                    'z-index:2147483646',
                ].join(';');
                field.style.margin = '0';
                frame.appendChild(field);
                document.body.appendChild(frame);
                document.documentElement.style.background = '#ffffff';
                document.body.style.margin = '0';
                document.body.style.overflow = 'hidden';
            })();
        `,
    };
}

export function positionIconPickerMenuStep(): ScreenshotStep {
    return {
        type: 'evaluate',
        expression: `
            (() => {
                const frame = document.getElementById('icon-picker-screenshot-frame');
                const popper = document.querySelector('[data-tippy-root]');

                if (!(frame instanceof HTMLElement) || !(popper instanceof HTMLElement)) {
                    throw new Error('Icon Picker popup was not found.');
                }

                frame.appendChild(popper);
                popper.style.position = 'absolute';
                popper.style.inset = '76px 18px auto 18px';
                popper.style.width = '647px';
                popper.style.transform = 'none';
                popper.style.zIndex = '1';
                const box = popper.querySelector('.tippy-box');
                if (box instanceof HTMLElement) {
                    box.style.maxWidth = 'none';
                    box.style.width = '647px';
                }
            })();
        `,
    };
}
