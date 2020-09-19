(function($) {

    /**
     * [Notice description]
     */
    function Notice() {
        this.params = wc_stripe_notices_params;
        this.container = this.params.container;
        this.notices = this.params.notices;
        this.display_notices();
    }

    /**
     * [display_notice description]
     * @return {[type]} [description]
     */
    Notice.prototype.display_notices = function() {
        for (var i = 0; i < this.notices.length; i++) {
            $(this.container).prepend(this.notices[i]);
        }
    }

    new Notice();

}(jQuery))