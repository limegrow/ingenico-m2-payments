<?php

namespace Ingenico\Payment\Model;

use Magento\Framework\Exception\LocalizedException;

class Processor
{
    protected $_orderFactory;
    protected $_invoiceRepository;
    protected $_orderRepository;
    protected $_creditmemoManagement;
    protected $_creditmemoFactory;
    protected $_invoiceService;
    protected $_invoiceSender;
    protected $_creditmemoSender;
    protected $_transactionBuilder;
    protected $_creditmemoRepository;
    protected $_registry;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Ingenico\Payment\Model\Connector
     */
    protected $_connector;

    /**
     * Constructor
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoManagement,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender $creditmemoSender,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Framework\Registry $registry,
        \Ingenico\Payment\Model\Config $config
    ) {
        $this->_orderFactory = $orderFactory;
        $this->_invoiceRepository = $invoiceRepository;
        $this->_orderRepository = $orderRepository;
        $this->_creditmemoManagement = $creditmemoManagement;
        $this->_creditmemoFactory = $creditmemoFactory;
        $this->_creditmemoRepository = $creditmemoRepository;
        $this->_invoiceService = $invoiceService;
        $this->_invoiceSender = $invoiceSender;
        $this->_creditmemoSender = $creditmemoSender;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_registry = $registry;
        $this->config = $config;
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
    public function processOrderAuthorization($incrementId, $paymentResult, $message)
    {
        $order = $this->getOrderByIncrementId($incrementId);
        $authorizedStatus = $this->config->getOrderStatusAuth();

        // skip already authorized orders (double ping-back)
        if ($order->getStatus() == $authorizedStatus) {
            return null;
        }

        // accept only authorizations for new (pending) orders
        if ($order->getStatus() !== $order->getConfig()->getStateDefaultStatus($order::STATE_NEW)) {
            $this->_addOrderMessage($order, __('ingenico.notification.message2', $authorizedStatus, $order->getStatus()));
            return $this->_orderRepository->save($order);
        }

        // Set order status
        $new_status = $this->config->getOrderStatusAuth();
        $status = $this->config->getAssignedState($new_status);
        $order->setData('state', $status->getState());
        $order->setStatus($status->getStatus());
        $this->_addOrderMessage($order, $message, __('ingenico.notification.message3'));
        $this->_registry->register($this->_connector::REGISTRY_KEY_CAN_SEND_AUTH_EMAIL, true, true);

        return $this->_orderRepository->save($order);
    }

    /**
     * Process successful order payment (capture)
     */
    public function processOrderPayment($incrementId, $paymentResult, $message)
    {
        $order = $this->getOrderByIncrementId($incrementId);
        
        // only process if request is not from Admin Panel
        if ($this->_registry->registry('current_invoice')) {
            return $order;
        }
        
        if ($order->isCanceled()) {
            $this->_addOrderMessage($order, __('ingenico.notification.message5'));
            return $this->_orderRepository->save($order);
        }
        
        $processStatus = false;
        // check if there is an Invoice with transaction ID
        $trxId = $paymentResult->getPayId() . '-' . $paymentResult->getPayIdSub();
        if ($order->hasInvoices()) {
            foreach ($order->getInvoiceCollection() as $invoice) {
                if ($invoice->getTransactionId() == $trxId && $invoice->canCancel()) {
                    $invoice->pay();
                    $this->_invoiceRepository->save($invoice);
                    $this->_orderRepository->save($invoice->getOrder());
                    if (!$invoice->getEmailSent()) {
                        $this->_invoiceSender->send($invoice);
                    }
                    $processStatus = true;
                }
            }
            
        } else {
            try {
                $invoice = $this->_invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase($invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->getOrder()->setIsInProcess(true);
                $invoice->setIsPaid(true);
                $invoice->setTransactionId($trxId);
                $this->_invoiceRepository->save($invoice);
                $this->_invoiceSender->send($invoice);
                $processStatus = true;
                
            } catch (\Exception $e) {
                $this->_connector->log($e->getMessage(), 'crit');
            } catch (LocalizedException $e) {
                $this->_connector->log(sprintf('%s::%s %s', __CLASS__, __METHOD__, $e->getMessage()));
                throw $e;
            }
        }
        
        if ($processStatus) {
            // Set order status
            $new_status = $this->config->getOrderStatusSale();
            $status = $this->config->getAssignedState($new_status);
            $order->setData('state', $status->getState());
            $order->setStatus($status->getStatus());
            $this->_addOrderMessage($order, $message, __('ingenico.notification.message6'));
        }
        
        return $this->_orderRepository->save($order);        
    }
    
    /**
     * Deprecated from v2.2.1, use processOrderDefault()
     */
    public function processOrderCaptureProcessing($incrementId, $paymentResult, $message)
    {
        return $this->processOrderDefault($incrementId, $paymentResult, $message);
    }
    
    /**
     * Deprecated from v2.2.1, use processOrderDefault()
     */
    public function processOrderRefundProcessing($incrementId, $paymentResult, $message)
    {
        return $this->processOrderDefault($incrementId, $paymentResult, $message);
    }
    
    /**
     * Simply add record to order history, nothing else
     */
    public function processOrderDefault($incrementId, $paymentResult, $message)
    {
        $order = $this->getOrderByIncrementId($incrementId);
        $this->_addOrderMessage($order, $message);
        return $this->_orderRepository->save($order);
    }
    
    /**
     * Process successful order refund
     */
    public function processOrderRefund($incrementId, $paymentResult, $message)
    {
        $order = $this->getOrderByIncrementId($incrementId);
        
        if ($order->isCanceled()) {
            $this->_addOrderMessage($order, __('ingenico.notification.message5'));
            return $this->_orderRepository->save($order);
        }
        
        try {
            // check if there is a Credit Memo with transaction ID
            $trxId = $paymentResult->getPayId() . '-' . $paymentResult->getPayIdSub();
            if ($order->hasCreditmemos()) {
                foreach ($order->getCreditmemosCollection() as $creditMemo) {
                    if ($creditMemo->getTransactionId() == $trxId && $creditMemo->canRefund()) {
                        $creditMemo->setPaymentRefundDisallowed(true);
                        $this->_creditmemoManagement->refund($creditMemo);
                        $this->_creditmemoSender->send($creditMemo);
                    }
                }
                
            } else {
                $creditMemo = $this->_creditmemoFactory->createByOrder($order);
                $creditMemo->setTransactionId($trxId);
                $creditMemo->setPaymentRefundDisallowed(true);
                $this->_creditmemoManagement->refund($creditMemo);
                $this->_creditmemoSender->send($creditMemo);
            }
            
            $order = $this->_orderRepository->get($order->getId());
            $this->_addOrderMessage($order, $message);
        
        } catch (\Exception $e) {
            $this->_connector->log($e->getMessage(), 'crit');
        } catch (LocalizedException $e) {
            $this->_connector->log(sprintf('%s::%s %s', __CLASS__, __METHOD__, $e->getMessage()));
        }
        
        return $this->_orderRepository->save($order);        
    }

    public function processOrderCancellation($incrementId, $paymentResult, $message = null)
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
}
