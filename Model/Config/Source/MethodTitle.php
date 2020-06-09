<?php

namespace Ingenico\Payment\Model\Config\Source;

class MethodTitle implements \Magento\Framework\Option\ArrayInterface
{
    const INGENICO_PAYMENTS_TITLE_MODE_LOGOS = 'logos';
    const INGENICO_PAYMENTS_TITLE_MODE_NAMES = 'names';
    const INGENICO_PAYMENTS_TITLE_MODE_TEXT = 'text';

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
            self::INGENICO_PAYMENTS_TITLE_MODE_LOGOS => __('ingenico.settings.option1'),
            self::INGENICO_PAYMENTS_TITLE_MODE_NAMES => __('ingenico.settings.option2'),
            self::INGENICO_PAYMENTS_TITLE_MODE_TEXT => __('ingenico.settings.option3')
        ];
    }
}
