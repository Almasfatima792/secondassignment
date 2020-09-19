(function ($, wc_stripe) {

    /**
     * @constructor
     */
    function ApplePay() {
        wc_stripe.BaseGateway.call(this, wc_stripe_applepay_checkout_params);
    }

    /**
     * [prototype description]
     * @type {[type]}
     */
    ApplePay.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.CheckoutGateway.prototype, wc_stripe.ApplePay.prototype);

    ApplePay.prototype.initialize = function () {
        wc_stripe.CheckoutGateway.call(this);
        wc_stripe.ApplePay.prototype.initialize.call(this);
    }

    ApplePay.prototype.canMakePayment = function () {
        wc_stripe.ApplePay.prototype.canMakePayment.apply(this, arguments).then(function () {
            if (this.banner_enabled()) {
                var $button = $(this.params.button);
                $button.addClass('banner-checkout');
                $(this.banner_container).append($button);
                $(this.banner_container).show().parent().parent().addClass('active');
            }
        }.bind(this))
    }

    /**
     * @return {[type]}
     */
    ApplePay.prototype.append_button = function () {
        $('#place_order').parent().append(this.$button);
        this.trigger_payment_method_selected();
    }

    ApplePay.prototype.updated_checkout = function () {
        if (this.can_pay) {
            this.create_button();
            $(this.container).show();
        }
    }

    /**
     * [Wrapper for main start function]
     * @param  {[@event]} e [description]
     */
    ApplePay.prototype.start = function (e) {
        if ($(e.target).is('.banner-checkout')) {
            this.set_payment_method(this.gateway_id);
            this.set_use_new_option(true);
        }
        wc_stripe.ApplePay.prototype.start.apply(this, arguments);
    }

    ApplePay.prototype.on_token_received = function () {
        wc_stripe.CheckoutGateway.prototype.on_token_received.apply(this, arguments);
        this.maybe_set_ship_to_different();
        this.fields.toFormFields({update_shipping_method: false});
        if (this.checkout_fields_valid()) {
            this.get_form().submit();
        }
    }

    new ApplePay();

}(jQuery, window.wc_stripe))