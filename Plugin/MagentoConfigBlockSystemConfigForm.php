<?php

namespace Ingenico\Payment\Plugin;

class MagentoConfigBlockSystemConfigForm
{
    protected $_authSession;

    public function __construct(
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        $this->_authSession = $authSession;
    }

    /**
     * Interceptor Function for Adding Email Value
     */
    public function aroundGetConfigValue(\Magento\Config\Block\System\Config\Form $subject, callable $proceed, $path)
    {
        if ($path == 'ingenico_support/config_assistance/email') {
            return $this->_authSession->getUser()->getEmail();
        }

        return $proceed($path);
    }
}
