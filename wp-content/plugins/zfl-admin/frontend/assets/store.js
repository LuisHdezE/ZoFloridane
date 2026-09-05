(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ── Pop-up obligatorio de localidad ── */
        var hasLocality = document.cookie.indexOf('zfl_localidad=') !== -1;
        var chip = document.getElementById('zslLocChip');
        var modal = document.getElementById('zslLocModal');

        function openLocalityModal(mandatory) {
            if (!modal) return;
            modal.hidden = false;
            if (chip) chip.setAttribute('aria-expanded', 'true');
            document.body.classList.add('zsl-no-scroll');
            var closeBtn = modal.querySelector('.zsl-loc-close');
            var overlay = modal.querySelector('.zsl-loc-overlay');
            if (mandatory) {
                modal.classList.add('zsl-loc-mandatory');
                if (closeBtn) closeBtn.hidden = true;
                if (overlay) overlay.style.pointerEvents = 'none';
            } else {
                modal.classList.remove('zsl-loc-mandatory');
                if (closeBtn) closeBtn.hidden = false;
                if (overlay) overlay.style.pointerEvents = '';
            }
            setTimeout(function () { modal.classList.add('zsl-open'); }, 10);
        }

        function closeLocalityModal() {
            if (!modal) return;
            modal.classList.remove('zsl-open');
            if (chip) chip.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('zsl-no-scroll');
            setTimeout(function () { modal.hidden = true; }, 200);
        }

        if (!hasLocality) {
            openLocalityModal(true);
        }

        if (chip && modal) {
            chip.addEventListener('click', function () { openLocalityModal(false); });
            modal.querySelectorAll('[data-zsl-close]').forEach(function (el) {
                el.addEventListener('click', closeLocalityModal);
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.hidden && hasLocality) closeLocalityModal();
            });
            modal.querySelectorAll('.zsl-loc-option').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var value = btn.getAttribute('data-loc') || '0';
                    document.cookie = 'zfl_localidad=' + encodeURIComponent(value) + ';path=/;max-age=31536000';
                    window.location.reload();
                });
            });
        }

        /* ── Selector de moneda (header) ── */
        var curSelect = document.getElementById('zslCurrencySelect');
        if (curSelect) {
            var savedCur = localStorage.getItem('zfl_currency') || 'USD';
            for (var ci = 0; ci < curSelect.options.length; ci++) {
                if (curSelect.options[ci].value === savedCur) {
                    curSelect.selectedIndex = ci;
                    break;
                }
            }
            curSelect.addEventListener('change', function () {
                localStorage.setItem('zfl_currency', curSelect.value);
                window.location.reload();
            });
        }

        /* ── Bloquear añadir al carrito sin localidad ── */
        document.addEventListener('click', function (e) {
            var addBtn = e.target.closest('.zfh-add');
            if (!addBtn) return;
            if (!hasLocality) {
                e.preventDefault();
                e.stopImmediatePropagation();
                openLocalityModal(true);
            }
        }, true);

        /* ── Contador del carrito siempre fresco ── */
        var count = document.getElementById('zslCartCount');
        if (count) {
            fetch('/wp-json/wc/store/v1/cart', { credentials: 'same-origin', cache: 'no-store' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (cart) {
                    if (!cart || !cart.items) return;
                    var total = cart.items.reduce(function (sum, item) { return sum + (Number(item.quantity) || 0); }, 0);
                    count.textContent = String(total);
                })
                .catch(function () {});
        }

        /* ── Header: ocultar al bajar, mostrar al subir ── */
        var siteHeader = document.querySelector('.zsl-header');
        if (siteHeader) {
            var lastY = window.scrollY || 0;
            window.addEventListener('scroll', function () {
                var y = window.scrollY || 0;
                if (y > lastY && y > 80) {
                    siteHeader.classList.add('zsl-header-hidden');
                } else {
                    siteHeader.classList.remove('zsl-header-hidden');
                }
                lastY = y;
            }, { passive: true });
        }

        /* ── Selector de tema base: negro / verde ── */
        var themeSwitch = document.getElementById('zslThemeSwitch');
        var THEME_KEY = 'zfl_base_theme';

        function normalizeBaseTheme(theme) {
            return theme === 'green' ? 'green' : 'black';
        }

        function applyBaseTheme(theme, persist) {
            theme = normalizeBaseTheme(theme);
            var root = document.documentElement;

            // Ambos diseños usan superficies oscuras; mantenemos los estilos
            // de compatibilidad dark-mode y cambiamos únicamente la base cromática.
            root.classList.add('dark-mode');
            root.classList.remove('zfl-theme-black', 'zfl-theme-green');
            root.classList.add('zfl-theme-' + theme);
            root.setAttribute('data-zfl-theme', theme);

            if (persist !== false) {
                localStorage.setItem(THEME_KEY, theme);
            }

            if (themeSwitch) {
                themeSwitch.querySelectorAll('[data-zsl-theme]').forEach(function (button) {
                    var selected = button.getAttribute('data-zsl-theme') === theme;
                    button.classList.toggle('is-active', selected);
                    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });
            }

            // Integra el color del navegador/PWA con el tema elegido.
            var themeMeta = document.querySelector('meta[name="theme-color"]');
            if (themeMeta) {
                themeMeta.setAttribute('content', theme === 'green' ? '#073d2a' : '#070a09');
            }
        }

        var savedBaseTheme = normalizeBaseTheme(localStorage.getItem(THEME_KEY));
        applyBaseTheme(savedBaseTheme, false);

        if (themeSwitch) {
            themeSwitch.addEventListener('click', function (e) {
                var button = e.target.closest('[data-zsl-theme]');
                if (!button) return;
                applyBaseTheme(button.getAttribute('data-zsl-theme'), true);
            });
        }

        /* ── AJAX Add to Cart (funciona en todas las páginas) ── */
        var toastTimer = null;

        function showErrorToast(message) {
            var bar = document.getElementById('zfhToast');
            if (!bar) {
                bar = document.createElement('div');
                bar.id = 'zfhToast';
                bar.className = 'zfh-toast';
                bar.innerHTML = '<span></span>';
                document.body.appendChild(bar);
            }
            bar.querySelector('span').textContent = message;
            bar.classList.add('zfh-show');
            if (toastTimer) clearTimeout(toastTimer);
            toastTimer = setTimeout(function () { bar.classList.remove('zfh-show'); }, 4500);
        }

        function bumpCart() {
            var count = document.getElementById('zslCartCount');
            if (!count) return;
            count.textContent = String((parseInt(count.textContent, 10) || 0) + 1);
            count.classList.remove('bump');
            void count.offsetWidth;
            count.classList.add('bump');
        }

        function flyToCart(imgEl) {
            if (!imgEl) return;
            var cart = document.querySelector('.zsl-cart');
            if (!cart) return;

            var from = imgEl.getBoundingClientRect();
            var to = cart.getBoundingClientRect();

            var clone = imgEl.cloneNode();
            var size = Math.max(72, Math.min(110, from.width * 0.6));
            clone.style.position = 'fixed';
            clone.style.left = (from.left + from.width / 2 - size / 2) + 'px';
            clone.style.top = (from.top + from.height / 2 - size / 2) + 'px';
            clone.style.width = size + 'px';
            clone.style.height = size + 'px';
            clone.style.objectFit = 'contain';
            clone.style.borderRadius = '12px';
            clone.style.border = '2px solid #20c77a';
            clone.style.boxShadow = '0 10px 30px rgba(0,0,0,.35)';
            clone.style.background = '#fff';
            clone.style.padding = '6px';
            clone.style.zIndex = 99991;
            clone.style.pointerEvents = 'none';
            clone.style.transition = 'transform .8s cubic-bezier(.3,.6,.4,1), opacity .8s ease';
            document.body.appendChild(clone);

            requestAnimationFrame(function () {
                var dx = (to.left + to.width / 2) - (from.left + from.width / 2);
                var dy = (to.top + to.height / 2) - (from.top + from.height / 2);
                clone.style.transform = 'translate(' + dx + 'px,' + dy + 'px) scale(.1)';
                clone.style.opacity = '.15';
            });

            setTimeout(function () { clone.remove(); }, 850);
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.zfh-add');
            if (!btn) return;
            e.preventDefault();

            if (btn.classList.contains('is-added')) return;

            var card = btn.closest('.zfh-card, .zfl-sp-gallery');
            var img = card ? card.querySelector('img') : null;

            flyToCart(img);
            btn.classList.add('is-added');
            btn.textContent = 'Añadiendo…';

            fetch(window.location.origin + '/?wc-ajax=add_to_cart', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + encodeURIComponent(btn.getAttribute('data-product-id')) + '&quantity=1'
            }).then(function (r) { return r.json(); }).then(function (res) {
                if (res && res.error) {
                    var msg = 'No se pudo añadir el producto (sin stock disponible).';
                    if (res.error_message) {
                        msg = res.error_message;
                    } else if (res.notices) {
                        var tmp = document.createElement('div');
                        tmp.innerHTML = res.notices;
                        var text = tmp.textContent.trim().split('\n')[0];
                        if (text) msg = text;
                    }
                    showErrorToast(msg);
                    btn.textContent = 'Añadir al carrito';
                    btn.classList.remove('is-added');
                    return;
                }

                bumpCart();
                btn.textContent = 'Añadido ✓';
                setTimeout(function () {
                    btn.classList.remove('is-added');
                    btn.textContent = 'Añadir al carrito';
                }, 1800);
            }).catch(function () {
                btn.textContent = 'Añadir al carrito';
                btn.classList.remove('is-added');
                showErrorToast('No se pudo añadir el producto. Inténtalo nuevamente.');
            });
        }, true);

    });
})();
