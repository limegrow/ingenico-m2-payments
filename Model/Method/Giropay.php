<?php

namespace Ingenico\Payment\Model\Method;

class Giropay extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_giropay';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Giropay::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
