<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $GLOBALS['zfl_page_title'] ); ?> &middot; Floridame</title>
    <link rel="stylesheet" href="<?php echo esc_url( ZFL_URL . 'frontend/assets/panel.css' ); ?>?v=<?php echo esc_attr( ZFL_VERSION ); ?>">
    <link rel="stylesheet" href="<?php echo esc_url( ZFL_URL . 'frontend/assets/dark-mode.css' ); ?>?v=<?php echo esc_attr( ZFL_VERSION ); ?>">
    <script>(function(){var t=localStorage.getItem("zfl_theme");var d=t==="dark"||(t!=="light"&&window.matchMedia&&window.matchMedia("(prefers-color-scheme:dark)").matches);if(d)document.documentElement.classList.add("dark-mode");})();</script>
</head>
<body class="zfl-body">
    <header class="zfl-topbar">
        <div class="zfl-topbar-inner">
            <a class="zfl-brand" href="https://zofloridane.com" target="_blank" rel="noopener">
                <img src="<?php echo esc_url( ZFL_URL . 'frontend/assets/logo-bg-black.png' ); ?>" alt="Floridame" class="zfl-brand-logo">
            </a>
            <div class="zfl-loc-switch">
                <span class="zfl-loc-switch-label">Localidad</span>
                <select id="zfl-loc-switch">
                    <option value="0">Todas las localidades</option>
                    <?php
                    $zfl_current_loc = isset( $_COOKIE['zfl_panel_loc'] ) ? (int) $_COOKIE['zfl_panel_loc'] : 0;
                    foreach ( ZFL_Catalog::get_localidades() as $zfl_loc ) :
                        ?>
                        <option value="<?php echo (int) $zfl_loc['id']; ?>" <?php selected( $zfl_current_loc, (int) $zfl_loc['id'] ); ?>><?php echo esc_html( $zfl_loc['name'] ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <nav class="zfl-topnav">
                <a href="<?php echo esc_url( home_url( ZFL_SLUG . '/dashboard/' ) ); ?>">Resumen</a>
                <a href="<?php echo esc_url( home_url( ZFL_SLUG . '/finance/' ) ); ?>">Finanzas</a>
                <a href="<?php echo esc_url( home_url( ZFL_SLUG . '/orders/' ) ); ?>">Pedidos</a>
                <a href="<?php echo esc_url( home_url( ZFL_SLUG . '/catalogo/' ) ); ?>">Catálogo</a>
                <a href="<?php echo esc_url( home_url( ZFL_SLUG . '/visits/' ) ); ?>">Visitas</a>
                <?php if ( current_user_can( 'manage_options' ) ) : ?>
                    <a href="<?php echo esc_url( home_url( ZFL_SLUG . '/nomina/' ) ); ?>">Nómina</a>
                <?php endif; ?>
                <button type="button" class="zfl-dark-toggle" id="zflDarkToggle" title="Cambiar modo" aria-label="Cambiar modo claro/oscuro">
                    <svg class="zfl-icon-sun" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                    <svg class="zfl-icon-moon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
                <a class="zfl-logout" href="<?php echo esc_url( home_url( ZFL_SLUG . '/logout/' ) ); ?>">Salir</a>
            </nav>
        </div>
    </header>
    <main class="zfl-main">
    <script>
    (function(){
        var toggle = document.getElementById('zflDarkToggle');
        if (!toggle) return;
        toggle.addEventListener('click', function(){
            var isDark = document.documentElement.classList.contains('dark-mode');
            var next = !isDark;
            if (next) {
                document.documentElement.classList.add('dark-mode');
            } else {
                document.documentElement.classList.remove('dark-mode');
            }
            localStorage.setItem('zfl_theme', next ? 'dark' : 'light');
        });
    })();
    </script>
