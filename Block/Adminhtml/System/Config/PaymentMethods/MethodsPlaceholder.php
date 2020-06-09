<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\PaymentMethods;

class MethodsPlaceholder extends \Magento\Backend\Block\AbstractBlock implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            '<tr class="system-fieldset-sub-head" id="row_'.$element->getHtmlId().'"><td colspan="5">',
            '<div class="message message-notice">',
            $element->getLabel(),
            '</div>',
            '</td></tr>'
        ]);
    }
}
