<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Settings;

/**
 * Provides field with additional information
 */
class PaymentReminderEmailHeading extends \Magento\Config\Block\System\Config\Form\Field\Heading
{

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $label = $element->getLabel()
            .'<span class="hint" onclick="Ogone.showModal(\'settings_paymentreminderemail_content\',\''.__("modal.reminder.whatis").'\');"></span>';

        $element->setLabel($label);

        return parent::render($element);
    }
}
