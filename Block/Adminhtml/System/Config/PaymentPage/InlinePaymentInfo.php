<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\PaymentPage;

class InlinePaymentInfo extends \Magento\Config\Block\System\Config\Form\Field
{

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            '<tr id="row_'.$element->getHtmlId().'">',
            '<td class="label"></td>',
            '<td class="value">',
            '<div class="message message-notice">',
            __('form.payment_page.label.pci'),
            '</div>',
            '</td>',
            '<td class=""></td></tr>'
        ]);
    }
}
