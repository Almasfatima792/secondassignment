<?php
defined( 'ABSPATH' ) || exit();

class WC_Stripe_Controller_Plaid extends WC_Stripe_Rest_Controller {

	use WC_Stripe_Controller_Frontend_Trait;

	protected $namespace = 'plaid';

	public function register_routes() {
		register_rest_route( $this->rest_uri(), 'link-token', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'get_link_token' ),
			'permission_callback' => array( $this, 'validate_rest_nonce' )
		) );
	}

	/**
	 * @param WP_REST_Request $request
	 */
	public function get_link_token( $request ) {
		/**
		 * @var WC_Payment_Gateway_Stripe_ACH $gateway
		 */
		$gateway = WC()->payment_gateways()->payment_gateways()['stripe_ach'];

		try {
			$response = $gateway->fetch_link_token();

			return rest_ensure_response( array( 'token' => $response->link_token ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'plaid-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}
}