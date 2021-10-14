<?php
// phpcs:ignoreFile InstallSchema scripts are obsolete. Please use declarative schema approach in module's etc/db_schema.xml file

namespace Ingenico\Payment\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * @codingStandardsIgnoreStart
 */
class InstallSchema implements InstallSchemaInterface
{
    const PARAM_NAME_TABLE_NAME_TRANSACTION = 'ingenico_payment_transaction';
    const PARAM_NAME_TABLE_NAME_REMINDER = 'ingenico_payment_reminder';
    const PARAM_NAME_TABLE_NAME_ALIAS = 'ingenico_payment_alias';
    const PARAM_NAME_NULLABLE = 'nullable';
    const PARAM_NAME_PRIMARY = 'primary';
    const PARAM_NAME_IDENTITY = 'identity';
    const PARAM_NAME_ORDER_ID = 'order_id';
    const PARAM_NAME_DEFAULT = 'default';
    const PARAM_NAME_PAY_ID = 'pay_id';
    const PARAM_NAME_SECURE_TOKEN = 'secure_token';
    const PARAM_NAME_CUSTOMER_ID = 'customer_id';
    const PARAM_NAME_ALIAS = 'alias';

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if (!$installer->tableExists(self::PARAM_NAME_TABLE_NAME_TRANSACTION)) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable(self::PARAM_NAME_TABLE_NAME_TRANSACTION))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        self::PARAM_NAME_IDENTITY => true,
                        self::PARAM_NAME_NULLABLE => false,
                        self::PARAM_NAME_PRIMARY => true
                    ],
                    'ID'
                )
                ->addColumn(
                    self::PARAM_NAME_ORDER_ID,
                    Table::TYPE_TEXT,
                    32,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Magento Order Increment ID'
                )
                ->addColumn(
                    self::PARAM_NAME_PAY_ID,
                    Table::TYPE_TEXT,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Ingenico Transaction ID'
                )
                ->addColumn(
                    'pay_id_sub',
                    Table::TYPE_INTEGER,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'The history level ID of the maintenance operation on the payment_id'
                )
                ->addColumn(
                    'status',
                    Table::TYPE_INTEGER,
                    11,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Transaction Status'
                )
                ->addColumn(
                    'pm',
                    Table::TYPE_TEXT,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Payment Method Name'
                )
                ->addColumn(
                    'brand',
                    Table::TYPE_TEXT,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Card Brand or similar information for other payment methods'
                )
                ->addColumn(
                    'card_no',
                    Table::TYPE_TEXT,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Masked credit card number'
                )
                ->addColumn(
                    'amount',
                    Table::TYPE_DECIMAL,
                    '12,4',
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Order Amount'
                )
                ->addColumn(
                    'currency',
                    Table::TYPE_TEXT,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Order Currency'
                )
                ->addColumn(
                    'transaction_data',
                    Table::TYPE_TEXT,
                    Table::DEFAULT_TEXT_SIZE,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Transaction data in JSON'
                )
                ->addColumn(
                    'trxdate',
                    Table::TYPE_TEXT,
                    Table::DEFAULT_TEXT_SIZE,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Trx date'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_DATETIME,
                    null,
                    [
                        self::PARAM_NAME_NULLABLE => true
                    ]
                )
                ->addColumn(
                    'updated_at',
                    Table::TYPE_DATETIME,
                    null,
                    [
                        self::PARAM_NAME_NULLABLE => true
                    ]
                )
                ->addIndex(
                    $installer->getIdxName(self::PARAM_NAME_TABLE_NAME_TRANSACTION, ['order_id']),
                    ['order_id'],
                    [
                        'type' => AdapterInterface::INDEX_TYPE_INDEX
                    ]
                )
                ->addIndex(
                    $installer->getIdxName(self::PARAM_NAME_TABLE_NAME_TRANSACTION, ['pay_id']),
                    ['pay_id'],
                    [
                        'type' => AdapterInterface::INDEX_TYPE_INDEX
                    ]
                )
                ->addIndex(
                    $installer->getIdxName(self::PARAM_NAME_TABLE_NAME_TRANSACTION, ['pay_id_sub']),
                    ['pay_id_sub'],
                    [
                        'type' => AdapterInterface::INDEX_TYPE_INDEX
                    ]
                )
                ->setComment(
                    'Ingenico Payment Transactions'
                );

            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists(self::PARAM_NAME_TABLE_NAME_REMINDER)) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable(self::PARAM_NAME_TABLE_NAME_REMINDER))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        self::PARAM_NAME_IDENTITY => true,
                        self::PARAM_NAME_NULLABLE => false,
                        self::PARAM_NAME_PRIMARY => true
                    ],
                    'ID'
                )
                ->addColumn(
                    self::PARAM_NAME_ORDER_ID,
                    Table::TYPE_TEXT,
                    32,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Magento Order Increment ID'
                )
                ->addColumn(
                    self::PARAM_NAME_SECURE_TOKEN,
                    Table::TYPE_TEXT,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Ingenico Reminder Unique Token'
                )
                ->addColumn(
                    'is_sent',
                    Table::TYPE_INTEGER,
                    11,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Sent Flag'
                )
                ->addColumn(
                    'sent_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Reminder Sending Date'
                )
                ->addIndex(
                    $installer->getIdxName(
                        self::PARAM_NAME_TABLE_NAME_REMINDER,
                        [
                            self::PARAM_NAME_ORDER_ID,
                            self::PARAM_NAME_SECURE_TOKEN
                        ]
                    ),
                    [
                        self::PARAM_NAME_ORDER_ID,
                        self::PARAM_NAME_SECURE_TOKEN
                    ],
                    [
                        'type' => AdapterInterface::INDEX_TYPE_UNIQUE
                    ]
                )
                ->setComment(
                    'Ingenico Payment Reminders'
                );

            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists(self::PARAM_NAME_TABLE_NAME_ALIAS)) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable(self::PARAM_NAME_TABLE_NAME_ALIAS))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        self::PARAM_NAME_IDENTITY => true,
                        self::PARAM_NAME_NULLABLE => false,
                        self::PARAM_NAME_PRIMARY => true
                    ],
                    'ID'
                )
                ->addColumn(
                    self::PARAM_NAME_CUSTOMER_ID,
                    Table::TYPE_INTEGER,
                    11,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Magento Customer ID'
                )
                ->addColumn(
                    self::PARAM_NAME_ALIAS,
                    Table::TYPE_TEXT,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ]
                )
                ->addColumn(
                    'cn',
                    Table::TYPE_TEXT,
                    255,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ],
                    'Customer name'
                )
                ->addColumn(
                    'brand',
                    Table::TYPE_TEXT,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ]
                )
                ->addColumn(
                    'cardno',
                    Table::TYPE_TEXT,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ]
                )
                ->addColumn(
                    'bin',
                    Table::TYPE_TEXT,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ]
                )
                ->addColumn(
                    'pm',
                    Table::TYPE_TEXT,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ]
                )
                ->addColumn(
                    'ed',
                    Table::TYPE_TEXT,
                    50,
                    [
                        self::PARAM_NAME_NULLABLE => true,
                        self::PARAM_NAME_DEFAULT => null
                    ]
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        self::PARAM_NAME_NULLABLE => false,
                        self::PARAM_NAME_DEFAULT => Table::TIMESTAMP_INIT
                    ]
                )
                ->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        self::PARAM_NAME_NULLABLE => false,
                        self::PARAM_NAME_DEFAULT => Table::TIMESTAMP_INIT_UPDATE
                    ]
                )
                ->addIndex(
                    $installer->getIdxName(self::PARAM_NAME_TABLE_NAME_ALIAS, [self::PARAM_NAME_ALIAS]),
                    [
                        self::PARAM_NAME_ALIAS
                    ],
                    [
                        'type' => AdapterInterface::INDEX_TYPE_UNIQUE
                    ]
                )
                ->addIndex(
                    $installer->getIdxName(self::PARAM_NAME_TABLE_NAME_ALIAS, ['customer_id']),
                    [
                        self::PARAM_NAME_CUSTOMER_ID
                    ],
                    [
                        'type' => AdapterInterface::INDEX_TYPE_INDEX
                    ]
                )
                ->setComment(
                    'Ingenico Payment Aliases'
                );

            $installer->getConnection()->createTable($table);
        }

        // Parent order ID for Multishipping Checkout
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ingenico_parent_order_id',
            [
                'type'     => Table::TYPE_INTEGER,
                'length'   => null,
                'unsigned' => true,
                'nullable' => true,
                'comment'  => 'Parent order ID for Multishipping Checkout'
            ]
        );

        // Add index (Multishipping Checkout)
        $installer->getConnection()->addIndex(
            $installer->getTable('sales_order'),
            $installer->getIdxName('sales_order', ['ingenico_parent_order_id']),
            [
                'ingenico_parent_order_id'
            ],
            AdapterInterface::INDEX_TYPE_INDEX
        );

        $installer->endSetup();
    }
}
