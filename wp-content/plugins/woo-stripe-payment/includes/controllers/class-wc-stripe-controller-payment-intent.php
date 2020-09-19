<?php
defined( 'ABSPATH' ) || exit();

/**
 * Controller which handles Payment Intent related actions such as creation.
 *
 * @author PaymentPlugins
 * @package Stripe/Controllers
 *
 */
class WC_Stripe_Controller_Payment_Intent extends WC_Stripe_Rest_Controller {

	protected $namespace = '';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'setup-intent',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array(
					$this,
					'create_setup_intent',
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'sync-payment-intent',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'sync_payment_intent' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'order_id'      => array( 'required' => true ),
					'client_secret' => array( 'required' => true ),
				),
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function create_setup_intent( $request ) {
		$intent = WC_Stripe_Gateway::load()->setupIntents->create( array( 'usage' => 'off_session' ) );
		try {
			if ( is_wp_error( $intent ) ) {
				throw new Exception( $intent->get_error_message() );
			}

			return rest_ensure_response( array( 'intent' => array( 'client_secret' => $intent->client_secret ) ) );
		} catch ( Exception $e ) {
			return new WP_Error(
				'payment-intent-error',
				sprintf( __( 'Error creating payment intent. Reason: %s', 'woo-stripe-payment' ), $e->getMessage() ),
				array(
					'status' => 200,
				)
			);
		}
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function sync_payment_intent( $request ) {
		try {
			$order = wc_get_order( absint( $request->get_param( 'order_id' ) ) );
			if ( ! $order ) {
				throw new Exception( __( 'Invalid order id provided', 'woo-stripe-payment' ) );
			}

			$intent = WC_Stripe_Gateway::load()->paymentIntents->retrieve( $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT_ID ) );

			if ( ! hash_equals( $intent->client_secret, $request->get_param( 'client_secret' ) ) ) {
				throw new Exception( __( 'You are not authorized to update this order.', 'woo-stripe-payment' ) );
			}

			$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT, $intent->jsonSerialize() );
			$order->save();

			return rest_ensure_response( array() );
		} catch ( Exception $e ) {
			return new WP_Error( 'payment-intent-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}
}
