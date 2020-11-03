<?php

namespace Ingenico\Payment\Model\Method;

class KlarnaPayNow extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_klarna_paynow';
    const CORE_CODE = \IngenicoClient\PaymentMethod\KlarnaPayNow::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
    protected $_canUseForMultishipping = false;
}
