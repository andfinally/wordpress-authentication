<?php
/**
 * Template Name: Authenticate endpoint
 */

/*

    Endpoint for clientside requests to check if a user is a site user.

    Get requests to this page must pass a token, timestamp and username like
    token=9ef50f6ae6777844de1b8b7124dca8ca8
    timestamp=1404981637
    username=xxxxx

    Make token using md5( $shared_secret . $timestamp )
    Make timestamp using time()

    We use token to check the timestamp is genuine
    We won't authenticate the user if timestamp is too old

    If user is known we return JSONP response like
    WPAuthCallback({"payload":"6ko3CoBX93HW7d1q0f9Xt36luIjUDxuGboghwqVtz2COD8knaPFYk6XA0c6SWVwD+n56WBRsfnyCWE1SLVJ\/hrAHIzk4lvdzix6EfAB90j+R94Evh79UD4uAGn\/PpLKXHdGRrBmsVw4gckfFspT5jgC9z52hcaYlqg9yLZ1wVjoIQxvvcYkmeWZ7lw\/L3pY75YGqxsGon9wdefynOocADHw==","status":true})

    Payload is a JSON object encrypted using $key, containing:
    status: Boolean - if username is known - should be same as the plain text status value we return
    token: the token that was sent with the request to this page
    timestamp: the timestamp that was sent with this page

    Requesting app can decrypt payload using $key like this:
    $payload = base64_decode( $payload );
    $payload = trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $key, $payload, MCRYPT_MODE_ECB ) );
    $payload = json_decode( $payload, true );

    Requesting app can check timestamp isn't too old and that encrypted token matches the original one

    If we don't authenticate user we return
    WPAuthCallback({"status":false, "status_code":1})

    where status_code is
    1 - request didn't send a token or timestamp
    2 - request is too old
    3 - request didn't send a valid token
    4 - request didn't send username
    5 - username not recognised

*/

// Change these settings ------------------------------------------


$shared_secret = 'put your unique phrase here';     // Must match the $shared_secret in your config file
$key = 'put your unique phrase here';               // Must match the $key in your config file


// Don't change this stuff ----------------------------------------


header( 'Content-Type: application/javascript; charset=utf-8' );

global $res_arr;
$res_arr = array();

if ( isset( $_GET[ 'timestamp' ] ) ) {
    $get_timestamp = filter_var($_GET['timestamp'], FILTER_SANITIZE_NUMBER_INT);
}

if ( isset( $_GET[ 'token' ] ) ) {
    $get_token = filter_var($_GET['token'], FILTER_SANITIZE_STRING);
}

// Check request sent timestamp and token
if ( empty( $get_timestamp ) || empty( $get_token ) ) {
    halt( 1 );
}

// Check request is less than 300 secs old
if ( ( time() - $get_timestamp ) > 300 ) {
    halt( 2 );
}

// Check request really came from a trusted app
$token = md5( $shared_secret . $get_timestamp );
if ( $token !== $get_token ) {
    halt( 3 );
}

// Get username
$username = filter_var($_GET['username'], FILTER_SANITIZE_STRING);
if ( empty( $username ) ) {
    halt( 4 );
}

// Check username exists
if ( !username_exists( $username ) ) {
    halt( 5 );
}

// Everything's OK, prepare payload for encryption
$payload = array();
$payload[ 'token' ] = $get_token;
$payload[ 'timestamp' ] = $get_timestamp;
$payload[ 'status' ] = true;
$payload = trim( json_encode( $payload ) );
$payload = mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $key, $payload, MCRYPT_MODE_ECB );

$res_arr[ 'payload' ] = base64_encode( $payload );
$res_arr[ 'status' ] = true;

output();

function halt( $code ) {
    global $res_arr;
    $res_arr[ 'status' ] = false;
    $res_arr[ 'status_code' ] = $code;
    output();
}

function output() {
    global $res_arr;
    echo 'WPAuthCallback(' . json_encode( $res_arr ) . ')';
    die();
}
