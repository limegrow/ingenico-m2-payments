<?php

namespace Ingenico\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class CcLogos implements ArrayInterface
{
    const LOGO_GENERIC = 'generic';
    const LOGO_VISA = \IngenicoClient\PaymentMethod\Visa::CODE;
    const LOGO_MC = \IngenicoClient\PaymentMethod\Mastercard::CODE;
    const LOGO_MAESTRO = \IngenicoClient\PaymentMethod\Maestro::CODE;
    const LOGO_JCB = \IngenicoClient\PaymentMethod\Jcb::CODE;
    const LOGO_AMEX = \IngenicoClient\PaymentMethod\Amex::CODE;
    const LOGO_DINERS = \IngenicoClient\PaymentMethod\DinersClub::CODE;
    const LOGO_DISCOVER = \IngenicoClient\PaymentMethod\Discover::CODE;

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::LOGO_GENERIC,
                'label' => __('Generic logo')
            ],
            [
                'value' => self::LOGO_VISA,
                'label' => __('VISA')
            ],
            [
                'value' => self::LOGO_MC,
                'label' => __('MasterCard')
            ],
            [
                'value' => self::LOGO_MAESTRO,
                'label' => __('Maestro')
            ],
            [
                'value' => self::LOGO_JCB,
                'label' => __('JCB')
            ],
            [
                'value' => self::LOGO_AMEX,
                'label' => __('Amex')
            ],
            [
                'value' => self::LOGO_DINERS,
                'label' => __('Diners Club')
            ],
            [
                'value' => self::LOGO_DISCOVER,
                'label' => __('Discover')
            ],
        ];
    }
}
