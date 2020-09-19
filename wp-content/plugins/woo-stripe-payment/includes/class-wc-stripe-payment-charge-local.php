<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author PaymentPlugins
 * @since 3.1.1
 * @package Stripe/Classes
 *
 */
class WC_Stripe_Payment_Charge_Local extends WC_Stripe_Payment_Charge {

	/**
	 *
	 * @param WC_Order $order
	 */
	public function process_payment( $order ) {

		/**
		 * If there is no order lock, then this is not being processed via a webhook
		 */
		if ( ! $this->payment_method->has_order_lock( $order ) ) {
			try {
				if ( ( $source_id = $this->payment_method->get_new_source_token() ) ) {
					// source was created client side.
					$source = $this->gateway->sources->mode( wc_stripe_order_mode( $order ) )->retrieve( $source_id );

					if ( is_wp_error( $source ) ) {
						return $source;
					}

					// update the source's metadata with the order id
					$this->gateway->sources->mode( wc_stripe_order_mode( $order ) )->update(
						$source_id,
						array(
							'metadata' => array(
								'order_id' => $order->get_id(),
								'created'  => time(),
							),
						)
					);
				} else {
					// create the source
					$args                         = $this->payment_method->get_source_args( $order );
					$args['metadata']['order_id'] = $order->get_id();
					$args['metadata']['created']  = time();
					$source                       = $this->gateway->sources->mode( wc_stripe_order_mode( $order ) )->create( $args );
				}

				if ( is_wp_error( $source ) ) {
					throw new Exception( $source->get_error_message() );
				}

				$order->update_meta_data( WC_Stripe_Constants::SOURCE_ID, $source->id );
				$order->update_meta_data( WC_Stripe_Constants::MODE, wc_stripe_mode() );

				$order->save();

				/**
				 * If source is chargeable, then proceed with processing it.
				 */
				if ( $source->status === 'chargeable' ) {
					$this->payment_method->set_order_lock( $order );
					$this->payment_method->set_new_source_token( $source->id );

					return $this->process_payment( $order );
				}

				return (object) array(
					'complete_payment' => false,
					'redirect'         => $this->payment_method->get_source_redirect_url( $source, $order ),
				);
			} catch ( Exception $e ) {
				return new WP_Error( 'source-error', $e->getMessage() );
			}
		} else {
			/**
			 * There is an order lock so this order is ready to be processed.
			 */
			return parent::process_payment( $order );
		}
	}
}
