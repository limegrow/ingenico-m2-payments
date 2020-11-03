<?php

namespace Ingenico\Payment\Model\Method;

class Cbc extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_cbc';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Cbc::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
