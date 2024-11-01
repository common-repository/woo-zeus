<?php
/**
 * Plugin Name: WooCommerce For Zeus Payments
 * Plugin URI: http://wordpress.org/plugins/woo-zeus/
 * Description: Woocommerce Zeus payment 
 * Version: 0.9.4
 * Author: Artisan Workshop
 * Author URI: http://wc.artws.info/
 * Requires at least: 4.0
 * Tested up to: 4.3
 *
 * Text Domain: woo-zeus
 * Domain Path: /i18n/
 *
 * @package woo-zeus
 * @category Core
 * @author Artisan Workshop
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Load plugin functions.
 */
add_action( 'plugins_loaded', 'wc4jp_zeus_plugin', 0 );

add_action('woocommerce_before_cart', 'zeus_recieved_func');

if ( ! class_exists( 'WC_Zeus' ) ) :

class WC_Zeus{

	/**
	 * WooCommerce Constructor.
	 * @access public
	 * @return WooCommerce
	 */
	public function __construct() {
		// Include required files
		$this->includes();
		$this->init();
	}
	/**
	 * Include required core files used in admin and on the frontend.
	 */
	private function includes() {
		// Module
		define('WC_ZEUS_PLUGIN_PATH',plugin_dir_path( __FILE__ ));
		define('WC_ZEUS_CC_API_URL','https://linkpt.cardservice.co.jp/cgi-bin/secure/api.cgi');
		define('WC_ZEUS_SECURE_API_URL','https://linkpt.cardservice.co.jp/cgi-bin/secure.cgi');
		define('WC_ZEUS_CS_URL','https://linkpt.cardservice.co.jp/cgi-bin/cvs.cgi');
		define('WC_ZEUS_CP_URL','https://linkpt.cardservice.co.jp/cgi-bin/carrier/order.cgi');
		define('WC_ZEUS_BT_URL','https://linkpt.cardservice.co.jp/cgi-bin/ebank.cgi');

		// Zeus Payment Gateway
		if(get_option('wc-zeus-cc')) {
			include_once( plugin_dir_path( __FILE__ ).'/includes/gateways/zeus/class-wc-gateway-zeus-cc.php' );	// Credit Card
			include_once( plugin_dir_path( __FILE__ ).'/includes/gateways/zeus/class-wc-addons-gateway-zeus-cc.php' );	// Credit Card Subscriptions
		}
		if(get_option('wc-zeus-cs')) include_once( plugin_dir_path( __FILE__ ).'/includes/gateways/zeus/class-wc-gateway-zeus-cs.php' );	// Convenience store
		if(get_option('wc-zeus-bt')) include_once( plugin_dir_path( __FILE__ ).'/includes/gateways/zeus/class-wc-gateway-zeus-bt.php' );	// Entrusted payment
		if(get_option('wc-zeus-pe')) include_once( plugin_dir_path( __FILE__ ).'/includes/gateways/zeus/class-wc-gateway-zeus-pe.php' );	// Pay-easy
		if(get_option('wc-zeus-cp')) include_once( plugin_dir_path( __FILE__ ).'/includes/gateways/zeus/class-wc-gateway-zeus-cp.php' );	// Carrier payment

		// Admin Setting Screen 
		include_once( plugin_dir_path( __FILE__ ).'/includes/class-wc-admin-screen-zeus.php' );
	}
	/**
	 * Init WooCommerce when WordPress Initialises.
	 */
	public function init() {
		// Set up localisation
		$this->load_plugin_textdomain();
	}

	/*
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woo-zeus' );
		// Global + Frontend Locale
		load_plugin_textdomain( 'woo-zeus', false, plugin_basename( dirname( __FILE__ ) ) . "/i18n" );
	}
}

endif;

//Zeus recieved from CGI

function zeus_recieved_func(){
	global $woocommerce;
	global $wpdb;

	if(!$_GET){
	}else{
	$woocommerce_zeus_cs = get_option('woocommerce_zeus_cs_settings');
	$woocommerce_zeus_bt = get_option('woocommerce_zeus_bt_settings');
	$woocommerce_zeus_pe = get_option('woocommerce_zeus_pe_settings');
	$woocommerce_zeus_cp = get_option('woocommerce_zeus_cp_settings');
	$clientid_array = array(
		'cs' => $woocommerce_zeus_cs['authentication_clientip'],
		'bt' => $woocommerce_zeus_bt['authentication_clientip'],
		'pe' => $woocommerce_zeus_pe['authentication_clientip'],
		'cp' => $woocommerce_zeus_cp['authentication_clientip'],
	);
			$get_order_id = substr($_GET['sendpoint'],3);
			$order = new WC_Order( $get_order_id );
			$order_status = $order->status;
		if(isset($_GET['clientid']) and $_GET['clientid'] == $clientid_array['cs']){
			//Convenience Store Payments
			if($order_status == 'pending' and $_GET['status'] == '01'){//Finish process
				$order->update_status( 'on-hold' );
			}elseif($order_status == 'on-hold' and $_GET['status'] == '04'){//Finish payment
				$order->update_status( 'processing' );
			}elseif($order_status == 'processing' and $_GET['status'] == '06'){//Cancelled
				$order->update_status( 'cancelled' );
			}elseif($order_status == 'processing' and $_GET['status'] == '05'){//Finish sales
				$order->update_status( 'completed' );
			}
		}elseif(isset($_GET['clientid']) and $_GET['clientid'] == $clientid_array['bt']){
			//Bank transfer
			if($order_status == 'pending' and $_GET['status'] == '02'){//Finish process
				$order->update_status( 'on-hold' );
			}elseif($order_status == 'on-hold' and $_GET['status'] == '03'){//Finish payment
				$order->update_status( 'processing' );
			}
		}elseif(isset($_GET['clientid']) and $_GET['clientid'] == $clientid_array['pe']){
			//Pay-easy
			if($order_status == 'pending' and $_GET['status'] == '01'){//Finish process
				$order->update_status( 'on-hold' );
			}elseif($order_status == 'on-hold' and $_GET['status'] == '04'){
				$order->update_status( 'processing' );
			}elseif($order_status == 'processing' and $_GET['status'] == '05'){
				$order->update_status( 'completed' );
			}
		}elseif(isset($_GET['clientid']) and $_GET['clientid'] == $clientid_array['cp']){
			//Carrier
			if($order_status == 'pending' and $_GET['status'] == '02'){//failed
				$order->update_status( 'failed' );
			}elseif($order_status == 'on-hold' and $_GET['status'] == '03'){//Finish pre payment
				$order->update_status( 'processing' );
			}elseif($_GET['status'] == '07'){//Canselled{
				$order->update_status( 'cancelled' );
			}
		}
	}
}

//If WooCommerce Plugins is not activate notice

	function wc4jp_zeus_fallback_notice(){
	?>
    <div class="error">
        <ul>
            <li><?php echo __( 'WooCommerce for Zeus Payment is enabled but not effective. It requires WooCommerce in order to work.', 'woo-zeus' );?></li>
        </ul>
    </div>
    <?php
}
function wc4jp_zeus_plugin() {
    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        $wc_zeus = new WC_Zeus();
    } else {
        add_action( 'admin_notices', 'wc4jp_zeus_fallback_notice' );
    }
}
