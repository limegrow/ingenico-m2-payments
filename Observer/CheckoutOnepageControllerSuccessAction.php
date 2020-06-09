<?php

namespace Ingenico\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;

class CheckoutOnepageControllerSuccessAction implements ObserverInterface
{
    /**
     * @var \Ingenico\Payment\Model\Connector
     */
    private $connector;

    /**
     * @var \Ingenico\Payment\Model\Config
     */
    private $cnf;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    private $checkoutHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * Constructor
     *
     * @param \Ingenico\Payment\Model\Connector $connector
     * @param \Ingenico\Payment\Model\Config    $cnf
     * @param OrderFactory                      $orderFactory
     * @param OrderRepository                   $orderRepository
     * @param \Magento\Checkout\Helper\Data     $checkoutHelper
     * @param \Magento\Customer\Model\Session   $customerSession
     */
    public function __construct(
        \Ingenico\Payment\Model\Connector $connector,
        \Ingenico\Payment\Model\Config $cnf,
        OrderFactory $orderFactory,
        OrderRepository $orderRepository,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->connector = $connector;
        $this->cnf = $cnf;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->checkoutHelper = $checkoutHelper;
        $this->customerSession = $customerSession;
    }

    public function execute(Observer $observer)
    {
        $orderId = $this->checkoutHelper->getCheckout()->getMultishippingMainOrderId();
        if ($orderId > 0) {
            try {
                // Trigger order saving
                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->orderRepository->get($orderId);
                $order->save();
            } catch (\Exception $e) {
                //
            }
        }

        // Remove Flag for Dummy shipping
        $this->customerSession->unsIsDummyShipping();

        // Remove session value
        $this->checkoutHelper->getCheckout()->unsMultishippingMainOrderId();
    }
}
