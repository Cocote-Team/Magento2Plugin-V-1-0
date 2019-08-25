<?php
namespace Cocote\Feed\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '2.0.1') < 0) {
            if (!$installer->tableExists('cocote_token')) {
                $table = $installer->getConnection()->newTable('cocote_token')
                    ->addColumn(
                        'id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'nullable' => false,
                            'primary' => true,
                            'unsigned' => true,
                        ],
                        'ID'
                    )
                    ->addColumn(
                        'order_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        0,
                        [],
                        'Order id'
                    )
                    ->addColumn(
                        'token',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        '',
                        [],
                        'token'
                    )
                    ->setComment('Token Table');

                $installer->getConnection()->createTable($table);
            }
        }

        $installer->endSetup();
    }
}