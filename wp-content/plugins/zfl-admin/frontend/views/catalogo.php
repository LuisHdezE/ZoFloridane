<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$catalog_result = ZFL_Catalog::handle_request();

$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'productos';
$base = home_url( ZFL_SLUG . '/catalogo/' );

$tabs = array(
    'productos'   => 'Productos',
    'categorias'  => 'Categorías',
    'localidades' => 'Localidades',
    'promos'      => 'Promos',
    'monedas'     => 'Monedas',
    'resenas'     => 'Reseñas',
    'zelle'       => 'Cuentas Zelle',
);

// Teléfonos: solo el administrador.
if ( current_user_can( 'manage_options' ) ) {
    $tabs['telefonos'] = 'Teléfonos';
}

// Gestor: ocultar Zelle, Teléfonos y Promos. Monedas SÍ la ve.
$user = wp_get_current_user();
$is_gestor = $user && ! in_array( 'administrator', (array) $user->roles, true ) && ! in_array( 'zfl_admin_2', (array) $user->roles, true );
if ( $is_gestor ) {
    unset( $tabs['zelle'], $tabs['telefonos'], $tabs['promos'] );
}

if ( ! isset( $tabs[ $tab ] ) ) {
    $tab = 'productos';
}
?>
<section class="zfl-section">

    <h1>Catálogo</h1>

    <nav class="zfl-subnav">
        <?php foreach ( $tabs as $slug => $label ) : ?>
            <a class="zfl-subnav-link <?php echo $tab === $slug ? 'active' : ''; ?>"
               href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base ) ); ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php
    if ( 'categorias' === $tab ) {
        include ZFL_PATH . 'frontend/views/tab-categorias.php';
    } elseif ( 'localidades' === $tab ) {
        include ZFL_PATH . 'frontend/views/tab-localidades.php';
    } elseif ( 'promos' === $tab ) {
        include ZFL_PATH . 'frontend/views/tab-promos.php';
    } elseif ( 'monedas' === $tab ) {
        include ZFL_PATH . 'frontend/views/tab-monedas.php';
    } elseif ( 'resenas' === $tab ) {
        include ZFL_PATH . 'frontend/views/tab-resenas.php';
    } elseif ( 'telefonos' === $tab ) {
        include ZFL_PATH . 'frontend/views/tab-telefonos.php';
    } elseif ( 'zelle' === $tab ) {
        include ZFL_PATH . 'frontend/views/zelle.php';
    } else {
        include ZFL_PATH . 'frontend/views/tab-productos.php';
    }
    ?>

    <!-- Lightbox -->
    <div class="zfl-lightbox" id="zfl-lightbox" role="dialog" aria-modal="true" aria-label="Imagen ampliada" hidden>
        <button type="button" class="zfl-lightbox-close" aria-label="Cerrar"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        <img class="zfl-lightbox-img" alt="">
        <p class="zfl-lightbox-caption"></p>
    </div>

</section>
