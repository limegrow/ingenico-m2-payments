<?php

namespace Ingenico\Payment\Plugin;

use Magento\Sales\Model\Order\Payment\Transaction;

class MagentoSalesControllerAdminhtmlOrderCreditmemoSave
{
    protected $_resultRedirectFactory;
    protected $_messageManager;
    protected $_creditmemoLoader;
    protected $_creditmemoRepository;
    protected $_creditmemoManagement;
    protected $_orderRepository;
    protected $_connector;
    
    public function __construct(
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader $creditmemoLoader,
        \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoManagement,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Ingenico\Payment\Model\Connector $connector,
        $data = []
    ) {
        $this->_resultRedirectFactory = $resultRedirectFactory;
        $this->_messageManager = $messageManager;
        $this->_creditmemoLoader = $creditmemoLoader;
        $this->_creditmemoRepository = $creditmemoRepository;
        $this->_creditmemoManagement = $creditmemoManagement;
        $this->_orderRepository = $orderRepository;
        $this->_connector = $connector;
    }

    /**
     * Intercept Credit Memo saving and use custom processing
     */
    public function aroundExecute(\Magento\Sales\Controller\Adminhtml\Order\Creditmemo\Save $subject, callable $proceed)
    {
        $resultRedirect = $this->_resultRedirectFactory->create();
        $data = $subject->getRequest()->getPost('creditmemo');
        $order = $this->_orderRepository->get($subject->getRequest()->getParam('order_id'));
        
        // only intercept if order with Ingenico Payment
        if ($order->getPayment()->getMethod() !== \Ingenico\Payment\Model\Method\Ingenico::PAYMENT_METHOD_CODE) {
            return $proceed();
        }
        
        try {
            $this->_creditmemoLoader->setOrderId($subject->getRequest()->getParam('order_id'));
            $this->_creditmemoLoader->setCreditmemoId($subject->getRequest()->getParam('creditmemo_id'));
            $this->_creditmemoLoader->setCreditmemo($subject->getRequest()->getParam('creditmemo'));
            $this->_creditmemoLoader->setInvoiceId($subject->getRequest()->getParam('invoice_id'));
            $creditmemo = $this->_creditmemoLoader->load();
            
            if ($creditmemo) {
                if (!$creditmemo->isValidGrandTotal()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The credit memo\'s total must be positive.')
                    );
                }

                if (!empty($data['comment_text'])) {
                    $creditmemo->addComment(
                        $data['comment_text'],
                        isset($data['comment_customer_notify']),
                        isset($data['is_visible_on_front'])
                    );

                    $creditmemo->setCustomerNote($data['comment_text']);
                    $creditmemo->setCustomerNoteNotify(isset($data['comment_customer_notify']));
                }

                if (isset($data['do_offline'])) {
                    //do not allow online refund for Refund to Store Credit
                    if (!$data['do_offline'] && !empty($data['refund_customerbalance_return_enable'])) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('Cannot create online refund for Refund to Store Credit.')
                        );
                    }
                }
                
                $this->_connector->setOrderId($order->getIncrementId());
                $payId = $this->_connector->getIngenicoPayIdByOrderId($order->getIncrementId());                
                $result = $this->_connector->getCoreLibrary()->refund($order->getIncrementId(), $payId, $creditmemo->getGrandTotal());
                $trxId = $result->getPayId() . '-' . $result->getPayIdSub();
                $transaction = $order->getPayment()
                    ->setTransactionId($trxId)
                    ->addTransaction(Transaction::TYPE_REFUND, null, true)
                    ->setIsClosed(0)
                    ->setAdditionalInformation(Transaction::RAW_DETAILS, $result->getData())
                    ->save();
                
                $creditmemo->setTransactionId($trxId);
                
                switch ($result->getPaymentStatus()) {
                    case $this->_connector->getCoreLibrary()::STATUS_REFUND_PROCESSING:
                        $creditmemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);
                        $this->_creditmemoRepository->save($creditmemo);
                        $this->_messageManager->addSuccessMessage(__('ingenico.notification.message16'));
                        break;
                        
                    case $this->_connector->getCoreLibrary()::STATUS_REFUNDED:
                        $creditMemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED);
                        $creditMemo->setPaymentRefundDisallowed(true);
                        $this->_creditmemoManagement->refund($creditMemo);
                        $this->_creditmemoSender->send($creditMemo);
                }
                
                $resultRedirect->setPath('sales/order/view', ['order_id' => $creditmemo->getOrderId()]);
                return $resultRedirect;
                
            } else {
                $resultRedirect->setPath('noroute');
                return $resultRedirect;
            }
            
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->_connector->log($e->getMessage(), 'crit');
            $msg = __('modal.refund_failed.label1');
            $msg .= ' '.__('modal.refund_failed.label2').' '.'support@ecom.ingenico.сom';
            $msg .= ' '.__('modal.refund_failed.label3').' '.'https://www.ingenico.com/support/phone';
            $this->_messageManager->addErrorMessage($msg);
        }
        
        $resultRedirect->setPath('sales/*/new', ['_current' => true]);
        return $resultRedirect;
    }
}
