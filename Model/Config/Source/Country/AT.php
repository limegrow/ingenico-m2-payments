<?php

namespace Ingenico\Payment\Model\Config\Source\Country;

use Magento\Directory\Model\Config\Source\Country;

class AT extends Country
{
    const COUNTRIES = ['AT'];

    /**
     * Return options array
     *
     * @param boolean $isMultiselect
     * @param string|array $foregroundCountries
     * @return array
     */
    public function toOptionArray($isMultiselect = false, $foregroundCountries = '')
    {
        if (!$this->_options) {
            $this->_options = $this->_countryCollection
                ->addCountryIdFilter(self::COUNTRIES)
                ->loadData()->setForegroundCountries(
                    $foregroundCountries
                )->toOptionArray(
                    false
                );
        }

        $options = $this->_options;
        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => __('--Please Select--')]);
        }

        return $options;
    }
}
