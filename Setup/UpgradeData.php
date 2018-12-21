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

use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;

class UpgradeData implements UpgradeDataInterface
{

    /**
     * Eav setup factory
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
    * Init
    *
    * @param EavSetupFactory $eavSetupFactory
    */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD)
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), "1.0.2", "<")) {
            
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

            $eavSetup->addAttribute(
                Product::ENTITY,
                'package_contents',
                [
                    'type' => 'text',
                    'label' => 'Package Contents',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'input' => 'textarea',
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => true,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'group' => 'Content',
                    'is_wysiwyg_enabled' => TRUE,
                    'apply_to' => 'simple,configurable,bundle'
                ]
            );

            $attributeSetId = $eavSetup->getAttributeSetId(Product::ENTITY, 'Default');
            $attributeGroupId = $eavSetup->getAttributeGroupId(Product::ENTITY, $attributeSetId, 'Content');
            $attributeId = $eavSetup->getAttributeId(Product::ENTITY, 'package_contents');

            $eavSetup->addAttributeToGroup(
                Product::ENTITY,
                $attributeSetId,
                $attributeGroupId,
                $attributeId
            );

            $eavSetup->addAttributeToSet(
                Product::ENTITY,
                $attributeSetId,
                $attributeGroupId,
                $attributeId
            );


        }
    }
}
