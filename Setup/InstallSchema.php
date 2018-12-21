<?php
/**
 * Module to import/update products
 * Copyright (C) 2018  John Park
 * 
 * This file is part of Onlinepromo/Sync.
 * 
 * Onlinepromo/Sync is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Onlinepromo\Sync\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
		$setup->startSetup();
        $this->createSyncProductTable($setup);
        $this->createSyncProductControlTable($setup);
		$setup->endSetup();
    }

    /**
     * Process the Sync Product table creation
     * @SuppressWarnings(PHPMD)
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup The Setup
     *
     * @throws \Zend_Db_Exception
     */
    private function createSyncProductTable(SchemaSetupInterface $setup)
    {
		if (!$setup->tableExists('sync_products')) {
			$table = $setup->getConnection()->newTable(
				$setup->getTable('sync_products')
			)
				->addColumn(
					'sku',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'SKU'
				)
				->addColumn(
					'product_name',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable => false'],
					'Product Name'
				)
				->addColumn(
					'short_description',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'64k',
					[],
					'short_description'
				)
				->addColumn(
					'long_description',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'64k',
					[],
					'long_description'
				)
				->addColumn(
					'attribute_set_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					'9999999',
					[],
					'attribute set id'
				)
				->addColumn(
					'status',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					'1',
					[],
					'Status'
				)
				->addColumn(
					'visibility',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					'1',
					[],
					'visibility'
				)
				->addColumn(
					'tax_class_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					'1',
					[],
					'tax_class_id'
				)
				->addColumn(
					'price',
					\Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
					'12,4',
					[],
					'price'
				)
				->setComment('Sync Product Table');

			$setup->getConnection()->createTable($table);

			$setup->getConnection()->addIndex(
				$setup->getTable('sync_products'),
				$setup->getIdxName(
					$setup->getTable('sync_products'),
					['sku'],
					\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
				),
				['sku'],
				\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
			);	
		}
    }

    /**
     * Process the Sync Product Control table creation
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup The Setup
     *
     * @throws \Zend_Db_Exception
     */
    private function createSyncProductControlTable(SchemaSetupInterface $setup)
    {
		if (!$setup->tableExists('sync_control_products')) {
			$table = $setup->getConnection()->newTable(
				$setup->getTable('sync_control_products')
			)
				->addColumn(
					'sku',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'SKU'
				)
				->addColumn(
					'control_hash',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable => false'],
					'Control hash'
				)
	            ->addColumn(
	                'updated_at',
	                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
	                null,
	                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
	                'Update Time'
	            )           
				->setComment('Sync Product Control Table');

			$setup->getConnection()->createTable($table);
			
			$setup->getConnection()->addIndex(
				$setup->getTable('sync_control_products'),
				$setup->getIdxName(
					$setup->getTable('sync_control_products'),
					['sku'],
					\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
				),
				['sku'],
				\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
			);			

		}
    }

}
