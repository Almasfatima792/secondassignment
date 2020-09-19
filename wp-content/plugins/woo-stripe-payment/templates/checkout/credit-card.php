<?php
/**
 * @version 3.0.0
 * 
 * @var WC_Payment_Gateway_Stripe_CC $gateway
 */
?>
<?php if($gateway->is_custom_form_active()):?>
	<div id="wc-stripe-cc-custom-form">
		<?php wc_stripe_get_template($gateway->get_custom_form_template(), ['gateway' => $gateway])?>
	</div>
<?php else:?>
	<div id="wc-stripe-card-element"></div>
<?php endif;?>
<?php if($gateway->show_save_source()):?>
<div class="wc-stripe-save-source">
	<label class="save-source-label"><?php _e('Save Card', 'woo-stripe-payment')?></label>
	<label class="checkbox"> <input type="checkbox"
		id="<?php echo $gateway->save_source_key?>"
		name="<?php echo $gateway->save_source_key?>" value="yes" /> <span
		class="save-source-checkbox"></span>
	</label>
</div>
<?php endif;?>