<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/5/29
 * Time: 14:58
 */
$installer = $this;
$installer->startSetup();
$installer->getConnection()
    ->changeColumn($installer->getTable('weblog/blogpost'), 'post', 'post', array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'nullable' => false,
            'comment' => 'Blogpost Body'
        )
    );
$installer->endSetup();
