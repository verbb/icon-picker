// ==========================================================================

// Icon Picker Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

if (typeof Craft.IconPicker === typeof undefined) {
    Craft.IconPicker = {};
}

(function($) {

Craft.IconPicker.Input = Garnish.Base.extend({
    $container: null,
    $selectize: null,
    $spinner: null,
    $errorText: null,

    iconData: null,
    preppedIconData: {},

    init: function(options) {
        this.options = options;

        var self = this;

        this.loadSpriteSheets();
        this.loadFonts();

        this.$container = $('#' + options.inputId);
        this.$selectize = this.$container.find('.icon-picker-select');
        this.$spinner = this.$container.find('.spinner');
        this.$errorText = this.$container.find('.error-text');

        // Fix up some CSS for parent fields that might have overflow clipping setup
        this.fixClipping();

        this.$selectize.selectize({
            maxItems: 1,
            maxOptions: options.settings.maxIconsShown,
            valueField: 'value',
            labelField: 'label',
            searchField: ['label', 'description'],
            options: [],
            optgroups: [],
            optgroupField: 'parent_id',
            create: false,
            preload: 'focus',
            render: {
                item: function(item, escape) {
                    if (item.type == 'svg') {
                        var content = '<img src="' + item.url + '" alt="' + escape(item.label) + '" />';
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
                        '<span>' + escape(item.label) + '</span>' + 
                    '</div>';
                },

                option: function(item, escape) {
                    if (item.type == 'svg') {
                        var content = '<img src="' + item.url + '" alt="' + escape(item.label) + '" title="' + escape(item.label) + '" />';
                    } else if (item.type == 'sprite') {
                        var content = '<svg viewBox="0 0 1000 1000"><use xlink:href="#' + item.url + '" /></svg>';
                    } else if (item.type == 'glyph') {
                        var content = '<span class="icon-picker-font font-face-' + item.name + '">' + item.url + '</span>';
                    } else if (item.type == 'css') {
                        var content = '<span class="icon-picker-font ' + item.classes + '">' + item.url + '</span>';
                    }

                    var labels = self.options.settings.showLabels ? escape(item.label) : '';

                    return '<div class="icon-picker-item">' +
                        '<div class="icon-picker-item-wrap">' +
                            '<div class="icon-picker-item-icon">' +
                                content + 
                            '</div>' +
                            '<span class="icon-picker-item-label">' + labels + '</span>' +
                        '</div>' +
                    '</div>';
                },
            },

            load: function(query, callback) {
                self.$spinner.removeClass('hidden');
                self.$errorText.html('');

                if (!$.isEmptyObject(self.preppedIconData)) {
                    self.$spinner.addClass('hidden');

                    return callback(self.preppedIconData);
                }

                var selectize = self.$selectize[0].selectize;

                var items = [];
                var i = 0;

                $.ajax({
                    url: Craft.getActionUrl('icon-picker/icons/icons-for-field', { fieldId: options.fieldId }),
                    type: 'GET',
                    error: function(response) {
                        self.$spinner.addClass('hidden');
                        self.$errorText.html(response.statusText);

                        callback();
                    },
                    success: function(iconData) {
                        $.each(iconData, function(groupLabel, icons) {
                            var optgroup = {
                                id: i,
                                label: groupLabel,
                            }

                            selectize.addOptionGroup(optgroup.id, optgroup);

                            $.each(icons, function(j, icon) {
                                icon.parent_id = optgroup.id;

                                items.push(icon);
                            });

                            i++;
                        });

                        // Add some local caching to prevent the above iteration on each search
                        self.preppedIconData = items;
                        
                        self.$spinner.addClass('hidden');

                        callback(self.preppedIconData);
                    },
                });
            },
            
        });
    },

    fixClipping: function() {
        var $neoField = this.$container.parents('.ni_block');

        if ($neoField.length) {
            $neoField.css({ overflow: 'visible' });
            $neoField.find('.ni_block_body').css({ overflow: 'visible' });
        }
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
