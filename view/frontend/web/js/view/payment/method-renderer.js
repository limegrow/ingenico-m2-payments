define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        _.each(window.checkoutConfig.payment.ingenico.methods, function (method, methodCode, list) {
            switch (methodCode) {
                case 'ingenico_alias':
                    if (window.checkoutConfig.payment.ingenico_alias.use_saved_cards &&
                        window.checkoutConfig.payment.ingenico_alias.savedCards.length > 1
                    ) {
                        rendererList.push(
                            {
                                type: 'ingenico_alias',
                                component: 'Ingenico_Payment/js/view/payment/method-renderer/alias'
                            }
                        );
                    }

                    return;
                case 'ingenico_ideal':
                    rendererList.push(
                        {
                            type: methodCode,
                            component: 'Ingenico_Payment/js/view/payment/method-renderer/ideal'
                        }
                    );
                    return;

                case 'ingenico_flex':
                    if (_.size(window.checkoutConfig.payment.ingenico_flex.methods) > 0) {
                        rendererList.push({
                            type: methodCode,
                            component: 'Ingenico_Payment/js/view/payment/method-renderer/flex'
                        });
                    }

                    return;
                default:
                    rendererList.push({
                        type: methodCode,
                        component: 'Ingenico_Payment/js/view/payment/method-renderer/abstract'
                    });
            }
        });

        return Component.extend({});
    }
);
