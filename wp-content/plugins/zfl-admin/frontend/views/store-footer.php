<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Footer público de Zofloridane.
 * Variables disponibles: $localidades, $logo, $shop_url, $account_url, $cart_url
 */
?>
<footer class="zsl-footer">
    <div class="zsl-footer-inner">
        <div class="zsl-footer-brand">
            <?php if ( $logo ) : ?>
                <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
            <?php else : ?>
                <span><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
            <?php endif; ?>
            <p class="zsl-footer-tag">Compra desde Estados Unidos para tus seres queridos en Cuba. Una experiencia simple, clara y acompañada de principio a fin.</p>
            <div class="zsl-footer-badges">
                <span>Pago con Zelle</span>
                <span>Atención personalizada</span>
            </div>
        </div>

        <div class="zsl-footer-col">
            <h4>Comprar</h4>
            <ul class="zsl-footer-links">
                <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a></li>
                <li><a href="<?php echo esc_url( $shop_url ); ?>">Todos los productos</a></li>
                <li><a href="<?php echo esc_url( $cart_url ); ?>">Carrito</a></li>
                <li><a href="<?php echo esc_url( $account_url ); ?>">Mi cuenta</a></li>
            </ul>
        </div>

        <div class="zsl-footer-col">
            <h4>Tu pedido</h4>
            <ul class="zsl-footer-links">
                <li><a href="<?php echo esc_url( home_url( '/rastrear-pedido/' ) ); ?>">Rastrear mi envío</a></li>
                <li><a href="<?php echo esc_url( home_url( '/?s=&post_type=product' ) ); ?>">Buscar productos</a></li>
            </ul>
            <p class="zsl-footer-note">El número o correo de Zelle se muestra al finalizar el pedido.</p>
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
                <p class="zsl-footer-tag">Consulta las localidades disponibles al comenzar tu compra.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="zsl-footer-bottom">
        <span>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong>. Todos los derechos reservados.</span>
        <span>Compras que acortan distancias.</span>
    </div>
</footer>
