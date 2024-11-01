<?php
/**
 * エラーメッセージハンドラ
 *
 * エラー詳細(ErrInfo)を、対応する日本語メッセージに置換します。
 *
 */
class ErrorHandler{

	var $messageMap = null;

	function ErrorHandler(){
		global $messageMap;
		$messageMap = array(
			'02130640' => __('There may be an error in your payment details or a technical error in the connection.Please confirm your payment details again or contact ZEUS customer support center.<br />
    Aoyama First Bldg. 2-1-1, Shibuya, Shibuya-ku, Tokyo, 150-0002 Japan<br />
    Customer Support (24hours/day, 7days/week)<br />
    Tel: +81-3-4334-0500)<br />
    E-mail: support@cardservice.co.jp<br />', 'woo-zeus' ),
			'02130922' => __('There may be an error in your payment details .Please confirm your payment details again or contact ZEUS customer support center.<br />
ZEUS Co., Ltd. Customer Support Center (24/7) TEL: 0570-02-3939 or 03-4334-0500', 'woo-zeus' ),
			'005' => __('We are unable to accept the card. Please use another card or contact your credit card issuing bank directly.', 'woo-zeus' ),
			'006' => __('We are unable to accept the card. Please use another card or contact your credit card issuing bank directly.', 'woo-zeus' ),
			'007' => __('Expiration date may be incorrect. Please confirm your payment details again or contact ZEUS customer support center.<br />
    Aoyama First Bldg. 2-1-1, Shibuya, Shibuya-ku, Tokyo, 150-0002 Japan<br />
    Customer Support (24hours/day, 7days/week)<br />
    Tel: +81-3-4334-0500)<br />
    E-mail: support@cardservice.co.jp', 'woo-zeus' ),
			'008' => __('There may be an error in the number of installment payment you specified. Please confirm your payment details again or contact ZEUS customer support center.<br />
    Aoyama First Bldg. 2-1-1, Shibuya, Shibuya-ku, Tokyo, 150-0002 Japan<br />
    Customer Support (24hours/day, 7days/week)<br />
    Tel: +81-3-4334-0500)<br />
    E-mail: support@cardservice.co.jp', 'woo-zeus' ),
			'026' => __('The credit card number is incorrect. Please confirm your card number or contact Customer Support for detail.<br />
    Aoyama First Bldg. 2-1-1, Shibuya, Shibuya-ku, Tokyo, 150-0002 Japan<br />
    Customer Support (24hours/day, 7days/week)<br />
    Tel: +81-3-4334-0500)<br />
    E-mail: support@cardservice.co.jp', 'woo-zeus' ),
			'028' => __('The credit card has expired. Please confirm your credit card expiring date or contact Customer Support for detail.<br />
    Aoyama First Bldg. 2-1-1, Shibuya, Shibuya-ku, Tokyo, 150-0002 Japan<br />
    Customer Support (24hours/day, 7days/week)<br />
    Tel: +81-3-4334-0500)<br />
    E-mail: support@cardservice.co.jp', 'woo-zeus' )
		);
	}

	function getMessage( $errorCode ){
		global $messageMap;
		if( array_key_exists( $errorCode , $messageMap )){
			return $messageMap[ $errorCode ];
		}
		return __('This creditcard is not able to use. Please try again with another card.<br />
Contact for credit card payment<br />
Zeus Co., Ltd.<br />
    Aoyama First Bldg. 2-1-1, Shibuya, Shibuya-ku, Tokyo, 150-0002 Japan<br />
    Customer Support (24hours/day, 7days/week)<br />
    Tel: +81-3-4334-0500)<br />
    E-mail: support@cardservice.co.jp', 'woo-zeus' );
	}
}
?>