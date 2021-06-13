define([
    'jquery'
], function ($) {
    'use strict';

    return function () {
        var agreementForm,
            agreementData,
            agreementIds = [],
            agreementSelected = [];

        if (!window.checkoutConfig.checkoutAgreements.isEnabled) {
            return true;
        }

        // Get the agreements configuration
        window.checkoutConfig.checkoutAgreements.agreements.forEach(function (item) {
            if (item.mode === '1') {
                agreementIds.push(item.agreementId);
            }
        });

        // Check the agreements form
        agreementForm = $('.payment-method._active div[data-role=checkout-agreements] input');
        agreementData = agreementForm.serializeArray();
        agreementData.forEach(function (item, index) {
            agreementSelected.push(item.value);
        });

        if (agreementSelected.length === 0) {
            return false;
        }

        return agreementIds.length === agreementSelected.length;
    };
});

