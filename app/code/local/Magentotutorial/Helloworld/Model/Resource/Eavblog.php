<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/13 0013
 * Time: 17:36
 */
class Magentotutorial_Helloworld_Model_Resource_Eavblog extends Mage_Eav_Model_Entity_Abstract{
    protected function _construct(){
        $resource = Mage::getSingleton('core/resource');
        $this->setType('helloworld_eavblog');
        $this->setConnection(
            $resource->getConnection('helloworld-eav_read'),
            $resource->getConnection('helloworld-eav_write')
        );
    }
}