define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/redirect-on-success',
    'mage/url',
    'mage/translate'
], function (Component, redirectOnSuccessAction, url, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            redirectAfterPlaceOrder: false,
            template: 'Ingenico_Payment/payment/alias',
        },

        /**
         * Get payment method type.
         */
        getTitle: function () {
            return $t("Pay with saved card.");
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
            return window.checkoutConfig.payment.ingenico.savedCards.filter(function(item) {
                return item.code !== '';
            });
        },

        selectSavedCard: function (data, event) {
            var redirectUrl = url.build(window.checkoutConfig.payment.ingenico.redirectUri);
            if (data.code){
                redirectUrl = url.build(window.checkoutConfig.payment.ingenico.redirectUri + '/alias/' + data.code);
            }

            window.checkoutConfig.payment.ingenico.redirectUrl = redirectUrl;
            return true;
        }
    });
});
