<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Instalments\Fixed;

class Split extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            parent::_getElementHtml($element),
            '<span>&nbsp;&nbsp;'.__('instalments').'</span>',
            '<style>',
            '#'.$element->getHtmlId().' {width:15%}',
            '</style>',
        ]);
    }
}
