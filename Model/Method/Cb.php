<?php

namespace Ingenico\Payment\Model\Method;

class Cb extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_cb';
    const CORE_CODE = \IngenicoClient\PaymentMethod\CarteBancaire::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;

    /**
     * @inheritDoc
     */
    public function isActive($storeId = null)
    {
        // Temporarily disable the Carte Banclair PM until we have time to get to work in INLINE mode
        if ($this->cnf->isPaymentPageModeInline()) {
            return false;
        }

        return parent::isActive($storeId);
    }
}
