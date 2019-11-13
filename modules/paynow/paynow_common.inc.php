<?php
/**
 * common.php
 *
 * Copyright 2019 Netcash (Pty) Ltd
 */

define( 'PN_SOFTWARE_NAME', 'PrestaShop' );
define( 'PN_SOFTWARE_VER', Configuration::get('PS_INSTALL_VERSION') );
define( 'PN_MODULE_NAME', 'Netcash-PayNow-Prestashop' );
define( 'PN_MODULE_VER', '1.0' );
define( 'PN_DEBUG', ( Configuration::get('PAYNOW_ENABLE_LOGS')  ? true : false ) );

if( in_array( 'curl', get_loaded_extensions() ) ) {
    define( 'PN_CURL', '' );
}

// Create user agrent
define( 'PN_USER_AGENT', PN_SOFTWARE_NAME . '/' . PN_SOFTWARE_VER . ' ' . PN_MODULE_NAME . '/' . PN_MODULE_VER );

// General Defines
define( 'PN_TIMEOUT', 15 );

define( 'PN_ERR_AMOUNT_MISMATCH', 'Amount mismatch' );
define( 'PN_ERR_BAD_ACCESS', 'Bad access of page' );
define( 'PN_ERR_BAD_SOURCE_IP', 'Bad source IP address' );
define( 'PN_ERR_CONNECT_FAILED', 'Failed to connect' );
define( 'PN_ERR_INVALID_SIGNATURE', 'Security signature mismatch' );
define( 'PN_ERR_MERCHANT_ID_MISMATCH', 'Merchant ID mismatch' );
define( 'PN_ERR_NO_SESSION', 'No saved session found for ITN transaction' );
define( 'PN_ERR_ORDER_ID_MISSING_URL', 'Order ID not present in URL' );
define( 'PN_ERR_ORDER_ID_MISMATCH', 'Order ID mismatch' );
define( 'PN_ERR_ORDER_INVALID', 'This order ID is invalid' );
define( 'PN_ERR_ORDER_NUMBER_MISMATCH', 'Order Number mismatch' );
define( 'PN_ERR_ORDER_PROCESSED', 'This order has already been processed' );
define( 'PN_ERR_PDT_FAIL', 'PDT query failed' );
define( 'PN_ERR_PDT_TOKEN_MISSING', 'PDT token not present in URL' );
define( 'PN_ERR_SESSIONID_MISMATCH', 'Session ID mismatch' );
define( 'PN_ERR_UNKNOWN', 'Unknown error occurred' );


define( 'PN_MSG_OK', 'Payment was successful' );
define( 'PN_MSG_FAILED', 'Payment has failed' );
define( 'PN_MSG_PENDING',
    'The payment is pending. Please note, you will receive another '.
    ' notification when the payment status changes to'.
    ' "Completed", or "Failed"' );

/**
 *
 * Log function for logging output.
 *
 * @param $msg String Message to log
 * @param $close Boolean Whether to close the log file or not
 */
function pnlog( $msg = '', $close = false ) {
    static $fh = 0;
    global $module;

    // Only log if debugging is enabled
    if( PN_DEBUG ) {
        if( $close ) {
            fclose( $fh );
        } else {
            // If file doesn't exist, create it
            if( !$fh ) {
                $pathinfo = pathinfo( __FILE__ );
                $fh = fopen( $pathinfo['dirname'] .'/paynow.log', 'a+' );
            }

            // If file was successfully created
            if( $fh ) {
                $line = date( 'Y-m-d H:i:s' ) .' : '. $msg ."\n";

                fwrite( $fh, $line );
            }
        }
    }
}

/**
 *
 */
function pnGetPostData() {
    $postedData = $_POST;

    // Strip any slashes in data
    foreach( $postedData as $key => $val )
        $postedData[$key] = stripslashes( $val );

    // Return "false" if no data was received
    if( sizeof( $postedData ) == 0 )
        return( false );
    else
        return( $postedData );
}

/**
 *
 */
function verifyPayNowSignature() {
    return true;
}

