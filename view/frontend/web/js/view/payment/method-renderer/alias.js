define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/redirect-on-success',
    'mage/url',
    'mage/translate',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Ui/js/modal/alert'
], function (
    $,
    Component,
    redirectOnSuccessAction,
    url,
    $t,
    urlBuilder,
    storage,
    fullScreenLoader,
    alert
) {
    'use strict';

    return Component.extend({
        defaults: {
            redirectAfterPlaceOrder: false,
            isPlaceOrderActionAllowed: null,
            template: 'Ingenico_Payment/payment/alias',
        },

        initialize: function () {
            this._super();
            this.isPlaceOrderActionAllowed(true);

            var self = this;
            $(document).on('click', '.payment-method._active [name="payment[alias]"]', function () {
                self.togglePlaceOrder();
            });

            // Check the first item
            setTimeout(function () {
                if (!$('.payment-method._active [name="payment[alias]"]:checked').val()) {
                    $('.payment-method._active [name="payment[alias]"]:first').prop('checked', true).click();
                }
            }, 3000);

            return this;
        },

        initObservable: function () {
            this._super().observe([
                'isPlaceOrderActionAllowed'
            ]);

            return this;
        },

        /**
         * Get payment method type.
         */
        getTitle: function () {
            return $t("Pay with saved card.");
        },

        /**
         * @override
         */
        getData: function () {
            return {
                'method': this.getCode(),
                'additional_data': {
                    'alias': $('.payment-method._active input[name="payment[alias]"]:checked').val(),
                }
            };
        },

        /**
         * After place order callback
         */
        afterPlaceOrder: function () {
            redirectOnSuccessAction.redirectUrl = window.checkoutConfig.payment.ingenico.redirectUrl;
            redirectOnSuccessAction.execute();
        },

        getMethodLogos: function () {
            return window.checkoutConfig.payment.ingenico.methodLogos;
        },

        getCCLogos: function () {
            return window.checkoutConfig.payment.ingenico.ccLogos;
        },

        getSavedCards: function () {
            return window.checkoutConfig.payment.ingenico.savedCards.filter(function (item) {
                return item.code !== '';
            });
        },

        selectSavedCard: function (data, event) {
            this.isPlaceOrderActionAllowed = true;

            var redirectUrl = url.build(window.checkoutConfig.payment.ingenico.redirectUrl);

            if (data.code) {
                redirectUrl += '/alias/' + data.code;
            }

            window.checkoutConfig.payment.ingenico.redirectUrl = redirectUrl;
            return true;
        },

        removeSavedCard: function (data, event) {
            var serviceUrl = urlBuilder.createUrl('/ingenico/payments/remove_alias/alias/aliasID', {
                'aliasID': data.code,
            });

            var self = this;

            fullScreenLoader.startLoader();

            return storage.post(
                serviceUrl
            ).always(function () {
                fullScreenLoader.stopLoader();
            }).done(function (response) {
                fullScreenLoader.stopLoader();

                $(event.target).closest('.card').remove();
                self.togglePlaceOrder();
            }).error(function (xhr) {
                fullScreenLoader.stopLoader();

                alert({
                    title: $t('Error'),
                    content: xhr.responseJSON.message,
                    actions: {
                        always: function (){}
                    }
                });
            });
        },

        enablePlaceOrder: function() {
            console.log('enablePlaceOrder');
            $('.payment-method._active button.checkout').removeClass('disabled');
        },

        disablePlaceOrder: function() {
            console.log('disablePlaceOrder');
            $('.payment-method._active button.checkout').addClass('disabled');
        },

        togglePlaceOrder: function () {
            console.log('togglePlaceOrder');
            if ($('.payment-method._active [name="payment[alias]"]').length === 0) {
                this.disablePlaceOrder();
                return;
            }

            var selected = $('.payment-method._active [name="payment[alias]"]:checked').val();
            if (selected) {
                this.enablePlaceOrder();
            } else {
                this.disablePlaceOrder();
            }
        }
    });
});
