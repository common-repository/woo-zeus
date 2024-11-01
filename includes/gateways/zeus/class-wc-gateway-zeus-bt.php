<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Zeus Payment Gateway
 *
 * Provides a Zeus Bank transfer Payment Gateway.
 *
 * @class 		WC_Zeus
 * @extends		WC_Gateway_Zeus_ENT
 * @version		0.9.0
 * @package		WooCommerce/Classes/Payment
 * @author		Artisan Workshop
 */
class WC_Gateway_Zeus_BT extends WC_Payment_Gateway {


	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id                = 'zeus_bt';
		$this->has_fields        = false;
		$this->method_title      = __( 'Zeus Bank transfer Payment', 'woo-zeus' );
		
        // Create plugin fields and settings
		$this->init_form_fields();
		$this->init_settings();
		$this->method_title       = __( 'Zeus Bank transfer Payment Gateway', 'woo-zeus' );
		$this->method_description = __( 'Allows payments by Zeus Bank transfer Payment in Japan.', 'woo-zeus' );

		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

		// Actions
		add_action( 'woocommerce_receipt_zeus_bt',                              array( $this, 'receipt_page' ) );
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
				'label'       => __( 'Enable Zeus Bank transfer Payment', 'woo-zeus' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title'           => array(
				'title'       => __( 'Title', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woo-zeus' ),
				'default'     => __( 'Bank transfer Payment (Zeus)', 'woo-zeus' )
			),
			'description'     => array(
				'title'       => __( 'Description', 'woo-zeus' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woo-zeus' ),
				'default'     => __( 'Pay with Bank transfer Payment via Zeus.', 'woo-zeus' )
			),
			'order_button_text'      => array(
				'title'       => __( 'Order Button Text', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'This controls the proceed button during checkout.', 'woo-zeus' ),
				'default'     => __( 'Proceed to Zeus Bank transfer Payment', 'woo-zeus' )
			),
			'authentication_clientip' => array(
				'title'       => __( 'Authentication Client IP', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'Enter Authentication Client IP.', 'woo-zeus' ),
			),
			'test_mode'       => array(
				'title'       => __( 'Test Mode', 'woo-zeus' ),
				'type'        => 'checkbox',
				'description' => __('If you want to use test mode, please check it.', 'woo-zeus' ),
				'default'     => 'yes'
			),
			'test_id'         => array(
				'title'       => __( 'Test card ID', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'If you want to use test mode, please input Test Card ID from Zeus Admin Site.', 'woo-zeus' ),
				'default'     => ''
			),
		);
	}

	/**
	 * Process the payment and return the result.
	 */
	function process_payment( $order_id ) {

		global $woocommerce;
		global $wpdb;

		$order = new WC_Order( $order_id );
		$user = wp_get_current_user();
		if($order->user_id){
			$customer_id   = $user->ID;
		}else{
			$customer_id   = $order->id.'-user';
		}
		$connect_url = WC_ZEUS_BT_URL;
		$post_data = array();
		$post_data['clientip'] = $this->authentication_clientip;
		$post_data['act'] = 'order';
		// Set send data for Zeus
		$post_data['money'] = $order->order_total;
		$post_data['username'] = mb_convert_kana($this->get_post( 'billing_yomigana_last_name' ), "KVC").mb_convert_kana($this->get_post( 'billing_yomigana_first_name' ), "KVC");
		if(!$post_data['username']){
			$post_data['username'] = mb_convert_kana($order->billing_yomigana_last_name, "KVC").mb_convert_kana($order->billing_yomigana_first_name, "KVC");
		}
		$post_data['username'] = mb_convert_encoding($post_data['username'],'SJIS', 'auto');
		if($this->test_mode == 'yes'){
			$post_data['username'] = $post_data['username'].'_'.$this->test_id;
		}
		$post_data['telno'] = str_replace('-','',$order->billing_phone);
		$post_data['email'] = $order->billing_email;
		$post_data['sendpoint'] = 'wc-'.$order->id;
		$post_data['sendid'] = $user->ID;
		$post_data['siteurl'] = esc_url( home_url( '/' ) );
		$post_data['sitestr'] =  mb_convert_encoding(__( 'Back to Store', 'woo-zeus' ), "SJIS", "auto");

		//Note for Message
		$message = ', '.__( 'Authorization number :', 'woo-zeus' ).$response_array['pay_no2'].', '.__( 'Pay limit :', 'woo-zeus' ).$response_array[ 'pay_limit' ];
		// Mark as pending (we're awaiting the payment)
		$order->update_status( 'pending', __( 'Awaiting Bank transfer payment', 'woo-zeus' ).$message );

		//set transaction id for Zeus Order Number
		update_post_meta( $order->id, '_transaction_id', wc_clean( $response_array[ 'order_no' ] ) );

		// Reduce stock levels
		$order->reduce_order_stock();

		// Remove cart
		WC()->cart->empty_cart();
		return array(
			'result'   => 'success',
			'redirect' => $this->get_zeus_url( $post_data ,$connect_url)
		);
	}


	function receipt_page( $order ) {
		echo '<p>' . __( 'Thank you for your order.', 'woo-zeus' ) . '</p>';
	}

	/**
	 * Get Zeus url
	 */
	private function get_zeus_url( $post_data , $connect_url) {
		$url = $connect_url;
		$url .= '?'.http_build_query($post_data);
		return $url;
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
	function add_wc_zeus_bt_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Zeus_BT';
		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'add_wc_zeus_bt_gateway' );

	/**
	 * Edit the available gateway to woocommerce
	 */
	function edit_zeus_bt_available_gateways( $methods ) {
		if ( isset($currency) ) {
		}else{
			$currency = get_woocommerce_currency();
		}
		if($currency !='JPY'){
		unset($methods['zeus_bt']);
		}
		return $methods;
	}

	add_filter( 'woocommerce_available_payment_gateways', 'edit_zeus_bt_available_gateways' );
