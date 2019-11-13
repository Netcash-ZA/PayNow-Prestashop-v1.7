<?php
/**
 * Copyright 2019 Netcash (Pty) Ltd
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

include 'paynow_common.inc.php';

if( !defined( '_PS_VERSION_' ) )
    exit;

class PayNow extends PaymentModule
{
    const LEFT_COLUMN = 0;
    const RIGHT_COLUMN = 1;
    const FOOTER = 2;
    const DISABLE = -1;

    public function __construct()
    {
        $this->name = 'paynow';
        $this->tab = 'payments_gateways';
        $this->version = PN_MODULE_VER;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->author  = 'PayNow';
        $this->page = basename(__FILE__, '.php');

        $this->displayName = $this->l( 'Netcash Pay Now' );
        $this->description = $this->l('Securely accept payments by credit card, EFT, and more with Netcash Pay Now.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your data?');

    }

    public function install()
    {
        if( !parent::install()
            OR !$this->registerHook('paymentOptions')
            OR !$this->registerHook('paymentReturn')
            OR !Configuration::updateValue('PAYNOW_SERVICE_KEY', '')
            OR !Configuration::updateValue('PAYNOW_ACCOUNT_NUMBER', '')
            OR !Configuration::updateValue('PAYNOW_ENABLE_LOGS', '1')
            OR !Configuration::updateValue('PAYNOW_MODE', 'test')
            OR !Configuration::updateValue('NETCASH_PAYNOW_TEXT', 'Pay Now using')
            OR !Configuration::updateValue('NETCASH_PAYNOW_LOGO', 'on')
            OR !Configuration::updateValue('NETCASH_PAYNOW_ALIGN', 'right')
            )
        {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        return ( parent::uninstall()
            AND Configuration::deleteByName('PAYNOW_SERVICE_KEY')
            AND Configuration::deleteByName('PAYNOW_ACCOUNT_NUMBER')
            AND Configuration::deleteByName('PAYNOW_MODE')
            AND Configuration::deleteByName('PAYNOW_ENABLE_LOGS')
            AND Configuration::deleteByName('NETCASH_PAYNOW_TEXT')
            AND Configuration::deleteByName('NETCASH_PAYNOW_LOGO')
            AND Configuration::deleteByName('NETCASH_PAYNOW_ALIGN')
            );

    }

    public function getContent()
    {
        global $cookie;
        $errors = array();
        $html = '
        <div id="paynow__content">
        <div id="content" class="config__paynow">
        <div class="paynow__header">
            <div class="col"><a href="https://netcash.co.za/" target="_blank">
                <img class="paynow__logo" src="'.__PS_BASE_URI__.'modules/paynow/paynow.png" alt="Netcash Pay Now" style="max-width:150px;" boreder="0" />
            </a></div>
            <div class="col col-b"><span>Secure Payments with Netcash Pay Now</span></div>
        </div>
        <div class="divider divider__longer"></div>';


        /* Update configuration variables */
        if( Tools::isSubmit( 'submitPayNow' ) )
        {
            if( $paynow_text =  Tools::getValue( 'netcash_paynow_text' ) )
            {
                 Configuration::updateValue( 'NETCASH_PAYNOW_TEXT', $paynow_text );
            }

            if( $paynow_logo =  Tools::getValue( 'netcash_paynow_logo' ) )
            {
                 Configuration::updateValue( 'NETCASH_PAYNOW_LOGO', $paynow_logo );
            }
            if( $paynow_align =  Tools::getValue( 'netcash_paynow_align' ) )
            {
                 Configuration::updateValue( 'NETCASH_PAYNOW_ALIGN', $paynow_align );
            }


            $mode = Tools::getValue( 'paynow_mode' );
            Configuration::updateValue( 'PAYNOW_MODE', $mode );

            $account_number = Tools::getValue( 'paynow_account_number' );
            $service_key = Tools::getValue( 'paynow_service_key' );

            $serviceKeyErrors = [];
            if( $account_number && $service_key ) {
                if(class_exists('SoapClient')) {
                    // We can continue, SOAP is installed

                    require_once(dirname(__FILE__).'/PayNowValidator.php');
                    $Validator = new Netcash\PayNowValidator();
                    $Validator->setVendorKey('94cdf2e6-f2e7-4c91-ad34-da5684bfbd6f');

                    try {
                        $result = $Validator->validate_paynow_service_key($account_number, $service_key);

                        if( $result !== true ) {
                            $serviceKeyErrors[] = (isset($result[$service_key]) ? $result[$service_key] : '<strong>Account Number:</strong> ' . $result) . ' ';
                            $serviceKeyErrors[] = (isset($result[$service_key]) ? $result[$service_key] : '<strong>Service Key</strong> could not be validated.') . ' ';
                        } else {

                            // Success
                            Configuration::updateValue( 'PAYNOW_ACCOUNT_NUMBER', $account_number );
                            Configuration::updateValue( 'PAYNOW_SERVICE_KEY', $service_key );

                        }
                    } catch(\Exception $e) {
                        $serviceKeyErrors[] = $e->getMessage() . ' ';
                    }
                } else {
                    $serviceKeyErrors[] = 'Cannot validate. Please install the PHP SOAP extension.';
                }
            } else {
                $serviceKeyErrors[] = 'Please specify an account number and service key.</div>';
            }

            if(!empty($serviceKeyErrors)) {
                $serviceKeyErrors[] = "Please contact your Netcash Account manager on 0861 338 338 for assistance.";
                foreach ($serviceKeyErrors as $error) {
                    $errors[] = "<div class='warning warn'>{$error}</div>";
                }
            }


            $paynow_enable_logs = Tools::getValue( 'paynow_enable_logs');
            Configuration::updateValue( 'PAYNOW_ENABLE_LOGS', $paynow_enable_logs );

            foreach( array('displayLeftColumn', 'displayRightColumn', 'displayFooter') as $hookName )
                if ( $this->isRegisteredInHook($hookName) )
                    $this->unregisterHook($hookName);
            if ( Tools::getValue('logo_position') == self::LEFT_COLUMN )
                $this->registerHook('displayLeftColumn');
            else if ( Tools::getValue('logo_position') == self::RIGHT_COLUMN )
                $this->registerHook('displayRightColumn');
             else if ( Tools::getValue('logo_position') == self::FOOTER )
                $this->registerHook('displayFooter');
            if( method_exists ('Tools','clearSmartyCache') )
            {
                Tools::clearSmartyCache();
            }

        }



        /* Display errors */
        if( sizeof($errors) )
        {
            $html .= '<ul style="color: red; font-weight: bold; width: 100%; background: #FFDFDF; ">';
            foreach ( $errors AS $error )
                $html .= '<li> '.$error.'</li>';
            $html .= '</ul>';
        }



        $blockPositionList = array(
            self::DISABLE => $this->l('Disable'),
            self::LEFT_COLUMN => $this->l('Left Column'),
            self::RIGHT_COLUMN => $this->l('Right Column'),
            self::FOOTER => $this->l('Footer'));

        if( $this->isRegisteredInHook('displayLeftColumn') )
        {
            $currentLogoBlockPosition = self::LEFT_COLUMN ;
        }
        elseif( $this->isRegisteredInHook('displayRightColumn') )
        {
            $currentLogoBlockPosition = self::RIGHT_COLUMN;
        }
        elseif( $this->isRegisteredInHook('displayFooter'))
        {
            $currentLogoBlockPosition = self::FOOTER;
        }
        else
        {
            $currentLogoBlockPosition = -1;
        }


    /* Display settings form */
        $html .= '
        <head>
            <link href="' .__PS_BASE_URI__.'modules/paynow/paynow_styles.css" rel=\'stylesheet\' type=\'text/css\' />
            <script src="' .__PS_BASE_URI__.'modules/paynow/paynow_validate.js" ></script>
        </head>
        <form action="'.$_SERVER['REQUEST_URI'].'" method="post">
          <div class="paynow__main--section" id="main__section">
          <span class="main__section--header">Netcash Pay Now Settings:</span>

              <div class="merchant__details merchant__config">
                 <div class="account__details">
                    <span class="merchant__headers">
                        '.$this->l('Pay Now Account Number').'
                    </span>
                    <input class="merchant__input" type="text" step="0" min="0" name="paynow_account_number" placeholder="" value="'.Tools::getValue('paynow_account_number', Configuration::get('PAYNOW_ACCOUNT_NUMBER')).'" />

                 </div>
             </div>

            <div class="divider"></div>

              <div class="merchant__details merchant__config">
                 <div class="account__details">
                    <span class="merchant__headers">
                        '.$this->l('Pay Now Service Key').'
                    </span>
                    <input class="merchant__input" type="text" step="0" min="0" name="paynow_service_key" placeholder="" value="'.Tools::getValue('paynow_service_key', Configuration::get('PAYNOW_SERVICE_KEY')).'" />

                 </div>
             <p class="additional__info additional__info--smaller">'.$this->l('You can find your Service Key in your ').'<a id="paynow__link" href="https://netcash.co.za/">'.
            $this->l('Netcash Pay Now').'</a>'.$this->l(' account.').'</p>
             </div>

            <div class="divider"></div>


             <div class="merchant__details merchant__config">
                <div class="account__details">
                    <span class="merchant__headers">
                     '.$this->l('Enable Debugging:').'
                    </span>
                <div class="paynow__selector debug__selector">
                    <span class="merchant__headers">
                    '.$this->l('Enable').'
                    </span>
                    <input type="radio" name="paynow_enable_logs"  value="1" '.(empty(Tools::getValue('paynow_enable_logs', Configuration::get('PAYNOW_ENABLE_LOGS'))) ? '' : ' checked').' />
                    <span class="merchant__headers">
                    '.$this->l('Disable').'
                    </span>
                    <input type="radio" name="paynow_enable_logs"  value="" '.(empty(Tools::getValue('paynow_enable_logs', Configuration::get('PAYNOW_ENABLE_LOGS'))) ? ' checked' : '').' />
                 </div>
            </div>
                 <p class="additional__info additional__info--taller">'.$this->l('Enable Debug to log the server-to-server communication. The log file for debugging can be found at ').' <code>'.__PS_BASE_URI__.'modules/paynow/paynow.log</code></p>
            </div>

            <div class="divider"></div>

            <div class="merchant__details merchant__config preview__section">
                <p class="additional__info additional__info--taller">'.$this->l('The following payment option text is displayed during checkout.').'</p>';

                //Pay now text field
                $html .= '<div class="account__details"><span class="merchant__headers">
                    '.$this->l('Payment option text').'
                  </span>

                  <input  class="merchant__input"   type="text" name="netcash_paynow_text" value="'. Configuration::get('NETCASH_PAYNOW_TEXT').'">
                  ';

                //Pay Now text preview.
                $html .= '<span class="merchant__headers preview__header">Preview</span>
                  <div>
                    '.Configuration::get('NETCASH_PAYNOW_TEXT') .
                    '&nbsp&nbsp<img alt="Pay Now" title="Pay Now" src="'.__PS_BASE_URI__.'modules/paynow/logo.png">
                  </div>
               </div>
            </div>

            <div class="divider"></div>';

        //image position field
