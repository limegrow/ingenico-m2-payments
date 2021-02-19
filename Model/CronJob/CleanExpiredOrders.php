<?php

namespace Ingenico\Payment\Model\CronJob;

use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory as PaymentCollectionFactory;
use Magento\Store\Model\StoresConfig;
use Magento\Sales\Model\Order;
use Ingenico\Payment\Logger\Main as IngenicoLogger;
use Ingenico\Payment\Model\Config as IngenicoConfig;

/**
 * Class that provides functionality of cleaning expired orders by cron
 */
class CleanExpiredOrders
{
    /**
     * @var StoresConfig
     */
    private $storesConfig;

    /**
     * @var IngenicoConfig
     */
    private $ingenicoConfig;

    /**
     * @var IngenicoLogger
     */
    private $logger;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var PaymentCollectionFactory
     */
    private $paymentCollectionFactory;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @param StoresConfig $storesConfig
     * @param IngenicoLogger $logger
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param PaymentCollectionFactory $paymentCollectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderManagementInterface|null $orderManagement
     */
    public function __construct(
        StoresConfig $storesConfig,
        IngenicoConfig $ingenicoConfig,
        IngenicoLogger $logger,
        OrderCollectionFactory $orderCollectionFactory,
        PaymentCollectionFactory $paymentCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        OrderManagementInterface $orderManagement = null
    ) {
        $this->storesConfig = $storesConfig;
        $this->ingenicoConfig = $ingenicoConfig;
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->orderManagement = $orderManagement ?: ObjectManager::getInstance()->get(OrderManagementInterface::class);
    }

    /**
     * Clean expired quotes (cron process)
     *
     * @return void
     */
    public function execute()
    {
        $lifetimes = $this->storesConfig->getStoresConfigByPath('sales/orders/delete_pending_after');

        // Check orders which were paid with Ingenico
        foreach ($lifetimes as $storeId => $lifetime) {
            // Ingenico statuses
            $authorizedStatus = $this->ingenicoConfig->getValue(
                IngenicoConfig::XML_PATH_ORDER_STATUS_AUTHORIZED,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );

            $capturedStatus = $this->ingenicoConfig->getValue(
                IngenicoConfig::XML_PATH_ORDER_STATUS_CAPTURED,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );

            /** @var $orders \Magento\Sales\Model\ResourceModel\Order\Collection */
            $orders = $this->orderCollectionFactory->create();
            $orders->addFieldToFilter('store_id', $storeId);
            $orders->getSelect()->where(
                new \Zend_Db_Expr(
                    'TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `updated_at`)) >= ' . $lifetime * 60
                )
            );
            $orders->getSelect()->joinLeft(
                ['payment_table' => $orders->getTable('sales_order_payment')],
                'main_table.entity_id=payment_table.parent_id',
                ['method']
            );
            $orders->addAttributeToFilter('payment_table.method', ['like' => 'ingenico_%']);

            // Check orders
            foreach ($orders->getAllIds() as $entityId) {
                $paymentMethod = $this->getOrderPayment($entityId)->getMethod();

                // Check Ingenico statuses to prevent refunds or capture issues
                // Get status which represent "Pending payment" status
                $newOrderStatus = $this->ingenicoConfig->getValue(
                    'payment/' . $paymentMethod . '/order_status',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                );

                // Skip the cancellation if "Pending payment" is used for "authorized" or "captured" payments
                if ($authorizedStatus === $newOrderStatus ||
                    $capturedStatus === $newOrderStatus
                ) {
                    $this->logger->info(
                        sprintf('CleanExpiredOrders: Unable to cancel #%s.', $entityId)
                    );

                    continue;
                }

                // Check if "Pending payment / New order" status
                $order = $this->orderRepository->get($entityId);
                if ($order->getStatus() === $newOrderStatus) {
                    try {
                        $this->logger->info(
                            sprintf('CleanExpiredOrders: Cancel #%s.', $entityId)
                        );

                        $this->orderManagement->cancel((int) $entityId);
                    } catch (\Exception $e) {
                        $this->logger->info(
                            sprintf('CleanExpiredOrders: Failed to cancel #%s. %s', $entityId, $e->getMessage())
                        );
                    }
                }
            }
        }

        // Check orders which were paid with non-Ingenico
        foreach ($lifetimes as $storeId => $lifetime) {
            /** @var $orders \Magento\Sales\Model\ResourceModel\Order\Collection */
            $orders = $this->orderCollectionFactory->create();
            $orders->addFieldToFilter('store_id', $storeId);
            $orders->addFieldToFilter('status', Order::STATE_PENDING_PAYMENT);
            $orders->getSelect()->where(
                new \Zend_Db_Expr(
                    'TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `updated_at`)) >= ' . $lifetime * 60
                )
            );

            // Check orders
            foreach ($orders->getAllIds() as $entityId) {
                $payment = $this->getOrderPayment($entityId);
                if ($payment && (strpos($payment->getMethod(), 'ingenico') !== false)) {
                    continue;
                }

                try {
                    $this->logger->info(
                        sprintf('CleanExpiredOrders: Cancel #%s.', $entityId)
                    );

                    $this->orderManagement->cancel((int) $entityId);
                } catch (\Exception $e) {
                    $this->logger->info(
                        sprintf('CleanExpiredOrders: Failed to cancel #%s. %s', $entityId, $e->getMessage())
                    );
                }
            }
        }
    }

    /**
     * Gets order payment.
     *
     * @param $orderId
     *
     * @return OrderPaymentInterface|null
     */
    private function getOrderPayment($orderId)
    {
        $collection = $this->paymentCollectionFactory->create()->setOrderFilter($orderId);
        if ($collection) {
            /** @var OrderPaymentInterface $payment */
            return $collection->getFirstItem();
        }

        return null;
    }
}
