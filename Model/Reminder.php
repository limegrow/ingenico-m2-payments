<?php

namespace Ingenico\Payment\Model;

class Reminder extends \Magento\Framework\Model\AbstractModel
{
    const CACHE_TAG = 'ingenico_payment_reminder';
    const PARAM_NAME_ORDER_ID = 'order_id';

    protected $_mathRandom;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\Math\Random $mathRandom,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_mathRandom = $mathRandom;
    }

    protected function _construct()
    {
        $this->_init(\Ingenico\Payment\Model\ResourceModel\Reminder::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function register($order)
    {
        $this->load($order->getIncrementId(), self::PARAM_NAME_ORDER_ID);
        $this->addData([
            self::PARAM_NAME_ORDER_ID => $order->getIncrementId(),
            'is_sent' => 0,
            'secure_token' => $this->_mathRandom->getUniqueHash()
        ])->save();
    }

    public function markAsSent($orderId)
    {
        $this->load($orderId, self::PARAM_NAME_ORDER_ID);
        if ($this->getId()) {
            $this->addData([
                'is_sent' => 1,
                'sent_at' => time()
            ])->save();
        }
    }
}
