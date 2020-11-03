<?php

namespace Ingenico\Payment\Model\Method;

/**
 * Ingenico payment method model
 */
class Ingenico extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_e_payments';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Visa::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;

    /**
     * @inheritDoc
     */
    public function isActive($storeId = null)
    {
        // The general payment method is meant for redirect mode only
        if ($this->cnf->isPaymentPageModeInline()) {
            return false;
        }

        return parent::isActive($storeId);
    }
}
