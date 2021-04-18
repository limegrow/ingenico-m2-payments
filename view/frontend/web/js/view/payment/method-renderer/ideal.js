define([
    'ko',
    'jquery',
    'Ingenico_Payment/js/view/payment/method-renderer/abstract',
    'Magento_Checkout/js/action/redirect-on-success'
], function (
    ko,
    $,
    Component
) {
    return Component.extend({
        defaults: {
            redirectAfterPlaceOrder: false,
            template: null,
            issuerId: {issuer_id: null}
        },

        isActive: function () {
            return this.issuerId().issuer_id !== '';
        },

        initialize: function () {
            this._super();

            // Get template
            this.template = 'Ingenico_Payment/payment/' + this.getTemplateName();

            this.observe([
                'issuerId'
            ]);

            this.isPlaceOrderAllowed(this.getPaymentMode() === 'redirect');

            return this;
        },

        getTemplateName: function () {
            return 'ingenico_ideal';
        },

        /**
         * @override
         */
        getData: function () {
            return {
                'method': this.getCode(),
                'additional_data': {
                    'issuer_id': this.issuerId().issuer_id,
                }
            };
        },

        /**
         * Get iDeal Banks
         * @returns []
         */
        getIdealBanks: function () {
            let banks = window.checkoutConfig.payment.ingenico_ideal.banks;
            return _.map(banks, function (bank) {
                return {
                    'value': bank.value,
                    'label': bank.label,
                };
            });
        },

        /**
         * On IssuerId Change.
         *
         * @param obj
         * @param event
         */
        onIssuerIdChange: function (obj, event) {
            this.setIssuerId($(event.target).val());
        },

        /**
         * Set Issuer ID.
         *
         * @param issuerId
         */
        setIssuerId: function (issuerId) {
            this.issuerId({'issuer_id': issuerId});
        },
    });
});
