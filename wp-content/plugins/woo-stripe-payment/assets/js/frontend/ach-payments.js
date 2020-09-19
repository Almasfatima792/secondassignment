(function ($, wc_stripe) {

    function ACH() {
        wc_stripe.BaseGateway.call(this, wc_stripe_ach_params);
        wc_stripe.CheckoutGateway.call(this);

        $(document.body).on('payment_method_selected', this.payment_method_selected.bind(this));
    }

    ACH.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.CheckoutGateway.prototype);

    ACH.prototype.initialize = function () {
        $(document.body).on('click', '#place_order', this.place_order.bind(this));
        this.init_plaid();
    }

    ACH.prototype.init_plaid = function () {
        this.fetch_link_token().then(function (link_token) {
            this.linkHandler = Plaid.create({
                clientName: this.params.client_name,
                env: this.params.env,
                product: ['auth'],
                token: link_token,
                selectAccount: true,
                countryCodes: ['US'],
                onSuccess: function (public_token, metadata) {
                    // serialize metadata and submit form
                    this.payment_token_received = true;
                    this.set_nonce(public_token);
                    this.set_metadata(metadata);
                    this.fields.toFormFields();
                    $('#place_order').text($('#place_order').data('value'));
                    this.get_form().submit();
                }.bind(this),
                onExit: function (err, metadata) {
                    if (err != null) {
                        this.submit_error(err.error_message);
                    }
                }.bind(this)
            });
        }.bind(this));
    }

    ACH.prototype.place_order = function (e) {
        if (this.is_gateway_selected()) {
            if (!this.payment_token_received && !this.is_saved_method_selected()) {
                e.preventDefault();
                this.linkHandler.open();
            }
        }
    }

    ACH.prototype.hide_place_order = function () {

    }

    ACH.prototype.show_payment_button = function () {
        wc_stripe.CheckoutGateway.prototype.show_place_order.apply(this, arguments);
    }

    ACH.prototype.set_metadata = function (metadata) {
        this.fields.set(this.gateway_id + '_metadata', JSON.stringify(metadata));
    }

    ACH.prototype.fees_enabled = function () {
        return this.params.fees_enabled == "1";
    }

    ACH.prototype.payment_method_selected = function () {
        if (this.fees_enabled()) {
            $(document.body).trigger('update_checkout');
        }
    }

    ACH.prototype.fetch_link_token = function () {
        return new Promise(function (resolve) {
            $.post({
                url: this.params.routes.link_token,
                dataType: 'json',
                data: {wp_rest_nonce: this.params.rest_nonce}
            }).done(function (response) {
                resolve(response.token);
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                $(this.container).hide();
                console.log(errorThrown);
            }.bind(this));
        }.bind(this));
    }

    new ACH();

}(jQuery, window.wc_stripe))