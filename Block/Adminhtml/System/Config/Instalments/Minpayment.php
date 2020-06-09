<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Instalments;

class Minpayment extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            parent::_getElementHtml($element),
            '<span>&nbsp;&nbsp;'.__('in base currency').'</span>',
            '<style>',
            '#'.$element->getHtmlId().' {width:15%}',
            '</style>'
        ]);
    }
}
