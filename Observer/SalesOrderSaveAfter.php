<?php

namespace Ingenico\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class SalesOrderSaveAfter implements ObserverInterface
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var CollectionFactory
     */
    private $salesOrderCollectionFactory;

    /**
     * Constructor
     *
     * @param OrderFactory      $orderFactory
     * @param OrderRepository   $orderRepository
     * @param CollectionFactory $salesOrderCollectionFactory
     */
    public function __construct(
        OrderFactory $orderFactory,
        OrderRepository $orderRepository,
        CollectionFactory $salesOrderCollectionFactory
    ) {
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->salesOrderCollectionFactory = $salesOrderCollectionFactory;
    }

    /**
     * @param Observer $observer
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getData('order');
        if (!$order) {
            return;
        }

        // Multishipping: Sync statuses
        $relatedOrders = $this->getSalesOrderCollection(['ingenico_parent_order_id' => $order->getId()]);
        foreach ($relatedOrders as $relatedOrder) {
            try {
                /** @var \Magento\Sales\Model\Order $relatedOrder */
                if ($relatedOrder->getState() !== $order->getState() ||
                    $relatedOrder->getStatus() !== $order->getStatus()
                ) {
                    $relatedOrder
                        ->setState($order->getState())
                        ->setStatus($order->getStatus())
                        ->addCommentToStatusHistory(__('ingenico.notification.message15'), $order->getStatus());

                    $this->orderRepository->save($relatedOrder);
                }
            } catch (\Exception $e) {
                $this->orderRepository->save($relatedOrder);
            }
        }
    }

    /**
     * Get sales Order collection model populated with data
     *
     * @param array $filters
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected function getSalesOrderCollection(array $filters = [])
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $salesOrderCollection */
        $salesOrderCollection = $this->salesOrderCollectionFactory->create();
        foreach ($filters as $field => $condition) {
            $salesOrderCollection->addFieldToFilter($field, $condition);
        }
        return $salesOrderCollection->load();
    }
}
