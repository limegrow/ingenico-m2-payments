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
            flexMethod: {title: null, pm: null, brand: null}
        },

        logos: [],
        methodTitle: '',

        afterRender: function () {
            // Initialize payment methods
            var self = this;
            if (this.isFlexSingleMode()) {
                // Select the single item automatically
                _.each(window.checkoutConfig.payment.ingenico_flex.methods, function (method, index, list) {
                    self.setFlexMethod(null, method.pm, method.brand);
                });
            } else {
                // Check the first item
                var int = setInterval(function () {
                    if ($('.payment-method [name="payment[ingenico_flex][method]"]').length > 0) {
                        if (!$('.payment-method [name="payment[ingenico_flex][method]"]:checked').val()) {
                            let first = $('.payment-method [name="payment[ingenico_flex][method]"]:first');
                            first.click();
                            first.prop('checked', true);

                            let method = first.prop('id').split('_');
                            self.setFlexMethod(null, method[1], method[2]);
                        }

                        window.clearInterval(int);
                    }
                }, 1000);
            }
        },

        initialize: function () {
            this._super();

            // Get template
            this.template = 'Ingenico_Payment/payment/' + this.getTemplateName();

            this.observe([
                'flexMethod'
            ]);

            this.isPlaceOrderAllowed(this.getPaymentMode() === 'redirect');

            return this;
        },

        getTemplateName: function () {
            return 'flex';
        },

        /**
         * @override
         */
        getData: function () {
            return {
                'method': this.getCode(),
                'additional_data': {
                    'flex_title': this.flexMethod().title,
                    'flex_pm': this.flexMethod().pm,
                    'flex_brand': this.flexMethod().brand
                }
            };
        },


        /**
         * Get Flex methods.
         *
         * @returns {*}
         */
        getFlexMethods: function () {
            var methods = window.checkoutConfig.payment.ingenico_flex.methods;
            return _.map(methods, function (method) {
                return {
                    'title': method.title,
                    'pm': method.pm,
                    'brand': method.brand
                };
            });
        },

        /**
         * Check if have onle one Flex method.
         *
         * @returns {boolean}
         */
        isFlexSingleMode: function () {
            return _.size(window.checkoutConfig.payment.ingenico_flex.methods) < 2;
        },

        /**
         * Set Flex method.
         *
         * @param title
         * @param pm
         * @param brand
         */
        setFlexMethod: function (title, pm, brand) {
            this.flexMethod({'title': title, 'pm': pm, 'brand': brand});
        },
    });
});
