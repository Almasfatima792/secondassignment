<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author PaymentPlugins
 * @package Stripe/Classes
 * @property WC_Stripe_Rest_Controller $order_actions
 * @property WC_Stripe_Rest_Controller $cart
 * @property WC_Stripe_Rest_Controller $checkout
 * @property WC_Stripe_Rest_Controller $payment_intent
 * @property WC_Stripe_Rest_Controller $googlepay
 * @property WC_Stripe_Rest_Controller $settings
 * @property WC_Stripe_Rest_Controller $webhook
 * @property WC_Stripe_Rest_Controller $product_data
 * @property WC_Stripe_Rest_Controller $plaid
 */
class WC_Stripe_Rest_API {

	/**
	 *
	 * @var array
	 */
	private $controllers = array();

	public function __construct() {
		$this->include_classes();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 *
	 * @param WC_Stripe_Rest_Controller $key
	 */
	public function __get( $key ) {
		$controller = isset( $this->controllers[ $key ] ) ? $this->controllers[ $key ] : '';
		if ( empty( $controller ) ) {
			wc_doing_it_wrong( __FUNCTION__, sprintf( __( '%1$s is an invalid controller name.', 'woo-stripe-payment' ), $key ), wc_stripe()->version );
		}

		return $controller;
	}

	public function __set( $key, $value ) {
		$this->controllers[ $key ] = $value;
	}

	private function include_classes() {
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/abstract/abstract-wc-stripe-rest-controller.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-order-actions.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-payment-intent.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-cart.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-checkout.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-googlepay.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-payment-method.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-gateway-settings.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-webhook.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-product-data.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-plaid.php';

		foreach ( $this->get_controllers() as $key => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->{$key} = new $class_name();
			}
		}
	}

	public function register_routes() {
		if ( self::is_rest_api_request() ) {
			foreach ( $this->controllers as $key => $controller ) {
				if ( is_callable( array( $controller, 'register_routes' ) ) ) {
					$controller->register_routes();
				}
			}
		}
	}

	public function get_controllers() {
		$controllers = array(
			'order_actions'  => 'WC_Stripe_Controller_Order_Actions',
			'checkout'       => 'WC_Stripe_Controller_Checkout',
			'cart'           => 'WC_Stripe_Controller_Cart',
			'payment_intent' => 'WC_Stripe_Controller_Payment_Intent',
			'googlepay'      => 'WC_Stripe_Controller_GooglePay',
			'payment_method' => 'WC_Stripe_Controller_Payment_Method',
			'settings'       => 'WC_Stripe_Controller_Gateway_Settings',
			'webhook'        => 'WC_Stripe_Controller_Webhook',
			'product_data'   => 'WC_Stripe_Controller_Product_Data',
			'plaid'          => 'WC_Stripe_Controller_Plaid'
		);

		/**
		 * @param string[] $controllers
		 */
		return apply_filters( 'wc_stripe_api_controllers', $controllers );
	}

	/**
	 * @return string
	 */
	public function rest_url() {
		return wc_stripe()->rest_url();
	}

	/**
	 * @return string
	 */
	public function rest_uri() {
		return wc_stripe()->rest_uri();
	}

	/**
	 * @return bool
	 */
	public static function is_rest_api_request() {
		global $wp;
		if ( ! empty( $wp->query_vars['rest_route'] ) && strpos( $wp->query_vars['rest_route'], wc_stripe()->rest_uri() ) !== false ) {
			return true;
		}
		if ( ! empty( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], wc_stripe()->rest_uri() ) !== false ) {
			return true;
		}

		return false;
	}
}
