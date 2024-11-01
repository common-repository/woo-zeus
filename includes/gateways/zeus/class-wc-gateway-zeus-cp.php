<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Zeus Payment Gateway
 *
 * Provides a Zeus Carrier Payment Gateway.
 *
 * @class 		WC_Zeus
 * @extends		WC_Gateway_Zeus_CP
 * @version		0.9.0
 * @package		WooCommerce/Classes/Payment
 * @author		Artisan Workshop
 */
class WC_Gateway_Zeus_CP extends WC_Payment_Gateway {


	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id                = 'zeus_cp';
		$this->has_fields        = false;
		$this->method_title      = __( 'Zeus Carrier Payment', 'woo-zeus' );
		
        // Create plugin fields and settings
		$this->init_form_fields();
		$this->init_settings();
		$this->method_title       = __( 'Zeus Carrier Payment Gateway', 'woo-zeus' );
		$this->method_description = __( 'Allows payments by Zeus Carrier Payment in Japan.', 'woo-zeus' );

		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

		// Actions
		add_action( 'woocommerce_receipt_zeus_cp',                              array( $this, 'receipt_page' ) );
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
				'label'       => __( 'Enable Zeus Carrier Payment', 'woo-zeus' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title'       => array(
				'title'       => __( 'Title', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woo-zeus' ),
				'default'     => __( 'Carrier Payment (Zeus)', 'woo-zeus' )
			),
			'description' => array(
				'title'       => __( 'Description', 'woo-zeus' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woo-zeus' ),
				'default'     => __( 'Pay with Carrier Payment via Zeus.', 'woo-zeus' )
			),
			'order_button_text'       => array(
				'title'       => __( 'Order Button Text', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'This controls the proceed button during checkout.', 'woo-zeus' ),
				'default'     => __( 'Proceed to Zeus Carrier Payment', 'woo-zeus' )
			),
			'authentication_clientip' => array(
				'title'       => __( 'Authentication Client IP', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'Enter Authentication Client IP.', 'woo-zeus' ),
			),
			'setting_carrier_dm' => array(
				'title'       => __( 'Set Carrier', 'woo-zeus' ),
				'id'              => 'wc-zeus-cp-dm',
				'type'        => 'checkbox',
				'label'       => __( 'docomo', 'woo-zeus' ),
				'default'     => 'yes',
			),
			'setting_carrier_sb' => array(
				'id'              => 'wc-zeus-cp-sb',
				'type'        => 'checkbox',
				'label'       => __( 'SoftBank', 'woo-zeus' ),
				'default'     => 'yes',
			),
			'setting_carrier_au' => array(
				'id'              => 'wc-zeus-cp-au',
				'type'        => 'checkbox',
				'label'       => __( 'Au', 'woo-zeus' ),
				'default'     => 'yes',
				'description' => __( 'Please check them you are able to use Carriers', 'woo-zeus' ),
			),
			'test_mode'       => array(
				'title'       => __( 'Test Mode', 'woo-zeus' ),
				'type'        => 'checkbox',
				'description' => __( 'Please check it when you want to use test-mode.', 'woo-zeus' ),
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

	function carrier_select() {
		$carrier_array =array(
			'D' => 'docomo',
			'A' => 'au',
			'S' => 'Softbank',
		);
		?><select name="carrier_type">
		<?php if($this->setting_carrier_dm == 'yes'){?>
			<option value="D"><?php echo __( 'docomo', 'woo-zeus' );?></option>
		<?php }
		if($this->setting_carrier_sb == 'yes'){?>
			<option value="S"><?php echo __( 'SoftBank', 'woo-zeus' );?></option>
		<?php }
		if($this->setting_carrier_au == 'yes'){?>
			<option value="A"><?php echo __( 'au', 'woo-zeus' );?></option>
		<?php }?>
		</select><?php 
	}

	/**
	* UI - Payment page fields for Zeus Payment.
	*/
	function payment_fields() {
		// Description of payment method from settings
		if ( $this->description ) { ?>
    		<p><?php echo $this->description; ?></p>
		<?php } ?>
		<fieldset  style="padding-left: 40px;">
        <p><?php _e( 'Please select carrier which you want to pay', 'woo-zeus' );?></p>
        <?php $this->carrier_select(); ?>
		</fieldset>
	<?php }

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
		$order->add_order_note('test00');
		$connect_url = WC_ZEUS_CP_URL;
		$post_data = array();
		$post_data['clientip'] = $this->authentication_clientip;
		$post_data['carrier_type'] = $this->get_post( 'carrier_type' );// Carrier Type ID
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
//		$post_data['success_url'] = esc_url( home_url( '/' ) );
		$post_data['success_url'] = $this->get_return_url( $order );
		$post_data['success_str'] = mb_convert_encoding(__( 'Back to Store', 'woo-zeus' ), "SJIS", "auto");
		$post_data['failure_url'] = esc_url( home_url( '/' ) );
		$post_data['failure_str'] = mb_convert_encoding(__( 'Back to Store', 'woo-zeus' ), "SJIS", "auto");

		if($post_data['money'] <= 50000){
			//Note for Message
			$message = ', '.__( 'Carrier Type :', 'woo-zeus' ).$this->get_post( 'carrier_type' );
			// Mark as pending (we're awaiting the payment)
			$order->update_status( 'pending', __( 'Awaiting Carrier payment', 'woo-zeus' ).$message );

			// Reduce stock levels
			$order->reduce_order_stock();

			// Remove cart
			WC()->cart->empty_cart();
			return array(
				'result'   => 'success',
				'redirect' => $this->get_zeus_url( $post_data ,$connect_url)
			);
		}elseif($post_data['carrier_type']=='S' and $post_data['money'] <= 100000){
			//Note for Message
			$message = ', '.__( 'Carrier Type :', 'woo-zeus' ).$this->get_post( 'carrier_type' );
			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'on-hold', __( 'Awaiting Carrier payment', 'woo-zeus' ).$message );

			// Reduce stock levels
			$order->reduce_order_stock();

			// Remove cart
			WC()->cart->empty_cart();
			return array(
				'result'   => 'success',
				'redirect' => $this->get_zeus_url( $post_data ,$connect_url)
			);
		}else{
			wc_add_notice( __( 'Purchase price is out of upper limit.' , 'woo-zeus' ), $notice_type = 'error' );
			$order->add_order_note(__( 'Error :' , 'woo-zeus' ).$response_array['error_msg']);
			return ;
		}
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
	function add_wc_zeus_cp_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Zeus_CP';
		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'add_wc_zeus_cp_gateway' );

	/**
	 * Edit the available gateway to woocommerce
	 */
	function edit_zeus_cp_available_gateways( $methods ) {
		if ( isset($currency) ) {
		}else{
			$currency = get_woocommerce_currency();
		}
		if($currency !='JPY'){
		unset($methods['zeus_cp']);
		}
		return $methods;
	}

	add_filter( 'woocommerce_available_payment_gateways', 'edit_zeus_cp_available_gateways' );
