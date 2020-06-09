<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\PaymentPage\Redirect;

class StepOne extends \Magento\Backend\Block\AbstractBlock implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $url = 'https://payment-services.ingenico.com/int/en/ogone/support/guides/integration%20guides/e-commerce/payment-page-look-and-feel#adapt-upload-customized-template';
        return implode('', [
            '<tr id="row_'.$element->getHtmlId().'">',
            '<td class="label"></td>',
            '<td class="value">',
            '<h4 style="padding:0;">'.$element->getLabel().'</h4>',
            '<p>',
            '<a target="_blank" href="'.$url.'">'.__('form.payment_page.label.readmore').'</a>',
            '</p>',
            '</td>',
            '<td class=""></td></tr>'
        ]);
    }
}
