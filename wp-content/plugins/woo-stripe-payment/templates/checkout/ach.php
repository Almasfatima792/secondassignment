<?php
/**
 * @version 3.0.5
 * @var WC_Payment_Gateway_Stripe_ACH $gateway
 */
wc_stripe_hidden_field ( $gateway->metadata_key );
?>
<div id="wc-stripe-ach-container">
	<?php if('sandbox' === $gateway->get_plaid_environment()):?>
	<p><?php _e('sandbox testing credentials', 'woo-stripe-payment')?>:</p>
	<p><strong><?php _e('username', 'woo-stripe-payment')?></strong>:&nbsp;user_good</p>
	<p><strong><?php _e('password', 'woo-stripe-payment')?></strong>:&nbsp;pass_good</p>
	<p><strong><?php _e('pin', 'woo-stripe-payment')?></strong>:&nbsp;credential_good&nbsp;(<?php _e('when required', 'woo-stripe-payment')?>)</p>
	<?php endif;?>
</div>