<?php

namespace Ingenico\Payment\Controller\Payment;

class Resume extends \Ingenico\Payment\Controller\Payment\Base
{
    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function execute()
    {
        if (!$this->getRequest()->getParam('token')) {
            return $this->resultRedirectFactory->create()->setPath('/');
        }

        $token = $this->getRequest()->getParam('token');
        $reminder = $this->_reminderFactory->create()->load($token, 'secure_token');
        if (!$reminder->getId()) {
            return $this->resultRedirectFactory->create()->setPath('/');
        }

        $url = $this->_urlBuilder->getUrl('/');
        try {
            $order = $this->_connector->getProcessor()->getOrderByIncrementId($reminder->getOrderId());
            if ($order->getStatus() !== $order->getConfig()->getStateDefaultStatus($order::STATE_PENDING_PAYMENT)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('ingenico.exception.message3', $order->getIncrementId()));
            }

            // load order and quote data to session
            $this->_session->setData([
                'reminder_order_id' => $reminder->getOrderId(),
                'last_real_order_id' => $order->getIncrementId(),
                'last_success_quote_id' => $order->getQuoteId(),
                'last_quote_id' => $order->getQuoteId(),
                'last_order_id' => $order->getId()
            ]);

            $paymentMode = strtolower($this->_cnf->getValue('ingenico_payment_page/presentation/mode'));
            $url = $this->_urlBuilder->getUrl('ingenico/payment/'.$paymentMode);
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setUrl($url);
    }
}
