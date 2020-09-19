<?php
return array(
	'desc'                 => array(
		'type'        => 'description',
		'description' => sprintf( '<div>%s</div>', __( 'For US customers only.', 'woo-stripe-payment' ) ) .
			 sprintf( '<p>%s</p>', sprintf( __( 'Read through our %1$sdocumentation%2$s to configure ACH payments', 'woo-stripe-payment' ), '<a target="_blank" href="https://docs.paymentplugins.com/wc-stripe/config/#/stripe_ach">', '</a>' ) ),
	),
	'enabled'              => array(
		'title'       => __( 'Enabled', 'woo-stripe-payment' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept ACH payments through Stripe.', 'woo-stripe-payment' ),
	),
	'environment'          => array(
		'type'        => 'select',
		'title'       => __( 'Plaid Environment', 'woo-stripe-payment' ),
		'options'     => array(
			'sandbox'     => __( 'Sandbox', 'woo-stripe-payment' ),
			'development' => __( 'Development', 'woo-stripe-payment' ),
			'production'  => __( 'Production', 'woo-stripe-payment' ),
		),
		'description' => __( 'This setting determines the Plaid environment you are connecting with. When you are ready to accept live transactions, switch this option to Production.<br><strong>Production</strong> - accept live ACH payments <br><strong>Development</strong> - use real bank login details with test transactions <br><strong>Sandbox</strong> - test integration using test credentials', 'woo-stripe-payment' ),
	),
	/* 'plaid_keys' => array( 'type' => 'title',
				'title' => __ ( 'Plaid Keys', 'wo-stripe-paymento' )
		),
		'client_id' => array( 'type' => 'text',
				'title' => __ ( 'Client ID' ),
				'default' => '',
				'description' => __ ( 'ID that identifies your Plaid account.', 'woo-stripe-payment' ),
				'desc_tip' => true
		),
		'public_key' => array( 'type' => 'text',
				'title' => __ ( 'Public Key' ),
				'default' => '',
				'description' => __ ( 'Used to identify ACH payments initiated from your site.', 'woo-stripe-payment' ),
				'desc_tip' => true
		),
		'plaid_secrets' => array( 'type' => 'title',
				'title' => __ ( 'Plaid Secrets', 'wo-stripe-paymento' )
		),
		'sandbox_secret' => array(
				'title' => __ ( 'Sandbox Secret', 'woo-stripe-payment' ),
				'type' => 'password', 'default' => '',
				'description' => __ ( 'Key that acts as a password when connecting to Plaid\'s sandbox environment.', 'woo-stripe-payment' ),
				'desc_tip' => true
		),
		 'development_secret' => array(
				'title' => __ ( 'Development Secret', 'woo-stripe-payment' ),
				'type' => 'password', 'default' => '',
				'description' => __ ( '', 'woo-stripe-payment' ),
				'desc_tip' => true
		),
		'production_secret' => array(
				'title' => __ ( 'Production Secret', 'woo-stripe-payment' ),
				'type' => 'password', 'default' => '',
				'description' => __ ( 'Key that acts as a password when connecting to Plaid\'s production environment.', 'woo-stripe-payment' ),
				'desc_tip' => true
		), */
		'general_settings' => array(
			'type'  => 'title',
			'title' => __( 'General Settings', 'woo-stripe-payment' ),
		),
	'title_text'           => array(
		'type'        => 'text',
		'title'       => __( 'Title', 'woo-stripe-payment' ),
		'default'     => __( 'ACH Payment', 'woo-stripe-payment' ),
		'desc_tip'    => true,
		'description' => __( 'Title of the ACH gateway' ),
	),
	'description'          => array(
		'title'       => __( 'Description', 'woo-stripe-payment' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __( 'Leave blank if you don\'t want a description to show for the gateway.', 'woo-stripe-payment' ),
		'desc_tip'    => true,
	),
	'client_name'          => array(
		'type'        => 'text',
		'title'       => __( 'Client Name', 'woo-stripe-payment' ),
		'default'     => get_bloginfo( 'name' ),
		'description' => __( 'The name that appears on the ACH payment screen.', 'woo-stripe-payment' ),
		'desc_tip'    => true,
	),
	'method_format'        => array(
		'title'       => __( 'ACH Display', 'woo-stripe-payment' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'options'     => wp_list_pluck( $this->get_method_formats(), 'example' ),
		'value'       => '',
		'default'     => 'type_ending_in',
		'desc_tip'    => true,
		'description' => __( 'This option allows you to customize how the credit card will display for your customers on orders, subscriptions, etc.' ),
	),
	'fee'                  => array(
		'title'       => __( 'ACH Fee', 'woo-stripe-payment' ),
		'type'        => 'ach_fee',
		'class'       => '',
		'value'       => '',
		'default'     => array(
			'type'    => 'none',
			'taxable' => 'no',
			'value'   => '0',
		),
		'options'     => array(
			'none'    => __( 'None', 'woo-stripe-payment' ),
			'amount'  => __( 'Amount', 'woo-stripe-payment' ),
			'percent' => __( 'Percentage', 'woo-stripe-payment' ),
		),
		'desc_tip'    => true,
		'description' => __( 'You can assign a fee to the order for ACH payments. Amount is a static amount and percentage is a percentage of the cart amount.', 'woo-stripe-payment' ),
	),
);
