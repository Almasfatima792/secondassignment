<?php
global $wc_stripe_subsection;
$tabs = apply_filters( 'wc_stripe_local_gateways_tab', array() );
?>
<div class="wc-stripe-advanced-settings-nav local-gateways">
	<?php foreach ( $tabs as $id => $tab ) : ?>
		<a
		class="nav-link 
		<?php
		if ( $wc_stripe_subsection === $id ) {
			echo 'nav-link-active';}
		?>
		"
		href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stripe_local_gateways&stripe_sub_section=' . $id ); ?>"><?php echo esc_attr( $tab ); ?></a>
	<?php endforeach; ?>
</div>
<div class="clear"></div>
