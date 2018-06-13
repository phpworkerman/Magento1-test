<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/5/28
 * Time: 15:52
 */
class Magentotutorial_Complexworld_IndexController extends Mage_Core_Controller_Front_Action {
    public function indexAction(){
        $thing_1 = new Varien_Object();
        $thing_1->setName('Richard');
        $thing_1->setAge(24);

        $thing_2 = new Varien_Object();
        $thing_2->setName('Jane');
        $thing_2->setAge(12);

        $thing_3 = new Varien_Object();
        $thing_3->setName('Spot');
        $thing_3->setLastName('The Dog');
        $thing_3->setAge(7);

        //var_dump($thing_1->getName());
        //var_dump($thing_3->getData());
        $collection_of_things = new Varien_Data_Collection();
        $collection_of_things
            ->addItem($thing_1)
            ->addItem($thing_2)
            ->addItem($thing_3);
        foreach($collection_of_things as $thing)
        {
            //echo '<pre>';
            //var_dump($thing->getData());
        }
        //var_dump($collection_of_things->getFirstItem()->getData());
        //var_dump($collection_of_things->getLastItem()->getData());
        //var_dump( $collection_of_things->toXml() );
        //var_dump($collection_of_things->getColumnValues('name'));
        echo '<pre>';
        var_dump($collection_of_things->getItemsByColumnValue('name','Spot'));
    }
    public function testAction()
    {
        $collection_of_products = Mage::getModel('catalog/product')->getCollection();
        $collection_of_products->addFieldToFilter('sku','n2610');
        echo "Our collection now has " . count($collection_of_products) . ' item(s)';
        var_dump($collection_of_products->getFirstItem()->getData());
    }

    public function populateEntriesAction() {
        for ($i=0;$i<10;$i++) {
            $weblog2 = Mage::getModel('complexworld/eavblogpost');
            $weblog2->setTitle('This is a test '.$i);
            $weblog2->setContent('This is test content '.$i);
            $weblog2->setDate(now());
            $weblog2->save();
        }

        echo 'Done';
    }

    public function showCollectionAction() {
        $weblog2 = Mage::getModel('complexworld/eavblogpost');
        $entries = $weblog2->getCollection()
            ->addAttributeToSelect('title')
            ->addAttributeToSelect('date')
            ->addAttributeToSelect('content');
        $entries->load();
        foreach($entries as $entry)
        {
            // var_dump($entry->getData());
            echo '<h2>' . $entry->getTitle() . '</h2>';
            echo '<p>Date: ' . $entry->getDate() . '</p>';
            echo '<p>' . $entry->getContent() . '</p>';
        }
        echo '</br>Done</br>';
    }


}