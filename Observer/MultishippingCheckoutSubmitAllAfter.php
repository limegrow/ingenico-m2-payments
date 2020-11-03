<?php

namespace Ingenico\Payment\Observer;

use Ingenico\Payment\Model\Connector;
use Ingenico\Payment\Model\Config as IngenicoConfig;
use Ingenico\Payment\Helper\Data as IngenicoHelper;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteFactory;
use Magento\Customer\Model\Session as CustomerSession;

class MultishippingCheckoutSubmitAllAfter implements ObserverInterface
{
    /**
     * @var Connector
     */
    private $connector;

    /**
     * @var IngenicoConfig
     */
    private $cnf;

    /**
     * @var IngenicoHelper
     */
    private $ingenicoHelper;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var CheckoutHelper
     */
    private $checkoutHelper;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Constructor
     *
     * @param Connector $connector
     * @param IngenicoConfig $cnf
     * @param OrderFactory $orderFactory
     * @param OrderRepository $orderRepository
     * @param QuoteManagement $quoteManagement
     * @param CheckoutHelper $checkoutHelper
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Connector $connector,
        IngenicoConfig $cnf,
        IngenicoHelper $ingenicoHelper,
        OrderFactory $orderFactory,
        OrderRepository $orderRepository,
        QuoteFactory $quoteFactory,
        QuoteManagement $quoteManagement,
        CheckoutHelper $checkoutHelper,
        CustomerSession $customerSession
    ) {
        $this->connector = $connector;
        $this->cnf = $cnf;
        $this->ingenicoHelper = $ingenicoHelper;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->quoteFactory = $quoteFactory;
        $this->quoteManagement = $quoteManagement;
        $this->checkoutHelper = $checkoutHelper;
        $this->customerSession = $customerSession;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');

        $orders = (array) $observer->getData('orders');
        if (count($orders) === 0) {
            return;
        }

        // Check if we have orders paid using Ingenico
        $hasIngenicoOrders = false;
        $methodCode = null;
        foreach ($orders as $order) {
            /** @var \Magento\Sales\Model\Order $order */
            if (in_array($order->getPayment()->getMethod(), $this->ingenicoHelper->getPaymentMethodCodes())) {
                $hasIngenicoOrders = true;
                $methodCode = $order->getPayment()->getMethod();
                break;
            }
        }

        if (!$hasIngenicoOrders) {
            return;
        }

        // Create Quote
        $quote1 = $this->quoteFactory->create();
        $quote1->setStoreId($quote->getStoreId());
        $quote1->setStore($quote->getStore());
        $quote1->assignCustomer($quote->getCustomer());
        $quote1->setCurrency($quote->getCurrency());
        $quote1->setBaseCurrencyCode($quote->getBaseCurrencyCode());
        $quote1->setCurrencyCode($quote->getBaseCurrencyCode());

        // Add items
        foreach ($orders as $order) {
            /** @var \Magento\Sales\Model\Order $order */
            $items = $order->getAllItems();
            foreach ($items as $item) {
                $quote1->addProduct(
                    $item->getProduct(),
                    $item->getBuyRequest(),
                    \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_LITE
                );
            }
        }

        // Set Address to quote
        foreach ($orders as $order) {
            $quote1->getBillingAddress()->addData($order->getBillingAddress()->getData());
            $quote1->getShippingAddress()->addData($order->getShippingAddress()->getData());
            break;
        }

        $shippingAmount = 0;
        $baseShippingAmount = 0;
        $shippingTaxAmount = 0;
        $baseShippingTaxAmount = 0;

        // Set dummy shipping
        if (!$quote1->isVirtual()) {
            // Flag for Dummy shipping
            $this->customerSession->setIsDummyShipping(true);

            // Calculate shipping
            foreach ($orders as $order) {
                /** @var \Magento\Sales\Model\Order $order */
                $shippingAmount += $order->getShippingAmount();
                $baseShippingAmount += $order->getBaseShippingAmount();
                $shippingTaxAmount += $order->getShippingTaxAmount();
                $baseShippingTaxAmount += $order->getBaseShippingTaxAmount();
            }

            $shippingAddress = $quote1->getShippingAddress();
            $shippingAddress
                ->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingAmount($shippingAmount)
                ->setBaseShippingAmount($baseShippingAmount)
                ->setShippingTaxAmount($shippingTaxAmount)
                ->setBaseShippingAmount($baseShippingTaxAmount)
                ->setShippingMethod('ingenico_dummy_ingenico_dummy')
                ->setShippingDescription('');
        }

        $quote1->setInventoryProcessed(true);
        $quote1->save();

        // Set Sales Order Payment
        $quote1->getPayment()->importData(array_merge(
            $quote->getPayment()->getAdditionalInformation(),
            ['method' => $methodCode]
        ));

        // Collect Totals & Save Quote
        $quote1->collectTotals()->save();

        // Create Order
        try {
            $mainOrder = $this->quoteManagement->submit($quote1);
        } catch (\Exception $e) {
            $this->connector->log(sprintf('%s::%s %s', __CLASS__, __METHOD__, $e->getMessage()), 'crit');

            throw new LocalizedException(__('An error occurred. Please try to place the order again.'));
        }

        if (!$quote1->getIsVirtual()) {
            $mainOrder
                ->setShippingAmount($shippingAmount)
                ->setBaseShippingAmount($baseShippingAmount)
                ->setShippingTaxAmount($shippingTaxAmount)
                ->setBaseShippingTaxAmount($baseShippingTaxAmount);

            $mainOrder->setBaseTotalDue($mainOrder->getBaseTotalDue() + $baseShippingAmount + $baseShippingTaxAmount);
            $mainOrder->setTotalDue($mainOrder->getTotalDue() + $shippingAmount + $shippingTaxAmount);
            $mainOrder->setBaseGrandTotal($mainOrder->getBaseGrandTotal() + $baseShippingAmount + $baseShippingTaxAmount);
            $mainOrder->setGrandTotal($mainOrder->getGrandTotal() + $shippingAmount + $shippingTaxAmount);

            $mainOrder->getPayment()
                ->setShippingAmount($shippingAmount)
                ->setBaseShippingAmount($baseShippingAmount);
        }

        $this->checkoutHelper->getCheckout()
            ->setLastQuoteId($quote1->getId())
            ->setLastSuccessQuoteId($quote1->getId())
            ->clearHelperData();

        // add order information to the session
        $this->checkoutHelper->getCheckout()
            ->setLastOrderId($mainOrder->getId())
            ->setLastRealOrderId($mainOrder->getIncrementId())
            ->setLastOrderStatus($mainOrder->getStatus());

        // Add order notes
        $incrementOrdersIds = [];
        foreach ($orders as $childOrder) {
            $incrementId = $childOrder->getIncrementId();
            if (in_array($incrementId, $incrementOrdersIds)) {
                continue;
            }

            /** @var \Magento\Sales\Model\Order $childOrder */
            $childOrder->setIngenicoParentOrderId($mainOrder->getId());
            $childOrder->addCommentToStatusHistory(__('Multishipping checkout. Parent order: %1', $mainOrder->getIncrementId()));
            $this->orderRepository->save($childOrder);

            $incrementOrdersIds[] = $incrementId;
        }

        // Add order note
        $mainOrder->addCommentToStatusHistory(__('Multishipping checkout. Child orders: %1', join(', ', $incrementOrdersIds)));
        $this->orderRepository->save($mainOrder);

        // Flag for Dummy shipping
        $this->customerSession->unsIsDummyShipping();

        // Save data in session
        $this->checkoutHelper->getCheckout()->setMultishippingMainOrderId($mainOrder->getId());
    }
}
