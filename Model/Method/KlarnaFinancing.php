<?php

namespace Ingenico\Payment\Model\Method;

class KlarnaFinancing extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_klarna_financing';
    const CORE_CODE = \IngenicoClient\PaymentMethod\KlarnaFinancing::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
    protected $_canUseForMultishipping = false;
}
