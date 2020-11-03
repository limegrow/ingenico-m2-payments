<?php

namespace Ingenico\Payment\Model\Method;

class Klarna extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_klarna';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Klarna::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
    protected $_canUseForMultishipping = false;
}
