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
            template: 'Ingenico_Payment/payment/alias',
        },

        afterRender: function () {
            var self = this;
            var int = setInterval(function () {
                let data = self.getData();

                if (data.additional_data.alias) {
                    self.selectSavedCard({code: data.additional_data.alias}, null);
                    window.clearInterval(int);
                }
            }, 1000);

        },

        initialize: function () {
            this._super();

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

        /**
         * Get Saved Cards.
         *
         * @returns {*}
         */
        getSavedCards: function () {
            return window.checkoutConfig.payment.ingenico_alias.savedCards.filter(function (item) {
                return item.code !== '';
            });
        },

        /**
         * Is Card Selected.
         *
         * @param aliasId
         * @returns {boolean}
         */
        isSelected: function (aliasId) {
            return aliasId === window.checkoutConfig.payment.ingenico_alias.default;
        },

        /**
         * Select Saved Card.
         *
         * @param data
         * @param event
         * @returns {boolean}
         */
        selectSavedCard: function (data, event) {
            if (data.code) {
                var redirectUrl = window.checkoutConfig.payment.ingenico_alias.aliasPayUrl.replace('aliasID', data.code);
                window.checkoutConfig.payment.ingenico.redirectUrl = redirectUrl;
            }

            return true;
        },

        /**
         * Remove Saved Card.
         *
         * @param data
         * @param event
         * @returns {*}
         */
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

                // Deactivate button
                if ($('.payment-method._active [name="payment[alias]"]').length === 0) {
                    $('.payment-method._active button.checkout').addClass('disabled');
                }
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
        }
    });
});
