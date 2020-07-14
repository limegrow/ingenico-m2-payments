<?php

namespace Ingenico\Payment\Plugin;

class OrderStatePlugin
{
    protected $_orderNotifier;
    protected $_cnf;

    public function __construct(
        \Magento\Sales\Model\OrderNotifier $orderNotifier,
        \Ingenico\Payment\Model\Config $cnf
    ) {
        $this->_orderNotifier = $orderNotifier;
        $this->_cnf = $cnf;
    }
    
    /**
     * Interceptor Function for Sending Order Email
     */
    public function afterSave(\Magento\Sales\Api\OrderRepositoryInterface $subject, $result)
    {
        if ($result->getPayment()->getMethod() == \Ingenico\Payment\Model\Method\Ingenico::PAYMENT_METHOD_CODE) {
            if ($this->_cnf->getOrderConfirmationEmailMode() == '3') {
                $targetStatus = $this->_cnf->getOrderStatusForConfirmationEmail();
                if (!$result->getEmailSent() 
                    && $result->getData('status') == $targetStatus 
                    && $result->getOrigData('status') !== $targetStatus) {
                        $this->_orderNotifier->notify($result);
                }
            }
        }
        
        return $result;
    }
}
