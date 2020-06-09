<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Connection;

class Password extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $id = $element->getHtmlId();
        return implode('', [
            parent::_getElementHtml($element),
            '<div class="copy-response message message-success" data-copy="'.$id.'">'.__('form.connection.label.copied').'</div>',
            '<div class="field-actions">',
            // value generate button
            '<button class="action-generate-signature" type="button" onclick="Ogone.generateHash(\''.$id.'\', 15);">',
            '<span>'.__('form.connection.button.generate').'</span>',
            '</button>',
            // value show/hide button
            '<button class="action-display" type="button" onclick="Ogone.toggleView(this, \''.$id.'\');">',
            '<span>'.__('form.connection.button.show').'</span>',
            '</button>',
            // value copy link
            '<a href="javascript:void(0);" onclick="Ogone.copyValue(\''.$id.'\', \''.$id.'\');">'.__("form.connection.button.copy_value").'</a>',
            '&nbsp;&nbsp;&nbsp;',
            // info modal link
            '<a href="#" class="modal-link" data-modal-id="connection_user_content" data-modal-title="'
                .__('modal.directlink.label14').'">'.__("form.connection.label.howto").'</a>',
            '</div>',
        ]);
    }
}
