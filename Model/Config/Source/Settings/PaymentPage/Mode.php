<?php

namespace Ingenico\Payment\Model\Config\Source\Settings\PaymentPage;

class Mode implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        $out = [];
        foreach ($this->toArray() as $value => $label) {
            $out[] = ['value' => $value, 'label' => $label];
        }

        return $out;
    }

    public function toArray()
    {
        return [
            \IngenicoClient\Configuration::PAYMENT_TYPE_INLINE => __('form.payment_page.label.inline'),
            \IngenicoClient\Configuration::PAYMENT_TYPE_REDIRECT => __('form.payment_page.label.redirect')
        ];
    }
}
