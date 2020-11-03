<?php

namespace Ingenico\Payment\Model\Method;

class KlarnaPayLater extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_klarna_paylater';
    const CORE_CODE = \IngenicoClient\PaymentMethod\KlarnaPayLater::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
    protected $_canUseForMultishipping = false;
}
