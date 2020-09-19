(function ($, wc_stripe) {

    var place_order_width = $('#place_order').css('width');

    // this will ensure the place order's width does not change when the 
    // text for the local payment method is added
    $(document.body).on('updated_checkout', function () {
        $('#place_order').css('min-width', place_order_width)
    })

    /**
     * [LocalPayment description]
     */
    function LocalPayment(params) {
        wc_stripe.BaseGateway.call(this, params);
        wc_stripe.CheckoutGateway.call(this);

        $(document.body).on('click', '#place_order', this.place_order.bind(this));

        if ($(document.body).is('.woocommerce-order-pay')) {
            $('#order_review').on('submit', this.process_order_pay.bind(this));
        }

        this.maybe_hide_gateway();
    }

    LocalPayment.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.CheckoutGateway.prototype);


    LocalPayment.prototype.initialize = function () {
        this.mount_button();
    }

    LocalPayment.prototype.elementType = null;

    LocalPayment.prototype.maybe_hide_gateway = function () {
        if (!$('#wc_stripe_local_payment_' + this.gateway_id).data('active')) {
            $(this.container).hide();
        } else {
            $(this.container).show();
        }
    }

    /**
     * [createSource description]
     * @return {[type]} [description]
     */
    LocalPayment.prototype.createSource = function () {
        return new Promise(function (resolve, reject) {
            var handler = function (result) {
                if (result.error) {
                    this.submit_error(result.error.message);
                } else {
                    this.payment_token_received = true;
                    $(this.token_selector).val(result.source.id);
                    this.get_form().submit();
                }
                resolve();
            }.bind(this);
            if (this.elementType != null) {
                if (this.confirmation_method) {
                    if (this.isValidElement()) {
                        this.payment_token_received = true;
                        this.get_form().submit();
                    } else {
                        return this.submit_error({
                            code: 'empty_element_' + this.params.local_payment_type,
                            message: wc_stripe_messages.empty_element
                        });
                    }
                } else {
                    this.stripe.createSource(this.element, this.getSourceArgs()).then(handler).catch(function (e) {
                        this.submit_error(e.message);
                    }.bind(this))
                }
            } else {
                this.payment_token_received = true;
                this.get_form().submit();
            }
        }.bind(this));
    }

    LocalPayment.prototype.place_order = function (e) {
        if (this.is_gateway_selected()) {
            if (!this.payment_token_received) {
                e.preventDefault();
                this.createSource();
            }
        }
    }

    LocalPayment.prototype.process_order_pay = function (e) {
        if (this.is_gateway_selected()) {
            e.preventDefault();
            var data = this.get_form().serializeArray();
            data.push({name: '_wpnonce', value: this.params.rest_nonce});
            data.push({name: 'order_id', value: this.params.order_id});
            $.ajax({
                url: this.params.routes.order_pay + window.location.search,
                method: 'POST',
                dataType: 'json',
                data: $.param(data)
            }).done(function (response) {
                if (response.success) {
                    window.location.href = response.redirect;
                } else {
                    this.submit_error(response.message);
                }
            }.bind(this)).fail(function (jqXHR, textStatus, errorThrown) {
                this.submit_error(errorThrown);
            }.bind(this))
        }
    }

    LocalPayment.prototype.show_payment_button = function () {
        this.show_place_order();
    }

    /**
     * [Leave empty so that the place order button is not hidden]
     * @return {[type]} [description]
     */
    LocalPayment.prototype.hide_place_order = function () {

    }

    LocalPayment.prototype.show_place_order = function () {
        wc_stripe.CheckoutGateway.prototype.show_place_order.apply(this, arguments);
        if (this.payment_token_received) {
            $('#place_order').text($('#place_order').data('value'));
        }
    }

    LocalPayment.prototype.getSourceArgs = function () {
        return {
            type: this.params.local_payment_type,
            amount: this.get_total_price_cents(),
            currency: this.get_currency(),
            owner: {
                name: this.get_customer_name('billing'),
                email: this.fields.get('billing_email', null)
            },
            redirect: {
                return_url: this.params.return_url
            }
        }
    }

    LocalPayment.prototype.updated_checkout = function () {
        this.mount_button()
        this.maybe_hide_gateway();
    }

    LocalPayment.prototype.mount_button = function () {
        var id = '#wc_stripe_local_payment_' + this.gateway_id;
        if ($(id).length && this.elementType != null) {
            $(id).empty();
            if (!this.element) {
                this.element = this.elements.create(this.elementType, this.params.element_params);
                this.element.on('change', this.handleElementChange.bind(this));
            }
            this.elementEmpty = true;
            this.element.mount(id);
        }

    }

    LocalPayment.prototype.handleElementChange = function (e) {
        this.elementEmpty = e.empty;
    }

    LocalPayment.prototype.load_external_script = function (url) {
        var script = document.createElement('script');
        script.type = "text/javascript";
        script.src = url;
        script.onload = function () {
            this.script_loaded = true;
        }.bind(this);
        document.body.appendChild(script);
    }

    LocalPayment.prototype.hashChange = function (e) {
        if (this.is_gateway_selected()) {
            var match = e.newURL.match(/response=(.*)/);
            if (match) {
                var obj = JSON.parse(window.atob(match[1]));
                this.processConfirmation(obj);
            }
        }
    }

    LocalPayment.prototype.processConfirmation = function (obj) {
        this.stripe[this.confirmation_method](obj.client_secret, this.get_confirmation_args(obj)).then(function (result) {
            if (result.error) {
                return this.submit_error(result.error.message);
            }
            this.get_form().submit();
        }.bind(this))
    }

    LocalPayment.prototype.get_confirmation_args = function (obj) {
        var args = {
            payment_method: {
                billing_details: {
                    name: this.get_first_name('billing') + ' ' + this.get_last_name('billing')
                }
            },
            return_url: obj.return_url
        };
        if (this.elementType) {
            args.payment_method[this.params.local_payment_type] = this.element;
        }
        return args;
    }

    LocalPayment.prototype.isValidElement = function () {
        if (this.element) {
            return !this.elementEmpty;
        }
        return true;
    }

    LocalPayment.prototype.delete_order_source = function () {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: this.params.routes.delete_source,
                method: 'DELETE',
                dataType: 'json',
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                resolve(response);
            }.bind(this)).fail(function () {
                reject(response);
            }.bind(this))
        }.bind(this))
    }

    /*********** iDEAL ***********/
    function IDEAL(params) {
        this.elementType = 'idealBank';
        this.confirmation_method = 'confirmIdealPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    /******* Sepa *******/
    function Sepa(params) {
        this.elementType = 'iban';
        LocalPayment.call(this, params);
    }

    /****** Klarna ******/
    function Klarna(params) {
        LocalPayment.call(this, params);
        $(document.body).on('change', '.wc-stripe-klarna-category', this.category_change.bind(this));
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function FPX(params) {
        this.elementType = 'fpxBank';
        this.confirmation_method = 'confirmFpxPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function WeChat(params) {
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function BECS(params) {
        this.elementType = 'auBankAccount';
        this.confirmation_method = 'confirmAuBecsDebitPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function GrabPay(params) {
        this.confirmation_method = 'confirmGrabPayPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    Klarna.prototype.category_change = function (e) {
        var $el = $(e.currentTarget);
        if ($el.is(':checked')) {
            $('[id^="klarna-instance-"]').slideUp();
            var category = $('[name="klarna_category"]:checked').val();
            $('#klarna-instance-' + category).slideDown();
        }
    }

    Klarna.prototype.processConfirmation = function (source) {
        var $form = $(this.token_selector).closest('form');
        $form.unblock().removeClass('processing');
        // get the source
        this.source = source;
        window.Klarna.Payments.init({
            client_token: source.klarna.client_token
        }, function (response) {

        }.bind(this));
        this.payment_categories = source.klarna.payment_method_categories.split(",");
        this.render_ui(true);
        this.show_place_order();
    }

    Klarna.prototype.render_ui = function (trigger_change) {
        this.show_form_false = 0;
        if (this.payment_categories.length > 0) {
            $('#wc_stripe_local_payment_stripe_klarna').show();
            for (var i = 0; i < this.payment_categories.length; i++) {
                var container = '#klarna-instance-' + this.payment_categories[i];
                if ($('#klarna-category-' + this.payment_categories[i]).length) {
                    $('#klarna-category-' + this.payment_categories[i]).show();
                    try {
                        window.Klarna.Payments.load({
                            container: container,
                            payment_method_category: this.payment_categories[i], //source.klarna.payment_method_categories
                            instance_id: 'klarna-instance-' + this.payment_categories[i]
                        }, function (response) {
                            if (!response.show_form) {
                                this.show_form_false++;
                                if (this.show_form_false == this.payment_categories.length) {
                                    this.delete_order_source();
                                    this.payment_categories = null;
                                }
                            }
                        }.bind(this));
                    } catch (e) {
                        window.alert(e);
                    }
                }
            }
            if (trigger_change) {
                $('[id^="klarna-category-"]:visible [name="klarna_category"]').first().prop('checked', true).trigger('change');
            }
        }
    }

    Klarna.prototype.place_order = function (e) {
        if (this.is_gateway_selected()) {
            e.preventDefault();
            this.payment_token_received = true;
            if (this.payment_categories) {
                window.Klarna.Payments.authorize({
                    instance_id: 'klarna-instance-' + $('[name="klarna_category"]:checked').val()
                }, function (res) {
                    if (res.approved) {
                        this.block();
                        this.payment_token_received = true;
                        window.location = this.source.redirect;
                    } else {
                        if (res.error) {
                            this.submit_error(res.error);
                        } else {
                            this.payment_categories = null;
                            this.submit_error(this.params.messages.klarna_error);
                        }
                    }
                }.bind(this));
            } else {
                // let checkout process so we can get back a source object
                this.get_form().submit();
            }
        }
    }

    Klarna.prototype.initialize = function () {

    }

    Klarna.prototype.createSource = function () {
        this.block();
    }

    Klarna.prototype.updated_checkout = function () {
        if (this.payment_categories) {
            this.render_ui(false);
        }
        LocalPayment.prototype.updated_checkout.apply(this, arguments);
    }

    WeChat.prototype.updated_checkout = function () {
        if (!this.script_loaded && $(this.container).length) {
            this.load_external_script(this.params.qr_script);
        }
        LocalPayment.prototype.updated_checkout.apply(this, arguments);
    }

    WeChat.prototype.hashChange = function (e) {
        if (this.is_gateway_selected()) {
            var match = e.newURL.match(/qrcode=(.*)/);
            if (match) {
                this.qrcode = JSON.parse(window.atob(match[1]));
                this.get_form().unblock().removeClass('processing').addClass('wechat');
                const qrCode = new QRCode('wc_stripe_local_payment_stripe_wechat', {
                    text: this.qrcode.code,
                    width: 128,
                    height: 128,
                    colorDark: '#424770',
                    colorLight: '#f8fbfd',
                    correctLevel: QRCode.CorrectLevel.H,
                });
                $('#wc_stripe_local_payment_stripe_wechat').append('<p class="qrcode-message">' + this.params.qr_message + '</p>');
                this.payment_token_received = true;
                this.show_place_order();
            }
        }
    }

    WeChat.prototype.place_order = function () {
        if (this.get_form().is('.wechat')) {
            window.location = this.qrcode.redirect;
        } else {
            LocalPayment.prototype.place_order.apply(this, arguments);
        }
    }

    BECS.prototype.get_confirmation_args = function (obj) {
        var args = LocalPayment.prototype.get_confirmation_args.apply(this, arguments);
        args.payment_method.billing_details.email = this.fields.get('billing_email');
        return args;
    }

    IDEAL.prototype = $.extend({}, LocalPayment.prototype, IDEAL.prototype);

    Sepa.prototype = $.extend({}, LocalPayment.prototype, Sepa.prototype);

    Klarna.prototype = $.extend({}, LocalPayment.prototype, Klarna.prototype);

    FPX.prototype = $.extend({}, LocalPayment.prototype, FPX.prototype);

    WeChat.prototype = $.extend({}, LocalPayment.prototype, WeChat.prototype);

    BECS.prototype = $.extend({}, LocalPayment.prototype, BECS.prototype);

    /**
     * Local payment types that require JS integration
     * @type {Object}
     */
    const types = {
        'ideal': IDEAL,
        'sepa_debit': Sepa,
        'klarna': Klarna,
        'fpx': FPX,
        'wechat': WeChat,
        'au_becs_debit': BECS
    }

    for (var i in wc_stripe_local_payment_params.gateways) {
        var params = wc_stripe_local_payment_params.gateways[i];
        if (types[params.local_payment_type]) {
            new types[params.local_payment_type](params);
        } else {
            new LocalPayment(params);
        }
    }

}(jQuery, window.wc_stripe))