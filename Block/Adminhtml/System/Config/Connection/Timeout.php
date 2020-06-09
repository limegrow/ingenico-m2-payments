<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Connection;

class Timeout extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            parent::_getElementHtml($element),
            '<span>&nbsp;&nbsp;'.__('form.connection.label.seconds').'</span>',
            '<style>',
            '#'.$element->getHtmlId().' {width:15%}',
            '</style>'
        ]);
    }
}
