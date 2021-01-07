<?php

namespace Ingenico\Payment\Model\Method;

class Bancontact extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_bancontact';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Bancontact::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
