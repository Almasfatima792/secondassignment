<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class that manages customer creation and custom updates.
 *
 * @since 3.0.0
 * @package Stripe/Classes
 * @author PaymentPlugins
 *
 */
class WC_Stripe_Customer_Manager {

	public function __construct() {
		add_action( 'woocommerce_checkout_update_customer', array( $this, 'checkout_update_customer' ), 10, 2 );
		add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );
	}

	/**
	 *
	 * @param WC_Customer $customer
	 * @param array $data
	 */
	public function checkout_update_customer( $customer, $data ) {
		if ( $this->user_has_id( $customer ) ) {
			if ( $this->should_update_customer( $customer ) ) {
				$result = $this->update_customer( $customer );
				if ( is_wp_error( $result ) ) {
					wc_add_notice( __( 'Error saving customer. Reason: %s', 'woo-stripe-payment' ), $result->get_error_message() );
				}
			}
		} else {
			// create the customer
			$response = $this->create_customer( $customer );
			if ( ! is_wp_error( $response ) ) {
				wc_stripe_save_customer( $response->id, $customer->get_id() );
			} else {
				wc_add_notice( __( 'Error saving customer. Reason: %s', 'woo-stripe-payment' ), $response->get_error_message() );
			}
		}
	}

	/**
	 *
	 * @param WC_Customer $customer
	 *
	 * @return \Stripe\Customer|WP_Error
	 */
	private function create_customer( $customer ) {
		return WC_Stripe_Gateway::load()->customers->create(
			apply_filters(
				'wc_stripe_customer_args',
				array(
					'email'    => $customer->get_email(),
					'name'     => sprintf( '%s %s', $customer->get_first_name(), $customer->get_last_name() ),
					'phone'    => $customer->get_billing_phone(),
					'metadata' => array(
						'user_id'  => $customer->get_id(),
						'username' => $customer->get_username(),
						'website'  => get_site_url(),
					),
				)
			)
		);
	}

	/**
	 *
	 * @param WC_Customer $customer
	 */
	private function update_customer( $customer ) {
		return WC_Stripe_Gateway::load()->customers->update(
			wc_stripe_get_customer_id( $customer->get_id() ),
			apply_filters( 'wc_stripe_update_customer_args', array(
				'email' => $customer->get_billing_email(),
				'name'  => sprintf( '%s %s', $customer->get_billing_first_name(), $customer->get_billing_last_name() ),
				'phone' => $customer->get_billing_phone(),
			) )
		);
	}

	/**
	 * Check if the Stripe customer has been created for the WC user.
	 * If there is no Stripe customer then
	 * create one and save it to the WC user.
	 */
	public function wp_loaded() {
		$customer = WC()->customer;
		if ( $customer && $customer->get_id() ) {
			if ( ! $this->user_has_id( $customer ) ) {
				$response = $this->create_customer( $customer );
				if ( ! is_wp_error( $response ) ) {
					wc_stripe_save_customer( $response->id, $customer->get_id() );
				}
			}
		}
	}

	/**
	 *
	 * @param WC_Customer $customer
	 */
	private function user_has_id( $customer ) {
		$id = wc_stripe_get_customer_id( $customer->get_id() );

		// this customer may have an ID from another plugin. Check that too.
		if ( empty( $id ) ) {
			$id = get_user_option( '_stripe_customer_id', $customer->get_id() );
			if ( ! empty( $id ) ) {
				// validate that this customer exists in the Stripe gateway
				$response = WC_Stripe_Gateway::load()->customers->retrieve( $id );
				if ( ! is_wp_error( $response ) ) {
					// id exists so save customer ID to this plugin's format.
					wc_stripe_save_customer( $id, $customer->get_id() );

					// load this customer's payment methods
					$this->sync_payment_methods( $id, $customer->get_id() );
				} else {
					$id = '';
				}
			}
		}

		return ! empty( $id );
	}

	/**
	 * Syncs the WC database payment methods with the payment methods stored in Stripe.
	 *
	 * @param string $customer_id
	 * @param int $user_id
	 *
	 * @since 3.1.0
	 */
	public static function sync_payment_methods( $customer_id, $user_id, $mode = '' ) {
		$payment_methods = WC_Stripe_Gateway::load()->paymentMethods->mode( $mode )->all( array(
			'customer' => $customer_id,
			'type'     => 'card',
		) );

		if ( ! is_wp_error( $payment_methods ) ) {
			foreach ( $payment_methods->data as $payment_method ) {
				if ( ! WC_Payment_Token_Stripe::token_exists( $payment_method->id, $user_id ) ) {
					$payment_gateways = WC()->payment_gateways()->payment_gateways();
					$gateway_id       = null;

					if ( strstr( $payment_method->id, 'pm_' ) || strstr( $payment_method->id, 'src_' ) ) {
						$gateway_id = 'stripe_cc';
					} elseif ( strstr( $payment_method->id, 'card_' ) ) {
						$gateway_id = 'stripe_googlepay';
					}
					/**
					 *
					 * @var WC_Payment_Gateway_Stripe_CC $payment_gateway
					 */
					$payment_gateway = $payment_gateways[ $gateway_id ];
					$token           = $payment_gateway->get_payment_token( $payment_method->id, $payment_method );
					$token->set_environment( $payment_method->livemode ? 'live' : 'test' );
					$token->set_user_id( $user_id );
					$token->set_customer_id( $customer_id );
					$token->save();
				}
			}
		} else {
			wc_stripe_log_info( sprintf( 'Payment methods for customer %s could not be synced. Reason: %s', $customer_id, $payment_methods->get_error_message() ) );
		}
	}

	/**
	 * Returns true if the customer should be updated in Stripe.
	 *
	 * @param WC_Customer $customer
	 *
	 * @return bool
	 */
	private function should_update_customer( $customer ) {
		$changes = $customer->get_changes();
		if ( ! empty( $changes['billing'] ) ) {
			return array_intersect_key( $changes['billing'], array_flip( $this->get_attribute_keys() ) );
		}

		return false;
	}

	/**
	 * Returns an array of user attributes.
	 *
	 * @return array
	 */
	private function get_attribute_keys() {
		return array( 'first_name', 'last_name', 'email' );
	}
}

new WC_Stripe_Customer_Manager();