/**
 * @param string $host Hostname to use
 * @param string $paramString  Parameter string to send
 * @param string $proxy Address of proxy to use or NULL if no proxy
 *
 * @return bool
 */
function validatePayNowData( $host = 'paynow.netcash.co.za', $paramString = '', $proxy = null )  {
    pnlog( 'Host = ' . $host );
    pnlog( 'Params = ' . $paramString );

     //Use cURL (if available)
     if( defined( 'PN_CURL' ) )
     {
         // Variable initialization
         $url = 'https://'. $host .'/eng/query/validate';

         // Create default cURL object
         $ch = curl_init();

         // Set cURL options - Use curl_setopt for freater PHP compatibility
         // Base settings
         curl_setopt( $ch, CURLOPT_USERAGENT, PN_USER_AGENT );  // Set user agent
         curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );      // Return output as string rather than outputting it
         curl_setopt( $ch, CURLOPT_HEADER, false );             // Don't include header in output
         curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
         curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

         // Standard settings
         curl_setopt( $ch, CURLOPT_URL, $url );
         curl_setopt( $ch, CURLOPT_POST, true );
         curl_setopt( $ch, CURLOPT_POSTFIELDS, $paramString );
         curl_setopt( $ch, CURLOPT_TIMEOUT, PN_TIMEOUT );
         if( !empty( $proxy ) )
             curl_setopt( $ch, CURLOPT_PROXY, $proxy );

         // Execute CURL
         $response = curl_exec( $ch );
         curl_close( $ch );
     }
     // Use fsockopen
     else
     {
         // Variable initialization
         $header = '';
         $res = '';
         $headerDone = false;

         // Construct Header
         $header = "POST /eng/query/validate HTTP/1.0\r\n";
         $header .= "Host: ". $host ."\r\n";
         $header .= "User-Agent: " . PN_USER_AGENT . "\r\n";
         $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
         $header .= "Content-Length: " . strlen( $paramString ) . "\r\n\r\n";

         // Connect to server
         $socket = fsockopen( 'ssl://'. $host, 443, $errno, $errstr, PN_TIMEOUT );

         // Send command to server
         fputs( $socket, $header . $paramString );

	     $response = '';
         // Read the response from the server
         while( !feof( $socket ) ) {
             $line = fgets( $socket, 1024 );

             // Check if we are finished reading the header yet
             if( strcmp( $line, "\r\n" ) == 0 ) {
                 // read the header
                 $headerDone = true;
             }
             // If header has been processed
             else if( $headerDone ) {
                 // Read the main response
                 $response .= $line;
             }
         }

     }

     pnlog( "Response:\n" . print_r( $response, true ) );

     // Interpret Response
     $lines = explode( "\r\n", $response );
     $verifyResult = trim( $lines[0] );

     if( strcasecmp( $verifyResult, 'VALID' ) == 0 )
         return( true );
     else
         return( false );

}

/**
 * @param string $sourceIP Source IP address
 *
 * @return bool
 */
function checkPayNowIP( $sourceIP ) {
    // Variable initialization
    $validHosts = array(
        'netcash.co.za',
        'paynow.netcash.co.za',
    );

    $validIps = array();

    foreach( $validHosts as $host ) {
        $ips = gethostbynamel( $host );

        if( $ips !== false )
            $validIps = array_merge( $validIps, $ips );
    }

    // Remove duplicates
    $validIps = array_unique( $validIps );

    pnlog( "Valid IPs:\n" . print_r( $validIps, true ) );

    if( in_array( $sourceIP, $validIps ) )
        return( true );
    else
        return( false );
}

/**
 *
 * Checks to see whether the given amounts are equal using a proper floating
 * point comparison with an Epsilon which ensures that insignificant decimal
 * places are ignored in the comparison.
 *
 * eg. 100.00 is equal to 100.0001
 *
 * @param $amount1 Float 1st amount for comparison
 * @param $amount2 Float 2nd amount for comparison
 *
 * @return bool
 */
function paynowAmoutsAreEqual( $amount1, $amount2 ) {
    if( abs( (float)$amount1 - (float)$amount2 ) > 0.01 )
        return( false );
    else
        return( true );
}
