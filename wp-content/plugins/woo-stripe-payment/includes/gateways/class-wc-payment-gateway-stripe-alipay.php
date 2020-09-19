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
class WC_Payment_Gateway_Stripe_Alipay extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Charge_Trait;

	public function __construct() {
		$this->local_payment_type = 'alipay';
		$this->currencies         = array( 'AUD', 'CAD', 'EUR', 'GBP', 'HKD', 'JPY', 'SGD', 'USD', 'CNY', 'NZD', 'MYR' );
		$this->id                 = 'stripe_alipay';
		$this->tab_title          = __( 'Alipay', 'woo-stripe-payment' );
		$this->template_name      = 'local-payment.php';
		$this->token_type         = 'Stripe_Local';
		$this->method_title       = __( 'Alipay', 'woo-stripe-payment' );
		$this->method_description = __( 'Alipay gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = wc_stripe()->assets_url( 'img/alipay.svg' );
		$this->order_button_text  = $this->get_order_button_text( __( 'Alipay', 'woo-stripe-payment' ) );
		parent::__construct();
	}

	/**
	 * @param string $currency
	 * @param string $billing_country
	 *
	 * @return bool
	 */
	public function validate_local_payment_available( $currency, $billing_country ) {
		$country          = wc_stripe()->account_settings->get_option( 'country' );
		$default_currency = wc_stripe()->account_settings->get_option( 'default_currency' );

		// https://stripe.com/docs/sources/alipay#create-source
		// Currency must be one of the allowed values
		if ( in_array( $currency, $this->currencies ) ) {
			// If merchant's country is DK, NO, SE, or CH, currency must be EUR.
			if ( in_array( $country, array( 'DK', 'NO', 'SE', 'CH' ) ) ) {
				return $currency === 'EUR';
			} else {
				// For all other countries, Ali pay is available if currency is CNY or
				// currency matches merchant's default currency
				return $currency === 'CNY' || $currency === $default_currency;
			}
		}

		return false;
	}

	protected function get_payment_description() {
		return __( 'Gateway will appear when store currency is CNY, or currency matches merchant\'s 
					default Stripe currency. For merchants located in DK, NO, SE, & CH, currency must be EUR.', 'woo-stripe-payment' );
	}
}

