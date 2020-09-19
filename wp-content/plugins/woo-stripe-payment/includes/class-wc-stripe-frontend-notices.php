<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class that manages frontend notices for customers.
 *
 * @author PaymentPlugins
 * @package Stripe/Classes
 * @since 3.0.0
 *
 */
class WC_Stripe_Frontend_Notices {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	public static function enqueue_scripts() {
		if ( isset( $wp->query_vars['order-received'] ) ) {
			$scripts  = wc_stripe()->scripts();
			$order_id = absint( $wp->query_vars['order-received'] );
			$order    = wc_get_order( $order_id );
			$notices  = array();
			if ( ( $message = $order->get_meta( '_wc_stripe_order_error', true ) ) ) {
				$notices[] = wc_get_template_html( 'notices/notice.php', array( 'messages' => array( $message ) ) );
			}
			if ( $order->has_status( 'on-hold' ) ) {
				$payment_method = $order->get_payment_method();
				$gateway        = WC()->payment_gateways()->payment_gateways()[ $payment_method ];
				if ( $gateway instanceof WC_Payment_Gateway_Stripe_Local_Payment ) {
					$notices[] = wc_get_template_html(
						'notices/notice.php',
						array(
							'messages' => array(
								__( 'Your payment is being processed and your order status will be updated once the funds are received.', 'woo-stripe-payment' ),
							),
						)
					);
				}
				if ( $notices ) {
					self::enqueue_notices( $notices );
				}
			}
		}
	}

	public static function enqueue_notices( $notices ) {
		$scripts->enqueue_script( 'notices', $scripts->assets_url( 'js/frontend/notices.js' ), array( 'jquery' ), wc_stripe()->version(), true );
		$scripts->localize_script(
			'notices',
			array(
				'container' => '.woocommerce-order',
				'notices'   => $notices,
			)
		);
	}
}
WC_Stripe_Frontend_Notices::init();
