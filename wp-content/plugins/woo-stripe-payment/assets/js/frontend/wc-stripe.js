(function (window, $) {
    window.wc_stripe = {};
    var stripe = null;

    if (typeof wc_stripe_checkout_fields === 'undefined') {
        window.wc_stripe_checkout_fields = [];
    }

    wc_stripe.BaseGateway = function (params, container) {
        this.params = params;
        this.gateway_id = this.params.gateway_id;
        this.container = typeof container === 'undefined' ? 'li.payment_method_'.concat(this.gateway_id) : container;

        if (!$(this.container).length) {
            this.container = '.payment_method_'.concat(this.gateway_id);
        }

        this.token_selector = this.params.token_selector;
        this.saved_method_selector = this.params.saved_method_selector;
        this.payment_token_received = false;
        this.stripe = stripe;
        this.elements = stripe.elements($.extend({}, {
            locale: 'auto'
        }, this.get_element_options()));
        this.fields = checkoutFields;
        this.initialize();
    };

    wc_stripe.BaseGateway.prototype.get_page = function () {
        return wc_stripe_params_v3.page;
    };

    wc_stripe.BaseGateway.prototype.set_nonce = function (value) {
        this.fields.set(this.gateway_id + '_token_key', value);
        $(this.token_selector).val(value);
    };
    /**
     * [get_element_options description]
     * @return {[type]} [description]
     */


    wc_stripe.BaseGateway.prototype.get_element_options = function () {
        return {};
    };

    wc_stripe.BaseGateway.prototype.initialize = function () {
    };
    /**
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.create_button = function () {
    };
    /**
     * @returns {Boolean}
     */


    wc_stripe.BaseGateway.prototype.is_gateway_selected = function () {
        return $('[name="payment_method"]:checked').val() === this.gateway_id;
    };
    /**
     * @returns {Boolean}
     */


    wc_stripe.BaseGateway.prototype.is_saved_method_selected = function () {
        return this.is_gateway_selected() && $('[name="' + this.gateway_id + '_payment_type_key"]:checked').val() === 'saved';
    };
    /**
     * @return {Boolean}
     */


    wc_stripe.BaseGateway.prototype.has_checkout_error = function () {
        return $('#wc_stripe_checkout_error').length > 0 && this.is_gateway_selected();
    };
    /**
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.submit_error = function (message) {
        message = this.get_error_message(message);

        if (message.indexOf('</ul>') == -1) {
            message = '<div class="woocommerce-error">' + message + '</div>';
        }

        this.submit_message(message);
    };

    wc_stripe.BaseGateway.prototype.submit_error_code = function (code) {
        console.log(code);
    };

    wc_stripe.BaseGateway.prototype.get_error_message = function (message) {
        if (typeof message == 'object' && message.code) {
            if (wc_stripe_messages[message.code]) {
                message = wc_stripe_messages[message.code];
            } else {
                message = message.message;
            }
        }

        return message;
    };
    /**
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.submit_message = function (message) {
        $('.woocommerce-error, .woocommerce-message, .woocommerce-info').remove();
        var $container = $(this.message_container);

        if ($container.closest('form').length) {
            $container = $container.closest('form');
        }

        $container.prepend(message);
        $container.removeClass('processing').unblock();
        $container.find('.input-text, select, input:checkbox').blur();

        if ($.scroll_to_notices) {
            $.scroll_to_notices($container);
        } else {
            $('html, body').animate({
                scrollTop: $container.offset().top - 100
            }, 1000);
        }
    };

    wc_stripe.BaseGateway.prototype.get_first_name = function (prefix) {
        return $('#' + prefix + '_first_name').val();
    };

    wc_stripe.BaseGateway.prototype.get_last_name = function (prefix) {
        return $('#' + prefix + '_last_name').val();
    };
    /**
     * Return true if the source should be saved.
     *
     * @returns {Boolean}
     */


    wc_stripe.BaseGateway.prototype.should_save_method = function () {
        return $('#' + this.gateway_id + '_save_source_key').is(':checked');
    };

    wc_stripe.BaseGateway.prototype.is_add_payment_method_page = function () {
        return this.get_page() === 'add_payment_method' || $(document.body).hasClass('woocommerce-add-payment-method');
    };

    wc_stripe.BaseGateway.prototype.is_change_payment_method = function () {
        return this.get_page() === 'change_payment_method';
    };

    wc_stripe.BaseGateway.prototype.get_selected_payment_method = function () {
        return $(this.saved_method_selector).val();
    };

    wc_stripe.BaseGateway.prototype.needs_shipping = function () {
        return this.get_gateway_data().needs_shipping;
    };

    wc_stripe.BaseGateway.prototype.get_currency = function () {
        return this.get_gateway_data().currency;
    };

    wc_stripe.BaseGateway.prototype.get_gateway_data = function () {
        return $(this.container).find(".woocommerce_".concat(this.gateway_id, "_gateway_data")).data('gateway');
    };

    wc_stripe.BaseGateway.prototype.set_gateway_data = function (data) {
        $(this.container).find(".woocommerce_".concat(this.gateway_id, "_gateway_data")).data('gateway', data);
    };
    /**
     * [get_customer_name description]
     * @return String
     */


    wc_stripe.BaseGateway.prototype.get_customer_name = function (prefix) {
        return this.fields.get(prefix + '_first_name') + ' ' + this.fields.get(prefix + '_last_name');
    };
    /**
     * [get_customer_email description]
     * @return {String} [description]
     */


    wc_stripe.BaseGateway.prototype.get_customer_email = function () {
        return this.fields.get('billing_email');
    };
    /**
     * Returns a string representation of an address.
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.get_address_field_hash = function (prefix) {
        var params = ['_first_name', '_last_name', '_address_1', '_address_2', '_postcode', '_city', '_state', '_country'];
        var hash = '';

        for (var i = 0; i < params.length; i++) {
            hash += this.fields.get(prefix + params[i]) + '_';
        }

        return hash;
    };
    /**
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.block = function () {
        if ($().block) {
            $.blockUI({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }
        ;
    }
    /**
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.unblock = function () {
        if ($().block) {
            $.unblockUI();
        }
    };
    /**
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.get_form = function () {
        return $(this.token_selector).closest('form');
    };
    /**
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.get_total_price = function () {
        return this.get_gateway_data().total;
    };

    wc_stripe.BaseGateway.prototype.get_total_price_cents = function () {
        return this.get_gateway_data().total_cents;
    };
    /**
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.set_total_price = function (total) {
        var data = this.get_gateway_data();
        data.total = total;
        this.set_gateway_data(data);
    };
    /**
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.set_total_price_cents = function (total) {
        var data = this.get_gateway_data();
        data.total_cents = total;
        this.set_gateway_data(data);
    };
    /**
     * [set_payment_method description]
     * @param {[type]} payment_method [description]
     */


    wc_stripe.BaseGateway.prototype.set_payment_method = function (payment_method) {
        $('[name="payment_method"][value="' + payment_method + '"]').prop("checked", true).trigger('click');
    };
    /**
     * [set_shipping_methods description]
     */


    wc_stripe.BaseGateway.prototype.set_selected_shipping_methods = function (shipping_methods) {
        this.fields.set('shipping_method', shipping_methods);

        if (shipping_methods && $('[name^="shipping_method"]').length) {
            for (var i in shipping_methods) {
                var method = shipping_methods[i];
                $('[name="shipping_method[' + i + ']"][value="' + method + '"]').prop("checked", true).trigger('change');
            }
        }
    };
    /**
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.on_token_received = function (paymentMethod) {
        this.payment_token_received = true;
        this.set_nonce(paymentMethod.id);
        this.process_checkout();
    };

    wc_stripe.BaseGateway.prototype.createPaymentRequest = function () {
        try {
            this.paymentRequest = stripe.paymentRequest(this.get_payment_request_options());
        } catch (err) {
            this.submit_error(err.message);
            return;
        }

        if (this.needs_shipping()) {
            this.paymentRequest.on('shippingaddresschange', this.update_shipping_address.bind(this));
            this.paymentRequest.on('shippingoptionchange', this.update_shipping_method.bind(this));
        }

        this.paymentRequest.on('paymentmethod', this.on_payment_method_received.bind(this));
    };
    /**
     * @return {[Object]}
     */


    wc_stripe.BaseGateway.prototype.get_payment_request_options = function () {
        var options = {
            country: this.params.country_code,
            currency: this.get_currency().toLowerCase(),
            total: {
                amount: this.get_total_price_cents(),
                label: this.params.total_label,
                pending: true
            },
            requestPayerName: true,
            requestPayerEmail: this.fields.requestFieldInWallet('billing_email'),
            requestPayerPhone: this.fields.requestFieldInWallet('billing_phone'),
            requestShipping: this.needs_shipping()
        };
        var displayItems = this.get_display_items(),
            shippingOptions = this.get_shipping_options();

        if (displayItems) {
            options.displayItems = displayItems;
        }

        if (this.needs_shipping() && shippingOptions) {
            options.shippingOptions = shippingOptions;
        }

        return options;
    };
    /**
     * @return {[Object]}
     */


    wc_stripe.BaseGateway.prototype.get_payment_request_update = function (data) {
        var options = {
            currency: this.get_currency().toLowerCase(),
            total: {
                amount: parseInt(this.get_total_price_cents()),
                label: this.params.total_label,
                pending: true
            }
        };
        var displayItems = this.get_display_items(),
            shippingOptions = this.get_shipping_options();

        if (displayItems) {
            options.displayItems = displayItems;
        }

        if (this.needs_shipping() && shippingOptions) {
            options.shippingOptions = shippingOptions;
        }

        if (data) {
            options = $.extend(true, {}, options, data);
        }

        return options;
    };
    /**
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.get_display_items = function () {
        return this.get_gateway_data().items;
    };
    /**
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.set_display_items = function (items) {
        var data = this.get_gateway_data();
        data.items = items;
        this.set_gateway_data(data);
    };
    /**
     * Return an array of shipping options for display in the Google payment sheet
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.get_shipping_options = function () {
        return this.get_gateway_data().shipping_options;
    };
    /**
     * Update the shipping options.
     * @param {[type]}
     */


    wc_stripe.BaseGateway.prototype.set_shipping_options = function (items) {
        var data = this.get_gateway_data();
        data.shipping_options = items;
        this.set_gateway_data(data);
    };
    /**
     * Maps an address from the Browser address format to WC format.
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.map_address = function (address) {
        return {
            city: address.city,
            postcode: address.postalCode,
            state: address.region,
            country: address.country
        };
    };
    /**
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.on_payment_method_received = function (paymentResponse) {
        try {
            this.payment_response = paymentResponse;
            this.populate_checkout_fields(paymentResponse);
            paymentResponse.complete("success");
            this.on_token_received(paymentResponse.paymentMethod);
        } catch (err) {
            window.alert(err);
        }
    };
    /**
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.populate_checkout_fields = function (data) {
        this.set_nonce(data.paymentMethod.id);
        this.update_addresses(data);
    };
    /**
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.update_addresses = function (data) {
        if (data.payerName) {
            this.fields.set('name', data.payerName, 'billing');
        }

        if (data.payerEmail) {
            this.fields.set('email', data.payerEmail, 'billing');
        }

        if (data.payerPhone) {
            this.fields.set('phone', data.payerPhone, 'billing');
        }

        if (data.shippingAddress) {
            this.populate_shipping_fields(data.shippingAddress);
        }

        if (data.paymentMethod.billing_details.address) {
            this.populate_billing_fields(data.paymentMethod.billing_details.address);
        }
    };

    wc_stripe.BaseGateway.prototype.populate_address_fields = function (address, prefix) {
        for (var k in address) {
            this.fields.set(k, address[k], prefix);
        }
    }

    wc_stripe.BaseGateway.prototype.populate_billing_fields = function (address) {
        this.populate_address_fields(address, 'billing');
    }

    wc_stripe.BaseGateway.prototype.populate_shipping_fields = function (address) {
        this.populate_address_fields(address, 'shipping');
    }

    wc_stripe.BaseGateway.prototype.address_mappings = function () {
        return new wc_stripe.CheckoutFields();
    };

    wc_stripe.BaseGateway.prototype.ajax_before_send = function (xhr) {
        if (this.params.user_id > 0) {
            xhr.setRequestHeader('X-WP-Nonce', this.params.rest_nonce);
        }
    };
    /**
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.process_checkout = function () {
        return new Promise(function () {
            this.block();
            $.ajax({
                url: this.params.routes.checkout,
                method: 'POST',
                dataType: 'json',
                data: $.extend({}, this.serialize_fields(), {
                    payment_method: this.gateway_id,
                    page_id: this.get_page()
                }),
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (result) {
                if (result.reload) {
                    window.location.reload();
                    return;
                }

                if (result.result === 'success') {
                    window.location = result.redirect;
                } else {
                    if (result.messages) {
                        this.submit_error(result.messages);
                    }

                    this.unblock();
                }
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.unblock();
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this));
    };
    /**
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.serialize_form = function ($form) {
        var formData = $form.find('input').filter(function (i, e) {
                if ($(e).is('[name^="add-to-cart"]')) {
                    return false;
                }

                return true;
            }.bind(this)).serializeArray(),
            data = {};

        for (var i in formData) {
            var obj = formData[i];
            data[obj.name] = obj.value;
        }

        data.payment_method = this.gateway_id;
        return data;
    };

    wc_stripe.BaseGateway.prototype.serialize_fields = function () {
        return $.extend({}, this.fields.toJson(), $(document.body).triggerHandler('wc_stripe_process_checkout_data', [this, this.fields]));
    };
    /**
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.BaseGateway.prototype.map_shipping_methods = function (shippingData) {
        var methods = {};

        if (shippingData !== "default") {
            var matches = shippingData.match(/^([\w+]):(.+)$/);

            if (matches.length > 1) {
                methods[matches[1]] = matches[2];
            }
        }

        return methods;
    };
    /**
     * [maybe_set_ship_to_different description]
     * @return {[type]} [description]
     */


    wc_stripe.BaseGateway.prototype.maybe_set_ship_to_different = function () {
        // if shipping and billing address are different,
        // set the ship to different address option.
        if ($('[name="ship_to_different_address"]').length) {
            $('[name="ship_to_different_address"]').prop('checked', this.get_address_field_hash("billing") !== this.get_address_field_hash("shipping")).trigger('change');
        }
    };

    wc_stripe.BaseGateway.prototype.update_shipping_address = function (ev) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: this.params.routes.shipping_address,
                method: 'POST',
                dataType: 'json',
                data: {
                    address: this.map_address(ev.shippingAddress),
                    payment_method: this.gateway_id,
                    page_id: this.get_page()
                },
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                if (response.code) {
                    ev.updateWith(response.data.newData);
                    reject(response.data);
                } else {
                    ev.updateWith(response.data.newData);
                    this.fields.set('shipping_method', data.shipping_method);
                    resolve(response.data);
                }
            }.bind(this)).fail(function () {
            }.bind(this));
        }.bind(this));
    };
    /**
     * @return {[@event]}
     */


    wc_stripe.BaseGateway.prototype.update_shipping_method = function (ev) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: this.params.routes.shipping_method,
                method: 'POST',
                dataType: 'json',
                data: {
                    shipping_method: ev.shippingOption.id,
                    payment_method: this.gateway_id,
                    page_id: this.get_page()
                },
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                if (response.code) {
                    ev.updateWith(response.data.newData);
                    reject(response.data);
                } else {
                    this.set_selected_shipping_methods(response.data.shipping_methods);
                    ev.updateWith(response.data.newData);
                    resolve(response.data);
                }
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this));
    };
    /********** Checkout Gateway ********/

    /**
     * @constructor
     */


    wc_stripe.CheckoutGateway = function () {
        this.message_container = 'li.payment_method_' + this.gateway_id;
        this.banner_container = 'li.banner_payment_method_' + this.gateway_id;
        $(document.body).on('update_checkout', this.update_checkout.bind(this));
        $(document.body).on('updated_checkout', this.updated_checkout.bind(this));
        $(document.body).on('cfw_updated_checkout', this.updated_checkout.bind(this));
        $(document.body).on('checkout_error', this.checkout_error.bind(this));
        $(this.token_selector).closest('form').on('checkout_place_order_' + this.gateway_id, this.checkout_place_order.bind(this)); // events for showing gateway payment buttons

        $(document.body).on('wc_stripe_new_method_' + this.gateway_id, this.on_show_new_methods.bind(this));
        $(document.body).on('wc_stripe_saved_method_' + this.gateway_id, this.on_show_saved_methods.bind(this));
        $(document.body).on('wc_stripe_payment_method_selected', this.on_payment_method_selected.bind(this));

        if (this.banner_enabled()) {
            if ($('.woocommerce-billing-fields').length) {
                $('.wc-stripe-banner-checkout').css('max-width', $('.woocommerce-billing-fields').outerWidth(true));
            }
        }

        this.order_review();
    };

    wc_stripe.CheckoutGateway.prototype.order_review = function () {
        var url = window.location.href;
        var matches = url.match(/order_review.+payment_method=([\w]+).+payment_nonce=(.+)/);

        if (matches && matches.length > 1) {
            var payment_method = matches[1],
                nonce = matches[2];

            if (this.gateway_id === payment_method) {
                this.payment_token_received = true;
                this.set_nonce(nonce);
                this.set_use_new_option(true);
            }
        }
    };

    wc_stripe.CheckoutGateway.prototype.update_shipping_address = function () {
        return wc_stripe.BaseGateway.prototype.update_shipping_address.apply(this, arguments).then(function (data) {
            // populate the checkout fields with the address
            this.populate_address_fields(data.address, this.get_shipping_prefix());
            this.fields.toFormFields({update_shipping_method: false});
        }.bind(this));
    }

    wc_stripe.CheckoutGateway.prototype.get_shipping_prefix = function () {
        if (this.needs_shipping() && $('[name="ship_to_different_address"]').length > 0 && $('[name="ship_to_different_address"]').is(':checked')) {
            return 'shipping';
        }
        return 'billing';
    }

    /**
     * Called on the WC updated_checkout event
     */
    wc_stripe.CheckoutGateway.prototype.updated_checkout = function () {
    };

    /**
     * Called on the WC update_checkout event
     */
    wc_stripe.CheckoutGateway.prototype.update_checkout = function () {
    };
    /**
     * Called on the WC checkout_error event
     */


    wc_stripe.CheckoutGateway.prototype.checkout_error = function () {
        if (this.has_checkout_error()) {
            this.payment_token_received = false;
            this.payment_response = null;
            this.show_payment_button();
            this.hide_place_order();
        }
    };
    /**
     *
     */


    wc_stripe.CheckoutGateway.prototype.is_valid_checkout = function () {
        if ($('[name="terms"]').length) {
            if (!$('[name="terms"]').is(':checked')) {
                return false;
            }
        }

        return true;
    };
    /**
     * Returns the selected payment gateway's id.
     *
     * @returns {String}
     */


    wc_stripe.CheckoutGateway.prototype.get_payment_method = function () {
        return $('[name="payment_method"]:checked').val();
    };

    wc_stripe.CheckoutGateway.prototype.set_use_new_option = function (bool) {
        $('#' + this.gateway_id + '_use_new').prop('checked', bool).trigger('change');
    };
    /**
     * Called on the WC checkout_place_order_{$gateway_id} event
     */


    wc_stripe.CheckoutGateway.prototype.checkout_place_order = function () {
        if (!this.is_valid_checkout()) {
            this.submit_error(this.params.messages.terms);
            return false;
        } else if (this.is_saved_method_selected()) {
            return true;
        }

        return this.payment_token_received;
    };
    /**
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.CheckoutGateway.prototype.on_token_received = function (paymentMethod) {
        this.payment_token_received = true;
        this.set_nonce(paymentMethod.id);
        this.hide_payment_button();
        this.show_place_order();
    };
    /**
     * @return {[type]}
     */


    wc_stripe.CheckoutGateway.prototype.block = function () {
        if ($().block) {
            $('form.checkout').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }

    };
    /**
     * @return {[type]}
     */


    wc_stripe.CheckoutGateway.prototype.unblock = function () {
        if ($().block) {
            $('form.checkout').unblock();
        }
    };

    wc_stripe.CheckoutGateway.prototype.hide_place_order = function () {
        $('#place_order').addClass('wc-stripe-hide');
    };
    /**
     * @return {[type]}
     */


    wc_stripe.CheckoutGateway.prototype.show_place_order = function () {
        $('#place_order').removeClass('wc-stripe-hide');
    };
    /**
     * Method that should perform actions when the show new methods contain is made visible.
     * @param  {[@event]}
     * @param  {[String]}
     * @return {[type]}
     */


    wc_stripe.CheckoutGateway.prototype.on_show_new_methods = function () {
        if (this.payment_token_received) {
            this.show_place_order();
            this.hide_payment_button();
        } else {
            this.hide_place_order();
            this.show_payment_button();
        }
    };
    /**
     * Method that performs actions when the saved methods contains is visible.
     * @param  {[type]}
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.CheckoutGateway.prototype.on_show_saved_methods = function () {
        this.hide_payment_button();
        this.show_place_order();
    };
    /**
     * @return {[type]}
     */


    wc_stripe.CheckoutGateway.prototype.show_payment_button = function () {
        if (this.$button) {
            this.$button.show();
        }
    };
    /**
     * @return {[type]}
     */


    wc_stripe.CheckoutGateway.prototype.hide_payment_button = function () {
        if (this.$button) {
            this.$button.hide();
        }
    };
    /**
     * Wrapper for on_payment_method_selected that is safe to call since it won't trigger
     * any DOM events.
     * @return {[type]}
     */


    wc_stripe.CheckoutGateway.prototype.trigger_payment_method_selected = function () {
        this.on_payment_method_selected(null, $('[name="payment_method"]:checked').val());
    };
    /**
     * @param  {[type]}
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.CheckoutGateway.prototype.on_payment_method_selected = function (e, payment_method) {
        if (payment_method === this.gateway_id) {
            if (this.payment_token_received || this.is_saved_method_selected()) {
                this.hide_payment_button();
                this.show_place_order();
            } else {
                this.show_payment_button();
                this.hide_place_order();
            }
        } else {
            this.hide_payment_button();

            if (payment_method.indexOf('stripe_') < 0) {
                this.show_place_order();
            }
        }
    };
    /**
     * [Return true if the banner option has been enabled for the gateway.]
     * @return {[type]} [description]
     */


    wc_stripe.CheckoutGateway.prototype.banner_enabled = function () {
        return this.params.banner_enabled === '1';
    };

    wc_stripe.CheckoutGateway.prototype.checkout_fields_valid = function () {
        if (typeof wc_stripe_checkout_fields === 'undefined' || this.get_page() !== 'checkout') {
            return true;
        }

        var valid = true;

        function validateFields(prefix, fields) {
            for (var k in fields) {
                var field = fields[k];

                if (k.indexOf(prefix) > -1 && field.required) {
                    if ($('#' + k).length) {
                        var val = $('#' + k).val();

                        if (typeof val === 'undefined' || val === null || val.length == 0) {
                            valid = false;
                            return;
                        }
                    }
                }
            }
        }

        validateFields('billing', wc_stripe_checkout_fields);

        if (this.needs_shipping() && $('#ship-to-different-address-checkbox').is(':checked')) {
            validateFields('shipping', wc_stripe_checkout_fields);
        }

        if (valid) {
            valid = this.is_valid_checkout();
        }

        return valid;
    };
    /************** Product Gateway ***************/


    wc_stripe.ProductGateway = function () {
        this.message_container = 'div.product'; // events

        $('form.cart').on('found_variation', this.found_variation.bind(this));
        $('form.cart').on('reset_data', this.reset_variation_data.bind(this));
        this.buttonWidth = $('form.cart div.quantity').outerWidth(true) + $('.single_add_to_cart_button').outerWidth();
        var marginLeft = $('.single_add_to_cart_button').css('marginLeft');

        if (marginLeft) {
            this.buttonWidth += parseInt(marginLeft.replace('px', ''));
        }

        $(this.container).css('max-width', this.buttonWidth + 'px');
    };
    /**
     * @return {[@int]}
     */


    wc_stripe.ProductGateway.prototype.get_quantity = function () {
        return parseInt($('[name="quantity"]').val());
    };
    /**
     * @param {[type]}
     * @param {[type]}
     */


    wc_stripe.ProductGateway.prototype.set_rest_nonce = function (e, nonce) {
        this.params.rest_nonce = nonce;
    };
    /**
     * @param  {[type]}
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.ProductGateway.prototype.found_variation = function (e, variation) {
        var data = this.get_gateway_data();
        data.product.price = variation.display_price;
        data.needs_shipping = !variation.is_virtual;
        data.product.variation = variation;
        this.set_gateway_data(data);
    };
    /**
     * @return {[type]}
     */


    wc_stripe.ProductGateway.prototype.reset_variation_data = function () {
        var data = this.get_product_data();
        data.variation = false;
        this.set_product_data(data);
        this.disable_payment_button();
    };
    /**
     * @return {[type]}
     */


    wc_stripe.ProductGateway.prototype.disable_payment_button = function () {
        if (this.$button) {
            this.get_button().prop('disabled', true).addClass('disabled');
        }
    };
    /**
     * @return {[type]}
     */


    wc_stripe.ProductGateway.prototype.enable_payment_button = function () {
        if (this.$button) {
            this.get_button().prop('disabled', false).removeClass('disabled');
        }
    };
    /**
     * @return {[type]}
     */


    wc_stripe.ProductGateway.prototype.get_button = function () {
        return this.$button;
    };
    /**
     * @return {Boolean}
     */


    wc_stripe.ProductGateway.prototype.is_variable_product = function () {
        return $('[name="variation_id"]').length > 0;
    };

    wc_stripe.ProductGateway.prototype.variable_product_selected = function () {
        return this.get_product_data().variation !== false;
    };
    /**
     * @return {[type]}
     */


    wc_stripe.ProductGateway.prototype.get_product_data = function () {
        return this.get_gateway_data().product;
    };
    /**
     * @return {[type]}
     */


    wc_stripe.ProductGateway.prototype.set_product_data = function (product) {
        var data = this.get_gateway_data();
        data.product = product;
        this.set_gateway_data(data);
    };
    /**
     * Add a product to the WC shopping cart
     */


    wc_stripe.ProductGateway.prototype.add_to_cart = function () {
        return new Promise(function (resolve, reject) {
            this.block();
            $.ajax({
                url: this.params.routes.add_to_cart,
                method: 'POST',
                dataType: 'json',
                data: {
                    product_id: this.get_product_data().id,
                    variation_id: this.is_variable_product() ? $('[name="variation_id"]').val() : 0,
                    qty: $('[name="quantity"]').val(),
                    payment_method: this.gateway_id,
                    page_id: this.get_page()
                },
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                this.unblock();

                if (response.code) {
                    this.submit_error(response.message);
                    reject(response);
                } else {
                    this.set_total_price(response.data.total);
                    this.set_total_price_cents(response.data.totalCents);
                    this.set_display_items(response.data.displayItems);
                    resolve(response.data);
                }
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.unblock();
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this));
    };

    wc_stripe.ProductGateway.prototype.cart_calculation = function (variation_id) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: this.params.routes.cart_calculation,
                method: 'POST',
                dataType: 'json',
                data: {
                    product_id: this.get_product_data().id,
                    variation_id: this.is_variable_product() && variation_id ? variation_id : 0,
                    qty: $('[name="quantity"]').val(),
                    payment_method: this.gateway_id
                },
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                if (response.code) {
                    this.cart_calculation_error = true;
                    reject(response);
                } else {
                    this.set_total_price(response.data.total);
                    this.set_total_price_cents(response.data.totalCents);
                    this.set_display_items(response.data.displayItems);
                    resolve(response.data);
                }
            }.bind(this)).fail(function () {
            }.bind(this));
        }.bind(this));
    };
    /************* Cart Gateway *************/

    /**
     * @constructor
     */


    wc_stripe.CartGateway = function () {
        this.message_container = 'div.woocommerce'; // cart events

        $(document.body).on('updated_wc_div', this.updated_html.bind(this));
        $(document.body).on('updated_cart_totals', this.updated_html.bind(this));
        $(document.body).on('wc_cart_emptied', this.cart_emptied.bind(this));
    };
    /**
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.CartGateway.prototype.submit_error = function (message) {
        this.submit_message(this.get_error_message(message));
    };
    /**
     * @param  {[@event]}
     * @return {[null]}
     */


    wc_stripe.CartGateway.prototype.updated_html = function (e) {
    };

    wc_stripe.CartGateway.prototype.cart_emptied = function (e) {
    };

    wc_stripe.CartGateway.prototype.add_cart_totals_class = function () {
        $('.cart_totals').addClass('stripe_cart_gateway_active');
    };

    /************* Google Pay Mixins **************/

    wc_stripe.GooglePay = function () {
    };

    var googlePayBaseRequest = {
        apiVersion: 2,
        apiVersionMinor: 0
    };
    var allowedCardNetworks = ["AMEX", "DISCOVER", "INTERAC", "JCB", "MASTERCARD", "VISA"];
    var allowedCardAuthMethods = ["PAN_ONLY"];
    var baseCardPaymentMethod = {
        type: 'CARD',
        parameters: {
            allowedAuthMethods: allowedCardAuthMethods,
            allowedCardNetworks: allowedCardNetworks
        }
    };
    /**
     * Populate the WC checkout fields.
     * @param  {[type]}
     * @return {[type]}
     */

    wc_stripe.GooglePay.prototype.update_addresses = function (paymentData) {
        this.populate_billing_fields(paymentData.paymentMethodData.info.billingAddress);

        if (paymentData.shippingAddress) {
            this.populate_shipping_fields(paymentData.shippingAddress);
        }

        if (paymentData.email) {
            this.fields.set('email', paymentData.email, 'billing');
        }
    };
    /**
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.GooglePay.prototype.map_address = function (address) {
        return {
            city: address.locality,
            postcode: address.postalCode,
            state: address.administrativeArea,
            country: address.countryCode
        };
    };
    /**
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.GooglePay.prototype.update_payment_data = function (data) {
        return new Promise(function (resolve, reject) {
            var shipping_method = data.shippingOptionData.id == 'default' ? null : data.shippingOptionData.id;
            $.when($.ajax({
                url: this.params.routes.payment_data,
                dataType: 'json',
                method: 'POST',
                data: {
                    shipping_address: this.map_address(data.shippingAddress),
                    shipping_method: shipping_method,
                    page_id: this.get_page()
                },
                beforeSend: this.ajax_before_send.bind(this)
            })).done(function (response) {
                if (response.code) {
                    reject(response.data.data);
                } else {
                    resolve(response.data);
                }
            }.bind(this)).fail(function () {
                reject();
            }.bind(this));
        }.bind(this));
    };
    /**
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.GooglePay.prototype.on_payment_data_changed = function (address) {
        return new Promise(function (resolve) {
            this.update_payment_data(address).then(function (response) {
                resolve(response.paymentRequestUpdate);
                this.set_selected_shipping_methods(response.shipping_methods);
                this.payment_data_updated(response, address);
            }.bind(this))['catch'](function (data) {
                resolve(data);
            }.bind(this));
        }.bind(this));
    };
    /**
     * Convenience method so that gateway can perform actions after the payment data
     * has been updated.
     * @param  {[type]}
     * @return {[type]}
     */


    wc_stripe.GooglePay.prototype.payment_data_updated = function (response) {
    };
    /**
     * @return {[type]}
     */


    wc_stripe.GooglePay.prototype.get_merchant_info = function () {
        var options = {
            merchantId: this.params.merchant_id,
            merchantName: this.params.merchant_name
        };

        if (this.params.environment === 'TEST') {
            delete options.merchantId;
        }

        return options;
    };
    /**
     * @return {[type]}
     */


    wc_stripe.GooglePay.prototype.get_payment_options = function () {
        var options = {
            environment: this.params.environment,
            merchantInfo: this.get_merchant_info()
        };

        if (this.needs_shipping()) {
            options.paymentDataCallbacks = {
                onPaymentDataChanged: this.on_payment_data_changed.bind(this),
                onPaymentAuthorized: function (data) {
                    return new Promise(function (resolve, reject) {
                        resolve({
                            transactionState: "SUCCESS"
                        });
                    }.bind(this));
                }.bind(this)
            };
        } else {
            options.paymentDataCallbacks = {
                onPaymentAuthorized: function onPaymentAuthorized(data) {
                    return new Promise(function (resolve, reject) {
                        resolve({
                            transactionState: "SUCCESS"
                        });
                    }.bind(this));
                }
            };
        }

        return options;
    };
    /**
     * @return {[type]}
     */


    wc_stripe.GooglePay.prototype.build_payment_request = function () {
        var request = $.extend({}, googlePayBaseRequest, {
            emailRequired: function () {
                if ('checkout' === this.get_page()) {
                    return this.fields.required('billing_email') && this.fields.isEmpty('billing_email');
                } else if ('order_pay' === this.get_page()) {
                    return false;
                }

                return this.fields.required('billing_email');
            }.bind(this)(),
            merchantInfo: this.get_merchant_info(),
            allowedPaymentMethods: [$.extend({
                type: "CARD",
                tokenizationSpecification: {
                    type: "PAYMENT_GATEWAY",
                    parameters: {
                        gateway: 'stripe',
                        "stripe:version": "2018-10-31",
                        "stripe:publishableKey": this.params.api_key
                    }
                }
            }, baseCardPaymentMethod)],
            shippingAddressRequired: this.needs_shipping(),
            transactionInfo: {
                currencyCode: this.get_currency(),
                totalPriceStatus: "ESTIMATED",
                totalPrice: this.get_total_price().toString(),
                displayItems: this.get_display_items(),
                totalPriceLabel: this.params.total_price_label
            }
        });
        request.allowedPaymentMethods[0].parameters['billingAddressRequired'] = true;
        request.allowedPaymentMethods[0].parameters['billingAddressParameters'] = {
            format: "FULL",
            phoneNumberRequired: function () {
                if ('checkout' === this.get_page()) {
                    return this.fields.required('billing_phone') && this.fields.isEmpty('billing_phone');
                } else if ('order_pay' === this.get_page()) {
                    return false;
                }

                return this.fields.required('billing_phone');
            }.bind(this)()
        };

        if (this.needs_shipping()) {
            request['shippingAddressParameters'] = {};
            request['shippingOptionRequired'] = true;
            request['shippingOptionParameters'] = {
                shippingOptions: this.get_shipping_options()
            };
            request['callbackIntents'] = ["SHIPPING_ADDRESS", "SHIPPING_OPTION", "PAYMENT_AUTHORIZATION"];
        } else {
            request['callbackIntents'] = ["PAYMENT_AUTHORIZATION"];
        }

        return request;
    };
    /**
     * @return {[type]}
     */


    wc_stripe.GooglePay.prototype.createPaymentsClient = function () {
        this.paymentsClient = new google.payments.api.PaymentsClient(this.get_payment_options());
    };
    /**
     * @return {Promise}
     */


    wc_stripe.GooglePay.prototype.isReadyToPay = function () {
        return new Promise(function (resolve) {
            var isReadyToPayRequest = $.extend({}, googlePayBaseRequest);
            isReadyToPayRequest.allowedPaymentMethods = [baseCardPaymentMethod];
            this.paymentsClient.isReadyToPay(isReadyToPayRequest).then(function () {
                this.can_pay = true;
                this.create_button();
                resolve();
            }.bind(this))["catch"](function (err) {
                this.submit_error(err);
            }.bind(this));
        }.bind(this));
    };

    wc_stripe.GooglePay.prototype.create_button = function () {
        if (this.$button) {
            this.$button.remove();
        }

        this.$button = $(this.paymentsClient.createButton({
            onClick: this.start.bind(this),
            buttonColor: this.params.button_color,
            buttonType: this.params.button_style
        }));
        this.$button.addClass('gpay-button-container');
    };
    /**
     * @return {[type]}
     */


    wc_stripe.GooglePay.prototype.start = function () {
        // always recreate the paymentClient to ensure latest data is used.
        this.createPaymentsClient();
        this.paymentsClient.loadPaymentData(this.build_payment_request()).then(function (paymentData) {
            var data = JSON.parse(paymentData.paymentMethodData.tokenizationData.token);
            this.update_addresses(paymentData);
            this.on_token_received(data);
        }.bind(this))["catch"](function (err) {
            if (err.statusCode === "CANCELED") {
                return;
            }

            if (err.statusMessage && err.statusMessage.indexOf("paymentDataRequest.callbackIntent") > -1) {
                this.submit_error_code("DEVELOPER_ERROR_WHITELIST");
            } else {
                this.submit_error(err.statusMessage);
            }
        }.bind(this));
    };

    wc_stripe.ApplePay = function () {
    };

    wc_stripe.ApplePay.prototype.initialize = function () {
        var selector = '.apple-pay-button';

        if (['checkout', 'order_pay'].indexOf(this.get_page()) < 0) {
            selector = this.container + ' .apple-pay-button';
        }

        $(document.body).on('click', selector, this.start.bind(this));
        this.createPaymentRequest();
        this.canMakePayment();
    };

    wc_stripe.ApplePay.prototype.create_button = function () {
        if (this.$button) {
            this.$button.remove();
        }

        this.$button = $(this.params.button);
        this.append_button();
    };

    wc_stripe.ApplePay.prototype.canMakePayment = function () {
        return new Promise(function (resolve) {
            this.paymentRequest.canMakePayment().then(function (result) {
                if (result && result.applePay) {
                    this.can_pay = true;
                    this.create_button();
                    $(this.container).show();
                    resolve(result);
                }
            }.bind(this));
        }.bind(this));
    };

    wc_stripe.ApplePay.prototype.start = function (e) {
        e.preventDefault();
        this.paymentRequest.update(this.get_payment_request_update({
            total: {
                pending: false
            }
        }));
        this.paymentRequest.show();
    };

    /*********** PaymentRequest *********/
    wc_stripe.PaymentRequest = function () {
    };

    wc_stripe.PaymentRequest.prototype.initialize = function () {
        this.createPaymentRequest();
        this.canMakePayment();
        this.createPaymentRequestButton();
        this.paymentRequestButton.on('click', this.button_click.bind(this));
    };

    wc_stripe.PaymentRequest.prototype.button_click = function (event) {
    };

    wc_stripe.PaymentRequest.prototype.createPaymentRequestButton = function () {
        this.paymentRequestButton = this.elements.create("paymentRequestButton", {
            paymentRequest: this.paymentRequest,
            style: {
                paymentRequestButton: {
                    type: this.params.button.type,
                    theme: this.params.button.theme,
                    height: this.params.button.height
                }
            }
        });
    };

    wc_stripe.PaymentRequest.prototype.canMakePayment = function () {
        return new Promise(function (resolve) {
            this.paymentRequest.canMakePayment().then(function (result) {
                if (result && !result.applePay) {
                    this.can_pay = true;
                    this.create_button();
                    $(this.container).show();
                    resolve(result);
                }
            }.bind(this));
        }.bind(this));
    };

    wc_stripe.PaymentRequest.prototype.create_button = function () {
        this.paymentRequestButton.mount('#wc-stripe-payment-request-container');
    };

    wc_stripe.CheckoutFields = function (params, page) {
        this.params = params;
        this.page = page;
        this.fields = new Map(Object.keys(this.params).map(function (k) {
            return [k, this.params[k].value];
        }.bind(this)));

        if ('checkout' === page) {
            $('form.checkout').on('change', '.input-text, select', this.onChange.bind(this));
            $('form.checkout').on('change', '[name="ship_to_different_address"]', this.on_ship_to_address_change.bind(this));
            this.init_i18n();
        }
    };

    wc_stripe.CheckoutFields.prototype.init_i18n = function () {
        if (typeof wc_address_i18n_params !== 'undefined') {
            this.locales = $.parseJSON(wc_address_i18n_params.locale.replace(/&quot;/g, '"'));
        } else {
            this.locales = null;
        }
    };

    wc_stripe.CheckoutFields.prototype.onChange = function (e) {
        try {
            var name = e.currentTarget.name,
                value = e.currentTarget.value;
            this.fields.set(name, value);

            if (name === 'billing_country' || name === 'shipping_country') {
                this.update_required_fields(value, name);
            }
        } catch (err) {
            console.log(err);
        }
    };

    wc_stripe.CheckoutFields.prototype.update_required_fields = function (country, name) {
        if (this.locales) {
            var prefix = name.indexOf('billing_') > -1 ? 'billing_' : 'shipping_';
            var locale = typeof this.locales[country] !== 'undefined' ? this.locales[country] : this.locales['default'];
            var fields = $.extend(true, {}, this.locales['default'], locale);

            for (var k in fields) {
                var k2 = prefix + k;

                if (this.params[k2]) {
                    this.params[k2] = $.extend(true, {}, this.params[k2], fields[k]);
                }
            }
        }
    };

    wc_stripe.CheckoutFields.prototype.on_ship_to_address_change = function (e) {
        if ($(e.currentTarget).is(':checked')) {
            this.update_required_fields($('shipping_country'), 'shipping_country');
        }
    };

    wc_stripe.CheckoutFields.prototype.requestFieldInWallet = function (key) {
        if ('checkout' === this.page) {
            return this.required(key) && this.isEmpty(key);
        } else if ('order_pay' === this.page) {
            return false;
        }

        return this.required(key);
    };

    wc_stripe.CheckoutFields.prototype.set = function (k, v, prefix) {
        if (this[k] && typeof this[k] === 'function') {
            this[k]().set.call(this, v, prefix);
        } else {
            this.fields.set(k, v);
        }
    };

    wc_stripe.CheckoutFields.prototype.get = function (k, prefix) {
        if (this[k] && typeof this[k] === 'function') {
            var value = this[k]().get.call(this, prefix);
        } else {
            var value = this.fields.get(k);

            if (typeof value === 'undefined' || value === null || value === '') {
                if (typeof prefix !== 'undefined') {
                    value = prefix;
                }
            }
        }

        return typeof value === 'undefined' ? '' : value;
    };
    /**
     * Return true if the field is required
     * @param k
     * @returns boolean
     */


    wc_stripe.CheckoutFields.prototype.required = function (k) {
        if (this.params[k]) {
            if (typeof this.params[k].required !== 'undefined') {
                return this.params[k].required;
            }
        }

        return false;
    };

    wc_stripe.CheckoutFields.prototype.isEmpty = function (k) {
        if (this.fields.has(k)) {
            var value = this.fields.get(k);
            return typeof value === 'undefined' || value === null || typeof value === 'string' && value.trim().length === 0;
        }

        return true;
    };

    wc_stripe.CheckoutFields.prototype.name = function () {
        return {
            set: function set(v, prefix) {
                var name = v.split(" ");
                this.fields.set(prefix + '_first_name', name[0]);
                this.fields.set(prefix + '_last_name', name[1]);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_first_name') + ' ' + this.fields.get(prefix + '_last_name');
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.payerName = function () {
        return wc_stripe.CheckoutFields.prototype.name.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.email = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_email', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_email');
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.payerEmail = function () {
        return wc_stripe.CheckoutFields.prototype.email.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.phone = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_phone', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_phone');
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.payerPhone = function () {
        return wc_stripe.CheckoutFields.prototype.phone.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.phoneNumber = function () {
        return wc_stripe.CheckoutFields.prototype.phone.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.recipient = function () {
        return {
            set: function set(v, prefix) {
                var name = v.split(" ");

                if (name.length > 0) {
                    this.fields.set(prefix + '_first_name', name[0]);
                }

                if (name.length > 1) {
                    this.fields.set(prefix + '_last_name', name[1]);
                }
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_first_name') + ' ' + this.fields.get(prefix + '_last_name');
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.country = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_country', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_country');
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.countryCode = function () {
        return wc_stripe.CheckoutFields.prototype.country.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.address1 = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_address_1', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_address_1');
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.address2 = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_address_2', v);
            },
            get: function get(prefix) {
                this.fields.get(prefix + '_address_2');
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.line1 = function () {
        return wc_stripe.CheckoutFields.prototype.address1.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.line2 = function () {
        return wc_stripe.CheckoutFields.prototype.address2.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.addressLine = function () {
        return {
            set: function set(v, prefix) {
                if (v.length > 0) {
                    this.fields.set(prefix + '_address_1', v[0]);
                }

                if (v.length > 1) {
                    this.fields.set(prefix + '_address_2', v[1]);
                }
            },
            get: function get(prefix) {
                return [this.fields.get(prefix + '_address_1'), this.fields.get(prefix + '_address_2')];
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.state = function () {
        return {
            set: function set(v, prefix) {
                v = v.toUpperCase();
                if (v.length > 2 && this.page === 'checkout') {
                    $('#' + prefix + '_state option').each(function () {
                        var $option = $(this);
                        var state = $option.text().toUpperCase();
                        if (v === state) {
                            v = $option.val();
                        }
                    });
                }
                this.fields.set(prefix + '_state', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_state');
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.region = function () {
        return wc_stripe.CheckoutFields.prototype.state.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.administrativeArea = function () {
        return wc_stripe.CheckoutFields.prototype.state.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.city = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_city', v);
            },
            get: function get(prefix) {
                this.fields.get(prefix + '_city');
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.locality = function () {
        return wc_stripe.CheckoutFields.prototype.city.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.postcode = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_postcode', v);
            },
            get: function get(prefix) {
                this.fields.get(prefix + '_postcode');
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.postal_code = function () {
        return wc_stripe.CheckoutFields.prototype.postcode.apply(this, arguments);
    }

    wc_stripe.CheckoutFields.prototype.postalCode = function () {
        return wc_stripe.CheckoutFields.prototype.postcode.apply(this, arguments);
    };
    /**
     * Serialize the fields into an expected format
     */


    wc_stripe.CheckoutFields.prototype.toJson = function () {
        var data = {};
        this.fields.forEach(function (value, key) {
            data[key] = value;
        });
        return data;
    };

    wc_stripe.CheckoutFields.prototype.toFormFields = function (args) {
        var changes = [];
        this.fields.forEach(function (value, key) {
            var name = '[name="' + key + '"]';

            if ($(name).length && value !== '') {
                if ($(name).val() !== value && $(name).is('select')) {
                    changes.push(name);
                }

                $(name).val(value);
            }
        });
        if (changes.length > 0) {
            $(changes.join(',')).trigger('change');
        }
        if (typeof args !== 'undefined') {
            $(document.body).trigger('update_checkout', args);
        }
    };

    try {
        stripe = Stripe(wc_stripe_params_v3.api_key, {
            stripeAccount: wc_stripe_params_v3.account
        });
    } catch (error) {
        window.alert(error);
        console.log(error);
        return;
    }

    var checkoutFields = new wc_stripe.CheckoutFields(wc_stripe_checkout_fields, wc_stripe_params_v3.page);
})(window, jQuery);
