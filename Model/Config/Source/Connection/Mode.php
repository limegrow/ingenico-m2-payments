<?php

namespace Ingenico\Payment\Model\Config\Source\Connection;

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
        return ['test' => __('form.header.test'), 'live' => __('form.header.live')];
    }
}
