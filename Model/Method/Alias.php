<?php

namespace Ingenico\Payment\Model\Method;

class Alias extends Cc
{
    const PAYMENT_METHOD_CODE = 'ingenico_alias';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Visa::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
