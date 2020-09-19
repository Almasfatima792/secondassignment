<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class WC_Stripe_Account_Settings
 *
 * @author Payment Plugins
 * @since 3.1.7
 * @package Stripe/Classes
 */
class WC_Stripe_Account_Settings extends WC_Stripe_Settings_API {

	public function __construct() {
		$this->id = 'stripe_account';
		parent::__construct();
	}

	public function hooks() {
		add_action( 'wc_stripe_connect_settings', array( $this, 'connect_settings' ) );
	}

	/**
	 * @param object $response
	 */
	public function connect_settings( $response ) {
		$this->save_account_settings( $response->live->stripe_user_id );
	}

	/**
	 * @param string $account_id
	 */
	public function save_account_settings( $account_id ) {
		// fetch the account and store the account data.
		$account = WC_Stripe_Gateway::load( 'live' )->accounts->retrieve( $account_id );
		if ( ! is_wp_error( $account ) ) {
			$this->settings['account_id']       = $account->id;
			$this->settings['country']          = strtoupper( $account->country );
			$this->settings['default_currency'] = strtoupper( $account->default_currency );
			update_option( $this->get_option_key(), $this->settings, 'yes' );
		}
	}
}