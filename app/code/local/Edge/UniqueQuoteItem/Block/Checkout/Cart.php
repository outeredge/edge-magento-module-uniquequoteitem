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
            Mage::helper('uniquequoteitem')->joinLineItems($items);
        }
        return $items;
    }
}