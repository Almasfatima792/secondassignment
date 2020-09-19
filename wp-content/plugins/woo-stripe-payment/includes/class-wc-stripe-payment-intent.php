<?php
defined( 'ABSPATH' ) || exit();

require_once( WC_STRIPE_PLUGIN_FILE_PATH . 'includes/abstract/abstract-wc-stripe-payment.php' );

/**
 *
 * @package Stripe/Classes
 * @author Payment Plugins
 * @since 3.1.0
 *
 */
class WC_Stripe_Payment_Intent extends WC_Stripe_Payment {

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::process_payment()
	 */
	public function process_payment( $order ) {
		// first check to see if a payment intent can be used
		if ( ( $intent = $this->can_use_payment_intent( $order ) ) ) {
			$intent_id = $intent['id'];
			if ( $this->can_update_payment_intent( $order ) ) {
				$intent = $this->gateway->paymentIntents->update( $intent_id, $this->get_payment_intent_args( $order, false ) );
			} else {
				$intent = $this->gateway->paymentIntents->retrieve( $intent_id );
			}
		} else {
			$intent = $this->gateway->paymentIntents->create( $this->get_payment_intent_args( $order ) );
		}

		if ( is_wp_error( $intent ) ) {
			$this->add_payment_failed_note( $order, $intent );

			return $intent;
		}

		// always update the order with the payment intent.
		$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT_ID, $intent->id );
		$order->update_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, $intent->payment_method );
		$order->update_meta_data( WC_Stripe_Constants::MODE, wc_stripe_mode() );
		// serialize the the intent and save to the order. The intent will be used to analyze if anything
		// has changed.
		$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT, $intent->jsonSerialize() );
		$order->save();

		if ( $intent->status === 'requires_confirmation' ) {
			$intent = $this->gateway->paymentIntents->confirm( $intent->id );
			if ( is_wp_error( $intent ) ) {
				$this->add_payment_failed_note( $order, $intent );

				return $intent;
			}
		}

		// the intent was processed.
		if ( $intent->status === 'succeeded' || $intent->status === 'requires_capture' ) {

			if ( isset( $intent['setup_future_usage'] ) && 'off_session' === $intent['setup_future_usage'] ) {
				$result = $this->payment_method->save_payment_method( $this->payment_method->get_new_source_token(), $order );
				if ( is_wp_error( $result ) ) {
					$this->payment_method->set_payment_save_error( $order, $result );
				}
			}

			// payment has been processed.
			$charges = $intent->charges;
			if ( count( $charges->data ) > 0 ) {
				$charge = $charges->data[0];

				return (object) array(
					'complete_payment' => true,
					'charge'           => $charge,
				);
			}
		}
		if ( $intent->status === 'requires_source_action' || $intent->status === 'requires_action' ) {
			return (object) array(
				'complete_payment' => false,
				'redirect'         => $this->payment_method->get_payment_intent_checkout_url( $intent, $order ),
			);
		}
		if ( $intent->status === 'requires_source' || $intent->status === 'requires_payment_method' ) {
			return (object) array(
				'complete_payment' => false,
				'redirect'         => $this->payment_method->get_payment_intent_checkout_url( $intent, $order ),
			);
		}
	}

	public function scheduled_subscription_payment( $amount, $order ) {
		$args = $this->get_payment_intent_args( $order );

		$args['confirm']        = true;
		$args['off_session']    = true;
		$args['payment_method'] = $this->payment_method->get_order_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, $order );

		if ( ( $customer = $this->payment_method->get_order_meta_data( WC_Stripe_Constants::CUSTOMER_ID, $order ) ) ) {
			$args['customer'] = $customer;
		}

		$intent = $this->gateway->paymentIntents->mode( wc_stripe_order_mode( $order ) )->create( $args );

		if ( is_wp_error( $intent ) ) {
			return $intent;
		} else {
			$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT_ID, $intent->id );

			$charge = $intent->charges->data[0];

			if ( $intent->status === 'succeeded' || $intent->status === 'requires_capture' ) {

				return (object) array(
					'complete_payment' => true,
					'charge'           => $charge,
				);
			} else {
				return (object) array(
					'complete_payment' => false,
					'charge'           => $charge,
				);
			}
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::process_pre_order_payment()
	 */
	public function process_pre_order_payment( $order ) {
		$args = $this->get_payment_intent_args( $order );

		$args['confirm']        = true;
		$args['off_session']    = true;
		$args['payment_method'] = $this->payment_method->get_order_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, $order );

		if ( ( $customer = $this->payment_method->get_order_meta_data( WC_Stripe_Constants::CUSTOMER_ID, $order ) ) ) {
			$args['customer'] = $customer;
		}

		$intent = $this->gateway->paymentIntents->mode( wc_stripe_order_mode( $order ) )->create( $args );

		if ( is_wp_error( $intent ) ) {
			return $intent;
		} else {
			$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT_ID, $intent->id );

			$charge = $intent->charges->data[0];

			if ( $intent->status === 'succeeded' || $intent->status === 'requires_capture' ) {
				return (object) array(
					'complete_payment' => true,
					'charge'           => $charge,
				);
			} else {
				return (object) array(
					'complete_payment' => false,
					'charge'           => $charge,
				);
			}
		}
	}

	/**
	 * Compares the order's saved intent to the order's attributes.
	 * If there is a delta, then the payment intent can be updated. The intent should
	 * only be updated if this is the checkout page.
	 *
	 * @param WC_Order $order
	 */
	public function can_update_payment_intent( $order ) {
		if ( ! is_checkout() || defined( WC_Stripe_Constants::REDIRECT_HANDLER ) ) {
			return false;
		}
		$intent = $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT );
		if ( $intent ) {
			$order_hash  = implode(
				'_',
				array(
					wc_stripe_add_number_precision( $order->get_total(), $order->get_currency() ),
					wc_stripe_get_customer_id( $order->get_user_id() ),
					$this->payment_method->get_payment_method_from_request(),
					$this->payment_method->get_payment_method_type(),
				)
			);
			$intent_hash = implode(
				'_',
				array(
					$intent['amount'],
					$intent['customer'],
					$intent['payment_method'],
					isset( $intent['payment_method_types'] ) ? $intent['payment_method_types'][0] : '',
				)
			);

			return $order_hash !== $intent_hash;
		}

		return false;
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function get_payment_intent_args( $order, $new = true ) {
		$this->add_general_order_args( $args, $order );

		if ( $new ) {
			$args['confirmation_method'] = $this->payment_method->get_confirmation_method( $order );
			$args['capture_method']      = $this->payment_method->get_option( 'charge_type' ) === 'capture' ? 'automatic' : 'manual';
			$args['confirm']             = false;
		}

		if ( get_option( 'woocommerce_stripe_email_receipt', 'no' ) === 'yes' && ( $email = $order->get_billing_email() ) ) {
			$args['receipt_email'] = $email;
		}

		if ( ( $customer_id = wc_stripe_get_customer_id( $order->get_customer_id() ) ) ) {
			$args['customer'] = $customer_id;
		}

		if ( $this->payment_method->should_save_payment_method( $order ) ) {
			$args['setup_future_usage'] = 'off_session';
		}

		$args['payment_method_types'][] = $this->payment_method->get_payment_method_type();

		$this->payment_method->add_stripe_order_args( $args, $order );

		/**
		 * @param array $args
		 * @param WC_Order $order
		 * @param WC_Stripe_Payment_Intent $this
		 */
		return apply_filters( 'wc_stripe_payment_intent_args', $args, $order, $this );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::capture_charge()
	 */
	public function capture_charge( $amount, $order ) {
		$payment_intent = $this->payment_method->get_order_meta_data( WC_Stripe_Constants::PAYMENT_INTENT_ID, $order );
		if ( empty( $payment_intent ) ) {
			$charge         = $this->gateway->charges->mode( wc_stripe_order_mode( $order ) )->retrieve( $order->get_transaction_id() );
			$payment_intent = $charge->payment_intent;
			$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT_ID, $payment_intent );
			$order->save();
		}

		return $this->gateway->paymentIntents->mode( wc_stripe_order_mode( $order ) )->capture(
			$payment_intent,
			array(
				'amount_to_capture' => wc_stripe_add_number_precision(
					$amount,
					$order->get_currency()
				),
			)
		);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::void_charge()
	 */
	public function void_charge( $order ) {
		// fetch the intent and check its status
		$payment_intent = $this->gateway->paymentIntents->mode( wc_stripe_order_mode( $order ) )->retrieve( $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT_ID ) );
		if ( is_wp_error( $payment_intent ) ) {
			return $payment_intent;
		}
		$statuses = array( 'requires_payment_method', 'requires_capture', 'requires_confirmation', 'requires_action' );
		if ( 'canceled' !== $payment_intent->status ) {
			if ( in_array( $payment_intent->status, $statuses ) ) {
				return $this->gateway->paymentIntents->mode( wc_stripe_order_mode( $order ) )->cancel( $payment_intent->id );
			} elseif ( 'succeeded' === $payment_intent->status ) {
				return $this->process_refund( $order, $order->get_total() );
			}
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::get_payment_method_from_charge()
	 */
	public function get_payment_method_from_charge( $charge ) {
		return $charge->payment_method;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::add_order_payment_method()
	 */
	public function add_order_payment_method( &$args, $order ) {
		$args['payment_method'] = $this->payment_method->get_payment_method_from_request();
		if ( empty( $args['payment_method'] ) ) {
			unset( $args['payment_method'] );
		}
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function can_use_payment_intent( $order ) {
		$intent = $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT );

		if ( $intent ) {
			if ( $intent['confirmation_method'] != $this->payment_method->get_confirmation_method( $order ) ) {
				return false;
			}

			// compare the active environment to the order's environment
			if ( wc_stripe_order_mode( $order ) != wc_stripe_mode() ) {
				return false;
			}

			return $intent;
		}

		return false;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::can_void_charge()
	 */
	public function can_void_order( $order ) {
		return $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT_ID );
	}
}
