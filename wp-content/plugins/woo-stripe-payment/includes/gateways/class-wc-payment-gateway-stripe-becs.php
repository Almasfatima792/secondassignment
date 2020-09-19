<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class WC_Payment_Gateway_Stripe_Becs
 *
 * @since 3.1.7
 * @package Stripe/Gateways
 * @author PaymentPlugins
 */
class WC_Payment_Gateway_Stripe_BECS extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Intent_Trait;

	protected $payment_method_type = 'au_becs_debit';

	public function __construct() {
		$this->local_payment_type = 'au_becs_debit';
		$this->currencies         = array( 'AUD' );
		$this->countries          = array( 'AU' );
		$this->id                 = 'stripe_becs';
		$this->tab_title          = __( 'BECS', 'woo-stripe-payment' );
		$this->method_title       = __( 'BECS', 'woo-stripe-payment' );
		$this->method_description = __( 'BECS direct debit gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = ''; //wc_stripe()->assets_url( 'img/becs.svg' );
		$this->order_button_text  = $this->get_order_button_text( __( 'BECS', 'woo-stripe-payment' ) );
		parent::__construct();

		$this->local_payment_description = sprintf(
			__(
				'By providing your bank account details and confirming this payment, you agree to this 
		Direct Debit Request and the %sDirect Debit Request service agreement%s, and authorise Stripe Payments Australia Pty Ltd ACN 160 180 343 Direct 
		Debit User ID number 507156 (“Stripe”) to debit your account through the Bulk Electronic Clearing System (BECS) on behalf of Stripe Press 
		(the "Merchant") for any amounts separately communicated to you by the Merchant. You certify that you are either an account holder or an 
		authorised signatory on the account listed above.',
				'woo-stripe-payment'
			)
			, '<a href="https://stripe.com/au-becs-dd-service-agreement/legal" target="_blank">', '</a>' );
	}
}
