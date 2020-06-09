<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Connection;

/**
 * Provides field with additional information
 */
class DirectLinkHeading extends \Magento\Config\Block\System\Config\Form\Field\Heading
{

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $label = $element->getLabel()
            .'<span class="hint" onclick="Ogone.showModal(\'connection_directlink_content\',\''.__("modal.directlink.why").'\');"></span>';

        $element->setLabel($label);

        return parent::render($element);
    }
}
