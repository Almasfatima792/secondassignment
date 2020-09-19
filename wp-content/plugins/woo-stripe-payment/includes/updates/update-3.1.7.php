<?php
$account_id = wc_stripe()->api_settings->get_option( 'account_id' );
if ( $account_id ) {
	wc_stripe()->account_settings->save_account_settings( $account_id );
}