<?php

namespace Ingenico\Payment\Model\Method;

class Paysafecard extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_paysafecard';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Paysafecard::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
