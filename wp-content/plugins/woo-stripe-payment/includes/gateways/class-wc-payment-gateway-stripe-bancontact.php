<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway_Stripe_Local_Payment' ) ) {
	return;
}

/**
 *
 * @package Stripe/Gateways
 * @author PaymentPlugins
 *
 */
class WC_Payment_Gateway_Stripe_Bancontact extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Charge_Trait;

	public function __construct() {
		$this->synchronous        = false;
		$this->local_payment_type = 'bancontact';
		$this->currencies         = array( 'EUR' );
		$this->countries          = array( 'BE' );
		$this->id                 = 'stripe_bancontact';
		$this->tab_title          = __( 'Bancontact', 'woo-stripe-payment' );
		$this->template_name      = 'local-payment.php';
		$this->token_type         = 'Stripe_Local';
		$this->method_title       = __( 'Bancontact', 'woo-stripe-payment' );
		$this->method_description = __( 'Bancontact gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = wc_stripe()->assets_url( 'img/bancontact.svg' );
		$this->order_button_text  = $this->get_order_button_text( __( 'Bancontact', 'woo-stripe-payment' ) );
		parent::__construct();
	}
}
