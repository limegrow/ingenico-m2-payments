<?php

namespace Ingenico\Payment\Model\Config\Source;

class Country implements \Magento\Framework\Option\ArrayInterface
{

    protected $_countryCollection;
    protected $_connector;

    protected $_options;

    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\Collection $countryCollection,
        \Ingenico\Payment\Model\Connector $connector
    ) {
        $this->_countryCollection = $countryCollection;
        $this->_connector = $connector;
    }

    public function toOptionArray($isMultiselect = false, $foregroundCountries = '')
    {
        $limitTo = array_keys($this->_connector->getAllCountries());

        // Remove countries
        $limitTo = array_flip($limitTo);
        unset($limitTo['SE'], $limitTo['FI'], $limitTo['DK'], $limitTo['NO'], $limitTo['CN']);
        $limitTo = array_flip($limitTo);

        if (!$this->_options) {
            $this->_options = $this->_countryCollection
                ->addCountryIdFilter($limitTo)
                ->loadData()
                ->setForegroundCountries($foregroundCountries)
                ->toOptionArray(false)
                ;
        }

        $options = $this->_options;
        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => __('ingenico.settings.label4')]);
        }

        return $options;
    }
}
