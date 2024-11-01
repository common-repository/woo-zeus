<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Zeus Payment Gateway
 *
 * Provides a Zeus Credit Card Payment Gateway.
 *
 * @class 		WC_Zeus
 * @extends		WC_Gateway_Zeus_CC
 * @version		0.9.0
 * @package		WooCommerce/Classes/Payment
 * @author		Artisan Workshop
 */
class WC_Gateway_Zeus_CC extends WC_Payment_Gateway {


	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		include_once( 'includes/class-wc-gateway-zeus-request.php' );
		include_once( 'includes/class-wc-zeus-error-message.php' );

		$this->id                = 'zeus_cc';
		$this->has_fields        = false;
		$this->order_button_text = __( 'Proceed to Zeus Credit Card', 'woo-zeus' );
		$this->method_title      = __( 'Zeus Credit Card', 'woo-zeus' );
		
        // Create plugin fields and settings
		$this->init_form_fields();
		$this->init_settings();
		$this->method_title       = __( 'Zeus Credit Card Payment Gateway', 'woo-zeus' );
		$this->method_description = __( 'Allows payments by Zeus Credit Card in Japan.', 'woo-zeus' );
		$this->supports = array(
			'products',
			'refunds',
			'tokenization',
			'subscriptions',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_amount_changes',
			'default_credit_card_form',
			'subscription_date_changes',
			'multiple_subscriptions',
		);

		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

		// Load plugin checkout credit Card icon
		if($this->setting_card_vm =='yes' and $this->setting_card_d =='yes' and $this->setting_card_aj =='yes'){
		$this->icon = plugins_url( 'assets/images/zeus-cards.png' , __FILE__ );
		}elseif($this->setting_card_vm =='yes' and $this->setting_card_d =='no' and $this->setting_card_aj =='no'){
		$this->icon = plugins_url( 'assets/images/zeus-cards-v-m.png' , __FILE__ );
		}elseif($this->setting_card_vm =='yes' and $this->setting_card_d =='yes' and $this->setting_card_aj =='no'){
		$this->icon = plugins_url( 'assets/images/zeus-cards-v-m-d.png' , __FILE__ );
		}elseif($this->setting_card_vm =='yes' and $this->setting_card_d =='no' and $this->setting_card_aj =='yes'){
		$this->icon = plugins_url( 'assets/images/zeus-cards-v-m-a-j.png' , __FILE__ );
		}elseif($this->setting_card_vm =='no' and $this->setting_card_d =='no' and $this->setting_card_aj =='yes'){
		$this->icon = plugins_url( 'assets/images/zeus-cards-a-j.png' , __FILE__ );
		}elseif($this->setting_card_vm =='no' and $this->setting_card_d =='yes' and $this->setting_card_aj =='no'){
		$this->icon = plugins_url( 'assets/images/zeus-cards-d.png' , __FILE__ );
		}elseif($this->setting_card_vm =='no' and $this->setting_card_d =='yes' and $this->setting_card_aj =='yes'){
		$this->icon = plugins_url( 'assets/images/zeus-cards-d-a-j.png' , __FILE__ );
		}

		// Actions
		add_action( 'woocommerce_receipt_zeus_cc',                              array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways',              array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'zeus_payment_completed' ),1,3 );
		// For Subscripstion Order
		add_action( 'scheduled_subscription_payment_' . $this->id, array( $this, 'process_scheduled_subscription_payment' ), 10, 3 );

//		add_action( 'wp_enqueue_scripts',                                       array( $this, 'add_zeus_cc_scripts' ) );
	}

