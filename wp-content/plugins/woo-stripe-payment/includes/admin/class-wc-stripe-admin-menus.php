<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Stripe/Admin
 *
 */
class WC_Stripe_Admin_Menus {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 10 );
		add_action( 'admin_menu', array( __CLASS__, 'sub_menu' ), 20 );
		add_action( 'admin_head', array( __CLASS__, 'remove_submenu' ) );
	}

	public static function admin_menu() {
		add_menu_page( __( 'Stripe Gateway', 'woo-stripe-payment' ), __( 'Stripe Gateway', 'woo-stripe-payment' ), 'manage_woocommerce', 'wc_stripe', null, null, '7.458' );
	}

	public static function sub_menu() {
		add_submenu_page( 'wc_stripe', __( 'Settings', 'woo-stripe-payment' ), __( 'Settings', 'woo-stripe-payment' ), 'manage_woocommerce', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stripe_api' ) );
		add_submenu_page( 'wc_stripe', __( 'Logs', 'woo-stripe-payment' ), __( 'Logs', 'woo-stripe-payment' ), 'manage_woocommerce', admin_url( 'admin.php?page=wc-status&tab=logs' ) );
		add_submenu_page( 'wc_stripe', __( 'Documentation', 'woo-stripe-payment' ), __( 'Documentation', 'woo-stripe-payment' ), 'manage_woocommerce', 'https://docs.paymentplugins.com/wc-stripe/config' );
	}

	public static function remove_submenu() {
		global $submenu;
		if ( isset( $submenu['wc_stripe'] ) ) {
			unset( $submenu['wc_stripe'][0] );
		}
	}

	public static function data_migration_page() {
		include 'views/html-data-migration.php';
	}
}
WC_Stripe_Admin_Menus::init();
