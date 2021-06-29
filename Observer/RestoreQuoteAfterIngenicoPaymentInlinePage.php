<?php

declare(strict_types=1);

namespace Ingenico\Payment\Observer;

use Ingenico\Payment\Model\CheckIsReturnFromPaymentInline;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RestoreQuoteAfterIngenicoPaymentInlinePage extends CheckIsReturnFromPaymentInline implements ObserverInterface
{
    /*
     * Restore shopping cart when customer back from ingenico/payment/inline page.
     */

    public function execute(Observer $observer)
    {
        if (!$this->isRequestFromPaymentInlinePage()) {
            return ;
        }

        $this->checkoutSession->restoreQuote();
    }
}
