<?php

// We store authentication status in session
session_start();
session_regenerate_id();


// Change these settings ------------------------------------------


// Environment and PHP error reporting
define ( 'ENVIRONMENT', 'dev' ); // dev, prod
if ( ENVIRONMENT === 'dev' ) {
    error_reporting( E_ALL );
    ini_set( 'display_errors', 1 );
}

// Copy these settings from your app details page in https://developer.wordpress.com/apps
define ( 'CLIENT_ID', 00000 );
define ( 'CLIENT_SECRET', 'paste your unique WordPress client secret here' );
define ( 'REDIRECT_URL', 'paste your redirect URL here' );

// WordPress site we're going to authenticate against
define( 'WORDPRESS_DOMAIN', 'http://mywordpress.com' );

// External site root folder - add a folder name here if your external site lives in a directory. End with a slash.
// For example if your site's http://example.com/yourapp put define( 'APP_ROOT', 'yourapp/' );
// If your site lives at a top-level address, like http://example.com leave this as is.
define( 'APP_ROOT', '' );

// Add a random phrase here - we use it to encrypt the token
$shared_secret = 'put your unique phrase here';

// Add a different random phrase here - we use it to decrypt the payload we receive from WordPress
$key = 'put your unique phrase here';


// Don't change this stuff ----------------------------------------


define ( 'REQUEST_TOKEN_URL', 'https://public-api.wordpress.com/oauth2/token' );
define ( 'AUTHENTICATE_URL', 'https://public-api.wordpress.com/oauth2/authenticate' );

// Base URL for this app
$protocol = strpos( strtolower( $_SERVER[ 'SERVER_PROTOCOL' ] ), 'https' ) === FALSE ? 'http' : 'https';
$host = $_SERVER[ 'HTTP_HOST' ];
define( 'BASE_URL', $protocol . '://' . $host . '/' . APP_ROOT );
