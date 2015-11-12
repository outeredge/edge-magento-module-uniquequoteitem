<?php

require_once(Mage::getModuleDir('controllers','Mage_Checkout').DS.'CartController.php');

class Edge_UniqueQuoteItem_CartController extends Mage_Checkout_CartController
{
    public function deleteAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $item = $this->_getQuote()->getItemById($id);
                $similarItems = Mage::helper('uniquequoteitem')->getSimilarItems($item);
                foreach ($similarItems as $similarItem) {
                    $this->_getCart()->removeItem($similarItem->getItemId());
                }
                $this->_getCart()->save();
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Cannot remove the item.'));
                Mage::logException($e);
            }
        }
        $this->_redirectReferer(Mage::getUrl('*/*'));
    }
}
