<?php

namespace Ingenico\Payment\Observer;

class CancelPaymentAuthorisation implements \Magento\Framework\Event\ObserverInterface
{
    private $_connector;
    private $_systemConfig;

    public function __construct(
        \Ingenico\Payment\Model\Connector $connector,
        \Ingenico\Payment\Model\Config $systemConfig
    ) {
        $this->_connector = $connector;
        $this->_systemConfig = $systemConfig;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getData('order');

        if ($order->getOrigData('state') !== $order::STATE_PENDING_PAYMENT) {
            return;
        }

        try {
            $this->_connector->setOrderId($order->getIncrementId());
            $this->_connector->getCoreLibrary()->cancel($order->getIncrementId(), null);
        } catch (\Exception $e) {
            $this->_connector->log($e->getMessage(), 'crit');
            $order->addStatusToHistory($order->getStatus(), $e->getMessage());
        }
    }
}
