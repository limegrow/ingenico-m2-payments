<?php

namespace Ingenico\Payment\Model\Method;

class Belfius extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_belfius';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Belfius::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
