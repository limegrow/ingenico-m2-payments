<?php

namespace Ingenico\Payment\Model\Method;

class Afterpay extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_afterpay';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Afterpay::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
    protected $_canUseForMultishipping = false;
}
