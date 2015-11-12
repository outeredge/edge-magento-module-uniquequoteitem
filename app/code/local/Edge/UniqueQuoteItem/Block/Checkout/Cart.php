<?php

class Edge_UniqueQuoteItem_Block_Checkout_Cart extends Mage_Checkout_Block_Cart
{
    public function getItems()
    {
        if ($this->getCustomItems()) {
            return $this->getCustomItems();
        }
        $items = parent::getItems();
        if (!Mage::getStoreConfigFlag('uniquequoteitem/display/separate_line_items')) {
            $this->_joinLineItems($items);
        }
        return $items;
    }

    protected function _joinLineItems(&$items)
    {
        $joinedItems = array();
        foreach ($items as $item) {
            $keyString = Mage::helper('uniquequoteitem')->getItemKeyString($item);
            if (!isset($joinedItems[$keyString])) {
                $joinedItems[$keyString] = $item;
            } else {
                $joinedItems[$keyString]->setQty($joinedItems[$keyString]->getQty() + 1);
            }
        }
        $items = $joinedItems;
    }
}