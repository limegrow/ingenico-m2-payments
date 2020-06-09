<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\PaymentPage\Redirect;

class StepTwo extends \Magento\Backend\Block\AbstractBlock implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            '<tr id="row_'.$element->getHtmlId().'">',
            '<td class="label"></td>',
            '<td class="value">',
            '<h4 style="padding:0;">'.$element->getLabel().'</h4>',
            '<p>',
            __('form.payment_page.label.create_own'),
            '</p>',
            '</td>',
            '<td class=""></td></tr>'
        ]);
    }
}
