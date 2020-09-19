<?php
defined( 'ABSPATH' ) || exit();

/**
 * Gateway that processes ACH payments.
 * Only available for U.S. based merchants at this time.
 *
 * @since 3.0.5
 * @author Payment Plugins
 * @package Stripe/Gateways
 *
 */
class WC_Payment_Gateway_Stripe_ACH extends WC_Payment_Gateway_Stripe {

	use WC_Stripe_Payment_Charge_Trait;

	/**
	 *
	 * @var object
	 */
	public $metadata_key = '';

	public function __construct() {
		$this->synchronous        = false;
		$this->id                 = 'stripe_ach';
		$this->tab_title          = __( 'ACH', 'woo-stripe-payment' );
		$this->template_name      = 'ach.php';
		$this->token_type         = 'Stripe_ACH';
		$this->method_title       = __( 'Stripe ACH', 'woo-stripe-payment' );
		$this->method_description = __( 'ACH gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = wc_stripe()->assets_url( 'img/ach.svg' );
		$this->order_button_text  = __( 'Bank Payment', 'woo-stripe-payment' );
		$this->metadata_key       = $this->id . '_metadata';
		parent::__construct();
		$this->settings['charge_type'] = 'capture';
	}

	public static function init() {
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'update_order_review' ) );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway::is_available()
	 */
	public function is_available() {
		$is_available = parent::is_available();

		return $is_available && get_woocommerce_currency() == 'USD';
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway_Stripe::init_supports()
	 */
	public function init_supports() {
		parent::init_supports();
		unset( $this->supports['add_payment_method'] );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway_Stripe::enqueue_checkout_scripts()
	 */
	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->enqueue_script(
			'ach',
			$scripts->assets_url( 'js/frontend/ach-payments.js' ),
			array(
				$scripts->get_handle( 'external' ),
				$scripts->get_handle( 'wc-stripe' ),
				$scripts->get_handle( 'plaid' ),
			)
		);
		$scripts->localize_script( 'ach', $this->get_localized_params() );
	}

	public function get_localized_params() {
		return array_merge_recursive(
			parent::get_localized_params(),
			array(
				'env'          => $this->get_plaid_environment(),
				'client_name'  => $this->get_option( 'client_name' ),
				'fees_enabled' => $this->fees_enabled(),
				'routes'       => array(
					'link_token' => wc_stripe()->rest_api->plaid->rest_url( 'link-token' )
				)
			)
		);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway_Stripe_Charge::process_payment()
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// generate the access token first.
		if ( ! $this->use_saved_source() ) {
			try {
				$result = $this->fetch_access_token( $this->get_public_token() );

				$result = $this->fetch_bank_token( $result->access_token );

				$this->set_new_source_token( $result->stripe_bank_account_token );
			} catch ( Exception $e ) {
				wc_add_notice( sprintf( __( 'Error processing payment. Reason: %s', 'woo-stripe-payment' ), $e->getMessage() ), 'error' );
			}
		}
		add_filter(
			'wc_stripe_order_meta_data',
			function ( $metadata ) {
				$metadata['plaid_id'] = $this->get_option( 'client_id' );

				return $metadata;
			}
		);

		return parent::process_payment( $order_id );
	}

	private function do_api_request( $uri, $body, $method = 'POST' ) {
		$response = wp_safe_remote_post(
			$this->get_plaid_url( $uri ),
			array(
				'headers'     => array( 'Content-Type' => 'application/json' ),
				'body'        => wp_json_encode( $body ),
				'data_format' => 'body',
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		} else {
			$body = json_decode( $response['body'] );
			if ( $response['response']['code'] > 299 ) {
				throw new Exception( $body->error_message );
			} else {
				return $body;
			}
		}
	}

	private function fetch_access_token( $public_token ) {
		$env = $this->get_plaid_environment();

		return $this->do_api_request(
			'item/public_token/exchange',
			array(
				'client_id'    => $this->get_option( 'client_id' ),
				'secret'       => $this->get_option( "{$env}_secret" ),
				'public_token' => $public_token
			)
		);
	}

	private function fetch_bank_token( $access_token ) {
		$env = $this->get_plaid_environment();

		return $this->do_api_request(
			'processor/stripe/bank_account_token/create',
			array(
				'client_id'    => $this->get_option( 'client_id' ),
				'secret'       => $this->get_option( "{$env}_secret" ),
				'access_token' => $access_token,
				'account_id'   => $this->get_metadata()['account_id'],
			)
		);
	}

	/**
	 * @return mixed|null
	 * @throws Exception
	 * @since 3.2.1
	 */
	public function fetch_link_token() {
		$env = $this->get_plaid_environment();

		return $this->do_api_request(
			'link/token/create',
			array(
				'client_id'     => $this->get_option( 'client_id' ),
				'secret'        => $this->get_option( "{$env}_secret" ),
				'client_name'   => $this->get_option( 'client_name' ),
				'language'      => 'en',
				'country_codes' => array( 'US' ),
				'user'          => array(
					'client_user_id' => WC()->session->get_customer_id()
				),
				'products'      => array( 'auth' )
			)
		);
	}

	/**
	 * Return the base plaid api url.
	 *
	 * @return string
	 */
	private function get_base_url() {
		$url = '';
		switch ( $this->get_plaid_environment() ) {
			case 'production':
				$url = 'https://production.plaid.com/';
				break;
			case 'sandbox':
				$url = 'https://sandbox.plaid.com/';
				break;
			case 'development':
				$url = 'https://development.plaid.com/';
				break;
		}

		return $url;
	}

	private function get_plaid_url( $uri ) {
		return sprintf( '%s%s', $this->get_base_url(), $uri );
	}

	public function get_plaid_environment() {
		return $this->get_option( 'environment' );
	}

	private function get_metadata() {
		return isset( $_POST[ $this->metadata_key ] ) ? json_decode( stripslashes( $_POST[ $this->metadata_key ] ), true ) : null;
	}

	private function get_public_token() {
		return $this->get_new_source_token();
	}

	public function get_saved_methods_label() {
		return __( 'Saved Banks', 'woo-stripe-payment' );
	}

	public function get_new_method_label() {
		return __( 'New Bank', 'woo-stripe-payment' );
	}

	public function generate_ach_fee_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => 'max-width: 150px; min-width: 150px;',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'options'           => array(),
		);
		$data      = wp_parse_args( $data, $defaults );
		ob_start();
		include wc_stripe()->plugin_path() . 'includes/admin/views/html-ach-fee.php';

		return ob_get_clean();
	}

