<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\PaymentPage;

class OptionalCustomisation extends \Magento\Config\Block\System\Config\Form\Fieldset
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
            '<span class="group-hint modal-link" data-modal-id="payment_optional_customisation_content" data-modal-title="' .
            __('modal.optional-customisation.title') .
            '"></span></a>';
    }
}
