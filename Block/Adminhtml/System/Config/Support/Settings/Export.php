<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Support\Settings;

/**
 * Provides field with additional information
 */
class Export extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_connector;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Ingenico\Payment\Model\Connector $connector,
        array $data = []
    ) {
        $this->_connector = $connector;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $id = $element->getHtmlId();
        $link = $this->_connector->getUrl('ingenico/settings/export', [\Ingenico\Payment\Model\Connector::CNF_SCOPE_PARAM_NAME => 0]);
        return implode('', [
            '<a href="' . $link . '">' . __('form.support.download_settings') . '</a>',
        ]);
    }
}
