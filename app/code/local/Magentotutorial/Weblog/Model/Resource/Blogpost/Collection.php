<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/5/29
 * Time: 13:57
 */
class Magentotutorial_Weblog_Model_Resource_Blogpost_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {
    protected function _construct()
    {
        $this->_init('weblog/blogpost');
    }
}