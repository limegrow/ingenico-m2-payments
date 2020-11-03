<?php

namespace Ingenico\Payment\Model\Method;

class Ideal extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_ideal';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Ideal::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
