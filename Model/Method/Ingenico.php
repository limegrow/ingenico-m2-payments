<?php

namespace Ingenico\Payment\Model\Method;

/**
 * Ingenico payment method model
 */
class Ingenico extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_e_payments';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Ingenico::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
