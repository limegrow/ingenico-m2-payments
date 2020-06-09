<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\PaymentMethods;

class MethodsTitle extends \Magento\Config\Block\System\Config\Form\Fieldset
{

    protected function _getHeaderTitleHtml($element)
    {
        return '<a id="' .
            $element->getHtmlId() .
            '-head" href="#' .
            $element->getHtmlId() .
            '-link" onclick="Fieldset.toggleCollapse(\'' .
            $element->getHtmlId() .
            '\', \'' .
            $this->getUrl(
                '*/*/state'
            ) . '\'); return false;">' .
            $element->getLegend() .
            '<span class="group-hint modal-link" data-modal-id="payment_methods_title_content" data-modal-title="' .
            __('modal.payment-methods-modal.title') .
            '"></span></a>';
    }
}
