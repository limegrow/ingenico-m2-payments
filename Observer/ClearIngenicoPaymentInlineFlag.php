<?php

declare(strict_types=1);

namespace Ingenico\Payment\Observer;

use Ingenico\Payment\Model\CheckIsReturnFromPaymentInline;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ClearIngenicoPaymentInlineFlag extends CheckIsReturnFromPaymentInline implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        // clear session value
        $this->isRequestFromPaymentInlinePage();
    }
}
