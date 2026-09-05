<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$stats  = ZFL_Visits::stats();
$daily  = ZFL_Visits::daily( 7 );
$recent = ZFL_Visits::recent( 15 );
$max_visitas = 1;
foreach ( $daily as $d ) {
    $max_visitas = max( $max_visitas, (int) $d['visitas'] );
}

if ( ! function_exists( 'zfl_visit_device' ) ) {
    function zfl_visit_device( $ua ) {
        $ua = (string) $ua;
        if ( false !== stripos( $ua, 'iphone' ) ) { return 'iPhone'; }
        if ( false !== stripos( $ua, 'ipad' ) ) { return 'iPad'; }
        if ( false !== stripos( $ua, 'android' ) ) { return 'Android'; }
        if ( false !== stripos( $ua, 'windows' ) ) { return 'Windows'; }
        if ( false !== stripos( $ua, 'mac os' ) || false !== stripos( $ua, 'macintosh' ) ) { return 'Mac'; }
        if ( false !== stripos( $ua, 'linux' ) ) { return 'Linux'; }
        return 'Otro';
    }
}
?>
<section class="zfl-section">

    <h1>Visitas</h1>

    <div class="zfl-stats">
        <div class="zfl-stat">
            <span class="zfl-stat-num"><?php echo (int) $stats['visits_today']; ?></span>
            <span class="zfl-stat-label">Visitas hoy</span>
            <span class="zfl-stat-sub"><?php echo (int) $stats['uniques_today']; ?> único(s)</span>
        </div>
        <div class="zfl-stat">
            <span class="zfl-stat-num"><?php echo (int) $stats['uniques_total']; ?></span>
            <span class="zfl-stat-label">Visitantes únicos</span>
            <span class="zfl-stat-sub">de <?php echo (int) $stats['visits_total']; ?> visitas totales</span>
        </div>
        <div class="zfl-stat">
            <span class="zfl-stat-num"><?php echo (int) $stats['initiated']; ?></span>
            <span class="zfl-stat-label">Inicios de pedido</span>
            <span class="zfl-stat-sub">Llegaron al checkout</span>
        </div>
        <div class="zfl-stat">
            <span class="zfl-stat-num"><?php echo (int) $stats['completed']; ?></span>
            <span class="zfl-stat-label">Pedidos completados</span>
            <span class="zfl-stat-sub"><?php echo esc_html( $stats['conversion'] ); ?>% de conversión</span>
        </div>
    </div>

    <h2 class="zfl-dash-h2">Últimos 7 días</h2>
    <div class="zfl-bars">
        <?php foreach ( $daily as $fecha => $d ) :
            $w = $max_visitas > 0 ? round( (int) $d['visitas'] * 100 / $max_visitas ) : 0;
            ?>
            <div class="zfl-bar-row">
                <span class="zfl-bar-date"><?php echo esc_html( mysql2date( 'D j', $fecha . ' 00:00:00' ) ); ?></span>
                <div class="zfl-bar-track"><div class="zfl-bar-fill" style="width: <?php echo (int) $w; ?>%;"></div></div>
                <span class="zfl-bar-nums">
                    <b><?php echo (int) $d['visitas']; ?></b> visitas ·
                    <?php echo (int) $d['unicos']; ?> únicos ·
                    <?php echo (int) $d['inicios']; ?> inicios ·
                    <?php echo (int) $d['completados']; ?> pedidos
                </span>
            </div>
        <?php endforeach; ?>
        <?php if ( empty( $daily ) ) : ?>
            <p class="zfl-order-label">Sin datos aún. Las visitas empiezan a registrarse desde que subes esta versión.</p>
        <?php endif; ?>
    </div>

    <h2 class="zfl-dash-h2">Visitas recientes</h2>
    <div class="zfl-card">
        <table class="zfl-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Dispositivo</th>
                    <th>Navegador/sistema</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $recent ) ) : ?>
                    <tr><td colspan="3">Sin visitas registradas todavía.</td></tr>
                <?php else : ?>
                    <?php foreach ( $recent as $r ) : ?>
                        <tr>
                            <td><?php echo esc_html( mysql2date( 'j M, H:i', $r['visited_at'] ) ); ?></td>
                            <td><?php echo esc_html( zfl_visit_device( $r['user_agent'] ) ); ?></td>
                            <td class="zfl-order-label"><?php echo esc_html( mb_substr( (string) $r['user_agent'], 0, 60 ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</section>
