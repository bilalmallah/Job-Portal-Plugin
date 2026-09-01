/* =========================================================
   CAREERHUB - ADMIN
   Colour pickers, presets and the live preview on the
   settings screen.
   ========================================================= */

(function ($) {
    'use strict';

    $(function () {

        var $fields = $('.cwcp-color-field');

        if (!$fields.length) {
            return;
        }

        function value(key) {
            return $('#cwcp-color-' + key).val() || '';
        }

        function shade(hex, amount) {

            hex = (hex || '').replace('#', '');

            if (hex.length === 3) {
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }

            if (hex.length !== 6) {
                return '#000000';
            }

            var target = amount >= 0 ? 255 : 0;
            var weight = Math.abs(amount);
            var out = '#';

            for (var i = 0; i < 3; i++) {

                var channel = parseInt(hex.substr(i * 2, 2), 16);
                var mixed = Math.round(channel + ((target - channel) * weight));

                out += ('0' + Math.max(0, Math.min(255, mixed)).toString(16)).slice(-2);
            }

            return out;
        }

        function readable(hex) {

            hex = (hex || '').replace('#', '');

            if (hex.length === 3) {
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }

            if (hex.length !== 6) {
                return '#ffffff';
            }

            var r = parseInt(hex.substr(0, 2), 16);
            var g = parseInt(hex.substr(2, 2), 16);
            var b = parseInt(hex.substr(4, 2), 16);

            return ((0.299 * r + 0.587 * g + 0.114 * b) / 255) > 0.6 ? '#172033' : '#ffffff';
        }

        function refreshPreview() {

            var primary = value('primary');

            $('.cwcp-color-preview').css({
                background: value('background')
            });

            $('.cwcp-preview-card').css({
                background: value('surface'),
                'border-color': value('border')
            });

            $('.cwcp-preview-title').css('color', value('text'));
            $('.cwcp-preview-muted').css('color', value('muted'));

            $('.cwcp-preview-btn').css({
                background: primary,
                color: readable(primary)
            });

            $('.cwcp-preview-success').css({
                background: shade(value('success'), 0.9),
                color: value('success')
            });

            $('.cwcp-preview-warning').css({
                background: shade(value('warning'), 0.88),
                color: value('warning')
            });

            $('.cwcp-preview-danger').css({
                background: shade(value('danger'), 0.92),
                color: value('danger')
            });
        }

        /* ---- pickers ---- */

        if ($.fn.wpColorPicker) {

            $fields.wpColorPicker({
                change: function () {
                    window.setTimeout(refreshPreview, 50);
                },
                clear: function () {
                    window.setTimeout(refreshPreview, 50);
                }
            });

        } else {

            /* Fallback when the colour picker script is unavailable. */
            $fields.attr('type', 'color');
        }

        $fields.on('input change', refreshPreview);

        /* ---- presets ---- */

        function setColor(key, hex) {

            var $field = $('#cwcp-color-' + key);

            if (!$field.length) {
                return;
            }

            if ($.fn.wpColorPicker && $field.hasClass('wp-color-picker')) {
                $field.wpColorPicker('color', hex);
            } else {
                $field.val(hex);
            }
        }

        $('.cwcp-preset').on('click', function () {

            var colors = $(this).data('colors') || {};

            Object.keys(colors).forEach(function (key) {
                setColor(key, colors[key]);
            });

            refreshPreview();
        });

        $('.cwcp-theme-color').on('click', function () {

            setColor('primary', $(this).data('color'));

            refreshPreview();
        });

        refreshPreview();
    });

}(jQuery));
