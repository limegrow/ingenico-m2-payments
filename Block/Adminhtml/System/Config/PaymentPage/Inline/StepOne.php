<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\PaymentPage\Inline;

class StepOne extends \Magento\Backend\Block\AbstractBlock implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            '<tr id="row_'.$element->getHtmlId().'">',
            '<td class="label"></td>',
            '<td class="value">',
            '<h4 style="padding:0;">'.$element->getLabel().'</h4>',
            '<p>',
            '<a target="_blank" href="https://epayments-support.ingenico.com/en/integration/all-sales-channels/flexcheckout/guide#customization">',
            __('form.payment_page.label.readmore'),
            '</a>',
            '</p>',
            '</td>',
            '<td class=""></td></tr>'
        ]);
    }
}
