<?php

namespace Ingenico\Payment\Plugin;

use Magento\Store\Model\StoreManagerInterface;
use Ingenico\Payment\Model\Config;
use Ingenico\Payment\Model\Connector;
use Ingenico\Payment\Helper\Data as IngenicoHelper;
use Ingenico\Payment\Model\Config\Source\Settings\OrderEmail;

class MagentoSalesModelOrder
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $cnf;

    /**
     * @var Connector
     */
    private $connector;

    /**
     * @var IngenicoHelper
     */
    private $ingenicoHelper;

    public function __construct(
        StoreManagerInterface $storeManager,
        Config $cnf,
        Connector $connector,
        IngenicoHelper $ingenicoHelper
    ) {
        $this->storeManager = $storeManager;
        $this->connector    = $connector;
        $this->ingenicoHelper = $ingenicoHelper;
        $this->cnf = $cnf;
    }

    /**
     * Interceptor Function for Preventing Sending Email
     */
    public function afterGetCanSendNewEmailFlag(\Magento\Sales\Model\Order $subject, $result)
    {
        if (in_array($subject->getPayment()->getMethod(), $this->ingenicoHelper->getPaymentMethodCodes())) {
            if ($this->cnf->getOrderConfirmationEmailMode($subject->getStoreId()) === OrderEmail::STATUS_ENABLED) {
                return $result;
            }

            return false;
        }

        return $result;
    }
}
