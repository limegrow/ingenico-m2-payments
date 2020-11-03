<?php

namespace Ingenico\Payment\Model\Method;

class Kbc extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_kbc';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Kbc::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
