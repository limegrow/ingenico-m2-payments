<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config;

class Text extends \Magento\Backend\Block\AbstractBlock implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            '<tr id="row_'.$element->getHtmlId().'">',
            '<td class="label"></td>',
            '<td class="value">'.$element->getLabel().'</td>',
            '<td class=""></td></tr>'
        ]);
    }
}
