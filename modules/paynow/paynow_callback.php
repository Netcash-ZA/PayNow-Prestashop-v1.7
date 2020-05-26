<?php
/**
 * paynow_callback.php
 *
 */

include( dirname(__FILE__).'/../../config/config.inc.php' );
include( dirname(__FILE__).'/paynow.php' );

if(!function_exists('pnlog')) {
	include( dirname( __FILE__ ) . '/paynow_common.inc.php' );
}

$route = "index.php?controller=my-account";
$url_for_redirect = _PS_BASE_URL_.__PS_BASE_URI__.$route;

function pnHalt($errMsg) {
	pnlog( 'Error occurred: ' . $errMsg, true );
	exit();
}

// Check if this is an ITN request
// Has to be done like this (as opposed to "exit" as processing needs
// to continue after this check.
// if( ( $_GET['itn_request'] == 'true' ) ) {
function pn_do_transaction() {
	// Variable Initialization
	$hasError     = false;
	$errorMessage = '';
	$isDone       = false;

	$paynowHost   = 'https://paynow.netcash.co.za/site/paynow.aspx';
	$paramString  = '';

	$paynow = new PayNow();

	pnlog( 'Pay Now callback call received' );

	// Indicate that the request has been successful
	header( 'HTTP/1.0 200 OK' );
	flush();

	pnlog( 'Get posted data' );
	// Posted variables
	$postedData = pnGetPostData();

	$cartId = (int) $postedData['Extra2'];

	pnlog( 'Pay Now Data: ' . print_r( $postedData, true ) );

	if( $postedData === false ) {
		pnHalt(PN_ERR_BAD_ACCESS);
	}


	// Verify source IP (If not in debug mode)
//	pnlog( 'Verify source IP' );
//	if( !checkPayNowIP( $_SERVER['REMOTE_ADDR'] ) ) {
//		pnHalt(PN_ERR_BAD_SOURCE_IP);
//	}

	// Get order data
	$cart = new Cart($cartId);

	//pnlog( "Purchase:\n". print_r( $cart, true )  );

	//// Verify data received
	pnlog( 'Verify data received' );

//	$dataValid = validatePayNowData( $paynowHost, $paramString );



	// Check data against internal order

	// pnlog( 'Check data against internal order' );
	$fromCurrency = new Currency(Currency::getIdByIsoCode('ZAR'));
	$toCurrency = new Currency((int)$cart->id_currency);

	$total = Tools::convertPriceFull( $postedData['Extra1'], $fromCurrency, $toCurrency );

	// Check order amount
	if( strcasecmp( $postedData['Extra3'], $cart->secure_key ) != 0 ) {
		$hasError     = true;
		$errorMessage = PN_ERR_SESSIONID_MISMATCH;
	}


	$vendor_name = Configuration::get('PS_SHOP_NAME');
	$vendor_url = Tools::getShopDomain(true, true);

	// Check status and update order

	pnlog( 'Check status and update order' );

	if (empty(Context::getContext()->link)) {
		Context::getContext()->link = new Link();
	}

//  $sessionid = $postedData['sessid'];
	$transaction_id = $cartId;
	$secureKey = $postedData['Extra3'];

	switch( $postedData['TransactionAccepted'] ) {
		case 'true':
			pnlog( '- Complete' );

			// Is Notify? Only notify posts back this reason
			$is_notify = isset($postedData['Reason']) && $postedData['Reason'] == 'Success';
			pnlog( '- Is Notify? ' . ($is_notify ? 'TRUE' : 'FALSE') );

			$order_exists = $cart->OrderExists();
			pnlog( '- Order Exists? ' . ($order_exists ? 'TRUE' : 'FALSE') );

			global $kernel;
			if(!$kernel){
			require_once _PS_ROOT_DIR_.'/app/AppKernel.php';
			$kernel = new \AppKernel('prod', false);
			$kernel->boot();
			}

			if(!$order_exists) {
				// Update the purchase status
				$paynow->validateOrder( $cartId, _PS_OS_PAYMENT_, (float) $total,
					$paynow->displayName, null, array( 'transaction_id' => $transaction_id ), null, false, $secureKey );
			} else {
				pnlog( '- Adding order history' );

				$orderId = Order::getIdByCartId((int) $cartId);

				$history = new OrderHistory();
				$history->id_order = $orderId;
				$order = new Order((int) $orderId);
				$history->changeIdOrderState((int) _PS_OS_PAYMENT_, $order, false);

				pnlog( "Changed to " . _PS_OS_PAYMENT_ . " for cart {$cartId} / order {$orderId}" );
			}

			pnlog( '- Redirecting to order-confirmation' );
            Tools::redirect('index.php?controller=order-confirmation&'.$_SERVER['QUERY_STRING']);
			break;

		case 'false':
			pnlog( '- Failed' );

			$is_pending = isset($postedData['Reason']) && stristr($postedData['Reason'], 'pending');
			pnlog( '- Is Pending? ' . ($is_pending ? 'TRUE' : 'FALSE') );

			$order_exists = $cart->OrderExists();
			pnlog( '- Order Exists? ' . ($order_exists ? 'TRUE' : 'FALSE') );

			if(!$order_exists) {

				$state = _PS_OS_ERROR_;
				if($is_pending) {
					$state = _PS_OS_BANKWIRE_;
				}
				// If payment fails, delete the purchase log
				$paynow->validateOrder( $cartId, $state, (float) $total,
					$paynow->displayName, null, array( 'transaction_id' => $transaction_id ), null, false, $secureKey );
			}

			pnlog( '- Redirecting to order history (a)' );
            Tools::redirect('index.php?controller=history');
			break;

		default:
			// If unknown status, do nothing (safest course of action)
			pnlog( 'Unknown status: ' . $postedData['TransactionAccepted'] );
			pnlog( '- Redirecting to order history (b)' );
	        Tools::redirect('index.php?controller=history');
			break;
	}
}

if( isset($_POST) && !empty($_POST) ) {

    // This is the notification OR CC payment coming in!
    // Act as an IPN request and forward request to Credit Card method.
    // Logic is exactly the same

    // DO your thang
    pn_do_transaction();
    pnlog("pn_do_transaction completed.");
    die();

} else {
    // Probably calling the "redirect" URL

    pnlog(__FILE__ . ' Probably calling the "redirect" URL');

    if( $url_for_redirect ) {
        header ( "Location: {$url_for_redirect}" );
    } else {
        pnHalt( "No 'redirect' URL set." );
    }
}
