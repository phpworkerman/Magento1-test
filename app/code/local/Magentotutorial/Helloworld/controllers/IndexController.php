<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/5/28
 * Time: 15:52
 */
class Magentotutorial_Helloworld_IndexController extends Mage_Core_Controller_Front_Action {
    public function indexAction() {
        //echo 'Hello World';
        $this->loadLayout();
        $this->renderLayout();
    }
    public function goodbyeAction() {
        //echo 'Goodbye World!';
        $this->loadLayout();
        $this->renderLayout();
    }
    public function paramsAction() {
        echo '';
        foreach($this->getRequest()->getParams() as $key=>$value) {
            echo 'Param: '.$key.'';
            echo 'Value: '.$value.'';
        }
        echo '';
    }

    public function getModelAction(){
        $params = $this->getRequest()->getParams();
        $blogpost = Mage::getModel('helloworld/blog');
        $blogpost->load('1');
        $data = $blogpost->getData('title');
        $dataOri = $blogpost->getOrigData('title');
        $blogpost->setTitle('改名字了');
        $blogpost->save();
        var_dump($dataOri);
        var_dump($blogpost->getBlogId());
        echo '<br><br>';

        $test = Mage::getModel('helloworld/testpost');
        $test->load('1');
        $data1 = $test->getData('title');
        var_dump($data1);
    }

    public function getCollectionAction(){
        $blog = Mage::getModel('helloworld/blog')->getCollection();
        foreach($blog as $value){
            echo $value->getTitle().'<br>';
        }
    }

    public function getEavModelAction(){
        $eavblog = Mage::getModel('helloworld/eavblog');
        $eavblog->load('1');
        var_dump($eavblog);
    }
}