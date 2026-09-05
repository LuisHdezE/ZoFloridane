(function () {
    'use strict';

    var THEME_KEY = 'zfl_base_theme';

    /*
     * Base Verde es la experiencia por defecto. Si el visitante ya eligió
     * explícitamente Base Negra o Base Verde, respetamos su preferencia.
     */
    try {
        if (!localStorage.getItem(THEME_KEY)) {
            localStorage.setItem(THEME_KEY, 'green');
        }
    } catch (e) {}

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.documentElement;
        var saved = 'green';

        try {
            saved = localStorage.getItem(THEME_KEY) === 'black' ? 'black' : 'green';
        } catch (e) {}

        root.classList.remove('zfl-theme-black', 'zfl-theme-green');
        root.classList.add('zfl-theme-' + saved);
        root.setAttribute('data-zfl-theme', saved);

        if (saved === 'green') {
            root.classList.remove('dark-mode');
        } else {
            root.classList.add('dark-mode');
        }

        var switcher = document.getElementById('zslThemeSwitch');
        if (switcher) {
            switcher.querySelectorAll('[data-zsl-theme]').forEach(function (button) {
                var selected = button.getAttribute('data-zsl-theme') === saved;
                button.classList.toggle('is-active', selected);
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
        }

        var themeMeta = document.querySelector('meta[name="theme-color"]');
        if (themeMeta) {
            themeMeta.setAttribute('content', saved === 'green' ? '#086b55' : '#070a09');
        }
    });
})();
