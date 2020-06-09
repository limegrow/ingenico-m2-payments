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
        rendererList.push(
            {
                type: 'ingenico_e_payments',
                component: 'Ingenico_Payment/js/view/payment/method-renderer/ingenico-e-payments'
            }
        );
        return Component.extend({});
    }
);