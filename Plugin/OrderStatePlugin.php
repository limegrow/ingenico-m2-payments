<?php

namespace Ingenico\Payment\Plugin;

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

    public function __construct(
        OrderNotifier $orderNotifier,
        Config $cnf
    ) {
        $this->orderNotifier = $orderNotifier;
        $this->cnf           = $cnf;
    }

    /**
     * Interceptor Function for Sending Order Email
     */
    public function afterSave(\Magento\Sales\Api\OrderRepositoryInterface $subject, $result)
    {
        if ($result->getPayment()->getMethod() == \Ingenico\Payment\Model\Method\Ingenico::PAYMENT_METHOD_CODE) {
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
