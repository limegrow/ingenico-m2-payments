<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Instalments;

class Rules extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $linkTitle = __('What is the difference?');
        $modalTitle = __('What is the difference between flexible and fixed rules?');

        return implode('', [
            parent::_getElementHtml($element),
            '<div class="field-actions">',
            '<a href="#" class="modal-link" data-modal-id="instalments_rules_content" data-modal-title="'.$modalTitle.'">'.$linkTitle.'</a>',
            '</div>'
        ]);
    }
}
