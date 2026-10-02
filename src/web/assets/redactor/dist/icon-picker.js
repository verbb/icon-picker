(function ($R) {
    $R.add('plugin', 'icon-picker', {
        init: function(app) {
            this.app = app;
            this.lang = app.lang;
            this.inline = app.inline;
            this.toolbar = app.toolbar;
            this.insertion = app.insertion;
            this.spriteNamespaces = {};
        },

        start: function() {
            this.stopped = false;
            this.app.cleaner.addConvertRules('icon-picker-sprites', function($wrapper) {
                this.rewriteSprites($wrapper.get(), this.spriteNamespaces);
            }.bind(this));
            this.app.cleaner.addUnconvertRules('icon-picker-sprites', function($wrapper) {
                this.rewriteSprites($wrapper.get(), null);
            }.bind(this));
            this.button = this.toolbar.addButton('icon-picker', {
                title: Craft.t('icon-picker', 'Icon Picker'),
                api: 'plugin.icon-picker.open',
                icon: '<i class="verbb icon icon-picker"></i>',
            });
        },

        onstarted: function() {
            this.loadSpritePreviews();
        },

        stop: function() {
            this.stopped = true;
            if (this.readyHandler) document.removeEventListener('icon-picker-ready', this.readyHandler);
        },

        rewriteSprites: function(root, namespaces) {
            // Older rich text has no source marker. Resolve it only when one sheet matches.
            if (namespaces) {
                root.querySelectorAll('.icon-picker-redactor-icon svg:not([class*="ip-sprite-sheet-"])').forEach(function(svg) {
                    var use = svg.querySelector('use');
                    var href = use?.getAttribute('href') || use?.getAttribute('xlink:href');
                    if (!href?.startsWith('#')) return;
                    var symbol = href.slice(1);
                    var matches = Object.keys(namespaces).filter(function(sheet) {
                        return document.getElementById(namespaces[sheet] + '-' + symbol);
                    });
                    if (matches.length === 1) svg.classList.add('ip-sprite-sheet-' + encodeURIComponent(matches[0]), 'ip-sprite-symbol-' + encodeURIComponent(symbol));
                });
            }
            root.querySelectorAll('svg[class*="ip-sprite-sheet-"]').forEach(function(svg) {
                var classes = Array.from(svg.classList);
                var sheet = classes.find(function(name) { return name.startsWith('ip-sprite-sheet-'); });
                var symbol = classes.find(function(name) { return name.startsWith('ip-sprite-symbol-'); });
                if (!sheet || !symbol) return;
                try {
                    sheet = decodeURIComponent(sheet.slice('ip-sprite-sheet-'.length));
                    symbol = decodeURIComponent(symbol.slice('ip-sprite-symbol-'.length));
                } catch (error) { return; }
                var use = svg.querySelector('use');
                if (!use || (namespaces && !namespaces[sheet])) return;
                use.removeAttributeNS('http://www.w3.org/1999/xlink', 'href');
                use.removeAttribute('xlink:href');
                use.setAttribute('href', '#' + (namespaces ? namespaces[sheet] + '-' : '') + symbol);
            });
        },

        loadSpritePreviews: function() {
            var root = this.app.editor.getElement().get();
            if (!root.querySelector('.icon-picker-redactor-icon svg use')) return;
            var load = function() {
                if (this.stopped) return;
                Craft.IconPicker.loadRedactorSpriteSheets().then(function(namespaces) {
                    if (this.stopped) return;
                    this.spriteNamespaces = namespaces;
                    this.rewriteSprites(root, namespaces);
                }.bind(this)).catch(function(error) {
                    console.error('[icon-picker] Failed to load Redactor sprite previews', error);
                });
            }.bind(this);
            if (Craft.IconPicker?.loadRedactorSpriteSheets) {
                load();
            } else {
                this.readyHandler = load;
                document.addEventListener('icon-picker-ready', load, { once: true });
            }
        },

        modals: {
            iconPickerModal: '<section id="icon-picker-modal"><div class="modal-content"><span class="spinner big main-spinner"></span></div></section>',
        },

        open: function() {
            var options = {
                title: 'Icon Picker',
                name: 'iconPickerModal',
                width: '650px',
                height: '80px',
                handle: 'insert',
                commands: {
                    insert: { title: 'Insert' },
                    cancel: { title: 'Cancel' },
                }
            };

            this.app.api('module.modal.build', options);
        },

        onmodal: {
            iconPickerModal: {
                opened: function($modal, $form) {
                    var $container = $modal.$modalBody.find('.modal-content');
                    var $spinner = $modal.$modalBody.find('.main-spinner');

                    Craft.sendActionRequest('POST', 'icon-picker/redactor')
                        .then(function(response) {
                            $spinner.addClass('hidden');
                            $container.html(response.data.inputHtml);
                            Garnish.$bod.append(response.data.footHtml);
                        })
                        .catch(function(error) {
                            $spinner.addClass('hidden');
                            $container.text(error.response?.data?.message || Craft.t('icon-picker', 'Request failed.'));
                            console.error(error);
                        });
                },

                insert: function($modal, $form) {
                    // Solo SVGs will be in a div, while others won't
                    var $icon = $modal.$modalBody.find('.ipui-icon-input-item .ipui-icon-input-svg');

                    if ($icon.find('div').length) {
                        $icon = $icon.find('div');
                    }

                    var iconHtml = $icon.html();

                    if (!iconHtml) {
                        return;
                    }

                    // Replace any xmlns attributes which don't place nice in Redactor
                    iconHtml = iconHtml.replace(/xmlns=\"(.*?)\"/g, '');

                    this.app.api('module.modal.close');
                    this.app.selection.restore();

                    var node = $('<span class="icon-picker-redactor-icon" />').html(iconHtml, false);
                    this.app.insertion.insertNode(node);
                    // Serialize public symbol IDs before an immediately following form save.
                    this.app.broadcast('hardsync');
                    this.loadSpritePreviews();
                },
            },
        },
    });
})(Redactor);
