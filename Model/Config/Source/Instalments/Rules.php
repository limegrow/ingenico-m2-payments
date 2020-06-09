<?php

namespace Ingenico\Payment\Model\Config\Source\Instalments;

class Rules implements \Magento\Framework\Option\ArrayInterface
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
            \IngenicoClient\Configuration::INSTALMENTS_TYPE_FIXED => __('Fixed rules for all clients'),
            \IngenicoClient\Configuration::INSTALMENTS_TYPE_FLEXIBLE => __('Flexible rules')
        ];
    }
}
