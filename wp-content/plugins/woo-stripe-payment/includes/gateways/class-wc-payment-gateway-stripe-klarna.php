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
class WC_Payment_Gateway_Stripe_Klarna extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Charge_Trait;

	public function __construct() {
		$this->local_payment_type = 'klarna';
		$this->currencies         = array( 'EUR', 'SEK', 'NOK', 'DKK', 'GBP', 'USD' );
		$this->id                 = 'stripe_klarna';
		$this->tab_title          = __( 'Klarna', 'woo-stripe-payment' );
		$this->template_name      = 'local-payment.php';
		$this->token_type         = 'Stripe_Local';
		$this->method_title       = __( 'Klarna', 'woo-stripe-payment' );
		$this->method_description = __( 'Klarna gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = wc_stripe()->assets_url( 'img/klarna.svg' );
		$this->order_button_text  = $this->get_order_button_text( __( 'Klarna', 'woo-stripe-payment' ) );
		parent::__construct();
		$this->template_name = 'klarna.php';
	}

	public function get_required_parameters() {
		return array(
			'USD' => array( 'US' ),
			'EUR' => array( 'AT', 'FI', 'DE', 'NL' ),
			'DKK' => array( 'DK' ),
			'NOK' => array( 'NO' ),
			'SEK' => array( 'SE' ),
			'GBP' => array( 'GB' ),
		);
	}

	/**
	 * @param string $currency
	 * @param string $billing_country
	 *
	 * @return bool
	 */
	public function validate_local_payment_available( $currency, $billing_country ) {
		if ( $billing_country ) {
			$params = $this->get_required_parameters();

			return isset( $params[ $currency ] ) && array_search( $billing_country, $params[ $currency ] ) !== false;
		}

		return false;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway_Stripe::payment_fields()
	 */
	public function payment_fields() {
		// this might be an update checkout request. If so, update the source if it exists
		if ( is_ajax() && ( $order_id = absint( WC()->session->get( 'order_awaiting_payment' ) ) ) ) {
			$order = wc_get_order( $order_id );
			if ( $order->get_payment_method() === $this->id && ( $source_id = $order->get_meta( WC_Stripe_Constants::SOURCE_ID, true ) ) ) {
				$this->payment_object->get_gateway()->sources->mode( wc_stripe_order_mode( $order ) )->update( $source_id, $this->get_update_source_args( $order ) );
			}
		}
		parent::payment_fields();
	}

	public function enqueue_checkout_scripts( $scripts ) {
		wc_stripe()->scripts()->enqueue_script( 'klarna', 'https://x.klarnacdn.net/kp/lib/v1/api.js', array(), wc_stripe()->version(), true );
		parent::enqueue_checkout_scripts( $scripts );
	}

	public function get_local_payment_settings() {
		return wp_parse_args(
			array(
				'payment_categories' => array(
					'title'       => __( 'Payment Categories', 'woo-stripe-payment' ),
					'type'        => 'multiselect',
					'class'       => 'wc-enhanced-select',
					'options'     => $this->get_payment_categories(),
					'default'     => array_keys( $this->get_payment_categories() ),
					'desc_tip'    => true,
					'description' => __(
						'These are the payment categories that will be displayed on the checkout page if they are supported. Note, depending on the customer\'s billing country, not all enabled options may show.',
						'woo-stripe-payment'
					),
				),
			),
			parent::get_local_payment_settings()
		);
	}

	private function get_update_source_args( $order ) {
		$args = $this->get_source_args( $order, true );
		unset( $args['type'], $args['currency'], $args['statement_descriptor'], $args['redirect'], $args['klarna']['product'], $args['klarna']['locale'], $args['klarna']['custom_payment_methods'] );

		return $args;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway_Stripe_Local_Payment::get_source_args()
	 */
	public function get_source_args( $order, $update = false ) {
		if ( $update ) {
			/*
			 * Use customer if this is an update since customer object has been updated
			 * with all checkout form data in the update_order_review process.
			 */
			$object = WC()->customer;
		} else {
			$object = $order;
		}

		$args = array_merge_recursive(
			parent::get_source_args( $order ),
			array(
				'klarna' => array(
					'product'          => 'payment',
					'purchase_country' => $object->get_billing_country(),
					'first_name'       => $object->get_billing_first_name(),
					'last_name'        => $object->get_billing_last_name(),
				),
				'owner'  => array(
					'address' => array(
						'city'        => $object->get_billing_city(),
						'country'     => $object->get_billing_country(),
						'line1'       => $object->get_billing_address_1(),
						'line2'       => $object->get_billing_address_2(),
						'postal_code' => $object->get_billing_postcode(),
						'state'       => $object->get_billing_state(),
					),
				),
			)
		);
		if ( 'US' === $object->get_billing_country() ) {
			$args['klarna']['custom_payment_methods'] = 'payin4,installments';
		}
		$args['source_order'] = array();

		if ( ( $locale = get_locale() ) ) {
			$args['klarna']['locale'] = str_replace( '_', '-', $locale );
		}

		if ( $order->get_shipping_address_1() ) {
			$args['klarna']['shipping_first_name']       = $object->get_shipping_first_name();
			$args['klarna']['shipping_last_name']        = $object->get_shipping_last_name();
			$args['source_order']['shipping']['address'] = array(
				'city'        => $object->get_billing_city(),
				'country'     => $object->get_shipping_country(),
				'line1'       => $object->get_shipping_address_1(),
				'line2'       => $object->get_shipping_address_2(),
				'postal_code' => $object->get_shipping_postcode(),
				'state'       => $object->get_shipping_state(),
			);
		}

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			/**
			 *
			 * @var WC_Order_Item_Product $item
			 */
			$args['source_order']['items'][] = array(
				'type'        => 'sku',
				'amount'      => wc_stripe_add_number_precision( $item->get_subtotal(), $order->get_currency() ),
				'currency'    => $order->get_currency(),
				'quantity'    => $item->get_quantity(),
				'description' => $item->get_name(),
			);
		}
		// shipping
		if ( $order->get_shipping_total() ) {
			$args['source_order']['items'][] = array(
				'type'        => 'shipping',
				'amount'      => wc_stripe_add_number_precision( $order->get_shipping_total(), $order->get_currency() ),
				'currency'    => $order->get_currency(),
				'quantity'    => 1,
				'description' => __( 'Shipping', 'woo-stripe-payment' ),
			);
		}
		// discount
		if ( $order->get_discount_total() ) {
			$args['source_order']['items'][] = array(
				'type'        => 'discount',
				'amount'      => - 1 * wc_stripe_add_number_precision( $order->get_discount_total(), $order->get_currency() ),
				'currency'    => $order->get_currency(),
				'quantity'    => 1,
				'description' => __( 'Discount', 'woo-stripe-payment' ),
			);
		}
		// fees
		if ( $order->get_fees() ) {
			$fee_total = 0;
			foreach ( $order->get_fees() as $fee ) {
				$fee_total += wc_stripe_add_number_precision( $fee->get_total(), $order->get_currency() );
			}
			$args['source_order']['items'][] = array(
				'type'        => 'sku',
				'amount'      => $fee_total,
				'currency'    => $order->get_currency(),
				'quantity'    => 1,
				'description' => __( 'Fee', 'woo-stripe-payment' ),
			);
		}
		// tax
		if ( $order->get_total_tax() ) {
			$args['source_order']['items'][] = array(
				'type'        => 'tax',
				'amount'      => wc_stripe_add_number_precision( $order->get_total_tax() ),
				'description' => __( 'Tax', 'woo-stripe-payment' ),
				'quantity'    => 1,
				'currency'    => $order->get_currency(),
			);
		}

		return $args;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway_Stripe_Local_Payment::get_source_redirect_url()
	 */
	public function get_source_redirect_url( $source, $order ) {
		$klarna_categories                             = explode( ',', $source['klarna']['payment_method_categories'] );
		$klarna_categories                             = array_intersect( $klarna_categories, $this->get_option( 'payment_categories' ) );
		$source['klarna']['payment_method_categories'] = implode( $klarna_categories, ',' );
		$source['redirect']                            = add_query_arg( 'source', $order->get_meta( WC_Stripe_Constants::SOURCE_ID ), $this->get_local_payment_return_url( $order ) );

		return '#response=' . base64_encode( wp_json_encode( $source ) );
	}

	/**
	 *
	 * @return mixed
	 */
	public function get_payment_categories() {
		return apply_filters(
			'wc_stripe_klarna_payment_categries',
			array(
				'pay_now'       => __( 'Pay Now', 'woo-stripe-payment' ),
				'pay_later'     => __( 'Pay Later', 'woo-stripe-payment' ),
				'pay_over_time' => __(
					'Pay Over Time',
					'woo-stripe-payment'
				),
			)
		);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway_Stripe_Local_Payment::get_payment_description()
	 */
	public function get_payment_description() {
		return '<p>' .
		       sprintf( __( 'Click %1$shere%2$s for Klarna test payment methods.', 'woo-stripe-payment' ), '<a target="_blank" href="https://stripe.com/docs/sources/klarna#testing-klarna-payments">', '</a>' ) .
		       '</p>' . parent::get_payment_description();
	}

	public function get_localized_params() {
		$params                             = parent::get_localized_params();
		$params['messages']['klarna_error'] = __( 'Your purchase is not approved.', 'woo-stripe-payment' );

		return $params;
	}
}
