<?php

/**
 * Include this page at the top of any page you want to protect
 */

require 'config.php';

if ( ! isset ( $_SESSION[ 'authenticated' ] ) || $_SESSION[ 'authenticated' ] !== true ) {
    // Not logged in, go to WordPress authorisation page
    go_to_authorise_page();
}

function go_to_authorise_page() {
    $wpcc_state = md5( mt_rand() );
    $_SESSION[ 'wpcc_state' ] = $wpcc_state;

    $params = array(
        'response_type' => 'code',
        'client_id' => CLIENT_ID,
        'state' => $wpcc_state,
        'redirect_uri' => REDIRECT_URL
    );

    $url_to = AUTHENTICATE_URL . '?' . http_build_query( $params );
    header( 'Location: ' . $url_to );
    die();
}
