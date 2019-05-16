(function ($, Drupal, drupalSettings) {
    'use strict';

    Drupal.behaviors.drup_iconpicker = {
        attach: function (context) {
            // Plugin Id
            this.plugin = 'drup_iconpicker';

            // Elements
            this.elements = {
                icon: $('#' + this.getSetting('input'))
            };

            // Variables
            this.picker = null;
            this.options = {
                hasSearch: false
            };

            // Load
            this.fontIconPicker();
        },
        detach: function (context) {},

        /**
         * Init "font icon picker" plugin
         */
        fontIconPicker: function () {
            var self = this;

            if (self.elements.icon.length) {
                self.getIcons();

                $(document).ajaxComplete(function (event, xhr, settings) {
                    if (settings.url === self.getFontPath()) {
                        self.picker = self.elements.icon.fontIconPicker(self.options);
                    }
                });
            }

            return self.picker;
        },

        /**
         * Récupère la liste des icones extrait à partir de la font icon au format SVG
         *
         * @return array
         */
        getIcons: function () {
            var self = this;
            var icons = [];

            // Icons scan global
            $.get(self.getFontPath(), function (data) {
                var data = $(data);

                if (data !== undefined && data.length) {
                    data.find('glyph').each(function (i, item) {
                        item = $(item);
                        var glyphName = item.attr('glyph-name');

                        if (glyphName !== undefined) {
                            icons.push(self.getSetting('prefix') + self.getType() + ' ' + self.getSetting('prefix') + '--' + glyphName);
                        }
                    });
                }
            }, 'xml');

            // Update options
            $.extend(self.options, {
                source: icons
            });

            return icons;
        },

        /**
         * Récupère le chemin vers la font icon
         *
         * @return string
         */
        getFontPath: function () {
            return '/' + this.getSetting('font_path');
        },

        /**
         * Récupère le type d'icone : permet de spécifier une classe supplémentaire permettant de choisir une autre font icon

         * @return {prefix}-type--{type} | null
         */
        getType: function () {
            var type = this.getSetting('type');

            if (type !== null) {
                return ' ' + this.getSetting('prefix') + '-type--' + type;
            }

            return null;
        },

        /**
         * Récupère une configuration du widget
         *
         * @param string setting Nom du paramètre
         * @return string|null
         */
        getSetting(setting) {
            if (drupalSettings[this.plugin] !== undefined && drupalSettings[this.plugin][setting] !== undefined) {
                return drupalSettings[this.plugin][setting];
            }

            return null;
        }
    };
}(jQuery, Drupal, drupalSettings));