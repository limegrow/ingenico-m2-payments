<?php

namespace Ingenico\Payment\Plugin;

use Ingenico\Payment\Helper\Data as IngenicoHelper;
use Magento\Sales\Model\OrderNotifier;
use Ingenico\Payment\Model\Config;
use Ingenico\Payment\Model\Config\Source\Settings\OrderEmail;

class OrderStatePlugin
{
    /**
     * @var OrderNotifier
     */
    private $orderNotifier;

    /**
     * @var Config
     */
    private $cnf;

    /**
     * @var IngenicoHelper
     */
    private $ingenicoHelper;

    /**
     * Constructor.
     *
     * @param OrderNotifier  $orderNotifier
     * @param Config         $cnf
     * @param IngenicoHelper $ingenicoHelper
     */
    public function __construct(
        OrderNotifier $orderNotifier,
        Config $cnf,
        IngenicoHelper $ingenicoHelper
    ) {
        $this->orderNotifier  = $orderNotifier;
        $this->cnf            = $cnf;
        $this->ingenicoHelper = $ingenicoHelper;
    }

    /**
     * Interceptor Function for Sending Order Email
     */
    public function afterSave(\Magento\Sales\Api\OrderRepositoryInterface $subject, $result)
    {
        if (in_array($result->getPayment()->getMethod(), $this->ingenicoHelper->getPaymentMethodCodes())) {
            if ($this->cnf->getOrderConfirmationEmailMode() === OrderEmail::STATUS_ONCHANGE) {
                $targetStatus = $this->cnf->getOrderStatusForConfirmationEmail();
                if (!$result->getEmailSent()
                    && $result->getData('status') == $targetStatus
                    && $result->getOrigData('status') !== $targetStatus) {
                    $this->orderNotifier->notify($result);
                }
            }
        }

        return $result;
    }
}
