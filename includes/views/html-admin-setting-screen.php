<?php global $woocommerce; ?>
<form id="wc-zeus-setting-form" method="post" action=""  enctype="multipart/form-data">
<?php wp_nonce_field( 'my-nonce-key','wc-zeus-setting');?>
<h3><?php echo __( 'Zeus Payment Method', 'woo-zeus' );?></h3>
<table class="form-table">
<?php 
	$zeus_paymethod = array(
		'cc' => __( 'Credit Card', 'woo-zeus' ),
		'cs' => __( 'Convenience store', 'woo-zeus' ),
		'bt' => __( 'Bank Transfer payment', 'woo-zeus' ),
		'pe' => __( 'Pay-Easy Payment', 'woo-zeus' ),
		'cp' => __( 'Carrier payment', 'woo-zeus' ),
	);
	foreach($zeus_paymethod as $key => $value){
		$payment_detail = sprintf( __( 'Please check it if you want to use the payment method of %s', 'woo-zeus' ),$value);
		$payment_str = 'wc-zeus-'.$key;
		$options = get_option($payment_str);
		echo '
<tr valign="top">
	<th scope="row" class="titledesc">
	    <label for="woocommerce_input_'.$key.'">'.$value.'</label>
    </th>
    <td class="forminp"><input type="checkbox" name="'.$payment_str.'" value="1" ';
    checked( $options, 1 );
    echo '>'.$value.'
    <p class="description">'.$payment_detail.'</p></td>
</tr>';
	}?>
</table>

<p class="submit">
   <input name="save" class="button-primary" type="submit" value="<?php echo __( 'Save changes', 'woo-zeus' );?>">
</p>
</form>
