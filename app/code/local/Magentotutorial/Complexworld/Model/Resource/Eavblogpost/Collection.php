<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/5/30
 * Time: 9:54
 */
class Magentotutorial_Complexworld_Model_Resource_Eavblogpost_Collection extends Mage_Eav_Model_Entity_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('complexworld/eavblogpost');
    }
}