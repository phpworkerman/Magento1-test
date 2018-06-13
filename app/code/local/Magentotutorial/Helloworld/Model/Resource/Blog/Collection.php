<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/13 0013
 * Time: 16:00
 */
class Magentotutorial_Helloworld_Model_Resource_Blog_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {
    protected function _construct()
    {
        $this->_init('helloworld/blog');
    }
}