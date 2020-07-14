<?php

namespace Ingenico\Payment\Plugin;

class MagentoSalesModelOrder
{
    protected $_storeManager;
    protected $_connector;
    protected $_cnf;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ingenico\Payment\Model\Config $cnf,
        \Ingenico\Payment\Model\Connector $connector
    ) {
        $this->_storeManager = $storeManager;
        $this->_connector = $connector;
        $this->_cnf = $cnf;
    }

    /**
     * Interceptor Function for Preventing Sending Email
     */
    public function afterGetCanSendNewEmailFlag(\Magento\Sales\Model\Order $subject, $result)
    {
        if ($subject->getPayment()->getMethod() == \Ingenico\Payment\Model\Method\Ingenico::PAYMENT_METHOD_CODE) {
            if ($this->_cnf->getOrderConfirmationEmailMode() == '0') {
                return $result;
            }
            return false;
        }
        return $result;
    }
}
