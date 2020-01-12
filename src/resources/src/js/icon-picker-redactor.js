(function ($R) {
    $R.add('plugin', 'icon-picker', {
        init: function(app) {
            this.app = app;
            this.lang = app.lang;
            this.inline = app.inline;
            this.toolbar = app.toolbar;
            this.insertion = app.insertion;
        },

        start: function() {
            this.button = this.toolbar.addButton('icon-picker', {
                title: Craft.t('icon-picker', 'Icon Picker'),
                api: 'plugin.icon-picker.open',
                icon: '<i class="verbb icon icon-picker"></i>',
            });
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

                    $.ajax({
                        url: Craft.getActionUrl('icon-picker/redactor'),
                        type: 'GET',
                        error: function(response) {
                            $spinner.addClass('hidden');
                            $errorText.html(response.statusText);
                        },

                        success: function(fieldData) {
                            $spinner.addClass('hidden');

                            $container.html(fieldData.inputHtml);

                            Garnish.$bod.append(fieldData.footHtml);
                        },
                    });
                },

                insert: function($modal, $form) {
                    var iconHtml = $modal.$modalBody.find('.icon-picker-thumb-icon').html();

                    this.app.api('module.modal.close');
                    this.app.selection.restore();

                    var node = $('<span />').html(iconHtml, false);
                    this.app.insertion.insertNode(node);
                },
            },
        },
    });
})(Redactor);