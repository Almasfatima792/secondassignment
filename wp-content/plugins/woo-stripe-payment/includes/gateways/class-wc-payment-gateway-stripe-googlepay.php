<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway_Stripe' ) ) {
	return;
}

/**
 *
 * @author PaymentPlugins
 * @since 3.0.0
 * @package Stripe/Gateways
 */
class WC_Payment_Gateway_Stripe_GooglePay extends WC_Payment_Gateway_Stripe {

	use WC_Stripe_Payment_Charge_Trait;

	public function __construct() {
		$this->id                 = 'stripe_googlepay';
		$this->tab_title          = __( 'Google Pay', 'woo-stripe-payment' );
		$this->template_name      = 'googlepay.php';
		$this->token_type         = 'Stripe_GooglePay';
		$this->method_title       = __( 'Stripe Google Pay', 'woo-stripe-payment' );
		$this->method_description = __( 'Google Pay gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->has_digital_wallet = true;
		parent::__construct();
		$this->icon = wc_stripe()->assets_url( 'img/' . $this->get_option( 'icon' ) . '.svg' );
	}

	public function init_supports() {
		parent::init_supports();
		$this->supports[] = 'wc_stripe_cart_checkout';
		$this->supports[] = 'wc_stripe_product_checkout';
		$this->supports[] = 'wc_stripe_banner_checkout';
		$this->supports[] = 'wc_stripe_mini_cart_checkout';
	}

	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->enqueue_script(
			'googlepay-checkout',
			$scripts->assets_url( 'js/frontend/googlepay-checkout.js' ),
			array(
				$scripts->get_handle( 'wc-stripe' ),
				$scripts->get_handle( 'gpay' ),
			),
			wc_stripe()->version(),
			true
		);
		$scripts->localize_script( 'googlepay-checkout', $this->get_localized_params() );
	}

	public function enqueue_product_scripts( $scripts ) {
		$scripts->enqueue_script(
			'googlepay-product',
			$scripts->assets_url( 'js/frontend/googlepay-product.js' ),
			array(
				$scripts->get_handle( 'wc-stripe' ),
				$scripts->get_handle( 'gpay' ),
			),
			wc_stripe()->version(),
			true
		);
		$scripts->localize_script( 'googlepay-product', $this->get_localized_params() );
	}

	public function enqueue_cart_scripts( $scripts ) {
		$scripts->enqueue_script(
			'googlepay-cart',
			$scripts->assets_url( 'js/frontend/googlepay-cart.js' ),
			array(
				$scripts->get_handle( 'wc-stripe' ),
				$scripts->get_handle( 'gpay' ),
			),
			wc_stripe()->version(),
			true
		);
		$scripts->localize_script( 'googlepay-cart', $this->get_localized_params() );
	}

	public function enqueue_admin_scripts() {
		wp_register_script( 'gpay-external', wc_stripe()->scripts()->global_scripts['gpay'], array(), wc_stripe()->version(), true );
		wp_enqueue_script(
			'wc-stripe-gpay-admin',
			wc_stripe()->assets_url( 'js/admin/googlepay.js' ),
			array(
				'gpay-external',
				'wc-stripe-admin-settings',
			),
			wc_stripe()->version(),
			true
		);
	}

	public function get_localized_params() {
		$data = array_merge_recursive(
			parent::get_localized_params(),
			array(
				'environment'       => wc_stripe_mode() === 'test' ? 'TEST' : 'PRODUCTION',
				'merchant_id'       => wc_stripe_mode() === 'test' ? '' : $this->get_option( 'merchant_id' ),
				'merchant_name'     => $this->get_option( 'merchant_name' ),
				'button_color'      => $this->get_option( 'button_color' ),
				'button_style'      => $this->get_option( 'button_style' ),
				'total_price_label' => __( 'Total', 'woo-stripe-payment' ),
				'routes'            => array( 'payment_data' => wc_stripe()->rest_api->googlepay->rest_url( 'shipping-data' ) ),
				'messages'          => array( 'invalid_amount' => __( 'Please update you product quantity before using Google Pay.', 'woo-stripe-payment' ) )
			)
		);

		return $data;
	}

