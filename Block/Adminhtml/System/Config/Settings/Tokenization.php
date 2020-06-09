<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Settings;

class Tokenization extends \Magento\Config\Block\System\Config\Form\Field
{

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            '<tr id="row_'.$element->getHtmlId().'">',
            '<td class="label"></td>',
            '<td class="value">',
            '<div class="message message-notice">',
            __('form.settings.label.disclaimer'),
            '<br/><a href="javascript:void(0);">'.__('form.settings.label.readmore').'</a>',
            '</div>',
            '</td>',
            '<td class=""></td></tr>'
        ]);
    }
}
