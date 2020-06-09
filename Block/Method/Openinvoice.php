<?php

namespace Ingenico\Payment\Block\Method;

class Openinvoice extends \Ingenico\Payment\Block\Method\View
{
    public function getRedirectPaymentData()
    {
        return $this->_registry->registry($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_REDIRECT);
    }
}
