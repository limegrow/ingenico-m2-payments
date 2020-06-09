<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Instalments\Flexible;

class Downpayment extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            parent::_getElementHtml($element),
            '<div class="range-bar-container">',
            '<div class="range-bar" id="'.$element->getHtmlId().'_range"></div>',
            '</div>',
            '<span>'.__('%').'</span>'
        ]);
    }
}
