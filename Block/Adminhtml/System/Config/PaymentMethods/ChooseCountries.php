<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\PaymentMethods;

class ChooseCountries extends \Magento\Backend\Block\AbstractBlock implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            '<tr class="system-fieldset-sub-head" id="row_'.$element->getHtmlId().'"><td colspan="5">',
            '<p id="'.$element->getHtmlId().'" style="padding:2rem 0; font-weight:700; color: grey;">',
            $element->getLabel(),
            '</p>',
            '</td></tr>'
        ]);
    }
}
