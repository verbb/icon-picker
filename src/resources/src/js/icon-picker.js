// ==========================================================================

// Icon Picker Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

if (typeof Craft.IconPicker === typeof undefined) {
    Craft.IconPicker = {};
}

(function($) {

Craft.IconPicker.Input = Garnish.Base.extend({
    container: null,
    $selectize: null,

    init: function(options) {
        this.options = options;

        this.container = $('#' + options.inputId + '-field');
        this.$selectize = this.container.find('.icon-picker-select');

        this.$selectize.selectize({
            maxItems: 1,
            maxOptions: 100,
            create: false,
            // dropdownParent: 'body',
            render: {
                item: function(item, escape) {
                    var html = '<div class="icon-picker-thumb">' +
                        '<div class="icon-picker-thumb-icon">' +
                            '<img src="' + item.url + '" alt="' + escape(item.text) + '" />' +
                        '</div>' +
                        '<span>' + escape(item.text) + '</span>' + 
                    '</div>';

                    return html;
                },
                option: function(item, escape) {
                    var html = '<div class="icon-picker-item">' +
                        '<div class="icon-picker-item-wrap">' +
                            '<div class="icon-picker-item-icon">' +
                                '<img src="' + item.url + '" alt="' + escape(item.text) + '" title="' + escape(item.text) + '" />' +
                            '</div>' +
                        '</div>' +
                    '</div>';

                    return html;
                }
            }
        });
    },
});


})(jQuery);
