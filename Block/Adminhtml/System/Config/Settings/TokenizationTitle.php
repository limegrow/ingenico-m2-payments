<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Settings;

class TokenizationTitle extends \Magento\Config\Block\System\Config\Form\Fieldset
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
            '<span class="group-hint modal-link" data-modal-id="settings_tokenization_content" data-modal-title="' .
            __('modal.tokenization.whatis') .
            '"></span></a>';
    }
}
