define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'jquery'
    ],
    function (
        Component,
        rendererList,
        $
    ) {
        'use strict';

        if (window.checkoutConfig.payment.ingenico.savedCards.length > 1) {
            rendererList.push(
                {
                    type: 'ingenico_alias',
                    component: 'Ingenico_Payment/js/view/payment/method-renderer/alias'
                }
            );
        }

        $.each(window.checkoutConfig.payment.ingenico.methods, function(methodCode, method) {
            rendererList.push({
                type: methodCode,
                component: 'Ingenico_Payment/js/view/payment/method-renderer/abstract'
            });
        });

        return Component.extend({});
    }
);
