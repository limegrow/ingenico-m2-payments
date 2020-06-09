<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Support\Settings;

class ImportButton extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Set template to itself
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('Ingenico_Payment::system/config/import_settings.phtml');
        }
        return $this;
    }
    
    /**
     * Unset some non-related element parameters
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
    
    /**
     * Get the button and scripts contents
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label' => $originalData['button_label'],
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('ingenico/settings/import'),
            ]
        );

        return $this->_toHtml();
    }
}
