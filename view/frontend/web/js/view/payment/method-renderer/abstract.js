define([
    'jquery',
    'mage/translate',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/redirect-on-success',
    'mage/url',
    'Ingenico_Payment/js/action/sprintf'
], function ($, $t, quote, Component, redirectOnSuccessAction, url, sprintf) {
    'use strict';

    return Component.extend({
        defaults: {
            redirectAfterPlaceOrder: false,
            template: null,
            iFrameUrl: '',
            iFrameId:'',
            iFrameCssDisplay: 'block',
            helperText: '',
            isPlaceOrderAllowed: null,
            aliasId: null,
            cardBrand: null
        },

        logos: [],
        methodTitle: '',

        initialize: function () {
            this._super();

            // Get template
            this.template = 'Ingenico_Payment/payment/' + this.getTemplateName();

            this.observe([
                'iFrameUrl',
                'iFrameId',
                'iFrameCssDisplay',
                'helperText',
                'isPlaceOrderAllowed',
                'aliasId',
                'cardBrand'
            ]);

            if (this.getMethodCategory() === 'card') {
                $('body').on('ingenico:inline:success', this.inlineSuccess.bind(this));
                $('body').on('ingenico:inline:failure', this.inlineFailure.bind(this));
                this.iFrameId(this.getIFrameId());
            }

            this.iFrameUrl(this.getIFrameUrl());
            this.isPlaceOrderAllowed(this.getPaymentMode() === 'redirect');

            return this;
        },

        inlineSuccess: function(event, aliasId, cardBrand) {
            if (quote.paymentMethod().method === this.getCode()) {
                this.iFrameUrl('');
                this.isPlaceOrderAllowed(true);
                this.aliasId(aliasId);
                this.cardBrand(cardBrand);
                this.hideIframe();
                if (!this.placeOrder()) {
                    this.fillHelperText(
                        sprintf(
                            $t("Could not submit the order. Please check your details and {0} try again {1} or choose another payment method."),
                            "<a href='javascript:void(0)' id='" + this.getIFrameRetryId() + "'>",
                            "</a>"
                        )
                    );
                    this.isPlaceOrderAllowed(false);
                } else {
                    this.helperText(
                        sprintf(
                            $t("Your payment data is ready to be processed by {0}."),
                            "Ingenico ePayments"
                        )
                    );
                }
            }
        },

        inlineFailure: function(event, aliasId, cardBrand) {
            if (quote.paymentMethod().method === this.getCode()) {
                this.iFrameUrl('');
                this.fillHelperText(
                    sprintf(
                        $t("Please {0} try again {1} or choose another payment method."),
                        "<a href='javascript:void(0)' id='" + this.getIFrameRetryId() + "'>",
                        "</a>"
                    )
                );
                this.isPlaceOrderAllowed(false);
                this.hideIframe();
            }
        },

        hideIframe: function () {
            this.iFrameCssDisplay('none');
        },

        showIframe: function () {
            this.iFrameCssDisplay('block');
        },

        resetIFrame: function () {
            this.emptyHelperText();
            this.isPlaceOrderAllowed(false);
            this.showIframe();

            // Workaround: this.iFrameUrl(this.getIFrameUrl()) does not update the iframe
            document.getElementById(this.getIFrameId()).src = this.getIFrameUrl();
        },

        fillHelperText: function (html) {
            $('#' + this.getIFrameId()).siblings('.cc-helper-text').html(html);
            $('#' + this.getIFrameRetryId()).on('click', this.resetIFrame.bind(this));
        },

        emptyHelperText: function () {
            $('#' + this.getIFrameId()).siblings('.cc-helper-text').html('');
        },

        /**
         * After place order callback
         */
        afterPlaceOrder: function () {
            // OpenInvoice require "inline" payment page to pay
            if (this.getMethodCategory() === 'open_invoice') {
                var redirectUrlObj1 = new URL(window.checkoutConfig.payment.ingenico.openInvoiceUrl);
                redirectOnSuccessAction.redirectUrl = redirectUrlObj1.toString();
                redirectOnSuccessAction.execute();
                return;
            }

            if (this.getPaymentMode() !== 'redirect' && this.getMethodCategory() === 'card') {
                var redirectUrlObj = new URL(window.checkoutConfig.payment.ingenico.inlineUrl);
                redirectUrlObj.searchParams.set('alias', this.aliasId());
                redirectUrlObj.searchParams.set('cardbrand', this.cardBrand());

                redirectOnSuccessAction.redirectUrl = redirectUrlObj.toString();
                redirectOnSuccessAction.execute();
                return;
            }

            redirectOnSuccessAction.redirectUrl = window.checkoutConfig.payment.ingenico.redirectUrl;
            redirectOnSuccessAction.execute();
        },

        getTemplateName: function () {
            var templateName;

            if (this.getCode() === 'ingenico_e_payments') {
                return  'ingenico-e-payments';
            }

            switch (this.getMethodCategory()) {
                case 'e_wallet':
                case 'open_invoice':
                case 'real_time_banking':
                case 'prepaid_vouchers':
                    templateName = 'redirect';
                    break;
                case 'card':
                    templateName = (this.getPaymentMode() === 'redirect') ? 'cc-redirect' : 'cc-form';
                    break;
                default:
                    // Non-registered category
                    templateName = 'redirect';
            }

            return templateName;
        },

        getPaymentMode: function () {
            return window.checkoutConfig.payment.ingenico.paymentMode;
        },

        getMethodCategory: function () {
            return window.checkoutConfig.payment.ingenico.methods[this.getCode()].category;
        },

        getIFrameUrl: function () {
            return window.checkoutConfig.payment.ingenico.methods[this.getCode()].url;
        },

        getIFrameId: function () {
            return this.getCode() + '_iframe';
        },

        getIFrameRetryId: function () {
            return this.getIFrameId() + '_retry';
        },

        getLogos: function () {
            if (this.logos.length === 0) {
                if (window.checkoutConfig.payment.ingenico.methodLogos.hasOwnProperty(this.getCode())) {
                    this.logos = window.checkoutConfig.payment.ingenico.methodLogos[this.getCode()];
                }
            }

            return this.logos;
        },

        getMethodName: function () {
            if (this.logos.length > 0) {
                if (window.checkoutConfig.payment.ingenico.methodLogos.hasOwnProperty(this.getCode())) {
                    this.methodTitle = window.checkoutConfig.payment.ingenico.methodLogos[this.getCode()].title;
                }
            }

            return this.methodTitle;
        },

        getTitle: function () {
            var title = this._super();

            // IE-1188
            if (title === 'Credit Cards') {
                title = $t('Credit Cards');
            }

            return title;
        },
    });
});