	protected function get_display_item_for_cart( $price, $label, $type, ...$args ) {
		switch ( $type ) {
			case 'tax':
				$type = 'TAX';
				break;
			default:
				$type = 'LINE_ITEM';
				break;
		}

		return array(
			'label' => $label,
			'type'  => $type,
			'price' => strval( round( $price, 2 ) )
		);
	}

	protected function get_display_item_for_product( $product ) {
		return array(
			'label' => esc_attr( $product->get_name() ),
			'type'  => 'SUBTOTAL',
			'price' => strval( round( $product->get_price(), 2 ) )
		);
	}

	protected function get_display_item_for_order( $price, $label, $order, $type, ...$args ) {
		switch ( $type ) {
			case 'tax':
				$type = 'TAX';
				break;
			default:
				$type = 'LINE_ITEM';
				break;
		}

		return array(
			'label' => $label,
			'type'  => $type,
			'price' => strval( round( $price, 2 ) )
		);
	}

	public function get_formatted_shipping_methods( $methods = array() ) {
		$methods = parent::get_formatted_shipping_methods( $methods );
		if ( empty( $methods ) ) {
			// GPay does not like empty shipping methods. Make a temporary one;
			$methods[] = array(
				'id'          => 'default',
				'label'       => __( 'Waiting...', 'woo-stripe-payment' ),
				'description' => __( 'loading shipping methods...', 'woo-stripe-payment' ),
			);
		}

		return $methods;
	}

	public function get_formatted_shipping_method( $price, $rate, $i, $package, $incl_tax ) {
		return array(
			'id'          => $this->get_shipping_method_id( $rate->id, $i ),
			'label'       => $this->get_formatted_shipping_label( $price, $rate, $incl_tax ),
			'description' => ''
		);
	}

	/**
	 * @param float $price
	 * @param WC_Shipping_Rate $rate
	 * @param bool $incl_tax
	 *
	 * @return string|void
	 */
	protected function get_formatted_shipping_label( $price, $rate, $incl_tax ) {
		$label = sprintf( '%s: %s %s', esc_attr( $rate->get_label() ), number_format( $price, 2 ), get_woocommerce_currency() );
		if ( $incl_tax ) {
			if ( $rate->get_shipping_tax() > 0 && ! wc_prices_include_tax() ) {
				$label .= ' ' . WC()->countries->inc_tax_or_vat();
			}
		} else {
			if ( $rate->get_shipping_tax() > 0 && wc_prices_include_tax() ) {
				$label .= ' ' . WC()->countries->ex_tax_or_vat();
			}
		}

		return $label;
	}

	/**
	 * Return a formatted shipping method label.
	 * <strong>Example</strong>&nbsp;5 Day shipping: 5 USD
	 *
	 * @param WC_Shipping_Rate $rate
	 *
	 * @return
	 * @deprecated
	 *
	 */
	public function get_shipping_method_label( $rate ) {
		$incl_tax = wc_stripe_display_prices_including_tax();
		$price    = $incl_tax ? $rate->cost + $rate->get_shipping_tax() : $rate->cost;

		return $this->get_formatted_shipping_label( $price, $rate, $incl_tax );
	}

	public function add_to_cart_response( $data ) {
		$data['googlepay']['displayItems'] = $this->get_display_items();

		return $data;
	}

	/**
	 * @param array $deps
	 * @param $scripts
	 *
	 * @return array
	 */
	public function get_mini_cart_dependencies( $deps, $scripts ) {
		if ( $this->mini_cart_enabled() ) {
			$deps[] = $scripts->get_handle( 'gpay' );
		}

		return $deps;
	}
}
