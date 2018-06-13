<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/13 0013
 * Time: 15:00
 */
class Magentotutorial_Helloworld_Model_Blog extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('helloworld/blog');
    }
}