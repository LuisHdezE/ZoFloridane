<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$result  = ZFL_Reviews::handle_request();
$reviews = ZFL_Reviews::get_reviews( false );

$pending = array();
$active  = array();
foreach ( $reviews as $idx => $rev ) {
    if ( ! empty( $rev['is_active'] ) ) {
        $active[ $idx ] = $rev;
    } else {
        $pending[ $idx ] = $rev;
    }
}
?>
<h2 class="zfl-tab-title">Reseñas de clientes</h2>

<?php if ( is_wp_error( $result ) ) : ?>
    <div class="zfl-error"><?php echo esc_html( $result->get_error_message() ); ?></div>
<?php elseif ( is_array( $result ) && isset( $result['msg'] ) ) : ?>
    <div class="zfl-success"><?php echo esc_html( $result['msg'] ); ?></div>
<?php endif; ?>

<p class="zfl-meta">Los clientes envían reseñas desde la página de confirmación de pedido. Aquí solo las apruebas, rechazas o eliminas.</p>

<?php if ( empty( $reviews ) ) : ?>
    <div class="zfl-empty">No hay reseñas todavía. Aparecerán aquí cuando los clientes envíen una desde la página de confirmación.</div>
<?php endif; ?>

<?php if ( count( $pending ) > 0 ) : ?>
<h3 class="zfl-section-subtitle">Pendientes (<?php echo count( $pending ); ?>)</h3>
<div class="zfl-reviews-admin">
    <?php foreach ( $pending as $idx => $rev ) : ?>
        <div class="zfl-review-card zfl-review-pending">
            <div class="zfl-review-header">
                <span class="zfl-review-stars"><?php echo wp_kses_post( ZFL_Reviews::render_stars( $rev['stars'] ) ); ?></span>
                <span class="zfl-review-author"><?php echo esc_html( $rev['name'] ); ?></span>
                <?php if ( ! empty( $rev['from'] ) ) : ?>
                    <span class="zfl-review-from">— <?php echo esc_html( $rev['from'] ); ?></span>
                <?php endif; ?>
            </div>
            <p class="zfl-review-text"><?php echo esc_html( $rev['text'] ); ?></p>
            <div class="zfl-review-actions">
                <form method="post" style="display:inline">
                    <?php wp_nonce_field( 'zfl_rev_action', 'zfl_rev_nonce' ); ?>
                    <input type="hidden" name="zfl_rev_action" value="activate">
                    <input type="hidden" name="zfl_rev_index" value="<?php echo (int) $idx; ?>">
                    <button type="submit" class="zfl-btn-sm zfl-btn-approve">Aprobar</button>
                </form>
                <form method="post" style="display:inline">
                    <?php wp_nonce_field( 'zfl_rev_action', 'zfl_rev_nonce' ); ?>
                    <input type="hidden" name="zfl_rev_action" value="delete">
                    <input type="hidden" name="zfl_rev_index" value="<?php echo (int) $idx; ?>">
                    <button type="submit" class="zfl-btn-sm zfl-btn-delete" onclick="return confirm('¿Eliminar esta reseña?')">Eliminar</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ( count( $active ) > 0 ) : ?>
<h3 class="zfl-section-subtitle">Aprobadas (<?php echo count( $active ); ?>)</h3>
<div class="zfl-reviews-admin">
    <?php foreach ( $active as $idx => $rev ) : ?>
        <div class="zfl-review-card zfl-review-active">
            <div class="zfl-review-header">
                <span class="zfl-review-stars"><?php echo wp_kses_post( ZFL_Reviews::render_stars( $rev['stars'] ) ); ?></span>
                <span class="zfl-review-author"><?php echo esc_html( $rev['name'] ); ?></span>
                <?php if ( ! empty( $rev['from'] ) ) : ?>
                    <span class="zfl-review-from">— <?php echo esc_html( $rev['from'] ); ?></span>
                <?php endif; ?>
            </div>
            <p class="zfl-review-text"><?php echo esc_html( $rev['text'] ); ?></p>
            <div class="zfl-review-actions">
                <form method="post" style="display:inline">
                    <?php wp_nonce_field( 'zfl_rev_action', 'zfl_rev_nonce' ); ?>
                    <input type="hidden" name="zfl_rev_action" value="deactivate">
                    <input type="hidden" name="zfl_rev_index" value="<?php echo (int) $idx; ?>">
                    <button type="submit" class="zfl-btn-sm zfl-btn-warn">Ocultar</button>
                </form>
                <form method="post" style="display:inline">
                    <?php wp_nonce_field( 'zfl_rev_action', 'zfl_rev_nonce' ); ?>
                    <input type="hidden" name="zfl_rev_action" value="delete">
                    <input type="hidden" name="zfl_rev_index" value="<?php echo (int) $idx; ?>">
                    <button type="submit" class="zfl-btn-sm zfl-btn-delete" onclick="return confirm('¿Eliminar esta reseña permanentemente?')">Eliminar</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
