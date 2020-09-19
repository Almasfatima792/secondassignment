<?php
/**
 * @version 3.0.0
 * 
 * @var WC_Payment_Gateway_Stripe[] $gateways
 */
?>
<div class="wc-stripe-banner-checkout">
	<span class="banner-title"><?php _e('Express Checkout', 'woo-stripe-payment')?></span>
	<ul class="wc_stripe_checkout_banner_gateways" style="list-style: none">
		<?php foreach($gateways as $gateway):?>
			<li class="wc-stripe-checkout-banner-gateway banner_payment_method_<?php echo $gateway->id?>">
				
			</li>
		<?php endforeach;?>
	</ul>
</div>