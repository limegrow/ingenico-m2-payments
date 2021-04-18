<?php

namespace Ingenico\Payment\Model\Method;

class Cb extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_cb';
    const CORE_CODE = \IngenicoClient\PaymentMethod\CarteBancaire::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
