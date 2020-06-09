<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Connection;

/**
 * Provides field with additional information
 */
class Webhook extends \Magento\Config\Block\System\Config\Form\Field
{

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $id = $element->getHtmlId();
        $link = $this->_storeManager->getStore()->getBaseUrl().'ingenico/payment/webhook';
        return implode('', [
            '<a href="javascript:void(0);" onclick="Ogone.copyLink(\''.$link.'\', \''.$id.'\');">'.$link.'</a>',
            '<div class="copy-response message message-success" data-copy="'.$id.'">'.__('form.connection.label.copied').'</div>',
            '<div class="field-actions">',
            '<a href="javascript:void(0);" onclick="Ogone.copyLink(\''.$link.'\', \''.$id.'\');">'.__("form.connection.button.copy_link").'</a>',
            '&nbsp;&nbsp;&nbsp;',
            '<a href="javascript:void(0);" onclick="Ogone.showModal(\'connection_webhook_content\',\''.__("modal.webhook.howto").'\');">'.__("form.connection.label.howto").'</a>',
            '</div>'
        ]);
    }
}
