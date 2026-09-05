(function () {
    'use strict';

    /* ── Instalación de la app (PWA) — solo móvil, PC no la muestra ── */
    var deferredInstall = null;
    var isStandalone = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;
    var isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredInstall = e;
    });

    function installReady(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    function openInstallModal() {
        var modal = document.getElementById('zfhInstallModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'zfhInstallModal';
            modal.className = 'zfh-imodal';

            var steps;
            if (isIOS) {
                steps = '1. Toca el botón <b>Compartir</b> (el cuadrito con la flecha).<br>2. Elige <b>Añadir a pantalla de inicio</b>.<br>3. Toca <b>Añadir</b>.';
            } else {
                steps = '1. Toca el menú <b>⋮</b> arriba a la derecha.<br>2. Elige <b>Instalar aplicación</b> o <b>Añadir a pantalla de inicio</b>.<br>3. Confirma con <b>Instalar</b>.';
            }

            modal.innerHTML = '<div class="zfh-imodal-box">'
                + '<div class="zfh-imodal-head"><b>Instalar la app</b><button type="button" class="zfh-imodal-close" aria-label="Cerrar"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>'
                + '<p>' + steps + '</p>'
                + '<button type="button" class="zfh-imodal-ok">Entendido</button>'
                + '</div>';
            document.body.appendChild(modal);

            modal.querySelector('.zfh-imodal-close').addEventListener('click', function () { modal.hidden = true; });
            modal.querySelector('.zfh-imodal-ok').addEventListener('click', function () { modal.hidden = true; });
            modal.addEventListener('click', function (e) { if (e.target === modal) { modal.hidden = true; } });
        }
        modal.hidden = false;
    }

    installReady(function () {
        var bar = document.getElementById('zfhInstall');
        var btn = document.getElementById('zfhInstallBtn');
        var close = document.getElementById('zfhInstallClose');

        if (!bar) return;

        // Solo móvil y solo en el navegador (no dentro de la app instalada)
        if (isMobile && !isStandalone) { bar.hidden = false; }

        if (btn) {
            btn.addEventListener('click', function () {
                if (deferredInstall) {
                    deferredInstall.prompt();
                    deferredInstall.userChoice.finally(function () { deferredInstall = null; });
                    return;
                }
                openInstallModal();
            });
        }

        if (close) {
            close.addEventListener('click', function () {
                try { sessionStorage.setItem('zfl_install_dismissed', '1'); } catch (err) {}
                bar.hidden = true;
            });
        }
    });
})();

(function () {
    'use strict';

    /* ── Selector de moneda ── */
    var curBtns = document.querySelectorAll('.zfh-cur-btn');
    var selectedCur = localStorage.getItem('zfl_currency') || 'USD';

    function applyCurrency(code) {
        var rates = window.zfhRates;
        if (!rates || !rates[code]) return;
        var rate = parseFloat(rates[code].rate);
        var symbol = rates[code].symbol || '$';
        if (rate <= 0) return;

        document.querySelectorAll('.zfh-price').forEach(function (el) {
            var cup = parseFloat(el.getAttribute('data-price-cup'));
            if (isNaN(cup)) return;
            if (code === 'CUP') {
                // Restaurar el HTML original de WooCommerce
                var orig = el.getAttribute('data-orig-html');
                if (orig) { el.innerHTML = orig; }
                return;
            }
            if (!el.getAttribute('data-orig-html')) {
                el.setAttribute('data-orig-html', el.innerHTML);
            }
            var converted = (cup / rate).toFixed(2);
            el.innerHTML = symbol + ' ' + converted;
        });

        curBtns.forEach(function (b) { b.classList.remove('active'); });
        var active = document.querySelector('.zfh-cur-btn[data-cur="' + code + '"]');
        if (active) { active.classList.add('active'); }
        localStorage.setItem('zfl_currency', code);
    }

    curBtns.forEach(function (b) {
        b.addEventListener('click', function () {
            var code = b.getAttribute('data-cur');
            localStorage.setItem('zfl_currency', code);
            applyCurrency(code);
        });
    });

    // Aplicar la moneda guardada al cargar
    if (selectedCur && selectedCur !== 'CUP') {
        setTimeout(function () { applyCurrency(selectedCur); }, 100);
    }

    document.addEventListener('DOMContentLoaded', function () {

        document.querySelectorAll('.zfh-carousel').forEach(function (root) {
            var track = root.querySelector('.zfh-track');
            if (!track) return;

            var slides = track.children.length;
            if (slides < 2) {
                var prev = root.querySelector('.zfh-prev');
                var next = root.querySelector('.zfh-next');
                var dots = root.querySelector('.zfh-dots');
                if (prev) prev.style.display = 'none';
                if (next) next.style.display = 'none';
                if (dots) dots.style.display = 'none';
                return;
            }

            var index = 0;
            var timer = null;
            var dotsWrap = root.querySelector('.zfh-dots');

            for (var d = 0; d < slides; d++) {
                (function (idx) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.setAttribute('aria-label', 'Ir a la promo ' + (idx + 1));
                    b.addEventListener('click', function () {
                        go(idx);
                        restart();
                    });
                    dotsWrap.appendChild(b);
                })(d);
            }

            function go(idx) {
                index = (idx + slides) % slides;
                track.style.transform = 'translateX(-' + (index * 100) + '%)';
                dotsWrap.querySelectorAll('button').forEach(function (btn, i) {
                    btn.classList.toggle('active', i === index);
                });
            }

            function restart() {
                if (timer) clearInterval(timer);
                timer = setInterval(function () { go(index + 1); }, 5000);
            }

            root.querySelector('.zfh-prev').addEventListener('click', function () {
                go(index - 1);
                restart();
            });

            root.querySelector('.zfh-next').addEventListener('click', function () {
                go(index + 1);
                restart();
            });

            root.addEventListener('mouseenter', function () {
                if (timer) clearInterval(timer);
            });

            root.addEventListener('mouseleave', restart);
            root.addEventListener('touchstart', function () {
                if (timer) clearInterval(timer);
            }, { passive: true });

            /* Swipe táctil: deslizar para cambiar de promo */
            var startX = 0, deltaX = 0;
            track.addEventListener('touchstart', function (e) {
                startX = e.touches[0].clientX;
                deltaX = 0;
            }, { passive: true });
            track.addEventListener('touchmove', function (e) {
                deltaX = e.touches[0].clientX - startX;
            }, { passive: true });
            track.addEventListener('touchend', function () {
                if (Math.abs(deltaX) > 40) {
                    go(deltaX < 0 ? index + 1 : index - 1);
                    restart();
                }
                deltaX = 0;
            });

            go(0);
            restart();
        });

    });

})();
