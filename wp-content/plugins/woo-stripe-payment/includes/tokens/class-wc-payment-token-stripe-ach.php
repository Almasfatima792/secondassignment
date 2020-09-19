<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.5
 * @package Stripe/Tokens
 * @author Payment Plugins
 *
 */
class WC_Payment_Token_Stripe_ACH extends WC_Payment_Token_Stripe {

	use WC_Payment_Token_Source_Trait;

	protected $type = 'Stripe_ACH';

	protected $stripe_payment_type = 'source';

	protected $stripe_data = array(
		'bank_name'      => '',
		'routing_number' => '',
		'last4'          => '',
	);

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Token_Stripe::details_to_props()
	 */
	public function details_to_props( $details ) {
		if ( isset( $details['ach_debit'] ) ) {
			$bank = $details['ach_debit'];
		} elseif ( $details instanceof \Stripe\BankAccount ) {
			$bank = $details;
		}
		$this->set_brand( $bank['bank_name'] );
		$this->set_bank_name( $bank['bank_name'] );
		$this->set_last4( $bank['last4'] );
		$this->set_routing_number( $bank['routing_number'] );
	}

	public function get_bank_name( $context = 'view' ) {
		return $this->get_prop( 'bank_name', $context );
	}

	public function get_routing_number( $context = 'view' ) {
		return $this->get_prop( 'routing_number', $context );
	}

	public function get_last4( $context = 'view' ) {
		return $this->get_prop( 'last4', $context );
	}

	public function set_bank_name( $value ) {
		$this->set_prop( 'bank_name', $value );
	}

	public function set_routing_number( $value ) {
		$this->set_prop( 'routing_number', $value );
	}

	public function set_last4( $value ) {
		$this->set_prop( 'last4', $value );
	}

	public function get_formats() {
		return array(
			'type_ending_in'    => array(
				'label'   => __( 'Type Ending In', 'woo-stripe-payment' ),
				'example' => 'Chase ending in 3434',
				'format'  => __( '{bank_name} ending in {last4}', 'woo-stripe-payment' ),
			),
			'name_masked_last4' => array(
				'label'   => __( 'Type Ending In', 'woo-stripe-payment' ),
				'example' => 'Chase **** 3434',
				'format'  => __( '{bank_name} **** {last4}', 'woo-stripe-payment' ),
			),
		);
	}

	public function get_html_classes() {
		return 'wc-stripe-ach';
	}
}
