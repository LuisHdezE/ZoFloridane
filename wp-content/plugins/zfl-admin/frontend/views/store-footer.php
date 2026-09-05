<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Footer público de ZoFloridane.
 * Variables: $localidades, $logo, $shop_url, $account_url, $cart_url.
 */

$faq_url     = function_exists( 'zfl_storefront_find_page_url' ) ? zfl_storefront_find_page_url( array( 'preguntas-frecuentes', 'faq' ) ) : '';
$contact_url = function_exists( 'zfl_storefront_find_page_url' ) ? zfl_storefront_find_page_url( array( 'contacto', 'contact' ) ) : '';
$privacy_url = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
$terms_url   = '';
if ( function_exists( 'wc_terms_and_conditions_page_id' ) ) {
    $terms_id = (int) wc_terms_and_conditions_page_id();
    if ( $terms_id > 0 ) {
        $terms_url = get_permalink( $terms_id );
    }
}
?>
<footer class="zsl-footer">
    <div class="zsl-footer-inner">
        <div class="zsl-footer-brand">
            <a class="zsl-footer-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="ZoFloridane, inicio">
                <?php if ( $logo ) : ?>
                    <img src="<?php echo esc_url( $logo ); ?>" alt="ZoFloridane">
                <?php else : ?>
                    <span>ZoFloridane</span>
                <?php endif; ?>
            </a>
            <p class="zsl-footer-tag">Compra desde Estados Unidos para tu familia en Cuba con disponibilidad por localidad, pago mediante Zelle y acompañamiento durante el pedido.</p>
            <div class="zsl-footer-badges">
                <span>Pago con Zelle</span>
                <span>Atención por WhatsApp</span>
            </div>
        </div>

        <div class="zsl-footer-col">
            <h4>Comprar</h4>
            <ul class="zsl-footer-links">
                <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a></li>
                <li><a href="<?php echo esc_url( $shop_url ); ?>">Todos los productos</a></li>
                <li><a href="<?php echo esc_url( home_url( '/#como-comprar' ) ); ?>">Cómo comprar</a></li>
            </ul>
        </div>

        <div class="zsl-footer-col">
            <h4>Tu pedido</h4>
            <ul class="zsl-footer-links">
                <li><a href="<?php echo esc_url( home_url( '/rastrear-pedido/' ) ); ?>">Seguimiento</a></li>
                <li><a href="<?php echo esc_url( $account_url ); ?>">Mi cuenta</a></li>
                <li><a href="<?php echo esc_url( $cart_url ); ?>">Carrito</a></li>
            </ul>
            <p class="zsl-footer-note">Las instrucciones de Zelle se muestran dentro del flujo del pedido.</p>
        </div>

        <div class="zsl-footer-col">
            <h4>Ayuda</h4>
            <ul class="zsl-footer-links">
                <?php if ( $faq_url ) : ?><li><a href="<?php echo esc_url( $faq_url ); ?>">Preguntas frecuentes</a></li><?php endif; ?>
                <?php if ( $contact_url ) : ?><li><a href="<?php echo esc_url( $contact_url ); ?>">Contacto</a></li><?php endif; ?>
                <li><span class="zsl-footer-static">Atención personalizada por WhatsApp</span></li>
            </ul>
            <?php if ( $privacy_url || $terms_url ) : ?>
                <div class="zsl-footer-legal">
                    <?php if ( $privacy_url ) : ?><a href="<?php echo esc_url( $privacy_url ); ?>">Privacidad</a><?php endif; ?>
                    <?php if ( $terms_url ) : ?><a href="<?php echo esc_url( $terms_url ); ?>">Términos</a><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="zsl-footer-col">
            <h4>Entregamos en</h4>
            <?php if ( ! empty( $localidades ) ) : ?>
                <div class="zsl-locs">
                    <?php foreach ( $localidades as $loc ) : ?>
                        <span class="zsl-loc-pill"><?php echo esc_html( $loc['name'] ); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="zsl-footer-tag">Selecciona tu localidad al comenzar para consultar la disponibilidad.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="zsl-footer-bottom">
        <span>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <strong>ZoFloridane</strong>. Todos los derechos reservados.</span>
        <span>Compras que acortan distancias.</span>
    </div>
</footer>
