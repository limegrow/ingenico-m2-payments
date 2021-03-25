<?php

namespace Ingenico\Payment\Model\Config\Backend\Flex;

use Magento\Framework\Exception\LocalizedException;
use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;

class Methods extends ArraySerialized
{
    /**
     * Use json encoding instead of serialisation
     *
     * @override
     * @return void
     */
    protected function _afterLoad()
    {
        if (!is_array($this->getValue())) {
            $value = $this->getValue();
            $this->setValue(empty($value) ? false : json_decode($value, true));
        }
    }

    /**
     * Additional validation for unique brands
     *
     * @override
     * @throws \Exception
     */
    public function beforeSave()
    {
        $methods = $this->getValue();
        $processedMethods = [];
        if (is_array($methods)) {
            foreach ($methods as $key => $method) {
                if ($this->validateMethod($method, $processedMethods)) {
                    $processedMethods[] = $method['pm'] . '_' . $method['brand'];
                } else {
                    unset($methods[$key]);
                };
            }
            $this->setValue(json_encode($methods));
        }

        parent::beforeSave();
    }

    /**
     * Filter out invalid methods,
     * throw LocalizedException when user should be notified.
     *
     * @param array $method
     * @param array $processedMethods
     * @return bool
     * @throws LocalizedException
     */
    private function validateMethod($method, $processedMethods)
    {
        if (!is_array($method)
            || !array_key_exists('pm', $method)
            || !array_key_exists('brand', $method)
        ) {
            return false;
        }

        if (empty($method['title']) || empty($method['pm'])) {
            throw new LocalizedException(__('Can not save empty title or PM fields'));
        }

        if (in_array($method['pm'] . '_' . $method['brand'], $processedMethods)) {
            throw new LocalizedException(__('PM and Brand combination must be unique'));
        }

        return true;
    }
}
