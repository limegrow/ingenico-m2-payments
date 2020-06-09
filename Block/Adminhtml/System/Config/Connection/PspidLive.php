<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Connection;

class PspidLive extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            parent::_getElementHtml($element),
            '<div class="field-actions">',
            '<a href="#" class="modal-link" data-modal-id="connection_pspid_live_content" data-modal-title="'.__('modal.psp.whatis').'">'.__('form.connection.label.where').'</a>',
            '</div>',
        ]);
    }
}
