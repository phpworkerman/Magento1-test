<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/13 0013
 * Time: 15:04
 */
class Magentotutorial_Helloworld_Model_Resource_Blog extends Mage_Core_Model_Resource_Db_Abstract{
    protected function _construct()
    {
        $this->_init('helloworld/blog', 'id');
    }
}