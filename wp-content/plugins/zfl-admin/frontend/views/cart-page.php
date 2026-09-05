<?php
/**
 * Página de carrito — Floridame.
 * Se carga en /carrito/ interceptando WooCommerce vía template_include.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$rates = class_exists( 'ZFL_Currencies' ) ? ZFL_Currencies::get_rates() : array();
$cart_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'cart' ) : home_url( '/' );
$shop_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$check_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'checkout' ) : home_url( '/checkout/' );
?>

<style>
.zfl-cart{max-width:600px;margin:0 auto;padding:20px 16px 60px}
.zfl-cart h1{font-size:22px;font-weight:700;color:#374151;margin-bottom:4px}
.zfl-cart-sub{font-size:13px;color:#9ca3af;margin-bottom:16px}
.zfl-cart-empty{text-align:center;padding:40px 20px;color:#9ca3af;font-size:15px}
.zfl-cart-empty a{color:#ffcc00;text-decoration:none;font-weight:600}
.zfl-cart-items{display:flex;flex-direction:column;gap:8px}
.zfl-cart-item{display:flex;align-items:center;gap:10px;background:#fafafa;border:1px solid #f3f4f6;border-radius:10px;padding:10px 12px}
.zfl-cart-item-img{width:50px;height:50px;border-radius:6px;object-fit:cover;flex-shrink:0;background:#f3f4f6}
.zfl-cart-item-info{flex:1;min-width:0}
.zfl-cart-item-name{font-weight:600;color:#374151;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.zfl-cart-item-price{font-size:12px;color:#6b7280;margin-top:1px}
.zfl-cart-item-qty{display:flex;align-items:center;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;flex-shrink:0}
.zfl-cart-item-qty button{width:30px;height:30px;border:none;background:#f9fafb;font-size:16px;color:#374151;cursor:pointer;display:flex;align-items:center;justify-content:center}
.zfl-cart-item-qty button:hover{background:#e5e7eb}
.zfl-cart-item-qty button:disabled{color:#d1d5db;cursor:not-allowed}
.zfl-cart-item-qty span{width:32px;text-align:center;font-weight:600;font-size:13px;color:#374151;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;line-height:30px}
.zfl-cart-item-sub{font-weight:700;color:#374151;font-size:13px;white-space:nowrap;min-width:60px;text-align:right}
.zfl-cart-item-remove{background:none;border:none;color:#d1d5db;cursor:pointer;font-size:16px;padding:2px;transition:color .15s}
.zfl-cart-item-remove:hover{color:#ef4444}
.zfl-cart-summary{border-top:2px solid #f3f4f6;padding-top:14px;margin-top:10px}
.zfl-cart-total{display:flex;justify-content:space-between;font-size:18px;font-weight:700;color:#374151;padding-top:10px}
.zfl-cart-actions{display:flex;gap:10px;margin-top:16px;flex-wrap:wrap}
.zfl-cart-checkout{background:#ffcc00;color:#374151;border:none;padding:14px 28px;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;flex:1;min-width:160px}
.zfl-cart-checkout:hover{background:#e6b800}
.zfl-cart-share{background:#25d366;color:#fff;border:none;padding:14px 20px;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.zfl-cart-share:hover{background:#1da851}
.zfl-cart-continue{background:none;border:2px solid #e5e7eb;color:#6b7280;padding:14px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}
.zfl-cart-continue:hover{border-color:#d1d5db;color:#374151}
.zfl-cart-emptyall{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;padding:10px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer}
.zfl-cart-emptyall:hover{background:#fee2e2}
.zfl-cart-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#374151;color:#fff;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:600;opacity:0;transition:opacity .3s;z-index:9999;pointer-events:none}
.zfl-cart-toast.show{opacity:1}

html.dark-mode body .zfl-cart h1{color:#f3f4f6!important}
html.dark-mode body .zfl-cart-sub{color:#9ca3af!important}
html.dark-mode body .zfl-cart-empty{color:#9ca3af!important}
html.dark-mode body .zfl-cart-empty a{color:#ffcc00!important}
html.dark-mode body .zfl-cart-item{background:#1a1d23!important;border-color:#374151!important}
html.dark-mode body .zfl-cart-item-name{color:#f3f4f6!important}
html.dark-mode body .zfl-cart-item-price{color:#9ca3af!important}
html.dark-mode body .zfl-cart-item-qty{border-color:#374151!important}
html.dark-mode body .zfl-cart-item-qty button{background:#252830!important;color:#f3f4f6!important}
html.dark-mode body .zfl-cart-item-qty button:hover{background:#374151!important}
html.dark-mode body .zfl-cart-item-qty button:disabled{color:#4b5563!important}
html.dark-mode body .zfl-cart-item-qty span{color:#f3f4f6!important;border-color:#374151!important}
html.dark-mode body .zfl-cart-item-sub{color:#f3f4f6!important}
html.dark-mode body .zfl-cart-item-remove{color:#6b7280!important}
html.dark-mode body .zfl-cart-item-remove:hover{color:#ef4444!important}
html.dark-mode body .zfl-cart-summary{border-top-color:#374151!important}
html.dark-mode body .zfl-cart-total{color:#f3f4f6!important}
html.dark-mode body .zfl-cart-checkout{background:#ffcc00!important;color:#111827!important}
html.dark-mode body .zfl-cart-continue{border-color:#374151!important;color:#9ca3af!important}
html.dark-mode body .zfl-cart-emptyall{background:#3b1111!important;color:#fca5a5!important;border-color:#7f1d1d!important}
html.dark-mode body .zfl-cart-toast{background:#f3f4f6!important;color:#111827!important}

@media(max-width:600px){
    .zfl-cart-actions{flex-direction:column}
    .zfl-cart-checkout,.zfl-cart-share,.zfl-cart-continue{text-align:center;justify-content:center}
}
</style>

<div class="zfl-cart" id="zflCart">
    <h1>Mi carrito</h1>
    <div class="zfl-cart-sub" id="zflCartSub"></div>
    <div class="zfl-cart-items" id="zflCartItems">
        <p class="zfl-cart-empty">Cargando carrito...</p>
    </div>
</div>

<div class="zfl-cart-toast" id="zflCartToast"></div>

<script>
(function () {
    var API  = '/wp-json/wc/store/v1/cart';
    var CART = document.getElementById('zflCartItems');
    var SUB  = document.getElementById('zflCartSub');
    var TOAST = document.getElementById('zflCartToast');
    var rates = <?php echo wp_json_encode( $rates ); ?>;
    var selCurrency = localStorage.getItem('zfl_currency') || 'USD';
    var checkoutUrl = <?php echo wp_json_encode( $check_url ); ?>;
    var shopUrl    = <?php echo wp_json_encode( $shop_url ); ?>;
    var currentCart = null;

    function getSymbol(code) { return (rates[code] && rates[code].symbol) || '$'; }
    function convert(cup) {
        var r = rates[selCurrency];
        if (!r || !r.rate || r.rate <= 0) return cup.toFixed(2);
        return (cup / r.rate).toFixed(2);
    }
    function fmt(cup) { return getSymbol(selCurrency) + ' ' + convert(cup); }

    function showToast(msg) {
        TOAST.textContent = msg;
        TOAST.classList.add('show');
        setTimeout(function () { TOAST.classList.remove('show'); }, 3000);
    }

    function updateCount(n) {
        var el = document.getElementById('zslCartCount');
        if (el) el.textContent = String(n);
    }

    function renderCart(cart) {
        currentCart = cart;
        if (!cart.items || !cart.items.length) {
            CART.innerHTML = '<div class="zfl-cart-empty"><p>Tu carrito está vacío.</p><p style="margin-top:10px"><a href="' + shopUrl + '">Explorar productos</a></p></div>';
            SUB.textContent = '';
            updateCount(0);
            return;
        }

        var minorUnit = cart.totals && cart.totals.currency_minor_unit ? cart.totals.currency_minor_unit : 2;
        var totalQty = 0;
        var itemsHtml = cart.items.map(function (item) {
            var img = (item.images && item.images[0]) ? (item.images[0].thumbnail || item.images[0].src) : '';
            var cup = Number(item.totals && item.totals.line_total ? item.totals.line_total : 0) / Math.pow(10, minorUnit);
            var cupUnit = item.quantity > 0 ? cup / item.quantity : cup;
            var qty = item.quantity || 1;
            totalQty += qty;
            return '<div class="zfl-cart-item" data-key="' + item.key + '" data-pid="' + item.id + '">' +
                (img ? '<img class="zfl-cart-item-img" src="' + img + '" alt="">' : '') +
                '<div class="zfl-cart-item-info"><div class="zfl-cart-item-name">' + item.name + '</div>' +
                '<div class="zfl-cart-item-price">' + fmt(cupUnit) + ' c/u</div></div>' +
                '<div class="zfl-cart-item-qty">' +
                '<button class="zfl-qty-minus"' + (qty <= 1 ? ' disabled' : '') + '>&minus;</button>' +
                '<span>' + qty + '</span>' +
                '<button class="zfl-qty-plus">+</button>' +
                '</div>' +
                '<div class="zfl-cart-item-sub">' + fmt(cup) + '</div>' +
                '<button class="zfl-cart-item-remove" title="Eliminar">&times;</button>' +
                '</div>';
        }).join('');

        var cupTotal = Number(cart.totals && cart.totals.total_price ? cart.totals.total_price : 0) / Math.pow(10, minorUnit);
        SUB.textContent = totalQty + ' producto' + (totalQty !== 1 ? 's' : '');

        var summaryHtml =
            '<div class="zfl-cart-summary"><div class="zfl-cart-total"><span>Total</span><strong>' + fmt(cupTotal) + '</strong></div></div>' +
            '<div class="zfl-cart-actions">' +
            '<a class="zfl-cart-checkout" href="' + checkoutUrl + '">Ir a pagar</a>' +
            '<button class="zfl-cart-share" id="zflShareCart">📤 Compartir</button>' +
            '</div>' +
            '<div class="zfl-cart-actions" style="margin-top:8px">' +
            '<a class="zfl-cart-continue" href="' + shopUrl + '">Seguir comprando</a>' +
            '<button class="zfl-cart-emptyall" id="zflEmptyCart">Vaciar carrito</button>' +
            '</div>';

        CART.innerHTML = '<div class="zfl-cart-items">' + itemsHtml + '</div>' + summaryHtml;

        document.getElementById('zflShareCart').addEventListener('click', shareCart);
        document.getElementById('zflEmptyCart').addEventListener('click', function () {
            if (!confirm('¿Vaciar carrito?')) return;
            emptyCart();
        });
    }

    function loadCart() {
        fetch(API, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (cart) { if (cart) renderCart(cart); })
            .catch(function () { CART.innerHTML = '<p class="zfl-cart-empty">No se pudo cargar el carrito.</p>'; });
    }

    /* ── Compartir carrito ── */
    function shareCart() {
        if (!currentCart || !currentCart.items || !currentCart.items.length) return;
        var items = currentCart.items.map(function (item) {
            return item.id + 'x' + item.quantity;
        }).join(',');
        var encoded = btoa(items);
        var shareUrl = window.location.origin + '/carrito/?cart=' + encodeURIComponent(encoded);
        var text = 'Hola! Te comparto mi carrito de Floridame para que lo compres: ' + shareUrl;

        if (navigator.share) {
            navigator.share({ title: 'Carrito Floridame', text: text, url: shareUrl }).catch(function () {});
        } else if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () {
                showToast('Enlace copiado al portapapeles');
            });
        } else {
            prompt('Copia este enlace para compartir:', text);
        }
    }

    function loadSharedCart() {
        var params = new URLSearchParams(window.location.search);
        var cartParam = params.get('cart');
        if (!cartParam) return false;
        try {
            var decoded = atob(cartParam);
            var items = decoded.split(',');
            var promises = items.map(function (pair) {
                var parts = pair.split('x');
                var pid = parseInt(parts[0], 10);
                var qty = parseInt(parts[1], 10) || 1;
                if (!pid) return Promise.resolve();
                return fetch(window.location.origin + '/?wc-ajax=add_to_cart', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'product_id=' + pid + '&quantity=' + qty
                }).then(function (r) { return r.json(); }).catch(function () {});
            });
            Promise.all(promises).then(function () {
                window.history.replaceState({}, '', '/carrito/');
                loadCart();
                showToast('Carrito compartido cargado');
            });
            return true;
        } catch (e) {
            return false;
        }
    }

    /* ── Acciones ── */
    function updateQuantity(key, qty) {
        CART.innerHTML = '<p class="zfl-cart-empty">Actualizando...</p>';
        fetch(API + '/update-item', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'key=' + encodeURIComponent(key) + '&quantity=' + qty
        }).then(function () { window.location.reload(); })
          .catch(function () { window.location.reload(); });
    }

    function removeItem(key) {
        CART.innerHTML = '<p class="zfl-cart-empty">Eliminando...</p>';
        fetch(API + '/remove-item', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'key=' + encodeURIComponent(key)
        }).then(function () { window.location.reload(); })
          .catch(function () { window.location.reload(); });
    }

    function emptyCart() {
        CART.innerHTML = '<p class="zfl-cart-empty">Vaciando...</p>';
        if (currentCart && currentCart.items) {
            var promises = currentCart.items.map(function (item) {
                return fetch(API + '/remove-item', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'key=' + encodeURIComponent(item.key)
                }).catch(function () {});
            });
            Promise.all(promises).then(function () { window.location.reload(); });
        } else {
            window.location.reload();
        }
    }

    CART.addEventListener('click', function (e) {
        var minus = e.target.closest('.zfl-qty-minus');
        var plus  = e.target.closest('.zfl-qty-plus');
        var remove = e.target.closest('.zfl-cart-item-remove');
        if (!minus && !plus && !remove) return;

        var item = e.target.closest('.zfl-cart-item');
        if (!item) return;
        var key = item.getAttribute('data-key');
        var span = item.querySelector('.zfl-cart-item-qty span');
        var qty = parseInt(span.textContent, 10) || 1;

        if (minus) { if (qty > 1) updateQuantity(key, qty - 1); }
        else if (plus) { updateQuantity(key, qty + 1); }
        else if (remove) { removeItem(key); }
    });

    if (!loadSharedCart()) {
        loadCart();
    }
})();
</script>

<?php
get_footer();
