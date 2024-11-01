<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Zeus Payment Gateway
 *
 * Provides a Zeus Convenience store Payment Gateway.
 *
 * @class 		WC_Zeus
 * @extends		WC_Gateway_Zeus_CS
 * @version		0.9.0
 * @package		WooCommerce/Classes/Payment
 * @author		Artisan Workshop
 */
class WC_Gateway_Zeus_CS extends WC_Payment_Gateway {


	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id                = 'zeus_cs';
		$this->has_fields        = false;
//		$this->order_button_text = __( 'Proceed to Zeus Convenience store Payment', 'woo-zeus' );
		$this->method_title      = __( 'Zeus Convenience store Payment', 'woo-zeus' );
		
        // Create plugin fields and settings
		$this->init_form_fields();
		$this->init_settings();
		$this->method_title       = __( 'Zeus Convenience store Payment Gateway', 'woo-zeus' );
		$this->method_description = __( 'Allows payments by Zeus Convenience store Payment in Japan.', 'woo-zeus' );

		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

        // Set Convenience Store
		$this->cs_stores = array();
		if(isset($this->setting_cs_se) and $this->setting_cs_se =='yes') $this->cs_stores = array_merge($this->cs_stores, array('D001' => __( 'Seven Eleven', 'woo-zeus' )));
		if(isset($this->setting_cs_ls) and $this->setting_cs_ls =='yes') $this->cs_stores = array_merge($this->cs_stores, array('D002' => __( 'Lawson', 'woo-zeus' )));
		if(isset($this->setting_cs_fm) and $this->setting_cs_fm =='yes') $this->cs_stores = array_merge($this->cs_stores, array('D030' => __( 'Family Mart', 'woo-zeus' )));
		if(isset($this->setting_cs_ck) and $this->setting_cs_ck =='yes') $this->cs_stores = array_merge($this->cs_stores, array('D040' => __( 'Circle K', 'woo-zeus' )));
		if(isset($this->setting_cs_sm) and $this->setting_cs_sm =='yes') $this->cs_stores = array_merge($this->cs_stores, array('D015' => __( 'Seicomart', 'woo-zeus' )));
		if(isset($this->setting_cs_ms) and $this->setting_cs_ms =='yes') $this->cs_stores = array_merge($this->cs_stores, array('D050' => __( 'Mini Stop', 'woo-zeus' )));
		if(isset($this->setting_cs_dy) and $this->setting_cs_dy =='yes') $this->cs_stores = array_merge($this->cs_stores, array('D060' => __( 'Daily Yamazaki', 'woo-zeus' )));

