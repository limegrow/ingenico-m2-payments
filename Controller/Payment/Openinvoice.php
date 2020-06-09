<?php

namespace Ingenico\Payment\Controller\Payment;

class Openinvoice extends \Ingenico\Payment\Controller\Payment\Base
{
    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create()->setRefererUrl();
        try {
            $this->_connector->processOpenInvoiceFields($this->getRequest());
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            return $redirect;
        }

        if ($this->_registry->registry($this->_connector::REGISTRY_KEY_REDIRECT_TO_REFERER)) {
            return $redirect;
        }
        return $this->resultFactory->create($this->resultFactory::TYPE_PAGE);
    }
}