//        $html .= '<div class="merchant__details merchant__config preview__section"><p class="additional__info additional__info--taller">'.$this->l('Select the position where the "Payments by Netcash Pay Now" image will appear on your website. This will be dependant on your theme.').'</p>
//
//            <div class="account__details">
//            <span>
//            '.$this->l('Image position').'
//            </span>
//
//            <select class="paynow__dropdown" id="box" name="logo_position" >';
//                foreach($blockPositionList as $position => $translation)
//                {
//                    $selected = ($currentLogoBlockPosition == $position) ? 'selected="selected"' : '';
//                    $html .= '<option value="'.$position.'" '.$selected.'>'.$translation.'</option>';
//                }
//                $html .='
//            </select>
//          </div>
//        </div>
//	    <div>';
    $html .= '<div class="divider"></div>
    <div>
        <button type="submit" name="submitPayNow" class="button" id="paynow__button" value="Save">Save Changes</button>
        <div id="paynowDetailsError" style="display:none;color:red"></div>
    </div>
    <div class="clear"></div>
    </div>
    </form>
 </div>
 <!--div class="divider divider__longer"></div>
      <div class="paynow__form--footer">
      <span class="footer__header">'.$this->l('Additional Information:').'</span>
      <div class="footer__info">
      <span class="footer__info--para">- '.$this->l('Any orders in currencies other than ZAR will be converted by PrestaShop prior to be sent to the Netcash Pay Now gateway.').'</span>
        </div>
    </div-->

