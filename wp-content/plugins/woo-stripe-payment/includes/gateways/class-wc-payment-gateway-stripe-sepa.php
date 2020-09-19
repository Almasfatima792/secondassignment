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
class WC_Payment_Gateway_Stripe_Sepa extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Charge_Trait;

	public function __construct() {
		$this->synchronous        = false;
		$this->local_payment_type = 'sepa_debit';
		$this->currencies         = array( 'EUR' );
		$this->id                 = 'stripe_sepa';
		$this->tab_title          = __( 'SEPA', 'woo-stripe-payment' );
		$this->template_name      = 'local-payment.php';
		$this->token_type         = 'Stripe_Local';
		$this->method_title       = __( 'Sepa', 'woo-stripe-payment' );
		$this->method_description = __( 'Sepa gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = wc_stripe()->assets_url( 'img/sepa.svg' );
		$this->order_button_text  = $this->get_order_button_text( __( 'SEPA', 'woo-stripe-payment' ) );
		parent::__construct();

		$this->local_payment_description = sprintf(
			__(
				'By providing your IBAN and confirming this payment, you are
			authorizing %s and Stripe, our payment service provider, to send instructions to your bank to debit your account
			and your bank to debit your account in accordance with those instructions. You are entitled to a refund from your bank under the
			terms and conditions of your agreement with your bank. A refund must be claimed within 8 weeks starting from the date on which your account was debited.',
				'woo-stripe-payment'
			),
			$this->get_option( 'company_name' )
		);
	}

	public function get_element_params() {
		return array_merge( parent::get_element_params(), array( 'supportedCountries' => array( 'SEPA' ) ) );
	}

	public function get_local_payment_settings() {
		return parent::get_local_payment_settings() + array(
			'company_name' => array(
				'title'       => __( 'Company Name', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => get_bloginfo( 'name' ),
				'desc_tip'    => true,
				'description' => __( 'The name of your company that will appear in the SEPA mandate.', 'woo-stripe-payment' ),
			),
		);
	}

	public function get_payment_description() {
		return parent::get_payment_description() .
			 sprintf( '<p><a target="_blank" href="https://stripe.com/docs/sources/sepa-debit#testing">%s</a></p>', __( 'SEPA Test Accounts', 'woo-stripe-payment' ) );
	}
}
