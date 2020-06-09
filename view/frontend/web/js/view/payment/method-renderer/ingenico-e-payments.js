define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/redirect-on-success',
    'mage/url'
], function (Component, redirectOnSuccessAction, url) {
    'use strict';
    
    return Component.extend({
        defaults: {
            redirectAfterPlaceOrder: false,
            template: 'Ingenico_Payment/payment/ingenico-e-payments'
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
        
        getSavedCards: function () {
            return window.checkoutConfig.payment.ingenico.savedCards;
        },
        
        selectSavedCard: function (data, event) {
            var redirectUrl = url.build(window.checkoutConfig.payment.ingenico.redirectUri);
            if(data.code){
                redirectUrl = url.build(window.checkoutConfig.payment.ingenico.redirectUri + '/alias/' + data.code);
            }
            window.checkoutConfig.payment.ingenico.redirectUrl = redirectUrl;
            return true;
        },
        
        showLogos: function () {
            return window.checkoutConfig.payment.ingenico.titleMode == 'logos';
        },
        
        showTitle: function () {
            var titleMode = window.checkoutConfig.payment.ingenico.titleMode;
            return titleMode == 'names' || titleMode == 'text';
        }
    });
});