</div>
</div>
</div>';

        return $html;
    }

    private function _displayLogoBlock( $position )
    {
        $html = '
            <div style="text-align:center;">
                <a href="https://netcash.co.za/" target="_blank" title="Payments via Netcash Pay Now">
                    <img src="'.__PS_BASE_URI__.'modules/paynow/secure_logo.png" width="150" />
                </a>
            </div>';

        return $html;
    }

    public function hookDisplayRightColumn( $params )
    {
        return $this->_displayLogoBlock(self::RIGHT_COLUMN);
    }

    public function hookDisplayLeftColumn( $params )
    {
        return $this->_displayLogoBlock(self::LEFT_COLUMN);
    }

    public function hookDisplayFooter( $params )
    {
        $html = '
        <section id="paynow_footer_link" class="footer-block col-xs-12 col-sm-2">
            <div style="text-align:center;">
                <a href="https://netcash.co.za/" rel="nofollow" title="Secure Payments With Netcash Pay Now">
                    <img src="'.__PS_BASE_URI__.'modules/paynow/secure_logo.png"  />
                </a>
            </div>
        </section>';
        return $html;
    }

    //new method
    public function hookPaymentOptions( $params )
    {
        if( !$this->active )
        {
            return;
        }
        $payment_options = array(
            $this->getCardPaymentOption()
        );

        return $payment_options;

    }

    public function getCardPaymentOption()
    {
        global $cookie, $cart;

        // Buyer details
        $customer = new Customer((int)($cart->id_customer));

        $toCurrency = new Currency(Currency::getIdByIsoCode('ZAR'));
        $fromCurrency = new Currency((int)$cookie->id_currency);

        $total = $cart->getOrderTotal();

        $pnAmount = Tools::convertPriceFull( $total, $fromCurrency, $toCurrency );

        $data = array();

        $currency = $this->getCurrency((int)$cart->id_currency);
        if( $cart->id_currency != $currency->id )
        {
            // For when currency differs from local currency
            $cart->id_currency = (int)$currency->id;
            $cookie->id_currency = (int)$cart->id_currency;
            $cart->update();
        }

        // Use appropriate merchant identifiers
        $service_key = Configuration::get('PAYNOW_SERVICE_KEY');

        // Live

        $software_vendor_key = 'de2c157a-04fb-4cca-beb5-8aa20f686ac6';
        $data['info']['m1'] = $service_key;
        $data['info']['m2'] = $software_vendor_key;

        $data['paynow_url'] = 'https://paynow.netcash.co.za/site/paynow.aspx';

        $data['netcash_paynow_text'] = Configuration::get('NETCASH_PAYNOW_TEXT');
        $data['netcash_paynow_logo'] = Configuration::get('NETCASH_PAYNOW_LOGO');
        $data['netcash_paynow_align'] = Configuration::get('NETCASH_PAYNOW_ALIGN');
        // Create URLs
        $data['info']['return_url'] = $this->context->link->getPageLink( 'order-confirmation', null, null, 'key='.$cart->secure_key.'&id_cart='.(int)($cart->id).'&id_module='.(int)($this->id));
        $data['info']['cancel_url'] = Tools::getHttpHost( true ).__PS_BASE_URI__;
        $data['info']['notify_url'] = Tools::getHttpHost( true ).__PS_BASE_URI__.'modules/paynow/paynow_callback.php';

	    $data['info']['p2'] = $cart->id . "_" . date("Ymds");

	    $data['info']['p3'] = Configuration::get('PS_SHOP_NAME') . " Payment for Cart: {$cart->id}";
	    $data['info']['p4'] = number_format( sprintf( "%01.2f", $pnAmount ), 2, '.', '' );

	    // Extra1
//	    $data['info']['m4'] = "{$cart->id_customer}";
	    $data['info']['m4'] = "{$pnAmount}";

	    // Cart Id sent as Extra2
	    $data['info']['m5'] = $cart->id;
	    // Secure key (Returned as Extra3)
	    $data['info']['m6'] = $cart->secure_key;

	    $data['info']['m9'] = $customer->email;

	    // Custom data
	    $return_vars = 'key='.$cart->secure_key.'&id_cart='.(int)($cart->id).'&id_module='.(int)($this->id);
	    $data['info']['m10'] = $return_vars;
	    $data['info']['m14'] = 1;

        $outputHtml = '';

	    $pnValues = array();
        foreach( ($data['info']) as $key => $val ) {
	        $outputHtml .= $key . '=' . urlencode( trim( $val ) ) . '&';

	        $pnValues[$key] = array(
		        'name' => $key,
		        'type' => 'hidden',
		        'value' => $val,
	        );
        }

        //create the payment option object
        $externalOption = new PaymentOption();
        $externalOption->setCallToActionText($this->l(Configuration::get('NETCASH_PAYNOW_TEXT')))
                       ->setAction($data['paynow_url']) //
                       ->setInputs($pnValues)
                       ->setAdditionalInformation($this->context->smarty->fetch('module:paynow/payment_info.tpl'))
                       ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'));

        return $externalOption;
    }

    public function hookPaymentReturn( $params )
    {
        if (!$this->active)
        {
            return;
        }
        $test = __FILE__;

        return $this->display($test, 'paynow_success.tpl');
    }

}