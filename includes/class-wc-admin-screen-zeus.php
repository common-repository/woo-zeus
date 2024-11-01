<?php
/**
 * Plugin Name: WooCommerce For Zeus Payments
 * Plugin URI: http://wordpress.org/plugins/wc4jp-zeus-payment/
 * Description: Woocommerce Zeus payment 
 * Version: 0.9.0
 * Author: Artisan Workshop
 * Author URI: http://wc.artws.info/
 * Requires at least: 4.0
 * Tested up to: 4.3
 *
 * Text Domain: wc4jp-zeus-payment
 * Domain Path: /i18n/
 *
 * @package wc4jp-zeus-payment
 * @category Setting
 * @author Artisan Workshop
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Admin_Screen_Zeus {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'wc_admin_zeus_menu' ) ,55);
		add_action( 'admin_notices', array( $this, 'zeus_ssl_check' ) );
		add_action( 'admin_init', array( $this, 'wc_setting_zeus_init') );
	}
	/**
	 * Admin Menu
	 */
	public function wc_admin_zeus_menu() {
		$page = add_submenu_page( 'woocommerce', __( 'Zeus Setting', 'woo-zeus' ), __( 'Zeus Setting', 'woo-zeus' ), 'manage_woocommerce', 'wc4jp-zeus-output', array( $this, 'wc_zeus_output' ) );
	}

	/**
	 * Admin Screen output
	 */
	public function wc_zeus_output() {
		$tab = ! empty( $_GET['tab'] ) && $_GET['tab'] == 'info' ? 'info' : 'setting';
		include( 'views/html-admin-screen.php' );
	}

	/**
	 * Admin page for Setting
	 */
	public function admin_zeus_setting_page() {
		include( 'views/html-admin-setting-screen.php' );
	}

	/**
	 * Admin page for infomation
	 */
	public function admin_zeus_info_page() {
		include( 'views/html-admin-info-screen.php' );
	}
	
      /**
       * Check if SSL is enabled and notify the user.
       */
      function zeus_ssl_check() {
		  if(isset($this->enabled)){
              if ( get_option( 'woocommerce_force_ssl_checkout' ) == 'no' && $this->enabled == 'yes' ) {
              echo '<div class="error"><p>' . sprintf( __('Zeus Commerce is enabled and the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'woo-zeus' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '</p></div>';
            }
		  }
	  }

	function wc_setting_zeus_init(){
		if( isset( $_POST['wc-zeus-setting'] ) && $_POST['wc-zeus-setting'] ){
			if( check_admin_referer( 'my-nonce-key', 'wc-zeus-setting')){
				$zeus_methods = array('cc','cs','bt','pe','cp');
				//All payment method setting
				foreach($zeus_methods as $method){
					$zeus_post_str = 'wc-zeus-'.$method;
					$zeus_method_str = 'woocommerce_gmopg_'.$method;
					$zeus_method_setting_str = $zeus_method_str.'_settings';
					$wc4jp_zeus = get_option( $zeus_method_setting_str );

					if(isset($_POST[$zeus_post_str]) && $_POST[$zeus_post_str]){
						update_option( $zeus_post_str, $_POST[$zeus_post_str]);
						if(isset($wc4jp_zeus)){
							$wc4jp_zeus['enabled'] = 'yes';
							update_option( $zeus_method_setting_str, $wc4jp_zeus);
						}
					}else{
						update_option( $zeus_post_str , '');
						if(isset($wc4jp_zeus)){
							$wc4jp_zeus['enabled'] = 'no';
							update_option( $zeus_method_setting_str , $wc4jp_zeus);
						}
					}
				}
/*				//Credit Card payment method
				$woocommerce_zeus_cc = get_option('woocommerce_zeus_cc_settings');
				if(isset($_POST['zeus_cc']) && $_POST['zeus_cc']){
					update_option( 'wc-zeus-cc', $_POST['zeus_cc']);
					if(isset($woocommerce_zeus_cc)){
						$woocommerce_zeus_cc['enabled'] = 'yes';
						update_option( 'woocommerce_zeus_cc_settings', $woocommerce_zeus_cc);
					}
				}else{
					update_option( 'wc-zeus-cc', '');
					if(isset($woocommerce_zeus_cc)){
						$woocommerce_zeus_cc['enabled'] = 'no';
						update_option( 'woocommerce_zeus_cc_settings', $woocommerce_zeus_cc);
					}
				}
				//Convenience store payment method
					$woocommerce_zeus_cs = get_option('woocommerce_zeus_cs_settings');
				if(isset($_POST['zeus_cs']) && $_POST['zeus_cs']){
					update_option( 'wc-zeus-cs', $_POST['zeus_cs']);
					if(isset($woocommerce_zeus_cs)){
						$woocommerce_zeus_cs['enabled'] = 'yes';
						update_option( 'woocommerce_zeus_cs_settings', $woocommerce_zeus_cs);
					}
				}else{
					update_option( 'wc-zeus-cs', '');
					if(isset($woocommerce_zeus_cs)){
						$woocommerce_zeus_cs['enabled'] = 'no';
						update_option( 'woocommerce_zeus_cs_settings', $woocommerce_zeus_cs);
					}
				}
				//Bank transfer payment method
					$woocommerce_zeus_bt = get_option('woocommerce_zeus_bt_settings');
				if(isset($_POST['zeus_bt']) && $_POST['zeus_bt']){
					update_option( 'wc-zeus-bt', $_POST['zeus_bt']);
					if(isset($woocommerce_zeus_bt)){
						$woocommerce_zeus_bt['enabled'] = 'yes';
						update_option( 'woocommerce_zeus_bt_settings', $woocommerce_zeus_bt);
					}
				}else{
					update_option( 'wc-zeus-bt', '');
					if(isset($woocommerce_zeus_bt)){
						$woocommerce_zeus_bt['enabled'] = 'no';
						update_option( 'woocommerce_zeus_bt_settings', $woocommerce_zeus_bt);
					}
				}
				//Pay-easy payment method
					$woocommerce_zeus_pe = get_option('woocommerce_zeus_pe_settings');
				if(isset($_POST['zeus_pe']) && $_POST['zeus_pe']){
					update_option( 'wc-zeus-pe', $_POST['zeus_pe']);
					if(isset($woocommerce_zeus_pe)){
						$woocommerce_zeus_pe['enabled'] = 'yes';
						update_option( 'woocommerce_zeus_pe_settings', $woocommerce_zeus_pe);
					}
				}else{
					update_option( 'wc-zeus-pe', '');
					if(isset($woocommerce_zeus_pe)){
						$woocommerce_zeus_pe['enabled'] = 'no';
						update_option( 'woocommerce_zeus_pe_settings', $woocommerce_zeus_pe);
					}
				}
				//Carrier payment method
					$woocommerce_zeus_cp = get_option('woocommerce_zeus_cp_settings');
				if(isset($_POST['zeus_cp']) && $_POST['zeus_cp']){
					update_option( 'wc-zeus-cp', $_POST['zeus_cp']);
					if(isset($woocommerce_zeus_cp)){
						$woocommerce_zeus_cp['enabled'] = 'yes';
						update_option( 'woocommerce_zeus_cp_settings', $woocommerce_zeus_cp);
					}
				}else{
					update_option( 'wc-zeus-cp', '');
					if(isset($woocommerce_zeus_cp)){
						$woocommerce_zeus_cp['enabled'] = 'no';
						update_option( 'woocommerce_zeus_cp_settings', $woocommerce_zeus_cp);
					}
				}*/
			}
		}
	}
}

new WC_Admin_Screen_Zeus();