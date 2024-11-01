<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Zeus Payment Gateway
 *
 * Provides a Zeus Pay-easy Payment Gateway.
 *
 * @class 		WC_Zeus
 * @extends		WC_Gateway_Zeus_PE
 * @version		0.9.0
 * @package		WooCommerce/Classes/Payment
 * @author		Artisan Workshop
 */
class WC_Gateway_Zeus_PE extends WC_Payment_Gateway {


	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id                = 'zeus_pe';
		$this->has_fields        = false;
		$this->method_title      = __( 'Zeus Pay-easy Payment', 'woo-zeus' );
		
        // Create plugin fields and settings
		$this->init_form_fields();
		$this->init_settings();
		$this->method_title       = __( 'Zeus Pay-easy Payment Gateway', 'woo-zeus' );
		$this->method_description = __( 'Allows payments by Zeus Pay-easy Payment in Japan.', 'woo-zeus' );

		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

		// Actions
		add_action( 'woocommerce_receipt_zeus_pe',                              array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways',              array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
//		add_action( 'wp_enqueue_scripts',                                       array( $this, 'add_zeus_cs_scripts' ) );
	}

	/**
	* Initialize Gateway Settings Form Fields.
	*/
	function init_form_fields() {

		$this->form_fields = array(
			'enabled'     => array(
				'title'       => __( 'Enable/Disable', 'woo-zeus' ),
				'label'       => __( 'Enable Zeus Pay-easy Payment', 'woo-zeus' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title'       => array(
				'title'       => __( 'Title', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woo-zeus' ),
				'default'     => __( 'Pay-easy Payment (Zeus)', 'woo-zeus' )
			),
			'description' => array(
				'title'       => __( 'Description', 'woo-zeus' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woo-zeus' ),
				'default'     => __( 'Pay with Pay-easy Payment via Zeus.', 'woo-zeus' )
			),
			'order_button_text'       => array(
				'title'       => __( 'Order Button Text', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'This controls the proceed button during checkout.', 'woo-zeus' ),
				'default'     => __( 'Proceed to Zeus Pay-easy Payment', 'woo-zeus' )
			),
			'authentication_clientip' => array(
				'title'       => __( 'Authentication Client IP', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'Enter Authentication Client IP.', 'woo-zeus' ),
			),
			'test_mode' => array(
				'title'       => __( 'Test Mode', 'woo-zeus' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Test Mode', 'woo-zeus' ),
				'default'     => 'no',
				'description' => __( 'Please check it when you want to use test-mode.', 'woo-zeus' ),
			),
			'testcard_id' => array(
				'title'       => __( 'Test Card ID', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'If you want to use test mode, please input Test Card ID from Zeus Admin Site.', 'woo-zeus' ),
			),
		);
	}

	/**
	 * Process the payment and return the result.
	 */
	function process_payment( $order_id ) {
		include_once( 'includes/class-wc-gateway-zeus-request.php' );

		global $woocommerce;
		global $wpdb;

		$order = new WC_Order( $order_id );
		$user = wp_get_current_user();
		if($order->user_id){
			$customer_id   = $user->ID;
		}else{
			$customer_id   = $order->id.'-user';
		}
		$connect_url = WC_ZEUS_CS_URL;
		$post_data = array();
		$post_data['clientip'] = $this->authentication_clientip;
		$post_data['act'] = 'secure_order';
		$post_data['pay_cvs'] = '0093';// Pay Easy Code
		$post_data['username'] = mb_convert_kana($this->get_post( 'billing_yomigana_last_name' ), "KVC").mb_convert_kana($this->get_post( 'billing_yomigana_first_name' ), "KVC");
		// Set send data for Zeus
		$post_data['sendid'] = $user->ID;

		//Test mode Setting
		if(isset($this->test_mode) and $this->test_mode == 'yes'){
			$post_data['testid'] = $this->testcard_id;
			$post_data['test_type'] = 1;
		}

		$zeus_request = new WC_Gateway_Zeus_Request( $this );
		$response_array = $zeus_request->send_zeus_request( $post_data, $order, $connect_url);
		if(isset($response_array['error_msg'])){
			wc_add_notice( $response_array['error_msg'], $notice_type = 'error' );
		}elseif($response_array['rel']=='failure_order'){
			wc_add_notice( $response_array['error_code'], $notice_type = 'error' );
		}elseif($response_array['rel']=='Success_order'){
			//Note for Message
			$message = ', '.__( 'Authorization number :', 'woo-zeus' ).$response_array['pay_no2'].', '.__( 'Pay limit :', 'woo-zeus' ).$response_array[ 'pay_limit' ];
			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'on-hold', __( 'Awaiting Pay-easy payment', 'woo-zeus' ).$message );
			
			update_post_meta( $order->id, '_pay_no2', wc_clean( $response_array[ 'pay_no2' ] ) );
			update_post_meta( $order->id, '_pay_limit', wc_clean( $response_array[ 'pay_limit' ] ) );
			//set transaction id for Zeus Order Number
			update_post_meta( $order->id, '_transaction_id', wc_clean( $response_array[ 'order_no' ] ) );

			// Reduce stock levels
			$order->reduce_order_stock();

			// Remove cart
			WC()->cart->empty_cart();
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		}
	}


	function receipt_page( $order ) {
		echo '<p>' . __( 'Thank you for your order.', 'woo-zeus' ) . '</p>';
	}

	/**
	 * Get post data if set
	 */
	private function get_post( $name ) {
		if ( isset( $_POST[ $name ] ) ) {
			return $_POST[ $name ];
		}
		return null;
	}

}

/**
 * Add the gateway to woocommerce
 */
function add_wc_zeus_pe_gateway( $methods ) {
	$methods[] = 'WC_Gateway_Zeus_PE';
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_wc_zeus_pe_gateway' );

/**
 * Edit the available gateway to woocommerce
 */
function edit_zeus_pe_available_gateways( $methods ) {
	if ( isset($currency) ) {
	}else{
		$currency = get_woocommerce_currency();
	}
	if($currency !='JPY'){
	unset($methods['zeus_pe']);
	}
	return $methods;
}

add_filter( 'woocommerce_available_payment_gateways', 'edit_zeus_pe_available_gateways' );
/**
 * Get Payeasy Payment details and place into a list format
 */
function zeus_pe_detail( $order ){
	global $woocommerce;

	$pay_limit = get_post_meta( $order->id, '_pay_limit', true);
	$pay_no2 = get_post_meta( $order->id, '_pay_no2', true);

	if( get_post_meta( $order->id, '_payment_method', true ) == 'zeus_pe' ){
		echo '<header class="title"><h3>'.__('Payment Detail', 'woo-zeus').'</h3></header>';
		echo '<table class="shop_table order_details">';
		echo '<tr><th>'.__( 'Authorization number', 'woo-zeus' ).'</th><td>'.$pay_no2.'</td></tr>'.PHP_EOL;
		echo '<tr><th>'.__('Payment limit term', 'woo-zeus').'</th><td>'.$pay_limit.'</td></tr>'.PHP_EOL;
		echo '</table>';
	}
}
add_action( 'woocommerce_order_details_after_order_table', 'zeus_pe_detail', 10, 1);
