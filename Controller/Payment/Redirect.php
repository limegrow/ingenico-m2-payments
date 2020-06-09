<?php

namespace Ingenico\Payment\Controller\Payment;

class Redirect extends \Ingenico\Payment\Controller\Payment\Base
{
    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function execute()
    {
        try {
            $this->_processPayment();
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        // redirect to success page if payment processing is final
        if ($redirectUrl = $this->_registry->registry($this->_connector::REGISTRY_KEY_REDIRECT_URL)) {
            return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
        }

        return $this->resultFactory->create($this->resultFactory::TYPE_PAGE);
    }
}
