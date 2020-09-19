<?php
/**
 * @var WP_User $user
 */
?>
<div class="wc-stripe-user-info">
	<h2><?php _e( 'Stripe Customer ID\'s', 'woo-stripe-payment' ); ?></h2>
	<p><?php _e( 'If you change a customer ID, the customer\'s payment methods will be imported from your Stripe account.' ); ?></p>
	<p><?php _e( 'If you remove a customer ID, the customer\'s payment methods will be removed from the WC payment methods table.' ); ?></p>
	<table class="form-table">
		<tbody>
			<tr>
				<th><?php _e( 'Live ID', 'woo-stripe-payment' ); ?></th>
				<td><input type="text" id="wc_stripe_live_id"
					name="wc_stripe_live_id"
					value="<?php echo wc_stripe_get_customer_id( $user->ID, 'live' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Test ID', 'woo-stripe-payment' ); ?></th>
				<td><input type="text" id="wc_stripe_test_id"
					name="wc_stripe_test_id"
					value="<?php echo wc_stripe_get_customer_id( $user->ID, 'test' ); ?>" />
				</td>
			</tr>
		</tbody>
	</table>
	<h2><?php _e( 'Stripe Live Payment Methods', 'woo-stripe-payment' ); ?></h2>
	<?php if ( $payment_methods['live'] ) : ?>
	<table class="wc-stripe-payment-methods">
		<thead>
			<tr>
				<th><?php _e( 'Payment Gateway', 'woo-stripe-payment' ); ?></th>
				<th><?php _e( 'Payment Method', 'woo-stripe-payment' ); ?></th>
				<th><?php _e( 'Token', 'woo-stripe-payment' ); ?></th>
				<th><?php _e( 'Actions', 'woo-stripe-payment' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $payment_methods['live'] as $token ) : ?>
				<tr>
				<td><?php echo $token->get_gateway_id(); ?></td>
				<td><?php echo $token->get_payment_method_title(); ?></td>
				<td><?php echo $token->get_token(); ?></td>
				<td><input type="checkbox" name="payment_methods[live][]"
					value="<?php echo $token->get_id(); ?>" /></td>
			</tr>
			<?php endforeach; ?>
			<tr>
				<th><?php _e( 'Action', 'delete' ); ?></th>
				<td><select name="live_payment_method_actions">
						<option value="none" selected><?php _e( 'No Action', 'woo-stripe-payment' ); ?></option>
						<option value="delete"><?php _e( 'Delete', 'woo-stripe-payment' ); ?></option>
				</select></td>
			</tr>
		</tbody>
	</table>
	<?php else : ?>
		<?php _e( 'No live payment methods saved', 'woo-stripe-payment' ); ?>
	<?php endif; ?>
	<h2><?php _e( 'Stripe Test Payment Methods', 'woo-stripe-payment' ); ?></h2>
	<?php if ( $payment_methods['test'] ) : ?>
	<table class="wc-stripe-payment-methods">
		<thead>
			<tr>
				<th><?php _e( 'Payment Gateway', 'woo-stripe-payment' ); ?></th>
				<th><?php _e( 'Payment Method', 'woo-stripe-payment' ); ?></th>
				<th><?php _e( 'Token', 'woo-stripe-payment' ); ?></th>
				<th><?php _e( 'Actions', 'woo-stripe-payment' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $payment_methods['test'] as $token ) : ?>
				<tr>
				<td><?php echo $token->get_gateway_id(); ?></td>
				<td><?php echo $token->get_payment_method_title(); ?></td>
				<td><?php echo $token->get_token(); ?></td>
				<td><input type="checkbox" name="payment_methods[test][]"
					value="<?php echo $token->get_id(); ?>" /></td>
			</tr>
			<?php endforeach; ?>
			<tr>
				<th><?php _e( 'Action', 'delete' ); ?></th>
				<td><select name="test_payment_method_actions">
						<option value="none" selected><?php _e( 'No Action', 'woo-stripe-payment' ); ?></option>
						<option value="delete"><?php _e( 'Delete', 'woo-stripe-payment' ); ?></option>
				</select></td>
			</tr>
		</tbody>
	</table>
	<?php else : ?>
		<?php _e( 'No test payment methods saved', 'woo-stripe-payment' ); ?>
	<?php endif; ?>
	<?php printf( __( '%1$snote:%2$s payment methods will be deleted in Stripe if you use the delete action.', 'woo-stripe-payment' ), '<strong>', '</strong>' ); ?>
</div>
