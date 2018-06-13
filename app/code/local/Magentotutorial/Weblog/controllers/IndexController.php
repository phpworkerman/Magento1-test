<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/5/29
 * Time: 10:05
 */
class Magentotutorial_Complexworld_IndexController extends Mage_Core_Controller_Front_Action {
    public function testModelAction() {
        $params = $this->getRequest()->getParams();
        $blogpost = Mage::getModel('weblog/blogpost');
        //echo("Loading the blogpost with an ID of ".$params['id']);
        $blogpost->load($params['id']);
        $data = $blogpost->setPost('123');
        $test = $blogpost->unsPost();
        $get = $blogpost->getPost();
        var_dump($get);
    }
    public function createNewPostAction() {
        $blogpost = Mage::getModel('weblog/blogpost');
        $blogpost->setTitle('Code Post!');
        $blogpost->setPost('This post was created from code!');
        $blogpost->save();
        echo 'post with ID ' . $blogpost->getId() . ' created';
    }
    public function showAllBlogPostsAction() {
        $posts = Mage::getModel('weblog/blogpost')->getCollection();
        foreach($posts as $blogpost){
            echo '<h3>'.$blogpost->getTitle().'</h3>';
            echo nl2br($blogpost->getPost());
        }
    }
}