<?php

namespace Ingenico\Payment\Plugin;

use Magento\Sales\Controller\Adminhtml\Order\Creditmemo\Save;
use Ingenico\Payment\Model\Connector;
use Ingenico\Payment\Helper\Data as IngenicoHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

class MagentoSalesControllerAdminhtmlOrderCreditmemoSave
{
    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var CreditmemoLoader
     */
    private $creditmemoLoader;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var CreditmemoSender
     */
    private $creditmemoSender;

    /**
     * @var CreditmemoManagementInterface
     */
    private $creditmemoManagement;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Connector
     */
    private $connector;

    /**
     * @var IngenicoHelper
     */
    private $ingenicoHelper;

    public function __construct(
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        CreditmemoLoader $creditmemoLoader,
        CreditmemoManagementInterface $creditmemoManagement,
        CreditmemoRepositoryInterface $creditmemoRepository,
        CreditmemoSender $creditmemoSender,
        OrderRepositoryInterface $orderRepository,
        Connector $connector,
        IngenicoHelper $ingenicoHelper,
        $data = []
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->creditmemoLoader = $creditmemoLoader;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->creditmemoSender = $creditmemoSender;
        $this->creditmemoManagement = $creditmemoManagement;
        $this->orderRepository = $orderRepository;
        $this->connector = $connector;
        $this->ingenicoHelper = $ingenicoHelper;
    }

    /**
     * Intercept Credit Memo saving and use custom processing
     */
    public function aroundExecute(Save $subject, callable $proceed)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $subject->getRequest()->getPost('creditmemo');
        $order = $this->orderRepository->get($subject->getRequest()->getParam('order_id'));

        // only intercept if order with Ingenico Payment and Online Refund
        if ((isset($data['do_offline']) && $data['do_offline']) ||
            !in_array(
                $order->getPayment()->getMethod(),
                $this->ingenicoHelper->getPaymentMethodCodes()
            )
        ) {
            return $proceed();
        }

        try {
            $this->creditmemoLoader->setOrderId($subject->getRequest()->getParam('order_id'));
            $this->creditmemoLoader->setCreditmemoId($subject->getRequest()->getParam('creditmemo_id'));
            $this->creditmemoLoader->setCreditmemo($subject->getRequest()->getParam('creditmemo'));
            $this->creditmemoLoader->setInvoiceId($subject->getRequest()->getParam('invoice_id'));
            $creditmemo = $this->creditmemoLoader->load();

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

                $payId = $this->connector->getIngenicoPayIdByOrderId($order->getIncrementId());

                // Has it been paid with "Bank transfer"?
                $result = $this->connector->getCoreLibrary()->getPaymentInfo(
                    $order->getIncrementId(),
                    $payId,
                    null
                );
                if (mb_strpos($result->getPm(), 'Bank transfer', null, 'UTF-8') !== false) {
                    throw new LocalizedException(__('modal.refund_failed.not_refundable', $result->getPm()));
                }

                $this->connector->setOrderId($order->getIncrementId());
                $result = $this->connector->getCoreLibrary()->refund(
                    $order->getIncrementId(),
                    $payId,
                    $creditmemo->getGrandTotal()
                );
                $trxId = $result->getPayId() . '-' . $result->getPayIdSub();
                $transaction = $order->getPayment()
                    ->setTransactionId($trxId)
                    ->addTransaction(Transaction::TYPE_REFUND, null, true)
                    ->setIsClosed(0)
                    ->setAdditionalInformation(Transaction::RAW_DETAILS, $result->getData())
                    ->save();

                $creditmemo->setTransactionId($trxId);

                switch ($result->getPaymentStatus()) {
                    case $this->connector->getCoreLibrary()::STATUS_REFUND_PROCESSING:
                        $creditmemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);
                        $this->creditmemoRepository->save($creditmemo);
                        $this->messageManager->addSuccessMessage(__('ingenico.notification.message16'));
                        break;

                    case $this->connector->getCoreLibrary()::STATUS_REFUNDED:
                        $creditmemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED);
                        $creditmemo->setPaymentRefundDisallowed(true);
                        $this->creditmemoManagement->refund($creditmemo);
                        if (isset($data['send_email']) && $data['send_email']) {
                            $this->creditmemoSender->send($creditmemo);
                        }
                }

                $resultRedirect->setPath('sales/order/view', ['order_id' => $creditmemo->getOrderId()]);
                return $resultRedirect;
            } else {
                $resultRedirect->setPath('noroute');
                return $resultRedirect;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\IngenicoClient\Exception $e) {
            $this->connector->log($e->getMessage(), 'crit');
            $msg = __('modal.refund_failed.label1');
            $msg .= ' '.__('modal.refund_failed.label2') . ' ' . $this->connector->getCoreLibrary()->getWhiteLabelsData()->getSupportEmail();
            $msg .= ' '.__('modal.refund_failed.label3') . ' ' . $this->connector->getCoreLibrary()->getWhiteLabelsData()->getSupportUrl();
            $this->messageManager->addErrorMessage($msg);
        }

        $resultRedirect->setPath('sales/*/new', ['_current' => true]);
        return $resultRedirect;
    }
}
