<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\PaymentMethods;

class PayPalTitle extends \Magento\Config\Block\System\Config\Form\Fieldset
{   
    protected $_config;
    
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Ingenico\Payment\Model\Config $config,
        array $data = []
    ) {
        $this->_config = $config;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }
    
    protected function _getHeaderTitleHtml($element)
    {
        $modalTitle = $this->_config->getMode() == 'test' ? __('modal.paypal_test.label1') : __('modal.paypal.howto');
        return '<a id="' .
            $element->getHtmlId() .
            '-head" href="#' .
            $element->getHtmlId() .
            '-link" onclick="Fieldset.toggleCollapse(\'' .
            $element->getHtmlId() .
            '\', \'' .
            $this->getUrl(
                '*/*/state'
            ) . '\'); return false;">' . 
            $element->getLegend() . 
            '<span class="group-hint modal-link" data-modal-id="paypal_title_content_' . $this->_config->getMode() . '" data-modal-title="' . 
            $modalTitle .
            '"></span></a>';
    }
}