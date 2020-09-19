<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway_Stripe' ) ) {
	return;
}

/**
 * Local payment method classes should extend this abstract class
 *
 * @package Stripe/Abstract
 * @author Payment Plugins
 *
 */
abstract class WC_Payment_Gateway_Stripe_Local_Payment extends WC_Payment_Gateway_Stripe {

	protected $tab_title = '';

	/**
	 * Currencies this gateway accepts
	 * @var array
	 */
	protected $currencies = array();

	protected $local_payment_type = '';

	public $countries = array();

	protected $local_payment_description = '';

	public function __construct() {
		$this->token_type    = 'Stripe_Local';
		$this->template_name = 'local-payment.php';
		parent::__construct();
		$this->settings['method_format'] = 'gateway_title';
		$this->settings['charge_type']   = 'capture';
		$this->settings['order_status']  = 'default';
	}

	public function hooks() {
		parent::hooks();
		add_filter( 'wc_stripe_local_gateway_tabs', array( $this, 'local_gateway_tab' ) );
		remove_filter( 'wc_stripe_settings_nav_tabs', array( $this, 'admin_nav_tab' ) );
		add_filter( 'wc_stripe_local_gateways_tab', array( $this, 'admin_nav_tab' ) );
		add_action( 'woocommerce_settings_checkout_stripe_local_gateways_' . $this->id, array( $this, 'admin_options' ) );
		add_action( 'wc_stripe_settings_before_options_stripe_local_gateways_' . $this->id, array( $this, 'navigation_menu' ) );
		add_action( 'woocommerce_update_options_checkout_stripe_local_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * @param WC_Stripe_Frontend_Scripts $scripts
	 */
	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->enqueue_local_payment_scripts();
	}

	/**
	 *
	 * @param \Stripe\Source $source
	 * @param WC_Order $order
	 */
	public function get_source_redirect_url( $source, $order ) {
		return $source->redirect->url;
	}

	public function output_settings_nav() {
		parent::output_settings_nav();
		include wc_stripe()->plugin_path() . 'includes/admin/views/html-settings-local-payments-nav.php';
	}

	public function init_form_fields() {
		$this->form_fields = apply_filters( 'wc_stripe_form_fields_' . $this->id, $this->get_local_payment_settings() );
	}

	public function init_supports() {
		$this->supports = array( 'tokenization', 'products', 'refunds' );
	}

	public function process_payment( $order_id ) {
		$result = parent::process_payment( $order_id );

		if ( defined( WC_Stripe_Constants::WOOCOMMERCE_STRIPE_ORDER_PAY ) && $result['result'] == 'success' ) {
			wp_send_json( array(
				'success'  => true,
				'redirect' => $result['redirect']
			), 200 );
			exit();
		}

		return $result;
	}

	/**
	 * Return an array of form fields for the gateway.
	 *
	 * @return array
	 */
	public function get_local_payment_settings() {
		return array(
			'desc'             => array(
				'type'        => 'description',
				'description' => $this->get_payment_description(),
			),
			'enabled'          => array(
				'title'       => __( 'Enabled', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => sprintf( __( 'If enabled, your site can accept %s payments through Stripe.', 'woo-stripe-payment' ), $this->get_method_title() ),
			),
			'general_settings' => array(
				'type'  => 'title',
				'title' => __( 'General Settings', 'woo-stripe-payment' ),
			),
			'title_text'       => array(
				'type'        => 'text',
				'title'       => __( 'Title', 'woo-stripe-payment' ),
				'default'     => $this->get_method_title(),
				'desc_tip'    => true,
				'description' => sprintf( __( 'Title of the %s gateway' ), $this->get_method_title() ),
			),
			'description'      => array(
				'title'       => __( 'Description', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Leave blank if you don\'t want a description to show for the gateway.', 'woo-stripe-payment' ),
				'desc_tip'    => true,
			),
		);
	}

	public function get_localized_params() {
		return array_merge_recursive(
			parent::get_localized_params(),
			array(
				'local_payment_type' => $this->local_payment_type,
				'return_url'         => add_query_arg(
					array(
						'_payment_nonce'          => wp_create_nonce( 'local-payment' ),
						'wc-stripe-local-gateway' => $this->id,
					),
					wc_get_checkout_url()
				),
				'element_params'     => $this->get_element_params(),
				'routes'             => array(
					'order_pay'     => wc_stripe()->rest_api->checkout->rest_url( 'order-pay' ),
					'delete_source' => wc_stripe()->rest_api->checkout->rest_url( 'source' )
				)
			)
		);
	}

	public function get_element_params() {
		return array(
			'style' => array(
				'base'    => array(
					'padding'       => '10px 12px',
					'color'         => '#32325d',
					'fontFamily'    => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
					'fontSmoothing' => 'antialiased',
					'fontSize'      => '16px',
					'::placeholder' => array( 'color' => '#aab7c4' ),
				),
				'invalid' => array( 'color' => '#fa755a' ),
			),
		);
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function get_source_args( $order ) {
		$args = array(
			'type'                 => $this->local_payment_type,
			'amount'               => wc_stripe_add_number_precision( $order->get_total(), $order->get_currency() ),
			'currency'             => $order->get_currency(),
			'statement_descriptor' => sprintf( __( 'Order %s', 'woo-stripe-payment' ), $order->get_order_number() ),
			'owner'                => array(
				'name' => $this->payment_object->get_name_from_order( $order, 'billing' )
			),
			'redirect'             => array( 'return_url' => $this->get_local_payment_return_url( $order ) ),
		);
		if ( ( $email = $order->get_billing_email() ) ) {
			$args['owner']['email'] = $email;
		}

		/**
		 * @param $args
		 *
		 * @since 3.1.8
		 */
		return apply_filters( 'wc_stripe_get_source_args', $args );
	}

	/**
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	protected function get_local_payment_return_url( $order ) {
		global $wp;
		if ( isset( $wp->query_vars['order-pay'] ) ) {
			$url = $order->get_checkout_payment_url();
		} else {
			$url = wc_get_checkout_url();
		}

		return add_query_arg(
			array(
				'_payment_nonce'          => wp_create_nonce( 'local-payment' ),
				'wc-stripe-local-gateway' => $this->id,
			),
			$url
		);
	}

	public function is_local_payment_available() {
		global $wp;
		if ( isset( $wp->query_vars['order-pay'] ) ) {
			$order           = wc_get_order( absint( $wp->query_vars['order-pay'] ) );
			$currency        = $order->get_currency();
			$billing_country = $order->get_billing_country();
		} else {
			$currency        = get_woocommerce_currency();
			$customer        = WC()->customer;
			$billing_country = $customer ? $customer->get_billing_country() : null;
			if ( ! $billing_country ) {
				$billing_country = WC()->countries->get_base_country();
			}
		}
		if ( method_exists( $this, 'validate_local_payment_available' ) ) {
			return $this->validate_local_payment_available( $currency, $billing_country );
		} else {
			if ( empty( $this->countries ) ) {
				return in_array( $currency, $this->currencies );
			} else {
				return $billing_country && in_array( $currency, $this->currencies ) && in_array( $billing_country, $this->countries );
			}
		}
	}

	public function get_payment_token( $method_id, $method_details = array() ) {
		/**
		 *
		 * @var WC_Payment_Token_Stripe_Local $token
		 */
		$token = parent::get_payment_token( $method_id, $method_details );
		$token->set_gateway_title( $this->title );

		return $token;
	}

	/**
	 * Return a description for (for admin sections) describing the required currency & or billing country(s).
	 *
	 * @return string
	 */
	protected function get_payment_description() {
		$desc = '';
		if ( $this->currencies ) {
			$desc .= sprintf( __( 'Gateway will appear when store currency is <strong>%s</strong>', 'woo-stripe-payment' ), implode( ', ', $this->currencies ) );
		}
		if ( $this->countries ) {
			$desc .= sprintf( __( ' & billing country is <strong>%s</strong>', 'woo-stripe-payment' ), implode( ', ', $this->countries ) );
		}

		return $desc;
	}

	/**
	 * Return a description of the payment method.
	 */
	public function get_local_payment_description() {
		return apply_filters( 'wc_stripe_local_payment_description', $this->local_payment_description, $this );
	}

	/**
	 *
	 * @param string $text
	 *
	 * @return string
	 * @since 3.1.3
	 */
	public function get_order_button_text( $text ) {
		return apply_filters( 'wc_stripe_order_button_text', $text, $this );
	}
}
