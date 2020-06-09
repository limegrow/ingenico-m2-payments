<?php

namespace Ingenico\Payment\Model\Config\Source\Settings;

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
        return ['basic' => __('form.settings.mode.basic'), 'advanced' => __('form.settings.mode.advanced')];
    }
}
