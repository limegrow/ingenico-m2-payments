<?php

namespace Ingenico\Payment\Model\Method;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Ingenico payment method model
 */
class Ingenico extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'ingenico_e_payments';

    protected $_code = self::PAYMENT_METHOD_CODE;
    protected $_isGateway = false;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canCancelInvoice = true;
    protected $_canFetchTransactionInfo = true;
    protected $_isInitializeNeeded = true;
    protected $_canUseForMultishipping = true;

    /**
     * @var string
     */
    protected $_formBlockType = \Ingenico\Payment\Block\Form::class;

    protected $_connector;
    protected $_cnf;
    protected $_registry;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Ingenico\Payment\Model\Connector $connector,
        \Ingenico\Payment\Model\Config $cnf,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_connector = $connector;
        $this->_cnf = $cnf;
        $this->_registry = $registry;
    }

    /**
     * Retrieve payment method title
     *
     * @return string
     */
    public function getTitle()
    {
        $titleMode = $this->_cnf->getTitleMode();

        if ($titleMode == \Ingenico\Payment\Model\Config\Source\MethodTitle::INGENICO_PAYMENTS_TITLE_MODE_TEXT) {
            return parent::getTitle();
        }

        $methods = $this->_connector->getCoreLibrary()->getSelectedPaymentMethods();
        $sorted = [];
        foreach ($methods as $code => $method) {
            if (!isset($sorted[$method->getCategoryName()])) {
                $sorted[$method->getCategoryName()] = [];
            }

            $sorted[$method->getCategoryName()][$code] = $method->getName();
        }

        $titleParts = [];
        foreach ($sorted as $methods) {
            foreach ($methods as $code => $name) {
                $titleParts[] = $name;
            }
        }

        return implode(', ', $titleParts);
    }

    /**
     * Assign data to info model instance
     *
     * @param DataObject|mixed $data
     * @return $this
     * @throws LocalizedException
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
        $info->setAlias($additionalData->getAlias());

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @return $this
     * @throws LocalizedException
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

        // Save Alias
        if ($info->hasAlias()) {
            $info->setAdditionalInformation('alias', $info->getAlias());
        }

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
        $alias = $info->getAdditionalInformation('alias');

        return $this;
    }

    /**
     * Authorize
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new LocalizedException(__('ingenico.exception.message5'));
        }

        return $this;
    }

    /**
     * Capture
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new LocalizedException(__('ingenico.exception.message6'));
        }

        $payment->setAmount($amount);

        // only execute payment capture if invoice created from Admin with "online capture" mode
        if (!$this->_registry->registry('current_invoice')) {
            return $this;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        // Convert amount if currency is different
        if ($order->getBaseCurrencyCode() !== $order->getOrderCurrencyCode()) {
            $amount = round($amount * $order->getBaseToOrderRate(), 2);
        }

        try {
            $trxId = $this->_connector->getIngenicoPayIdByOrderId($order->getIncrementId());
            $this->_connector->setOrderId($order->getIncrementId());
            $result = $this->_connector->getCoreLibrary()->capture($order->getIncrementId(), $trxId, $amount);

            $payment->setStatus(self::STATUS_APPROVED)
                ->setTransactionId($result->getPayId() . '-' . $result->getPayIdSub())
                ->setIsTransactionClosed(0)
                ->setAdditionalInformation(Transaction::RAW_DETAILS, $result->getData());
        } catch (\Exception $e) {
            $this->_connector->log($e->getMessage(), 'crit');

            throw new LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new LocalizedException(__('ingenico.exception.message7'));
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        // Convert amount if currency is different
        if ($order->getBaseCurrencyCode() !== $order->getOrderCurrencyCode()) {
            $amount = round($amount * $order->getBaseToOrderRate(), 2);
        }

        try {
            $trxId = $this->_connector->getIngenicoPayIdByOrderId($order->getIncrementId());
            $this->_connector->setOrderId($order->getIncrementId());
            $result = $this->_connector->getCoreLibrary()->refund($order->getIncrementId(), $trxId, $amount);

            // Add Credit Transaction
            $payment->setAnetTransType(Transaction::TYPE_REFUND)
                ->setAmount($amount)
                ->setStatus(self::STATUS_APPROVED)
                ->setTransactionId($result->getPayId() . '-' . $result->getPayIdSub())
                ->setIsTransactionClosed(0)
                ->setAdditionalInformation(Transaction::RAW_DETAILS, $result->getData());
        } catch (\Exception $e) {
            $this->_connector->log($e->getMessage(), 'crit');

            $msg = __('modal.refund_failed.label1');
            $msg .= ' '.__('modal.refund_failed.label2').' '.'support@ecom.ingenico.сom';
            $msg .= ' '.__('modal.refund_failed.label3').' '.'https://www.ingenico.com/support/phone';

            throw new LocalizedException(__($msg));
        }

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        try {
            $trxId = $this->_connector->getIngenicoPayIdByOrderId($order->getIncrementId());
            $this->_connector->setOrderId($order->getIncrementId());
            $result = $this->_connector->getCoreLibrary()->cancel($order->getIncrementId(), $trxId, null);

            // Add Cancel Transaction
            $payment->setStatus(self::STATUS_DECLINED)
                    ->setTransactionId($result->getPayId() . '-' . $result->getPayIdSub())
                    ->setIsTransactionClosed(1)
                    ->setAdditionalInformation(Transaction::RAW_DETAILS, $result);
        } catch (\Exception $e) {
            $this->_connector->log($e->getMessage(), 'crit');

            throw new LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * Fetch transaction info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetchTransactionInfo(\Magento\Payment\Model\InfoInterface $payment, $transactionId)
    {
        $trxData = $this->_connector->getIngenicoPaymentById($transactionId);
        if (!$trxData) {
            throw new LocalizedException(__('Unable to retrieve data.'));
        }

        $data = $this->_connector->getCoreLibrary()->getPaymentInfo($trxData['order_id'], $transactionId);
        if (!$data->isTransactionSuccessful()) {
            throw new LocalizedException(__('Unable to retrieve online data.'));
        }

        return $data->getData();
    }
}
