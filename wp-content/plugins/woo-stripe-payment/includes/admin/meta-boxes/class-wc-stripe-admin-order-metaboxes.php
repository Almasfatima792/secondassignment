<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Admin
 * @author PaymentPlugins
 *
 */
class WC_Stripe_Admin_Order_Metaboxes {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 10, 2 );
	}

	/**
	 *
	 * @param string $post_type
	 * @param WP_Post $post
	 */
	public static function add_meta_boxes( $post_type, $post ) {
		// only add meta box if shop_order and Stripe gateway was used.
		if ( $post_type !== 'shop_order' ) {
			return;
		}

		add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'pay_order_section' ) );

		$order          = wc_get_order( $post->ID );
		$payment_method = $order->get_payment_method();
		if ( $payment_method ) {
			$gateways = WC()->payment_gateways()->payment_gateways();
			if ( isset( $gateways[ $payment_method ] ) ) {
				$gateway = WC()->payment_gateways()->payment_gateways()[ $payment_method ];
				if ( $gateway instanceof WC_Payment_Gateway_Stripe ) {
					add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'charge_data_view' ) );
				}
			}
		}
		self::enqueue_scripts();
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public static function charge_data_view( $order ) {
		if ( ( $transaction_id = $order->get_transaction_id() ) ) {
			include 'views/html-order-charge-data.php';
		}
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public static function pay_order_section( $order ) {
		if ( $order->get_type() === 'shop_order' && $order->has_status( array( 'pending', 'auto-draft' ) ) ) {
			include 'views/html-order-pay.php';
			$payment_methods = array();
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
				if ( $gateway instanceof WC_Payment_Gateway_Stripe ) {
					$payment_methods = array_merge( $payment_methods, WC_Payment_Tokens::get_customer_tokens( $order->get_user_id(), $gateway->id ) );
				}
			}
			wp_enqueue_script( 'wc-stripe-elements', 'https://js.stripe.com/v3/', array(), wc_stripe()->version, true );
			wp_localize_script(
				'wc-stripe-elements',
				'wc_stripe_order_pay_params',
				array(
					'api_key'         => wc_stripe_get_publishable_key(),
					'payment_methods' => array_map(
						function ( $payment_method ) {
							return $payment_method->to_json();
						},
						$payment_methods
					),
					'order_status'    => $order->get_status(),
				)
			);
			wp_enqueue_script( 'wc-stripe-admin-modals', wc_stripe()->assets_url( 'js/admin/modals.js' ), array( 'wc-backbone-modal', 'jquery-blockui' ), wc_stripe()->version, true );
		}
	}

	public static function enqueue_scripts() {
		wp_enqueue_script( 'wc-stripe-order-metabox', wc_stripe()->assets_url( 'js/admin/meta-boxes-order.js' ), array( 'jquery', 'jquery-blockui' ), wc_stripe()->version(), true );

		wp_localize_script(
			'wc-stripe-order-metabox',
			'wc_stripe_order_metabox_params',
			array(
				'_wpnonce' => wp_create_nonce( 'wp_rest' ),
				'routes'   => array(
					'charge_view'     => wc_stripe()->rest_api->order_actions->rest_url( 'charge-view' ),
					'capture'         => wc_stripe()->rest_api->order_actions->rest_url( 'capture' ),
					'void'            => wc_stripe()->rest_api->order_actions->rest_url( 'void' ),
					'pay'             => wc_stripe()->rest_api->order_actions->rest_url( 'pay' ),
					'payment_methods' => wc_stripe()->rest_api->order_actions->rest_url( 'customer-payment-methods' ),
				),
			)
		);
	}
}
WC_Stripe_Admin_Order_Metaboxes::init();
