(function($) {
    /**
     * @constructor
     */
    function MetaBox() {
        this.params = wc_stripe_order_metabox_params;
        this.initialize();
    }

    MetaBox.prototype.initialize = function() {
        $(document.body).on('click', '.do-transaction-view',
            this.render_charge_view.bind(this)).on('click',
            '.do-api-capture', this.do_api_capture.bind(this)).on('click',
            '.do-api-cancel', this.do_api_cancel.bind(this)).on('click', '.wc-stripe-pay-order', this.open_pay_order_modal.bind(this)).on(
            'change', '#customer_user', this.fetch_payment_methods.bind(this));
    }

    /**
     * Fetch the charge view data and render the modal.
     */
    MetaBox.prototype.render_charge_view = function(e) {
        e.preventDefault();
        var $icon = $(e.target);
        $icon.addClass('disabled');
        if (!$icon.data('charge')) {
            $.when($.ajax({
                method: 'GET',
                dataType: 'json',
                url: this.params.routes.charge_view,
                data: {
                    order_id: $icon.data('order'),
                    _wpnonce: this.params._wpnonce
                }
            })).done(function(response) {
                if (!response.code) {
                    $icon.data('charge', response.data);
                    $icon.removeClass('disabled');
                    $icon.WCBackboneModal({
                        template: 'wc-stripe-view-transaction',
                        variable: response.data
                    });
                } else {
                    window.alert(response.message);
                }
            }.bind(this)).fail(function(jqXHR, textStatus, errorThrown) {
                $icon.removeClass('disabled');
                window.alert(errorThrown);
            }.bind(this))
        } else {
            $icon.WCBackboneModal({
                template: 'wc-stripe-view-transaction',
                variable: $icon.data('charge')
            });
        }
    }

    /**
     * 
     */
    MetaBox.prototype.do_api_capture = function(e) {
        e.preventDefault();
        var $modal = $('.wc-transaction-data');
        this.block($modal);
        $.when($.ajax({
            method: 'POST',
            dataType: 'json',
            url: this.params.routes.capture,
            data: {
                _wpnonce: this.params._wpnonce,
                order_id: $('#post_ID').val(),
                amount: $('[name="capture_amount"]').val()
            },
        }).done(function(response) {
            if (!response.code) {
                window.location.reload();
            } else {
                this.unblock($modal);
                window.alert(response.message);
            }
        }.bind(this))).fail(function(jqXHR, textStatus, errorThrown) {
            this.unblock($modal);
            window.alert(errorThrown);
        }.bind(this));
    }

    /**
     * 
     */
    MetaBox.prototype.do_api_cancel = function(e) {
        e.preventDefault();
        var $modal = $('.wc-transaction-data');
        this.block($modal);
        $.when($.ajax({
            method: 'POST',
            dataType: 'json',
            url: this.params.routes.void,
            data: {
                _wpnonce: this.params._wpnonce,
                order_id: $('#post_ID').val()
            },
        }).done(function(response) {
            if (!response.code) {
                window.location.reload();
            } else {
                this.unblock($modal);
                window.alert(response.message);
            }
        }.bind(this))).fail(function(jqXHR, textStatus, errorThrown) {
            this.unblock($modal);
            window.alert(errorThrown);
        }.bind(this));
    }

    MetaBox.prototype.open_pay_order_modal = function(e) {
        e.preventDefault();
        $(e.target).WCStripePayOrderBackboneModal({
            template: 'wc-stripe-modal-pay-order',
            params: {
                customer_id: $('#customer_user').val(),
                payment_methods: wc_stripe_order_pay_params.payment_methods,
                order_id: $('#post_ID').val()
            }
        })
    }

    MetaBox.prototype.fetch_payment_methods = function(e) {
        wc_stripe_order_pay_params.payment_methods = [];
        var customer_id = $('#customer_user').val();
        if (customer_id) {
            $.ajax({
                method: 'GET',
                dataType: 'json',
                url: this.params.routes.payment_methods,
                data: {
                    _wpnonce: this.params._wpnonce,
                    customer_id: customer_id
                }
            }).done(function(response) {
                wc_stripe_order_pay_params.payment_methods = response.payment_methods;
            }.bind(this)).fail(function() {

            }.bind(this))
        }
    }

    /**
     * 
     */
    MetaBox.prototype.block = function($el) {
        $el.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
    }

    /**
     * 
     */
    MetaBox.prototype.unblock = function($el) {
        $el.unblock();
    }

    new MetaBox();
}(jQuery))