<?php
namespace Clicksend\Sms\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
		
        if (!$context->getVersion()) {
			 $orderTable = $installer->getTable('sales_order');
             $installer->getConnection()->addColumn(
                $orderTable,
                'is_clicksend_send',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length'    => 1,
                    'comment' => 'Flag for Clicksend is sent on new order'
                ]
            );
        }
        $installer->endSetup();
    }
}
