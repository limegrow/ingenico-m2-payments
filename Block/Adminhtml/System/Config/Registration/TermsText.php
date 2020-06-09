<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Registration;

class TermsText extends \Magento\Config\Block\System\Config\Form\Field
{

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            '<tr id="row_'.$element->getHtmlId().'">',
            '<td class="label"></td>',
            '<td class="value">',
            '<div class="message message-notice">',
            __('ingenico.settings.label23'),
            '</div>',
            '</td>',
            '<td class=""></td></tr>'
        ]);
    }
}