		// Actions
		add_action( 'woocommerce_receipt_zeus_cs',                              array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways',              array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
//		add_action( 'wp_enqueue_scripts',                                       array( $this, 'add_zeus_cs_scripts' ) );
	    // Customer Emails
	    add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	* Initialize Gateway Settings Form Fields.
	*/
	function init_form_fields() {

		$this->form_fields = array(
			'enabled'     => array(
				'title'       => __( 'Enable/Disable', 'woo-zeus' ),
				'label'       => __( 'Enable Zeus Convenience store Payment', 'woo-zeus' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title'       => array(
				'title'       => __( 'Title', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woo-zeus' ),
				'default'     => __( 'Convenience store Payment (Zeus)', 'woo-zeus' )
			),
			'description' => array(
				'title'       => __( 'Description', 'woo-zeus' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woo-zeus' ),
				'default'     => __( 'Pay with Convenience store Payment via Zeus.', 'woo-zeus' )
			),
			'order_button_text'       => array(
				'title'       => __( 'Order Button Text', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'This controls the proceed button during checkout.', 'woo-zeus' ),
				'default'     => __( 'Proceed to Zeus Convenience store Payment', 'woo-zeus' )
			),
			'authentication_clientip' => array(
				'title'       => __( 'Authentication Client IP', 'woo-zeus' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Enter Authentication Client IP.', 'woo-zeus' )),
			),
			'setting_cs_se' => array(
				'title'       => __( 'Set Convenience Store', 'woo-zeus' ),
				'id'              => 'wc-paygent-cs-se',
				'type'        => 'checkbox',
				'label'       => __( 'Seven Eleven', 'woo-zeus' ),
				'default'     => 'yes',
			),
			'setting_cs_ls' => array(
				'id'              => 'wc-paygent-cs-ls',
				'type'        => 'checkbox',
				'label'       => __( 'Lawson', 'woo-zeus' ),
				'default'     => 'yes',
			),
			'setting_cs_fm' => array(
				'id'              => 'wc-paygent-cs-f',
				'type'        => 'checkbox',
				'label'       => __( 'Family Mart', 'woo-zeus' ),
				'default'     => 'yes',
			),
			'setting_cs_ck' => array(
				'id'              => 'wc-paygent-cs-ctd',
				'type'        => 'checkbox',
				'label'       => __( 'Circle K', 'woo-zeus' ),
				'default'     => 'yes',
			),
			'setting_cs_sm' => array(
				'id'              => 'wc-paygent-cs-sm',
				'type'        => 'checkbox',
				'label'       => __( 'Seicomart', 'woo-zeus' ),
				'default'     => 'yes',
			),
			'setting_cs_ms' => array(
				'id'              => 'wc-paygent-cs-ms',
				'type'        => 'checkbox',
				'label'       => __( 'Mini Stop', 'woo-zeus' ),
				'default'     => 'yes',
			),
			'setting_cs_dy' => array(
				'id'              => 'wc-paygent-cs-ctd',
				'type'        => 'checkbox',
				'label'       => __( 'Daily Yamazaki', 'woo-zeus' ),
				'default'     => 'yes',
				'description' => sprintf( __( 'Please check them you are able to use Convenience Store', 'woo-zeus' ))
			),
			'processing_email_subject'       => array(
				'title'       => __( 'Email Subject when complete payment check', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'send e-mail subject when check Zeus after customer paid.', 'woo-zeus' ),
				'default'     => __( 'Payment Complete by CS', 'woo-zeus' )
			),
			'processing_email_heading'       => array(
				'title'       => __( 'Email Heading when complete payment check', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'send e-mail heading when check Zeus after customer paid.', 'woo-zeus' ),
				'default'     => __( 'Thank you for your payment', 'woo-zeus' )
			),
			'processing_email_body'       => array(
				'title'       => __( 'Email body when complete payment check', 'woo-zeus' ),
				'type'        => 'textarea',
				'description' => __( 'send e-mail Body when check Zeus after customer paid.', 'wc4jp-gmo-pg' ),
				'default'     => __( 'I checked your payment. Thank you. I will ship your order as soon as possible.', 'woo-zeus' )
			),
			'payment_limit_description'       => array(
				'title'       => __( 'Explain Payment limit date', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'Explain Payment limite date in New order E-mail.', 'woo-zeus' ),
				'default'     => __( 'The payment deadline is 10 days from completed the order.', 'woo-zeus' )
			),
			'test_mode' => array(
				'title'       => __( 'Test Mode', 'woo-zeus' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Test Mode', 'woo-zeus' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Please check it when you want to use test-mode.', 'woo-zeus' )),
			),
			'testcard_id' => array(
				'title'       => __( 'Test Card ID', 'woo-zeus' ),
				'type'        => 'text',
				'description' => __( 'If you want to use test mode, please input Test Card ID from Zeus Admin Site.', 'woo-zeus' ),
			),
		);
	}

	function cs_select() {
		?><select name="cvs_company_id">
		<?php foreach($this->cs_stores as $num => $value){?>
			<option value="<?php echo $num; ?>"><?php echo $value;?></option>
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
        <p><?php _e( 'Please select Convenience Store where you want to pay', 'woo-zeus' );?></p>
        <?php $this->cs_select(); ?>
		</fieldset>
	<?php }

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
		$post_data['pay_cvs'] = $this->get_post( 'cvs_company_id' );// Convenience Store Company ID
		$post_data['username'] = mb_convert_kana($this->get_post( 'billing_yomigana_last_name' ), "KVC").mb_convert_kana($this->get_post( 'billing_yomigana_first_name' ), "KVC");
		// Set send data for Zeus
		$post_data['sendid'] = $user->ID;
		
		//Test mode Setting
		if(isset($this->test_mode) and $this->test_mode == 'yes'){
			$post_data['testid'] = $this->testcard_id;
			$post_data['test_type'] = 1;
		}

		$zeus_request = new WC_Gateway_Zeus_Request();
		$response_array = $zeus_request->send_zeus_request( $post_data, $order, $connect_url);
		if(isset($response_array['error_msg'])){
			wc_add_notice( $response_array['error_msg'], $notice_type = 'error' );
			$order->add_order_note(__( 'Error :' , 'woo-zeus' ).$response_array['error_msg']);
		}elseif($response_array['rel']=='failure_order'){
			wc_add_notice( $response_array['error_code'], $notice_type = 'error' );
			$order->add_order_note(__( 'Error Code:' , 'woo-zeus' ).$response_array['error_msg']);
		}elseif($response_array['rel']=='Success_order'){
			//save pay infomation
			update_post_meta( $order->id, '_zeus_pay_no1', wc_clean( $response_array[ 'pay_no1' ] ) );
			update_post_meta( $order->id, '_zeus_cvs_id', wc_clean( $this->get_post( 'cvs_company_id' ) ) );
			if(isset($response_array[ 'pay_no2' ])){
				update_post_meta( $order->id, '_zeus_pay_no2', wc_clean( $response_array[ 'pay_no2' ] ) );
			}
			if(isset($response_array[ 'pay_url' ])){
				update_post_meta( $order->id, '_zeus_pay_url', wc_clean( $response_array[ 'pay_url' ] ) );
			}
			if(isset($response_array[ 'pay_limit' ])){
				update_post_meta( $order->id, '_zeus_pay_limit', wc_clean( $response_array[ 'pay_limit' ] ) );
			}
			//Note for Message
			$message = '';
			if($this->get_post( 'cvs_company_id' ) == 'D001'){
				$message = '('.__( 'Seven Eleven', 'woo-zeus' ).') ,'.__('Payment slip number :','woo-zeus').$response_array['pay_no1'];
			}elseif($this->get_post( 'cvs_company_id' )=='D002'){
				$message = '('.__( 'Lawson', 'woo-zeus' ).')'.__('Receipt number :','woo-zeus').$response_array['pay_no1'].', '.__('Authorization number :','woo-zeus').$response_array['pay_no2'];
			}elseif($this->get_post( 'cvs_company_id' )=='D030'){
				$message = '('.__( 'Family Mart', 'woo-zeus' ).') ,'.__('Order number :','woo-zeus').$response_array['pay_no1'].', '.__('Corporate code :','woo-zeus').$response_array['pay_no2'];
			}elseif($this->get_post( 'cvs_company_id' )=='D040'){
				$message = '('.__( 'Circle K', 'woo-zeus' ).') ,'.__('Payment receipt number :','woo-zeus').$response_array['pay_no1'];
			}elseif($this->get_post( 'cvs_company_id' )=='D015'){
				$message = '('.__( 'Seicomart', 'woo-zeus' ).') ,'.__('Payment receipt number :','woo-zeus').$response_array['pay_no1'];
			}elseif($this->get_post( 'cvs_company_id' )=='D050'){
				$message = '('.__( 'Mini Stop', 'woo-zeus' ).') ,'.__('Receipt number :','woo-zeus').$response_array['pay_no1'].', '.__('Authorization number :','woo-zeus').$response_array['pay_no2'];
			}elseif($this->get_post( 'cvs_company_id' )=='D060'){
				$message = '('.__( 'Daily Yamazaki', 'woo-zeus' ).') ,'.__('Online payment number :','woo-zeus').$response_array['pay_no1'];
			}

			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'on-hold', __( 'Awaiting Convenience Store payment', 'woo-zeus' ).$message );
			
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
    /**
     * Add content to the WC emails For Convenient Infomation.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     * @return void
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
    	if ( ! $sent_to_admin && 'zeus_cs' === $order->payment_method && 'on-hold' === $order->status ) {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
			$this->zeus_cs_details( $order->id );
    	}elseif ( ! $sent_to_admin && 'zeus_cs' === $order->payment_method && 'processing' === $order->status ) {
	    	echo $this->processing_email_body;
		}
    }

    /**
     * Get Convini Payment details and place into a list format
     */
	private function zeus_cs_details( $order_id = '' ) {
		$cvs_array = $this->cs_stores;
		$cvs_array = array(
			'D001' => array(//Seven Eleven
				'name' =>$cvs_array['D001'], 
				'number_title'=>__('Payment slip number :','woo-zeus'),
				'confirm_num'=>'no',
				'url' =>'711.html',
				'pay_url' => get_post_meta( $order->id, '_zeus_pay_url')
			),
			'D002' => array(//Lawson
				'name' =>$cvs_array['D002'], 
				'number_title'=>__('Receipt number :','woo-zeus'),
				'confirm_num'=>__('Authorization number :','woo-zeus'),
				'url' =>'law.html'
			),
			'D030' => array(//Family Mart
				'name' =>$cvs_array['D030'], 
				'number_title'=>__('Order number :','woo-zeus'),
				'confirm_num'=>__('Corporate code :','woo-zeus'),
				'url' =>'fm.html'
			),
			'D040' => array(//Circle K
				'name' =>$cvs_array['D040'], 
				'number_title'=>__('Payment receipt number :','woo-zeus'),
				'confirm_num'=>'no',
				'url' =>'ss.html'
			),
			'D015' => array(//Seico Mart
				'name' =>$cvs_array['D015'], 
				'number_title'=>__('Payment receipt number :','woo-zeus'),
				'confirm_num'=>'no',
				'url' =>'sm.html'
			),
			'D050' => array(//Mini Stop
				'name' =>$cvs_array['D050'], 
				'number_title'=>__('Receipt number :','woo-zeus'),
				'confirm_num'=>__('Authorization number :','woo-zeus'),
				'url' =>'mini_lp.html'
			),
			'D060' => array(//Daily Yamazaki
				'name' =>$cvs_array['D060'], 
				'number_title'=>__('Online payment number :','woo-zeus'),
				'confirm_num'=>'no',
				'url' =>'da.html'
			),
		);
		global $woocommerce;
		$order = new WC_Order( $order_id );
		$cvs_id = get_post_meta($order->id, '_zeus_cvs_id',true);
		$pay_no1 = get_post_meta($order->id, '_zeus_pay_no1',true);
		$pay_no2 = get_post_meta($order->id, '_zeus_pay_no2',true);
		
		echo __('CVS Name : ', 'woo-zeus').$cvs_array[$cvs_id]['name'].'<br />'.PHP_EOL;
		echo $cvs_array[$cvs_id]['number_title'].$pay_no1.'<br />'.PHP_EOL;
		if($cvs_array[$cvs_id]['confirm_num'] != 'no'){
			echo $cvs_array[$cvs_id]['confirm_num'].$pay_no2.'<br />'.PHP_EOL;
		}
		if(isset($cvs_array[$cvs_id]['pay_url'])){
			echo __('Internet shopping payment slip URL:', 'woo-zeus').$cvs_array[$cvs_id]['pay_url'];
		}
		echo __('How to Pay via CVS expalin URL : ', 'woo-zeus').'http://www.cardservice.co.jp/info/cvd/pc/'.$cvs_array[$cvs_id]['url'].'<br />'.PHP_EOL;
		if(isset($this->payment_limit_description)){
			$pay_limit = get_post_meta($order->id, '_zeus_pay_limit',true);
			echo __('Payment limit term : ', 'woo-zeus').$pay_limit.'<br />'.$this->payment_limit_description;
		}
	}
	function receipt_page( $order ) {
		echo '<p>' . __( 'Thank you for your order.', 'woo-zeus' ) . '</p>';
	}

    /**
     * Include jQuery and our scripts
     */
    function add_zeus_cs_scripts() {

      wp_enqueue_script( 'jquery' );
//      wp_enqueue_script( 'edit_billing_details', plugin_dir_path( __FILE__ ) . 'js/edit_billing_details.js', array( 'jquery' ), 1.0 );

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
function add_wc_zeus_cs_gateway( $methods ) {
	$methods[] = 'WC_Gateway_Zeus_CS';
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_wc_zeus_cs_gateway' );

/**
 * Edit the available gateway to woocommerce
 */
function edit_zeus_cs_available_gateways( $methods ) {
	if ( isset($currency) ) {
	}else{
		$currency = get_woocommerce_currency();
	}
	if($currency !='JPY'){
		unset($methods['zeus_cs']);
	}
	return $methods;
}

add_filter( 'woocommerce_available_payment_gateways', 'edit_zeus_cs_available_gateways' );

// E-mail Subject Change when processing in this Payment
function change_email_subject_cs($subject, $order){
	global $woocommerce;
	if ( 'zeus_cs' == $order->payment_method  && 'processing' === $order->status) {
		$payment_setting = get_option('woocommerce_zeus_cs_settings');
		$subject =$payment_setting['processing_email_subject'];
	}
	return $subject;
}
function change_email_heading_cs($heading, $order){
	global $woocommerce;
	if ( 'zeus_cs' == $order->payment_method  && 'processing' === $order->status) {
		$payment_setting = get_option('woocommerce_zeus_cs_settings');
		$heading = $payment_setting['processing_email_heading'];
	}
	return $heading;
}

add_filter( 'woocommerce_email_subject_customer_processing_order', 'change_email_subject_cs', 1, 2 );
add_filter( 'woocommerce_email_heading_customer_processing_order', 'change_email_heading_cs', 1, 2 );

/**
 * Get Payeasy Payment details and place into a list format
 */
function zeus_cs_detail( $order ){
	global $woocommerce;
		$cvs_array = array(
			'D001' => array(//Seven Eleven
				'name' =>__( 'Seven Eleven', 'woo-zeus' ), 
				'number_title'=>__('Payment slip number :','woo-zeus'),
				'confirm_num'=>'no',
				'url' =>'711.html',
				'pay_url' => get_post_meta( $order->id, '_zeus_pay_url')
			),
			'D002' => array(//Lawson
				'name' =>__( 'Lawson', 'woo-zeus' ), 
				'number_title'=>__('Receipt number :','woo-zeus'),
				'confirm_num'=>__('Authorization number :','woo-zeus'),
				'url' =>'law.html'
			),
			'D030' => array(//Family Mart
				'name' =>__( 'Family Mart', 'woo-zeus' ), 
				'number_title'=>__('Order number :','woo-zeus'),
				'confirm_num'=>__('Corporate code :','woo-zeus'),
				'url' =>'fm.html'
			),
			'D040' => array(//Circle K
				'name' =>__( 'Circle K', 'woo-zeus' ), 
				'number_title'=>__('Payment receipt number :','woo-zeus'),
				'confirm_num'=>'no',
				'url' =>'ss.html'
			),
			'D015' => array(//Seico Mart
				'name' =>__( 'Seicomart', 'woo-zeus' ), 
				'number_title'=>__('Payment receipt number :','woo-zeus'),
				'confirm_num'=>'no',
				'url' =>'sm.html'
			),
			'D050' => array(//Mini Stop
				'name' =>__( 'Mini Stop', 'woo-zeus' ), 
				'number_title'=>__('Receipt number :','woo-zeus'),
				'confirm_num'=>__('Authorization number :','woo-zeus'),
				'url' =>'mini_lp.html'
			),
			'D060' => array(//Daily Yamazaki
				'name' =>__( 'Daily Yamazaki', 'woo-zeus' ), 
				'number_title'=>__('Online payment number :','woo-zeus'),
				'confirm_num'=>'no',
				'url' =>'da.html'
			),
		);

	$payment_setting = get_option('woocommerce_zeus_cs_settings');
	$cvs_id = get_post_meta($order->id, '_zeus_cvs_id',true);
	$pay_no1 = get_post_meta($order->id, '_zeus_pay_no1',true);
	$pay_no2 = get_post_meta($order->id, '_zeus_pay_no2',true);

	if( get_post_meta( $order->id, '_payment_method', true ) == 'zeus_cs' ){
		echo '<header class="title"><h3>'.__('Payment Detail', 'woo-zeus').'</h3></header>';
		echo '<table class="shop_table order_details">';
		echo '<tr><th>'.__('CVS Name', 'woo-zeus').'</th><td>'.$cvs_array[$cvs_id]['name'].'</td></tr>'.PHP_EOL;
		echo '<tr><th>'.__('Payment Information', 'woo-zeus').'</th><td>';
		echo $cvs_array[$cvs_id]['number_title'].' : '.$pay_no1.'<br />'.PHP_EOL;
		if($cvs_array[$cvs_id]['confirm_num'] != 'no'){
			echo $cvs_array[$cvs_id]['confirm_num'].' : '.$pay_no2.'<br />'.PHP_EOL;
		}
		if(isset($cvs_array[$cvs_id]['pay_url'])){
			echo __('Internet shopping payment slip URL:', 'woo-zeus').$cvs_array[$cvs_id]['pay_url'];
		}
		echo '</td></tr>'.PHP_EOL;
		echo '<tr><th>'.__('How to Pay via CVS expalin URL', 'woo-zeus').'</th><td>';
		echo '<a href="http://www.cardservice.co.jp/info/cvd/pc/'.$cvs_array[$cvs_id]['url'].'" target="_blank">'.__('Click here.', 'woo-zeus').'</a>';
		echo '</td></tr>'.PHP_EOL;
		echo '</table>';
	}
}
add_action( 'woocommerce_order_details_after_order_table', 'zeus_cs_detail', 10, 1);
