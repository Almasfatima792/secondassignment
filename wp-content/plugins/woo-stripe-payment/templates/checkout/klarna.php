<?php
/**
 * @version 3.1.7
 *
 * @var WC_Payment_Gateway_Stripe_Klarna $gateway
 */
$payment_options = $gateway->get_option( 'payment_categories' );
?>
<div id="wc_stripe_local_payment_<?php echo $gateway->id ?>" style="display: none" data-active="<?php echo $gateway->is_local_payment_available() ?>">
    <ul class="stripe-klarna-categories">
		<?php foreach ( $gateway->get_payment_categories() as $category => $label ): ?>
			<?php if ( in_array( $category, $payment_options ) ): ?>
                <li id="klarna-category-<?php echo $category ?>" style="display:none">
                    <input type="radio" id="klarna_<?php echo $category ?>"
                           class="wc-stripe-klarna-category" name="klarna_category"
                           value="<?php echo $category ?>"/>
                    <label for="klarna_<?php echo $category ?>" class="wc-stripe-label-klarna-category"><?php echo $label ?></label>
                    <div id="klarna-instance-<?php echo $category ?>" class="klarna-instance-<?php echo $category ?>" style="display: none"></div>
                </li>
			<?php endif; ?>
		<?php endforeach; ?>
    </ul>
</div>