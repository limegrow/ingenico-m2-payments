<?php

namespace Ingenico\Payment\Model\Method;

class PayPal extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_paypal';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Paypal::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
