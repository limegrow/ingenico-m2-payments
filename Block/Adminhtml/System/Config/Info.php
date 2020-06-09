<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config;

class Info extends \Magento\Backend\Block\AbstractBlock implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return sprintf(
            '<tr class="system-fieldset-sub-head" id="row_%s"><td colspan="5"><p id="%s" style="padding:2rem 0 0 0; font-weight:normal; color: grey;">%s</p></td></tr>',
            $element->getHtmlId(),
            $element->getHtmlId(),
            $element->getLabel()
        );
    }
}
