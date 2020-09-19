<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author Payment Plugins
 * @since 3.0.0
 * @package Stripe/Classes
 *
 */
class WC_Stripe_API_Settings extends WC_Stripe_Settings_API {

	public function __construct() {
		$this->id        = 'stripe_api';
		$this->tab_title = __( 'API Settings', 'woo-stripe-payment' );
		parent::__construct();
	}

	public function hooks() {
		parent::hooks();
		add_action( 'woocommerce_update_options_checkout_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'wc_stripe_settings_nav_tabs', array( $this, 'admin_nav_tab' ) );
		add_action( 'woocommerce_settings_checkout_' . $this->id, array( $this, 'admin_options' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'title'               => array(
				'type'  => 'title',
				'title' => __( 'API Settings', 'woo-stripe-payment' ),
			),
			'mode'                => array(
				'type'        => 'select',
				'title'       => __( 'Mode', 'woo-stripe-payment' ),
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'test' => __( 'Test', 'woo-stripe-payment' ),
					'live' => __( 'Live', 'woo-stripe-payment' ),
				),
				'default'     => 'test',
				'desc_tip'    => true,
				'description' => __( 'The mode determines if you are processing test transactions or live transactions on your site. Test mode allows you to simulate payments so you can test your integration.', 'woo-stripe-payment' ),
			),
			'account_id'          => array(
				'type'        => 'paragraph',
				'title'       => __( 'Account ID', 'woo-stripe-payment' ),
				'text'        => '',
				'class'       => '',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'This is your Stripe Connect ID and serves as a unique identifier.', 'woo-stripe-payment' ),
			),
			'stripe_connect'      => array(
				'type'        => 'stripe_connect',
				'title'       => __( 'Connect Stripe Account', 'woo-stripe-payment' ),
				'label'       => __( 'Click to Connect', 'woo-stripe-payment' ),
				'class'       => 'do-stripe-connect',
				'description' => __( 'We make it easy to connect Stripe to your site. Click the Connect button to go through our connect flow.', 'woo-stripe-payment' ),
			),
			'connection_test'     => array(
				'type'        => 'stripe_button',
				'title'       => __( 'Connection Test', 'woo-stripe-payment' ),
				'label'       => __( 'Connection Test', 'woo-stripe-payment' ),
				'class'       => 'wc-stripe-connection-test button-secondary',
				'description' => __( 'Click this button to perform a connection test. If successful, your site is connected to Stripe.', 'woo-stripe-payment' ),
			),
			'webhook_url'         => array(
				'type'        => 'paragraph',
				'title'       => __( 'Webhook url', 'woo-stripe-payment' ),
				'class'       => 'wc-stripe-webhook',
				'text'        => wc_stripe()->rest_api->webhook->rest_url( 'webhook' ),
				'description' => sprintf( __( '<strong>Important:</strong> the webhook url is called by Stripe when events occur in your account, like a source becomes chargeable. You must add this webhook to your Stripe Dashboard if you are using any of the local gateways. %1$sWebhook guide%2$s', 'woo-stripe-payment' ), '<a target="_blank" href="https://docs.paymentplugins.com/wc-stripe/config/#/webhooks?id=configure-webhooks">', '</a>' ),
			),
			'webhook_secret_live' => array(
				'type'              => 'password',
				'title'             => __( 'Live Webhook Secret', 'woo-stripe-payment' ),
				'description'       => sprintf( __( 'The webhook secret is used to authenticate webhooks sent from Stripe. It ensures no 3rd party can send you events, pretending to be Stripe. %1$sWebhook guide%2$s', 'woo-stripe-payment' ), '<a target="_blank" href="https://docs.paymentplugins.com/wc-stripe/config/#/webhooks?id=configure-webhooks">', '</a>' ),
				'custom_attributes' => array( 'data-show-if' => array( 'mode' => 'live' ) ),
			),
			'webhook_secret_test' => array(
				'type'              => 'password',
				'title'             => __( 'Test Webhook Secret', 'woo-stripe-payment' ),
				'description'       => sprintf( __( 'The webhook secret is used to authenticate webhooks sent from Stripe. It ensures no 3rd party can send you events, pretending to be Stripe. %1$sWebhook guide%2$s', 'woo-stripe-payment' ), '<a target="_blank" href="https://docs.paymentplugins.com/wc-stripe/config/#/webhooks?id=configure-webhooks">', '</a>' ),
				'custom_attributes' => array( 'data-show-if' => array( 'mode' => 'test' ) ),
			),
			'debug_log'           => array(
				'title'       => __( 'Debug Log', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'desc_tip'    => true,
				'default'     => 'yes',
				'description' => __( 'When enabled, the plugin logs important errors and info that can help you troubleshoot potential issues.', 'woo-stripe-payment' ),
			),
		);
		if ( $this->get_option( 'account_id' ) ) {
			$this->form_fields['account_id']['text']            = $this->get_option( 'account_id' );
			$this->form_fields['stripe_connect']['description'] = sprintf( __( '%s Your Stripe account has been connected. You can now accept Live and Test payments. You can Re-Connect if you want to recycle your API keys for security.', 'woo-stripe-payment' ), '<span class="dashicons dashicons-yes stipe-connect-active"></span>' );
			$this->form_fields['stripe_connect']['active']      = true;
		} else {
			unset( $this->form_fields['account_id'], $this->form_fields['connection_test'], $this->form_fields['mode'], $this->form_fields['webhook_url'] );
		}
	}

	public function generate_stripe_connect_html( $key, $data ) {
		$field_key           = $this->get_field_key( $key );
		$data                = wp_parse_args(
			$data,
			array(
				'class'       => '',
				'style'       => '',
				'description' => '',
				'desc_tip'    => false,
				'css'         => '',
				'active'      => false,
			)
		);
		$data['connect_url'] = $this->get_connect_url();
		if ( $data['active'] ) {
			$data['label'] = __( 'Click To Re-Connect', 'woo-stripe-payment' );
		}
		ob_start();
		include wc_stripe()->plugin_path() . 'includes/admin/views/html-stripe-connect.php';

		return ob_get_clean();
	}

	public function admin_options() {
		// Check if user is being returned from Stripe Connect
		if ( isset( $_GET['_stripe_connect_nonce'] ) && wp_verify_nonce( $_GET['_stripe_connect_nonce'], 'stripe-connect' ) ) {
			if ( isset( $_GET['error'] ) ) {
				$error = json_decode( base64_decode( wc_clean( $_GET['error'] ) ) );
				if ( property_exists( $error, 'message' ) ) {
					$message = $error->message;
				} elseif ( property_exists( $error, 'raw' ) ) {
					$message = $error->raw->message;
				} else {
					$message = __( 'Please try again.', 'woo-stripe-payment' );
				}
				wc_stripe_log_error( sprintf( 'Error connecting to Stripe account. Reason: %s', $message ) );
				$this->add_error( sprintf( __( 'We were not able to connect your Stripe account. Reason: %s', 'woo-stripe-payment' ), $message ) );
			} elseif ( isset( $_GET['response'] ) ) {
				$response = json_decode( base64_decode( $_GET['response'] ) );

				// save the token to the api settings
				$this->settings['account_id']    = $response->live->stripe_user_id;
				$this->settings['refresh_token'] = $response->live->refresh_token;

				$this->settings['secret_key_live']      = $response->live->access_token;
				$this->settings['publishable_key_live'] = $response->live->stripe_publishable_key;

				$this->settings['secret_key_test']      = $response->test->access_token;
				$this->settings['publishable_key_test'] = $response->test->stripe_publishable_key;

				update_option( $this->get_option_key(), $this->settings );

				delete_option( 'wc_stripe_connect_notice' );

				/**
				 * @param array $response
				 * @param WC_Stripe_API_Settings $this
				 *
				 * @since 3.1.6
				 */
				do_action( 'wc_stripe_connect_settings', $response, $this );

				$this->init_form_fields();

				echo '<div class="updated inline notice-success is-dismissible "><p>' .
				     __( 'Your Stripe account has been connected to your WooCommerce store. You may now accept payments in Live and Test mode.', 'woo-stripe-payment' ) .
				     '</p></div>';
			}
		}
		parent::admin_options();
	}

	public function get_connect_url() {
		return \Stripe\OAuth::authorizeUrl( array(
			'response_type'  => 'code',
			'client_id'      => wc_stripe()->client_id,
			'stripe_landing' => 'login',
			'always_prompt'  => 'true',
			'scope'          => 'read_write',
			'state'          => base64_encode(
				wp_json_encode(
					array(
						'redirect' => add_query_arg( '_stripe_connect_nonce', wp_create_nonce( 'stripe-connect' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stripe_api' ) )
					)
				)
			)
		) );
	}
}
