<?php
defined( 'ABSPATH' ) || exit();

require_once( WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-api-operation.php' );

/**
 * Gateway class that abstracts all API calls to Stripe.
 *
 * @author Payment Plugins
 * @package Stripe/Classes
 *
 * @property \Stripe\Service\AccountLinkService $accountLinks
 * @property \Stripe\Service\AccountService $accounts
 * @property \Stripe\Service\ApplePayDomainService $applePayDomains
 * @property \Stripe\Service\ApplicationFeeService $applicationFees
 * @property \Stripe\Service\BalanceService $balance
 * @property \Stripe\Service\BalanceTransactionService $balanceTransactions
 * @property \Stripe\Service\BillingPortal\BillingPortalServiceFactory $billingPortal
 * @property \Stripe\Service\ChargeService $charges
 * @property \Stripe\Service\Checkout\CheckoutServiceFactory $checkout
 * @property \Stripe\Service\CountrySpecService $countrySpecs
 * @property \Stripe\Service\CouponService $coupons
 * @property \Stripe\Service\CreditNoteService $creditNotes
 * @property \Stripe\Service\CustomerService $customers
 * @property \Stripe\Service\DisputeService $disputes
 * @property \Stripe\Service\EphemeralKeyService $ephemeralKeys
 * @property \Stripe\Service\EventService $events
 * @property \Stripe\Service\ExchangeRateService $exchangeRates
 * @property \Stripe\Service\FileLinkService $fileLinks
 * @property \Stripe\Service\FileService $files
 * @property \Stripe\Service\InvoiceItemService $invoiceItems
 * @property \Stripe\Service\InvoiceService $invoices
 * @property \Stripe\Service\Issuing\IssuingServiceFactory $issuing
 * @property \Stripe\Service\MandateService $mandates
 * @property \Stripe\Service\OrderReturnService $orderReturns
 * @property \Stripe\Service\OrderService $orders
 * @property \Stripe\Service\PaymentIntentService $paymentIntents
 * @property \Stripe\Service\PaymentMethodService $paymentMethods
 * @property \Stripe\Service\PayoutService $payouts
 * @property \Stripe\Service\PlanService $plans
 * @property \Stripe\Service\PriceService $prices
 * @property \Stripe\Service\ProductService $products
 * @property \Stripe\Service\Radar\RadarServiceFactory $radar
 * @property \Stripe\Service\RefundService $refunds
 * @property \Stripe\Service\Reporting\ReportingServiceFactory $reporting
 * @property \Stripe\Service\ReviewService $reviews
 * @property \Stripe\Service\SetupIntentService $setupIntents
 * @property \Stripe\Service\Sigma\SigmaServiceFactory $sigma
 * @property \Stripe\Service\SkuService $skus
 * @property \Stripe\Service\SourceService $sources
 * @property \Stripe\Service\SubscriptionItemService $subscriptionItems
 * @property \Stripe\Service\SubscriptionScheduleService $subscriptionSchedules
 * @property \Stripe\Service\SubscriptionService $subscriptions
 * @property \Stripe\Service\TaxRateService $taxRates
 * @property \Stripe\Service\Terminal\TerminalServiceFactory $terminal
 * @property \Stripe\Service\TokenService $tokens
 * @property \Stripe\Service\TopupService $topups
 * @property \Stripe\Service\TransferService $transfers
 * @property \Stripe\Service\WebhookEndpointService $webhookEndpoints
 */
class WC_Stripe_Gateway {

	/**
	 *
	 * @since 3.0.5
	 * @var Stripe mode (test, live)
	 */
	private $mode = null;

	private $messages = array();

	/**
	 *
	 * @since 3.0.8
	 * @var string
	 */
	private $secret_key = null;

	/**
	 *
	 * @var \Stripe\StripeClient
	 */
	private $client = null;

	public function __construct( $mode = null, $secret_key = null ) {
		if ( null != $mode ) {
			$this->mode = $mode;
		}
		if ( null != $secret_key ) {
			$this->secret_key = $secret_key;
		}
		$this->client = new \Stripe\StripeClient( array( 'stripe_version' => '2020-03-02' ) );
	}

	public static function init() {
		\Stripe\Stripe::setAppInfo( 'WordPress woo-stripe-payment', wc_stripe()->version(), 'https://wordpress.org/plugins/woo-stripe-payment/', 'pp_partner_FdPtriN2Q7JLOe' );
	}

	public function __get( $key ) {
		return new WC_Stripe_API_Operation( $this, $this->client, $key );
	}

	/**
	 *
	 * @param string $mode
	 * @param string $secret_key
	 *
	 * @return WC_Stripe_Gateway
	 * @since 3.1.0
	 */
	public static function load( $mode = null, $secret_key = null ) {
		$class = apply_filters( 'wc_stripe_gateway_class', 'WC_Stripe_Gateway' );

		return new $class( $mode, $secret_key );
	}

	/**
	 *
	 * @param string $mode
	 *
	 * @since 3.1.0
	 */
	public function set_mode( $mode ) {
		$this->mode = $mode;
	}

	/**
	 * Create a customer within Stripe.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|string
	 */
	public function create_customer( $args, $mode = '' ) {
		return $this->customers->create( apply_filters( 'wc_stripe_create_customer_args', $args ), $this->get_api_options( $mode ) );

	}

	public function update_customer( $id, $args, $mode = '' ) {
		return $this->customers->update( $id, $args, $this->get_api_options( $mode ) );
	}

	public function charge( $args, $mode = '' ) {
		return $this->charges->create( $args, $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param array $args
	 * @param string $mode
	 *
	 * @return WP_Error|\Stripe\PaymentIntent
	 */
	public function create_payment_intent( $args, $mode = '' ) {
		return $this->paymentIntents->create( $args, $this->get_api_options( $mode ) );
	}

	public function create_setup_intent( $args, $mode = '' ) {
		return $this->setupIntents->create( $args, $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param \Stripe\PaymentIntent $intent
	 * @param array $args
	 * @param string $mode
	 */
	public function update_payment_intent( $id, $args, $mode = '' ) {
		return $this->paymentIntents->update( $id, $args, $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param \Stripe\PaymentIntent $intent
	 * @param array $args
	 * @param string $mode
	 */
	public function confirm_payment_intent( $id, $args = array(), $mode = '' ) {
		return $this->paymentIntents->confirm( $id, $args, $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param string $id
	 * @param string $mode
	 *
	 * @return WP_Error|\Stripe\PaymentIntent
	 */
	public function fetch_payment_intent( $id, $mode = '' ) {
		return $this->paymentIntents->retrieve( $id, array(), $this->get_api_options( $mode ) );
	}

	public function capture_payment_intent( $id, $args = array(), $mode = '' ) {
		return $this->paymentIntents->capture( $id, $args, $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param \Stripe\PaymentIntent|string $id
	 * @param string $mode
	 */
	public function cancel_payment_intent( $id, $mode = '' ) {
		return $this->paymentIntents->cancel( $id, array(), $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param string $id
	 * @param string $mode
	 *
	 * @return WP_Error|\Stripe\SetupIntent
	 */
	public function fetch_setup_intent( $id, $mode = '' ) {
		return $this->setupIntents->retrieve( $id, array(), $this->get_api_options( $mode ) );
	}

	public function capture( $id, $args, $mode = '' ) {
		return $this->charges->capture( $id, $args, $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param string $charge_id
	 * @param string $mode
	 *
	 * @return \Stripe\Charge|WP_Error
	 */
	public function get_charge( $charge_id, $mode = '' ) {
		return $this->charges->retrieve( $charge_id, array(), $this->get_api_options( $mode ) );
	}

	public function refund( $args, $mode = '' ) {
		return $this->refunds->create( $args, $this->get_api_options( $mode ) );
	}

	public function get_payment_method( $id, $mode = '' ) {
		return \Stripe\PaymentMethod::retrieve( $id, $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param \Stripe\PaymentMethod $payment_method
	 * @param array $args
	 * @param string $mode
	 */
	public function attach_payment_method( $id, $args = array(), $mode = '' ) {
		return $this->paymentMethods->attach( $id, $args, $this->get_api_options( $mode ) );
	}

	public function fetch_payment_method( $id, $mode = '' ) {
		return $this->paymentMethods->retrieve( $id, null, $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param \Stripe\PaymentMethod $payment_method
	 * @param string $mode
	 */
	public function delete_payment_method( $id, $mode = '' ) {
		return $this->paymentMethods->detach( $id, array(), $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param string $id
	 * @param string $customer
	 * @param string $mode
	 */
	public function delete_card( $id, $customer, $mode = '' ) {
		return $this->sources->detach( $customer, $id, null, $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param array $args
	 * @param string $mode
	 *
	 * @return WP_Error|\Stripe\PaymentMethod
	 */
	public function create_payment_method( $args, $mode = '' ) {
		return $this->paymentMethods->create( $args, $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param string $id
	 * @param string $mode
	 *
	 * @return WP_Error|\Stripe\Source
	 *
	 */
	public function fetch_payment_source( $id, $mode = '' ) {
		return $this->sources->retrieve( $id, null, $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param string $customer_id
	 * @param string $id
	 * @param string $mode
	 *
	 * @return WP_Error|\Stripe\Source
	 */
	public function create_customer_source( $customer_id, $id, $mode = '' ) {
		return $this->customers->createSource( $customer_id, array( 'source' => $id ), $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param array $args
	 * @param string $mode
	 *
	 * @return WP_Error|\Stripe\Source
	 */
	public function create_source( $args, $mode = '' ) {
		return $this->sources->create( $args, $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param string $source_id
	 * @param array $args
	 * @param string $mode
	 *
	 * @since 3.0.2
	 */
	public function update_source( $source_id, $args, $mode = '' ) {
		return $this->sources->update( $source_id, $args, $this->get_api_options( $mode ) );
	}

	public function fetch_customer( $customer_id, $mode = '' ) {
		return $this->customers->retrieve( $customer_id, null, $this->get_api_options( $mode ) );
	}

	public function fetch_customers( $mode = '' ) {
		return $this->customers->all( array( 'limit' => 1 ), $this->get_api_options( $mode ) );
	}

	public function fetch_payment_methods( $customer_id, $mode = '', $type = 'card' ) {
		return $this->paymentMethods->all(
			array(
				'customer' => $customer_id,
				'type'     => $type,
			),
			$this->get_api_options( $mode )
		);
	}

	public function register_domain( $domain, $mode = '' ) {
		return $this->applePayDomains->create( array( 'domain_name' => $domain ), $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param string $mode
	 *
	 * @since 3.1.6
	 */
	public function fetch_domains( $mode = '' ) {
		return $this->applePayDomains->all( array( 'limit' => 50 ), $this->get_api_options( $mode ) );
	}

	/**
	 *
	 * @param \Stripe\ApplePayDomain $domain
	 * @param string $mode
	 *
	 * @since 3.1.6
	 */
	public function delete_domain( $id, $mode = '' ) {
		return $this->applePayDomains->delete( $id, array(), $this->get_api_options( $mode ) );
	}

	public function webhooks( $mode = '' ) {
		return $this->webhookEndpoints->all( array( 'limit' => 100 ), $this->get_api_options( $mode ) );
	}

	public function create_webhook( $url, $events, $mode = '' ) {
		return $this->webhookEndpoints->create(
			array(
				'url'            => $url,
				'enabled_events' => $events,
			),
			$this->get_api_options( $mode )
		);
	}

	public function update_webhook( $id, $params, $mode = '' ) {
		return $this->webhookEndpoints->update( $id, $params, $this->get_api_options( $mode ) );
	}

	public function fetch_webhook( $id, $mode = '' ) {
		return $this->webhookEndpoints->retrieve( $id, null, $this->get_api_options( $mode ) );
	}

	public function get_api_options( $mode = '' ) {
		if ( empty( $mode ) && $this->mode != null ) {
			$mode = $this->mode;
		}
		$args = array( 'api_key' => $this->secret_key ? $this->secret_key : wc_stripe_get_secret_key( $mode ) );

		return apply_filters( 'wc_stripe_api_options', $args );
	}

	/**
	 *
	 * @param mixed $err
	 */
	private function get_error_message( $err ) {
		if ( is_a( $err, '\Stripe\Exception\ApiErrorException' ) ) {
			$err = $err->getError();
		}
		if ( is_array( $err ) || $err instanceof \Stripe\ErrorObject ) {
			$this->messages = ! $this->messages ? wc_stripe_get_error_messages() : $this->messages;
			if ( isset( $err['decline_code'] ) && isset( $this->messages[ $err['decline_code'] ] ) ) {
				return $this->messages[ $err['decline_code'] ];
			}
			if ( isset( $err['code'] ) && isset( $this->messages[ $err['code'] ] ) ) {
				return $this->messages[ $err['code'] ];
			}
			if ( isset( $err['message'] ) ) {
				return $err['message'];
			}
		}
		if ( is_string( $err ) ) {
			return $err;
		}
	}

	/**
	 *
	 * @param \Stripe\Exception\ApiErrorException $e
	 *
	 * @since 3.1.1
	 * @todo use in future version to replace manual returns of WP_Error in each method
	 */
	public function get_wp_error( $e, $code = 'stripe-error' ) {
		$err = $e->getJsonBody()['error'];

		return new WP_Error( $code, $this->get_error_message( $err ), $err );
	}
}

WC_Stripe_Gateway::init();
