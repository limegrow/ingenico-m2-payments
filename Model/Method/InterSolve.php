<?php

namespace Ingenico\Payment\Model\Method;

class InterSolve extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_intersolve';
    const CORE_CODE = \IngenicoClient\PaymentMethod\InterSolve::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
