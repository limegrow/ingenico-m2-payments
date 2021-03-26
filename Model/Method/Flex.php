<?php

namespace Ingenico\Payment\Model\Method;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;

class Flex extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_flex';
    const CORE_CODE = \IngenicoClient\PaymentMethod\BankTransfer::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;

    /**
     * @var string
     */
    protected $_formBlockType = \Ingenico\Payment\Block\Form\Flex::class;

    /**
     * Assign data to info model instance
     *
     * @param DataObject|mixed $data
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(DataObject $data)
    {
        if (!$data instanceof DataObject) {
            $data = new DataObject($data);
        }

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_object($additionalData)) {
            $additionalData = new DataObject($additionalData ?: []);
        }

        /** @var \Magento\Quote\Model\Quote\Payment $info */
        $info = $this->getInfoInstance();

        $flex = $additionalData->getFlex();
        if ($flex) {
            $flex = explode(':', $flex);
            $additionalData->setFlexPm($flex[0]);
            $additionalData->setFlexBrand($flex[1]);
        }

        $info->setFlexTitle($additionalData->getFlexTitle());
        $info->setFlexPm($additionalData->getFlexPm());
        $info->setFlexBrand($additionalData->getFlexBrand());

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function validate()
    {
        parent::validate();

        /** @var \Magento\Quote\Model\Quote\Payment $info */
        $info = $this->getInfoInstance();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $info->getQuote();

        if (!$quote) {
            return $this;
        }

        // Save data
        $info->setAdditionalInformation('flex_title', $info->getFlexTitle());
        $info->setAdditionalInformation('flex_pm', $info->getFlexPm());
        $info->setAdditionalInformation('flex_brand', $info->getFlexBrand());

        return $this;
    }
}
