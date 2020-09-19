<?php
defined( 'ABSPATH' ) || exit();

/**
 * Handles scrip enqueuement and output of params needed by the plugin.
 *
 * @package Stripe/Classes
 * @author PaymentPlugins
 */
class WC_Stripe_Frontend_Scripts {

	public $prefix = 'wc-stripe-';

	public $registered_scripts = array();

	public $enqueued_scripts = array();

	public $localized_scripts = array();

	public $localized_data = array();

	public $global_scripts = array(
		'external' => 'https://js.stripe.com/v3/',
		'gpay'     => 'https://pay.google.com/gp/p/js/pay.js',
		'plaid'    => 'https://cdn.plaid.com/link/v2/stable/link-initialize.js',
	);

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_print_scripts', array( $this, 'localize_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( $this, 'localize_scripts' ), 5 );
	}

	/**
	 * Enqueue all frontend scripts needed by the plugin
	 */
	public function enqueue_scripts() {
		// register global scripts
		foreach ( $this->global_scripts as $handle => $src ) {
			$this->register_script( $handle, $src );
		}

		$this->register_script( 'form-handler', $this->assets_url( 'js/frontend/form-handler.js' ), array( 'jquery' ) );

		// register scripts that aren't part of gateways
		$this->register_script( 'wc-stripe', $this->assets_url( 'js/frontend/wc-stripe' . $this->get_min() . '.js' ),
			array(
				'jquery',
				$this->get_handle( 'external' ),
				'woocommerce',
				$this->get_handle( 'form-handler' )
			) );

		// mini cart is not relevant on cart and checkout page.
		if ( ! is_checkout() && ! is_cart() ) {
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
				if ( $gateway instanceof WC_Payment_Gateway_Stripe && $gateway->is_available() && $gateway->mini_cart_enabled() ) {
					$gateway->enqueue_frontend_scripts( 'mini_cart' );
				}
			}
		}
	}

	public function localize_scripts() {
		$this->localize_script( 'wc-stripe',
			array(
				'api_key' => wc_stripe_get_publishable_key(),
				'account' => wc_stripe_get_account_id(),
				'page'    => $this->get_page_id(),
				'version' => wc_stripe()->version()
			),
			'wc_stripe_params_v3'
		);
		$this->localize_script( 'form-handler',
			array(
				'no_results' => __(
					'No matches found',
					'woo-stripe-payment'
				),
			)
		);
		$this->localize_script( 'wc-stripe', wc_stripe_get_error_messages(), 'wc_stripe_messages' );
		$this->localize_script( 'wc-stripe', wc_stripe_get_checkout_fields(), 'wc_stripe_checkout_fields' );

		// don't need to call localize_scripts twice.
		if ( doing_action( 'wp_print_scripts' ) ) {
			remove_action( 'wp_print_footer_scripts', array( $this, 'localize_scripts' ), 5 );
		}
	}

	public function enqueue_checkout_scripts() {
		$this->enqueue_local_payment_scripts();
	}

	public function enqueue_local_payment_scripts() {
		if ( ! in_array( $this->get_handle( 'local-payment' ), $this->enqueued_scripts ) ) {
			$data = wc_stripe_get_local_payment_params();
			// only enqueue local payment script if there are local payment gateways that have been enabled.
			if ( ! empty( $data['gateways'] ) ) {
				$this->enqueue_script(
					'local-payment',
					$this->assets_url( 'js/frontend/local-payment.js' ),
					array(
						$this->get_handle( 'external' ),
						$this->get_handle( 'wc-stripe' ),
					)
				);
				$this->localize_script( 'local-payment', $data );
			}
		}
	}

	public function register_script( $handle, $src, $deps = array(), $version = '', $footer = true ) {
		$version                    = empty( $version ) ? wc_stripe()->version() : $version;
		$this->registered_scripts[] = $this->get_handle( $handle );
		wp_register_script( $this->get_handle( $handle ), $src, $deps, $version, $footer );
	}

	public function enqueue_script( $handle, $src = '', $deps = array(), $version = '', $footer = true ) {
		$handle  = $this->get_handle( $handle );
		$version = empty( $version ) ? wc_stripe()->version() : $version;
		if ( ! in_array( $handle, $this->registered_scripts ) ) {
			$this->register_script( $handle, $src, $deps, $version, $footer );
		}
		$this->enqueued_scripts[] = $handle;
		wp_enqueue_script( $handle );
	}

	/**
	 *
	 * @param string $handle
	 * @param array $data
	 * @param string $object_name
	 */
	public function localize_script( $handle, $data, $object_name = '' ) {
		$handle = $this->get_handle( $handle );
		if ( wp_script_is( $handle, 'registered' ) ) {
			$name = str_replace( $this->prefix, '', $handle );
			if ( ! $object_name ) {
				$object_name = str_replace( '-', '_', $handle ) . '_params';
			}
			if ( ! in_array( $object_name, $this->localized_data ) ) {
				$data = apply_filters( 'wc_stripe_localize_script_' . $name, $data, $object_name );
				if ( $data ) {
					$this->localized_scripts[] = $handle;
					$this->localized_data[]    = $object_name;
					wp_localize_script( $handle, $object_name, $data );
				}
			}
		}
	}

	public function get_handle( $handle ) {
		return strpos( $handle, $this->prefix ) === false ? $this->prefix . $handle : $handle;
	}

	/**
	 *
	 * @param string $uri
	 */
	public function assets_url( $uri = '' ) {
		return untrailingslashit( wc_stripe()->assets_url( $uri ) );
	}

	public function get_min() {
		return $suffix = SCRIPT_DEBUG ? '' : '.min';
	}

	private function get_page_id() {
		global $wp;
		if ( is_product() ) {
			return 'product';
		}
		if ( is_cart() ) {
			return 'cart';
		}
		if ( is_checkout() ) {
			if ( ! empty( $wp->query_vars['order-pay'] ) ) {
				return 'order_pay';
			}

			return 'checkout';
		}
		if ( is_add_payment_method_page() ) {
			return 'add_payment_method';
		}

		return '';
	}
}
