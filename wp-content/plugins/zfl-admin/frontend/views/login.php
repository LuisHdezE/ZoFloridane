<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Floridame &middot; Iniciar sesión</title>
    <link rel="stylesheet" href="<?php echo esc_url( ZFL_URL . 'frontend/assets/login.css' ); ?>?v=<?php echo esc_attr( ZFL_VERSION ); ?>">
    <link rel="stylesheet" href="<?php echo esc_url( ZFL_URL . 'frontend/assets/dark-mode.css' ); ?>?v=<?php echo esc_attr( ZFL_VERSION ); ?>">
    <script>(function(){var t=localStorage.getItem("zfl_theme");var d=t==="dark"||(t!=="light"&&window.matchMedia&&window.matchMedia("(prefers-color-scheme:dark)").matches);if(d)document.documentElement.classList.add("dark-mode");})();</script>
</head>
<body class="zfl-login-body">
    <main class="zfl-login-card">
        <h1>Floridame</h1>
        <p class="zfl-login-sub">Panel interno</p>

        <?php if ( ! empty( $error ) ) : ?>
            <div class="zfl-login-error"><?php echo esc_html( $error ); ?></div>
        <?php endif; ?>

        <form method="post" class="zfl-login-form">
            <?php wp_nonce_field( 'zfl_login', 'zfl_login_nonce' ); ?>
            <label>
                Usuario
                <input type="text" name="log" required autofocus>
            </label>
            <label>
                Contraseña
                <input type="password" name="pwd" required>
            </label>
            <label class="zfl-login-remember">
                <input type="checkbox" name="rememberme" value="forever">
                Recordarme
            </label>
            <button type="submit" class="zfl-login-submit">Entrar</button>
        </form>
    </main>
</body>
</html>