      /**
       * Initialize Gateway Settings Form Fields.
       */
	    function init_form_fields() {

	      $this->form_fields = array(
	      'enabled'     => array(
	        'title'       => __( 'Enable/Disable', 'woo-zeus' ),
	        'label'       => __( 'Enable Zeus Credit Card Payment', 'woo-zeus' ),
	        'type'        => 'checkbox',
	        'description' => '',
	        'default'     => 'no'
	        ),
	      'title'       => array(
	        'title'       => __( 'Title', 'woo-zeus' ),
	        'type'        => 'text',
	        'description' => __( 'This controls the title which the user sees during checkout.', 'woo-zeus' ),
	        'default'     => __( 'Credit Card (Zeus)', 'woo-zeus' )
	        ),
	      'description' => array(
	        'title'       => __( 'Description', 'woo-zeus' ),
	        'type'        => 'textarea',
	        'description' => __( 'This controls the description which the user sees during checkout.', 'woo-zeus' ),
	        'default'     => __( 'Pay with your credit card via Zeus.', 'woo-zeus' )
	        ),
			'authentication_clientip' => array(
				'title'       => __( 'Authentication Client IP', 'woo-zeus' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Enter Authentication Client IP.', 'woo-zeus' )),
			),
			'authentication_key' => array(
				'title'       => __( 'Authentication Key', 'woo-zeus' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Enter Authentication Key.', 'woo-zeus' )),
			),
/*			'store_card_info' => array(
				'title'       => __( 'Store Card Infomation', 'woo-zeus' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Store Card Infomation', 'woo-zeus' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Store user Credit Card information in Zeus Server.(Option)', 'woo-zeus' )),
			),*/
			'setting_card_vm' => array(
				'title'       => __( 'Set Credit Card', 'woo-zeus' ),
				'id'              => 'wc-zeus-cc-vm',
				'type'        => 'checkbox',
				'label'       => __( 'VISA & MASTER', 'woo-zeus' ),
				'default'     => 'yes',
			),
			'setting_card_d' => array(
				'id'              => 'wc-zeus-cc-d',
				'type'        => 'checkbox',
				'label'       => __( 'DINNERS', 'woo-zeus' ),
				'default'     => 'yes',
			),
			'setting_card_aj' => array(
				'id'              => 'wc-zeus-cc-aj',
				'type'        => 'checkbox',
				'label'       => __( 'AMEX & JCB', 'woo-zeus' ),
				'default'     => 'yes',
				'description' => sprintf( __( 'Please check them you are able to use Credit Card', 'woo-zeus' )),
			),
			'payment_installments' => array(
				'title'       => __( 'Payment in installments', 'woo-zeus' ),
				'id'          => 'wc-zeus-payment-installments',
				'type'        => 'checkbox',
				'label'       => __( 'Enable Payment in installments', 'woo-zeus' ),
				'default'     => 'no',
			),
			'payment_counts' => array(
				'title'       => __( 'Payment counts', 'woo-zeus' ),
				'id'          => 'wc-zeus-payment-counts',
				'type'        => 'multiselect',
				'label'       => __( 'Enable Payment in installments', 'woo-zeus' ),
				'options'     => array(
					'03' => __('3 times', 'woocommerce' ),
					'05' => __('5 times', 'woocommerce' ),
					'06' => __('6 times', 'woocommerce' ),
					'10' => __('10 times', 'woocommerce' ),
					'12' => __('12 times', 'woocommerce' ),
					'15' => __('15 times', 'woocommerce' ),
					'18' => __('18 times', 'woocommerce' ),
					'20' => __('20 times', 'woocommerce' ),
					'24' => __('24 times', 'woocommerce' ),
				)
			),
			'revolving_repayment' => array(
				'title'       => __( 'Revolving repayment', 'woo-zeus' ),
				'id'          => 'wc-zeus-revolving-repayment',
				'type'        => 'checkbox',
				'label'       => __( 'Enable Revolving repayment', 'woo-zeus' ),
				'default'     => 'no',
			),
			'twice_payment' => array(
				'title'       => __( 'Twice payment', 'woo-zeus' ),
				'id'          => 'wc-zeus-twice-payment',
				'type'        => 'checkbox',
				'label'       => __( 'Enable Twice payment', 'woo-zeus' ),
				'default'     => 'no',
			),
			'bonus_pay' => array(
				'title'       => __( 'Bonus pay', 'woo-zeus' ),
				'id'          => 'wc-zeus-bonus-pay',
				'type'        => 'checkbox',
				'label'       => __( 'Enable Bonus pay', 'woo-zeus' ),
				'default'     => 'no',
				'description' => __( 'Split twice, bonus payments are it separately as an option. If the use of any of your choice, Please contact Zeus support.', 'woo-zeus' ),

			),
		);
		}


      /**
       * UI - Admin Panel Options
       */
		function admin_options() { ?>
			<h3><?php _e( 'Zeus Credit Card','woo-zeus' ); ?></h3>
		    <table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table>
		<?php }

      /**
       * UI - Payment page fields for Zeus Payment.
       */
		function payment_fields() {
		// Description of payment method from settings
			if ( $this->description ) { ?>
            		<p><?php echo $this->description; ?></p>
      		<?php } ?>
      		
		<fieldset  style="padding-left: 40px;">
				<?php
				$user = wp_get_current_user();
				$zeus_tokens = $this->user_has_stored_data( $user->ID );
				if($this->store_card_info == 'yes' and isset($zeus_tokens)){ ?>
					<fieldset>
						<input type="radio" name="zeus-use-stored-payment-info" id="zeus-use-stored-payment-info-yes" value="yes" checked="checked" onclick="document.getElementById('zeus-new-info').style.display='none'; document.getElementById('zeus-stored-info').style.display='block'"; />
						<label for="zeus-use-stored-payment-info-yes" style="display: inline;"><?php _e( 'Use a stored credit card', 'woo-zeus' ) ?></label>
						<div id="zeus-stored-info" style="padding: 10px 0 0 40px; clear: both;">
						<p>
						<?php echo __( 'credit card last 4 numbers and expires: ', 'woo-zeus' ).'<br />';
							foreach($zeus_tokens as $zeus_token) { 
								$key =+ 1;
							?>
								<input type="radio" name="zeus-token-cc" value="<?php echo $key;?>" id="stored-info">
								****-****-****-<?php echo $zeus_token->get_last4(); ?> (<?php echo $zeus_token->get_expiry_year(); ?>/<?php $zeus_token->get_expiry_month(); ?>)
								<br />
							<?php }?>
						</p>
						<p class="form-row form-row-first">
							<label for="-card-cvc"><?php echo __( 'Security Code', 'woo-zeus' ) ?> <span class="required">*</span></label>
							<input id="-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="CVC" name="-card-cvc">
						</p>
						</fieldset>
						<fieldset>
							<input type="radio" name="zeus-use-stored-payment-info" id="zeus-use-stored-payment-info-no" value="no" onclick="document.getElementById('zeus-stored-info').style.display='none'; document.getElementById('zeus-new-info').style.display='block'"; />
		                  	<label for="zeus-use-stored-payment-info-no"  style="display: inline;"><?php _e( 'Use a new payment method', 'woo-zeus' ) ?></label>
						</fieldset>
		                	<div id="zeus-new-info" style="display:none">
				<?php } else { ?>
              			<fieldset>
              				<!-- Show input boxes for new data -->
              				<div id="zeus-new-info">
              	<?php } ?>
						<!-- Credit card number -->
				<?PHP $credit_card_form = new WC_Payment_Gateway_CC;
					$credit_card_form->form();
					//$this->credit_card_form( array( 'fields_have_names' => true ) ); 
				?>
						<!-- Credit card holder Name -->
                    	<p class="form-row form-row-first">
							<label for="ccname"><?php echo __( 'Credit Card holder Name', 'woo-zeus' ) ?> <span class="required">*</span></label>
							<input type="text" class="input-text" id="card_holder_name" name="card_holder_name" maxlength="32" />
                    	</p>
                <?php if($this->payment_installments == 'yes'){ ?>
						<!-- Credit card holder Name -->
                    	<p class="form-row form-row-first">
							<label for="payment_times"><?php echo __( 'Payment Times', 'woo-zeus' ) ?> <span class="required">*</span></label>
							<select name="zeus-payment-count" class="zeus-payment-count">
							<?php
							$payment_setting_array = get_option('woocommerce_zeus_cc_settings');
							echo '<option value="01">1'.__( ' times', 'woo-zeus' ).'</option>';
							if($this->twice_payment == 'yes'){
								echo '<option value="02">2'.__( ' times', 'woo-zeus' ).'</option>';
							}
							foreach($payment_setting_array['payment_counts'] as $key => $value){
								if(substr($value,0,1) == 0){$value = substr($value,1,1);}
								echo '<option value="'.$value.'">'.$value.__( ' times', 'woo-zeus' ).'</option>';
							}
							if($this->revolving_repayment == 'yes'){
								echo '<option value="99">'.__( 'Revolving repayment', 'woo-zeus' ).'</option>';
							}
							if($this->bonus_pay == 'yes'){
								echo '<option value="B1">'.__( 'Enable Bonus pay', 'woo-zeus' ).'</option>';
							}
							?>
							</select>
                    	</p>
                <?php } ?>
            	</fieldset>
			</fieldset>
<?php
    }

	/**
	 * Process the payment and return the result.
	 */
	function process_payment( $order_id ) {
		include_once( 'includes/class-wc-gateway-zeus-request.php' );

		global $woocommerce;
		global $wpdb;

		$connect_url = WC_ZEUS_CC_API_URL;

		$order = new WC_Order( $order_id );
		$user_id = $order->user_id;
//		$order->add_order_note($post_data['uniq_key']['sendid']);
		$this_setting = array(
			'clientip' => $this->authentication_clientip,
			'key' => $this->authentication_key,
			'payment_installments' => $this->payment_installments,
			'store_card_info' => $this->store_card_info,
		);
		$zeus_stored_cc = $this->user_has_stored_data( $order->user_id );
//		$order->add_order_note('test01');

		$zeus_request = new WC_Gateway_Zeus_Request( $this );
		$post_request = $zeus_request->make_post_request_cc($order, 'no');
		
		$sendid = null;
		$post_data = $zeus_request->make_post_data_cc($order, $this_setting, $zeus_stored_cc, $sendid);

		$response_array = $zeus_request->send_zeus_pay_request($post_request, $post_data, $order, $connect_url);
		if($response_array->result->status == 'success'){
			// Mark as processing (we're awaiting the shipment)
			$order->update_status( 'processing', __( 'Finished Payment. Order Number:', 'woo-zeus' ).$response_array->order_number );
			$transaction_id = (string) $response_array->order_number;
			//set transaction id for Zeus Order Number
			update_post_meta( $order->id, '_transaction_id', $transaction_id );
			if($post_data['uniq_key']['sendid']){
				update_post_meta( $order->id, '_zeus_customer_cc_id', wc_clean( $post_data['uniq_key']['sendid'] ) );
			}

			if($this->store_card_info == 'yes'){
				$token = new WC_Payment_Token_CC();
				$token->set_token( $sendid ); // Token comes from payment processor
				$token->set_gateway_id( $this->id );
				if((string) $response_array->addition_value->ctype == 'V'){
					$token->set_card_type( 'visa' );
				}elseif((string) $response_array->addition_value->ctype == 'M'){
					$token->set_card_type( 'mastercard' );
				}elseif((string) $response_array->addition_value->ctype == 'J'){
					$token->set_card_type( 'jcb' );
				}elseif((string) $response_array->addition_value->ctype == 'A'){
					$token->set_card_type( 'american express' );
				}elseif((string) $response_array->addition_value->ctype == 'I'){
					$token->set_card_type( 'discover' );
				}elseif((string) $response_array->addition_value->ctype == 'D'){
					$token->set_card_type( 'diners' );
				}
				$token->set_last4( (string)$response_array->card->number->suffix );
				$token->set_expiry_month( (string)$response_array->card->expires->month );
				$token->set_expiry_year( (string)$response_array->card->expires->year );
				$token->set_user_id( get_current_user_id() );
				// Save the new token to the database
				$token->save();
				// Set this token as the users new default token
//				WC_Payment_Tokens::set_users_default( get_current_user_id(), $token->get_id() );
			}
			// Reduce stock levels
			$order->reduce_order_stock();

			// Remove cart
			WC()->cart->empty_cart();
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		}else{
			$this->notice_invalid($response_array->result->code, $order, 'Auth');
		}
	}

    /**
     * Check if the user has any billing records in the Customer Vault
     */
	function user_has_stored_data( $user_id ) {
		$tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id(), $this->id );
		return $tokens;
	}

    /**
     * Check payment details for valid format
     */
	function validate_fields() {

		if ( $this->get_post( 'zeus-use-stored-payment-info' ) == 'yes' ) return;

		global $woocommerce;

		// Check for saving payment info without having or creating an account
		if ( $this->get_post( 'saveinfo' )  && ! is_user_logged_in() && ! $this->get_post( 'createaccount' ) ) {
        wc_add_notice( __( 'Sorry, you need to create an account in order for us to save your payment information.', 'woo-zeus'), $notice_type = 'error' );
        return false;
      }

		$cardNumber          = str_replace(array(" ","/" ), "",$this->get_post( '-card-number' ));
		$cardCVC             = $this->get_post( '-card-cvc' );
		$countCVC = strlen($cardCVC);

		// Check card number
		if ( empty( $cardNumber ) || ! ctype_digit( $cardNumber ) ) {
			wc_add_notice( __( 'Card number is invalid.', 'woo-zeus' ), $notice_type = 'error' );
			return false;
		}
		// Check CVC number
		if ( empty( $cardCVC ) || ! ctype_digit( $cardCVC ) ) {
			wc_add_notice( __( 'Card CVV is invalid.', 'woo-zeus' ), $notice_type = 'error' );
			return false;
		} elseif ( $countCVC > 5 ) {
			wc_add_notice( __( 'Card CVV is invalid.', 'woo-zeus' ), $notice_type = 'error' );
			return false;
		}
		$card_valid_term = str_replace(array(" ","/" ), "", $this->get_post( '-card-expiry' ) );
		$ct_year = substr($card_valid_term, -2);
		$ct_month = substr($card_valid_term, 0, -2);
		$this_month = date('ym');
		$ct_month = $ct_year.$ct_month;
		if(strlen($card_valid_term) > 4){
			wc_add_notice( __( 'Card ternm is invalid. MM/YY only', 'woo-zeus' ), $notice_type = 'error' );
			return false;
		}elseif($ct_month <= $this_month){
			wc_add_notice( __( 'Card ternm is past date.', 'woo-zeus' ), $notice_type = 'error' );
			return false;			
		}
		return true;

	}
	/**
	* Notice Error in Request Invalid
	*/
	function error_notice($response, $order, $req){
		if($response->result->status == 'invalid'){
			$this->notice_invalid($response->result->code, $order, $req);
			return ;
		}elseif($response->result->status == 'failure'){
			$this->notice_failure($response->result->code, $order, $req);
			return ;
		}elseif($response->result->status == 'meintenance'){
			$this->notice_meintenance($response->result->code, $order, $req);
			return ;
		}
	}

	/**
	* Notice Error in Request Invalid
	*/
	function notice_invalid($code, $order, $req){
		$error_messages = new ErrorHandler();
		if(is_checkout()){
			wc_add_notice( $error_messages->getMessage( $code ).__( ' Invalid error code is' , 'woo-zeus' ).$code, $notice_type = 'error' );
		}
		$order->add_order_note($error_messages->getMessage( $code ).__( ' Invalid Error Code:' , 'woo-zeus' ).$code.'in '.$req);
	}

	/**
	* Notice Error in Request Failure
	*/
	function notice_failure($code, $order, $req) {
		$error_messages = new ErrorHandler();
		if(is_checkout()){
			wc_add_notice( $error_messages->getMessage( $code ).__( ' Failure error code is' , 'woo-zeus' ).$code, $notice_type = 'error' );
		}
		$order->add_order_note($error_messages->getMessage( $code ).__( ' Failure Error Code:' , 'woo-zeus' ).$code.'in '.$req);
	}
	/**
	* Notice Error in Request Meintenance
	*/
	function notice_meintenance($code, $order, $req) {
		$error_messages = new ErrorHandler();
		if(is_checkout()){
			wc_add_notice( $error_messages->getMessage( $code ).__( ' Mentenance error code is' , 'woo-zeus' ).$code, $notice_type = 'error' );
		}
		$order->add_order_note($error_messages->getMessage( $code ).__( ' Mentenance Error Code:' , 'woo-zeus' ).$code.'in '.$req);
	}

	function receipt_page( $order ) {
		echo '<p>' . __( 'Thank you for your order.', 'woo-zeus' ) . '</p>';
	}

	function zeus_payment_completed( $order_id ,$old_status , $new_status) {
		include_once( 'includes/class-wc-gateway-zeus-request.php' );
		global $woocommerce;
		$order = new WC_Order( $order_id );
		if($new_status == 'completed' || $old_status == 'processing'){
			$connect_url = WC_ZEUS_SECURE_API_URL;
			$post_data['clientip'] = $this->authentication_clientip;
			$post_data['king'] = $order->order_total;
			$post_data['date'] = date('Ymd');
			$post_data['ordd'] = get_post_meta( $order_id, '_transaction_id', true );
			$post_data['autype'] = 'sale';
			$zeus_request = new WC_Gateway_Zeus_Request();
//			$order->add_order_note('00');
			
			$zeus_response = $zeus_request->send_zeus_complete_request( $post_data, $connect_url );
			if($zeus_response == 'Success_order'){
				$order->add_order_note(__( 'Zeus Auto finished to sale.' , 'woo-zeus' ));
			}else{
				$order->add_order_note(__( 'Fail to authority for sale, please update sale in Zeus Admin site.' , 'woo-zeus' ));
			}
		}
	}

    /**
     * Include jQuery and our scripts
     */
/*    function add_zeus_cc_scripts() {

//      if ( ! $this->user_has_stored_data( wp_get_current_user()->ID ) ) return;

      wp_enqueue_script( 'jquery' );
      wp_enqueue_script( 'edit_billing_details', plugin_dir_path( __FILE__ ) . 'js/edit_billing_details.js', array( 'jquery' ), 1.0 );

      if ( $this->security_check == 'yes' ) wp_enqueue_script( 'check_cvv', plugin_dir_path( __FILE__ ) . 'js/check_cvv.js', array( 'jquery' ), 1.0 );

    }
*/
		/**
		 * Get post data if set
		 */
		private function get_post( $name ) {
			if ( isset( $_POST[ $name ] ) ) {
				return $_POST[ $name ];
			}
			return null;
		}

		/**
     * Check whether an order is a subscription
     */
		private function is_subscription( $order ) {
      return class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order );
		}

}
/**
 * Add the gateway to woocommerce
 */
function add_wc_zeus_cc_gateway( $methods ) {
	$methods[] = 'WC_Gateway_Zeus_CC';
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_wc_zeus_cc_gateway' );

/**
 * Edit the available gateway to woocommerce
 */
function edit_available_gateways( $methods ) {
	if ( isset($currency) ) {
	}else{
	$currency = get_woocommerce_currency();
	}
	if($currency !='JPY'){
	unset($methods['zeus_cc']);
	}
	return $methods;
}

add_filter( 'woocommerce_available_payment_gateways', 'edit_available_gateways' );
