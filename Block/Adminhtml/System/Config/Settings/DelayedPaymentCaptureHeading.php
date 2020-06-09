<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Settings;

/**
 * Provides field with additional information
 */
class DelayedPaymentCaptureHeading extends \Magento\Config\Block\System\Config\Form\Field\Heading
{

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $label = $element->getLabel()
            .'<span class="hint" onclick="Ogone.showModal(\'settings_delayedpaymentcapture_content\',\''.__("modal.delayed_payment.whatis").'\');"></span>';

        $element->setLabel($label);

        return parent::render($element);
    }
}
