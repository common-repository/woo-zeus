<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Generates requests to send to Zeus
 */
class WC_Gateway_Zeus_Request {

	/**
	 * Pointer to gateway making the request
	 * @var WC_Gateway_Zeus
	 */
	protected $gateway;

	/**
	 * Constructor
	 * @param WC_Gateway_Zeus $gateway
	 */
	public function __construct() {
//		$this->gateway    = $gateway;
//		$this->notify_url = WC()->api_request_url( 'WC_Gateway_Zeus' );
	}

	/**
	 * Send the Zeus request URL for an order to get response
	 * @param  WC_Order  $order
	 * @param  boolean $connect_url
	 * @return array
	 */
	public function send_zeus_request( $post_data, $order, $connect_url ) {
		global $woocommerce;
		$post_data['money'] = $order->order_total;
		$post_data['username'] = mb_convert_encoding($post_data['username'],'SJIS', 'auto');
		$post_data['telno'] = str_replace('-', '', $order->billing_phone);
		$post_data['email'] = $order->billing_email;
		$post_data['sendpoint'] = 'wc-'.$order->id;

		$contents = $this->send_curl_request($post_data, $connect_url);

		$response = array();
		$response_array = array();

		$response = explode("\n", $contents);
		if(substr($response[0],0,3)=='rel'){
			$response_array['rel'] = substr($response[0],4);
			$response_array['order_no'] = substr($response[1],9);
			$response_array['pay_no1'] = substr($response[2],8);
			$response_array['pay_no2'] = substr($response[3],8);
			$response_array['pay_url'] = substr($response[4],8);
			$response_array['pay_limit'] = substr($response[5],10);
			$response_array['error_code'] = substr($response[6],11);
		}else{
			$response_array['error_msg'] = $response[0];
		}
		return $response_array;
	}
	/**
	 * Send the Zeus request URL for an order complate and sales complate
	 * @param  array  $post_data
	 * @param  boolean $connect_url
	 * @return boolean
	 */
	public function send_zeus_complete_request( $post_data, $connect_url ) {
		$contents = $this->send_curl_request($post_data, $connect_url);
		return $contents;
	}

	/**
	 * User curl function for reqquest 
	 * @param  array  $post_data
	 * @param  boolean $connect_url
	 * @return array
	 */
	public function send_curl_request($post_data, $connect_url){
	//Curl Request send
		$curl_zeus_cs = curl_init($connect_url);
		curl_setopt($curl_zeus_cs,CURLOPT_POST, TRUE);
		curl_setopt($curl_zeus_cs,CURLOPT_POSTFIELDS, http_build_query($post_data));
		curl_setopt($curl_zeus_cs,CURLOPT_SSL_VERIFYPEER, FALSE);  // For Self-signed certificate
		curl_setopt($curl_zeus_cs,CURLOPT_SSL_VERIFYHOST, FALSE);  //
		curl_setopt($curl_zeus_cs,CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl_zeus_cs,CURLOPT_COOKIEJAR,      'cookie');
		curl_setopt($curl_zeus_cs,CURLOPT_COOKIEFILE,     'tmp');
		curl_setopt($curl_zeus_cs,CURLOPT_FOLLOWLOCATION, TRUE);
		//Get response
		$contents = '';
		$contents = curl_exec($curl_zeus_cs);

		curl_close($curl_zeus_cs);
		return $contents;
	}

