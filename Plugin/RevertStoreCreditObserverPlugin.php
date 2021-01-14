<?php

namespace Ingenico\Payment\Plugin;

use Magento\CustomerBalance\Observer\RevertStoreCreditObserver;
use Magento\Framework\Event\Observer;

class RevertStoreCreditObserverPlugin
{
    public function aroundExecute(
        RevertStoreCreditObserver $subject,
        callable $proceed,
        Observer $observer
    ) {
        /* @var $order \Magento\Sales\Model\Order */
        $order = $observer->getEvent()->getOrder();

        if ($observer->getEvent()->getName() === 'restore_quote'
            && strpos($order->getPayment()->getMethod(), 'ingenico_') === 0
        ) {
            // Don't execute original observer:
            return $subject;
        }

        return $proceed($observer);
    }
}
