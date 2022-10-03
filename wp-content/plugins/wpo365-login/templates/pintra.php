<?php

    // Prevent public access to this script
    defined( 'ABSPATH' ) or die();
    
    ?>
        <!-- Dependencies -->
        <script src="https://unpkg.com/react@16/umd/react.production.min.js"></script>
        <script src="https://unpkg.com/react-dom@16/umd/react-dom.production.min.js"></script>

        <script>
            window.wpo365 = window.wpo365 || {}; 
            window.wpo365.blocks = <?php
                echo json_encode( array(
                    'nonce' => \wp_create_nonce( 'wp_rest' ),
                    'apiUrl' => esc_url_raw( \trailingslashit( $GLOBALS[ 'WPO_CONFIG' ][ 'url_info' ][ 'wp_site_url' ] ) ) . 'wp-json/wpo365/v1/graph',
                )); ?>
        </script>

        <!-- Main -->
        <div>
            <script src="<?php echo esc_url( $script_url ) ?>"
                data-nonce="<?php echo wp_create_nonce( 'wpo365_fx_nonce' ) ?>"
                data-wpajaxadminurl="<?php echo admin_url() . '/admin-ajax.php' ?>"
                data-props="<?php echo htmlspecialchars( $props ) ?>">
            </script>
            <!-- react root element will be added here -->
        </div>