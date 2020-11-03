<?php

namespace Ingenico\Payment\Model\Method;

class Ing extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_ing';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Ing::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
