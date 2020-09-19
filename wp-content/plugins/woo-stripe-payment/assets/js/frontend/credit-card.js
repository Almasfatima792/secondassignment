(function ($, wc_stripe) {

    /**
     * Credit card class.
     *
     * @constructor
     */
    function CC() {
        wc_stripe.BaseGateway.call(this, wc_stripe_credit_card_params);
        wc_stripe.CheckoutGateway.call(this);
        window.addEventListener('hashchange', this.hashChange.bind(this));
        wc_stripe.credit_card = this;
    }

    const elementClasses = {
        focus: 'focused',
        empty: 'empty',
        invalid: 'invalid'
    }

    CC.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.CheckoutGateway.prototype);

    /**
     *
     */
    CC.prototype.initialize = function () {
        $(document.body).on('click', '#place_order', this.place_order.bind(this));
        this.setup_card();
        this.create_card_element();

        if (this.can_create_setup_intent()) {
            this.create_setup_intent().then(function (response) {
                if (response.code) {
                    this.submit_error(response.message);
                } else {
                    this.client_secret = response.intent.client_secret;
                }
            }.bind(this))
        }
    }

    /**
     *
     */
    CC.prototype.setup_card = function () {
        if (this.is_custom_form()) {
            // create individual card sections
            this.cardNumber = this.elements.create('cardNumber', {
                style: this.params.style,
                classes: elementClasses
            });
            this.cardNumber.on('change', this.card_number_change.bind(this));
            this.cardExpiry = this.elements.create('cardExpiry', {
                style: this.params.style,
                classes: elementClasses
            });
            this.cardCvc = this.elements.create('cardCvc', {
                style: this.params.style,
                classes: elementClasses
            });
            if (this.fields.required('billing_postcode') && '' !== this.fields.get('billing_postcode')) {
                if ($('#stripe-postal-code').length > 0) {
                    $('#stripe-postal-code').val(this.fields.get('billing_postcode'));
                    this.validate_postal_field();
                }
            }
            $(document.body).on('change', '#billing_postcode', function (e) {
                var val = $('#billing_postcode').val();
                $('#stripe-postal-code').val(val).trigger('keyup');
            }.bind(this));
        } else {
            this.card = this.elements.create('card', {
                value: {
                    postalCode: this.fields.get('billing_postcode', '')
                },
                hidePostalCode: this.fields.required('billing_postcode'),
                style: this.params.style,
                iconStyle: 'default'
            });
            $(document.body).on('change', '#billing_postcode', function (e) {
                if (this.card) {
                    this.card.update({value: $('#billing_postcode').val()});
                }
            }.bind(this));
        }
        // setup a timeout so CC element is always rendered.
        setInterval(this.create_card_element.bind(this), 2000);
    }

    CC.prototype.validate_postal_field = function () {
        if ($('#billing_postcode').length && $('#stripe-postal-code').length) {
            // validate postal code
            if (this.params.postal_regex[this.fields.get('billing_country')]) {
                var regex = this.params.postal_regex[this.fields.get('billing_country')],
                    postal = $('#stripe-postal-code').val(),
                    regExp = new RegExp(regex, "i");
                if (postal !== '') {
                    if (regExp.exec(postal) !== null) {
                        $('#stripe-postal-code').addClass('StripeElement--complete').removeClass('invalid');
                    } else {
                        $('#stripe-postal-code').removeClass('StripeElement--complete').addClass('invalid');
                    }
                } else {
                    $('#stripe-postal-code').removeClass('StripeElement--complete').removeClass('invalid');
                }
            } else {
                if ($('#stripe-postal-code').val() != 0) {
                    $('#stripe-postal-code').addClass('StripeElement--complete');
                } else {
                    $('#stripe-postal-code').removeClass('StripeElement--complete');
                }
            }
        } else if ($('#stripe-postal-code').length) {
            if ($('#stripe-postal-code').val() != 0) {
                $('#stripe-postal-code').addClass('StripeElement--complete');
            }
        }
    }

    /**
     *
     */
    CC.prototype.create_card_element = function () {
        if (this.is_custom_form()) {
            if ($('#wc-stripe-cc-custom-form').length && $('#wc-stripe-cc-custom-form').find('iframe').length == 0) {
                if ($('#stripe-card-number').length) {
                    this.cardNumber.mount('#stripe-card-number');
                    $('#stripe-card-number').prepend(this.params.html.card_brand);
                }
                if ($('#stripe-exp').length) {
                    this.cardExpiry.mount('#stripe-exp');
                }
                if ($('#stripe-cvv').length) {
                    this.cardCvc.mount('#stripe-cvv');
                }
                if ($('#stripe-postal-code').length) {
                    $('#stripe-postal-code, .postalCode').on('focus', function (e) {
                        $('#stripe-postal-code').addClass('focused');
                    }.bind(this));
                    $('#stripe-postal-code, .postalCode').on('blur', function (e) {
                        $('#stripe-postal-code').removeClass('focused').trigger('keyup');
                    }.bind(this));
                    $('#stripe-postal-code').on('keyup', function (e) {
                        if ($('#stripe-postal-code').val() == 0) {
                            $('#stripe-postal-code').addClass('empty');
                        } else {
                            $('#stripe-postal-code').removeClass('empty');
                        }
                    }.bind(this))
                    $('#stripe-postal-code').on('change', this.validate_postal_field.bind(this));
                    $('#stripe-postal-code').trigger('change');
                }
            }
        } else {
            if ($('#wc-stripe-card-element').length) {
                if ($('#wc-stripe-card-element').find('iframe').length == 0) {
                    this.card.mount('#wc-stripe-card-element');
                    this.card.update({
                        value: {
                            postalCode: this.fields.get('billing_postcode', '')
                        },
                        hidePostalCode: this.fields.required('billing_postcode')
                    });
                }
            }
        }
        if ($(this.container).outerWidth(true) < 450) {
            $(this.container).addClass('stripe-small-container');
        } else {
            $(this.container).removeClass('stripe-small-container');
        }
    }

    /**
     *
     */
    CC.prototype.hashChange = function (e) {
        if (this.is_gateway_selected()) {
            var match = e.newURL.match(/response=(.*)/);
            if (match) {
                var obj = JSON.parse(window.atob(match[1]));
                this.stripe.handleCardAction(obj.client_secret).then(function (result) {
                    if (result.error) {
                        this.payment_token_received = false;
                        this.submit_error(result.error);
                        this.sync_payment_intent(obj.order_id, obj.client_secret).catch(function (response) {
                            this.submit_error(response.message);
                        }.bind(this));
                        return;
                    }
                    var $form = $(this.token_selector).closest('form');
                    $form.unblock().removeClass('processing');
                    $form.submit();
                }.bind(this)).catch(function (error) {
                    this.submit_error(error.message);
                }.bind(this))
                return false;
            }
            return true;
        }
        return true;
    }

    /**
     *
     */
    CC.prototype.place_order = function (e) {
        if (this.is_gateway_selected()) {
            if (this.can_create_setup_intent() && !this.is_saved_method_selected()) {
                e.preventDefault();
                this.stripe.confirmCardSetup(this.client_secret, {
                    payment_method: {
                        card: this.is_custom_form() ? this.cardNumber : this.card,
                        billing_details: $.extend({}, this.is_custom_form() ? {address: {postal_code: $('#stripe-postal-code').val()}} : {})
                    }
                }).then(function (result) {
                    if (result.error) {
                        this.submit_error(result.error);
                        return;
                    }
                    this.on_setup_intent_received(result.setupIntent.payment_method);
                }.bind(this))
            } else {
                if (!this.payment_token_received && !this.is_saved_method_selected()) {
                    e.preventDefault();
                    this.stripe.createPaymentMethod({
                        type: 'card',
                        card: this.is_custom_form() ? this.cardNumber : this.card,
                        billing_details: this.get_billing_details()
                    }).then(function (result) {
                        if (result.error) {
                            this.submit_error(result.error);
                            return;
                        }
                        this.on_token_received(result.paymentMethod);
                    }.bind(this))
                }
            }
        }
    }

    /**
     * @since 3.1.8 - added to ensure 3rd party plugin's can't stop the tokenization process
     *                if e.preventDefault is called on place_order
     * @returns {boolean}
     */
    CC.prototype.checkout_place_order = function () {
        if (!this.is_saved_method_selected() && !this.payment_token_received) {
            this.place_order.apply(this, arguments);
            return false;
        }
        return wc_stripe.CheckoutGateway.prototype.checkout_place_order.apply(this, arguments);
    }

    /**
     * [sync_payment_intent description]
     * @param  {[type]} order_id      [description]
     * @param  {[type]} client_secret [description]
     * @return {[type]}               [description]
     */
    CC.prototype.sync_payment_intent = function (order_id, client_secret) {
        return new Promise(function (resolve, reject) {
            // call intent api
            $.when($.ajax({
                method: 'POST',
                dataType: 'json',
                url: this.params.routes.sync_intent,
                data: {order_id: order_id, client_secret: client_secret},
                beforeSend: this.ajax_before_send.bind(this)
            })).done(function (response) {
                if (response.code) {
                    reject(response);
                } else {
                    resolve(response);
                }
            }).fail(function (xhr, textStatus, errorThrown) {
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this))
    }

    /**
     *
     */
    CC.prototype.create_setup_intent = function () {
        return new Promise(function (resolve, reject) {
            // call intent api
            $.when($.ajax({
                method: 'GET',
                dataType: 'json',
                url: this.params.routes.setup_intent,
                beforeSend: this.ajax_before_send.bind(this)
            })).done(function (response) {
                if (response.code) {
                    reject(response);
                } else {
                    resolve(response);
                }
            }).fail(function (xhr, textStatus, errorThrown) {
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this))
    }

    /**
     *
     */
    CC.prototype.on_token_received = function (paymentMethod) {
        this.payment_token_received = true;
        this.set_nonce(paymentMethod.id);
        this.get_form().submit();
    }

    /**
     *
     */
    CC.prototype.on_setup_intent_received = function (payment_method) {
        this.payment_token_received = true;
        this.set_nonce(payment_method);
        this.get_form().submit();
    }

    /**
     *
     */
    CC.prototype.updated_checkout = function () {
        this.create_card_element();
    }

    /**
     *
     */
    CC.prototype.update_checkout = function () {
        this.clear_card_elements();
    }

    CC.prototype.show_payment_button = function () {
        wc_stripe.CheckoutGateway.prototype.show_place_order.apply(this, arguments);
    }

    /**
     * [Leave empty so that the place order button is not hidden]
     * @return {[type]} [description]
     */
    CC.prototype.hide_place_order = function () {

    }

    /**
     * Returns true if a custom form is being used.
     * @return {Boolean} [description]
     */
    CC.prototype.is_custom_form = function () {
        return this.params.custom_form === "1";
    }

    CC.prototype.get_element_options = function () {
        return this.params.elementOptions;
    }

    /**
     * [get_postal_code description]
     * @return {[type]} [description]
     */
    CC.prototype.get_postal_code = function () {
        if (this.is_custom_form()) {
            if ($('#stripe-postal-code').length > 0) {
                return $('#stripe-postal-code').val();
            }
            return this.fields.get('billing_postcode', null);
        }
        return this.fields.get('billing_postcode', null);
    }

    CC.prototype.card_number_change = function (data) {
        if (data.brand === "unkown") {
            $('#wc-stripe-card').removeClass('active');
        } else {
            $('#wc-stripe-card').addClass('active');
        }
        $('#wc-stripe-card').attr('src', this.params.cards[data.brand]);
    }

    CC.prototype.clear_card_elements = function () {
        var elements = ['cardNumber', 'cardExpiry', 'cardCvc'];
        for (var i = 0; i < elements.length; i++) {
            if (this[elements[i]]) {
                this[elements[i]].clear();
            }
        }
    }

    CC.prototype.checkout_error = function () {
        if (this.is_gateway_selected()) {
            this.payment_token_received = false;
        }
        wc_stripe.CheckoutGateway.prototype.checkout_error.call(this);
    }

    CC.prototype.get_billing_details = function () {
        var details = {
            name: this.get_customer_name('billing'),
            address: {
                city: this.fields.get('billing_city', null),
                country: this.fields.get('billing_country', null),
                line1: this.fields.get('billing_address_1', null),
                line2: this.fields.get('billing_address_2', null),
                postal_code: this.get_postal_code(),
                state: this.fields.get('billing_state', null)
            }
        }
        if (!details.name || details.name === ' ') {
            delete details.name;
        }
        if (this.fields.get('billing_email') != '') {
            details.email = this.fields.get('billing_email');
        }
        if (this.fields.get('billing_phone') != '') {
            details.phone = this.fields.get('billing_phone');
        }
        return details;
    }

    CC.prototype.can_create_setup_intent = function () {
        return this.is_add_payment_method_page() || this.is_change_payment_method();
    }

    new CC();

}(jQuery, window.wc_stripe))