<?php

namespace Ingenico\Payment\Controller\Payment;

class Openinvoice extends Base
{
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create()->setRefererUrl();
        try {
            // @see Connector::showPaymentListRedirectTemplate()
            // @see Connector::clarifyOpenInvoiceAdditionalFields()
            // Expect an argument like ['payment_id' => '', 'brand' => '', 'pm' => '', 'customer_dob' => ''...]
            $this->_connector->processOpenInvoiceFields($this->getRequest());
        } catch (\Exception $e) {
            /** @var \Ingenico\Payment\Logger\Main $logger */
            $logger = $this->_objectManager->get(\Ingenico\Payment\Logger\Main::class);
            $logger->err(sprintf('%s %s', __METHOD__, $e->getMessage()));

            // Suppress: Unable to use %s as Open Invoice method. Use %s::initiateRedirectPayment() instead of.
            if (strpos($e->getMessage(), 'initiateRedirectPayment()') !== false) {
                return $redirect;
            }

            $this->messageManager->addError(__($e->getMessage()));
            return $redirect;
        }

        // Transfer the Redirect vars to OpenInvoice vars.
        // See View::getOpenInvoicePaymentData()
        if ($fields = $this->_registry->registry($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_REDIRECT)) {
            $this->_registry->register($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_OPENINVOICE, $fields);

            // OpenInvoice: Unregister inline vars if exists
            //$this->_registry->unregister($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_REDIRECT);
            //$this->_registry->unregister($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_INLINE);

            return $this->resultFactory->create($this->resultFactory::TYPE_PAGE);
        }

        if ($this->_registry->registry($this->_connector::REGISTRY_KEY_REDIRECT_TO_REFERER)) {
            return $redirect;
        }
        return $this->resultFactory->create($this->resultFactory::TYPE_PAGE);
    }
}
