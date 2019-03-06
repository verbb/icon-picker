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

        var self = this;

        this.loadSpriteSheets();
        this.loadFonts();

        this.container = $('#' + options.inputId + '-field');
        this.$selectize = this.container.find('.icon-picker-select');

        this.$selectize.selectize({
            maxItems: 1,
            maxOptions: 100,
            create: false,
            dropdownParent: 'body',
            render: {
                item: function(item, escape) {
                    if (item.type == 'svg') {
                        var content = '<img src="' + item.url + '" alt="' + escape(item.text) + '" />';
                    } else if (item.type == 'sprite') {
                        var content = '<svg viewBox="0 0 1000 1000"><use xlink:href="#' + item.url + '" /></svg>';
                    } else if (item.type == 'glyph') {
                        var content = '<span class="icon-picker-font font-face-' + item.name + '">' + item.url + '</span>';
                    } else if (item.type == 'css') {
                        var content = '<span class="icon-picker-font ' + item.classes + '">' + item.url + '</span>';
                    }

                    return '<div class="icon-picker-thumb">' +
                        '<div class="icon-picker-thumb-icon">' +
                            content + 
                        '</div>' +
                        '<span>' + escape(item.text) + '</span>' + 
                    '</div>';
                },
                option: function(item, escape) {
                    if (item.type == 'svg') {
                        var content = '<img src="' + item.url + '" alt="' + escape(item.text) + '" title="' + escape(item.text) + '" />';
                    } else if (item.type == 'sprite') {
                        var content = '<svg viewBox="0 0 1000 1000"><use xlink:href="#' + item.url + '" /></svg>';
                    } else if (item.type == 'glyph') {
                        var content = '<span class="icon-picker-font font-face-' + item.name + '">' + item.url + '</span>';
                    } else if (item.type == 'css') {
                        var content = '<span class="icon-picker-font ' + item.classes + '">' + item.url + '</span>';
                    }

                    var labels = self.options.settings.showLabels ? escape(item.text) : '';

                    return '<div class="icon-picker-item">' +
                        '<div class="icon-picker-item-wrap">' +
                            '<div class="icon-picker-item-icon">' +
                                content + 
                            '</div>' +
                            '<span class="icon-picker-item-label">' + labels + '</span>' +
                        '</div>' +
                    '</div>';
                }
            }
        });
    },
    loadFonts: function() {
        for (var i = 0; i < this.options.fonts.length; i++) {
            var font = this.options.fonts[i];

            if ($.inArray(font.name, Craft.IconPicker.Cache.fonts) == -1) {
                Craft.IconPicker.Cache.fonts.push(font.name);

                if (font.type == 'local') {
                    var css = '@font-face {' + 
                        'font-family: "' + font.name + '";' + 
                        'src: url("' + font.url + '");' + 
                        'font-weight: normal;' + 
                        'font-style: normal;' + 
                    '}' + 

                    '.' + font.name + ' {' + 
                        'font-family: "' + font.name + '" !important;' + 
                    '}';

                    $('head').append('<style type="text/css">' + css + '</style>');
                } else if (font.type == 'remote') {
                    $('head').append('<link rel="stylesheet" type="text/css" href="' + font.url + '">');
                }
            }
        }
    },

    loadSpriteSheets: function() {
        for (var i = 0; i < this.options.spriteSheets.length; i++) {
            var sheet = this.options.spriteSheets[i];

            if ($.inArray(sheet.name, Craft.IconPicker.Cache.stylesheets) == -1) {
                Craft.IconPicker.Cache.stylesheets.push(sheet.name);

                $.get(sheet.url, function(data) {
                    var div = document.createElement('div');
                    div.innerHTML = new XMLSerializer().serializeToString(data.documentElement);
                    $svg = $(div).find('> svg');
                    $svg.attr('id', 'icon-picker-spritesheet-' + sheet.name);
                    $svg.css('display', 'none').prependTo('body');
                });
            }
        }
    },
});


})(jQuery);
