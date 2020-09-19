(function ($, wc_stripe) {

    /**
     *
     * @param container
     * @constructor
     */
    function MiniCart(params) {
        this.message_container = '.widget_shopping_cart_content';
        wc_stripe.BaseGateway.call(this, params, container);
    }

    MiniCart.prototype.on_token_received = function () {
        this.block();
        this.block_cart();
        wc_stripe.BaseGateway.prototype.on_token_received.apply(this, arguments);
    }

    MiniCart.prototype.block_cart = function () {
        $(this.container).closest('.widget_shopping_cart_content').find('.wc-stripe-overlay').addClass('active');
    }

    MiniCart.prototype.unblock_cart = function () {
        $(this.container).closest('.widget_shopping_cart_content').find('.wc-stripe-overlay').addClass('active');
    }

    /*------------------------- GPay -------------------------*/
    function GPay(params) {
        MiniCart.apply(this, arguments);
    }

    GPay.prototype = Object.assign({}, wc_stripe.BaseGateway.prototype, MiniCart.prototype, wc_stripe.GooglePay.prototype);

    GPay.prototype.initialize = function () {
        this.createPaymentsClient();
        this.isReadyToPay().then(function () {
            this.$button.find('.gpay-button').addClass('button');
            this.append_button();
        }.bind(this));
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.create_button = function () {
        wc_stripe.GooglePay.prototype.create_button.apply(this, arguments);
        this.$button = this.$button.find('.gpay-button');
        this.append_button();
    }

    GPay.prototype.append_button = function () {
        $(this.container).find('.wc-stripe-gpay-mini-cart').empty();
        $(this.container).find('.wc-stripe-gpay-mini-cart').append(this.$button).show();
    }

    /*------------------------- ApplePay -------------------------*/
    function ApplePay(params) {
        MiniCart.apply(this, arguments);
    }

    ApplePay.prototype = Object.assign({}, wc_stripe.BaseGateway.prototype, MiniCart.prototype, wc_stripe.ApplePay.prototype);


    ApplePay.prototype.initialize = function () {
        wc_stripe.ApplePay.prototype.initialize.apply(this, arguments);
    }

    ApplePay.prototype.append_button = function () {
        $(this.container).find('.wc-stripe-applepay-mini-cart').empty();
        $(this.container).find('.wc-stripe-applepay-mini-cart').append(this.$button).show();
    }

    /*------------------------- PaymentRequest -------------------------*/
    function PaymentRequest(params) {
        MiniCart.apply(this, arguments);
    }

    PaymentRequest.prototype = Object.assign({}, wc_stripe.BaseGateway.prototype, MiniCart.prototype, wc_stripe.PaymentRequest.prototype);

    PaymentRequest.prototype.initialize = function () {
        wc_stripe.PaymentRequest.prototype.initialize.apply(this, arguments);
    }

    PaymentRequest.prototype.create_button = function () {
        this.append_button();
    }

    PaymentRequest.prototype.append_button = function () {
        $(this.container).find('.wc-stripe-payment-request-mini-cart').empty().show();
        this.paymentRequestButton.mount($(this.container).find('.wc-stripe-payment-request-mini-cart').first()[0]);
    }

    /*-------------------------------------------------------------------------*/

    var gateways = [], container = null;

    if (typeof wc_stripe_googlepay_mini_cart_params !== 'undefined') {
        gateways.push([GPay, wc_stripe_googlepay_mini_cart_params]);
    }
    if (typeof wc_stripe_applepay_mini_cart_params !== 'undefined') {
        gateways.push([ApplePay, wc_stripe_applepay_mini_cart_params]);
    }
    if (typeof wc_stripe_payment_request_mini_cart_params !== 'undefined') {
        gateways.push([PaymentRequest, wc_stripe_payment_request_mini_cart_params]);
    }

    function load_mini_cart() {
        $('.widget_shopping_cart_content').not(':empty').each(function (idx, el) {
            if ($(el).find('.wc_stripe_mini_cart_payment_methods').length) {
                var $parent = $(el).parent();
                if ($parent.length) {
                    var class_name = 'wc-stripe-mini-cart-idx-' + idx;
                    $parent.addClass(class_name);
                    $parent.find('.widget_shopping_cart_content').prepend('<div class="wc-stripe-overlay"></div>');
                    container = '.' + class_name + ' .widget_shopping_cart_content p.buttons';
                    gateways.forEach(function (gateway) {
                        new gateway[0](gateway[1]);
                    })
                }
            }
        });
    }

    $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', load_mini_cart);

}(jQuery, window.wc_stripe));