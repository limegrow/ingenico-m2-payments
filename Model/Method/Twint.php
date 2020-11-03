<?php

namespace Ingenico\Payment\Model\Method;

class Twint extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_twint';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Twint::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
