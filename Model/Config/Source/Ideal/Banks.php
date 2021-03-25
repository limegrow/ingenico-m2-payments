<?php

namespace Ingenico\Payment\Model\Config\Source\Ideal;

use Magento\Framework\Option\ArrayInterface;

class Banks implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'ABNANL2A',
                'label' => __('ABN AMRO')
            ],
            [
                'value' => 'RABONL2U',
                'label' => __('Rabobank')
            ],
            [
                'value' => 'INGBNL2A',
                'label' => __('ING')
            ],
            [
                'value' => 'SNSBNL2A',
                'label' => __('SNS Bank')
            ],
            [
                'value' => 'RBRBNL21',
                'label' => __('Regio Bank')
            ],
            [
                'value' => 'ASNBNL21',
                'label' => __('ASN Bank')
            ],
            [
                'value' => 'BUNQNL2A',
                'label' => __('Bunq')
            ],
            [
                'value' => 'TRIONL2U',
                'label' => __('Triodos Bank')
            ],

            [
                'value' => 'FVLBNL22',
                'label' => __('van Lanschot Bankiers')
            ],
            [
                'value' => 'KNABNL2H',
                'label' => __('Knab bank')
            ],
            [
                'value' => 'MOYONL21',
                'label' => __('Moneyou')
            ],
            [
                'value' => 'HANDNL2A',
                'label' => __('Handelsbanken')
            ],
            [
                'value' => 'REVOLT21',
                'label' => __('Revolut')
            ],
            [
                'value' => '9999%2BTST',
                'label' => __('!TEST!')
            ],
        ];
    }
}
