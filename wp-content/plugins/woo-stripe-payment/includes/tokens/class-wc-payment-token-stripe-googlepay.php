<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Token_Stripe_CC' ) ) {
	return;
}

/**
 *
 * @author PaymentPlugins
 * @package Stripe/Tokens
 *
 */
class WC_Payment_Token_Stripe_GooglePay extends WC_Payment_Token_Stripe_CC {

	use WC_Payment_Token_Source_Trait;

	protected $type = 'Stripe_GooglePay';

	protected $stripe_payment_type = 'source';

	public function get_formats() {
		return array(
			       'gpay_name' => array(
				       'label'   => __( 'Gateway Name', 'woo-stripe-payment' ),
				       'example' => 'Visa 1111 (Google Pay)',
				       'format'  => '{brand} {last4} (Google Pay)'
			       )
		       ) + parent::get_formats();
	}
}
