<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author PaymentPlugins
 * @package Stripe/Classes
 *
 */
class WC_Stripe_Redirect_Handler {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'local_payment_redirect' ) );
		add_action( 'get_header', array( __CLASS__, 'maybe_restore_cart' ), 100 );
	}

	/**
	 * Check if this request is for a local payment redirect.
	 */
	public static function local_payment_redirect() {
		if ( isset( $_GET['wc-stripe-local-gateway'], $_GET['_payment_nonce'] ) && wp_verify_nonce( $_GET['_payment_nonce'], 'local-payment' ) ) {
			self::process_redirect();
		}
	}

	/**
	 */
	public static function process_redirect() {
		if ( isset( $_GET['source'] ) ) {
			$result = WC_Stripe_Gateway::load()->sources->retrieve( wc_clean( $_GET['source'] ) );
		} else {
			$result = WC_Stripe_Gateway::load()->paymentIntents->retrieve( wc_clean( $_GET['payment_intent'] ) );
		}
		if ( is_wp_error( $result ) ) {
			wc_add_notice( sprintf( __( 'Error retrieving payment source. Reason: %s', 'woo-stripe-payment' ), $result->get_error_message() ), 'error' );

			return;
		} else {
			define( WC_Stripe_Constants::REDIRECT_HANDLER, true );
			$order_id = $result->metadata['order_id'];
			$order    = wc_get_order( wc_stripe_filter_order_id( $order_id, $result ) );

			/**
			 *
			 * @var WC_Payment_Gateway_Stripe_Local_Payment $payment_method
			 */
			$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
			$redirect       = $order->get_checkout_order_received_url();

			if ( in_array( $result->status, array( 'requires_action', 'pending' ) ) ) {
				wc_add_notice( __( 'Payment for order was not completed.', 'woo-stripe-payment' ), 'error' );

				return;
			} elseif ( in_array( $result->status, array( 'requires_payment_method', 'failed' ) ) ) {
				wc_add_notice( __( 'Payment authorization failed. Please select another payment method.', 'woo-stripe-payment' ), 'error' );
				if ( $result instanceof \Stripe\PaymentIntent ) {
					$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT, $result->jsonSerialize() );
				} else {
					$order->delete_meta_data( WC_Stripe_Constants::SOURCE_ID );
				}
				$order->update_status( 'failed', __( 'Payment authorization failed.', 'woo-stripe-payment' ) );

				return;
			} elseif ( 'chargeable' === $result->status ) {
				if ( ! $payment_method->has_order_lock( $order ) && ! $order->get_transaction_id() ) {
					$payment_method->set_order_lock( $order );
					$payment_method->set_new_source_token( $result->id );
					$result = $payment_method->process_payment( $order_id );
					// we don't release the order lock so there aren't conflicts with the source.chargeable webhook
					if ( $result['result'] === 'success' ) {
						$redirect = $result['redirect'];
					}
				}
			} elseif ( 'succeeded' == $result->status ) {
				if ( ! $payment_method->has_order_lock( $order ) ) {
					$payment_method->set_order_lock( $order );
					$result = $payment_method->process_payment( $order_id );
					if ( $result['result'] === 'success' ) {
						$redirect = $result['redirect'];
					}
				}
			}
			wp_safe_redirect( $redirect );
			exit();
		}
	}

	public static function maybe_restore_cart() {
		global $wp;
		if ( isset( $wp->query_vars['order-received'] ) && isset( $_GET['wc_stripe_product_checkout'] ) ) {
			add_action( 'woocommerce_cart_emptied', 'wc_stripe_restore_cart_after_product_checkout' );
		}
	}
}

WC_Stripe_Redirect_Handler::init();
