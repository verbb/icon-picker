<template>
    <div>
        <div class="ipui-icon-input" :class="{ 'tippy-visible': tippyVisible }">
            <div v-if="selected && !tippyVisible" class="ipui-icon-input-item">
                <div class="ipui-icon-input-svg">
                    <component :is="getGlyphComponent(selected.type)" :icon="selected" />
                </div>

                <span class="ipui-icon-input-label">{{ selected.label }}</span>
            </div>

            <input
                :id="id"
                v-model="search"
                type="text"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                :class="inputClasses"
            >

            <input type="hidden" :name="name + '[icon]'" :value="selectedValue">

            <button v-if="selected" type="button" class="ipui-icon-input-delete" @click.prevent="deleteIcon">
                <!-- eslint-disable-next-line -->
                <svg aria-hidden="true" focusable="false" data-prefix="fal" data-icon="times" class="svg-inline--fa fa-times fa-w-10" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M193.94 256L296.5 153.44l21.15-21.15c3.12-3.12 3.12-8.19 0-11.31l-22.63-22.63c-3.12-3.12-8.19-3.12-11.31 0L160 222.06 36.29 98.34c-3.12-3.12-8.19-3.12-11.31 0L2.34 120.97c-3.12 3.12-3.12 8.19 0 11.31L126.06 256 2.34 379.71c-3.12 3.12-3.12 8.19 0 11.31l22.63 22.63c3.12 3.12 8.19 3.12 11.31 0L160 289.94 262.56 392.5l21.15 21.15c3.12 3.12 8.19 3.12 11.31 0l22.63-22.63c3.12-3.12 3.12-8.19 0-11.31L193.94 256z" /></svg>
            </button>
        </div>

        <div class="ipui-icons-pane js-ipui-tippy-template" style="display: none;">
            <div v-if="isFetching" class="ipui-no-icons">
                <span class="ipui-loading"></span>
            </div>

            <div v-else-if="Object.keys(iconsFiltered).length" class="ipui-icons-groups">
                <div v-for="(group, j) in iconsFiltered" :key="j" class="ipui-icons-group">
                    <span class="ipui-icons-group-name">{{ group.name }}</span>

                    <div class="ipui-icons">
                        <div v-for="(icon, i) in group.icons" :key="i" class="ipui-icon-wrap" :title="`${icon.label} (${group.name})`" @click.prevent="select(icon)">
                            <div class="ipui-icon-svg">
                                <component :is="getGlyphComponent(icon.type)" :icon="icon" />
                            </div>

                            <span class="ipui-icon-label">{{ icon.label }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="ipui-no-icons">
                {{ t('icon-picker', 'No icons match your query.') }}
            </div>
        </div>
    </div>
</template>

<script>
import { isEmpty, camelCase, startCase } from 'lodash-es';

import IconCss from '@components/IconCss.vue';
import IconGlyph from '@components/IconGlyph.vue';
import IconSprite from '@components/IconSprite.vue';
import IconSvg from '@components/IconSvg.vue';

import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/themes/light-border.css';

export default {
    name: 'IconPickerInput',

    components: {
        IconCss,
        IconGlyph,
        IconSprite,
        IconSvg,
    },

    props: {
        inputClasses: {
            type: Object,
            default: () => {},
        },

        name: {
            type: String,
            default: '',
        },

        value: {
            type: String,
            default: '',
        },
    },

    data() {
        return {
            tippy: null,
            id: `icon-picker-${Craft.randomString(10)}`,
            icons: [],
            search: '',
            selected: null,
            isFetching: false,
            tippyVisible: false,
        };
    },

    computed: {
        settings() {
            return this.$root.settings;
        },

        iconsFiltered() {
            if (isEmpty(this.icons)) {
                return [];
            }

            return this.icons.reduce((acc, iconGroup) => {
                const icons = iconGroup.icons.filter((icon) => {
                    return icon.label.toLowerCase().includes(this.search.toLowerCase());
                });

                return !icons.length ? acc : acc.concat({ ...iconGroup, icons });
            }, []);
        },

        selectedValue() {
            return this.selected ? this.selected.value : '';
        },
    },

    created() {
        if (this.value) {
            this.selected = JSON.parse(this.value);
        }
    },

    mounted() {
        const self = this;

        // Modify the jQuery data for `ElementEditor.js`, otherwise a change will be detected, and the draft saved.
        // This is due to jQuery kicking in and serializing the form before Vue kicks in.
        this.updateInitialSerializedValue();

        const template = this.$el.querySelector('.js-ipui-tippy-template');
        template.style.display = 'block';

        this.tippy = tippy(`#${this.id}`, {
            content: template,
            trigger: 'focus',
            allowHTML: true,
            arrow: true,
            interactive: true,
            placement: 'bottom-start',
            theme: 'light-border icon-picker',
            maxWidth: 'none',
            zIndex: 10,
            hideOnClick: false,

            onCreate(instance) {
                self.isFetching = false;

                instance.popper.style.width = '100%';
            },

            onShow(instance) {
                self.tippyVisible = true;

                // Have we cached already, or fetching?
                if (self.isFetching || self.icons.length) {
                    return;
                }

                self.isFetching = true;

                const data = {
                    fieldId: self.settings.fieldId,
                };

                Craft.sendActionRequest('POST', 'icon-picker/icons/icons-for-field', { data })
                    .then((response) => {
                        self.icons = response.data;
                    })
                    .catch((error) => {
                        instance.setContent(`Request failed. ${error}`);
                    })
                    .finally(() => {
                        self.isFetching = false;
                    });
            },

            onHide(instance) {
                self.isFetching = false;
                self.tippyVisible = false;
            },

            onHidden(instance) {
                // Always clear the search, but after transition. Otherwise, jumpy...
                self.search = '';
            },
        });

        this.loadSpriteSheets();
        this.loadFonts();
    },

    methods: {
        select(icon) {
            // Set the selected icon
            this.selected = icon;

            // Close the popover
            this.tippy[0].hide();
        },

        deleteIcon() {
            this.selected = null;
        },

        getGlyphComponent(type) {
            return startCase(camelCase(`Icon ${type}`)).replace(/ /g, '');
        },

        updateInitialSerializedValue() {
            const $mainForm = $('form#main-form');

            if ($mainForm.length) {
                const elementEditor = $mainForm.data('elementEditor');
                if (elementEditor) {
                    // Serialize the form again, now Vue is ready
                    const formData = elementEditor.serializeForm(true);

                    // Update the local cache, and the DOM cache
                    elementEditor.lastSerializedValue = formData;
                    $mainForm.data('initialSerializedValue', formData);
                }
            }
        },

        loadFonts() {
            for (let i = 0; i < this.settings.fonts.length; i++) {
                const font = this.settings.fonts[i];

                if (!Craft.IconPicker.Cache.fonts.includes(font.name)) {
                    Craft.IconPicker.Cache.fonts.push(font.name);

                    if (font.type == 'local') {
                        const css = '@font-face {' +
                            `font-family: "${font.name}";` +
                            `src: url("${font.url}");` +
                            'font-weight: normal;' +
                            'font-style: normal;' +
                            '}' +

                            `.${font.name} {` +
                            `font-family: "${font.name}" !important;` +
                            '}';

                        $('head').append(`<style type="text/css">${css}</style>`);
                    } else if (font.type == 'remote') {
                        // Support multiple remote stylesheets
                        if (!Array.isArray(font.url)) {
                            font.url = [font.url];
                        }

                        font.url.forEach((url) => {
                            const link = document.createElement('link');
                            link.href = url;
                            link.rel = 'stylesheet';
                            link.type = 'text/css';

                            document.getElementsByTagName('head')[0].appendChild(link);
                        });
                    }
                }
            }
        },

        loadSpriteSheets() {
            for (let i = 0; i < this.settings.spriteSheets.length; i++) {
                const sheet = this.settings.spriteSheets[i];

                if (!Craft.IconPicker.Cache.stylesheets.includes(sheet.name)) {
                    Craft.IconPicker.Cache.stylesheets.push(sheet.name);

                    fetch(sheet.url)
                        .then((response) => { return response.text(); })
                        .then((text) => {
                            this.injectSpriteSheet(sheet, text);
                        })
                        .catch((error) => {
                            console.log(error);
                        });
                }
            }
        },

        injectSpriteSheet(sheet, data) {
            const $div = document.createElement('div');
            $div.innerHTML = data;
            $div.setAttribute('id', `icon-picker-spritesheet-${sheet.name}`);
            $div.style.display = 'none';

            document.body.insertBefore($div, document.body.firstChild);
        },
    },
};

</script>

<style lang="scss">

// ==========================================================================
// Input
// ==========================================================================

.ipui-icon-input {
    display: flex;
    align-items: center;

    position: relative;
    width: 100%;
    height: 34px;
    border-radius: 3px;
    border: 1px solid rgba(96, 125, 159, 0.25);
    background-color: #fbfcfe;
    box-shadow: inset 0 1px 4px -1px rgba(96, 125, 159, 0.25);
    color: #3f4d5a;
    box-sizing: border-box;
    padding: 6px;
    cursor: text;
}

.ipui-icon-input input {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 100%;
    background: transparent;
    padding: 0;
    margin: 0;
    border: 0;
    appearance: none;
    padding: 0 9px;
    border-radius: 3px;

    // Helps with existing search text when transitioning closed
    color: transparent;

    &:focus {
        outline: none;
    }

    &.error {
        border: 1px solid #CF1124 !important
    }
}

.ipui-icon-input.tippy-visible input {
    color: inherit;
}

.ipui-icon-input-item {
    display: flex;
    align-items: center;
}

.ipui-icon-input-label {
    margin-right: 3px;
}

.ipui-icon-input-svg {
    width: 18px;
    height: 18px;
    font-size: 18px;
    line-height: 18px;
    margin-right: 8px;
    text-align: center;
    align-items: center;

    svg {
        width: 100%;
        height: 100%;
        display: block;
        fill: currentColor;
    }
}

.ipui-icon-input-delete {
    position: absolute;
    right: 8px;
    top: 50%;
    width: 20px;
    height: 20px;
    cursor: pointer;
    transform: translateY(-50%);
    z-index: 1;
    border-radius: 100%;
    border: 0;
    background: transparent;
    transition: all 0.2s ease;
    outline: none;

    &:hover {
        background: #596673;
        color: #fff;
    }

    svg {
        width: 100%;
        height: 100%;
        display: block;
        fill: currentColor;
    }
}


// ==========================================================================
// Dropdown
// ==========================================================================

.tippy-box[data-theme~='icon-picker'] >.tippy-content {
    overflow-y: auto;
    overflow-x: hidden;
    max-height: 50vh;
    min-height: 100px;
}

.ipui-no-icons {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #596673;
}

.ipui-icons-group-name {
    font-size: 11px;
    color: #606d7b;
    text-transform: uppercase;
    font-weight: bold;
    display: flex;
    margin: 5px;
}

.ipui-icon-wrap {
    width: 56px;
    height: 56px;
    color: #3f4d5a;
    cursor: pointer;
    overflow: hidden;
    display: inline-flex;
    flex-wrap: wrap;

    &:hover {
        background-color: #f3f7fc;
    }
}

.ipui-icon-label {
    display: none;
}

.ipui-icon-svg {
    width: 32px;
    height: 32px;
    font-size: 32px;
    line-height: 32px;
    margin: auto;
    text-align: center;

    svg {
        width: 100%;
        height: 100%;
        display: block;
        fill: currentColor;
    }
}


// ==========================================================================
// Loading
// ==========================================================================

@keyframes loading {
    0% {
        transform: rotate(0)
    } 100% {
        transform: rotate(360deg)
    }
}

.ipui-loading {
    position: relative;
    pointer-events: none;
    color: transparent !important;
    min-height: 1rem;

    &::after {
        position: absolute;
        display: block;
        height: 1rem;
        width: 1rem;
        margin-top: -0.65rem;
        margin-left: -0.65rem;
        border-width: 2px;
        border-style: solid;
        border-radius: 9999px;
        border-color: #E5422B;
        animation: loading 0.5s infinite linear;
        border-right-color: transparent;
        border-top-color: transparent;
        content: "";
        left: 50%;
        top: 50%;
        z-index: 1;
    }
}

.ipui-loading.ipui-loading-lg {
    min-height: 2rem;

    &::after {
        height: 2rem;
        width: 2rem;
        margin-top: -1rem;
        margin-left: -1rem;
    }
}

.ipui-loading.ipui-loading-sm {
    min-height: 0.75rem;

    &::after {
        height: 0.75rem;
        width: 0.75rem;
        margin-top: -0.5rem;
        margin-left: -0.375rem;
    }
}

.ipui-loading.ipui-loading-tiny {
    min-height: 0.5rem;

    &::after {
        height: 0.5rem;
        width: 0.5rem;
        margin-top: -6px;
        margin-left: -6px;
    }
}

.btn.submit.ipui-loading {
    color: transparent !important;
}

.btn.submit.ipui-loading::after {
    border-bottom-color: #fff;
    border-left-color: #fff;
}

.btn.ipui-loading {
    color: transparent !important;
}

</style>
