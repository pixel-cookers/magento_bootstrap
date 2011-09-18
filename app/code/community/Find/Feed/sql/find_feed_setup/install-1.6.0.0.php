<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    
 * @package     _storage
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();
/**
 * Create table 'find_feed/feed_import_codes'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('find_feed/feed_import_codes'))
    ->addColumn('code_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Code id')
    ->addColumn('import_code', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => false,
        ), 'Import type')
    ->addColumn('eav_code', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => false,
        ), 'EAV code')
    ->addColumn('is_imported', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        ), 'Is imported')
    ->setComment('Find feed import codes');
$installer->getConnection()->createTable($table);

$this->addAttribute('catalog_product', 'is_imported', array(
    'group'                    => 'General',
    'type'                     => 'int',
    'input'                    => 'select',
    'label'                    => 'In feed',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'is_configurable'          => 0,
    'source'                   => 'eav/entity_attribute_source_boolean',
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'user_defined'             => false,
    'is_user_defined'          => false,
    'used_in_product_listing'  => true
));

$installer->endSetup();
