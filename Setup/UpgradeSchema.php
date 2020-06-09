<?php

namespace Ingenico\Payment\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Do Upgrade Schema
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();

        if (version_compare($context->getVersion(), '0.0.2', '<')) {
            // Add Transactions column
            if ($connection->isTableExists($setup->getTable(InstallSchema::PARAM_NAME_TABLE_NAME_TRANSACTION))) {
                $connection->addColumn(
                    $setup->getTable(InstallSchema::PARAM_NAME_TABLE_NAME_TRANSACTION),
                    'transaction_data',
                    [
                        'type'     => Table::TYPE_TEXT,
                        'length'   => Table::DEFAULT_TEXT_SIZE,
                        'nullable' => true,
                        'default'  => null,
                        'comment'  => 'Transaction data in JSON'
                    ]
                );

                $connection->addColumn(
                    $setup->getTable(InstallSchema::PARAM_NAME_TABLE_NAME_TRANSACTION),
                    'trxdate',
                    [
                        'type'     => Table::TYPE_TEXT,
                        'length'   => Table::DEFAULT_TEXT_SIZE,
                        'nullable' => true,
                        'default'  => null,
                        'comment'  => 'Trx date'
                    ]
                );

                $connection->addColumn(
                    $setup->getTable(InstallSchema::PARAM_NAME_TABLE_NAME_TRANSACTION),
                    'created_at',
                    [
                        'type'     => Table::TYPE_DATETIME,
                        'nullable' => true,
                        'default'  => null,
                        'comment'  => 'Creation date'
                    ]
                );

                $connection->addColumn(
                    $setup->getTable(InstallSchema::PARAM_NAME_TABLE_NAME_TRANSACTION),
                    'updated_at',
                    [
                        'type'     => Table::TYPE_DATETIME,
                        'nullable' => true,
                        'default'  => null,
                        'comment'  => 'Update date'
                    ]
                );

                $connection->changeColumn(
                    $setup->getTable(InstallSchema::PARAM_NAME_TABLE_NAME_TRANSACTION),
                    'pay_id_sub',
                    'pay_id_sub',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => 11,
                        'comment' => 'The history level ID of the maintenance operation on the payment_id'
                    ]
                );

                // Indexes
                $connection->dropIndex(
                    InstallSchema::PARAM_NAME_TABLE_NAME_TRANSACTION,
                    $setup->getIdxName(InstallSchema::PARAM_NAME_TABLE_NAME_TRANSACTION, ['pay_id'])
                );

                $connection->addIndex(
                    InstallSchema::PARAM_NAME_TABLE_NAME_TRANSACTION,
                    $setup->getIdxName(InstallSchema::PARAM_NAME_TABLE_NAME_TRANSACTION, ['order_id']),
                    ['order_id'],
                    AdapterInterface::INDEX_TYPE_INDEX
                );

                $connection->addIndex(
                    InstallSchema::PARAM_NAME_TABLE_NAME_TRANSACTION,
                    $setup->getIdxName(InstallSchema::PARAM_NAME_TABLE_NAME_TRANSACTION, ['pay_id_sub']),
                    ['pay_id_sub'],
                    AdapterInterface::INDEX_TYPE_INDEX
                );
            }

            // Parent order ID for Multishipping Checkout
            $connection->addColumn(
                $setup->getTable('sales_order'),
                'ingenico_parent_order_id',
                [
                    'type'     => Table::TYPE_INTEGER,
                    'length'   => null,
                    'unsigned' => true,
                    'nullable' => true,
                    'comment'  => 'Parent order ID for Multishipping Checkout'
                ]
            );
        }

        $setup->endSetup();
    }
}
