<?php
global $current_section;
$tabs = apply_filters( 'wc_stripe_settings_nav_tabs', array() );
?>
<div class="wc-stripe-settings-logo">
	<img
		src="<?php echo wc_stripe()->assets_url() . 'img/stripe_logo.svg'; ?>" />
</div>
<div class="stripe-settings-nav">
<?php foreach ( $tabs as $id => $tab ) : ?>
		<a
		class="nav-tab 
		<?php
		if ( $current_section === $id ) {
			echo 'nav-tab-active';}
		?>
		"
		href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $id ); ?>"><?php echo esc_attr( $tab ); ?></a>
	<?php endforeach; ?>
</div>
<div class="clear"></div>
<div class="wc-stripe-docs">
	<a target="_blank" class="button button-secondary"
		href="https://docs.paymentplugins.com/wc-stripe/config/#/<?php echo $current_section; ?>"><?php _e( 'Documentation', 'woo-stripe-payment' ); ?></a>
</div>