	public function validate_ach_fee_field( $key, $value ) {
		$value = empty( $value ) ? array(
			'type'    => 'none',
			'taxable' => 'no',
			'value'   => '0',
		) : $value;
		if ( ! isset( $value['taxable'] ) ) {
			$value['taxable'] = 'no';
		}

		return $value;
	}

	/**
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function validate_environment_field( $key, $value ) {
		if ( 'test' == wc_stripe_mode() && 'development' == $value ) {
			WC_Admin_Settings::add_error( __( 'You must set the API mode to live in order to enable the Plaid development environment.', 'woo-stripe-payment' ) );
		}

		return $value;
	}

	public function fees_enabled() {
		$fee = $this->get_option(
			'fee',
			array(
				'type'  => 'none',
				'value' => '0',
			)
		);

		return ! empty( $fee ) && $fee['type'] != 'none';
	}

	/**
	 *
	 * @param WC_Cart $cart
	 */
	public function after_calculate_totals( $cart ) {
		remove_action( 'woocommerce_after_calculate_totals', array( $this, 'after_calculate_totals' ) );

		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_fees' ) );

		WC()->session->set( 'wc_stripe_cart_total', $cart->total );
		$cart->calculate_totals();
	}

	/**
	 *
	 * @param WC_Cart $cart
	 */
	public function calculate_fees( $cart ) {
		remove_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_fees' ) );
		$fee     = $this->get_option( 'fee' );
		$taxable = $fee['taxable'] == 'yes';
		switch ( $fee['type'] ) {
			case 'amount':
				$cart->add_fee( __( 'ACH Fee' ), $fee['value'], $taxable );
				break;
			case 'percent':
				$cart->add_fee( __( 'ACH Fee' ), $fee['value'] * WC()->session->get( 'wc_stripe_cart_total', 0 ), $taxable );
				break;
		}
		unset( WC()->session->wc_stripe_cart_total );
	}

	public static function update_order_review() {
		if ( ! empty( $_POST['payment_method'] ) && $_POST['payment_method'] == 'stripe_ach' ) {
			$payment_method = new WC_Payment_Gateway_Stripe_ACH();
			if ( $payment_method->fees_enabled() ) {
				add_action( 'woocommerce_after_calculate_totals', array( $payment_method, 'after_calculate_totals' ) );
			}
		}
	}

	/**
	 * Override so Plaid token can be converted to a Stripe bank token.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway_Stripe::create_payment_method()
	 */
	public function create_payment_method( $id, $customer_id ) {
		if ( $this->is_change_payment_method_request() ) {
			$result = $this->fetch_access_token( $this->get_public_token() );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$result = $this->fetch_bank_token( $result->access_token );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$id = $result->stripe_bank_account_token;
		}

		return parent::create_payment_method( $id, $customer_id );
	}
}

WC_Payment_Gateway_Stripe_ACH::init();
