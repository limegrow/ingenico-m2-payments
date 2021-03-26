<?php

namespace Ingenico\Payment\Model\Method;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;

class Ideal extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_ideal';
    const CORE_CODE = \IngenicoClient\PaymentMethod\Ideal::CODE;

    protected $_code = self::PAYMENT_METHOD_CODE;

    /**
     * @var string
     */
    protected $_formBlockType = \Ingenico\Payment\Block\Form\Ideal::class;

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
        $info->setIssuerId($additionalData->getIssuerId());

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

        // Validate field
        if (!$info->hasIssuerId()) {
            throw new CouldNotSaveException(__('Please select bank.'));
        }

        // Save Issuer ID
        $info->setAdditionalInformation('issuer_id', $info->getIssuerId());

        return $this;
    }

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @api
     */
    public function initialize($paymentAction, $stateObject)
    {
        /** @var \Magento\Quote\Model\Quote\Payment $info */
        $info = $this->getInfoInstance();
        $isserId = $info->getAdditionalInformation('issuer_id');

        if (!empty($isserId)) {
            $this->connector->log(sprintf('initialize: issuer_id: %s', $isserId));
        }

        return $this;
    }
}
