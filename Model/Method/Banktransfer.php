<?php

namespace Ingenico\Payment\Model\Method;

class Banktransfer extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_banktransfer';
    const CORE_CODE = \IngenicoClient\PaymentMethod\BankTransfer::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;
}
