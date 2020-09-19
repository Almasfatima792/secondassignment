(function ($, wc_stripe) {

    /**
     * @construct
     */
    function GPay() {
        this.can_pay = false;
        wc_stripe.BaseGateway.call(this, wc_stripe_googlepay_checkout_params);
        wc_stripe.CheckoutGateway.call(this);
    }

    /**
     * [prototype description]
     * @type {[type]}
     */
    GPay.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.CheckoutGateway.prototype, wc_stripe.GooglePay.prototype);

    /**
     * @return {[type]}
     */
    GPay.prototype.initialize = function () {
        // create payments client
        this.createPaymentsClient();
        this.isReadyToPay().then(function () {
            $(this.container).show();
            if (this.banner_enabled()) {
                var $button = $(this.paymentsClient.createButton({
                    onClick: this.banner_checkout.bind(this),
                    buttonColor: this.params.button_color,
                    buttonType: this.params.button_style
                }));
                $(this.banner_container).show().parent().parent().addClass('active');
                $(this.banner_container).append($button);
            }
        }.bind(this))
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.create_button = function () {
        wc_stripe.GooglePay.prototype.create_button.apply(this, arguments);
        $("#place_order").parent().append(this.$button);
        this.trigger_payment_method_selected();
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.updated_checkout = function () {
        if (this.can_pay) {
            $(this.container).show();
            this.create_button();
        }
    }

    /**
     * [banner_checkout description]
     * @return {[type]} [description]
     */
    GPay.prototype.banner_checkout = function () {
        this.set_payment_method(this.gateway_id);
        this.set_use_new_option(true);
        wc_stripe.GooglePay.prototype.start.apply(this, arguments);
    }

    GPay.prototype.on_token_received = function () {
        wc_stripe.CheckoutGateway.prototype.on_token_received.apply(this, arguments);
        this.maybe_set_ship_to_different();
        this.fields.toFormFields({update_shipping_method: false});
        if (this.checkout_fields_valid()) {
            this.get_form().submit();
        }
    }

    GPay.prototype.payment_data_updated = function (response) {
        this.populate_billing_fields(response.address);
        this.fields.toFormFields({update_shipping_method: false});
    }

    new GPay();

}(jQuery, window.wc_stripe))