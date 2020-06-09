<?php

namespace Ingenico\Payment\Model\Config\Source\Settings\PaymentPage;

class TemplateSource implements \Magento\Framework\Option\ArrayInterface
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
            \IngenicoClient\Configuration::PAYMENT_PAGE_TEMPLATE_INGENICO => __('form.payment_page.label.ingenico'),
            \IngenicoClient\Configuration::PAYMENT_PAGE_TEMPLATE_STORE => __('form.payment_page.label.upload'),
            \IngenicoClient\Configuration::PAYMENT_PAGE_TEMPLATE_EXTERNAL => __('form.payment_page.label.point_your'),
        ];
    }
}
