<?php

namespace Ingenico\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use IngenicoClient\Configuration;

class Pmlist implements ArrayInterface
{
    /**
     * Prepare ops payment block layout as option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Configuration::PMLIST_HORIZONTAL_LEFT,
                'label' => __('Horizontally grouped logo with group name on left')
            ],
            [
                'value' => Configuration::PMLIST_HORIZONTAL,
                'label' => __('Horizontally grouped logo with no group name')
            ],
            [
                'value' => Configuration::PMLIST_VERTICAL,
                'label' => __('Vertical list')
            ]
        ];
    }
}
