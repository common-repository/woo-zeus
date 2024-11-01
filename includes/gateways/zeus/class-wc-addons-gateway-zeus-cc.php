<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Zeus Gateway for subscriptions.
 *
 * @class 		WC_Addons_Gateway_Zeus_Commerce
 * @extends		WC_Gateway_Zeus_Commerce
 * @since       2.2.0
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_Addons_Gateway_Zeus_CC extends WC_Gateway_Zeus_CC {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 3 );
			add_action( 'woocommerce_subscription_failing_payment_method_updated_' . $this->id, array( $this, 'update_failing_payment_method' ), 10, 2 );

			add_action( 'wcs_resubscribe_order_created', array( $this, 'delete_resubscribe_meta' ), 10 );
			add_action( 'wcs_renewal_order_created', array( $this, 'delete_renewal_meta' ), 10 );

			// Allow store managers to manually set Zeus as the payment method on a subscription
			add_filter( 'woocommerce_subscription_payment_meta', array( $this, 'add_subscription_payment_meta' ), 10, 2 );
//			add_filter( 'woocommerce_subscription_validate_payment_meta', array( $this, 'validate_subscription_payment_meta' ), 10, 2 );
		}

	}

	/**
	 * Process the subscription.
	 *
	 * @param  WC_Order $order
	 * @return array
	 * @throws Exception
	 */
	protected function process_subscription( $order_id ) {
		$order = wc_get_order( $order_id );
		$payment_response = $this->process_subscription_payment( $order, $order->get_total() );

		$customer_cc_id = get_post_meta($order_id , '_zeus_customer_cc_id');//sendid
		$this->save_subscription_meta( $order_id, $customer_cc_id );

		if ( is_wp_error( $payment_response ) ) {
			//Error Message
//			throw new Exception( $payment_response->get_error_message() );
		} else {
			// Remove cart
			WC()->cart->empty_cart();

			// Return thank you page redirect
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		}
	}

	/**
	 * Store the customer and card IDs on the order and subscriptions in the order.
	 *
	 * @param int $order_id
	 * @param string $customer_cc_id as sendid
	 */
	protected function save_subscription_meta( $order_id, $customer_cc_id ) {

		$customer_cc_id = wc_clean( $customer_cc_id );

		update_post_meta( $order_id, '_zeus_customer_cc_id', $customer_cc_id );

		// Also store it on the subscriptions being purchased in the order
		foreach( wcs_get_subscriptions_for_order( $order_id ) as $subscription ) {
			update_post_meta( $subscription->id, '_zeus_customer_cc_id', $customer_cc_id );
		}
	}

	/**
	 * Process the payment.
	 *
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment( $order_id, $retry = true ) {
		// Processing subscription
		if ( class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order_id ) ) {
			return $this->process_subscription( $order_id, $retry );
		// Processing regular product
		} else {
			return parent::process_payment( $order_id, $retry );
		}
	}

	/**
	 * process_subscription_payment function.
	 *
	 * @param WC_order $order
	 * @param int $amount (default: 0)
	 * @return bool|WP_Error
	 */
	public function process_subscription_payment( $order, $amount = 0 ) {
		include_once( 'includes/class-wc-gateway-zeus-request.php' );
		if ( 0 == $amount ) {
			// Payment complete
			$order->payment_complete();

			return true;
		}

		$customer_id = $order->user_id;
		if ( ! $customer_id ) {
			return new WP_Error( 'zeus_error', __( 'Customer not found', 'woocommerce' ) );
		}

		$customer_cc_id = get_post_meta( $order->id, '_zeus_customer_cc_id', true );
		$connect_url = WC_ZEUS_SECURE_API_URL;

		$post_data = array();
		$post_data['clientip'] = $this->authentication_clientip;
		$post_data['cardnumber'] = '8888888888882';
		$post_data['expyy'] = '00';
		$post_data['expmm'] = '00';
		$post_data['send'] = 'mall';
		$post_data['telno'] = $order->billing_phone;
		$post_data['email'] = $order->billing_email;
		$post_data['sendid'] = $customer_cc_id;
		$post_data['sendpoint'] = 'wc-'.$order->id;
		$post_data['printord'] = 'yes';

		$zeus_request = new WC_Gateway_Zeus_Request();
		$contents = $zeus_request->send_curl_request($post_data, $connect_url);

		$response = array();
		$response = explode("\n", $contents);
		
		if($response[0] == 'Success_order'){
			// Mark as processing (we're awaiting the shipment)
			$order->update_status( 'processing', __( 'Finished Payment. Order Number:', 'woo-zeus' ).$response[1] );
			$order->payment_complete( $response[1] );
		}else{
			parent::notice_invalid($response[0], $order, 'Auth');
		}
		return $response;
	}

	/**
	 * scheduled_subscription_payment function.
	 *
	 * @param float $amount_to_charge The amount to charge.
	 * @param $order WC_Order The WC_Order object of the order which the subscription was purchased in.
	 * @param $product_id int The ID of the subscription product for which this payment relates.
	 * @access public
	 * @return void
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $order, $product_id ) {
		$result = $this->process_subscription_payment( $order, $amount_to_charge );

		if ( is_wp_error( $result ) ) {
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );
		}else{
			WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
		}
	}

	/**
	 * Update the customer_id for a subscription after using Simplify to complete a payment to make up for.
	 * an automatic renewal payment which previously failed.
	 *
	 * @param WC_Subscription $subscription The subscription for which the failing payment method relates.
	 * @param WC_Order $renewal_order The order which recorded the successful payment (to make up for the failed automatic payment).
	 */
	public function update_failing_payment_method( $subscription, $renewal_order ) {
		update_post_meta( $subscription->id, '_zeus_customer_cc_id', get_post_meta( $renewal_order->id, '_zeus_customer_cc_id', true ) );
	}

	/**
	 * Include the payment meta data required to process automatic recurring payments so that store managers can.
	 * manually set up automatic recurring payments for a customer via the Edit Subscription screen in Subscriptions v2.0+.
	 *
	 * @since 2.4
	 * @param array $payment_meta associative array of meta data required for automatic payments
	 * @param WC_Subscription $subscription An instance of a subscription object
	 * @return array
	 */
	public function add_subscription_payment_meta( $payment_meta, $subscription ) {

		$payment_meta[ $this->id ] = array(
			'post_meta' => array(
				'_zeus_customer_cc_id' => array(
					'value' => get_post_meta( $subscription->id, '_zeus_customer_cc_id', true ),
					'label' => 'Zeus Customer CC ID(sendid)',
				),
			),
		);

		return $payment_meta;
	}
	/**
	 * Don't transfer customer meta to resubscribe orders.
	 *
	 * @access public
	 * @param int $resubscribe_order The order created for the customer to resubscribe to the old expired/cancelled subscription
	 * @return void
	 */
	public function delete_resubscribe_meta( $resubscribe_order ) {
		delete_post_meta( $resubscribe_order->id, '_zeus_customer_cc_id' );
		return $resubscribe_order;
	}
	/**
	 * Don't transfer sendid meta to renewal orders.
	 *
	 * @access public
	 * @param int $resubscribe_order The order created for the customer to resubscribe to the old expired/cancelled subscription
	 * @return void
	 */
	public function delete_renewal_meta( $renewal_order ) {
		delete_post_meta( $resubscribe_order->id, '_zeus_customer_cc_id' );
		return $renewal_order;
	}

}