	//1.EnrolReq first time
	public function send_zeus_enrol_request( $post_request, $post_data, $order, $connect_url ) {
		$post_xml = $this->array2xml4zeus_cc($post_request, $post_data);
		$result = $this->send_post_xml($post_xml, $connect_url);

		return $result;
	}
	//5.AuthReq send request
	public function send_auth_request($xid, $PaRes, $connect_url){
		$post_request['service'] = 'secure_link_3d';
		$post_request['action'] = 'authentication';
		$post_data['xid'] = $xid;
		$post_data['PaRes'] = 'yes';
		// Set XML Data
		$xmlstr = '<?xml version="1.0" encoding="utf-8"?><request></request>';
		$xml = new SimpleXMLElement($xmlstr);
		$xml->addAttribute('service', $post_request['service']);
		$xml->addAttribute('action', $post_request['action']);
		foreach($post_data as $key => $value){
			$xml -> addChild($key, $value);
		}
		$post_xml = $xml -> asXML();
		
		$result = $this->send_post_xml($post_xml, $connect_url);

		return $result;
	}
	//7.PayReq send request
	public function send_zeus_3dpay_request($xid, $connect_url){
		$post_request['action'] = 'payment';
		$post_data['xid'] = $xid;
//		$post_data['print_am'] = 'yes';
//		$post_data['print_addtion_value'] = 'yes';
		// Set XML Data
		$xmlstr = '<?xml version="1.0" encoding="utf-8"?><request></request>';
		$xml = new SimpleXMLElement($xmlstr);
		$xml->addAttribute('service', $post_request['service']);
		$xml->addAttribute('action', $post_request['action']);
		foreach($post_data as $key => $value){
			$xml -> addChild($key, $value);
		}
		$post_xml = $xml -> asXML();
		
		$result = $this->send_post_xml($post_xml, $connect_url);

		return $result;
	}
	//Direct Payment first time when 3D secure is outside
	public function send_zeus_direct_pay_request( $post_request, $post_data, $order, $connect_url ) {
		$post_xml = $this->array2xml4zeus_cc($post_request, $post_data);
		$result = $this->send_post_xml($post_xml, $connect_url);

		return $result;
	}
	//PayReq send request for non 3D secure
	public function send_zeus_pay_request($post_request, $post_data, $order, $connect_url){
		$post_xml = $this->array2xml4zeus_cc($post_request, $post_data);
		$result = $this->send_post_xml($post_xml, $connect_url);

		return $result;
	}
	/**
	* @param string $name
	* @param Change to XML for Zeus Credit Card $post_data as array
	* @return string $str 
	*/
	public function array2xml4zeus_cc($post_request, $post_data){
		$xmlstr = '<?xml version="1.0" encoding="utf-8"?><request></request>';
		$xml = new SimpleXMLElement($xmlstr);
		$xml->addAttribute('service', $post_request['service']);
		$xml->addAttribute('action', $post_request['action']);
		//foreach($post_data as $key1 => $value1){
		//Authentication XML
		$xml_authentication = $xml -> addChild('authentication');
		foreach($post_data['authentication'] as $key => $value){
			$xml_authentication -> addChild($key, $value);
		}
		//Card XML
		$check = $post_data['card']['history'];
		$xml_card = $xml -> addChild('card');
		foreach($post_data['card'] as $key => $value){
			if(is_array($value)){
				if(isset($check)){
					$xml_card_history = $xml_card -> addChild('history');
					$xml_card_history->addAttribute('action', $value['action']);
					$xml_card_history -> addChild('key', $value['key']);
				}else{
					$xml_card_expires = $xml_card -> addChild('expires');
					$xml_card_expires -> addChild('year', $value['year']);
					$xml_card_expires -> addChild('month', $value['month']);
				}
			}else{
        		$xml_card -> addChild($key, $value);
			}
		}
		//Payment XML
		$xml_payment = $xml -> addChild('payment');
		foreach($post_data['payment'] as $key => $value){
			$xml_payment -> addChild($key, $value);
		}
		//User XML
		$xml_user = $xml -> addChild('user');
		foreach($post_data['user'] as $key => $value){
			if($key == 'telno'){
			    $xml_user_tel = $xml_user -> addChild('telno', $value['telno']);
			    $xml_user_tel->addAttribute('validation', $value['validation']);
		    }elseif($key == 'email'){
			    $xml_user_tel = $xml_user -> addChild('email', $value['email']);
			    $xml_user_tel->addAttribute('language', $value['language']);
		    }else{
	        	$xml_user -> addChild($key, $value);
	        }
	    }
		//Uniq Key XML
		$xml_uniq_key = $xml -> addChild('uniq_key');
		foreach($post_data['uniq_key'] as $key => $value){
			$xml_uniq_key -> addChild($key, $value);
		}
		return $xml -> asXML();
	}
	//
	public function send_post_xml($post_xml, $connect_url){
		$send_context = stream_context_create(
			array(
				'http' => array(
					'method' => "POST",
					'header' => "Content-Type: text/xml",
					'content' => $post_xml
				)
			)
		);

		$result = new SimpleXMLElement(file_get_contents($connect_url, false, $send_context));

		return $result;
	}

	//make post_request for Credit Card
	public function make_post_request_cc($order, $zeus_td_secure){
		$post_request = array();
		if($zeus_td_secure == 'yes'){
			$post_request['service'] = 'secure_link_3d';
			$post_request['action'] = 'enroll';
		}else{
			$post_request['service'] = 'secure_link';
			$post_request['action'] = 'payment';
		}
		return $post_request;
	}

	//make post_data for Credit Card
	public function make_post_data_cc($order, $this_setting, $zeus_stored_cc){
		$post_data = array();
		$post_data['authentication']['clientip'] = $this_setting['clientip'];
		$post_data['authentication']['key'] = $this_setting['key'];
		$post_data['payment']['amount'] = $order->order_total;
		if($this_setting['payment_installments'] == 'no'){
			$post_data['payment']['count'] = '01';
		}else{
			$post_data['payment']['count'] = $this->get_post('zeus-payment-count');
		}
		$post_data['user']['telno']['telno'] = str_replace('-','',$order->billing_phone);
		$post_data['user']['telno']['validation'] = 'permissive';
		$post_data['user']['email']['email'] = $order->billing_email;
		$post_data['user']['email']['language'] = 'japanese';
		$post_data['uniq_key']['sendpoint'] = 'wc-'.$order->id;

//		$zeus_stored_cc = $this->user_has_stored_data( $order->user_id );
//		$order->add_order_note(serialize($zeus_stored_cc));

		$zeus_use_stored_payment_info = $this->get_post('zeus-use-stored-payment-info');

		if($zeus_use_stored_payment_info == 'yes'){
			$post_data['card']['history']['key'] = 'sendid';
			$post_data['card']['history']['action'] = 'send_email';
			$post_data['uniq_key']['sendid'] = $this->get_post( 'zeus-token-cc' );
			//Card CVV
			$post_data['card']['cvv'] = $this->get_post( '-card-cvc' );
		}else{
			//First Time or New Card payment
			$post_data['card']['number'] = str_replace(array(" ", "　"), "", $this->get_post( '-card-number' ));
			//Edit Card Expire Data
			$card_valid_term = str_replace(array(" ","/" ), "", $this->get_post( '-card-expiry' ) );
			$post_data['card']['expires']['year'] = '20'.substr($card_valid_term, -2);
			$post_data['card']['expires']['month'] = substr($card_valid_term, 0, -2);
			$post_data['card']['name'] = $this->get_post( 'card_holder_name' );
			//Card CVV
			$post_data['card']['cvv'] = $this->get_post( '-card-cvc' );
			if($this_setting['store_card_info'] == 'yes'){
				$post_data['uniq_key']['sendid'] = $order->user_id.'-'.$order->id;
			}
		}
		return $post_data;
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
