<?php

namespace Ingenico\Payment\Model;

use Magento\Framework\Exception\LocalizedException;

class Processor
{
    protected $_orderFactory;
    protected $_invoiceRepository;
    protected $_orderRepository;
    protected $_invoiceService;
    protected $_invoiceSender;
    protected $_transactionBuilder;
    protected $_registry;

    /**
     * @var \Ingenico\Payment\Model\Connector
     */
    protected $_connector;

    protected $_createTransactions = false;

    /**
     * Constructor
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\Registry $registry
    ) {
        $this->_orderFactory = $orderFactory;
        $this->_invoiceRepository = $invoiceRepository;
        $this->_orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_invoiceSender = $invoiceSender;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_registry = $registry;
    }

    public function setConnector(\Ingenico\Payment\Model\Connector $connector)
    {
        $this->_connector = $connector;
        return $this;
    }

    /**
     * Load order object using increment_id
     *
     * @param $incrementId
     *
     * @return \Magento\Sales\Model\Order
     * @throws LocalizedException
     */
    public function getOrderByIncrementId($incrementId)
    {
        if (!$incrementId) {
            throw new \Magento\Framework\Exception\LocalizedException(__('ingenico.exception.message8'));
        }
        $order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        if (!$order->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('ingenico.exception.message9', $incrementId));
        }

        return $order;
    }

    /**
     * Process successful order payment (capture)
     */
    public function processOrderAuthorization($incrementId, $message)
    {
        $order = $this->getOrderByIncrementId($incrementId);
        $authorizedStatus = $order->getConfig()->getStateDefaultStatus($order::STATE_PENDING_PAYMENT);

        // skip already authorized orders (double ping-back)
        if ($order->getStatus() == $authorizedStatus) {
            return null;
        }

        // accept only authorizations for new (pending) orders
        if ($order->getStatus() !== $order->getConfig()->getStateDefaultStatus($order::STATE_NEW)) {
            $this->_addOrderMessage($order, __('ingenico.notification.message2', $authorizedStatus, $order->getStatus()));
            return $this->_orderRepository->save($order);
        }

        // create transaction
        $trx = $this->_connector->getIngenicoPaymentLog($incrementId);
        $this->_createTransaction($order, $trx->getPayId() . '-' . $trx->getPayIdSub(), \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH);

        $order
            ->setState($order::STATE_PENDING_PAYMENT)
            ->setStatus($order->getConfig()->getStateDefaultStatus($order::STATE_PENDING_PAYMENT));

        $this->_addOrderMessage($order, $message, __('ingenico.notification.message3'));

        return $this->_orderRepository->save($order);
    }

    /**
     * Process successful order payment (capture)
     */
    public function processOrderPayment($incrementId, $message)
    {
        $order = $this->getOrderByIncrementId($incrementId);
        if ($order->hasInvoices()) {
            $this->_addOrderMessage($order, __('ingenico.notification.message4'));
            return $this->_orderRepository->save($order);
        }

        if ($order->isCanceled()) {
            $this->_addOrderMessage($order, __('ingenico.notification.message5'));
            return $this->_orderRepository->save($order);
        }

        // only create invoice if initiated by gateway, not from Admin Panel
        if (!$this->_registry->registry('current_invoice')) {
            try {
                $invoice = $this->_invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase($invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->getOrder()->setIsInProcess(true);
                $invoice->setIsPaid(true);
                $this->_invoiceRepository->save($invoice);
            } catch (LocalizedException $e) {
                $this->_connector->log(sprintf('%s::%s %s', __CLASS__, __METHOD__, $e->getMessage()));

                throw $e;
            }

            try {
                $this->_invoiceSender->send($invoice);
            } catch (\Exception $e) {
                $this->_connector->log($e->getMessage(), 'crit');
            }

            // create transaction
            $trx = $this->_connector->getIngenicoPaymentLog($incrementId);
            $this->_createTransaction($order, $trx->getPayId() . '-' . $trx->getPayIdSub(), \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

            $invoice->setTransactionId($trx->getPayId() . '-' . $trx->getPayIdSub());
            $this->_invoiceRepository->save($invoice);

            // register order status
            $order
                ->setState($order::STATE_PROCESSING)
                ->setStatus($order->getConfig()->getStateDefaultStatus($order::STATE_PROCESSING));
        }

        $this->_addOrderMessage($order, $message, __('ingenico.notification.message6'));

        return $this->_orderRepository->save($order);
    }

    public function processOrderCancellation($incrementId, $message = null)
    {
        $order = $this->getOrderByIncrementId($incrementId);

        if (!$order->canCancel()) {
            $this->_addOrderMessage($order, __('ingenico.notification.message7', $message));
            return $this->_orderRepository->save($order);
        }

        $order->cancel();

        $this->_addOrderMessage($order, $message, __('ingenico.notification.message8'));

        return $this->_orderRepository->save($order);
    }

    protected function _addOrderMessage($order, $message, $fallbackMsg = null)
    {
        $order->addStatusToHistory(
            $order->getStatus(),
            $message ? $message : $fallbackMsg
        );
    }

    protected function _createTransaction($order, $trxId, $type)
    {
        if (!$this->_createTransactions) {
            return;
        }

        try {
            $payment = $order->getPayment()
                ->setLastTransId($trxId)
                ->setTransactionId($trxId)
                ->setParentTransactionId($trxId)
                ;

            //get the object of builder class
            $transaction = $this->_transactionBuilder
                ->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($trxId)
                ->setFailSafe(true)
                ->build($type);

            $payment->save();
            $transaction->save();
        } catch (\Exception $e) {
            $this->_connector->log($e->getMessage(), 'crit');
        }
    }
}
