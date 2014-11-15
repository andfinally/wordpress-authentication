<?php
/**
 * This page handles the communications between your external and WordPress sites
 */

require 'config.php';
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base id="baseURL" href="<?php echo BASE_URL; ?>"/>
    <title>Login processing page</title>
    <link rel="stylesheet" type="text/css" href="css/style.css"/>
</head>
<body>

<?php

if ( isset( $_GET[ 'code' ] ) ) {

    // WP authenticate API has returned user here. Passes us a code which we send to the REQUEST_TOKEN_URL
    // to get a permanent token for this user

    // Do we have the right token from our original connect form?
    if ( false == isset( $_GET[ 'state' ] ) ) {
        echo '<h2>Sorry, something went wrong there</h2>';
        echo '<p>Please <a href="login.php">try again</a>.</p>';
        die();
    }

    if ( $_GET[ 'state' ] != $_SESSION[ 'wpcc_state' ] ) {
        echo '<h2>Sorry, something went wrong there</h2>';
        echo '<p>Please <a href="login.php">try again</a>.</p>';
        die();
    }

    // Ask WP for a permanent access token for this user
    $curl = curl_init( REQUEST_TOKEN_URL );
    curl_setopt( $curl, CURLOPT_POST, true );
    curl_setopt( $curl, CURLOPT_POSTFIELDS, array(
        'client_id' => CLIENT_ID,
        'redirect_uri' => REDIRECT_URL,
        'client_secret' => CLIENT_SECRET,
        'code' => $_GET[ 'code' ], // Code /oauth2/authenticate sent us
        'grant_type' => 'authorization_code'
    ) );

    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
    $auth = curl_exec( $curl );
    $secret = json_decode( $auth );
    $access_token = $secret->access_token;

    // Use the access token to get this user's WP profile
    $curl = curl_init( "https://public-api.wordpress.com/rest/v1/me/" );
    curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Bearer ' . $access_token ) );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
    $me = json_decode( curl_exec( $curl ) );

    //  Returns values like
    //	$me->display_name
    //	$me->username
    //	$me->email
    //	$me->avatar_URL
    //	$me->verified

    if ( !empty( $me->username ) ) {

        // Ask WordPress if this username exists
        $url = WORDPRESS_DOMAIN . '/authenticate';

        // Hash the timestamp so we can expire requests
        $timestamp = time();
        $token = md5( $shared_secret . $timestamp );
        $url .= '?timestamp=' . $timestamp . '&token=' . $token . '&username=' . $me->username;

        // Response is like WPAuthCallback({"payload":"ueVI\/bEgRuN3Sftp8bBe1l8laz\/Fec+wqQJf3unAG4APl5dDlOfVVFpGTTp7D+hkjk4OTjRwRsGWG05IzsqHX6HaIJcvRgjThi612woSIFuV2auHL9FajE2e6sXVlC+fm","status":true})
        $response = jsonp_decode( file_get_contents( $url ), true );

        if ( $response[ 'status' ] == true ) {

            // Decrypt payload
            $payload = base64_decode( $response[ 'payload' ] );
            $payload = trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $key, $payload, MCRYPT_MODE_ECB ) );
            $payload = json_decode( $payload, true );

            // Decrypted payload has values like
            // [token] => fa33fd6525bb1317bd0970190395eb92
            // [timestamp] => 1405693264
            // [status] => 1

            if ( $payload[ 'status' ] == true ) {

                // Logged in, redirect to index page
                $_SESSION['authenticated'] = true;
                header( 'Location: ' . BASE_URL );
                die();

            } else {

                echo '<h2>Sorry, you don\'t seem to be a valid user</h2>';
                please_log_in();
                die();

            }

        } else {

            echo '<h2>Sorry, you don\'t seem to be a valid user</h2>';
            please_log_in();
            die();

        }

    } else {

        // No username
        echo '<h2>Sorry, something went wrong there</h2>';
        please_log_in();
        die();

    }

} else {

    please_log_in();

}

function please_log_in() {
    // Prompt the user to log in via WordPress
    $wpcc_state = md5( mt_rand() );
    $_SESSION[ 'wpcc_state' ] = $wpcc_state;

    $params = array(
        'response_type' => 'code',
        'client_id' => CLIENT_ID,
        'state' => $wpcc_state,
        'redirect_uri' => REDIRECT_URL
    );

    $url_to = AUTHENTICATE_URL . '?' . http_build_query( $params );

    echo "<h3>Log in with your WordPress account</h3>";
    echo '<a href="' . $url_to . '"><img src="//s0.wp.com/i/wpcc-button.png" width="231" /></a>';
    die();
}

// json_decodes JSONP response
function jsonp_decode( $jsonp, $assoc = false ) {
    if ( $jsonp[ 0 ] !== '[' && $jsonp[ 0 ] !== '{' ) { // we have JSONP
        $jsonp = substr( $jsonp, strpos( $jsonp, '(' ) );
    }
    return json_decode( trim( $jsonp, '();' ), $assoc );
}

?>
</body>
</html>
