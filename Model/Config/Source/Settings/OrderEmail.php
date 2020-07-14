<?php

namespace Ingenico\Payment\Model\Config\Source\Settings;

class OrderEmail implements \Magento\Framework\Option\ArrayInterface
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
        return ['0' => __('No'), '1' => __('Yes'), '3' => __('ingenico.settings.label24')];
    }
}
