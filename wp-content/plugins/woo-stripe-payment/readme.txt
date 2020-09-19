=== Stripe For WooCommerce ===
Contributors: mr.clayton
Tags: stripe, ach, klarna, credit card, apple pay, google pay, ideal, sepa, sofort
Requires at least: 3.0.1
Tested up to: 5.5
Requires PHP: 5.6
Stable tag: 3.2.2
Copyright: Payment Plugins
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Accept Credit Cards, Google Pay, ApplePay, ACH, P24, Klarna, iDEAL and more all in one plugin for free!

= Official Stripe Partner = 
Payment Plugins is an official partner of Stripe. 

= Boost conversion by offering product and cart page checkout =
Stripe for WooCommerce is made to supercharge your conversion rate by decreasing payment friction for your customer.
Offer Google Pay, Apple Pay, and Stripe's Browser payment methods on product pages, cart pages, and at the top of your checkout page.

= Visit our demo site to see all the payment methods in action = 
[Demo Site](https://demos.paymentplugins.com/wc-stripe/product/pullover/)

To see Apple Pay, visit the site using an iOS device. Google Pay will display for supported browsers like Chrome.

= Features =
- Credit Cards
- Google Pay
- Apple Pay
- ACH Payments
- 3DS 2.0
- Local Payment Methods
- WooCommerce Subscriptions
- WooCommerce Pre-Orders

== Frequently Asked Questions ==
= How do I test this plugin? = 
 You can enable the plugin's test mode, which allows you to simulate transactions.
 
= Does your plugin support WooCommerce Subscriptions? = 
Yes, the plugin supports all functionality related to WooCommerce Subscriptions.

= Where is your documentation? = 
https://docs.paymentplugins.com/wc-stripe/config/#/

= Why isn't the Payment Request button showing on my local machine? = 
If you're site is not loading over https, then Stripe won't render the Payment Request button. Make sure you are using https.

== Screenshots ==
1. Let customers pay directly from product pages
2. Apple pay on the cart page
3. Custom credit card forms
4. Klarna on checkout page
5. Local payment methods like iDEAL and P24
6. Configuration pages
7. Payment options at top of checkout page for easy one click checkout
8. Edit payment gateways on the product page

== Changelog ==
= 3.2.2 =
* Fixed - 403 for logged out user when link-token fetched on checkout page
* Added - Payment method format for GPay. Example: Visa 1111 (Google Pay)
* Added - Filter for product and cart page checkout so 3rd party plugins can add custom fields to checkout process
* Updated - Stripe PHP lib version to 7.52.0
= 3.2.1 =
* Updated - Plaid Link integration to use new Link Token
* Updated - Convert state long name i.e. Texas = TX in case address is not abbreviated in wallet
* Updated - On checkout page, only request phone and email in Apple Pay and GPay if fields are empty
* Fixed - Issue where JS error triggered if cart/checkout page combined using Elementor
* Fixed - Apple Pay and Payment Request wallet requiring shipping address for variable virtual products
= 3.2.0 =
* Fixed - Conflict with Checkout Field Editor for WooCommerce and JS checkout field variable
* Fixed - Mini-cart html
* Fixed - SEPA JS error on checkout page
* Added - WC tested to 4.4.1
* Updated - removed selectWoo as form-handler.js dependency
= 3.1.9 =
* Fixed - WP 5.5 rest permission_callback notice
* Fixed - Conflict with SG Optimizer plugin
* Fixed - Conflict with https://wordpress.org/plugins/woocommerce-gateway-paypal-express-checkout/ button on checkout page
= 3.1.8 =
* Fixed - Do not redirect to order received page if customer has not completed local payment process
* Fixed - Disable Apple Pay button on variation product when no default product is selected
* Added - Mini-cart integration for GPay and Apple Pay.
* Added - Filter wc_stripe_get_source_args added
* Added - Email validation for local payment methods
* Added - WC tested up to: 4.3.2
* Updated - Stripe PHP lib version 7.45.0
= 3.1.7 =
* Fixed - SEPA payment flow
* Added - BECS payment method
* Updated - Stripe php lib version 7.40.0
* Updated - AliPay display logic
= 3.1.6 = 
* Updated - WC tested to 4.3.0
* Updated - Bumped PHP min version to 5.6
* Updated - Stripe php lib version 7.39.0
* Updated - Apple domain registration check for existing domain
* Fixed - Notice on cart page when payment request button active and cart emptied
* Fixed - Google Pay fee line item in wallet
* Added - New filters for API requests
= 3.1.5 = 
* Fixed - Capture type on product page checkout
* Fixed - WP 5.4.2 deprecation message for namespaces
* Fixed - PHP 7.4 warning message
* Updated - Error message API uses $.scroll_to_notices when available
* Updated - Apple Pay and Payment Request require phone and email based on required billing fields
* Updated - Webkit autofill text color
* Added - Non numerical check to wc_stripe_add_number_precision function
= 3.1.4 = 
* Updated - WC 4.2.0 support
* Added - Validation for local payment methods to ensure payment option cannot be empty
* Added - Account ID to Stripe JS initialization
* Added - Local payment method support for manual subscriptions
* Fixed - Exception that occurs after successful payment of a renewal order retry. 
= 3.1.3 = 
* Added - WC 4.1.1 support
* Added - Klarna payment categories option
* Added - Order ID filter added
* Added - Local payment button filter so text on buttons can be changed
* Update - Webhook controller returns 401 for failed signature
= 3.1.2 = 
* Added - Merchants can now control which payment buttons appear for each product and their positioning
* Added - VAT tax display for Apple Pay, GPay, Payment Request
* Added - Optional Stripe email receipt
* Updated - Stripe API version to 2020-03-02
* Fixed - iDEAL not redirecting on order pay page.
= 3.1.1 = 
* Fixed - Error when changing WCS payment method to new ACH payment method
* Fixed - Error when payment_intent status 'success' and order cancelled status applied
* Added - Recipient email for payment_intent
* Added - Translations for credit card decline errors
* Added - Option to force 3D secure for all transactions
* Added - Option to show generic credit card decline error
* Added - SEPA mandate message on checkout page
* Updated - Google Pay documentation
= 3.1.0 = 
* Added - FPX payment method
* Added - Alipay payment method
* Updated - Stripe connect integration
* Updated - WeChat support for other countries besides CN
* Updated - CSS so prevent theme overrides
* Fixed - WeChat QR code
= 3.0.9 = 
* Added - Payment methods with payment sheets like Apple Pay now show order items on order pay page instead of just total.
* Fixed - Error if 100% off coupon is used on checkout page.
= 3.0.8 = 
* Updated - billing phone and email check added for card payment
* Updated - template checkout/payment-method.php name changed to checkout/stripe-payment-method.php
* Updated - cart checkout button styling
* Added - Connection test in admin API settings
* Misc - WC 3.9.1
= 3.0.7 = 
* Added - WPML support for gateway titles and descriptions
* Added - ACH fee option
* Added - Webhook registration option in Admin
* Updated - Cart one click checkout buttons
* Updated - WC 3.9
= 3.0.6 = 
* Added - ACH subscription support
* Updated - Top of checkout styling
* Updated =Positioning of cart buttons. They are now below cart checkout button
= 3.0.5 =
* Added - ACH payment support
* Added - New credit card form
* Fixed - Klarna error if item totals don't equal order total.
* Updated - API version to 2019-12-03
* Updated - Local payment logic.
= 3.0.4 =
* Added - Bootstrap form added
* Updated - WC 3.8.1
* Fixed - Check for customer object in Admin pages for local payment methods
= 3.0.3 = 
* Fixed - Check added to wc_stripe_order_status_completed function to ensure capture charge is only called when Stripe is the payment gateway for the order.
* Updated - Stripe API version to 2019-11-05
= 3.0.2 = 
* Added - Klarna payments now supported
* Added - Bancontact
* Updated - Local payments webhook
= 3.0.1 = 
* Updated - Google Pay paymentDataCallbacks in JavaScript
* Updated - Text domain to match plugin slug
* Added - Dynamic price option for Google Pay
* Added - Pre-orders support
= 3.0.0 = 
* First commit

== Upgrade Notice ==
= 3.0.0 = 